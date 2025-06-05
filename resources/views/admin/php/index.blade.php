@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">PHP Versions</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#installModal">
                            <i class="fas fa-plus"></i> Install PHP Version
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Version</th>
                                    <th>Status</th>
                                    <th>Modules</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($versions as $version)
                                <tr>
                                    <td>
                                        PHP {{ $version['version'] }}
                                        @if($version['version'] === $currentVersion)
                                            <span class="badge badge-success">Current</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $version['status'] === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($version['status']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modulesModal{{ $version['version'] }}">
                                                <i class="fas fa-list"></i> View Modules
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#installModuleModal{{ $version['version'] }}">
                                                <i class="fas fa-plus"></i> Install Module
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#configModal{{ $version['version'] }}">
                                                <i class="fas fa-cog"></i> Configure
                                            </button>
                                            <form action="{{ route('admin.php.uninstall') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="version" value="{{ $version['version'] }}">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure? This will uninstall PHP {{ $version['version'] }}.')">
                                                    <i class="fas fa-trash"></i> Uninstall
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

<!-- Install PHP Version Modal -->
<div class="modal fade" id="installModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.php.install') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Install PHP Version</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="version">PHP Version</label>
                        <select class="form-control" id="version" name="version" required>
                            <option value="7.4">PHP 7.4</option>
                            <option value="8.0">PHP 8.0</option>
                            <option value="8.1">PHP 8.1</option>
                            <option value="8.2">PHP 8.2</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Install</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modules Modal -->
@foreach($versions as $version)
<div class="modal fade" id="modulesModal{{ $version['version'] }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">PHP {{ $version['version'] }} Modules</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    @foreach($version['modules'] as $module)
                    <div class="col-md-4">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="module{{ $version['version'] }}{{ $module }}" checked disabled>
                            <label class="custom-control-label" for="module{{ $version['version'] }}{{ $module }}">{{ $module }}</label>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Install Module Modal -->
<div class="modal fade" id="installModuleModal{{ $version['version'] }}" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.php.install-extension') }}" method="POST">
                @csrf
                <input type="hidden" name="version" value="{{ $version['version'] }}">
                <div class="modal-header">
                    <h5 class="modal-title">Install PHP Module</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="extension">Module Name</label>
                        <input type="text" class="form-control" id="extension" name="extension" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Install</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Configuration Modal -->
<div class="modal fade" id="configModal{{ $version['version'] }}" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.php.update-ini') }}" method="POST">
                @csrf
                <input type="hidden" name="version" value="{{ $version['version'] }}">
                <div class="modal-header">
                    <h5 class="modal-title">PHP {{ $version['version'] }} Configuration</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="memory_limit">Memory Limit</label>
                        <input type="text" class="form-control" id="memory_limit" name="settings[memory_limit]" value="{{ $configs[$version['version']]['memory_limit'] ?? '128M' }}">
                    </div>
                    <div class="form-group">
                        <label for="max_execution_time">Max Execution Time</label>
                        <input type="number" class="form-control" id="max_execution_time" name="settings[max_execution_time]" value="{{ $configs[$version['version']]['max_execution_time'] ?? 30 }}">
                    </div>
                    <div class="form-group">
                        <label for="upload_max_filesize">Upload Max Filesize</label>
                        <input type="text" class="form-control" id="upload_max_filesize" name="settings[upload_max_filesize]" value="{{ $configs[$version['version']]['upload_max_filesize'] ?? '2M' }}">
                    </div>
                    <div class="form-group">
                        <label for="post_max_size">Post Max Size</label>
                        <input type="text" class="form-control" id="post_max_size" name="settings[post_max_size]" value="{{ $configs[$version['version']]['post_max_size'] ?? '8M' }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection 