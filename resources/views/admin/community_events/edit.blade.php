@extends('admin.layout')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Edit Community Event</h2>
    <a href="{{ route('admin.community-events.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Events
    </a>
</div>

<div class="card">
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('admin.community-events.update', $community_event) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" 
                               class="form-control @error('title') is-invalid @enderror" 
                               id="title" 
                               name="title" 
                               value="{{ old('title', $community_event->title) }}" 
                               required 
                               maxlength="255">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" 
                                  name="description" 
                                  rows="6" 
                                  required>{{ old('description', $community_event->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date <span class="text-danger">*</span></label>
                                <input type="datetime-local" 
                                       class="form-control @error('event_date') is-invalid @enderror" 
                                       id="event_date" 
                                       name="event_date" 
                                       value="{{ old('event_date', $community_event->event_date->format('Y-m-d\TH:i')) }}" 
                                       required>
                                @error('event_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('location') is-invalid @enderror" 
                                       id="location" 
                                       name="location" 
                                       value="{{ old('location', $community_event->location) }}" 
                                       required 
                                       maxlength="255">
                                @error('location')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="source_url" class="form-label">Source URL (Optional)</label>
                        <input type="url" 
                               class="form-control @error('source_url') is-invalid @enderror" 
                               id="source_url" 
                               name="source_url" 
                               value="{{ old('source_url', $community_event->source_url) }}" 
                               placeholder="https://example.com/event-details"
                               maxlength="255">
                        <div class="form-text">Link to the original event page (e.g., Ticketmaster, Facebook).</div>
                        @error('source_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="image" class="form-label">Event Image</label>
                        
                        @if($community_event->image)
                            <div class="mb-2">
                                <img src="{{ asset('storage/' . $community_event->image) }}" 
                                     alt="{{ $community_event->title }}" 
                                     class="img-thumbnail" 
                                     style="max-width: 100%;">
                                <div class="form-text mt-1">Current image (will be replaced if you upload a new one)</div>
                            </div>
                        @endif
                        
                        <input type="file" 
                               class="form-control @error('image') is-invalid @enderror" 
                               id="image" 
                               name="image" 
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            <strong>Recommended Resolution:</strong> 800 &times; 500 px (or 1200 &times; 630 px, 16:9 / 8:5 aspect ratio).<br>
                            Allowed formats: JPEG, PNG, JPG, GIF, WEBP (Max: 10MB)
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" 
                                name="status" 
                                required>
                            <option value="active" {{ old('status', $community_event->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $community_event->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="cancelled" {{ old('status', $community_event->status) === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" 
                               class="form-control @error('display_order') is-invalid @enderror" 
                               id="display_order" 
                               name="display_order" 
                               value="{{ old('display_order', $community_event->display_order) }}" 
                               min="0">
                        @error('display_order')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Lower numbers appear first.</div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.community-events.index') }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Event
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
