class EvGuestbookLiteHandler extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
        return {
            selectors: {
                container: '.ev-lite-guestbook-container',
                tabs: '.ev-lite-tab',
                panes: '.ev-lite-tab-pane',
                addForm: '#ev-lite-add-form',
                toggleAddBtn: '#lite-toggle-add-form',
                addFormWrap: '#lite-add-form-wrap',
                btnSearch: '#lite-btn-search',
                inputSearch: '#lite-guest-search',
                statTotal: '#lite-stat-total',
                statOpened: '#lite-stat-opened',
                statShared: '#lite-stat-shared',
                statNotShared: '#lite-stat-not-shared',
                statRsvpHadir: '#lite-stat-rsvp-hadir',
                statRsvpTidak: '#lite-stat-rsvp-tidak',
                statRsvpRagu: '#lite-stat-rsvp-ragu',
                statCheckin: '#lite-stat-checkin'
            }
        };
    }

    getDefaultElements() {
        const selectors = this.getSettings('selectors');
        return {
            $container: this.$element.find(selectors.container),
            $tabs: this.$element.find(selectors.tabs),
            $panes: this.$element.find(selectors.panes),
            $addForm: this.$element.find(selectors.addForm),
            $toggleAddBtn: this.$element.find(selectors.toggleAddBtn),
            $addFormWrap: this.$element.find(selectors.addFormWrap),
            $btnSearch: this.$element.find(selectors.btnSearch),
            $inputSearch: this.$element.find(selectors.inputSearch),
            $statTotal: this.$element.find(selectors.statTotal),
            $statOpened: this.$element.find(selectors.statOpened),
            $statShared: this.$element.find(selectors.statShared),
            $statNotShared: this.$element.find(selectors.statNotShared),
            $statRsvpHadir: this.$element.find(selectors.statRsvpHadir),
            $statRsvpTidak: this.$element.find(selectors.statRsvpTidak),
            $statRsvpRagu: this.$element.find(selectors.statRsvpRagu),
            $statCheckin: this.$element.find(selectors.statCheckin)
        };
    }

    // ─── In-memory API Cache ────────────────────────────────
    _getCacheKey(route, data) {
        return route + ':' + JSON.stringify(data || {});
    }
    _getCached(route, data) {
        if (!this._apiCache) return null;
        const key = this._getCacheKey(route, data);
        const entry = this._apiCache[key];
        if (!entry) return null;
        if (Date.now() - entry.ts > entry.ttl) {
            delete this._apiCache[key];
            return null;
        }
        return entry.data;
    }
    _setCache(route, data, result, ttlMs) {
        if (!this._apiCache) this._apiCache = {};
        const key = this._getCacheKey(route, data);
        this._apiCache[key] = { data: result, ts: Date.now(), ttl: ttlMs };
    }
    _clearCache(route) {
        if (!this._apiCache) return;
        Object.keys(this._apiCache).forEach(k => {
            if (k.startsWith(route + ':')) delete this._apiCache[k];
        });
    }
    // ───────────────────────────────────────────────────────

    bindEvents() {
        this.elements.$tabs.on('click', this.onTabClick.bind(this));
        
        if (this.elements.$addForm.length) {
            this.elements.$addForm.on('submit', this.onAddSubmit.bind(this));
        }
        
        if (this.elements.$toggleAddBtn.length) {
            this.elements.$toggleAddBtn.on('click', () => {
                this.elements.$addFormWrap.slideToggle(300);
                this.elements.$toggleAddBtn.toggleClass('open');
            });
        }

        if (this.elements.$btnSearch.length) {
            this.elements.$btnSearch.on('click', () => this.fetchGuests());
            this.elements.$inputSearch.on('keypress', (e) => {
                if (e.which === 13) this.fetchGuests();
            });
        }

        // WA Template selector → fill textarea
        const $waSelect = this.$element.find('#lite-wa-template-select');
        const $waText = this.$element.find('#lite-wa-template-text');
        $waSelect.on('change', function() {
            const $selected = jQuery(this).find(':selected');
            const content = $selected.data('content') || '';
            $waText.val(content);
        });

        // Info tooltip toggle
        const $infoToggle = this.$element.find('#lite-wa-info-toggle');
        const $tooltip = this.$element.find('#lite-wa-tooltip');
        $infoToggle.on('click', function(e) {
            e.stopPropagation();
            $tooltip.toggle();
        });
        jQuery(document).on('click', () => $tooltip.hide());

        // Filter by type in guest list
        this.$element.find('#lite-guest-type-filter').on('change', () => this.fetchGuests());

        // Global delegate for dynamic buttons
        this.$element.on('click', '.lite-action-delete', this.onDeleteGuest.bind(this));
        this.$element.on('click', '.lite-action-edit', this.onEditGuest.bind(this));
        this.$element.on('click', '.lite-action-copy-link', this.onCopyLink.bind(this));
        this.$element.on('click', '.lite-action-copy-text', this.onCopyText.bind(this));
        this.$element.on('click', '.ev-lite-dropdown-toggle', this.toggleDropdown.bind(this));
        this.$element.on('click', '.lite-action-wa-share', this.onWaShare.bind(this));

        // ── Login screen events ──────────────────────────────
        this.$element.on('click', '#ev-login-submit', this.verifyPasskey.bind(this));
        this.$element.on('keypress', '#ev-login-passkey, #ev-login-slug', (e) => {
            if (e.which === 13) this.verifyPasskey();
        });
        this.$element.on('click', '#ev-lite-logout', this.doLogout.bind(this));
        this.$element.on('click', '.ev-lite-toggle-pass', (e) => {
            const $input = this.$element.find('#ev-login-passkey');
            const isPass = $input.attr('type') === 'password';
            $input.attr('type', isPass ? 'text' : 'password');
            jQuery(e.currentTarget).text(isPass ? 'Sembunyikan' : 'Lihat');
        });

        // Close dropdowns when clicking outside
        jQuery(document).on('click', (e) => {
            if (!jQuery(e.target).closest('.ev-lite-dropdown-wrapper').length) {
                this.$element.find('.ev-lite-dropdown-menu').removeClass('show');
            }
        });
    }

    async onWaShare(e) {
        const $btn = jQuery(e.currentTarget);
        const guestId = $btn.data('guest-id');
        const waUrlEncoded = $btn.data('wa-url');
        const waUrl = decodeURIComponent(waUrlEncoded);

        // Immediately update UI badge to "Dibagikan"
        const $card = this.$element.find(`.ev-lite-accordion-card[data-guest-id="${guestId}"]`);
        $card.find('.ev-lite-detail-row').each(function() {
            const $label = jQuery(this).find('.ev-detail-label');
            if ($label.text().trim() === 'Dibagikan') {
                jQuery(this).find('.ev-lite-badge').removeClass('belum bg-yellow-50 text-yellow-800').addClass('hadir bg-green-50 text-green-800').text('Dibagikan');
            }
        });

        // Open WA in new tab immediately
        window.open(waUrl, '_blank');

        // Fire-and-forget: mark as shared in API (don't block UX)
        try {
            await this.sendProxyRequest('share', { guest_id: guestId }, false);
            this._clearCache();
        } catch (err) {
            console.warn('WA share tracking failed:', err);
        }
    }


    onInit() {
        super.onInit();
        this._apiCache = {};
        this.postId = this.elements.$container.data('post-id');
        this.currentWaitTemplate = '';
        // Read ?id= URL param for dynamic event mode
        const urlParams = new URLSearchParams(window.location.search);
        this.urlSlug = urlParams.get('id') || '';
        
        const slugTag = this.elements.$container.data('slug-hint');
        const passkeyTag = this.elements.$container.data('passkey-hint');
        
        if (slugTag && passkeyTag) {
            this.session = { slug: slugTag, passkey: passkeyTag, ts: Date.now() };
        } else {
            // Load session if exists
        try {
            const rawSession = sessionStorage.getItem('ev_lite_auth_' + this.postId);
            if (rawSession) {
                const parsed = JSON.parse(rawSession);
                if (Date.now() - parsed.ts < 8 * 3600 * 1000) {
                    this.session = parsed;
                } else {
                    sessionStorage.removeItem('ev_lite_auth_' + this.postId);
                }
            }
        } catch (e) {}
        }

        this.checkSession();
    }

    checkSession() {
        const $loginScreen = this.$element.find('.ev-lite-login-screen');
        const $dashboard = this.$element.find('.ev-lite-dashboard');
        
        // Hide slug input early if URL param is used
        if (this.urlSlug) {
            this.$element.find('#ev-login-slug').closest('.ev-lite-login-field').hide();
        }

        // Show login securely in Elementor editor mode for visual editing, or if not logged in
        if (!this.session) {
            $dashboard.hide();
            $loginScreen.fadeIn();
            
            // If in Elementor editor, stop here so we see the login UI
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.isEditMode()) {
                return;
            }
        } else {
            // User is logged in
            $loginScreen.hide();
            $dashboard.fadeIn();
            
            // Hide logout button if passkey is injected dynamically (they can't "logout" of a dynamic tag)
            if (this.elements.$container.data('passkey-hint')) {
                 this.$element.find('#ev-lite-logout').hide();
            }

            if (this.postId) {
                this.fetchStats().then(() => this.fetchGuests());
            }
        }
    }

    async verifyPasskey(e) {
        if(e) e.preventDefault();
        
        const $slugInput = this.$element.find('#ev-login-slug');
        const $passInput = this.$element.find('#ev-login-passkey');
        const $errorMsg = this.$element.find('#ev-login-error');
        const $btn = this.$element.find('#ev-login-submit');

        const slugHint = this.elements.$container.data('slug-hint');
        const slug = slugHint || $slugInput.val() || this.urlSlug;
        const passkey = $passInput.val();

        if (!passkey || (!slug && !slugHint)) {
            $errorMsg.text('Harap isi ' + (slugHint ? 'Passkey' : 'Slug Event dan Passkey') + ' dengan benar.').slideDown();
            return;
        }

        $errorMsg.hide();
        $btn.text('Memverifikasi...').prop('disabled', true);

        try {
            // Inject direct credentials into session temporarily to let sendProxyRequest use it
            this.session = { slug: slug, passkey: passkey, ts: Date.now() };
            
            // We ping fetchStats as our gateway verify token
            const res = await this.sendProxyRequest('stats', {}, false);
            
            // If success, store strictly in sessionStorage
            sessionStorage.setItem('ev_lite_auth_' + this.postId, JSON.stringify(this.session));
            this.checkSession();
            
        } catch (error) {
            this.session = null;
            $errorMsg.text('Akses Ditolak: ' + (typeof error === 'string' ? error : 'Passkey salah atau event tidak ditemukan.')).slideDown();
        } finally {
            $btn.text('Masuk').prop('disabled', false);
        }
    }

    doLogout(e) {
        if(e) e.preventDefault();
        this.session = null;
        sessionStorage.removeItem('ev_lite_auth_' + this.postId);
        this._apiCache = {}; // clear cache on logout
        this.$element.find('.ev-lite-login-passkey').val('');
        this.checkSession();
    }

    onTabClick(e) {
        e.preventDefault();
        const $btn = jQuery(e.currentTarget);
        const target = $btn.data('target');

        this.elements.$tabs.removeClass('active');
        $btn.addClass('active');

        this.elements.$panes.removeClass('active');
        this.elements.$container.find('#' + target).addClass('active');

        if (target === 'tab-stats') {
            this.fetchStats();
        } else if (target === 'tab-guests') {
            this.fetchGuests();
        }
    }

    toggleDropdown(e) {
        e.preventDefault();
        e.stopPropagation();
        const $btn = jQuery(e.currentTarget);
        const $menu = $btn.next('.ev-lite-dropdown-menu');
        
        // Close others
        this.$element.find('.ev-lite-dropdown-menu').not($menu).removeClass('show');
        $menu.toggleClass('show');
    }

    async sendProxyRequest(route, data = {}, useCache = false, cacheTtlMs = 120000) {
        // Check cache for cacheable routes
        if (useCache) {
            const cached = this._getCached(route, data);
            if (cached !== null) return cached;
        }

        return new Promise((resolve, reject) => {
            jQuery.ajax({
                url: typeof evGuestbookLiteConfig !== 'undefined' ? evGuestbookLiteConfig.ajaxUrl : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'ev_lite_guestbook',
                    nonce: typeof evGuestbookLiteConfig !== 'undefined' ? evGuestbookLiteConfig.nonce : '',
                    route: route,
                    post_id: this.postId,
                    // Session-based credentials (login mode)
                    direct_slug:    (this.session && this.session.slug) ? this.session.slug : '',
                    direct_passkey: (this.session && this.session.passkey) ? this.session.passkey : '',
                    // Legacy fallback
                    url_slug: this.urlSlug || '',
                    ...data
                },
                success: (response) => {
                    if (response.success) {
                        if (useCache) this._setCache(route, data, response.data, cacheTtlMs);
                        resolve(response.data);
                    } else {
                        reject(response.data || 'Terjadi kesalahan sistem.');
                    }
                },
                error: () => reject('Gagal terhubung ke jaringan.')
            });
        });
    }

    async fetchStats() {
        const $pane = this.$element.find('#tab-stats');
        const $loader = $pane.find('.ev-lite-loader');
        const $wrappers = $pane.find('.ev-lite-stats-wrapper');

        $loader.show();
        $wrappers.hide();

        try {
            // Cache stats for 3 minutes
            const res = await this.sendProxyRequest('stats', {}, true, 180000);
            if (res && res.data) {
                const $banner = this.$element.find('#lite-event-banner');
                if (res.data.event_title) {
                    let bannerHtml = '';
                    if (res.data.event_thumbnail) {
                        bannerHtml += `<img src="${this.escapeHtml(res.data.event_thumbnail)}" class="ev-lite-event-thumbnail" alt="Event Thumbnail" />`;
                    }
                    bannerHtml += `<h4 class="ev-lite-event-title">${this.escapeHtml(res.data.event_title)}</h4>`;
                    $banner.html(bannerHtml).slideDown();
                }

                let total = parseInt(res.data.total_guests) || 0;
                let opened = parseInt(res.data.opened_invitations) || 0;
                let shared = parseInt(res.data.shared_invitations) || 0;
                let notShared = parseInt(res.data.not_shared_invitations) || 0;
                
                let rsvpHadir = parseInt(res.data.wp_rsvp_hadir) || 0;
                let rsvpTidak = parseInt(res.data.wp_rsvp_tidak_hadir) || 0;
                let rsvpRagu = parseInt(res.data.wp_rsvp_ragu) || 0;

                let pctOpened = total > 0 ? Math.round((opened / total) * 100) : 0;
                let pctShared = total > 0 ? Math.round((shared / total) * 100) : 0;
                let pctNotShared = total > 0 ? Math.round((notShared / total) * 100) : 0;
                
                let pctHadir = total > 0 ? Math.round((rsvpHadir / total) * 100) : 0;
                let pctTidak = total > 0 ? Math.round((rsvpTidak / total) * 100) : 0;
                let pctRagu = total > 0 ? Math.round((rsvpRagu / total) * 100) : 0;

                this.elements.$statTotal.text(total);
                
                this.elements.$statOpened.html(`${opened} <span class="ev-lite-stat-pct">${pctOpened}%</span>`);
                this.elements.$statShared.html(`${shared} <span class="ev-lite-stat-pct">${pctShared}%</span>`);
                this.elements.$statNotShared.html(`${notShared} <span class="ev-lite-stat-pct">${pctNotShared}%</span>`);
                
                this.elements.$statRsvpHadir.html(`${rsvpHadir} <span class="ev-lite-stat-pct">${pctHadir}%</span>`);
                this.elements.$statRsvpTidak.html(`${rsvpTidak} <span class="ev-lite-stat-pct">${pctTidak}%</span>`);
                this.elements.$statRsvpRagu.html(`${rsvpRagu} <span class="ev-lite-stat-pct">${pctRagu}%</span>`);
                
                // Show ucapan (total comments) instead of check-in
                this.elements.$statCheckin.text(res.data.wp_ucapan !== undefined ? res.data.wp_ucapan : (res.data.attended_guests || 0));

                // Populate guest types
                if (res.data.guest_types && res.data.guest_types.length > 0) {
                    const $selectFilters = this.$element.find('#lite-guest-type-filter');
                    const $selectForm = this.$element.find('#lite-guest-type-select');
                    
                    const oldFilter = $selectFilters.val();
                    const oldForm = $selectForm.val();
                    
                    $selectFilters.html('<option value="">Semua Tipe</option>');
                    $selectForm.html('');
                    
                    res.data.guest_types.forEach(gt => {
                        $selectFilters.append(`<option value="${this.escapeHtml(gt.name)}">${this.escapeHtml(gt.name)}</option>`);
                        $selectForm.append(`<option value="${this.escapeHtml(gt.name)}">${this.escapeHtml(gt.name)}</option>`);
                    });
                    
                    if(oldFilter) $selectFilters.val(oldFilter);
                    if(oldForm) $selectForm.val(oldForm);
                }

                // Populate WA templates
                if (res.data.wa_templates && res.data.wa_templates.length > 0) {
                    const $waSelect = this.$element.find('#lite-wa-template-select');
                    const $waText = this.$element.find('#lite-wa-template-text');
                    
                    $waSelect.html('<option value="">Pilih Template Teks</option>');
                    
                    let defaultContent = '';
                    res.data.wa_templates.forEach(wt => {
                        const isDefault = wt.name === 'Umum';
                        $waSelect.append(`<option value="${wt.id}" data-content="${this.escapeHtml(wt.content)}" ${isDefault ? 'selected' : ''}>${this.escapeHtml(wt.name)}</option>`);
                        if (isDefault) defaultContent = wt.content;
                    });
                    
                    this.currentWaitTemplate = defaultContent;

                    // Pre-fill with default template
                    if (defaultContent && !$waText.val()) {
                        $waText.val(defaultContent);
                    }
                }

                // Populate Wishes
                this._allWishesData = res.data.wishes || [];
                if (this._allWishesData.length > 0) {
                    this.isWishesExpanded = false;
                    this.wishesCurrentPage = 1;
                    this.renderWishesPage();
                } else {
                    const $wishesWrapper = this.$element.find('#lite-wishes-wrapper');
                    $wishesWrapper.html('<div style="text-align:center; padding: 20px; color: #6b7280; font-size:13px; border: 1px dashed #e2e8f0; border-radius: 8px;">Belum ada ucapan.</div>');
                }
            }
        } catch (error) {
            console.error('Stats Error:', error);
        } finally {
            $loader.hide();
            $wrappers.fadeIn(200);
        }
    }

    renderWishesPage() {
        const $wishesWrapper = this.$element.find('#lite-wishes-wrapper');
        const isLimitedView = !this.isWishesExpanded && this._allWishesData.length > 15;
        const perPage = isLimitedView ? 15 : 15;
        const totalPages = Math.ceil(this._allWishesData.length / perPage);
        const start = (this.wishesCurrentPage - 1) * perPage;
        const pageWishes = this._allWishesData.slice(start, start + perPage);

        let wishesHtml = '';
        pageWishes.forEach(w => {
            let label = w.label || (w.status === 'hadir' ? 'Hadir' : (w.status === 'tidak_hadir' ? 'Tidak Hadir' : 'Ragu'));
            
            const statusBadge = w.status === 'hadir' 
                ? `<span class="ev-lite-mini-badge bg-green-50 text-green-700">${this.escapeHtml(label)}</span>`
                : (w.status === 'tidak_hadir' 
                    ? `<span class="ev-lite-mini-badge bg-red-50 text-red-700">${this.escapeHtml(label)}</span>` 
                    : `<span class="ev-lite-mini-badge bg-yellow-50 text-yellow-700">${this.escapeHtml(label)}</span>`);
            
            const contentSticker = w.sticker ? `<div class="ev-lite-wish-sticker">${w.sticker}</div>` : '';

            wishesHtml += `
                <div class="ev-lite-wish-card">
                    <div class="ev-lite-wish-header">
                        <span class="ev-lite-wish-author">${this.escapeHtml(w.author)}</span>
                        ${w.status ? statusBadge : ''}
                    </div>
                    <div class="ev-lite-wish-date">${this.escapeHtml(w.date)}</div>
                    <div class="ev-lite-wish-content">${this.escapeHtml(w.content)}${contentSticker}</div>
                </div>
            `;
        });

        // Add container for pagination / load more
        wishesHtml = `<div class="ev-lite-wishes-list">${wishesHtml}</div>`;
        
        $wishesWrapper.html(wishesHtml);

        if (isLimitedView) {
            const $btnAll = jQuery('<button class="ev-lite-btn ev-lite-btn-secondary ev-lite-btn-block" style="margin-top: 5px; margin-bottom: 20px;">Lihat Semua Ucapan</button>');
            $btnAll.on('click', () => {
                this.isWishesExpanded = true;
                this.wishesCurrentPage = 1;
                this.renderWishesPage();
            });
            $wishesWrapper.append($btnAll);
        } else if (this.isWishesExpanded && totalPages > 1) {
            const prevDisabled = this.wishesCurrentPage <= 1 ? 'disabled' : '';
            const nextDisabled = this.wishesCurrentPage >= totalPages ? 'disabled' : '';
            
            const $pagination = jQuery(`
                <div class="ev-lite-pagination" style="display:block; margin-top:20px;">
                    <div class="ev-lite-pagination-inner">
                        <button class="ev-lite-page-btn ev-lite-page-prev" ${prevDisabled}><i class="fas fa-chevron-left"></i></button>
                        <span class="ev-lite-page-info">${this.wishesCurrentPage} / ${totalPages}</span>
                        <button class="ev-lite-page-btn ev-lite-page-next" ${nextDisabled}><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            `);
            
            $pagination.find('.ev-lite-page-prev').on('click', () => {
                if (this.wishesCurrentPage > 1) {
                    this.wishesCurrentPage--;
                    this.renderWishesPage();
                    this.$element.find('#lite-wishes-wrapper')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
            $pagination.find('.ev-lite-page-next').on('click', () => {
                if (this.wishesCurrentPage < totalPages) {
                    this.wishesCurrentPage++;
                    this.renderWishesPage();
                    this.$element.find('#lite-wishes-wrapper')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
            
            $wishesWrapper.append($pagination);
        }
    }

    async fetchGuests() {
        const $pane = this.$element.find('#tab-guests');
        const $loader = $pane.find('.ev-lite-loader');
        const $listWrapper = $pane.find('#lite-guest-list-wrapper');
        const $paginationWrapper = $pane.find('.ev-lite-pagination');
        const $count = $pane.find('#lite-guest-count');
        
        const search = this.elements.$inputSearch.val();
        const typeFilter = $pane.find('#lite-guest-type-filter').val() || '';

        $loader.show();
        $listWrapper.hide();
        $paginationWrapper.hide();

        try {
            const cacheEnabled = !search;
            const res = await this.sendProxyRequest('guests', { search: search }, cacheEnabled, 120000);
            if (res && res.data) {
                let guestsData = res.data;
                if (typeFilter) {
                    guestsData = guestsData.filter(g => g.type === typeFilter);
                }

                this.lastGuestsData = guestsData;
                this.currentPage = 1;
                this.perPage = 10;
                this.totalGuests = guestsData.length;
                
                // If user is searching or filtering, expand the list automatically
                if (search || typeFilter) {
                    this.isListExpanded = true;
                } else {
                    this.isListExpanded = false;
                }

                $count.text(guestsData.length);
                this.renderGuestsPage($listWrapper, $paginationWrapper);
            }
        } catch (error) {
            $listWrapper.html(`<div style="color:red; text-align:center; padding:20px;">Gagal memuat daftar tamu.</div>`);
        } finally {
            $loader.hide();
            $listWrapper.fadeIn(200);
        }
    }

    renderGuestsPage($listWrapper, $paginationWrapper) {
        if (!$paginationWrapper) {
            const $pane = this.$element.find('#tab-guests');
            $listWrapper = $pane.find('#lite-guest-list-wrapper');
            $paginationWrapper = $pane.find('.ev-lite-pagination');
        }

        const guestsData = this.lastGuestsData || [];
        
        // Pagination logic + Lihat Semua expansion
        const isLimitedView = !this.isListExpanded && guestsData.length > 5;
        const perPage = isLimitedView ? 5 : (this.perPage || 10);
        const currentPage = isLimitedView ? 1 : (this.currentPage || 1);
        const totalPages = Math.ceil(guestsData.length / perPage);
        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        
        const pageGuests = guestsData.slice(start, end);

        let html = '';
        if (guestsData.length === 0) {
            html = '<div style="text-align:center; padding: 40px 20px; color: #6b7280; font-size:14px;">Tidak ada tamu ditemukan.</div>';
        } else {
            pageGuests.forEach(g => {
                const initials = this.getInitials(g.name);
                const avatarColor = this.getAvatarColor(g.name);

                const openedBadge = g.clicked_at
                    ? '<span class="ev-lite-badge hadir bg-green-50 text-green-800">Dibuka</span>'
                    : '<span class="ev-lite-badge belum bg-yellow-50 text-yellow-800">Belum Dibuka</span>';

                // If opened, treat as shared already
                const isShared = g.shared_at || g.clicked_at;
                const sharedBadge = isShared
                    ? '<span class="ev-lite-badge hadir bg-green-50 text-green-800">Dibagikan</span>'
                    : '<span class="ev-lite-badge belum bg-yellow-50 text-yellow-800">Belum Dibagikan</span>';

                const wamsg = this.generateWaText(g);
                const waUrl = 'https://wa.me/?text=' + encodeURIComponent(wamsg);

                html += `
                    <div class="ev-lite-accordion-card" data-guest-id="${g.id}">
                        <div class="ev-lite-accordion-header" onclick="this.parentElement.classList.toggle('open')">
                            <div class="ev-lite-accordion-left">
                                <div class="ev-lite-avatar" style="background:${avatarColor}">${initials}</div>
                                <div class="ev-lite-accordion-info">
                                    <h4>${this.escapeHtml(g.name)}</h4>
                                    <span>Tipe: ${this.escapeHtml(g.type)}</span>
                                </div>
                            </div>
                            <div class="ev-lite-accordion-arrow"><i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div class="ev-lite-accordion-body">
                            <div class="ev-lite-detail-row">
                                <span class="ev-detail-label">Dibuka</span>
                                ${openedBadge}
                            </div>
                            <div class="ev-lite-detail-row">
                                <span class="ev-detail-label">Dibagikan</span>
                                ${sharedBadge}
                            </div>
                            <div class="ev-lite-action-buttons">
                                <button type="button" class="ev-lite-action-btn ev-lite-btn-wa lite-action-wa-share" data-guest-id="${g.id}" data-wa-url="${encodeURIComponent(waUrl)}">
                                    <i class="fab fa-whatsapp"></i> WA
                                </button>
                                <div class="ev-lite-dropdown-wrapper">
                                    <button type="button" class="ev-lite-action-btn ev-lite-btn-copy ev-lite-dropdown-toggle">
                                        <i class="fas fa-copy"></i> Salin <i class="fas fa-caret-down"></i>
                                    </button>
                                    <div class="ev-lite-dropdown-menu">
                                        <a href="#" class="lite-action-copy-link" data-id="${g.id}">Salin Link Saja</a>
                                        <a href="#" class="lite-action-copy-text" data-id="${g.id}">Salin dengan Pengantar</a>
                                    </div>
                                </div>
                                <div class="ev-lite-dropdown-wrapper">
                                    <button type="button" class="ev-lite-action-btn ev-lite-btn-action ev-lite-dropdown-toggle">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="ev-lite-dropdown-menu">
                                        <a href="#" class="lite-action-edit" data-id="${g.id}"><i class="fas fa-pen"></i> Edit Nama</a>
                                        <a href="#" class="lite-action-delete" data-id="${g.id}" style="color:#ef4444;"><i class="fas fa-trash"></i> Hapus</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        $listWrapper.html(html).show();

        if (isLimitedView) {
            const $btnAll = jQuery('<button class="ev-lite-btn ev-lite-btn-secondary ev-lite-btn-block" style="margin-top: 5px; margin-bottom: 20px;">Lihat Semua Tamu</button>');
            $btnAll.on('click', () => {
                this.isListExpanded = true;
                this.currentPage = 1;
                this.perPage = 10;
                this.renderGuestsPage();
            });
            $listWrapper.append($btnAll);
            $paginationWrapper.html('').hide();
        } else {
            // Render pagination controls if they exist and count > perPage
            const actualTotalPages = Math.ceil(guestsData.length / (this.perPage || 10));
            if (actualTotalPages > 1) {
                const prevDisabled = currentPage <= 1 ? 'disabled' : '';
                const nextDisabled = currentPage >= actualTotalPages ? 'disabled' : '';
                $paginationWrapper.html(`
                    <div class="ev-lite-pagination-inner">
                        <button class="ev-lite-page-btn ev-lite-page-prev" ${prevDisabled}>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <span class="ev-lite-page-info">${currentPage} / ${actualTotalPages}</span>
                        <button class="ev-lite-page-btn ev-lite-page-next" ${nextDisabled}>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                `).show();
    
                $paginationWrapper.find('.ev-lite-page-prev').off('click').on('click', () => {
                    if (this.currentPage > 1) {
                        this.currentPage--;
                        this.renderGuestsPage();
                        this.$element.find('#tab-guests')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
                $paginationWrapper.find('.ev-lite-page-next').off('click').on('click', () => {
                    if (this.currentPage < actualTotalPages) {
                        this.currentPage++;
                        this.renderGuestsPage();
                        this.$element.find('#tab-guests')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            } else {
                $paginationWrapper.html('').hide();
            }
        }
    }

    async onAddSubmit(e) {
        e.preventDefault();
        const $form = this.elements.$addForm;
        const $btn = $form.find('button[type="submit"]');
        const $btnText = $btn.find('.btn-text');
        const $btnLoading = $btn.find('.btn-loading');
        const $msg = $form.find('#lite-add-message');

        const data = {
            guest_name: $form.find('[name="guest_name"]').val(),
            guest_type: $form.find('[name="guest_type"]').val(),
            wa_template_text: $form.find('[name="wa_template_text"]').val()
        };

        if(!data.guest_name.trim()) return;

        $btn.prop('disabled', true);
        $btnText.hide();
        $btnLoading.show();
        $msg.html('');

        try {
            const res = await this.sendProxyRequest('add', data);
            
            $msg.html(`<div class="ev-lite-alert ev-lite-alert-success"><i class="fas fa-check-circle"></i> <strong>Sukses!</strong> ${this.escapeHtml(res.message || 'Tamu berhasil ditambahkan.')}</div>`);
            $form.find('[name="guest_name"]').val('');
            
            this.showToast(res.message || 'Tamu berhasil ditambahkan.');
            
            // Clear guest cache and refresh
            this._clearCache('guests');
            this._clearCache('stats');
            this.fetchGuests();
            
        } catch (error) {
            let errorText = typeof error === 'object' && error.message ? error.message : error;
            $msg.html(`<div class="ev-lite-alert ev-lite-alert-danger"><i class="fas fa-times-circle"></i> Gagal: ${this.escapeHtml(errorText)}</div>`);
        } finally {
            $btn.prop('disabled', false);
            $btnLoading.hide();
            $btnText.show();
            
            setTimeout(() => $msg.html(''), 5000);
        }
    }

    async onDeleteGuest(e) {
        e.preventDefault();
        e.stopPropagation();
        this.$element.find('.ev-lite-dropdown-menu').removeClass('show');
        const guestId = jQuery(e.currentTarget).data('id');
        const guest = this.lastGuestsData ? this.lastGuestsData.find(g => g.id == guestId) : null;
        const guestName = guest ? guest.name : 'tamu ini';
        this.showModal({
            title: 'Hapus Tamu',
            text: `Apakah Anda yakin ingin menghapus "${guestName}"?`,
            confirmText: 'Ya, Hapus',
            cancelText: 'Batal',
            isDanger: true,
            onConfirm: async () => {
                try {
                    // Send both variants to be fail-safe with API
                    await this.sendProxyRequest('delete', { guest_id: guestId, id: guestId });
                    this._clearCache('guests');
                    this._clearCache('stats');
                    this.showToast('Tamu berhasil dihapus', 'success');
                    this.fetchGuests();
                } catch (err) {
                    const msg = typeof err === 'object' && err.message ? err.message
                              : (typeof err === 'string' ? err : 'Gagal menghapus tamu.');
                    this.showToast('Gagal menghapus: ' + msg, 'error');
                }
            }
        });
    }

    async onEditGuest(e) {
        e.preventDefault();
        e.stopPropagation();
        this.$element.find('.ev-lite-dropdown-menu').removeClass('show');
        const guestId = jQuery(e.currentTarget).data('id');
        
        const guest = this.lastGuestsData ? this.lastGuestsData.find(g => g.id == guestId) : null;
        if (!guest) return;

        this.showModal({
            title: 'Ubah Nama Tamu',
            input: true,
            inputValue: guest.name,
            confirmText: 'Simpan',
            cancelText: 'Batal',
            onConfirm: async (newName) => {
                if (!newName || newName.trim() === '' || newName.trim() === guest.name) return;
                try {
                    // Send multiple variants
                    await this.sendProxyRequest('update', { 
                        guest_id: guestId,
                        id: guestId,
                        guest_name: newName.trim(),
                        nama: newName.trim(),
                        name: newName.trim()
                    });
                    this._clearCache('guests');
                    this.showToast('Nama tamu berhasil diperbarui', 'success');
                    this.fetchGuests();
                } catch (err) {
                    const msg = typeof err === 'object' && err.message ? err.message
                              : (typeof err === 'string' ? err : 'Gagal mengupdate.');
                    this.showToast('Gagal mengupdate: ' + msg, 'error');
                }
            }
        });
    }

    onCopyLink(e) {
        e.preventDefault();
        const $btn = jQuery(e.currentTarget);
        const guestId = $btn.data('id');
        const guest = this.lastGuestsData.find(g => g.id == guestId);
        
        if (guest && guest.invitation_url) {
            const url = 'https://' + guest.invitation_url;
            navigator.clipboard.writeText(url).then(() => {
                this.showToast('Link undangan berhasil disalin!', 'success');
                const oldHtml = $btn.html();
                $btn.html('<i class="fas fa-check"></i> Tersalin');
                setTimeout(() => $btn.html(oldHtml), 1500);
            });
        }
    }

    onCopyText(e) {
        e.preventDefault();
        const $btn = jQuery(e.currentTarget);
        const guestId = $btn.data('id');
        const guest = this.lastGuestsData.find(g => g.id == guestId);
        
        if (guest) {
            const text = this.generateWaText(guest);
            navigator.clipboard.writeText(text).then(() => {
                this.showToast('Pesan WhatsApp berhasil disalin!', 'success');
                const oldHtml = $btn.html();
                $btn.html('<i class="fas fa-check"></i> Tersalin');
                setTimeout(() => $btn.html(oldHtml), 1500);
            });
        }
    }

    generateWaText(guest) {
        let template = guest.wa_template || this.currentWaitTemplate || "Halo [NamaTamu],\n\n[LinkUndangan]";
        const url = 'https://' + (guest.invitation_url || '');
        
        return template
            .replace(/\[NamaTamu\]/g, guest.name)
            .replace(/\[LinkUndangan\]/g, url)
            .replace(/\[dari\]/g, '')
            .replace(/\[sesi\]/g, '')
            .replace(/\[mempelai_pria\]/g, 'Mempelai Pria')
            .replace(/\[mempelai_wanita\]/g, 'Mempelai Wanita')
            .replace(/\[hormat_kami\]/g, 'Kami yang berbahagia');
    }

    getInitials(name) {
        if (!name) return '?';
        const parts = name.trim().split(/\s+/);
        if (parts.length >= 2) {
            return (parts[0][0] + parts[1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    getAvatarColor(name) {
        const colors = ['#ef4444','#f97316','#eab308','#22c55e','#14b8a6','#3b82f6','#6366f1','#a855f7','#ec4899'];
        let hash = 0;
        for (let i = 0; i < (name || '').length; i++) {
            hash = name.charCodeAt(i) + ((hash << 5) - hash);
        }
        return colors[Math.abs(hash) % colors.length];
    }

    showToast(message, type = 'success') {
        let $container = jQuery('.ev-lite-toast-container');
        if ($container.length === 0) {
            $container = jQuery('<div class="ev-lite-toast-container"></div>').appendTo('body');
        }
        
        const $toast = jQuery(`<div class="ev-lite-toast ${type}">${this.escapeHtml(message)}</div>`);
        $container.append($toast);
        
        $toast[0].offsetHeight; // trigger reflow
        $toast.addClass('show');
        
        setTimeout(() => {
            $toast.removeClass('show');
            setTimeout(() => $toast.remove(), 300);
        }, 3000);
    }

    showModal(options) {
        let $overlay = jQuery('.ev-lite-modal-overlay');
        if ($overlay.length === 0) {
            $overlay = jQuery(`
                <div class="ev-lite-modal-overlay">
                    <div class="ev-lite-modal">
                        <h3 class="ev-lite-modal-title"></h3>
                        <p class="ev-lite-modal-text"></p>
                        <input type="text" class="ev-lite-modal-input" style="display:none;" />
                        <div class="ev-lite-modal-actions">
                            <button class="ev-lite-modal-btn ev-lite-modal-btn-cancel"></button>
                            <button class="ev-lite-modal-btn ev-lite-modal-btn-submit"></button>
                        </div>
                    </div>
                </div>
            `).appendTo('body');
        }

        const $modal = $overlay.find('.ev-lite-modal');
        const $title = $modal.find('.ev-lite-modal-title');
        const $text = $modal.find('.ev-lite-modal-text');
        const $input = $modal.find('.ev-lite-modal-input');
        const $cancel = $modal.find('.ev-lite-modal-btn-cancel');
        const $submit = $modal.find('.ev-lite-modal-btn-submit');

        $title.text(options.title || 'Konfirmasi');
        
        if (options.text) {
            $text.text(options.text).show();
        } else {
            $text.hide();
        }

        if (options.input) {
            $input.val(options.inputValue || '').show();
            // Optional: Focus input on mount
            setTimeout(() => $input.focus(), 100);
        } else {
            $input.hide().val('');
        }

        $cancel.text(options.cancelText || 'Batal');
        $submit.text(options.confirmText || 'OK');
        
        $submit.removeClass('ev-lite-modal-btn-danger ev-lite-modal-btn-submit');
        $submit.addClass(options.isDanger ? 'ev-lite-modal-btn-danger' : 'ev-lite-modal-btn-submit');

        $overlay.css('display', 'flex');
        
        // Trigger reflow & show
        $overlay[0].offsetHeight;
        $overlay.addClass('show');

        // Prevent multiple handlers
        $cancel.off('click');
        $submit.off('click');

        const closeModal = () => {
            $overlay.removeClass('show');
            setTimeout(() => $overlay.css('display', 'none'), 300);
        };

        $cancel.on('click', () => {
            closeModal();
            if(options.onCancel) options.onCancel();
        });

        $submit.on('click', () => {
            const val = $input.val();
            closeModal();
            if(options.onConfirm) options.onConfirm(options.input ? val : null);
        });
    }

    escapeHtml(unsafe) {
        return (unsafe || '').toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

jQuery(window).on('elementor/frontend/init', () => {
    elementorFrontend.elementsHandler.attachHandler('ev_guestbook_lite', EvGuestbookLiteHandler);
});
