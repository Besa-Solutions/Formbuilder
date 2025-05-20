@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('Form Management') }}</h5>
                    <a href="{{ route('admin.forms.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> {{ __('Create Form') }}
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

                    @if(isset($forms) && count($forms) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('ID') }}</th>
                                        <th>{{ __('Title') }}</th>
                                        <th>{{ __('Identifier') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Submissions') }}</th>
                                        <th>{{ __('Created At') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($forms as $form)
                                        <tr>
                                            <td>{{ $form->id }}</td>
                                            <td>{{ $form->title }}</td>
                                            <td>
                                                <code>{{ $form->identifier }}</code>
                                            </td>
                                            <td>
                                                <span class="badge {{ $form->is_published && $form->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                                                    {{ $form->is_published && $form->status === 'published' ? 'Published' : 'Inactive' }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('admin.forms.submissions', $form) }}" class="text-decoration-none">
                                                    {{ $form->submissions_count ?? 0 }} {{ __('Submissions') }}
                                                </a>
                                            </td>
                                            <td>{{ $form->created_at->format('Y-m-d H:i') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('public.form.render', $form->identifier) }}" class="btn btn-sm btn-info" target="_blank" title="Preview Form">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('admin.forms.show', $form) }}" class="btn btn-sm btn-success ms-1" title="View Details">
                                                        <i class="fas fa-info-circle"></i>
                                                    </a>
                                                    <a href="{{ route('admin.forms.edit', $form) }}" class="btn btn-sm btn-primary ms-1" title="Edit Form">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('admin.forms.destroy', $form) }}" method="POST" class="d-inline ms-1">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this form?')" title="Delete Form">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x mb-3 text-muted"></i>
                            <h4>{{ __('No Forms Created Yet') }}</h4>
                            <p class="text-muted">{{ __('Get started by creating your first form.') }}</p>
                            <a href="{{ route('admin.forms.create') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-plus-circle me-2"></i> {{ __('Create Your First Form') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 