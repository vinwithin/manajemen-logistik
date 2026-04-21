<div class="lansir-row border rounded p-2 mb-2">
    <div class="row g-2 align-items-center">
        <div class="col-auto">
            <span class="badge bg-info row-num">{{ $idx + 1 }}</span>
        </div>
        <div class="col-md-3 col-6">
            <input type="text" name="mobils[{{ $idx }}][no_polisi]"
                class="form-control form-control-sm text-uppercase" value="{{ old("mobils.$idx.no_polisi") }}"
                placeholder="No. Polisi *">
        </div>
        <div class="col-md-3 col-6">
            <input type="text" name="mobils[{{ $idx }}][tim_bongkar]" class="form-control form-control-sm"
                value="{{ old("mobils.$idx.tim_bongkar") }}" placeholder="Tim bongkar">
        </div>
        <div class="col-md-1 col-4">
            <input type="number" name="mobils[{{ $idx }}][berat_lansir]"
                class="form-control form-control-sm berat-input" value="{{ old("mobils.$idx.berat_lansir") }}"
                placeholder="kg" step="0.01" min="0">
        </div>
        <div class="col-md-2 col-4">
            <input type="number" name="mobils[{{ $idx }}][ongkos_lansir]"
                class="form-control form-control-sm ongkos-input" value="{{ old("mobils.$idx.ongkos_lansir") }}"
                placeholder="Ongkos" step="0.01" min="0">
        </div>
        <div class="col-md-2 col-4">
            <input type="number" name="mobils[{{ $idx }}][upah_bongkar]"
                class="form-control form-control-sm upah-input" value="{{ old("mobils.$idx.upah_bongkar") }}"
                placeholder="Upah" step="0.01" min="0">
        </div>
        <div class="col-auto ms-auto">
            <button type="button" class="btn btn-sm btn-outline-danger btn-hapus"
                {{ isset($first) && $first ? 'disabled' : '' }}>
                <i class="fa fa-times"></i>
            </button>
        </div>
    </div>
    <div class="row-preview small text-muted mt-1 ps-4" style="display:none">
        Ongkos: <strong class="prev-ongkos">-</strong> &nbsp;|&nbsp;
        Upah: <strong class="prev-upah">-</strong>
    </div>
</div>
