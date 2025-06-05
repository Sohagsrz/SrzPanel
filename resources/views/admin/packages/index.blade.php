@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Hosting Packages</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">New Package</a>
                    </div>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Disk Space</th>
                                <th>Bandwidth</th>
                                <th>Domains</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($packages as $package)
                                <tr>
                                    <td>{{ $package->name }}</td>
                                    <td>{{ $package->formatted_price }}</td>
                                    <td>{{ $package->formatted_disk_space }}</td>
                                    <td>{{ $package->formatted_bandwidth }}</td>
                                    <td>{{ $package->domains }}</td>
                                    <td>{{ $package->is_active ? 'Active' : 'Inactive' }}</td>
                                    <td>
                                        <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-sm btn-info">Edit</a>
                                        <form action="{{ route('admin.packages.destroy', $package) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No packages found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $packages->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 