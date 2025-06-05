@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Databases</h3>
                    <div class="card-tools">
                        <a href="{{ route('reseller.databases.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Database
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
                                    <th>User</th>
                                    <th>Domain</th>
                                    <th>Size</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($databases as $database)
                                <tr>
                                    <td>{{ $database->id }}</td>
                                    <td>{{ $database->name }}</td>
                                    <td>{{ $database->user->name }}</td>
                                    <td>{{ $database->domain->name }}</td>
                                    <td>{{ $database->size }}</td>
                                    <td>
                                        <span class="badge badge-{{ $database->is_active ? 'success' : 'danger' }}">
                                            {{ $database->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $database->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('reseller.databases.show', $database) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('reseller.databases.edit', $database) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('reseller.databases.destroy', $database) }}" method="POST" class="d-inline">
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
                        {{ $databases->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 