@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<style>
    .stat-card {
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .progress {
        height: 8px;
    }
    .server-status {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }
    .status-active { background-color: #10B981; }
    .status-inactive { background-color: #EF4444; }
    .status-warning { background-color: #F59E0B; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Dashboard</h1>
    
    <!-- System Statistics -->
    <div class="row mt-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">CPU Usage</h6>
                            <h2 class="mb-0">{{ $systemStats['cpu']['usage'] }}%</h2>
                        </div>
                        <i class="fas fa-microchip fa-2x"></i>
                    </div>
                    <div class="progress bg-white bg-opacity-25 mt-3">
                        <div class="progress-bar bg-white" role="progressbar" 
                             style="width: {{ $systemStats['cpu']['usage'] }}%"></div>
                    </div>
                    <small class="mt-2 d-block">Load: {{ implode(', ', $systemStats['cpu']['load']) }}</small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">RAM Usage</h6>
                            <h2 class="mb-0">{{ $systemStats['ram']['usage'] }}%</h2>
                        </div>
                        <i class="fas fa-memory fa-2x"></i>
                    </div>
                    <div class="progress bg-white bg-opacity-25 mt-3">
                        <div class="progress-bar bg-white" role="progressbar" 
                             style="width: {{ $systemStats['ram']['usage'] }}%"></div>
                    </div>
                    <small class="mt-2 d-block">
                        {{ number_format($systemStats['ram']['used'] / 1024 / 1024, 2) }}GB / 
                        {{ number_format($systemStats['ram']['total'] / 1024 / 1024, 2) }}GB
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Disk Usage</h6>
                            <h2 class="mb-0">{{ $systemStats['disk']['usage'] }}%</h2>
                        </div>
                        <i class="fas fa-hdd fa-2x"></i>
                    </div>
                    <div class="progress bg-white bg-opacity-25 mt-3">
                        <div class="progress-bar bg-white" role="progressbar" 
                             style="width: {{ $systemStats['disk']['usage'] }}%"></div>
                    </div>
                    <small class="mt-2 d-block">
                        {{ number_format($systemStats['disk']['used'] / 1024 / 1024 / 1024, 2) }}GB / 
                        {{ number_format($systemStats['disk']['total'] / 1024 / 1024 / 1024, 2) }}GB
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4 stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">Bandwidth</h6>
                            <h2 class="mb-0">{{ $systemStats['bandwidth']['usage'] }}%</h2>
                        </div>
                        <i class="fas fa-network-wired fa-2x"></i>
                    </div>
                    <div class="progress bg-white bg-opacity-25 mt-3">
                        <div class="progress-bar bg-white" role="progressbar" 
                             style="width: {{ $systemStats['bandwidth']['usage'] }}%"></div>
                    </div>
                    <small class="mt-2 d-block">
                        {{ number_format($systemStats['bandwidth']['used'] / 1024 / 1024, 2) }}GB / 
                        {{ number_format($systemStats['bandwidth']['total'] / 1024 / 1024, 2) }}GB
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- User and Resource Statistics -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-users me-1"></i>
                    User Statistics
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h3>{{ $counts['totalUsers'] }}</h3>
                                <p class="text-muted">Total Users</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h3>{{ $counts['totalResellers'] }}</h3>
                                <p class="text-muted">Resellers</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h3>{{ $counts['totalAdmins'] }}</h3>
                                <p class="text-muted">Admins</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-server me-1"></i>
                    Resource Statistics
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3>{{ $counts['domains'] }}</h3>
                                <p class="text-muted">Domains</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3>{{ $counts['databases'] }}</h3>
                                <p class="text-muted">Databases</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3>{{ $counts['emails'] }}</h3>
                                <p class="text-muted">Email Accounts</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <h3>{{ $counts['activeServers'] }}</h3>
                                <p class="text-muted">Active Servers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Server Status and Recent Activity -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-server me-1"></i>
                    Server Status
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Server</th>
                                    <th>Status</th>
                                    <th>Last Check</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($serverStatus as $server)
                                <tr>
                                    <td>{{ $server->name }}</td>
                                    <td>
                                        <span class="server-status status-{{ $server->status }}"></span>
                                        {{ ucfirst($server->status) }}
                                    </td>
                                    <td>{{ $server->last_check_at->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center">No servers found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i>
                    Recent Activity
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentActivity as $activity)
                                <tr>
                                    <td>{{ $activity->description }}</td>
                                    <td>{{ $activity->created_at->diffForHumans() }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center">No recent activity</td>
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Refresh dashboard data every 5 minutes
    setInterval(function() {
        fetch('/admin/dashboard/refresh')
            .then(response => response.json())
            .then(data => {
                // Update dashboard data
                updateDashboard(data);
            })
            .catch(error => console.error('Error refreshing dashboard:', error));
    }, 300000);
});

function updateDashboard(data) {
    // Update system stats
    updateSystemStats(data.systemStats);
    // Update counts
    updateCounts(data.counts);
    // Update server status
    updateServerStatus(data.serverStatus);
    // Update recent activity
    updateRecentActivity(data.recentActivity);
}

function updateSystemStats(stats) {
    // Update CPU
    document.querySelector('.cpu-usage').textContent = stats.cpu.usage + '%';
    document.querySelector('.cpu-progress').style.width = stats.cpu.usage + '%';
    
    // Update RAM
    document.querySelector('.ram-usage').textContent = stats.ram.usage + '%';
    document.querySelector('.ram-progress').style.width = stats.ram.usage + '%';
    
    // Update Disk
    document.querySelector('.disk-usage').textContent = stats.disk.usage + '%';
    document.querySelector('.disk-progress').style.width = stats.disk.usage + '%';
    
    // Update Bandwidth
    document.querySelector('.bandwidth-usage').textContent = stats.bandwidth.usage + '%';
    document.querySelector('.bandwidth-progress').style.width = stats.bandwidth.usage + '%';
}

function updateCounts(counts) {
    // Update user counts
    document.querySelector('.total-users').textContent = counts.totalUsers;
    document.querySelector('.total-resellers').textContent = counts.totalResellers;
    document.querySelector('.total-admins').textContent = counts.totalAdmins;
    
    // Update resource counts
    document.querySelector('.total-domains').textContent = counts.domains;
    document.querySelector('.total-databases').textContent = counts.databases;
    document.querySelector('.total-emails').textContent = counts.emails;
    document.querySelector('.active-servers').textContent = counts.activeServers;
}

function updateServerStatus(servers) {
    const tbody = document.querySelector('.server-status-table tbody');
    tbody.innerHTML = servers.map(server => `
        <tr>
            <td>${server.name}</td>
            <td>
                <span class="server-status status-${server.status}"></span>
                ${server.status.charAt(0).toUpperCase() + server.status.slice(1)}
            </td>
            <td>${new Date(server.last_check_at).toLocaleString()}</td>
        </tr>
    `).join('');
}

function updateRecentActivity(activities) {
    const tbody = document.querySelector('.recent-activity-table tbody');
    tbody.innerHTML = activities.map(activity => `
        <tr>
            <td>${activity.description}</td>
            <td>${new Date(activity.created_at).toLocaleString()}</td>
        </tr>
    `).join('');
}
</script>
@endpush 