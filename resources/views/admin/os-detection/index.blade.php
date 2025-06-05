@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">OS Detection</h3>
                </div>
                <div class="card-body">
                    <p>Detected OS: <strong>{{ $osType }}</strong></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 