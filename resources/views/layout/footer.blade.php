<footer>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 text-muted" style="font-size:0.78rem;">
        <div>
          
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25"
                style="font-size:10px; padding:3px 7px;">
                v{{ config('app.version', '1.0') }}
            </span>
            <span>{{ now()->format('H:i') }} WIB</span>
        </div>
    </div>
</footer>
