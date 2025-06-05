@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Log Viewer</h3>
                    <div class="card-tools">
                        <form action="{{ route('admin.logs.search', ['path' => base64_encode($path)]) }}" method="GET" class="d-inline">
                            <div class="input-group">
                                <input type="text" name="query" class="form-control" placeholder="Search in log...">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-default">Search</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="log-viewer">
                        <pre class="log-content">{{ implode("\n", $content) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.log-viewer {
    background: #1e1e1e;
    padding: 1rem;
    border-radius: 4px;
    max-height: 600px;
    overflow-y: auto;
}
.log-content {
    color: #d4d4d4;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>
@endpush
@endsection 