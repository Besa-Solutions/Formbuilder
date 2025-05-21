@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Submission Details</h2>
                </div>

                <div class="card-body">
                    <div class="mb-4">
                        <h4>Form: {{ $form->name }}</h4>
                        <p><strong>Submitted:</strong> {{ $submission->created_at->format('F j, Y g:i A') }}</p>
                    </div>

                    <div class="mb-4">
                        <h4>Submission Data</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        // Safely decode the submission content
                                        $content = is_array($submission->content) 
                                            ? $submission->content 
                                            : json_decode($submission->content, true);
                                            
                                        // Ensure content is an array
                                        if (!is_array($content)) $content = [];
                                        
                                        // Debug files_meta
                                        $filesMeta = is_array($submission->files_meta) 
                                            ? $submission->files_meta 
                                            : json_decode($submission->files_meta, true);
                                    @endphp
                                    
                                    @foreach($content as $key => $value)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td>
                                                @if($filesMeta && isset($filesMeta[$key]))
                                                    <a href="{{ asset('storage/' . $filesMeta[$key]['path']) }}" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="fas fa-download me-1"></i> 
                                                        {{ $filesMeta[$key]['original_name'] }}
                                                        ({{ round($filesMeta[$key]['size'] / 1024) }} KB)
                                                    </a>
                                                @else
                                                    {{ is_array($value) ? implode(', ', $value) : $value }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Debug Information Section -->
                    <div class="mb-4">
                        <h4>Debug Information</h4>
                        <div class="alert alert-info">
                            <p><strong>Has Files Method Result:</strong> {{ $submission->hasFiles() ? 'Yes' : 'No' }}</p>
                            <p><strong>Files Meta Raw:</strong> <code>{{ json_encode($submission->files_meta) }}</code></p>
                        </div>
                    </div>

                    @if(!empty($filesMeta))
                    <div class="mb-4">
                        <h4>Attached Files</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>File Name</th>
                                        <th>Size</th>
                                        <th>Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($filesMeta as $fieldName => $fileMeta)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $fieldName)) }}</td>
                                            <td>{{ $fileMeta['original_name'] ?? 'Unknown' }}</td>
                                            <td>{{ isset($fileMeta['size']) ? round($fileMeta['size'] / 1024) . ' KB' : 'Unknown' }}</td>
                                            <td>{{ $fileMeta['mime_type'] ?? 'Unknown' }}</td>
                                            <td>
                                                <a href="{{ asset('storage/' . ($fileMeta['path'] ?? '')) }}" class="btn btn-sm btn-primary" target="_blank">
                                                    <i class="fas fa-download me-1"></i> Download
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('formbuilder::forms.submissions.index', $form->id) }}" class="btn btn-secondary">
                            Back to Submissions
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 