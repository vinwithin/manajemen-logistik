<a class='btn btn-xs btn-info' href="{{ route('user.edit', encrypt($q->id)) }}"><i class='fa fa-edit'></i> Edit</a> |
<a class='btn btn-xs btn-danger' onclick="confirmation('del-{{ $q->id }}')"><i class='fa fa-trash'></i> Delete</a>
<form action="{{ route('user.destroy', $q->id) }}" method='post' id="del-{{ $q->id }}">
    @csrf
    @method('DELETE')
</form>
