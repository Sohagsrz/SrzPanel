@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Announcement</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label>Title</label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $announcement->title) }}" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Content</label>
                            <textarea name="content" id="editor" class="form-control" rows="10" required>{{ old('content', $announcement->content) }}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Type</label>
                            <select name="type" class="form-control" required>
                                <option value="info" {{ $announcement->type === 'info' ? 'selected' : '' }}>Info</option>
                                <option value="success" {{ $announcement->type === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="warning" {{ $announcement->type === 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="danger" {{ $announcement->type === 'danger' ? 'selected' : '' }}>Danger</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="datetime-local" name="start_date" class="form-control" value="{{ old('start_date', $announcement->start_date ? $announcement->start_date->format('Y-m-d\TH:i')) : '' }}">
                        </div>
                        
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="datetime-local" name="end_date" class="form-control" value="{{ old('end_date', $announcement->end_date ? $announcement->end_date->format('Y-m-d\TH:i')) : '' }}">
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ $announcement->is_active ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Announcement</button>
                            <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Cancel</a>
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

// Set initial content
quill.root.innerHTML = document.querySelector('#editor').value;

document.querySelector('form').onsubmit = function() {
    var content = document.querySelector('input[name=content]');
    content.value = quill.root.innerHTML;
    return true;
};
</script>
@endpush
@endsection 