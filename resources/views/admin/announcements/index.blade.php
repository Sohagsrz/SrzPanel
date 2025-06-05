@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Announcements</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                            Create Announcement
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($announcements as $announcement)
                                <tr>
                                    <td>{{ $announcement->title }}</td>
                                    <td>
                                        <span class="badge badge-{{ $announcement->type }}">
                                            {{ ucfirst($announcement->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $announcement->start_date?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                    <td>{{ $announcement->end_date?->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $announcement->is_active ? 'success' : 'danger' }}">
                                            {{ $announcement->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.announcements.edit', $announcement) }}" 
                                           class="btn btn-sm btn-info">
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.announcements.toggle', $announcement) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            <button type="submit" 
                                                    class="btn btn-sm btn-{{ $announcement->is_active ? 'warning' : 'success' }}">
                                                {{ $announcement->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.announcements.destroy', $announcement) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
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
@endsection 