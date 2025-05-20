@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Create New Form') }}</h5>
                    <a href="{{ route('admin.forms.index') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Forms') }}
                    </a>
                </div>

                <div class="card-body">
                    <form id="create-form" method="POST" action="{{ route('admin.forms.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Form Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('Enter a descriptive name for your form.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">{{ __('Description') }}</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('Provide a brief description of the purpose of this form.') }}</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', '1') == '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_published">{{ __('Publish Form') }}</label>
                            </div>
                            <div class="form-text">{{ __('If checked, the form will be available for submissions.') }}</div>
                        </div>
                        
                        <!-- Hidden field to store form builder JSON -->
                        <input type="hidden" name="form_builder_json" id="form-builder-json">

                        <hr>

                        <h5 class="mb-3">{{ __('Form Builder') }}</h5>
                        
                        <!-- FormBuilder Container -->
                        <div class="mt-4 mb-4">
                            <div id="form-builder"></div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary" id="save-form">
                                <i class="fas fa-save me-1"></i> {{ __('Save Form') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="text/javascript">
    $(document).ready(function() {
        // Debug: Check if jQuery and FormBuilder are loaded
        console.log('jQuery version:', $.fn.jquery);
        console.log('FormBuilder available:', typeof $.fn.formBuilder);
        
        // Initialize FormBuilder with options
        var options = {
            controlPosition: 'left',
            disableFields: ['button'], // Disable button field as we'll add our own submit button
            disabledAttrs: ['access'] // Disable the access attribute
        };
        
        try {
            // Initialize the form builder
            var formBuilder = $('#form-builder').formBuilder(options);
            
            // Handle form submission
            $('#create-form').on('submit', function(e) {
                e.preventDefault();
                console.log('Form submission triggered');
                
                try {
                    // Get the form builder data
                    var formData = formBuilder.actions.getData('json');
                    console.log('Form data:', formData);
                    
                    // Set the form builder JSON data to the hidden input
                    $('#form-builder-json').val(formData);
                    
                    // Submit the form
                    console.log('Submitting form...');
                    this.submit();
                } catch (error) {
                    console.error('Error processing form data:', error);
                    alert('There was an error processing your form. Please check the console for details.');
                }
            });
            
            // Alternative button click handler
            $('#save-form').on('click', function(e) {
                console.log('Save button clicked');
                var formData = formBuilder.actions.getData('json');
                $('#form-builder-json').val(formData);
                $('#create-form').submit();
            });
        } catch(error) {
            console.error('Error initializing FormBuilder:', error);
            $('#form-builder').html('<div class="alert alert-danger">Error loading form builder. Please check console for details.</div>');
        }
    });
</script>
@endpush 