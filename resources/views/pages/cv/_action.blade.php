<a class='btn btn-xs btn-info' href="{{ route('perusahaan.edit', encrypt($q->id)) }}"><i class='fa fa-edit'></i> Edit</a> |
<a class='btn btn-xs btn-danger' onclick="confirmation('del-cv-{{ $q->id }}')"><i class='fa fa-trash'></i>
    Delete</a>
<form action="{{ route('perusahaan.destroy', $q->id) }}" method='post' id="del-cv-{{ $q->id }}">
    @csrf
    @method('DELETE')
</form>
