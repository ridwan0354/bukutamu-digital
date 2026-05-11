jQuery(function ($) {
    'use strict';

    
    const swalAnimation = {
        showClass: {
            popup: 'animate__animated animate__zoomIn animate__faster'
        },
        hideClass: {
            popup: 'animate__animated animate__zoomOut animate__faster'
        }
    };

    $('#ev-deactivate-license-btn').on('click', function (e) {
        e.preventDefault();

        Swal.fire({
            title: 'Anda Yakin?',
            text: "Lisensi akan dinonaktifkan untuk domain ini. Anda perlu mengaktifkannya kembali untuk menggunakan fitur premium.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Nonaktifkan!',
            cancelButtonText: 'Batal',
            ...swalAnimation 
        }).then((result) => {
            if (result.isConfirmed) {
                deactivateLicense();
            }
        });
    });

    function deactivateLicense() {
        var btn = $('#ev-deactivate-license-btn');
        var originalText = btn.text();
        btn.prop('disabled', true).text('Memproses...');

        $.post(evAdminAjax.ajaxurl, {
            action: 'ev_widget_deactivate_license',
            _ajax_nonce: evAdminAjax.nonce
        })
        .done(function (response) {
            if (response.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: response.data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                    willClose: () => {
                        location.reload();
                    },
                    ...swalAnimation 
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: response.data.message,
                    icon: 'error',
                    ...swalAnimation 
                });
                btn.prop('disabled', false).text(originalText);
            }
        })
        .fail(function () {
            Swal.fire({
                title: 'Error',
                text: 'Terjadi kesalahan saat menghubungi server.',
                icon: 'error',
                ...swalAnimation 
            });
            btn.prop('disabled', false).text(originalText);
        });
    }
});