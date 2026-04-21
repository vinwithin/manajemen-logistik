    <a class='btn btn-xs btn-info' href="{{ route('purchase-order.edit', encrypt($q->id)) }}">
        <i class='fa fa-edit'></i> Edit
    </a> |
    <a class='btn btn-xs btn-secondary' href="{{ route('purchase-order.show', encrypt($q->id)) }}">
        <i class='fa fa-eye'></i> Detail
    </a> |
    <a class='btn btn-xs btn-danger' onclick="confirmDelete({{ $q->id }})">
        <i class='fa fa-trash'></i>Hapus
    </a>
    <form action="{{ route('purchase-order.destroy', $q->id) }}" method='post' id="del-po-{{ $q->id }}">
        @csrf @method('DELETE')
    </form>

