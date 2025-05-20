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
                                <i class="fas fa-cog me-2"></i> {{ __('Basic Info') }}
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
                            <form method="POST" action="{{ route('admin.forms.store') }}" id="create-form">
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
                                    <label for="identifier" class="form-label">{{ __('Form Identifier') }} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">{{ url('/forms/') }}/</span>
                                        <input type="text" class="form-control @error('identifier') is-invalid @enderror" id="identifier" name="identifier" value="{{ old('identifier') }}" required>
                                    </div>
                                    @error('identifier')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">{{ __('This will be used in the URL to access your form. Use only lowercase letters, numbers, and hyphens.') }}</div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_published" name="is_published" value="1" {{ old('is_published', '1') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_published">{{ __('Publish Form') }}</label>
                                    </div>
                                    <div class="form-text">{{ __('If checked, the form will be available for submissions.') }}</div>
                                </div>

                                <!-- Hidden field to store form builder JSON -->
                                <input type="hidden" name="form_builder_json" id="form-builder-json" value="[]">

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-primary" id="go-to-builder-btn">
                                        <i class="fas fa-arrow-right me-1"></i> {{ __('Next: Design Form') }}
                                    </button>
                                    <button type="submit" class="btn btn-secondary" id="save-basic-info">
                                        <i class="fas fa-save me-1"></i> {{ __('Save Basic Info Only') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Form Builder Tab -->
                        <div class="tab-pane fade" id="builder" role="tabpanel" aria-labelledby="builder-tab">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                {{ __('Design your form by dragging elements from the left panel. When you\'re done, click the "Create Form" button to save.') }}
                            </div>
                            
                            <div id="form-builder-container" class="mb-4"></div>
                            
                            <form id="create-form-with-builder" method="POST" action="{{ route('admin.forms.store') }}">
                                @csrf
                                <input type="hidden" name="name" id="builder-form-name">
                                <input type="hidden" name="description" id="builder-form-description">
                                <input type="hidden" name="identifier" id="builder-form-identifier">
                                <input type="hidden" name="is_published" id="builder-form-published">
                                <input type="hidden" name="form_builder_json" id="form-builder-output">
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="button" class="btn btn-secondary" id="back-to-settings-btn">
                                        <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Basic Info') }}
                                    </button>
                                    <button type="button" class="btn btn-info" id="preview-form-btn">
                                        <i class="fas fa-eye me-1"></i> {{ __('Preview') }}
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="create-complete-form-btn">
                                        <i class="fas fa-save me-1"></i> {{ __('Create Form') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Auto-generate identifier from name
        $('#name').on('input', function() {
            let name = $(this).val();
            let identifier = name.toLowerCase()
                .replace(/[^\w\s-]/g, '')
                .replace(/\s+/g, '-');
            
            $('#identifier').val(identifier);
        });
        
        // Navigate between tabs
        $('#go-to-builder-btn').on('click', function() {
            // Copy values from basic info to hidden fields in the builder form
            $('#builder-form-name').val($('#name').val());
            $('#builder-form-description').val($('#description').val());
            $('#builder-form-identifier').val($('#identifier').val());
            $('#builder-form-published').val($('#is_published').is(':checked') ? '1' : '0');
            
            // Switch to the builder tab
            $('#builder-tab').tab('show');
        });
        
        $('#back-to-settings-btn').on('click', function() {
            $('#settings-tab').tab('show');
        });
        
        // Initialize the form builder
        var options = {
            controlPosition: 'left',
            disableFields: ['button'], // Disable button field as we'll add our own submit button
            disabledAttrs: ['access'], // Disable the access attribute
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
            // Handle form submission with builder data
            $('#create-complete-form-btn').on('click', function(e) {
                e.preventDefault();
                var formData = formBuilder.actions.getData('json');
                $('#form-builder-output').val(formData);
                // Submit the form
                $('#create-form-with-builder').submit();
            });
        } catch (error) {
            console.error('Error initializing FormBuilder:', error);
            $('#form-builder-container').html('<div class="alert alert-danger">Error loading form builder. Please check console for details.</div>');
        }
    });
</script>
@endpush 