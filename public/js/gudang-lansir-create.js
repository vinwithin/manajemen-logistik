// Gudang Lansir Create - Dynamic Form Handler with Nested Kendaraan
let kendaraanCount = 0;
let selectedPoPenerimaData = null;

$(document).ready(function() {
    // Auto-add first kendaraan on page load
    tambahKendaraan();

    $('#selectGudang').on('change', function() {
        const gudangId = $(this).val();
        if (gudangId) {
            window.location.href = `?gudang_id=${gudangId}`;
        }
    });
    
    // ========== PO PENERIMA AUTO-FILL ==========
    $('#selectPoPenerima').on('change', function() {
        const poPenerimaId = $(this).val();
        
        if (poPenerimaId) {
            const selectedOption = $(this).find('option:selected');
            $('#poNoDisplay').text(selectedOption.data('po-no'));
            $('#poPenerimaDisplay').text(selectedOption.text().split('|')[1].trim());
            $('#poTibaDisplay').text(selectedOption.data('tiba'));
            $('#poInfoDisplay').show();
            
            $.get(`/gudang/lansir/api/po-penerima/${poPenerimaId}`, function(response) {
                if (response.success) {
                    selectedPoPenerimaData = response.data;
                    alertify.success('Data PO berhasil dimuat. Klik "Auto-Fill Data" untuk mengisi form.');
                }
            }).fail(function() {
                alertify.error('Gagal memuat data PO');
                selectedPoPenerimaData = null;
            });
        } else {
            $('#poInfoDisplay').hide();
            selectedPoPenerimaData = null;
        }
    });
    
    $('#btnAutoFill').on('click', function() {
        if (!selectedPoPenerimaData) {
            alertify.error('Pilih PO Penerima terlebih dahulu');
            return;
        }
        autoFillFromPo(selectedPoPenerimaData);
    });
});

function autoFillFromPo(poData) {
    $('.kendaraan-card').remove();
    kendaraanCount = 0;
    tambahKendaraan();
    
    setTimeout(function() {
        const ki = 0;
        $(`.list-penerima[data-kendaraan="${ki}"]`).empty();
        tambahPenerima(ki);
        
        setTimeout(function() {
            const pi = 0;
            const $pCard = $(`.penerima-card[data-kendaraan="${ki}"][data-penerima="${pi}"]`);
            const $select = $pCard.find('.select-penerima');
            $select.val(poData.nama_penerima).trigger('change');
            
            if (!$select.val()) {
                $select.prepend(`<option value="${poData.nama_penerima}" selected>${poData.nama_penerima}</option>`);
            }
            
            if (poData.tujuan_id) {
                $pCard.find('.input-tujuan-id').val(poData.tujuan_id);
            }
            
            $(`.list-pakan[data-kendaraan="${ki}"][data-penerima="${pi}"]`).empty();
            
            poData.pakans.forEach(function(pakan, index) {
                tambahPakan(ki, pi);
                setTimeout(function() {
                    const pakanIdx = index;
                    $(`select[name="kendaraans[${ki}][penerimas][${pi}][pakans][${pakanIdx}][kode_pakan_id]"]`).val(pakan.kode_pakan_id);
                    $(`input[name="kendaraans[${ki}][penerimas][${pi}][pakans][${pakanIdx}][jumlah_kg]"]`).val(pakan.jumlah_kg);
                    // ongkos_oa default 70, harga_pt_sum dari PO
                    $(`input[name="kendaraans[${ki}][penerimas][${pi}][pakans][${pakanIdx}][harga_pt_sum]"]`).val(pakan.harga_pt_sum || 0);
                    $(`input[name="kendaraans[${ki}][penerimas][${pi}][pakans][${pakanIdx}][jumlah_kg]"]`).trigger('input');
                }, 100 * (index + 1));
            });
            
            alertify.success('Data dari PO berhasil diisi!');
        }, 200);
    }, 200);
}

$('#btnTambahKendaraan').on('click', function() {
    tambahKendaraan();
});

function tambahKendaraan() {
    const ki = kendaraanCount++;
    
    const html = `
        <div class="card mb-4 kendaraan-card border-success" data-kendaraan="${ki}">
            <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">🚚 Kendaraan <span class="kendaraan-number">${ki + 1}</span></h6>
                <button type="button" class="btn btn-sm btn-outline-light btn-hapus-kendaraan">
                    <i class="fa fa-times"></i> Hapus Kendaraan
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">No. Polisi <span class="text-danger">*</span></label>
                        <input type="text" name="kendaraans[${ki}][no_polisi]" class="form-control text-uppercase"
                            placeholder="B 1234 XY" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nama Sopir</label>
                        <input type="text" name="kendaraans[${ki}][nama_sopir]" class="form-control"
                            placeholder="Opsional">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">No. Surat Jalan</label>
                        <input type="text" name="kendaraans[${ki}][no_surat_jalan]" class="form-control"
                            placeholder="Opsional">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Daftar Penerima</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-tambah-penerima" data-kendaraan="${ki}">
                            <i class="fa fa-plus"></i> Tambah Penerima
                        </button>
                    </div>
                    <div class="list-penerima" data-kendaraan="${ki}"></div>
                </div>
            </div>
        </div>
    `;

    $('#listKendaraan').append(html);
    tambahPenerima(ki);
    updateKendaraanNumbers();
}

$(document).on('click', '.btn-hapus-kendaraan', function() {
    if ($('.kendaraan-card').length > 1) {
        $(this).closest('.kendaraan-card').remove();
        updateKendaraanNumbers();
    } else {
        alertify.error('Minimal harus ada 1 kendaraan');
    }
});

function updateKendaraanNumbers() {
    $('.kendaraan-card').each(function(index) {
        $(this).find('.kendaraan-number').text(index + 1);
    });
}

// ========== PENERIMA MANAGEMENT ==========
$(document).on('click', '.btn-tambah-penerima', function() {
    const kendaraanIdx = $(this).data('kendaraan');
    tambahPenerima(kendaraanIdx);
});

function tambahPenerima(kendaraanIdx) {
    const container = $(`.list-penerima[data-kendaraan="${kendaraanIdx}"]`);
    const penerimaIdx = container.find('.penerima-card').length;

    const penerimaOptions = penerimaList.map(p =>
        `<option value="${p.nama}" data-penerima-id="${p.id}" data-tujuan-id="${p.tujuan_id}" data-tujuan-nama="${p.tujuan_nama}" data-ongkos="${p.ongkos_angkut}" data-bongkar="${p.ongkos_bongkar}">${p.nama}</option>`
    ).join('');

    const html = `
        <div class="card mb-3 penerima-card" data-kendaraan="${kendaraanIdx}" data-penerima="${penerimaIdx}">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <h6 class="mb-0">👤 Penerima <span class="penerima-number">${penerimaIdx + 1}</span></h6>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-penerima">
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Nama Penerima <span class="text-danger">*</span></label>
                        <select name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][nama_penerima]" 
                            class="form-select select-penerima" required>
                            <option value="">-- Pilih Penerima --</option>
                            ${penerimaOptions}
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tujuan</label>
                        <input type="hidden" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][tujuan_id]" class="input-tujuan-id" value="">
                        <input type="text" class="form-control form-control-sm bg-light input-tujuan-display" placeholder="Otomatis dari penerima" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-semibold mb-0">Pakan <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-xs btn-outline-success btn-tambah-pakan" 
                            data-kendaraan="${kendaraanIdx}" data-penerima="${penerimaIdx}">
                            <i class="fa fa-plus"></i> Tambah Pakan
                        </button>
                    </div>
                    <div class="list-pakan" data-kendaraan="${kendaraanIdx}" data-penerima="${penerimaIdx}"></div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-semibold mb-0">Tim Bongkar <span class="text-muted small">(Opsional)</span></label>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-tambah-tim" 
                            data-kendaraan="${kendaraanIdx}" data-penerima="${penerimaIdx}">
                            <i class="fa fa-plus"></i> Tambah Tim
                        </button>
                    </div>
                    <div class="list-tim" data-kendaraan="${kendaraanIdx}" data-penerima="${penerimaIdx}"></div>
                </div>
            </div>
        </div>
    `;

    container.append(html);
    tambahPakan(kendaraanIdx, penerimaIdx);
    tambahTim(kendaraanIdx, penerimaIdx);
    updatePenerimaNumbers(kendaraanIdx);
}

$(document).on('click', '.btn-hapus-penerima', function() {
    const kendaraanCard = $(this).closest('.kendaraan-card');
    const kendaraanIdx = kendaraanCard.data('kendaraan');
    const container = kendaraanCard.find(`.list-penerima[data-kendaraan="${kendaraanIdx}"]`);
    
    if (container.find('.penerima-card').length > 1) {
        $(this).closest('.penerima-card').remove();
        updatePenerimaNumbers(kendaraanIdx);
    } else {
        alertify.error('Minimal harus ada 1 penerima per kendaraan');
    }
});

// Saat penerima dipilih: auto-fill tujuan
$(document).on('change', '.select-penerima', function() {
    const $pCard = $(this).closest('.penerima-card');
    const $opt = $(this).find('option:selected');
    const tujuanId   = $opt.data('tujuan-id')   || '';
    const tujuanNama = $opt.data('tujuan-nama')  || '';

    $pCard.find('.input-tujuan-id').val(tujuanId);
    $pCard.find('.input-tujuan-display').val(tujuanNama);
});

function updatePenerimaNumbers(kendaraanIdx) {
    const container = $(`.list-penerima[data-kendaraan="${kendaraanIdx}"]`);
    container.find('.penerima-card').each(function(index) {
        $(this).find('.penerima-number').text(index + 1);
    });
}

// ========== PAKAN MANAGEMENT ==========
$(document).on('click', '.btn-tambah-pakan', function() {
    const kendaraanIdx = $(this).data('kendaraan');
    const penerimaIdx = $(this).data('penerima');
    tambahPakan(kendaraanIdx, penerimaIdx);
});

function tambahPakan(kendaraanIdx, penerimaIdx) {
    const container = $(`.list-pakan[data-kendaraan="${kendaraanIdx}"][data-penerima="${penerimaIdx}"]`);
    const pakanIdx = container.find('.row-pakan').length;

    const kodePakanOptions = kodePakanList.map(kp => {
        // Cari stok untuk kode pakan ini
        const stok = stokData.find(s => s.kode_pakan_id === kp.id);
        const stokKg = stok ? parseFloat(stok.stok_kg) : 0;
        const disabled = stokKg <= 0 ? 'disabled' : '';
        const stokText = stokKg > 0 ? stokKg.toLocaleString('id-ID') : '0';
        return `<option value="${kp.id}" data-stok="${stokKg}" ${disabled}>${kp.kode} - ${kp.nama} (Stok: ${stokText} kg)</option>`;
    }).join('');

    const html = `
        <div class="row g-2 align-items-end mb-2 row-pakan">
            <div class="col-md-3">
                <label class="form-label small">Kode Pakan <span class="text-danger">*</span></label>
                <select name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][pakans][${pakanIdx}][kode_pakan_id]" 
                        class="form-select form-select-sm select-pakan" required>
                    <option value="">-- Pilih Pakan --</option>
                    ${kodePakanOptions}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Jumlah (kg) <span class="text-danger">*</span></label>
                <input type="number" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][pakans][${pakanIdx}][jumlah_kg]" 
                       class="form-control form-control-sm input-kg" placeholder="0" step="0.01" min="0.01" required>
                <small class="text-muted stok-info"></small>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Karung</label>
                <input type="number" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][pakans][${pakanIdx}][jumlah_karung]" 
                       class="form-control form-control-sm input-karung" placeholder="Auto" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Ongkos OA (Rp/kg)</label>
                <input type="number" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][pakans][${pakanIdx}][ongkos_oa]" 
                       class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="70">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Harga PT Sum (Rp/kg)</label>
                <input type="number" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][pakans][${pakanIdx}][harga_pt_sum]" 
                       class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="0">
            </div>
            <div class="col-md-1">
                <label class="form-label small">Keterangan</label>
                <input type="text" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][pakans][${pakanIdx}][keterangan]" 
                       class="form-control form-control-sm" placeholder="Opsional">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-pakan w-100">
                   Hapus
                </button>
            </div>
        </div>
    `;

    container.append(html);
    updatePakanHapus(kendaraanIdx, penerimaIdx);
}

$(document).on('click', '.btn-hapus-pakan', function() {
    const row = $(this).closest('.row-pakan');
    const container = row.closest('.list-pakan');
    const kendaraanIdx = container.data('kendaraan');
    const penerimaIdx = container.data('penerima');
    
    if (container.find('.row-pakan').length > 1) {
        row.remove();
        updatePakanHapus(kendaraanIdx, penerimaIdx);
    } else {
        alertify.error('Minimal harus ada 1 pakan per penerima');
    }
});

function updatePakanHapus(kendaraanIdx, penerimaIdx) {
    const container = $(`.list-pakan[data-kendaraan="${kendaraanIdx}"][data-penerima="${penerimaIdx}"]`);
    const rows = container.find('.row-pakan');
    rows.find('.btn-hapus-pakan').prop('disabled', rows.length === 1);
}

// Auto-calculate karung from kg
$(document).on('input', '.input-kg', function() {
    const kg = parseFloat($(this).val()) || 0;
    const karung = kg > 0 ? Math.ceil(kg / 50) : '';
    $(this).closest('.row-pakan').find('.input-karung').val(karung);
    
    const row = $(this).closest('.row-pakan');
    const select = row.find('.select-pakan');
    const stok = parseFloat(select.find('option:selected').data('stok')) || 0;
    const stokInfo = row.find('.stok-info');
    
    if (kg > stok) {
        stokInfo.text(`⚠️ Melebihi stok (${stok.toLocaleString('id-ID')} kg)`).addClass('text-danger');
    } else {
        stokInfo.text('').removeClass('text-danger');
    }
});

$(document).on('change', '.select-pakan', function() {
    const stok = parseFloat($(this).find('option:selected').data('stok')) || 0;
    const row = $(this).closest('.row-pakan');
    row.find('.stok-info').text(`Tersedia: ${stok.toLocaleString('id-ID')} kg`);
});

// ========== TIM MANAGEMENT ==========
$(document).on('click', '.btn-tambah-tim', function() {
    const kendaraanIdx = $(this).data('kendaraan');
    const penerimaIdx = $(this).data('penerima');
    tambahTim(kendaraanIdx, penerimaIdx);
});

function tambahTim(kendaraanIdx, penerimaIdx) {
    const container = $(`.list-tim[data-kendaraan="${kendaraanIdx}"][data-penerima="${penerimaIdx}"]`);
    const timIdx = container.find('.row-tim').length;

    const html = `
        <div class="row g-2 align-items-end mb-2 row-tim">
            <div class="col-md-3">
                <label class="form-label small">Nama Tim <span class="text-danger">*</span></label>
                <input type="text" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][tims][${timIdx}][nama_tim]" 
                       class="form-control form-control-sm" placeholder="Nama tim bongkar" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Jumlah (kg) <span class="text-danger">*</span></label>
                <input type="number" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][tims][${timIdx}][jumlah_kg]" 
                       class="form-control form-control-sm" placeholder="0" step="0.01" min="0.01" required>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Upah per KG (Rp)</label>
                <input type="number" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][tims][${timIdx}][upah_per_kg]" 
                       class="form-control form-control-sm" placeholder="0" step="0.01" min="0" value="45">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Keterangan</label>
                <input type="text" name="kendaraans[${kendaraanIdx}][penerimas][${penerimaIdx}][tims][${timIdx}][keterangan]" 
                       class="form-control form-control-sm" placeholder="Opsional">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tim w-100">
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
        </div>
    `;

    container.append(html);
}

$(document).on('click', '.btn-hapus-tim', function() {
    $(this).closest('.row-tim').remove();
});

// ========== FORM VALIDATION ==========
$('#formLansir').on('submit', function(e) {
    const gudangId = $('#selectGudang').val();
    if (!gudangId) {
        e.preventDefault();
        alertify.error('Pilih gudang asal terlebih dahulu');
        return false;
    }

    let hasError = false;
    $('.row-pakan').each(function() {
        const kg = parseFloat($(this).find('.input-kg').val()) || 0;
        const select = $(this).find('.select-pakan');
        const stok = parseFloat(select.find('option:selected').data('stok')) || 0;
        
        if (kg > stok) {
            hasError = true;
            $(this).find('.input-kg').addClass('is-invalid');
        }
    });

    if (hasError) {
        e.preventDefault();
        alertify.error('Ada pakan yang melebihi stok tersedia');
        return false;
    }

    return true;
});
