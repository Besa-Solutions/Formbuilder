@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Form Details') }}: {{ $form->name ?? 'Unnamed Form' }}</h5>
                    <div>
                        <a href="{{ route('public.form.render', $form->identifier ?? '') }}" class="btn btn-info btn-sm me-2" target="_blank">
                            <i class="fas fa-eye me-1"></i> {{ __('Preview') }}
                        </a>
                        <a href="{{ route('admin.forms.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> {{ __('Back to Forms') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold">{{ __('Form Details') }}</h6>
                                <table class="table table-striped">
                                    <tr>
                                        <th>{{ __('Name') }}</th>
                                        <td>{{ $form->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Description') }}</th>
                                        <td>{{ $form->description ?? 'No description provided' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Identifier') }}</th>
                                        <td><code>{{ $form->identifier ?? 'N/A' }}</code></td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Status') }}</th>
                                        <td>
                                            <span class="badge {{ ($form->is_published ?? false) ? 'bg-success' : 'bg-secondary' }}">
                                                {{ ($form->is_published ?? false) ? 'Published' : 'Draft' }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Created On') }}</th>
                                        <td>{{ isset($form->created_at) ? $form->created_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Last Updated') }}</th>
                                        <td>{{ isset($form->updated_at) ? $form->updated_at->format('Y-m-d H:i') : 'N/A' }}</td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">{{ __('Form Settings') }}</h6>
                                <table class="table table-striped">
                                    <tr>
                                        <th>{{ __('Availability') }}</th>
                                        <td>
                                            @if(isset($form->start_date) || isset($form->end_date))
                                                @if(isset($form->start_date) && isset($form->end_date))
                                                    {{ $form->start_date->format('Y-m-d') }} to {{ $form->end_date->format('Y-m-d') }}
                                                @elseif(isset($form->start_date))
                                                    From {{ $form->start_date->format('Y-m-d') }} onwards
                                                @else
                                                    Until {{ $form->end_date->format('Y-m-d') }}
                                                @endif
                                            @else
                                                Always available
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('Submissions') }}</th>
                                        <td>
                                            <a href="{{ route('admin.forms.submissions', $form->id ?? 0) }}" class="text-decoration-none">
                                                {{ $form->submissions_count ?? 0 }} {{ __('Submissions') }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mt-4">{{ __('Actions') }}</h6>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.forms.edit', $form->id ?? 0) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i> {{ __('Edit Form') }}
                        </a>
                        <a href="{{ route('admin.forms.submissions', $form->id ?? 0) }}" class="btn btn-info">
                            <i class="fas fa-list-alt me-2"></i> {{ __('View Submissions') }}
                        </a>
                        <a href="{{ route('admin.forms.analytics', $form->id ?? 0) }}" class="btn btn-success">
                            <i class="fas fa-chart-line me-2"></i> {{ __('Analytics') }}
                        </a>
                        <a href="{{ route('admin.forms.export.excel', $form->id) }}" class="btn btn-warning">
                            <i class="fas fa-file-excel me-2"></i> Export to Excel
                        </a>
                        <form action="{{ route('admin.forms.destroy', $form->id ?? 0) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this form?')">
                                <i class="fas fa-trash me-2"></i> {{ __('Delete Form') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 