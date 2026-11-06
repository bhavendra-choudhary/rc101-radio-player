@extends('admin.layout')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2></h2>
        <a href="{{ route('admin.uploaded-news.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add News Article
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($news->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th>Published</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($news as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>
                                        @if($item->image_path)
                                            <img src="{{ asset('storage/' . $item->image_path) }}" 
                                                 alt="{{ $item->title }}" 
                                                 class="img-thumbnail" 
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                        @else
                                            <span class="text-muted">No Image</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $item->title }}</strong>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($item->content, 100) }}</small>
                                    </td>
                                    <td>{{ $item->source_name }}</td>
                                    <td>
                                        @if($item->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->display_order }}</td>
                                    <td>{{ $item->published_at->format('M d, Y') }}</td>
                                    <td>
                                        <div class="btn-group gap-2" role="group">
                                            <a href="{{ route('admin.uploaded-news.edit', $item) }}" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-sm btn-outline-primary toggle-status-btn"
                                                    data-id="{{ $item->id }}"
                                                    data-current-status="{{ $item->is_active }}"
                                                    data-url="{{ route('admin.uploaded-news.toggle-status', $item->id) }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                            <form action="{{ route('admin.uploaded-news.destroy', $item) }}" 
                                                  method="POST" 
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-sm btn-sm btn-outline-primary"
                                                        onclick="return confirm('Are you sure you want to delete this news article?')">
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
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No uploaded news articles found</h5>
                    <p class="text-muted">Start by adding your first news article.</p>
                    <a href="{{ route('admin.uploaded-news.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add News Article
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle toggle status
        document.querySelectorAll('.toggle-status-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const currentStatus = this.dataset.currentStatus === '1';

                fetch(`/admin/uploaded-news/${id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to show updated status
                        location.reload();
                    } else {
                        alert('Error updating status: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating status. Please try again.');
                });
            });
        });
    });
    </script>
@endpush
