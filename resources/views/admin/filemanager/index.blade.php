@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">File Manager</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">
                            <i class="fas fa-upload"></i> Upload File
                        </button>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createDirectoryModal">
                            <i class="fas fa-folder-plus"></i> New Directory
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('admin.filemanager.index') }}">Root</a></li>
                                    @foreach(explode('/', $path) as $segment)
                                        @if($segment)
                                            <li class="breadcrumb-item">
                                                <a href="{{ route('admin.filemanager.index', ['path' => implode('/', array_slice(explode('/', $path), 0, array_search($segment, explode('/', $path)) + 1))]) }}">
                                                    {{ $segment }}
                                                </a>
                                            </li>
                                        @endif
                                    @endforeach
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Modified</th>
                                    <th>Permissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td>
                                            @if($item['type'] === 'directory')
                                                <i class="fas fa-folder text-warning"></i>
                                                <a href="{{ route('admin.filemanager.index', ['path' => $item['path']]) }}">
                                                    {{ $item['name'] }}
                                                </a>
                                            @else
                                                <i class="fas fa-file text-primary"></i>
                                                {{ $item['name'] }}
                                            @endif
                                        </td>
                                        <td>{{ ucfirst($item['type']) }}</td>
                                        <td>
                                            @if($item['type'] === 'file')
                                                {{ number_format($item['size'] / 1024, 2) }} KB
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ date('Y-m-d H:i:s', $item['modified']) }}</td>
                                        <td>{{ $item['permissions'] }}</td>
                                        <td>
                                            <div class="btn-group">
                                                @if($item['type'] === 'file')
                                                    <a href="{{ route('admin.filemanager.edit', ['path' => $item['path']]) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('admin.filemanager.show', ['path' => $item['path']]) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                @endif
                                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteItem('{{ $item['path'] }}', '{{ $item['type'] }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.filemanager.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="path" value="{{ $path }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Upload File</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="file">Select File</label>
                        <input type="file" class="form-control-file" id="file" name="file" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Directory Modal -->
<div class="modal fade" id="createDirectoryModal" tabindex="-1" role="dialog" aria-labelledby="createDirectoryModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.filemanager.create-directory') }}" method="POST">
                @csrf
                <input type="hidden" name="path" value="{{ $path }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="createDirectoryModalLabel">Create Directory</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Directory Name</label>
                        <input type="text" class="form-control" id="name" name="name" required pattern="[a-zA-Z0-9-_]+" title="Only letters, numbers, hyphens and underscores allowed">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function deleteItem(path, type) {
    if (confirm('Are you sure you want to delete this ' + type + '?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = type === 'directory' ? '{{ route('admin.filemanager.delete-directory') }}' : '{{ route('admin.filemanager.delete-file') }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        
        const pathInput = document.createElement('input');
        pathInput.type = 'hidden';
        pathInput.name = 'path';
        pathInput.value = path;
        
        form.appendChild(csrfToken);
        form.appendChild(pathInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endpush
@endsection 