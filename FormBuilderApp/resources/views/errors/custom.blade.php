<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Form Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">An error occurred</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <strong>Error Message:</strong> {{ $message ?? 'Unknown error' }}
                        </div>
                        
                        @if(isset($file) && isset($line))
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                Error Location
                            </div>
                            <div class="card-body">
                                <p><strong>File:</strong> {{ $file }}</p>
                                <p><strong>Line:</strong> {{ $line }}</p>
                            </div>
                        </div>
                        @endif
                        
                        @if(isset($trace))
                        <div class="card">
                            <div class="card-header bg-light">
                                Stack Trace
                            </div>
                            <div class="card-body">
                                <pre class="bg-light p-3 border rounded"><code>{{ $trace }}</code></pre>
                            </div>
                        </div>
                        @endif
                        
                        <div class="mt-4 text-center">
                            <a href="{{ url()->previous() }}" class="btn btn-primary">Go Back</a>
                            <a href="{{ url('/') }}" class="btn btn-secondary ms-2">Go to Homepage</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 