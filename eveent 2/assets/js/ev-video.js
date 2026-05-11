jQuery(window).on('elementor/frontend/init', function() {
    
    var EVVideoHandler = function( $scope, $ ) {
        
        var card = $scope.find('.ev-main-video-card')[0];
        if (!card) return;

        var playerEl = card.querySelector('.ev-player-el');
        var btnPlay = card.querySelector('.ev-btn-play');
        var btnStop = card.querySelector('.ev-btn-stop');
        var isMuteSetting = card.getAttribute('data-mute') === 'yes';

        function stopEveentAudio() {
            var audioTags = document.querySelectorAll('audio.ev-html5-tag');
            audioTags.forEach(function(audio) { if (!audio.paused) audio.pause(); });
            
            var toggleIcons = document.querySelectorAll('.ev-toggle-icon');
            toggleIcons.forEach(function(icon) {
                var w = icon.closest('.ev-player-wrapper');
                if(w) {
                    var ring = w.querySelector('.ev-spinner-ring');
                    var style = window.getComputedStyle(ring);
                    
                    if (style.animationPlayState === 'running' || icon.classList.contains('fa-pause')) {
                        icon.click();
                    }
                }
            });
        }

        function playEveentAudio() {
            var audioTags = document.querySelectorAll('audio.ev-html5-tag');
            audioTags.forEach(function(audio) { 
                audio.play().catch(e => console.log('Auto-resume blocked:', e)); 
            });
            var toggleIcons = document.querySelectorAll('.ev-toggle-icon');
            toggleIcons.forEach(function(icon) {
                if (icon.classList.contains('fa-play')) icon.click();
            });
        }

        function startVideo() {
            if (card.classList.contains('is-playing')) return;
            card.classList.add('is-playing');
            stopEveentAudio(); 

            if (playerEl.tagName === 'IFRAME') {
                var src = playerEl.getAttribute('data-src');
                var vType = playerEl.getAttribute('data-type'); 
                
                if (src) {
                   
                    if (vType === 'youtube') {
                        var separator = (src.indexOf('?') !== -1) ? '&' : '?';
                        var newSrc = src + separator + 'autoplay=1';
                        newSrc += (isMuteSetting) ? '&mute=1' : '&mute=0';
                        playerEl.src = newSrc;
                    } 
                    
                    else if (vType === 'gdrive') {
                        playerEl.src = src;
                    }
                    
                    var allowAttr = playerEl.getAttribute('allow') || '';
                    if (allowAttr.indexOf('autoplay') === -1) {
                        playerEl.setAttribute('allow', allowAttr + '; autoplay');
                    }
                }
            } else if (playerEl.tagName === 'VIDEO') {
                playerEl.play();
            }
        }

        function stopVideo() {
            if (!card.classList.contains('is-playing')) return;
            card.classList.remove('is-playing');
            playEveentAudio(); 

            if (playerEl.tagName === 'IFRAME') {
                playerEl.src = 'about:blank'; 
            } else if (playerEl.tagName === 'VIDEO') {
                playerEl.pause();
                playerEl.currentTime = 0;
            }
        }

        if (btnPlay) btnPlay.addEventListener('click', startVideo);
        if (btnStop) btnStop.addEventListener('click', stopVideo);

        if(playerEl && playerEl.tagName === 'VIDEO'){
            playerEl.addEventListener('ended', function() {
                card.classList.remove('is-playing');
                playEveentAudio();
            });
            playerEl.addEventListener('play', stopEveentAudio);
        }
    };

    elementorFrontend.hooks.addAction( 'frontend/element_ready/ev-video-card.default', EVVideoHandler );
});