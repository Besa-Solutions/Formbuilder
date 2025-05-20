@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Form Analytics') }}: {{ $form->name }}</h5>
                    <a href="{{ route('admin.forms.show', $form->id) }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Form') }}
                    </a>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-muted">{{ __('Total Views') }}</h6>
                                    <h3 class="mb-0">{{ $analytics->sum('views') ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-muted">{{ __('Started') }}</h6>
                                    <h3 class="mb-0">{{ $analytics->sum('starts') ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-muted">{{ __('Completions') }}</h6>
                                    <h3 class="mb-0">{{ $analytics->sum('completions') ?? 0 }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-muted">{{ __('Completion Rate') }}</h6>
                                    <h3 class="mb-0">
                                        @php
                                            $starts = $analytics->sum('starts') ?? 0;
                                            $completions = $analytics->sum('completions') ?? 0;
                                            $rate = $starts > 0 ? round(($completions / $starts) * 100, 1) : 0;
                                            echo $rate . '%';
                                        @endphp
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table with daily data -->
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">{{ __('Daily Analytics') }}</h6>
                        @if($analytics->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Views') }}</th>
                                            <th>{{ __('Starts') }}</th>
                                            <th>{{ __('Completions') }}</th>
                                            <th>{{ __('Completion Rate') }}</th>
                                            <th>{{ __('Avg. Time') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($analytics->sortByDesc('date') as $analytic)
                                            <tr>
                                                <td>{{ $analytic->date->format('M d, Y') }}</td>
                                                <td>{{ $analytic->views }}</td>
                                                <td>{{ $analytic->starts }}</td>
                                                <td>{{ $analytic->completions }}</td>
                                                <td>
                                                    @php
                                                        $rate = $analytic->starts > 0 ? 
                                                            round(($analytic->completions / $analytic->starts) * 100, 1) : 0;
                                                        echo $rate . '%';
                                                    @endphp
                                                </td>
                                                <td>
                                                    @if($analytic->average_completion_time)
                                                        {{ gmdate("i:s", $analytic->average_completion_time) }}
                                                    @else
                                                        --
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                {{ __('No analytics data available for this form yet.') }}
                            </div>
                        @endif
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        {{ __('Detailed analytics charts will be available in a future update.') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 