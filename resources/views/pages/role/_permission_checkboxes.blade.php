
@php $selected = $selected ?? []; @endphp

<div class="form-group">
    <label class="col-sm-2 control-label">Permissions</label>
    <div class="col-sm-10">
        <div class="mb-2">
            <label class="mr-3">
                <input type="checkbox" id="checkAll"> <strong>Pilih Semua</strong>
            </label>
        </div>
        @foreach ($permissions as $menuId => $perms)
            <div class="card mb-2">
                <div class="card-header py-1 px-2">
                    <label class="mb-0">
                        <input type="checkbox" class="group-check" data-group="{{ $menuId }}">
                        <strong>Menu ID: {{ $menuId }}</strong>
                    </label>
                </div>
                <div class="card-body py-2 px-3">
                    @foreach ($perms as $perm)
                        <label class="mr-3 perm-item" data-group="{{ $menuId }}">
                            <input type="checkbox" name="permissions[]" value="{{ $perm->id }}"
                                class="perm-check group-{{ $menuId }}"
                                {{ in_array($perm->id, $selected) ? 'checked' : '' }}>
                            {{ $perm->name }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    // Check all
    $('#checkAll').on('change', function() {
        $('input[name="permissions[]"]').prop('checked', $(this).is(':checked'));
        $('.group-check').prop('checked', $(this).is(':checked'));
    });

    // Per-group check
    $('.group-check').on('change', function() {
        var group = $(this).data('group');
        $('.group-' + group).prop('checked', $(this).is(':checked'));
    });

    // Sync group checkbox state when individual items change
    $('input[name="permissions[]"]').on('change', function() {
        var group = $(this).closest('.card').find('.group-check').data('group');
        var total = $('.group-' + group).length;
        var checked = $('.group-' + group + ':checked').length;
        $(this).closest('.card').find('.group-check').prop('checked', total === checked);
    });
</script>
