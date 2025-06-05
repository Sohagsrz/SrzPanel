@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Cron Jobs</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.cron.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Cron Job
                        </a>
                        <a href="{{ route('admin.cron.logs') }}" class="btn btn-info">
                            <i class="fas fa-list"></i> View Logs
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Schedule</th>
                                    <th>Command</th>
                                    <th>Last Run</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($jobs as $job)
                                <tr>
                                    <td>{{ $job['schedule'] }}</td>
                                    <td>{{ $job['command'] }}</td>
                                    <td class="last-run" data-schedule="{{ $job['schedule'] }}" data-command="{{ $job['command'] }}">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </td>
                                    <td class="status" data-schedule="{{ $job['schedule'] }}" data-command="{{ $job['command'] }}">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.cron.destroy') }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="schedule" value="{{ $job['schedule'] }}">
                                            <input type="hidden" name="command" value="{{ $job['command'] }}">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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

@push('scripts')
<script>
$(document).ready(function() {
    function updateJobStatus() {
        $('.last-run, .status').each(function() {
            var element = $(this);
            var schedule = element.data('schedule');
            var command = element.data('command');

            $.get('{{ route('admin.cron.status') }}', {
                schedule: schedule,
                command: command
            }, function(data) {
                if (element.hasClass('last-run')) {
                    element.text(data.last_run || 'Never');
                } else {
                    var statusClass = data.status === 'success' ? 'success' : 
                                    data.status === 'error' ? 'danger' : 'warning';
                    element.html('<span class="badge badge-' + statusClass + '">' + 
                               data.status.charAt(0).toUpperCase() + data.status.slice(1) + '</span>');
                }
            });
        });
    }

    // Update status immediately and then every 30 seconds
    updateJobStatus();
    setInterval(updateJobStatus, 30000);
});
</script>
@endpush
@endsection 