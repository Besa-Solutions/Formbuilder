@extends('formbuilder::layout')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card rounded-0">
                <div class="card-header">
                    <h5 class="card-title">
                        Viewing Submission #{{ $submission->id }} for form '{{ $submission->form->name }}'
                        
                        <div class="btn-toolbar float-right" role="toolbar">
                            <div class="btn-group" role="group" aria-label="First group">
                                <a href="{{ route('admin.forms.submissions', $submission->form->id) }}" class="btn btn-primary float-md-right btn-sm" title="Back To Submissions">
                                    <i class="fa fa-arrow-left"></i> Back To Submissions
                                </a>
                                <form action="{{ route('formbuilder::forms.submissions.destroy', [$submission->form, $submission]) }}" method="POST" id="deleteSubmissionForm_{{ $submission->id }}" class="d-inline-block">
                                    @csrf 
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-danger btn-sm rounded-0 confirm-form" data-form="deleteSubmissionForm_{{ $submission->id }}" data-message="Delete submission" title="Delete this submission?">
                                        <i class="fa fa-trash-o"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </h5>
                </div>

                <ul class="list-group list-group-flush">
                    @if(isset($form_headers) && is_array($form_headers) && count($form_headers) > 0)
                        @foreach($form_headers as $header)
                            <li class="list-group-item">
                                <strong>{{ $header['label'] ?? ucfirst($header['name']) }}: </strong> 
                                <span class="float-right">
                                    @php
                                        // Get content as array
                                        $content = is_array($submission->content) 
                                            ? $submission->content 
                                            : json_decode($submission->content, true);
                                        
                                        // Default to empty array if not an array
                                        if (!is_array($content)) $content = [];
                                        
                                        // Get value for this field
                                        $value = $content[$header['name']] ?? '';
                                        
                                        // Format value for display
                                        $displayValue = is_array($value) ? implode(', ', $value) : $value;
                                    @endphp
                                    {{ $displayValue }}
                                </span>
                            </li>
                        @endforeach
                    @elseif(is_array($submission->content) && count($submission->content) > 0)
                        {{-- If we don't have form headers but do have content, display the content directly --}}
                        @foreach($submission->content as $fieldName => $fieldValue)
                            <li class="list-group-item">
                                <strong>{{ ucfirst(str_replace('_', ' ', $fieldName)) }}: </strong> 
                                <span class="float-right">
                                    @php
                                        // Format value for display
                                        $displayValue = is_array($fieldValue) ? implode(', ', $fieldValue) : $fieldValue;
                                    @endphp
                                    {{ $displayValue }}
                                </span>
                            </li>
                        @endforeach
                    @else
                        <li class="list-group-item">
                            <div class="alert alert-info mb-0">No form data available.</div>
                        </li>
                    @endif
                </ul>
            </div>
            
            @php
                // Get file metadata with proper type handling
                if (is_string($submission->files_meta)) {
                    // Try to decode the JSON string
                    $filesMeta = json_decode($submission->files_meta, true);
                } elseif (is_array($submission->files_meta)) {
                    // Already an array
                    $filesMeta = $submission->files_meta;
                } else {
                    // Not a string or array, set to empty array
                    $filesMeta = [];
                }
            @endphp
            
            @if(!empty($filesMeta) && is_array($filesMeta))
            <div class="card rounded-0 mt-4">
                <div class="card-header">
                    <h5 class="card-title">Attached Files</h5>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach($filesMeta as $fieldName => $fileMeta)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ ucfirst(str_replace('_', ' ', $fieldName)) }}: </strong>
                                    <span>{{ $fileMeta['original_name'] ?? 'File' }}</span>
                                    <small class="text-muted ml-2">({{ isset($fileMeta['size']) ? round($fileMeta['size'] / 1024) . ' KB' : 'Unknown size' }})</small>
                                </div>
                                <div>
                                    <!-- Standard download method -->
                                    <a href="{{ asset('storage/' . ($fileMeta['path'] ?? '')) }}" class="btn btn-primary btn-sm" download>
                                        <i class="fa fa-download"></i> Download
                                    </a>
                                    
                                    <!-- Alternative download method -->
                                    <a href="{{ route('download.file', [$submission->id, $fieldName]) }}" class="btn btn-success btn-sm">
                                        <i class="fa fa-file"></i> Secure Download
                                    </a>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            @php
                // Get content for downloads
                if (is_array($submission->content)) {
                    $rawContent = $submission->content;
                } else {
                    try {
                        $rawContent = json_decode($submission->content, true);
                    } catch (\Exception $e) {
                        $rawContent = [];
                    }
                }
                
                // Make sure we have an array
                if (!is_array($rawContent)) {
                    $rawContent = [];
                }
                
                // Check specifically for a file in filedownloader field
                $hasUploadedFile = false;
                if (isset($rawContent['filedownloader']) && is_string($rawContent['filedownloader'])) {
                    $hasUploadedFile = true;
                } elseif (is_array($filesMeta) && !empty($filesMeta) && isset($filesMeta['filedownloader'])) {
                    $hasUploadedFile = true;
                }
            @endphp
            
            @if($hasUploadedFile)
            <div class="card rounded-0 mt-4">
                <div class="card-header">
                    <h5 class="card-title">Download Files</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        @if(isset($rawContent['filedownloader']) && is_string($rawContent['filedownloader']))
                            <a href="{{ asset('storage/' . $rawContent['filedownloader']) }}" class="btn btn-primary" download>
                                <i class="fa fa-download"></i> Standard Download
                            </a>
                        @endif
                            
                        <a href="{{ route('download.file', $submission->id) }}" class="btn btn-success">
                            <i class="fa fa-file"></i> Secure Download
                        </a>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card rounded-0">
                <div class="card-header">
                    <h5 class="card-title">Details</h5>
                </div>

                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>Form: </strong> 
                        <span class="float-right">{{ $submission->form->name }}</span>
                    </li>
                    <li class="list-group-item">
                        <strong>Submitted By: </strong> 
                        <span class="float-right">{{ $submission->user->name ?? 'Guest' }}</span>
                    </li>
                    <li class="list-group-item">
                        <strong>Last Updated On: </strong> 
                        <span class="float-right">{{ $submission->updated_at->toDayDateTimeString() }}</span>
                    </li>
                    <li class="list-group-item">
                        <strong>Submitted On: </strong> 
                        <span class="float-right">{{ $submission->created_at->toDayDateTimeString() }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
