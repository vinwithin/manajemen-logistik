<a class='btn btn-xs btn-info' href="{{ route('role.edit', encrypt($q->id)) }}"><i class='fa fa-edit'></i>
    Edit</a> |
<a class='btn btn-xs btn-danger' onclick="confirmation('del-role-{{ $q->id }}')"><i class='fa fa-trash'></i>
    Delete</a>
<form action="{{ route('role.destroy', $q->id) }}" method='post' id="del-role-{{ $q->id }}">
    @csrf
    @method('DELETE')
</form>
