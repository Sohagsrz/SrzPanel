@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New Virtual Host</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.vhosts.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="domain">Domain Name</label>
                            <input type="text" class="form-control" id="domain" name="domain" required>
                        </div>
                        <div class="form-group">
                            <label for="user">User</label>
                            <input type="text" class="form-control" id="user" name="user" required>
                        </div>
                        <div class="form-group">
                            <label for="php_version">PHP Version</label>
                            <select class="form-control" id="php_version" name="php_version" required>
                                <option value="8.1">PHP 8.1</option>
                                <option value="8.0">PHP 8.0</option>
                                <option value="7.4">PHP 7.4</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="ssl" name="ssl" value="1">
                                <label class="custom-control-label" for="ssl">Enable SSL</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Create Virtual Host</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 