@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Settings</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('reseller.settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- General Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">General Settings</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="company_name">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" value="{{ $settings->company_name }}">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ $settings->email }}">
                                </div>
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="{{ $settings->phone }}">
                                </div>
                            </div>
                        </div>

                        <!-- Resource Limits -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="card-title">Resource Limits</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="max_domains">Maximum Domains</label>
                                    <input type="number" class="form-control" id="max_domains" name="max_domains" value="{{ $settings->max_domains }}">
                                </div>
                                <div class="form-group">
                                    <label for="max_databases">Maximum Databases</label>
                                    <input type="number" class="form-control" id="max_databases" name="max_databases" value="{{ $settings->max_databases }}">
                                </div>
                                <div class="form-group">
                                    <label for="max_email_accounts">Maximum Email Accounts</label>
                                    <input type="number" class="form-control" id="max_email_accounts" name="max_email_accounts" value="{{ $settings->max_email_accounts }}">
                                </div>
                                <div class="form-group">
                                    <label for="max_ftp_accounts">Maximum FTP Accounts</label>
                                    <input type="number" class="form-control" id="max_ftp_accounts" name="max_ftp_accounts" value="{{ $settings->max_ftp_accounts }}">
                                </div>
                            </div>
                        </div>

                        <!-- Backup Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="card-title">Backup Settings</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="backup_retention">Backup Retention (days)</label>
                                    <input type="number" class="form-control" id="backup_retention" name="backup_retention" value="{{ $settings->backup_retention }}">
                                </div>
                                <div class="form-group">
                                    <label for="backup_frequency">Backup Frequency</label>
                                    <select class="form-control" id="backup_frequency" name="backup_frequency">
                                        <option value="daily" {{ $settings->backup_frequency === 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="weekly" {{ $settings->backup_frequency === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="monthly" {{ $settings->backup_frequency === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h4 class="card-title">Security Settings</h4>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="two_factor_auth">Two-Factor Authentication</label>
                                    <select class="form-control" id="two_factor_auth" name="two_factor_auth">
                                        <option value="enabled" {{ $settings->two_factor_auth ? 'selected' : '' }}>Enabled</option>
                                        <option value="disabled" {{ !$settings->two_factor_auth ? 'selected' : '' }}>Disabled</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="session_timeout">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" value="{{ $settings->session_timeout }}">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 