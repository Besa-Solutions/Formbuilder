@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h2>Forms</h2>
                    <a href="{{ route('formbuilder::forms.create') }}" class="btn btn-primary">Create New Form</a>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($forms->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Created</th>
                                        <th>Submissions</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($forms as $form)
                                        <tr>
                                            <td>{{ $form->name }}</td>
                                            <td>{{ $form->created_at->format('Y-m-d H:i:s') }}</td>
                                            <td>{{ $form->submissions_count }}</td>
                                            <td>
                                                <a href="{{ route('formbuilder::forms.show', $form->id) }}" class="btn btn-sm btn-info">View</a>
                                                <a href="{{ route('formbuilder::forms.edit', $form->id) }}" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="{{ route('formbuilder::forms.submissions', $form->id) }}" class="btn btn-sm btn-success">Submissions</a>
                                                <form action="{{ route('formbuilder::forms.destroy', $form->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this form?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            No forms found. <a href="{{ route('formbuilder::forms.create') }}">Create your first form</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 