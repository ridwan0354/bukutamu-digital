document.addEventListener('DOMContentLoaded', function() {
    let hasShownMaxAmountAlert = false;

    function formatNumber(value) {
        if (!value) return '';
        const raw = value.toString().replace(/[^0-9]/g, '');
        return new Intl.NumberFormat('id-ID').format(parseInt(raw) || 0);
    }

    document.querySelectorAll('.elementor-widget-ev-atm-card').forEach(function(widget) {
        const widgetId = widget.dataset.id;
        const modal = document.getElementById('ewf-gift-modal-' + widgetId);
        let activeCopyButton = null;
        let activeCardWrapper = null;

        
        widget.querySelectorAll('.ewf-qr-download-btn').forEach(downloadButton => {
            downloadButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                const url = this.dataset.downloadUrl;
                const filename = this.dataset.downloadFilename;

                if (!url) return;
                
                fetch(url).then(response => response.blob())
                    .then(blob => {
                        const blobUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = blobUrl;
                        a.download = filename || 'qris_transfer.png';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(blobUrl);
                    })
                    .catch(error => console.error('Gagal mengunduh QR:', error));
            });
        });

        
        widget.querySelectorAll('.ewf-card-number-area .ewf-copy-button').forEach(copyButton => {
            copyButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                activeCopyButton = this;
                activeCardWrapper = this.closest('.ewf-atm-card-widget-wrapper');
                const contentToCopy = this.dataset.copyContent || '';
                
                navigator.clipboard.writeText(contentToCopy.replace(/\s/g, '')).then(() => {
                    
                    
                    if (activeCardWrapper.dataset.showAlert !== 'yes' || typeof Swal === 'undefined') {
                        const buttonTextEl = this.querySelector('.ewf-button-text');
                        if (buttonTextEl) {
                             const originalText = buttonTextEl.textContent;
                             buttonTextEl.textContent = activeCardWrapper.dataset.copyButtonSuccessText || 'Tersalin!';
                             setTimeout(() => { buttonTextEl.textContent = originalText; }, 1500);
                        }
                        return;
                    }

                    
                    let enableConfirmation = activeCardWrapper.dataset.enableGiftConfirmation === 'yes';
                    const confirmationStatusKey = 'giftConfirmed_' + widgetId + '_' + (this.dataset.accountName || '');

                    if (sessionStorage.getItem(confirmationStatusKey) === 'true') {
                        enableConfirmation = false;
                    }
                    
                    const swalConfig = {
                        title: activeCardWrapper.dataset.saTitle || 'Nomor Disalin',
                        html: activeCardWrapper.dataset.saText || '',
                        timer: parseInt(activeCardWrapper.dataset.saTimer) || 0,
                        customClass: { popup: 'ewf-atm-card-popup', icon: 'swal2-icon-html' },
                        showClass: { popup: 'swal2-show' },
                        hideClass: { popup: 'swal2-hide' },
                        showConfirmButton: false,
                    };

                    
                    const iconHtml = activeCardWrapper.dataset.saIconHtml;
                    if (iconHtml) {
                        swalConfig.iconHtml = iconHtml;
                    } else {
                        swalConfig.icon = activeCardWrapper.dataset.saIcon || 'success';
                    }

                    if (enableConfirmation) {
                        swalConfig.showDenyButton = true;
                        swalConfig.denyButtonText = activeCardWrapper.dataset.confirmButtonText || 'Konfirmasi Transfer';
                    }
                    
                    Swal.fire(swalConfig).then((result) => {
                        if (result.isDenied && modal) {
                            const bankInput = modal.querySelector('input[name="bank_name"]');
                            
                            
                            if (bankInput) {
                                bankInput.value = ''; 
                            }
                            
                            modal.style.display = 'flex';
                        }
                    });

                }).catch(err => console.error('Gagal menyalin:', err));
            });
        });

        
        widget.querySelectorAll('.ewf-confirm-button.ewf-open-modal').forEach(confirmButton => {
            confirmButton.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                activeCopyButton = this;
                activeCardWrapper = this.closest('.ewf-atm-card-widget-wrapper');
                
                if (modal) {
                    const bankInput = modal.querySelector('input[name="bank_name"]');
                    if (bankInput) {
                        
                        bankInput.value = 'QRIS';
                    }
                    modal.style.display = 'flex';
                }
            });
        });


        
        if (modal) {
            const closeModalBtn = modal.querySelector('.ewf-gift-modal-close');
            const giftForm = modal.querySelector('form');
            const submitButton = giftForm.querySelector('.ev-rsvp-submit-button');
            const amountInput = giftForm.querySelector('input[name="amount"]');
            
           
            try {
                const urlParams = new URLSearchParams(window.location.search);
                let guestNameFromUrl = urlParams.get('to');
                const nameInput = modal.querySelector('input[name="guest_name"]');
                
                if (guestNameFromUrl && nameInput) {
                    const decodedGuestName = decodeURIComponent(guestNameFromUrl.replace(/\+/g, ' ')).trim();
                    const genericNames = ['Nama Tamu', 'Tamu Undangan'];
                    if (!genericNames.map(n => n.toLowerCase()).includes(decodedGuestName.toLowerCase())) {
                        nameInput.value = decodedGuestName;
                        nameInput.readOnly = true;
                    }
                }
            } catch (e) { console.error("Gagal mengambil nama dari URL:", e); }

            
            if (amountInput) {
                amountInput.addEventListener('input', (e) => {
                    let rawValue = e.target.value.toString().replace(/[^0-9]/g, '');
                    const maxLimit = 10000000; 

                    if (rawValue.length > 9 && parseInt(rawValue) > maxLimit) {
                         rawValue = maxLimit.toString();
                    }

                    if (parseInt(rawValue) > maxLimit && !hasShownMaxAmountAlert && typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Batas Nominal',
                            text: 'Maaf, nominal yang dimasukkan terlalu besar.',
                            icon: 'warning',
                            confirmButtonText: 'Oke',
                            customClass: { popup: 'ewf-atm-card-popup', container: 'ewf-swal-top-index' }
                        });
                        hasShownMaxAmountAlert = true;
                    } else if (parseInt(rawValue) < maxLimit) {
                         hasShownMaxAmountAlert = false;
                    }
                    
                    e.target.value = formatNumber(rawValue);
                });
            }

            
            closeModalBtn.addEventListener('click', () => { modal.style.display = 'none'; });
            modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

            
            giftForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                activeCardWrapper = activeCardWrapper || this.closest('.ewf-atm-card-widget-wrapper');
                activeCopyButton = activeCopyButton || activeCardWrapper.querySelector('.ewf-copy-button.ewf-download-button') || activeCardWrapper.querySelector('.ewf-card-number-area .ewf-copy-button');
                
                if (!activeCardWrapper) {
                    Swal.fire('Error', 'Kesalahan internal widget. Silakan muat ulang halaman.', 'error');
                    return;
                }
                
                const fileInput = this.querySelector('input[name="proof_of_transfer"]');
                const fileError = this.querySelector('.ewf-file-error-' + widgetId);
                const fileInfo = this.querySelector('.ewf-file-info-' + widgetId);
                
                const maxFileSize = 1 * 1024 * 1024; 

                fileError.style.display = 'none';
                submitButton.classList.add('is-loading');

                const formData = new FormData(this);
                
                
                formData.append('post_title', activeCardWrapper.dataset.postTitle || '');
                formData.append('client_name', activeCardWrapper.dataset.waNoticeName || '');
                formData.append('client_number', activeCardWrapper.dataset.waNoticeNumber || '');
                formData.append('enable_digital_gift_api', activeCardWrapper.dataset.enableDigitalGiftApi || 'no');
                formData.append('template', activeCardWrapper.dataset.waTemplate || '');
                
                
                let accountName = '';
                if(activeCopyButton && activeCopyButton.dataset.accountName) {
                    accountName = activeCopyButton.dataset.accountName;
                } else if (activeCardWrapper.querySelector('.ewf-card-footer-value')) {
                    accountName = activeCardWrapper.querySelector('.ewf-card-footer-value').textContent.trim();
                }
                formData.append('account_name', accountName);
                
                
                const cleanAmount = formData.get('amount').toString().replace(/[.,]/g, '');
                formData.set('amount', cleanAmount);

               
                if (fileInput && fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    
                    if (file.type === 'image/png') {
                         fileError.textContent = 'Gagal: File .png tidak diperbolehkan. Mohon gunakan .jpg, .jpeg, atau .webp.';
                         fileError.style.display = 'block';
                         submitButton.classList.remove('is-loading');
                         return;
                    }
                    if (file.size > maxFileSize) {
                        fileError.textContent = 'Gagal: Ukuran file asli melebihi batas 1MB.';
                        fileError.style.display = 'block';
                        submitButton.classList.remove('is-loading');
                        return;
                    }
                    
                    fileInfo.style.display = 'block';
                    
                    try {
                        const options = { maxSizeMB: 0.2, maxWidthOrHeight: 800, useWebWorker: true, };
                        const compressedFile = await imageCompression(file, options);
                        fileInfo.style.display = 'none';
                        
                        if (compressedFile.size > maxFileSize) {	
                             fileError.textContent = 'Gagal: Ukuran file terkompresi masih melebihi batas 2MB.';
                             fileError.style.display = 'block';
                             submitButton.classList.remove('is-loading');
                             return;
                        }

                        formData.set('proof_of_transfer', compressedFile, compressedFile.name);
                        
                    } catch (error) {
                        fileInfo.style.display = 'none';
                        fileError.textContent = 'Gagal memproses file. Silakan coba lagi.';
                        fileError.style.display = 'block';
                        submitButton.classList.remove('is-loading');
                        return;	
                    }
                }
                
                
                fetch(EvGlobal.ajaxurl, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    submitButton.classList.remove('is-loading');
                    modal.style.display = 'none';
                    giftForm.reset();
                    
                    if (data.success) {
                        const confirmationStatusKey = 'giftConfirmed_' + widgetId + '_' + (activeCopyButton ? (activeCopyButton.dataset.accountName || '') : '');
                        sessionStorage.setItem(confirmationStatusKey, 'true');
                        Swal.fire('Sukses!', data.data.message || 'Konfirmasi Anda berhasil dikirim dan sedang diproses.', 'success');
                    } else {
                        Swal.fire('Gagal', data.data.message || 'Terjadi kesalahan saat mengirim data.', 'error');
                    }
                })
                .catch(error => {
                    submitButton.classList.remove('is-loading');
                    Swal.fire('Error', 'Tidak dapat terhubung ke server.', 'error');
                });
            });
        }
        
        
        widget.querySelectorAll('.ewf-can-flip').forEach(container => {
            container.addEventListener('click', function(e) {
                
                if (e.target.closest('.ewf-copy-button, .ewf-qr-download-btn, .ewf-open-modal')) {
                    return;
                }
                
                const flipCardInner = container.querySelector('.ewf-flip-card-inner');
                if (flipCardInner) {
                    flipCardInner.classList.toggle('is-flipped');
                }

                e.stopPropagation();
                
            });
        });

    });
});