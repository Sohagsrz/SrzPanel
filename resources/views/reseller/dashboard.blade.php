@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Reseller Dashboard</h3>
                </div>
                <div class="card-body">
                    <!-- Resource Overview -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $totalUsers }}</h3>
                                    <p>Total Users</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
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
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $totalDatabases }}</h3>
                                    <p>Total Databases</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-database"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $totalEmails }}</h3>
                                    <p>Total Email Accounts</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resource Usage -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Resource Usage</h3>
                                </div>
                                <div class="card-body">
                                    <div class="progress-group">
                                        Disk Space
                                        <span class="float-right"><b>{{ $diskUsage }}</b>/{{ $diskLimit }}</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ ($diskUsage / $diskLimit) * 100 > 80 ? 'danger' : (($diskUsage / $diskLimit) * 100 > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ ($diskUsage / $diskLimit) * 100 }}%"></div>
                                        </div>
                                    </div>
                                    <div class="progress-group mt-4">
                                        Bandwidth
                                        <span class="float-right"><b>{{ $bandwidthUsage }}</b>/{{ $bandwidthLimit }}</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ ($bandwidthUsage / $bandwidthLimit) * 100 > 80 ? 'danger' : (($bandwidthUsage / $bandwidthLimit) * 100 > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ ($bandwidthUsage / $bandwidthLimit) * 100 }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Package Limits</h3>
                                </div>
                                <div class="card-body">
                                    <div class="progress-group">
                                        Domains
                                        <span class="float-right"><b>{{ $totalDomains }}</b>/{{ $package->max_domains }}</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ ($totalDomains / $package->max_domains) * 100 > 80 ? 'danger' : (($totalDomains / $package->max_domains) * 100 > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ ($totalDomains / $package->max_domains) * 100 }}%"></div>
                                        </div>
                                    </div>
                                    <div class="progress-group mt-4">
                                        Databases
                                        <span class="float-right"><b>{{ $totalDatabases }}</b>/{{ $package->max_databases }}</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ ($totalDatabases / $package->max_databases) * 100 > 80 ? 'danger' : (($totalDatabases / $package->max_databases) * 100 > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ ($totalDatabases / $package->max_databases) * 100 }}%"></div>
                                        </div>
                                    </div>
                                    <div class="progress-group mt-4">
                                        Email Accounts
                                        <span class="float-right"><b>{{ $totalEmails }}</b>/{{ $package->max_email_accounts }}</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ ($totalEmails / $package->max_email_accounts) * 100 > 80 ? 'danger' : (($totalEmails / $package->max_email_accounts) * 100 > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ ($totalEmails / $package->max_email_accounts) * 100 }}%"></div>
                                        </div>
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
                                    <h3 class="card-title">Recent Users</h3>
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
                                                @foreach($recentUsers as $user)
                                                <tr>
                                                    <td>{{ $user->name }}</td>
                                                    <td>{{ $user->email }}</td>
                                                    <td>{{ $user->created_at->diffForHumans() }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $user->is_active ? 'success' : 'danger' }}">
                                                            {{ $user->is_active ? 'Active' : 'Inactive' }}
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
                                    <h3 class="card-title">Recent Domains</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Domain</th>
                                                    <th>User</th>
                                                    <th>Created</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentDomains as $domain)
                                                <tr>
                                                    <td>{{ $domain->name }}</td>
                                                    <td>{{ $domain->user->name }}</td>
                                                    <td>{{ $domain->created_at->diffForHumans() }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $domain->is_active ? 'success' : 'danger' }}">
                                                            {{ $domain->is_active ? 'Active' : 'Inactive' }}
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
                                            <a href="{{ route('reseller.users.create') }}" class="btn btn-primary btn-block">
                                                <i class="fas fa-user-plus"></i> Add User
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('reseller.domains.create') }}" class="btn btn-success btn-block">
                                                <i class="fas fa-globe"></i> Add Domain
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('reseller.backups.create') }}" class="btn btn-warning btn-block">
                                                <i class="fas fa-database"></i> Create Backup
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('reseller.settings.index') }}" class="btn btn-info btn-block">
                                                <i class="fas fa-cog"></i> Settings
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