// Gudang Lansir Create - Dynamic Form Handler
let penerimaCount = 0;

$(document).ready(function() {
    tambahPenerima();

    $('#selectGudang').on('change', function() {
        const gudangId = $(this).val();
        if (gudangId) {
            window.location.href = `?gudang_id=${gudangId}`;
        }
    });
});

$('#btnTambahPenerima').on('click', function() {
    tambahPenerima();
});

function tambahPenerima() {
    const i = penerimaCount++;
    
    const tujuanOptions = tujuanList.map(t => 
        `<option value="${t.id}">${t.nama}</option>`
    ).join('');

    const html = `
        <div class="card mb-3 penerima-card" data-index="${i}">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Penerima <span class="penerima-number">${i + 1}</span></h6>
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-penerima">
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Nama Penerima <span class="text-danger">*</span></label>
                        <input type="text" name="penerimas[${i}][nama_penerima]" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small">Tujuan</label>
                        <select name="penerimas[${i}][tujuan_id]" class="form-select">
                            <option value="">-- Pilih Tujuan --</option>
                            ${tujuanOptions}
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-semibold mb-0">Tim Bongkar <span class="text-muted small">(Opsional)</span></label>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-tambah-tim" data-penerima="${i}">
                            <i class="fa fa-plus"></i> Tambah Tim
                        </button>
                    </div>
                    <div class="list-tim" data-penerima="${i}"></div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-semibold mb-0">Pakan <span class="text-danger">*</span></label>
                        <button type="button" class="btn btn-xs btn-outline-success btn-tambah-pakan" data-penerima="${i}">
                            <i class="fa fa-plus"></i> Tambah Pakan
                        </button>
                    </div>
                    <div class="list-pakan" data-penerima="${i}"></div>
                </div>

               
            </div>
        </div>
    `;

    $('#listPenerima').append(html);
    
    // Auto-add first pakan
    tambahPakan(i);
    tambahTim(i);
    
    updatePenerimaNumbers();
}

// Hapus Penerima
$(document).on('click', '.btn-hapus-penerima', function() {
    if ($('.penerima-card').length > 1) {
        $(this).closest('.penerima-card').remove();
        updatePenerimaNumbers();
    } else {
        alertify.error('Minimal harus ada 1 penerima');
    }
});

// Tambah Pakan
$(document).on('click', '.btn-tambah-pakan', function() {
    const penerimaIdx = $(this).data('penerima');
    tambahPakan(penerimaIdx);
});

function tambahPakan(penerimaIdx) {
    const container = $(`.list-pakan[data-penerima="${penerimaIdx}"]`);
    const pakanIdx = container.find('.row-pakan').length;
    
    const kodePakanOptions = kodePakanList.map(kp => {
        const stok = stokData.find(s => s.kode_pakan_id === kp.id);
        const stokKg = stok ? parseFloat(stok.stok_kg) : 0;
        const disabled = stokKg <= 0 ? 'disabled' : '';
        return `<option value="${kp.id}" data-stok="${stokKg}" ${disabled}>${kp.kode} - ${kp.nama} (Stok: ${stokKg.toLocaleString('id-ID')} kg)</option>`;
    }).join('');

    const html = `
        <div class="row g-2 align-items-end mb-2 row-pakan">
            <div class="col-md-4">
                <label class="form-label small">Kode Pakan <span class="text-danger">*</span></label>
                <select name="penerimas[${penerimaIdx}][pakans][${pakanIdx}][kode_pakan_id]" 
                        class="form-select form-select-sm select-pakan" required>
                    <option value="">-- Pilih Pakan --</option>
                    ${kodePakanOptions}
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Jumlah (kg) <span class="text-danger">*</span></label>
                <input type="number" name="penerimas[${penerimaIdx}][pakans][${pakanIdx}][jumlah_kg]" 
                       class="form-control form-control-sm input-kg" placeholder="0" step="0.01" min="0.01" required>
                <small class="text-muted stok-info"></small>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Karung</label>
                <input type="number" name="penerimas[${penerimaIdx}][pakans][${pakanIdx}][jumlah_karung]" 
                       class="form-control form-control-sm input-karung" placeholder="Auto" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Ongkos OA (Rp/kg)</label>
                <input type="number" name="penerimas[${penerimaIdx}][pakans][${pakanIdx}][ongkos_oa]" 
                       class="form-control form-control-sm" placeholder="0" step="0.01" min="0">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-pakan w-100">
                    <i class="fa fa-times"></i> Hapus
                </button>
            </div>
        </div>
    `;

    container.append(html);
    updatePakanHapus(penerimaIdx);
}

// Hapus Pakan
$(document).on('click', '.btn-hapus-pakan', function() {
    const container = $(this).closest('.list-pakan');
    const penerimaIdx = container.data('penerima');
    
    if (container.find('.row-pakan').length > 1) {
        $(this).closest('.row-pakan').remove();
        updatePakanHapus(penerimaIdx);
    } else {
        alertify.error('Minimal harus ada 1 pakan per penerima');
    }
});

// Auto-calculate karung from kg
$(document).on('input', '.input-kg', function() {
    const kg = parseFloat($(this).val()) || 0;
    const karung = kg > 0 ? Math.ceil(kg / 50) : '';
    $(this).closest('.row-pakan').find('.input-karung').val(karung);
    
    // Validate against stock
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

// Show stok info when pakan selected
$(document).on('change', '.select-pakan', function() {
    const stok = parseFloat($(this).find('option:selected').data('stok')) || 0;
    const row = $(this).closest('.row-pakan');
    row.find('.stok-info').text(`Tersedia: ${stok.toLocaleString('id-ID')} kg`);
});

// Tambah Tim
$(document).on('click', '.btn-tambah-tim', function() {
    const penerimaIdx = $(this).data('penerima');
    tambahTim(penerimaIdx);
});

function tambahTim(penerimaIdx) {
    const container = $(`.list-tim[data-penerima="${penerimaIdx}"]`);
    const timIdx = container.find('.row-tim').length;

    const html = `
        <div class="row g-2 align-items-end mb-2 row-tim">
            <div class="col-md-4">
                <label class="form-label small">Nama Tim <span class="text-danger">*</span></label>
                <input type="text" name="penerimas[${penerimaIdx}][tims][${timIdx}][nama_tim]" 
                       class="form-control form-control-sm" placeholder="Nama tim bongkar" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Jumlah (kg) <span class="text-danger">*</span></label>
                <input type="number" name="penerimas[${penerimaIdx}][tims][${timIdx}][jumlah_kg]" 
                       class="form-control form-control-sm" placeholder="0" step="0.01" min="0.01" required>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Upah per KG (Rp)</label>
                <input type="number" name="penerimas[${penerimaIdx}][tims][${timIdx}][upah_per_kg]" 
                       class="form-control form-control-sm" placeholder="0" step="0.01" min="0">
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

// Hapus Tim
$(document).on('click', '.btn-hapus-tim', function() {
    $(this).closest('.row-tim').remove();
});

// Helper functions
function updatePenerimaNumbers() {
    $('.penerima-card').each(function(index) {
        $(this).find('.penerima-number').text(index + 1);
    });
}

function updatePakanHapus(penerimaIdx) {
    const container = $(`.list-pakan[data-penerima="${penerimaIdx}"]`);
    const rows = container.find('.row-pakan');
    rows.find('.btn-hapus-pakan').prop('disabled', rows.length === 1);
}

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
