/**
 * PO Down Payment (DP) Handler
 * Auto-calculate DP percentage dan info tagihan
 */

// Format angka ke format Rupiah (dengan titik sebagai pemisah ribuan)
function formatRupiah(angka) {
    if (!angka || angka === 0) return '0';
    const numberString = angka.toString().replace(/[^,\d]/g, '');
    const split = numberString.split(',');
    const sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    const ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    
    if (ribuan) {
        const separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }
    
    rupiah = split[1] !== undefined ? rupiah + ',' + split[1] : rupiah;
    return rupiah;
}

// Parse format Rupiah kembali ke angka
function parseRupiah(rupiah) {
    if (!rupiah) return 0;
    return parseFloat(rupiah.toString().replace(/\./g, '').replace(/,/g, '.')) || 0;
}

// Hitung total tagihan supplier untuk kendaraan
function hitungTotalTagihanSupplier($kendaraanCard) {
    let total = 0;
    
    // Loop semua penerima di kendaraan ini
    $kendaraanCard.find('.item-penerima').each(function() {
        // Loop semua pakan di penerima ini
        $(this).find('.item-pakan').each(function() {
            const jumlahKg = parseFloat($(this).find('.input-jumlah-kg').val()) || 0;
            // Total tagihan supplier = jumlah kg × ongkos OA
            const ongkosOa = parseFloat($(this).find('[name*="[ongkos_oa]"]').val()) || 0;
            total += jumlahKg * ongkosOa;
        });
    });
    
    return total;
}

// Update persentase DP dan info tagihan
function updateDpInfo($kendaraanCard) {
    const totalTagihan = hitungTotalTagihanSupplier($kendaraanCard);
    const dpNominal = parseFloat($kendaraanCard.find('.input-dp-nominal').val()) || 0;
    
    // Hitung persentase
    let dpPersen = 0;
    if (totalTagihan > 0 && dpNominal > 0) {
        dpPersen = (dpNominal / totalTagihan) * 100;
    }
    
    // Update field persentase
    $kendaraanCard.find('.input-dp-persen').val(dpPersen > 0 ? dpPersen.toFixed(2) : '');
    
    // Update info tagihan
    const $infoBox = $kendaraanCard.find('.info-dp');
    const $infoText = $kendaraanCard.find('.info-dp-text');
    
    if (totalTagihan > 0) {
        const sisaTagihan = totalTagihan - dpNominal;
        const formatRp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
        
        let infoHtml = `Total Tagihan: <strong>${formatRp(totalTagihan)}</strong>`;
        
        if (dpNominal > 0) {
            infoHtml += ` | DP: <strong>${formatRp(dpNominal)}</strong> (${dpPersen.toFixed(1)}%)`;
            infoHtml += ` | Sisa: <strong>${formatRp(sisaTagihan)}</strong>`;
            
            // Warning jika DP > total tagihan
            if (dpNominal > totalTagihan) {
                $infoBox.removeClass('alert-info').addClass('alert-warning');
                infoHtml += ' <span class="text-danger">⚠️ DP melebihi total tagihan!</span>';
            } else {
                $infoBox.removeClass('alert-warning').addClass('alert-info');
            }
        }
        
        $infoText.html(infoHtml);
        $infoBox.show();
    } else {
        $infoBox.hide();
    }
}

// Event handler untuk input DP nominal display (format Rupiah)
$(document).on('input', '.input-dp-nominal-display', function() {
    const $this = $(this);
    const $kCard = $this.closest('.item-kendaraan');
    
    // Parse input dan format
    const rawValue = parseRupiah($this.val());
    const formatted = formatRupiah(rawValue);
    
    // Update display dan hidden field
    $this.val(formatted);
    $kCard.find('.input-dp-nominal').val(rawValue);
    
    // Update info DP
    updateDpInfo($kCard);
});

// Event handler untuk blur - pastikan format tetap rapi
$(document).on('blur', '.input-dp-nominal-display', function() {
    const $this = $(this);
    const rawValue = parseRupiah($this.val());
    
    if (rawValue === 0) {
        $this.val('0');
    } else {
        $this.val(formatRupiah(rawValue));
    }
});

// Event handler untuk perubahan jumlah kg atau ongkos OA (trigger recalc DP)
$(document).on('input', '.input-jumlah-kg, [name*="[ongkos_oa]"]', function() {
    const $kCard = $(this).closest('.item-kendaraan');
    if ($kCard.length) {
        // Delay sedikit untuk memastikan nilai sudah terupdate
        setTimeout(function() {
            updateDpInfo($kCard);
        }, 100);
    }
});

// Trigger update DP info saat pakan ditambah/dihapus
$(document).on('click', '.btn-tambah-pakan', function() {
    const $kCard = $(this).closest('.item-kendaraan');
    setTimeout(function() {
        updateDpInfo($kCard);
    }, 200);
});

$(document).on('click', '.btn-hapus-pakan', function() {
    const $kCard = $(this).closest('.item-kendaraan');
    setTimeout(function() {
        updateDpInfo($kCard);
    }, 200);
});

// Trigger update DP info saat penerima ditambah/dihapus
$(document).on('click', '.btn-tambah-penerima', function() {
    const $kCard = $(this).closest('.item-kendaraan');
    setTimeout(function() {
        updateDpInfo($kCard);
    }, 200);
});

$(document).on('click', '.btn-hapus-penerima', function() {
    const $kCard = $(this).closest('.item-kendaraan');
    setTimeout(function() {
        updateDpInfo($kCard);
    }, 200);
});
