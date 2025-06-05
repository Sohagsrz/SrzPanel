@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit File: {{ $fileInfo['name'] }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.filemanager.index', ['path' => dirname($fileInfo['path'])]) }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.filemanager.save') }}" method="POST">
                        @csrf
                        <input type="hidden" name="path" value="{{ $fileInfo['path'] }}">
                        
                        <div class="form-group">
                            <label>File Information</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <p><strong>Size:</strong> {{ number_format($fileInfo['size'] / 1024, 2) }} KB</p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Type:</strong> {{ $fileInfo['type'] }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Modified:</strong> {{ date('Y-m-d H:i:s', $fileInfo['modified']) }}</p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Permissions:</strong> {{ $fileInfo['permissions'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contents">File Contents</label>
                            <textarea name="contents" id="contents" class="form-control" rows="20" style="font-family: monospace;">{{ $contents }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="{{ route('admin.filemanager.index', ['path' => dirname($fileInfo['path'])]) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var editor = CodeMirror.fromTextArea(document.getElementById('contents'), {
        mode: '{{ $fileInfo['type'] === 'text/html' ? 'htmlmixed' : ($fileInfo['type'] === 'text/css' ? 'css' : ($fileInfo['type'] === 'application/javascript' ? 'javascript' : ($fileInfo['type'] === 'application/x-httpd-php' ? 'php' : 'text/plain'))) }}',
        theme: 'monokai',
        lineNumbers: true,
        indentUnit: 4,
        tabSize: 4,
        lineWrapping: true,
        matchBrackets: true,
        autoCloseBrackets: true,
        extraKeys: {
            "Ctrl-Space": "autocomplete"
        }
    });

    // Set editor height
    editor.setSize(null, 500);
});
</script>
@endpush
@endsection 