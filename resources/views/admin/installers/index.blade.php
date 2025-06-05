@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Available Installers</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Version</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($installers as $installer)
                                <tr>
                                    <td>{{ $installer['name'] }}</td>
                                    <td>{{ $installer['version'] }}</td>
                                    <td>{{ $installer['status'] }}</td>
                                    <td>
                                        <form action="{{ route('admin.installers.install') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="installer" value="{{ $installer['name'] }}">
                                            <button type="submit" class="btn btn-sm btn-success">Install</button>
                                        </form>
                                        <form action="{{ route('admin.installers.uninstall') }}" method="POST" style="display:inline;">
                                            @csrf
                                            <input type="hidden" name="installer" value="{{ $installer['name'] }}">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Uninstall</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">No installers available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 