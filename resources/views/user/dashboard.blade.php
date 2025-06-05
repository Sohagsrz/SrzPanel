@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">User Dashboard</h3>
                </div>
                <div class="card-body">
                    <!-- Resource Overview -->
                    <div class="row">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
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
                            <div class="small-box bg-success">
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
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $totalEmails }}</h3>
                                    <p>Email Accounts</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $totalFtp }}</h3>
                                    <p>FTP Accounts</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-file-upload"></i>
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
                                        <span class="float-right"><b>{{ $diskUsage }}</b>/{{ $package->disk_limit }}</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ ($diskUsage / $package->disk_limit) * 100 > 80 ? 'danger' : (($diskUsage / $package->disk_limit) * 100 > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ ($diskUsage / $package->disk_limit) * 100 }}%"></div>
                                        </div>
                                    </div>
                                    <div class="progress-group mt-4">
                                        Bandwidth
                                        <span class="float-right"><b>{{ $bandwidthUsage }}</b>/{{ $package->bandwidth_limit }}</span>
                                        <div class="progress">
                                            <div class="progress-bar bg-{{ ($bandwidthUsage / $package->bandwidth_limit) * 100 > 80 ? 'danger' : (($bandwidthUsage / $package->bandwidth_limit) * 100 > 60 ? 'warning' : 'success') }}" 
                                                 style="width: {{ ($bandwidthUsage / $package->bandwidth_limit) * 100 }}%"></div>
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
                                    <h3 class="card-title">Recent Domains</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Domain</th>
                                                    <th>Created</th>
                                                    <th>Status</th>
                                                    <th>SSL</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentDomains as $domain)
                                                <tr>
                                                    <td>{{ $domain->name }}</td>
                                                    <td>{{ $domain->created_at->diffForHumans() }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $domain->is_active ? 'success' : 'danger' }}">
                                                            {{ $domain->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-{{ $domain->ssl ? 'success' : 'warning' }}">
                                                            {{ $domain->ssl ? 'Active' : 'None' }}
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
                                    <h3 class="card-title">Recent Backups</h3>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Size</th>
                                                    <th>Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($recentBackups as $backup)
                                                <tr>
                                                    <td>{{ $backup->name }}</td>
                                                    <td>{{ ucfirst($backup->type) }}</td>
                                                    <td>{{ $backup->size }}</td>
                                                    <td>{{ $backup->created_at->diffForHumans() }}</td>
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
                                            <a href="{{ route('user.domains.create') }}" class="btn btn-primary btn-block">
                                                <i class="fas fa-globe"></i> Add Domain
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('user.databases.create') }}" class="btn btn-success btn-block">
                                                <i class="fas fa-database"></i> Add Database
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('user.email.create') }}" class="btn btn-warning btn-block">
                                                <i class="fas fa-envelope"></i> Add Email
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <a href="{{ route('user.profile.index') }}" class="btn btn-info btn-block">
                                                <i class="fas fa-user"></i> Profile
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