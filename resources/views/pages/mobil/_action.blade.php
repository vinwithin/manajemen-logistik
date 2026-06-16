<div class="d-flex gap-1">
    <a href="{{ route('mobil.edit', encrypt($row->id)) }}" class="btn btn-sm btn-warning" title="Edit">
        Edit
    </a>
    <button onclick="confirmDelete({{ $row->id }})" class="btn btn-sm btn-danger" title="Hapus">
        Hapus
    </button>
</div>
