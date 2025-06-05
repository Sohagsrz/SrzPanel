@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Admin Dashboard</h3>
                </div>
                <div class="card-body">
                    <!-- System Overview -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $totalResellers }}</h3>
                                    <p>Total Resellers</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $totalUsers }}</h3>
                                    <p>Total Users</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-user"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $totalDomains }}</h3>
                                    <p>Total Domains</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $totalServers }}</h3>
                                    <p>Total Servers</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-server"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">System Health</h3>
                                </div>
                                <div class="card-body">
                                    <div class="progress-group">
                                        CPU Usage
                                        <span class="float-right"><b>{{ $cpuUsage }}%</b></span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ $cpuUsage > 80 ? 'danger' : ($cpuUsage > 60 ? 'warning' : 'success') }}" style="width: {{ $cpuUsage }}%"></div>
                                        </div>
                                    </div>
                                    <div class="progress-group mt-4">
                                        Memory Usage
                                        <span class="float-right"><b>{{ $memoryUsage }}%</b></span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ $memoryUsage > 80 ? 'danger' : ($memoryUsage > 60 ? 'warning' : 'success') }}" style="width: {{ $memoryUsage }}%"></div>
                                        </div>
                                    </div>
                                    <div class="progress-group mt-4">
                                        Disk Usage
                                        <span class="float-right"><b>{{ $diskUsage }}%</b></span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ $diskUsage > 80 ? 'danger' : ($diskUsage > 60 ? 'warning' : 'success') }}" style="width: {{ $diskUsage }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Server Status</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Server</th>
                                                    <th>Status</th>
                                                    <th>Load</th>
                                                    <th>Uptime</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($servers as $server)
                                                <tr>
                                                    <td>{{ $server->name }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $server->status === 'online' ? 'success' : 'danger' }}">
                                                            {{ ucfirst($server->status) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $server->load }}</td>
                                                    <td>{{ $server->uptime }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Recent Resellers</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Created</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentResellers as $reseller)
                                                <tr>
                                                    <td>{{ $reseller->name }}</td>
                                                    <td>{{ $reseller->email }}</td>
                                                    <td>{{ $reseller->created_at->diffForHumans() }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $reseller->is_active ? 'success' : 'danger' }}">
                                                            {{ $reseller->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">System Logs</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Type</th>
                                                    <th>Message</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($systemLogs as $log)
                                                <tr>
                                                    <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $log->type === 'error' ? 'danger' : ($log->type === 'warning' ? 'warning' : 'info') }}">
                                                            {{ ucfirst($log->type) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $log->message }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Quick Actions</h3>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <a href="{{ route('admin.servers.create') }}" class="btn btn-primary btn-block">
                                                <i class="fas fa-server"></i> Add Server
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('admin.packages.create') }}" class="btn btn-success btn-block">
                                                <i class="fas fa-box"></i> Create Package
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('admin.updates.create') }}" class="btn btn-warning btn-block">
                                                <i class="fas fa-sync"></i> System Update
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('admin.backups.create') }}" class="btn btn-info btn-block">
                                                <i class="fas fa-database"></i> Create Backup
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 