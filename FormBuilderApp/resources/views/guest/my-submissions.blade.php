@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('My Form Submissions') }}</h5>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if(isset($submissions) && count($submissions) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Form') }}</th>
                                        <th>{{ __('Submitted On') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($submissions as $submission)
                                        <tr>
                                            <td>{{ $submission->form->name ?? 'Unknown Form' }}</td>
                                            <td>{{ $submission->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <a href="{{ route('guest.my-submissions.show', $submission->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> {{ __('View') }}
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-paper-plane fa-4x text-muted mb-3"></i>
                            <h4>{{ __('No Submissions Yet') }}</h4>
                            <p class="text-muted">{{ __('You have not submitted any forms yet.') }}</p>
                            <a href="{{ route('guest.forms.index') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-file-alt me-2"></i> {{ __('View Available Forms') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 