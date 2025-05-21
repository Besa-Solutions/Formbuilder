@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0 text-danger">Error</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h5>{{ $message }}</h5>
                    </div>
                    
                    <div class="mt-4">
                        <h6>Debug Information:</h6>
                        <div class="bg-light p-3 rounded">
                            <p><strong>File:</strong> {{ $file }}</p>
                            <p><strong>Line:</strong> {{ $line }}</p>
                        </div>
                        
                        <h6 class="mt-3">Stack Trace:</h6>
                        <div class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto">
                            <pre>{{ $trace }}</pre>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Go Back
                        </a>
                        
                        <a href="{{ url('/') }}" class="btn btn-primary ml-2">
                            <i class="fas fa-home mr-1"></i> Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 