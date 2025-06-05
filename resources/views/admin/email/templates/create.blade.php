@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create Email Template</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.email-templates.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Body</label>
                            <textarea name="body" id="editor" class="form-control" rows="10" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Variables (comma-separated)</label>
                            <input type="text" name="variables" class="form-control" 
                                   placeholder="e.g., name, email, domain">
                            <small class="form-text text-muted">
                                Use these variables in the template with {{variable}} syntax
                            </small>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Template</button>
                            <a href="{{ route('admin.email-templates.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
@endpush

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'color': [] }, { 'background': [] }],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link', 'image'],
            ['clean']
        ]
    }
});

document.querySelector('form').onsubmit = function() {
    var body = document.querySelector('input[name=body]');
    body.value = quill.root.innerHTML;
    return true;
};
</script>
@endpush
@endsection 