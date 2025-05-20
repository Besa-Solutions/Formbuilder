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
