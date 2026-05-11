jQuery(window).on('elementor/frontend/init', function () {

    if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
        var tag = document.createElement('script');
        tag.src = "https://www.youtube.com/iframe_api";
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }

    jQuery(document).on('click', '#ev-play-trigger', function(e) {
        e.preventDefault();
        var $widget = jQuery('.ev-player-wrapper').first();
        if ($widget.length && !$widget.hasClass('is-playing')) {
            $widget.trigger('click'); 
        }
    });

    elementorFrontend.hooks.addAction('frontend/element_ready/ev-audio-player.default', function ($scope) {
        
        var $wrapper = $scope.find('.ev-player-wrapper');
        var config = $wrapper.data('ev-config');
        
        if (!config) return;

        var isPlaying = false;
        var mode = config.mode; 
        var htmlAudio = null; 
        var ytPlayer = null;
        var fadeInterval = null;
        var wasPlayingBeforeHidden = false; 

        
        config.start = parseFloat(config.start) || 0;
        config.end = parseFloat(config.end) || 0;

        function init() {
            if (mode === 'file') {
                htmlAudio = $wrapper.find('.ev-html5-tag')[0];
                if (htmlAudio) {
                    
                    
                    if (config.fade) {
                        htmlAudio.volume = 0;
                        htmlAudio.muted = true;
                    }
                    htmlAudio.addEventListener('loadedmetadata', function() {
                        if (config.start > 0) htmlAudio.currentTime = config.start;
                    });

                    htmlAudio.addEventListener('play', onAudioPlay);
                    htmlAudio.addEventListener('pause', onAudioPause);
                    
                    
                    htmlAudio.addEventListener('timeupdate', function() {
                        if (config.end > 0 && htmlAudio.currentTime >= config.end) {
                            htmlAudio.currentTime = config.start;
                            htmlAudio.play(); 
                        }
                    });

                    
                    htmlAudio.addEventListener('ended', function() {
                        htmlAudio.currentTime = config.start;
                        htmlAudio.play(); 
                    });

                    
                    if (htmlAudio.readyState >= 1 && config.start > 0) {
                        htmlAudio.currentTime = config.start;
                    }
                }
            } else if (mode === 'youtube') {
                var interval = setInterval(function() {
                    if (typeof window.YT !== 'undefined' && window.YT.Player) {
                        clearInterval(interval);
                        var el = $wrapper.find('.ev-yt-frame')[0];
                        var vidId = getVidId(config.link);
                        if (el && vidId) {
                            ytPlayer = new YT.Player(el, {
                                height: '0', width: '0',
                                videoId: vidId,
                                playerVars: {
                                    'playsinline': 1, 'controls': 0, 'disablekb': 1,
                                    'start': parseInt(config.start), 
                                    'end': config.end > 0 ? parseInt(config.end) : undefined 
                                },
                                events: { 
                                    'onStateChange': onYtState 
                                }
                            });
                        }
                    }
                }, 500);
            }
        }
        
        init();

        $wrapper.on('click', function() {
            if (isPlaying) pause();
            else play();
        });

        function play() {
            if (mode === 'file' && htmlAudio) {
                if (config.fade) {
                    htmlAudio.volume = 0;
                    htmlAudio.muted = true;
                }
                htmlAudio.play();
            }
            else if (mode === 'youtube' && ytPlayer) {
                if (config.fade && typeof ytPlayer.setVolume === 'function') {
                    ytPlayer.setVolume(0);
                    if (typeof ytPlayer.mute === 'function') ytPlayer.mute();
                }
                ytPlayer.playVideo();
            }
        }

        function pause() {
            if (mode === 'file' && htmlAudio) htmlAudio.pause();
            else if (mode === 'youtube' && ytPlayer) ytPlayer.pauseVideo();
        }

        function onAudioPlay() {
            updateUI(true);
            if(config.fade) {
                if(htmlAudio.currentTime <= config.start + 0.5 || htmlAudio.volume === 0 || htmlAudio.volume === 1) {
                     htmlAudio.volume = 0;
                     fadeIn(htmlAudio);
                }
            } else {
                htmlAudio.volume = 1;
            }
        }

        function onAudioPause() {
            updateUI(false);
            clearInterval(fadeInterval);
        }

        function fadeIn(element) {
            var duration = config.fade_dur; 
            var step = 0.05; 
            var intervalTime = duration * step; 
            clearInterval(fadeInterval);
            element.muted = false;
            fadeInterval = setInterval(function() {
                if (element.volume < 1) {
                    var newVol = element.volume + step;
                    if(newVol > 1) newVol = 1;
                    element.volume = newVol;
                } else {
                    clearInterval(fadeInterval);
                }
            }, intervalTime);
        }
        
        function playYTWithFade() {
             if(config.fade) {
                 if (typeof ytPlayer.setVolume === 'function') {
                     ytPlayer.setVolume(0);
                     if (typeof ytPlayer.unMute === 'function') ytPlayer.unMute();
                 }
                 var duration = config.fade_dur;
                 var step = 5; 
                 var intervalTime = duration * (step/100);
                 clearInterval(fadeInterval);
                 fadeInterval = setInterval(function() {
                    var curVol = ytPlayer.getVolume();
                    if (curVol < 100) ytPlayer.setVolume(curVol + step);
                    else clearInterval(fadeInterval);
                 }, intervalTime);
             } else {
                 ytPlayer.setVolume(100);
             }
        }

        function updateUI(play) {
            isPlaying = play;
            var $toggleIcon = $wrapper.find('.ev-toggle-icon');
            if (play) {
                $wrapper.addClass('is-playing');
                $toggleIcon.removeClass('fa-play').addClass('fa-pause');
            } else {
                $wrapper.removeClass('is-playing');
                $toggleIcon.removeClass('fa-pause').addClass('fa-play');
            }
        }

        function onYtState(e) {
            if (e.data === YT.PlayerState.PLAYING) {
                updateUI(true);
                playYTWithFade();
            } else if (e.data === YT.PlayerState.PAUSED) {
                updateUI(false);
            } else if (e.data === YT.PlayerState.ENDED) {
                
                ytPlayer.seekTo(config.start);
                ytPlayer.playVideo();
            }
        }

        function getVidId(url) {
            if(!url) return false;
            var m = url.match(/^.*((youtu.be\/)|(v\/)|(\/u\/\w\/)|(embed\/)|(watch\?))\??v?=?([^#\&\?]*).*/);
            return (m && m[7].length == 11) ? m[7] : false;
        }

        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'hidden') {
                if (isPlaying) {
                    pause();
                    wasPlayingBeforeHidden = true;
                } else {
                    wasPlayingBeforeHidden = false;
                }
            } else {
                if (wasPlayingBeforeHidden) {
                    play();
                    wasPlayingBeforeHidden = false;
                }
            }
        });

        if ($wrapper.hasClass('ev-hide-on-scroll')) {
            var scrollTimeout;
            window.addEventListener('scroll', function(e) {
                // Ignore if the widget itself is internally scrolling
                if ($wrapper[0] === e.target || $wrapper[0].contains(e.target)) return;

                if (!$wrapper.hasClass('ev-is-scrolling')) {
                    $wrapper.addClass('ev-is-scrolling');
                }
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    $wrapper.removeClass('ev-is-scrolling');
                }, 500); 
            }, true); // Use capture phase to intercept ANY scrolling container
        }

    });
});