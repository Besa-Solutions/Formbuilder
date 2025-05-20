@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h2>Submissions for {{ $form->name }}</h2>
                </div>

                <div class="card-body">
                    @if($submissions->count() > 0)
                        <div class="mb-3">
                            <div class="input-group">
                                <input type="text" id="search-input" class="form-control" placeholder="Search submissions...">
                                <button class="btn btn-primary" type="button" id="search-button">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>User Name</th>
                                        <th>Submitted At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($submissions as $submission)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $submission->user->name ?? 'Anonymous' }}</td>
                                            <td>{{ $submission->created_at->format('F j, Y g:i A') }}</td>
                                            <td>
                                                <a href="{{ route('formbuilder::forms.submissions.show', [$form->id, $submission->id]) }}" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fa fa-eye"></i> View
                                                </a>
                                                
                                                <form action="{{ route('admin.forms.submissions.destroy', [$form, $submission]) }}" 
                                                      method="POST" 
                                                      class="d-inline-block" 
                                                      onsubmit="return confirm('Are you sure you want to delete this submission?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $submissions->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            No submissions yet for this form.
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('admin.forms.show', $form->id) }}" class="btn btn-secondary">
                            Back to Form
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Client-side search functionality
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const tableRows = document.querySelectorAll('tbody tr');
    
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    searchButton.addEventListener('click', performSearch);
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });
});
</script>
@endsection 