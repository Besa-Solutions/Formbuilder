@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>{{ $form->name }} - Test Upload</h2>
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

                    <form id="test-upload-form" method="POST" action="{{ route('test.upload.process', $form->identifier) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="filedownloader" class="form-label">Upload Document</label>
                            <input type="file" class="form-control" id="filedownloader" name="filedownloader">
                            <div class="form-text">Upload your Word document here</div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Submit Document</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
