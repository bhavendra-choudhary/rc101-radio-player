@extends('admin.layout')


@section('title', 'Upload Contest Images - ' . (App\Models\Footer::getFooter()->brand_name ?? config('app.name')))

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="display-4">Upload Contest Images</h1>
                <a href="{{ route('admin.contests.show', $contest) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Contest
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

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

            <div class="row">
                <div class="col-md-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h3 class="mb-0">Contest Info</h3>
                        </div>
                        <div class="card-body">
                            <h4>{{ $contest->title }}</h4>
                            <p>{{ $contest->description ?? 'No description provided.' }}</p>
                            <div class="mb-3">
                                <h5>Status</h5>
                                @if($contest->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                            <div class="mb-3">
                                <h5>Duration</h5>
                                <p>{{ $contest->start_date->format('M d, Y') }} - {{ $contest->end_date->format('M d, Y') }}</p>
                            </div>
                            <div class="mb-3">
                                <h5>Current Images</h5>
                                <p>{{ $contest->images->count() }} images uploaded</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h3 class="mb-0">Upload Images</h3>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.contests.upload.store', $contest) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label for="images" class="form-label">Select Images</label>
                                    <div class="mb-2">
                                        <div class="btn-group w-100" role="group">
                                            <input type="radio" class="btn-check" name="storage_source" id="local_storage" value="local" checked autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="local_storage">
                                                <i class="fas fa-folder-open"></i> Local Files
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="storage_source" id="google_drive" value="google_drive" autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="google_drive">
                                                <i class="fab fa-google-drive"></i> Google Drive
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="storage_source" id="onedrive" value="onedrive" autocomplete="off">
                                            <label class="btn btn-outline-secondary" for="onedrive">
                                                <i class="fas fa-cloud"></i> OneDrive
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div id="local_storage_input" class="storage-input">
                                        <input class="form-control" type="file" id="images" name="images[]" multiple accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" required>
                                    </div>
                                    
                                    <div id="cloud_storage_input" class="storage-input d-none">
                        <div class="input-group mb-3">
                            <button class="btn btn-outline-secondary" type="button" id="browse_cloud_btn">
                                <i class="fas fa-search"></i> Browse
                            </button>
                            <input type="text" class="form-control" id="cloud_file_path" name="cloud_file_path" placeholder="No files selected" readonly>
                            <input type="hidden" id="cloud_file_data" name="cloud_file_data">
                        </div>
                        <div id="selected_files_preview" class="mt-3 d-none">
                            <h6>Selected Files:</h6>
                            <div id="file_list" class="list-group"></div>
                        </div>
                        <small class="form-text text-muted">Select images from your cloud storage.</small>
                    </div>
                                    
                                    <div class="form-text"><strong>Recommended Resolution:</strong> 1200 &times; 800 px (or 800 &times; 600 px, 4:3 or 16:9 aspect ratio).<br>You can select multiple images. Maximum size: 10MB per image. Supported formats: JPG, PNG, GIF, WEBP.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="title" class="form-label">Image Title (optional)</label>
                                    <input type="text" class="form-control" id="title" name="title" value="{{ old('title') }}">
                                    <div class="form-text">This title will be applied to all uploaded images.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Image Description (optional)</label>
                                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                                    <div class="form-text">This description will be applied to all uploaded images.</div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="reset" class="btn btn-outline-secondary me-md-2">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload"></i> Upload Images
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
                        // API Configuration (Replace with your actual API keys in production)
                        const GOOGLE_API_KEY = 'YOUR_GOOGLE_API_KEY';
                        const GOOGLE_CLIENT_ID = 'YOUR_GOOGLE_CLIENT_ID';
                        const ONEDRIVE_CLIENT_ID = 'YOUR_ONEDRIVE_CLIENT_ID';
                        
                        // Handle storage source selection
                        document.querySelectorAll('input[name="storage_source"]').forEach(radio => {
                            radio.addEventListener('change', function() {
                                const localStorageInput = document.getElementById('local_storage_input');
                                const cloudStorageInput = document.getElementById('cloud_storage_input');
                                const imagesInput = document.getElementById('images');
                                const browseCloudBtn = document.getElementById('browse_cloud_btn');
                                
                                if (this.value === 'local') {
                                    localStorageInput.classList.remove('d-none');
                                    cloudStorageInput.classList.add('d-none');
                                    imagesInput.setAttribute('required', 'required');
                                    document.getElementById('cloud_file_data').value = '';
                                    document.getElementById('cloud_file_path').value = '';
                                } else {
                                    localStorageInput.classList.add('d-none');
                                    cloudStorageInput.classList.remove('d-none');
                                    imagesInput.removeAttribute('required');
                                    
                                    // Update browse button text based on selected storage
                                    if (this.value === 'google_drive') {
                                        browseCloudBtn.innerHTML = '<i class="fab fa-google-drive"></i> Browse Google Drive';
                                    } else if (this.value === 'onedrive') {
                                        browseCloudBtn.innerHTML = '<i class="fas fa-cloud"></i> Browse OneDrive';
                                    }
                                }
                            });
                        });
                        
                        // Handle cloud storage browse button
                        document.getElementById('browse_cloud_btn').addEventListener('click', function() {
                            const storageSource = document.querySelector('input[name="storage_source"]:checked').value;
                            
                            if (storageSource === 'google_drive') {
                                openGoogleDrivePicker();
                            } else if (storageSource === 'onedrive') {
                                openOneDrivePicker();
                            }
                        });
                        
                        // Simulate file selection (for demonstration)
                        function simulateFileSelection(source, files) {
                            const filePathInput = document.getElementById('cloud_file_path');
                            const fileDataInput = document.getElementById('cloud_file_data');
                            const fileList = document.getElementById('file_list');
                            const filesPreview = document.getElementById('selected_files_preview');
                            
                            // Display file names
                            if (files.length > 0) {
                                filePathInput.value = files.length + ' files selected';
                                
                                // Show file list
                                filesPreview.classList.remove('d-none');
                                fileList.innerHTML = '';
                                
                                files.forEach(file => {
                                    const item = document.createElement('div');
                                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                                    item.innerHTML = `
                                        <div>
                                            <i class="fas fa-image me-2"></i>
                                            ${file.name}
                                        </div>
                                        <span class="badge bg-primary rounded-pill">${source}</span>
                                    `;
                                    fileList.appendChild(item);
                                });
                                
                                // Store file data as JSON
                                fileDataInput.value = JSON.stringify(files.map(f => ({
                                    name: f.name,
                                    id: f.id,
                                    source: source,
                                    url: f.url || ''
                                })));
                            } else {
                                filePathInput.value = 'No files selected';
                                filesPreview.classList.add('d-none');
                                fileDataInput.value = '';
                            }
                        }
                        
                        // Google Drive Picker (placeholder implementation)
                        function openGoogleDrivePicker() {
                            // In a real implementation, you would use the Google Picker API
                            // For demonstration, we'll simulate file selection
                            const mockFiles = [
                                { name: 'concert_image1.jpg', id: 'gdrive1', url: 'https://drive.google.com/file/d/123' },
                                { name: 'band_photo.png', id: 'gdrive2', url: 'https://drive.google.com/file/d/456' }
                            ];
                            
                            simulateFileSelection('Google Drive', mockFiles);
                            
                            /* 
                            // Real implementation would look something like this:
                            gapi.load('picker', () => {
                                const picker = new google.picker.PickerBuilder()
                                    .addView(google.picker.ViewId.PHOTOS)
                                    .addView(google.picker.ViewId.DOCS_IMAGES)
                                    .setOAuthToken(YOUR_OAUTH_TOKEN)
                                    .setDeveloperKey(GOOGLE_API_KEY)
                                    .setCallback(pickerCallback)
                                    .build();
                                picker.setVisible(true);
                            });
                            */
                        }
                        
                        // OneDrive Picker (placeholder implementation)
                        function openOneDrivePicker() {
                            // In a real implementation, you would use the OneDrive Picker API
                            // For demonstration, we'll simulate file selection
                            const mockFiles = [
                                { name: 'event_photo.jpg', id: 'onedrive1', url: 'https://onedrive.live.com/123' },
                                { name: 'stage_setup.png', id: 'onedrive2', url: 'https://onedrive.live.com/456' },
                                { name: 'audience.jpg', id: 'onedrive3', url: 'https://onedrive.live.com/789' }
                            ];
                            
                            simulateFileSelection('OneDrive', mockFiles);
                            
                            /*
                            // Real implementation would look something like this:
                            OneDrive.open({
                                clientId: ONEDRIVE_CLIENT_ID,
                                action: 'pick',
                                multiSelect: true,
                                advanced: {
                                    filter: '.jpg,.jpeg,.png,.gif'
                                },
                                success: function(files) {
                                    // Process selected files
                                    simulateFileSelection('OneDrive', files);
                                },
                                cancel: function() {
                                    console.log('OneDrive selection cancelled');
                                },
                                error: function(error) {
                                    console.error('OneDrive selection error', error);
                                }
                            });
                            */
                        }
                    </script>
                    @endsection

                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-light">
                            <h3 class="mb-0">Upload Guidelines</h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Images should be high quality and relevant to the contest.</li>
                                <li>Recommended image dimensions: 1200 x 800 pixels.</li>
                                <li>Maximum file size: 2MB per image.</li>
                                <li>Supported formats: JPG, PNG, GIF.</li>
                                <li>Avoid uploading copyrighted images unless you have permission.</li>
                                <li>Images will be displayed in the order they are uploaded.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('head')
<!-- Google Drive API -->
<script src="https://apis.google.com/js/api.js"></script>
<script src="https://accounts.google.com/gsi/client"></script>

<!-- OneDrive API -->
<script src="https://js.live.net/v7.2/OneDrive.js"></script>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all radio buttons and storage input containers
        const storageRadios = document.querySelectorAll('input[name="storage_source"]');
        const localStorageInput = document.getElementById('local_storage_input');
        const cloudStorageInput = document.getElementById('cloud_storage_input');
        const browseCloudBtn = document.getElementById('browse_cloud_btn');
        const cloudFilePath = document.getElementById('cloud_file_path');
        const cloudFileData = document.getElementById('cloud_file_data');
        const imagesInput = document.getElementById('images');
        const selectedFilesPreview = document.getElementById('selected_files_preview');
        const fileList = document.getElementById('file_list');
        
        // Array to store selected files
        let selectedFiles = [];
        
        // Add event listeners to radio buttons
        storageRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'local') {
                    localStorageInput.classList.remove('d-none');
                    cloudStorageInput.classList.add('d-none');
                    imagesInput.setAttribute('required', 'required');
                    // Clear cloud storage selections when switching to local
                    selectedFiles = [];
                    updateSelectedFilesUI();
                } else {
                    localStorageInput.classList.add('d-none');
                    cloudStorageInput.classList.remove('d-none');
                    imagesInput.removeAttribute('required');
                }
            });
        });
        
        // Function to update the UI with selected files
        function updateSelectedFilesUI() {
            if (selectedFiles.length > 0) {
                cloudFilePath.value = selectedFiles.length + ' files selected';
                selectedFilesPreview.classList.remove('d-none');
                
                // Clear and rebuild the file list
                fileList.innerHTML = '';
                selectedFiles.forEach((file, index) => {
                    const item = document.createElement('div');
                    item.className = 'list-group-item d-flex justify-content-between align-items-center';
                    item.innerHTML = `
                        <div>
                            <i class="fas fa-image me-2"></i>
                            ${file.name}
                        </div>
                        <span class="badge bg-primary rounded-pill">${file.source}</span>
                    `;
                    fileList.appendChild(item);
                });
                
                // Store file data as JSON
                cloudFileData.value = JSON.stringify(selectedFiles);
            } else {
                cloudFilePath.value = 'No files selected';
                selectedFilesPreview.classList.add('d-none');
                cloudFileData.value = '';
            }
        }
        
        // Handle cloud storage browse button click
        browseCloudBtn.addEventListener('click', function() {
            const selectedStorage = document.querySelector('input[name="storage_source"]:checked').value;
            
            if (selectedStorage === 'google_drive') {
                openGoogleDrivePicker();
            } else if (selectedStorage === 'onedrive') {
                openOneDrivePicker();
            }
        });
        
        // Google Drive Picker implementation
        function openGoogleDrivePicker() {
            // Check if Google API is loaded
            if (typeof gapi === 'undefined') {
                alert('Google Drive API is not loaded. Please try again later.');
                return;
            }
            
            // For demonstration purposes, we'll simulate multiple file selections
            const mockFiles = [
                { name: 'concert_image1.jpg', id: 'gdrive1', url: 'https://drive.google.com/file/d/123', source: 'Google Drive' },
                { name: 'band_photo.png', id: 'gdrive2', url: 'https://drive.google.com/file/d/456', source: 'Google Drive' },
                { name: 'venue_shot.jpg', id: 'gdrive3', url: 'https://drive.google.com/file/d/789', source: 'Google Drive' }
            ];
            
            // Add the mock files to our selection
            selectedFiles = mockFiles;
            updateSelectedFilesUI();
            
            /* Real implementation would look something like this:
            
            gapi.load('picker', {'callback': function() {
                const view = new google.picker.View(google.picker.ViewId.PHOTOS);
                view.setMimeTypes("image/png,image/jpeg,image/jpg,image/gif");
                
                const picker = new google.picker.PickerBuilder()
                    .enableFeature(google.picker.Feature.NAV_HIDDEN)
                    .enableFeature(google.picker.Feature.MULTISELECT_ENABLED)
                    .setAppId(googleDriveConfig.appId)
                    .setOAuthToken(accessToken)
                    .addView(view)
                    .addView(new google.picker.DocsUploadView())
                    .setCallback(function(data) {
                        if (data.action === google.picker.Action.PICKED) {
                            selectedFiles = data.docs.map(doc => ({
                                name: doc.name,
                                id: doc.id,
                                url: doc.url,
                                source: 'Google Drive'
                            }));
                            updateSelectedFilesUI();
                        }
                    })
                    .build();
                    
                picker.setVisible(true);
            }});
            */
        }
        
        // OneDrive Picker implementation
        function openOneDrivePicker() {
            // Check if OneDrive API is loaded
            if (typeof OneDrive === 'undefined') {
                alert('OneDrive API is not loaded. Please try again later.');
                return;
            }
            
            // For demonstration purposes, we'll simulate multiple file selections
            const mockFiles = [
                { name: 'event_photo.jpg', id: 'onedrive1', url: 'https://onedrive.live.com/123', source: 'OneDrive' },
                { name: 'stage_setup.png', id: 'onedrive2', url: 'https://onedrive.live.com/456', source: 'OneDrive' },
                { name: 'audience.jpg', id: 'onedrive3', url: 'https://onedrive.live.com/789', source: 'OneDrive' },
                { name: 'backstage.jpg', id: 'onedrive4', url: 'https://onedrive.live.com/101', source: 'OneDrive' }
            ];
            
            // Add the mock files to our selection
            selectedFiles = mockFiles;
            updateSelectedFilesUI();
            
            /* Real implementation would look something like this:
            
            const oneDriveOptions = {
                clientId: "YOUR_CLIENT_ID",
                action: "download",
                multiSelect: true,
                advanced: {
                    filter: ".jpg,.jpeg,.png,.gif"
                },
                success: function(response) {
                    selectedFiles = response.value.map(file => ({
                        name: file.name,
                        id: file.id,
                        url: file['@microsoft.graph.downloadUrl'],
                        source: 'OneDrive'
                    }));
                    updateSelectedFilesUI();
                },
                cancel: function() {
                    // Handle cancellation
                },
                error: function(error) {
                    // Handle error
                    alert(`Error: ${error.message}`);
                }
            };
            
            OneDrive.open(oneDriveOptions);
            */
        }
    });
</script>
@endsection