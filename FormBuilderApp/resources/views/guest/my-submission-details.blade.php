@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Submission Details') }}</h5>
                    <a href="{{ route('guest.my-submissions') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('Back to My Submissions') }}
                    </a>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-3">{{ __('Form Responses') }}</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <tbody>
                                        @if(isset($displayData) && count($displayData) > 0)
                                            @foreach($displayData as $item)
                                                <tr>
                                                    <th>{{ $item['label'] }}</th>
                                                    <td>{{ $item['value'] }}</td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="2" class="text-center">{{ __('No form fields found') }}</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <h6 class="fw-bold mb-3">{{ __('Submission Information') }}</h6>
                            <div class="card">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('Form') }}</span>
                                        <span class="fw-bold">{{ $form->name }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('Submitted On') }}</span>
                                        <span>{{ $submission->created_at->format('M d, Y h:i A') }}</span>
                                    </li>
                                    @if($submission->created_at != $submission->updated_at)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>{{ __('Last Updated') }}</span>
                                        <span>{{ $submission->updated_at->format('M d, Y h:i A') }}</span>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 