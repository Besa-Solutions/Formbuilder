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
                                    @endphp
                                    
                                    @foreach($content as $key => $value)
                                        <tr>
                                            <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                            <td>{{ is_array($value) ? implode(', ', $value) : $value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

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