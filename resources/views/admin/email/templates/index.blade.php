@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Email Templates</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.email-templates.create') }}" class="btn btn-primary">
                            Create Template
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Subject</th>
                                    <th>Variables</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                <tr>
                                    <td>{{ $template->name }}</td>
                                    <td>{{ $template->subject }}</td>
                                    <td>{{ implode(', ', $template->variables ?? []) }}</td>
                                    <td>
                                        <a href="{{ route('admin.email-templates.edit', $template) }}" 
                                           class="btn btn-sm btn-info">
                                            Edit
                                        </a>
                                        <a href="{{ route('admin.email-templates.preview', $template) }}" 
                                           class="btn btn-sm btn-secondary">
                                            Preview
                                        </a>
                                        <form action="{{ route('admin.email-templates.destroy', $template) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this template?');">
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