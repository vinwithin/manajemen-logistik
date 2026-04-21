<a class='btn btn-xs btn-info' href="{{ route('supplier.edit', encrypt($q->id)) }}"><i class='fa fa-edit'></i> Edit</a> |
<a class='btn btn-xs btn-danger' onclick="confirmation('del-sup-{{ $q->id }}')"><i class='fa fa-trash'></i>
    Delete</a>
<form action="{{ route('supplier.destroy', $q->id) }}" method='post' id="del-sup-{{ $q->id }}">
    @csrf
    @method('DELETE')
</form>
