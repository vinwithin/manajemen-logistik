<a class='btn btn-xs btn-info' href="{{ route('tujuan.edit', encrypt($q->id)) }}">Edit</a> |
<a class='btn btn-xs btn-danger' onclick="confirmation('del-tujuan-{{ $q->id }}')"><i class='fa fa-trash'></i>
    Delete</a>
<form action="{{ route('tujuan.destroy', $q->id) }}" method='post' id="del-tujuan-{{ $q->id }}">
    @csrf
    @method('DELETE')
</form>
