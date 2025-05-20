@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Edit Form') }}: {{ $form->name }}</h5>
                    <div>
                        <a href="{{ route('admin.forms.show', $form->id) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Form Details') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <ul class="nav nav-tabs" id="formTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="settings-tab" data-bs-toggle="tab" data-bs-target="#settings" type="button" role="tab" aria-controls="settings" aria-selected="true">
                                <i class="fas fa-cog me-2"></i> {{ __('Settings') }}
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="builder-tab" data-bs-toggle="tab" data-bs-target="#builder" type="button" role="tab" aria-controls="builder" aria-selected="false">
                                <i class="fas fa-edit me-2"></i> {{ __('Form Builder') }}
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content mt-4" id="formTabsContent">
                        <!-- Settings Tab -->
                        <div class="tab-pane fade show active" id="settings" role="tabpanel" aria-labelledby="settings-tab">
                            <form method="POST" action="{{ route('admin.forms.update', $form) }}">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('Form Name') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $form->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">{{ __('Description') }}</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $form->description) }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', $form->is_published) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_published">{{ __('Publish Form') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('If checked, the form will be available for submissions.') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label for="start_date" class="form-label">{{ __('Start Date') }}</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date', isset($form->start_date) ? $form->start_date->format('Y-m-d') : '') }}">
                                    @error('start_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">{{ __('Optional. The date when the form becomes available.') }}</div>
                                </div>

                                <div class="mb-3">
                                    <label for="end_date" class="form-label">{{ __('End Date') }}</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date', isset($form->end_date) ? $form->end_date->format('Y-m-d') : '') }}">
                                    @error('end_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">{{ __('Optional. The date when the form becomes unavailable.') }}</div>
                                </div>

                                <input type="hidden" name="form_builder_json" id="form-builder-json" value="{{ $form->form_builder_json }}">

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> {{ __('Save Changes') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Form Builder Tab -->
                        <div class="tab-pane fade" id="builder" role="tabpanel" aria-labelledby="builder-tab">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('Use the form builder below to create your form. When you\'re done, click the "Update Form" button to save your changes.') }}
                            </div>
                            
                            <div id="form-builder-container" class="mb-4"></div>
                            
                            <form id="update-form-builder" method="POST" action="{{ route('admin.forms.update', $form) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="name" value="{{ $form->name }}">
                                <input type="hidden" name="description" value="{{ $form->description }}">
                                <input type="hidden" name="is_published" id="builder-is-published" value="1" class="is-published-field">
                                <input type="hidden" name="form_builder_json" id="form-builder-output">
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-info" id="preview-form-btn">
                                        <i class="fas fa-eye me-1"></i> {{ __('Preview') }}
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="update-form-btn">
                                        <i class="fas fa-save me-1"></i> {{ __('Update Form') }}
                                    </button>
                                </div>
                            </form>

                            <!-- Modal for Preview -->
                            <div class="modal fade" id="formPreviewModal" tabindex="-1" aria-labelledby="formPreviewModalLabel" aria-hidden="true">
                              <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title" id="formPreviewModalLabel">Form Preview</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                  </div>
                                  <div class="modal-body">
                                    <div id="form-preview-render"></div>
                                  </div>
                                </div>
                              </div>
                            </div>
                        </div>
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
        // Sync the checkbox between tabs
        var $isPublishedCheckbox = $('#is_published');
        var $builderIsPublishedField = $('#builder-is-published');
        
        // Initial state check
        function updateBuilderField() {
            // If the checkbox is checked, keep the hidden field in the builder form 
            // If not checked, remove the hidden field from the builder form to ensure it's not sent
            if ($isPublishedCheckbox.is(':checked')) {
                if (!$builderIsPublishedField.length) {
                    $('#update-form-builder').prepend('<input type="hidden" name="is_published" id="builder-is-published" value="1" class="is-published-field">');
                    $builderIsPublishedField = $('#builder-is-published');
                }
            } else {
                $('.is-published-field').remove();
                $builderIsPublishedField = $('#builder-is-published');
            }
        }
        
        // Set initial state
        updateBuilderField();
        
        // Update when checkbox changes
        $isPublishedCheckbox.on('change', updateBuilderField);
        
        // Before the builder form submits, ensure the is_published field reflects the checkbox
        $('#update-form-btn').on('click', function() {
            updateBuilderField();
        });
        
        // Initialize the form builder
        var options = {
            controlPosition: 'left',
            disableFields: ['button'], // Disable button field as we'll add our own submit button
            disabledAttrs: ['access'], // Disable the access attribute
            formData: '{!! is_string($form->form_builder_json) ? $form->form_builder_json : json_encode($form->form_builder_json) !!}',
            editFieldAttrs: [
                {
                    label: 'Field Name',
                    name: 'name',
                    type: 'text',
                    className: 'form-control fld-name',
                    required: true,
                    description: 'This is the internal key for this field. It must be unique.'
                }
            ],
            onAddField: function(fieldId) {
                setTimeout(function() {
                    var $labelInput = $('.fld-label', document.getElementById(fieldId));
                    var $nameInput = $('.fld-name', document.getElementById(fieldId));
                    // Auto-generate field name from label, but allow manual editing
                    $labelInput.on('input', function() {
                        var labelValue = $(this).val();
                        var nameValue = labelValue
                            .replace(/\s+/g, '-')
                            .replace(/[^a-zA-Z0-9_\-+*/=]/g, '')
                            .toLowerCase();
                        if (!$nameInput.data('touched')) {
                            $nameInput.val(nameValue);
                        }
                    });
                    $nameInput.on('input', function() {
                        $nameInput.data('touched', true);
                    });
                    setupOptionValueListeners(fieldId);
                }, 100);
            },
            onAddOption: function(optionTemplate, optionIndex) {
                setTimeout(function() {
                    $('.option-label', document.getElementById('frm-' + optionIndex)).on('input', function() {
                        var $this = $(this);
                        var labelValue = $this.val();
                        var $valueInput = $this.closest('.field-options').find('.option-value').eq($this.closest('.option-label').index('.option-label'));
                        var valueInputValue = $valueInput.val();
                        if (!valueInputValue || valueInputValue.startsWith('option-')) {
                            $valueInput.val(labelValue.toLowerCase().replace(/\s+/g, '-'));
                        }
                    });
                }, 100);
            }
        };
        
        // Function to set up option value listeners for multi-option fields (select, radio, checkbox)
        function setupOptionValueListeners(fieldId) {
            var fieldType = document.getElementById(fieldId).getAttribute('type');
            
            // Only apply this to field types with options
            if (['select', 'checkbox-group', 'radio-group'].includes(fieldType)) {
                // Monitor when Add Option button is clicked
                $(document).on('click', '#' + fieldId + ' .add-opt', function() {
                    setTimeout(function() {
                        // Find all option label inputs in this field
                        $('#' + fieldId + ' .option-label').each(function(index) {
                            var $labelInput = $(this);
                            
                            // Remove existing listeners to avoid duplicates
                            $labelInput.off('input.optionValue');
                            
                            // Add new listener
                            $labelInput.on('input.optionValue', function() {
                                var labelValue = $(this).val();
                                var valueField = $('#' + fieldId + ' .option-value').eq(index);
                                
                                // Only update if the value field exists and follows our naming pattern or is empty
                                if (valueField.length && (valueField.val() === '' || valueField.val().startsWith('option-'))) {
                                    valueField.val(labelValue.toLowerCase().replace(/\s+/g, '-'));
                                }
                            });
                        });
                    }, 100);
                });
                
                // Initial setup for existing options
                setTimeout(function() {
                    $('#' + fieldId + ' .option-label').each(function(index) {
                        var $labelInput = $(this);
                        
                        $labelInput.off('input.optionValue').on('input.optionValue', function() {
                            var labelValue = $(this).val();
                            var valueField = $('#' + fieldId + ' .option-value').eq(index);
                            
                            if (valueField.length && (valueField.val() === '' || valueField.val().startsWith('option-'))) {
                                valueField.val(labelValue.toLowerCase().replace(/\s+/g, '-'));
                            }
                        });
                    });
                }, 200);
            }
        }
        
        try {
            var formBuilder = $('#form-builder-container').formBuilder(options);
            // Preview button logic
            $('#preview-form-btn').on('click', function() {
                var formData = formBuilder.actions.getData('json');
                $('#form-preview-render').empty();
                $('#form-preview-render').formRender({
                    dataType: 'json',
                    formData: formData
                });
                var modal = new bootstrap.Modal(document.getElementById('formPreviewModal'));
                modal.show();
            });
            // Handle update form submission
            $('#update-form-btn').on('click', function(e) {
                e.preventDefault();
                var formData = formBuilder.actions.getData('json');
                $('#form-builder-output').val(formData);
                // Submit the form
                $('#update-form-builder').submit();
            });
        } catch (error) {
            console.error('Error initializing FormBuilder:', error);
            $('#form-builder-container').html('<div class="alert alert-danger">Error loading form builder. Please check console for details.</div>');
        }
    });
</script>
@endpush 