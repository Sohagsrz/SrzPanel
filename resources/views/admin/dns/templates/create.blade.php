@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create DNS Template</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.dns-templates.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>DNS Records</label>
                            <div id="records-container">
                                <div class="record-entry mb-3">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <select name="records[0][type]" class="form-control" required>
                                                <option value="A">A</option>
                                                <option value="AAAA">AAAA</option>
                                                <option value="CNAME">CNAME</option>
                                                <option value="MX">MX</option>
                                                <option value="TXT">TXT</option>
                                                <option value="NS">NS</option>
                                                <option value="SRV">SRV</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="records[0][name]" class="form-control" placeholder="Name" required>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="text" name="records[0][value]" class="form-control" placeholder="Value" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="records[0][ttl]" class="form-control" placeholder="TTL" value="3600" required>
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" name="records[0][priority]" class="form-control" placeholder="Priority">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-secondary mt-2" onclick="addRecord()">Add Record</button>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Create Template</button>
                            <a href="{{ route('admin.dns-templates.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let recordCount = 1;

function addRecord() {
    const container = document.getElementById('records-container');
    const template = `
        <div class="record-entry mb-3">
            <div class="row">
                <div class="col-md-2">
                    <select name="records[${recordCount}][type]" class="form-control" required>
                        <option value="A">A</option>
                        <option value="AAAA">AAAA</option>
                        <option value="CNAME">CNAME</option>
                        <option value="MX">MX</option>
                        <option value="TXT">TXT</option>
                        <option value="NS">NS</option>
                        <option value="SRV">SRV</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="records[${recordCount}][name]" class="form-control" placeholder="Name" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="records[${recordCount}][value]" class="form-control" placeholder="Value" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="records[${recordCount}][ttl]" class="form-control" placeholder="TTL" value="3600" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="records[${recordCount}][priority]" class="form-control" placeholder="Priority">
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', template);
    recordCount++;
}
</script>
@endpush
@endsection 