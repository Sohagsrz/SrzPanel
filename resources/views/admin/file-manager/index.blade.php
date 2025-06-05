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
                            <i class="fas fa-upload"></i> Upload
                        </button>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#newFolderModal">
                            <i class="fas fa-folder-plus"></i> New Folder
                        </button>
                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#archiveModal">
                            <i class="fas fa-file-archive"></i> Archive
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                                @foreach($directories as $dir)
                                <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                   data-path="{{ $dir }}" 
                                   href="#">
                                    <i class="fas fa-folder"></i> {{ basename($dir) }}
                                </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Size</th>
                                            <th>Modified</th>
                                            <th>Permissions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fileList">
                                        <!-- Files will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Files</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.files.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Select Files</label>
                        <input type="file" name="files[]" class="form-control" multiple required>
                    </div>
                    <input type="hidden" name="path" id="uploadPath">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- New Folder Modal -->
<div class="modal fade" id="newFolderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Folder</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.files.create-folder') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Folder Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <input type="hidden" name="path" id="newFolderPath">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Archive Modal -->
<div class="modal fade" id="archiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Archive Files</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.files.compress') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Archive Name</label>
                        <input type="text" name="archive_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Archive Type</label>
                        <select name="archive_type" class="form-control" required>
                            <option value="zip">ZIP</option>
                            <option value="tar">TAR</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Files to Archive</label>
                        <select name="files[]" class="form-control" multiple required>
                            <!-- Files will be loaded here -->
                        </select>
                    </div>
                    <input type="hidden" name="path" id="archivePath">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Archive</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div class="modal fade" id="permissionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Set Permissions</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.files.permissions') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Owner</label>
                        <input type="text" name="owner" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Group</label>
                        <input type="text" name="group" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="row">
                            <div class="col-md-4">
                                <label>Owner</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="owner_read">
                                    <label class="custom-control-label">Read</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="owner_write">
                                    <label class="custom-control-label">Write</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="owner_execute">
                                    <label class="custom-control-label">Execute</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label>Group</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="group_read">
                                    <label class="custom-control-label">Read</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="group_write">
                                    <label class="custom-control-label">Write</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="group_execute">
                                    <label class="custom-control-label">Execute</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label>Others</label>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="others_read">
                                    <label class="custom-control-label">Read</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="others_write">
                                    <label class="custom-control-label">Write</label>
                                </div>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" name="permissions[]" value="others_execute">
                                    <label class="custom-control-label">Execute</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="path" id="permissionsPath">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Set Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load files when directory is clicked
    $('.nav-link').click(function(e) {
        e.preventDefault();
        const path = $(this).data('path');
        loadFiles(path);
    });

    // Load files function
    function loadFiles(path) {
        $.get(`{{ route('admin.files.list') }}?path=${encodeURIComponent(path)}`, function(data) {
            $('#fileList').html(data);
            $('#uploadPath').val(path);
            $('#newFolderPath').val(path);
            $('#archivePath').val(path);
            $('#permissionsPath').val(path);
        });
    }

    // Initial load
    loadFiles($('.nav-link.active').data('path'));
});
</script>
@endpush 