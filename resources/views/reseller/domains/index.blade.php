@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Domains</h3>
                    <div class="card-tools">
                        <a href="{{ route('reseller.domains.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Domain
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Domain</th>
                                    <th>User</th>
                                    <th>SSL</th>
                                    <th>PHP Version</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($domains as $domain)
                                <tr>
                                    <td>{{ $domain->id }}</td>
                                    <td>{{ $domain->name }}</td>
                                    <td>{{ $domain->user->name }}</td>
                                    <td>
                                        <span class="badge badge-{{ $domain->ssl ? 'success' : 'warning' }}">
                                            {{ $domain->ssl ? 'Active' : 'None' }}
                                        </span>
                                    </td>
                                    <td>{{ $domain->php_version }}</td>
                                    <td>
                                        <span class="badge badge-{{ $domain->is_active ? 'success' : 'danger' }}">
                                            {{ $domain->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $domain->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('reseller.domains.show', $domain) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('reseller.domains.edit', $domain) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('reseller.domains.destroy', $domain) }}" method="POST" class="d-inline">
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
                        {{ $domains->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 