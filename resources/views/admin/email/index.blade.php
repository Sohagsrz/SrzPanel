@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Email Accounts</h3>
                    <div class="card-tools">
                        <a href="{{ route('email.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Email Account
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Domain</th>
                                    <th>Quota</th>
                                    <th>Used</th>
                                    <th>Status</th>
                                    <th>Forwarding</th>
                                    <th>Autoresponder</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emails as $email)
                                <tr>
                                    <td>{{ $email->full_email }}</td>
                                    <td>{{ $email->domain->name }}</td>
                                    <td>{{ $email->quota }} MB</td>
                                    <td>{{ $email->used_quota }} MB</td>
                                    <td>
                                        <span class="badge badge-{{ $email->status === 'active' ? 'success' : 'danger' }}">
                                            {{ ucfirst($email->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($email->forward_to)
                                            <span class="text-success">
                                                <i class="fas fa-check"></i> {{ $email->forward_to }}
                                            </span>
                                        @else
                                            <span class="text-muted">None</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($email->autoresponder_enabled)
                                            <span class="text-success">
                                                <i class="fas fa-check"></i> Enabled
                                            </span>
                                        @else
                                            <span class="text-muted">Disabled</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('email.show', $email) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('email.edit', $email) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('email.destroy', $email) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
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