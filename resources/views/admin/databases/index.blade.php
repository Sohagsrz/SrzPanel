@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Database Manager</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDatabaseModal">
                            <i class="fas fa-plus"></i> New Database
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="databaseTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="mysql-tab" data-toggle="tab" href="#mysql" role="tab">
                                MySQL/MariaDB
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="postgres-tab" data-toggle="tab" href="#postgres" role="tab">
                                PostgreSQL
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="databaseTabsContent">
                        <div class="tab-pane fade show active" id="mysql" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>User</th>
                                            <th>Size</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($databases['mysql'] ?? [] as $db)
                                        <tr>
                                            <td>{{ $db->name }}</td>
                                            <td>{{ $db->user }}</td>
                                            <td>{{ $db->size }}</td>
                                            <td>{{ $db->created_at }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            data-toggle="modal" 
                                                            data-target="#backupModal"
                                                            data-db-name="{{ $db->name }}"
                                                            data-db-type="mysql">
                                                        <i class="fas fa-download"></i> Backup
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            data-toggle="modal" 
                                                            data-target="#restoreModal"
                                                            data-db-name="{{ $db->name }}"
                                                            data-db-type="mysql">
                                                        <i class="fas fa-upload"></i> Restore
                                                    </button>
                                                    <form action="{{ route('admin.databases.destroy') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="name" value="{{ $db->name }}">
                                                        <input type="hidden" name="type" value="mysql">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this database?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="postgres" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Owner</th>
                                            <th>Size</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($databases['postgres'] ?? [] as $db)
                                        <tr>
                                            <td>{{ $db->name }}</td>
                                            <td>{{ $db->owner }}</td>
                                            <td>{{ $db->size }}</td>
                                            <td>{{ $db->created_at }}</td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            data-toggle="modal" 
                                                            data-target="#backupModal"
                                                            data-db-name="{{ $db->name }}"
                                                            data-db-type="postgres">
                                                        <i class="fas fa-download"></i> Backup
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning" 
                                                            data-toggle="modal" 
                                                            data-target="#restoreModal"
                                                            data-db-name="{{ $db->name }}"
                                                            data-db-type="postgres">
                                                        <i class="fas fa-upload"></i> Restore
                                                    </button>
                                                    <form action="{{ route('admin.databases.destroy') }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <input type="hidden" name="name" value="{{ $db->name }}">
                                                        <input type="hidden" name="type" value="postgres">
                                                        <button type="submit" class="btn btn-sm btn-danger" 
                                                                onclick="return confirm('Are you sure you want to delete this database?')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
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
    </div>
</div>

<!-- Create Database Modal -->
<div class="modal fade" id="createDatabaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Database</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.databases.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Database Type</label>
                        <select name="type" class="form-control" required>
                            <option value="mysql">MySQL/MariaDB</option>
                            <option value="postgres">PostgreSQL</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Database Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Backup Modal -->
<div class="modal fade" id="backupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Backup Database</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.databases.backup') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="name" id="backupDbName">
                    <input type="hidden" name="type" id="backupDbType">
                    <p>Are you sure you want to backup this database?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Backup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restore Database</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.databases.restore') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="name" id="restoreDbName">
                    <input type="hidden" name="type" id="restoreDbType">
                    <div class="form-group">
                        <label>Backup File</label>
                        <input type="file" name="backup" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Restore</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Set database name and type for backup modal
    $('#backupModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var dbName = button.data('db-name');
        var dbType = button.data('db-type');
        $('#backupDbName').val(dbName);
        $('#backupDbType').val(dbType);
    });

    // Set database name and type for restore modal
    $('#restoreModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var dbName = button.data('db-name');
        var dbType = button.data('db-type');
        $('#restoreDbName').val(dbName);
        $('#restoreDbType').val(dbType);
    });
});
</script>
@endpush 