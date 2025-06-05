@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Backups</h3>
                    <div class="card-tools">
                        <a href="{{ route('reseller.backups.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Create Backup
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                <tr>
                                    <td>{{ $backup->id }}</td>
                                    <td>{{ $backup->name }}</td>
                                    <td>{{ ucfirst($backup->type) }}</td>
                                    <td>{{ $backup->size }}</td>
                                    <td>
                                        <span class="badge badge-{{ $backup->status === 'completed' ? 'success' : ($backup->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($backup->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $backup->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('reseller.backups.show', $backup) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('reseller.backups.download', $backup) }}" class="btn btn-success btn-sm">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <form action="{{ route('reseller.backups.destroy', $backup) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $backups->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 