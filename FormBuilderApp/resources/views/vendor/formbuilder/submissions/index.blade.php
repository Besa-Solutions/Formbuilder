@extends('formbuilder::layout')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card rounded-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">
                        {{ $pageTitle }} ({{ $submissions->count() }})
                    </h5>
                    <div>
                        <a href="{{ route('vendor.forms.submissions.export', $form->id) }}" class="btn btn-success">
                            <i class="fas fa-file-excel me-1"></i> {{ __('Export to Excel') }}
                        </a>
                        <a href="{{ route('admin.forms.export.excel', $form->id) }}" class="btn btn-primary">
                            <i class="fas fa-file-excel me-1"></i> {{ __('Alternative Export') }}
                        </a>
                        <a href="{{ route('formbuilder::forms.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Forms') }}
                        </a>
                    </div>
                </div>

                @if($submissions->count())
                    <div class="table-responsive">
                        <table class="table table-bordered d-table table-striped pb-0 mb-0">
                            <thead>
                                <tr>
                                    <th class="five">#</th>
                                    <th class="fifteen">User Name</th>
                                    @foreach($form_headers as $header)
                                        <th>{{ $header['label'] ?? title_case($header['name']) }}</th>
                                    @endforeach
                                    <th class="fifteen">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($submissions as $submission)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $submission->user->name ?? 'Anonymous' }}</td>
                                        @foreach($form_headers as $header)
                                            <td>
                                                {{ 
                                                    $submission->renderEntryContent(
                                                        $header['name'], $header['type'], true
                                                    ) 
                                                }}
                                            </td>
                                        @endforeach
                                        <td>
                                            <a href="{{ route('formbuilder::forms.submissions.show', [$form, $submission->id]) }}" class="btn btn-primary btn-sm" title="View submission">
                                                <i class="fa fa-eye"></i> View
                                            </a> 
                                            
                                            <a href="{{ route('download.file', $submission->id) }}" class="btn btn-success btn-sm" title="Download files">
                                                <i class="fa fa-download"></i> Files
                                            </a>

                                            <form action="{{ route('formbuilder::forms.submissions.destroy', [$form, $submission]) }}" method="POST" id="deleteSubmissionForm_{{ $submission->id }}" class="d-inline-block">
                                                @csrf 
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-danger btn-sm confirm-form" data-form="deleteSubmissionForm_{{ $submission->id }}" data-message="Delete this submission?" title="Delete submission">
                                                    <i class="fa fa-trash-o"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if($submissions->hasPages())
                        <div class="card-footer mb-0 pb-0">
                            <div>{{ $submissions->links() }}</div>
                        </div>
                    @endif
                @else
                    <div class="card-body">
                        <h4 class="text-danger text-center">
                            No submission to display.
                        </h4>
                    </div>  
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
