@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Edit Form: {{ $form->name }}</h2>
                </div>

                <div class="card-body">
                    <form id="edit-form" method="POST" action="{{ route('formbuilder::forms.update', $form->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label for="name">Form Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $form->name }}" required>
                            <input type="hidden" name="form_builder_json" id="form-builder-json">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Update Form</button>
                            <a href="{{ route('formbuilder::forms.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                    
                    <!-- FormBuilder Container (outside the form) -->
                    <div class="mt-4">
                        <h3>Form Builder</h3>
                        <div id="form-builder"></div>
                    </div>
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
        
        // Simple initialization with existing form data
        var formData = '{!! addslashes($form->form_builder_json) !!}';
        var options = {
            controlPosition: 'left',
            formData: formData
        };
        
        try {
            var formBuilder = $('#form-builder').formBuilder(options);
            
            $('#edit-form').on('submit', function(e) {
                e.preventDefault();
                var formData = formBuilder.actions.getData('json');
                $('#form-builder-json').val(formData);
                this.submit();
            });
        } catch(error) {
            console.error('Error initializing FormBuilder:', error);
        }
    });
</script>
@endpush 