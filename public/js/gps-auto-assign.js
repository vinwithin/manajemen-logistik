// GPS Auto Assignment Handler
$(document).ready(function() {
    // Button: Auto Assign GPS berdasarkan Nopol
    $(document).on('click', '.btn-auto-assign-gps', function() {
        var kendaraanId = $(this).data('kendaraan-id');
        var nopol = $(this).data('nopol');
        
        alertify.confirm(
            'Auto Assign GPS',
            'Sistem akan mencari GPS device dengan nopol <strong>' + nopol + '</strong> dan meng-assign-nya secara otomatis. Lanjutkan?',
            function() {
                // Cari device berdasarkan nopol
                $.getJSON('/gps/position-by-nopol', { nopol: nopol })
                    .done(function(res) {
                        if (!res.success || !res.device_id) {
                            alertify.error('GPS device dengan nopol ' + nopol + ' tidak ditemukan di tracker');
                            return;
                        }
                        
                        // Assign GPS
                        $.ajax({
                            url: '/gps/kendaraan/' + kendaraanId + '/assign',
                            method: 'POST',
                            data: {
                                _token: $('meta[name="csrf-token"]').attr('content'),
                                device_id: res.device_id,
                                catatan: 'Auto-assigned berdasarkan nopol'
                            },
                            success: function(assignRes) {
                                if (assignRes.success) {
                                    alertify.success('GPS berhasil di-assign ke ' + nopol);
                                    setTimeout(function() {
                                        location.reload();
                                    }, 700);
                                } else {
                                    alertify.error(assignRes.message || 'Gagal assign GPS');
                                }
                            },
                            error: function(xhr) {
                                var msg = xhr.responseJSON?.message || 'Gagal assign GPS';
                                alertify.error(msg);
                            }
                        });
                    })
                    .fail(function(xhr) {
                        var msg = xhr.responseJSON?.message || 'GPS device dengan nopol ' + nopol + ' tidak ditemukan';
                        alertify.error(msg);
                    });
            },
            function() {}
        ).set('labels', { ok: 'Ya, Auto Assign', cancel: 'Batal' });
    });

    // Button: Unassign GPS
    $(document).on('click', '.btn-unassign-gps', function() {
        var kendaraanId = $(this).data('kendaraan-id');
        var nopol = $(this).data('nopol');
        
        alertify.confirm(
            'Lepas GPS',
            'Yakin ingin melepas GPS dari kendaraan <strong>' + nopol + '</strong>?',
            function() {
                $.ajax({
                    url: '/gps/kendaraan/' + kendaraanId + '/unassign',
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            alertify.success(res.message || 'GPS berhasil dilepas');
                            setTimeout(function() {
                                location.reload();
                            }, 700);
                        } else {
                            alertify.error(res.message || 'Gagal melepas GPS');
                        }
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON?.message || 'Gagal melepas GPS';
                        alertify.error(msg);
                    }
                });
            },
            function() {}
        ).set('labels', { ok: 'Ya, Lepas', cancel: 'Batal' });
    });
});
