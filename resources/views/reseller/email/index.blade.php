@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Email Accounts</h3>
                    <div class="card-tools">
                        <a href="{{ route('reseller.email.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Email Account
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>User</th>
                                    <th>Domain</th>
                                    <th>Quota</th>
                                    <th>Used</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emails as $email)
                                <tr>
                                    <td>{{ $email->id }}</td>
                                    <td>{{ $email->email }}</td>
                                    <td>{{ $email->user->name }}</td>
                                    <td>{{ $email->domain->name }}</td>
                                    <td>{{ $email->quota }}</td>
                                    <td>{{ $email->used }}</td>
                                    <td>
                                        <span class="badge badge-{{ $email->is_active ? 'success' : 'danger' }}">
                                            {{ $email->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>{{ $email->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('reseller.email.show', $email) }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('reseller.email.edit', $email) }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('reseller.email.destroy', $email) }}" method="POST" class="d-inline">
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
                        {{ $emails->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 