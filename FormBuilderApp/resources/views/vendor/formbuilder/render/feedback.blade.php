@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        Form Successfully submitted

                        @auth
                            <a href="{{ route('guest.my-submissions') }}" class="btn btn-primary btn-sm float-md-right">
                                <i class="fas fa-list-alt me-1"></i> {{ __('Go To My Submissions') }}
                            </a>
                        @endauth
                    </h5>
                </div>

                <div class="card-body">
                    <h3 class="text-center text-success">
                        Your entry for <strong>{{ $form->name }}</strong> was successfully submitted.
                    </h3>
                </div>

                <div class="card-footer">
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="fas fa-home me-1"></i> {{ __('Return Home') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
