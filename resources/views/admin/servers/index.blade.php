@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Servers</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addServerModal">
                            <i class="fas fa-plus"></i> Add Server
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Hostname</th>
                                    <th>IP Address</th>
                                    <th>Type</th>
                                    <th>OS</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($servers as $server)
                                <tr>
                                    <td>{{ $server->name }}</td>
                                    <td>{{ $server->hostname }}</td>
                                    <td>{{ $server->ip_address }}</td>
                                    <td>{{ $server->type }}</td>
                                    <td>{{ $server->os }}</td>
                                    <td>
                                        <span class="badge badge-{{ $server->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($server->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewServer({{ $server->id }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" onclick="editServer({{ $server->id }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="deleteServer({{ $server->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No servers found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $servers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Server Modal -->
<div class="modal fade" id="addServerModal" tabindex="-1" role="dialog" aria-labelledby="addServerModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addServerModalLabel">Add New Server</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addServerForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="hostname">Hostname</label>
                        <input type="text" class="form-control" id="hostname" name="hostname" required>
                    </div>
                    <div class="form-group">
                        <label for="ip_address">IP Address</label>
                        <input type="text" class="form-control" id="ip_address" name="ip_address" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="web">Web Server</option>
                            <option value="database">Database Server</option>
                            <option value="mail">Mail Server</option>
                            <option value="dns">DNS Server</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="os">Operating System</label>
                        <select class="form-control" id="os" name="os" required>
                            <option value="linux">Linux</option>
                            <option value="windows">Windows</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Server</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function viewServer(id) {
    // Implement view server functionality
}

function editServer(id) {
    // Implement edit server functionality
}

function deleteServer(id) {
    if (confirm('Are you sure you want to delete this server?')) {
        // Implement delete server functionality
    }
}

$(document).ready(function() {
    $('#addServerForm').on('submit', function(e) {
        e.preventDefault();
        // Implement add server functionality
    });
});
</script>
@endpush 