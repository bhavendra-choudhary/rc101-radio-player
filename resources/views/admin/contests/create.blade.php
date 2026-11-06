@extends('admin.layout')


@section('title', 'Create Contest - ' . (App\Models\Footer::getFooter()->brand_name ?? config('app.name')))

@section('content')
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="display-4">Create New Contest</h1>
                    <a href="{{ route('admin.contests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Contests
                    </a>
                </div>

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Validation Error:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h3 class="mb-0">Contest Details</h3>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.contests.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Contest Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-select" id="is_active" name="is_active">
                                        <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                                        <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="url" class="form-label">Url</label>
                                    <input type="url" class="form-control" id="url" name="url" value="{{ old('url', $contest->url ?? '') }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5">{{ old('description') }}</textarea>
                            </div>

                            <div class="mb-4">
                                <label for="images" class="form-label fw-bold">Contest Images (Optional)</label>
                                <input type="file" 
                                       class="form-control @error('images.*') is-invalid @enderror" 
                                       id="images" 
                                       name="images[]" 
                                       multiple 
                                       accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                                @error('images.*')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    <strong>Recommended Resolution:</strong> 1200 &times; 800 px (or 800 &times; 600 px, 4:3 or 16:9 aspect ratio).<br>
                                    You can select one or more images. Supported formats: JPG, PNG, GIF, WEBP (Max: 10MB each).
                                </div>
                                <div id="image-preview-container" class="d-flex flex-wrap gap-2 mt-3"></div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-outline-secondary me-md-2" onclick="document.getElementById('image-preview-container').innerHTML = '';">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Create Contest
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('images')?.addEventListener('change', function(e) {
            const container = document.getElementById('image-preview-container');
            container.innerHTML = '';
            if (!this.files) return;
            Array.from(this.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const reader = new FileReader();
                reader.onload = function(evt) {
                    const img = document.createElement('img');
                    img.src = evt.target.result;
                    img.className = 'img-thumbnail';
                    img.style.width = '100px';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    container.appendChild(img);
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
@endsection