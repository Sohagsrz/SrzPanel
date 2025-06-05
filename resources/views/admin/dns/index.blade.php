@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">DNS Manager</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createZoneModal">
                            <i class="fas fa-plus"></i> New Zone
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Zone Name</th>
                                    <th>Type</th>
                                    <th>Records</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($zones as $zone)
                                <tr>
                                    <td>{{ $zone['name'] }}</td>
                                    <td>{{ $zone['type'] }}</td>
                                    <td>{{ count($zone['records']) }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-toggle="modal" 
                                                    data-target="#addRecordModal"
                                                    data-zone-name="{{ $zone['name'] }}">
                                                <i class="fas fa-plus"></i> Add Record
                                            </button>
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-toggle="modal" 
                                                    data-target="#viewRecordsModal"
                                                    data-zone-name="{{ $zone['name'] }}"
                                                    data-records='@json($zone['records'])'>
                                                <i class="fas fa-list"></i> View Records
                                            </button>
                                            <form action="{{ route('admin.dns.destroy') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="zone_name" value="{{ $zone['name'] }}">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Are you sure you want to delete this zone?')">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No DNS zones found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Zone Modal -->
<div class="modal fade" id="createZoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New DNS Zone</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.dns.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Zone Name</label>
                        <input type="text" name="zone_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Zone Type</label>
                        <select name="type" class="form-control" required>
                            <option value="master">Master</option>
                            <option value="slave">Slave</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Record Modal -->
<div class="modal fade" id="addRecordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add DNS Record</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('admin.dns.add-record') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="zone_name" id="recordZoneName">
                    <div class="form-group">
                        <label>Record Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Record Type</label>
                        <select name="type" class="form-control" required>
                            <option value="A">A</option>
                            <option value="AAAA">AAAA</option>
                            <option value="CNAME">CNAME</option>
                            <option value="MX">MX</option>
                            <option value="TXT">TXT</option>
                            <option value="NS">NS</option>
                            <option value="PTR">PTR</option>
                            <option value="SRV">SRV</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Value</label>
                        <input type="text" name="value" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>TTL (seconds)</label>
                        <input type="number" name="ttl" class="form-control" value="3600" min="60" max="86400" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Records Modal -->
<div class="modal fade" id="viewRecordsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">DNS Records for <span id="viewZoneName"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>TTL</th>
                            </tr>
                        </thead>
                        <tbody id="recordsTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Record Modal
    $('#addRecordModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var zoneName = button.data('zone-name');
        var modal = $(this);
        modal.find('#recordZoneName').val(zoneName);
    });

    // View Records Modal
    $('#viewRecordsModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var zoneName = button.data('zone-name');
        var records = button.data('records');
        var modal = $(this);
        
        modal.find('#viewZoneName').text(zoneName);
        
        var tbody = modal.find('#recordsTableBody');
        tbody.empty();
        
        records.forEach(function(record) {
            tbody.append(`
                <tr>
                    <td>${record.name}</td>
                    <td>${record.type}</td>
                    <td>${record.value}</td>
                    <td>${record.ttl}</td>
                </tr>
            `);
        });
    });
});
</script>
@endpush
@endsection 