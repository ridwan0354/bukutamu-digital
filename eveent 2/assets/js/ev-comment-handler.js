jQuery(function ($) {
    'use strict';

    function handleGuestName() {
        const guestNameInput = $('#ev-comment-author');
        if (!guestNameInput.length) return;

        const urlParams = new URLSearchParams(window.location.search);
        const guestNameFromUrl = urlParams.get('to');
        const isGroupInvite = urlParams.has('group');

        if (isGroupInvite) {
            return;
        }

        if (guestNameFromUrl) {
            const cleanedName = decodeURIComponent(guestNameFromUrl.replace(/\+/g, ' ')).trim();
            guestNameInput.val(cleanedName);
        }
    }
    handleGuestName();

    $('#ev-comment-sticker-trigger').on('click', function () {
        $('.ev-comment-sticker-modal-overlay').addClass('active');
    });

    $('body').on('click', '.ev-comment-sticker-modal-overlay', function (e) {
        if ($(e.target).is('.ev-comment-sticker-modal-overlay')) {
            $(this).removeClass('active');
        }
    });

    $('body').on('click', '.ev-comment-modal-sticker-option', function () {
        var $this = $(this);
        var stickerData = { type: $this.data('sticker-type'), value: $this.data('sticker-value') };
        
        $('#ev-comment-selected-sticker').val(JSON.stringify(stickerData));

        if (stickerData.type === 'icon') {
            $('#ev-comment-sticker-preview').html(`<i class="${stickerData.value}"></i>`);
        } else {
            $('#ev-comment-sticker-preview').html(`<img src="${stickerData.value}" width="100" height="100">`);
        }

        $('.ev-comment-sticker-modal-overlay').removeClass('active');
    });

    $('#ev-comment-content').on('focus', function () {
        $('#ev-comment-selected-sticker').val('');
        $('#ev-comment-sticker-preview').html('');
    });

    function updateLikeButtons() {
        var likedComments = JSON.parse(localStorage.getItem('ev_comment_liked_comments')) || [];
        $('.ev-rsvp-like-button').each(function () {
            var button = $(this);
            var commentId = button.data('comment-id');
            if (likedComments.includes(commentId)) {
                button.addClass('liked').prop('disabled', true);
            }
        });
    }

    function buildReplyHTML(replyObject) {
        var badgeText = 'Pemilik Acara'; 
        var avatarUrl = replyObject.avatar;
        
        return `
            <div class="ev-rsvp-public-reply">
                <div class="ev-rsvp-reply-avatar"><img src="${avatarUrl}" alt="" width="32" height="32"></div>
                <div class="ev-rsvp-reply-bubble">
                    <div class="ev-rsvp-reply-header">
                        <span class="ev-rsvp-reply-author">${replyObject.author}</span>
                        <span class="ev-rsvp-reply-badge">${badgeText}</span>
                    </div>
                    <div class="ev-rsvp-reply-body">${replyObject.content}</div>
                    <div class="ev-rsvp-reply-footer">
                        <time class="ev-rsvp-reply-comment-time">${replyObject.time_ago}</time>
                    </div>
                </div>
            </div>
        `;
    }

    function loadComments(page, retryCount) {
        var container = $('.ev-comment-list-container');
        if (!container.length) return;

        
        var wrapper = container.closest('.ev-comment-wrapper'); 
        
        var perPage = parseInt(container.data('per-page')) || 10;
        var link = container.data('link');
        retryCount = retryCount || 0;
        
        page = page || 1;
        if (!link) return;

        container.parent().find('.ev-rsvp-loader').show();
        
        
        wrapper.find('.ev-comment-pagination, .ev-rsvp-pagination-nav').remove(); 
       

        $.ajax({
            url: EvGlobal.ajaxurl,
            type: 'POST',
            dataType: 'json',
            timeout: 15000,
            data: {
                action: 'ev_get_comments',
                nonce: EvRSVP.nonce,
                post_id: new URLSearchParams(link).get('post_id'),
                paged: page,
                number: perPage,
                badge_present_text: wrapper.data('badge-present-text'),
                badge_notpresent_text: wrapper.data('badge-notpresent-text'),
                badge_notsure_text: wrapper.data('badge-notsure-text'),
            },
            success: function (response) {
                container.parent().find('.ev-rsvp-loader').hide();
                wrapper.find('.ev-comment-pagination, .ev-rsvp-pagination-nav').remove();

                if (response.success && response.data.comments.length > 0) {
                    var tempHTML = '';
                    var renderedAny = false;
                    $.each(response.data.comments, function (index, comment) {
                        var html = buildCommentHTML(comment);
                        if (html !== '') {
                            tempHTML += html;
                            renderedAny = true;
                        }
                    });

                    if (renderedAny) {
                        container.empty();
                        container.append(tempHTML);
                        updateLikeButtons();

                        var meta = response.data.meta;
                        var currentPage = parseInt(meta.current_page);
                        var total = parseInt(meta.total) || 0;
                        var maxPages = Math.max(1, Math.ceil(total / perPage));

                        // Always sync count from server (bypasses page cache)
                        var countEl = wrapper.find('.ev-rsvp-count');
                        if (countEl.length && total > 0) {
                            countEl.text(total);
                        }

                        if (maxPages > 1) {
                            var navHTML = '<div class="ev-comment-pagination ev-rsvp-pagination-nav">';
                            
                            // Prev 
                            if (currentPage > 1) {
                                navHTML += '<button class="ev-rsvp-page-btn prev" type="button" onclick="window.evLoadCommentPage(' + (currentPage - 1) + ')" aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg></button>';
                            } else {
                                navHTML += '<button class="ev-rsvp-page-btn prev disabled" disabled aria-label="Halaman sebelumnya"><svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg></button>';
                            }

                            // Smart ellipsis
                            var pages = [];
                            if (maxPages <= 7) {
                                for (var i = 1; i <= maxPages; i++) pages.push(i);
                            } else {
                                pages.push(1);
                                if (currentPage > 3) pages.push('...');
                                var s = Math.max(2, currentPage - 1);
                                var e = Math.min(maxPages - 1, currentPage + 1);
                                for (var i = s; i <= e; i++) pages.push(i);
                                if (currentPage < maxPages - 2) pages.push('...');
                                pages.push(maxPages);
                            }

                            for (var p = 0; p < pages.length; p++) {
                                if (pages[p] === '...') {
                                    navHTML += '<span class="ev-rsvp-page-ellipsis">…</span>';
                                } else {
                                    var activeClass = pages[p] === currentPage ? ' active' : '';
                                    navHTML += '<button class="ev-rsvp-page-btn page-num' + activeClass + '" type="button" onclick="window.evLoadCommentPage(' + pages[p] + ')">' + pages[p] + '</button>';
                                }
                            }

                            // Next
                            if (currentPage < maxPages) {
                                navHTML += '<button class="ev-rsvp-page-btn next" type="button" onclick="window.evLoadCommentPage(' + (currentPage + 1) + ')" aria-label="Halaman selanjutnya"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"></polyline></svg></button>';
                            } else {
                                navHTML += '<button class="ev-rsvp-page-btn next disabled" disabled aria-label="Halaman selanjutnya"><svg viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"></polyline></svg></button>';
                            }
                            
                            navHTML += '</div>';
                            container.after(navHTML);
                        }
                    } else {
                        showEmptyFeedbackAndRestorePagination(page);
                    }
                } else {
                    if (page === 1) {
                         // Sync count to 0 (bypasses page cache)
                         var countEl = wrapper.find('.ev-rsvp-count');
                         if (countEl.length) countEl.text('0');

                         container.empty().html(`
                            <div class="ev-rsvp-no-comments combined">
                                <div class="icon-wrapper"><i class="fas fa-envelope-open-text"></i></div>
                                <h3>Belum Ada Ucapan</h3>
                                <p>Jadilah yang pertama memberikan Ucapan.</p>
                                <button class="cta-button" onclick="document.getElementById('ev-comment-content').focus()">Tulis Ucapan</button>
                            </div>
                        `);
                    } else {
                        showEmptyFeedbackAndRestorePagination(page);
                    }
                }
            },
            error: function() {
                // Auto-retry once before showing error (handles stale nonce from cached pages)
                if (retryCount < 1) {
                    setTimeout(function() {
                        loadComments(page, retryCount + 1);
                    }, 1500);
                    return;
                }
                // Don't overwrite server-rendered comments if they exist
                var hasExistingComments = container.find('.ev-rsvp-comment-item-wrapper').length > 0;
                if (!hasExistingComments) {
                    container.html('<p style="text-align:center; color:red;">Gagal memuat komentar.</p>');
                }
                container.parent().find('.ev-rsvp-loader').hide();
            }
        });

        // Helper to solve the empty next page UX issue
        function showEmptyFeedbackAndRestorePagination(failedPage) {
            Swal.fire({
                icon: 'info',
                title: 'Selesai',
                text: 'Tidak ada ucapan berikutnya yang tersedia.',
                timer: 2000,
                showConfirmButton: false,
                customClass: { popup: 'ev-rsvp-swal' }
            });
            
            // Go back internally to render the last valid page
            var goBackTo = Math.max(1, failedPage - 1);
            if (goBackTo > 0) {
                setTimeout(function() {
                     window.evLoadCommentPage(goBackTo);
                }, 500);
            }
        }
    }
    
    window.evLoadCommentPage = loadComments;
    loadComments(1);

    function buildCommentHTML(comment) {
        var stickerHTML = '';
        if (comment.sticker_data && typeof comment.sticker_data === 'object') {
            stickerHTML = `<div class="ev-rsvp-comment-sticker">`;
            if (comment.sticker_data.type === 'svg') {
                stickerHTML += `<img src="${comment.sticker_data.value}" class="ev-rsvp-sticker-image">`;
            } else {
                stickerHTML += `<i class="${comment.sticker_data.value}"></i>`;
            }
            stickerHTML += `</div>`;
        }

        var wrapper = $('.ev-comment-wrapper');
        var enablePublicReply = wrapper.data('public-reply');
        
        var bodyHTML = '';
        var tempDiv = document.createElement("div");
        tempDiv.innerHTML = comment.content;
        var cleanText = tempDiv.textContent || tempDiv.innerText || "";

        if (comment.content && cleanText.trim() !== '') {
            bodyHTML = `<div class="ev-rsvp-comment-body">${comment.content}</div>`;
        }

        var repliesHTML = '<div class="ev-rsvp-replies-wrapper">';
        if (comment.replies) {
            $.each(comment.replies, function (i, reply) {
                repliesHTML += buildReplyHTML(reply);
            });
        }
        repliesHTML += '</div>';

        return `
            <div class="ev-rsvp-comment-item-wrapper" id="comment-item-${comment.comment_id}">
                <div class="ev-rsvp-comment-item">
                    <div class="ev-rsvp-comment-avatar ev-rsvp-gravatar-avatar"><img src="${comment.avatar}" width="40"></div>
                    <div class="ev-rsvp-comment-content-wrapper">
                        <div class="ev-rsvp-comment-header">
                            <div class="ev-rsvp-author-meta">
                                <span class="ev-rsvp-comment-author">${comment.author}</span>
                                ${comment.attendance_tag}
                            </div>
                            <div class="ev-rsvp-header-actions">
                                ${enablePublicReply === 'yes' ? `<button class="ev-rsvp-reply-button" data-comment-id="${comment.comment_id}">Balas</button>` : ''}
                            </div>
                        </div>
                        ${stickerHTML}
                        ${bodyHTML}
                        <div class="ev-rsvp-comment-footer">
                            <time class="ev-rsvp-comment-time">${comment.time_ago}</time>
                            <button class="ev-rsvp-like-button" data-comment-id="${comment.comment_id}">
                                <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                <span class="ev-rsvp-like-count">${comment.like_count}</span>
                            </button>
                        </div>
                    </div>
                </div>
                ${repliesHTML}
            </div>`;
    }

    $('.ev-comment-list-container').on('click', '.ev-rsvp-like-button', function () {
        var button = $(this);
        var commentId = button.data('comment-id');

        if (button.prop('disabled')) return;
        button.prop('disabled', true);

        var likedComments = JSON.parse(localStorage.getItem('ev_comment_liked_comments')) || [];
        if (likedComments.includes(commentId)) return;

        button.addClass('liking').addClass('liked');
        var countSpan = button.find('.ev-rsvp-like-count');
        countSpan.text(parseInt(countSpan.text()) + 1);

        likedComments.push(commentId);
        localStorage.setItem('ev_comment_liked_comments', JSON.stringify(likedComments));

        $.ajax({
            url: EvGlobal.ajaxurl,
            type: 'POST',
            data: {
                action: 'ev_rsvp_toggle_like',
                nonce: EvRSVP.nonce,
                comment_id: commentId,
                liked: false
            },
            complete: function () {
                button.removeClass('liking');
            }
        });
    });

    var replyModalHTML = `
    <div class="ev-comment-reply-modal-overlay ev-rsvp-reply-modal-overlay">
        <div class="ev-rsvp-reply-modal">
            <h4>Pesan Balasan</h4>
            <div class="ev-rsvp-modal-error"></div>
            <form id="ev-comment-public-reply-form">
                <div class="ev-rsvp-field">
                    <label>Nama Anda</label>
                    <input type="text" id="ev-comment-reply-author" required>
                </div>
                <div class="ev-rsvp-field">
                    <label>Pesan</label>
                    <textarea id="ev-comment-reply-content" rows="3" required></textarea>
                </div>
                <div class="ev-rsvp-field">
                    <label>Password</label>
                    <input type="password" id="ev-comment-reply-password" required>
                </div>
                <div class="ev-rsvp-reply-modal-actions">
                    <button type="button" class="ev-comment-modal-cancel ev-rsvp-modal-cancel">Batal</button>
                    <button type="submit" class="ev-comment-modal-submit ev-rsvp-modal-submit">Kirim Balasan</button>
                </div>
            </form>
        </div>
    </div>`;
    
    if ($('.ev-comment-reply-modal-overlay').length === 0) {
        $('body').append(replyModalHTML);
    }

    var currentReplyParentId = 0;

    $('.ev-comment-list-container').on('click', '.ev-rsvp-reply-button', function (e) {
        e.preventDefault();
        currentReplyParentId = $(this).data('comment-id');
        $('.ev-comment-reply-modal-overlay').addClass('active');
    });

    $('body').on('click', '.ev-comment-modal-cancel, .ev-comment-reply-modal-overlay', function (e) {
        if ($(e.target).is('.ev-comment-modal-cancel') || $(e.target).is('.ev-comment-reply-modal-overlay')) {
            $('.ev-comment-reply-modal-overlay').removeClass('active');
            $('#ev-comment-public-reply-form').trigger('reset');
            $('.ev-rsvp-modal-error').hide();
        }
    });

    $('#ev-comment-public-reply-form').on('submit', function (e) {
        e.preventDefault();
        var button = $(this).find('.ev-comment-modal-submit');
        var errorDiv = $(this).siblings('.ev-rsvp-modal-error');
        var originalText = button.text();
        
        button.prop('disabled', true).text('Mengirim...'); 
        errorDiv.hide();

        var postId = $('input[name="comment_post_ID"]').val(); 

        $.ajax({
            url: EvGlobal.ajaxurl, 
            type: 'POST', 
            dataType: 'json', 
            data: {
                action: 'ev_insert_public_reply', 
                nonce: EvRSVP.nonce, 
                post_id: postId,
                parent_id: currentReplyParentId, 
                password: $('#ev-comment-reply-password').val(),
                author_name: $('#ev-comment-reply-author').val(), 
                reply_content: $('#ev-comment-reply-content').val()
            },
            success: function (response) {
                if (response.success) {
                    var newReplyHTML = buildReplyHTML(response.data);
                    var newReply = $(newReplyHTML); 
                    newReply.css('opacity', 0);
                    
                    $('#comment-item-' + currentReplyParentId).find('.ev-rsvp-replies-wrapper').append(newReply);
                    newReply.animate({ opacity: 1 }, 500);
                    
                    $('.ev-comment-reply-modal-overlay').removeClass('active');
                    $('#ev-comment-public-reply-form').trigger('reset');
                } else { 
                    errorDiv.text(response.data.message || 'Terjadi kesalahan.').show(); 
                }
            },
            error: function () { errorDiv.text('Gagal menghubungi server.').show(); },
            complete: function () { button.prop('disabled', false).text(originalText); }
        });
    });

    $('#ev-comment-form').on('submit', function (e) {
        e.preventDefault();
        var form = $(this);
        var button = form.find('button[type="submit"]');
        
        button.prop('disabled', true).addClass('is-loading');

        var formData = form.serializeArray();
        formData.push({ name: 'action', value: 'ev_insert_comment' });
        formData.push({ name: 'nonce', value: EvRSVP.nonce });
        formData.push({ name: 'commentpress', value: '1' });
        formData.push({ name: 'attendance', value: '' }); 
        
        formData.push({ name: 'enable_wa_notice', value: form.data('enable-wa-notice') });
        formData.push({ name: 'wa_notice_name', value: form.data('wa-notice-name') });
        formData.push({ name: 'wa_notice_number', value: form.data('wa-notice-number') });
        formData.push({ name: 'wa_template', value: form.data('wa-template') });

        $.ajax({
            url: EvGlobal.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: $.param(formData),
            success: function (response) {
                if (response.success) {
                    form.closest('.ev-rsvp-form-wrapper').slideUp();

                    
                    var swalClass = form.data('swal-class');
                    Swal.fire({ 
                        icon: 'success', 
                        title: 'Terkirim', 
                        text: 'Ucapan Anda berhasil dikirim!', 
                        timer: 2000, 
                        timerProgressBar: true, 
                        showConfirmButton: false, 
                        customClass: { popup: 'ev-rsvp-swal ' + swalClass }
                    });
                    
                    form.trigger('reset');
                    $('#ev-comment-sticker-preview').html('');
                    
                    var existingItems = $("[id='comment-item-" + response.data.comment_id + "']");
                    var listContainer = $('.ev-rsvp-list-container, .ev-comment-list-container');
                    var newCommentHTML = buildCommentHTML(response.data);
                    
                    if (existingItems.length > 0) {
                        existingItems.replaceWith(newCommentHTML);
                    } else {
                        var newCommentEl = $(newCommentHTML);
                        newCommentEl.css('opacity', 0);
                        listContainer.prepend(newCommentEl);
                        newCommentEl.animate({ opacity: 1 }, 500);
                        
                        var countEl = $('.ev-rsvp-count');
                        var currentCount = parseInt(countEl.text()) || 0;
                        countEl.text(currentCount + 1);
                    }
                    
                } else {
                    
                    Swal.fire({ 
                        icon: 'error', 
                        title: 'Gagal', 
                        text: response.data.message || 'Terjadi kesalahan',
                        timer: 3000, 
                        timerProgressBar: true,
                        showConfirmButton: false 
                    });
                    button.prop('disabled', false).removeClass('is-loading');
                }
            },
            error: function() {
                
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Error', 
                    text: 'Terjadi kesalahan jaringan.',
                    timer: 3000, 
                    timerProgressBar: true,
                    showConfirmButton: false 
                });
                button.prop('disabled', false).removeClass('is-loading');
            }
        });
    });
});