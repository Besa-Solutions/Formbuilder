@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>{{ $form->name }}</h2>
                </div>

                <div class="card-body">
                    <div class="mb-4">
                        <h4>Form Details</h4>
                        <p><strong>Created:</strong> {{ $form->created_at->format('F j, Y') }}</p>
                        <p><strong>Submissions:</strong> {{ $form->submissions_count }}</p>
                    </div>

                    <div class="mb-4">
                        <h4>Form Preview</h4>
                        <div id="form-preview"></div>
                    </div>

                    <div class="mb-4">
                        <h4>Form Link</h4>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ route('formbuilder::form.render', $form->identifier) }}" readonly>
                            <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard(this)">Copy</button>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('formbuilder::forms.edit', $form->id) }}" class="btn btn-primary">Edit Form</a>
                        <a href="{{ route('formbuilder::forms.submissions.index', $form->id) }}" class="btn btn-info">View Submissions</a>
                        <a href="{{ route('formbuilder::forms.index') }}" class="btn btn-secondary">Back to Forms</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#form-preview').formRender({
            dataType: 'json',
            formData: {!! $form->form_builder_json !!},
            readOnly: true
        });
    });

    function copyToClipboard(button) {
        var input = button.previousElementSibling;
        input.select();
        document.execCommand('copy');
        button.textContent = 'Copied!';
        setTimeout(function() {
            button.textContent = 'Copy';
        }, 2000);
    }
</script>
@endpush 