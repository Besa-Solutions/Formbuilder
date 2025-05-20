@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Available Forms') }}</h5>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (count($forms) > 0)
                        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                            @foreach ($forms as $form)
                                <div class="col">
                                    <div class="card h-100 border shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $form->title }}</h5>
                                            @if ($form->description)
                                                <p class="card-text text-muted small">{{ Str::limit($form->description, 100) }}</p>
                                            @endif
                                        </div>
                                        <div class="card-footer bg-transparent d-grid">
                                            <a href="{{ route('public.form.render', $form->identifier) }}" class="btn btn-primary">
                                                <i class="fas fa-file-alt me-2"></i> {{ __('Fill Out Form') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                            <h4>{{ __('No Forms Available') }}</h4>
                            <p class="text-muted">{{ __('There are currently no forms available.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 