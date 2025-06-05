@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">SSL Certificates</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#letsEncryptModal">
                            <i class="fas fa-lock"></i> Request Let's Encrypt
                        </button>
                        <form action="{{ route('ssl.renew-all') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-sync"></i> Renew All
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Domain</th>
                                    <th>Issuer</th>
                                    <th>Valid Until</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($certs as $cert)
                                <tr>
                                    <td>{{ $cert->domain }}</td>
                                    <td>{{ $cert->issuer }}</td>
                                    <td>{{ $cert->valid_until }}</td>
                                    <td>
                                        <span class="badge badge-{{ $cert->is_valid ? 'success' : 'danger' }}">
                                            {{ $cert->is_valid ? 'Valid' : 'Expired' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-info" 
                                                    data-toggle="modal" 
                                                    data-target="#viewCertModal"
                                                    data-cert='@json($cert)'>
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <form action="{{ route('ssl.destroy', $cert->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('Are you sure you want to remove this certificate?')">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center">No SSL certificates found.</td>
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

<!-- Let's Encrypt Modal -->
<div class="modal fade" id="letsEncryptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Let's Encrypt Certificate</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="{{ route('ssl.request-lets-encrypt') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Domain</label>
                        <input type="text" name="domain" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Request Certificate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Certificate Modal -->
<div class="modal fade" id="viewCertModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Certificate Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            <th>Domain</th>
                            <td id="certDomain"></td>
                        </tr>
                        <tr>
                            <th>Issuer</th>
                            <td id="certIssuer"></td>
                        </tr>
                        <tr>
                            <th>Valid From</th>
                            <td id="certValidFrom"></td>
                        </tr>
                        <tr>
                            <th>Valid Until</th>
                            <td id="certValidUntil"></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td id="certStatus"></td>
                        </tr>
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
    $('#viewCertModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var cert = button.data('cert');
        var modal = $(this);
        
        modal.find('#certDomain').text(cert.domain);
        modal.find('#certIssuer').text(cert.issuer);
        modal.find('#certValidFrom').text(cert.valid_from);
        modal.find('#certValidUntil').text(cert.valid_until);
        modal.find('#certStatus').html(
            `<span class="badge badge-${cert.is_valid ? 'success' : 'danger'}">${cert.is_valid ? 'Valid' : 'Expired'}</span>`
        );
    });
});
</script>
@endpush
@endsection 