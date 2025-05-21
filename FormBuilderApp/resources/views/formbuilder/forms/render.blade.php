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
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="render-form" method="POST" action="{{ route('public.form.submit', $form->identifier) }}" enctype="multipart/form-data">
                        @csrf
                        <div id="form-render"></div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize the form renderer
        var formRenderer = $('#form-render').formRender({
            dataType: 'json',
            formData: {!! $form->form_builder_json !!}
        });
        
        // Fix form submission by intercepting it
        $('#render-form').on('submit', function(e) {
            // Check for file inputs
            var hasFiles = $('input[type="file"]').length > 0;
            var fileSelected = false;
            
            // Check if any files are selected
            $('input[type="file"]').each(function() {
                if (this.files && this.files.length > 0) {
                    fileSelected = true;
                    return false; // Exit the loop
                }
            });
            
            // Get all form fields
            var formFields = $('#render-form').serializeArray();
            
            // If this is a file upload form and we have files selected, allow submission
            if (hasFiles && fileSelected) {
                return true;
            }
            
            // For non-file forms, make sure we have data
            if (formFields.length <= 1 && !fileSelected) { // Just _token would be length 1
                e.preventDefault();
                alert('Please fill out the form before submitting.');
                return false;
            }
        });
    });
</script>
@endpush 