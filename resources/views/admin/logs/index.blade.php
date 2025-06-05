@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Logs</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Service</th>
                                    <th>Type</th>
                                    <th>Path</th>
                                    <th>Size</th>
                                    <th>Last Modified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                <tr>
                                    <td>{{ ucfirst($log['service']) }}</td>
                                    <td>{{ ucfirst($log['type']) }}</td>
                                    <td>{{ $log['path'] }}</td>
                                    <td>{{ $log['size'] }}</td>
                                    <td>{{ $log['last_modified'] }}</td>
                                    <td>
                                        <a href="{{ route('admin.logs.show', ['path' => base64_encode($log['path'])]) }}" 
                                           class="btn btn-sm btn-info">
                                            View
                                        </a>
                                        <a href="{{ route('admin.logs.download', ['path' => base64_encode($log['path'])]) }}" 
                                           class="btn btn-sm btn-success">
                                            Download
                                        </a>
                                        <form action="{{ route('admin.logs.clear', ['path' => base64_encode($log['path'])]) }}" 
                                              method="POST" 
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to clear this log?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">Clear</button>
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