<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Concerts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Test Concerts</h1>
        
        <button id="test-btn" class="btn btn-primary mb-3">Test JS</button>
        <button id="load-btn" class="btn btn-success mb-3">Load Concerts</button>
        
        <div id="loading" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p>Loading concerts...</p>
        </div>
        
        <div id="concerts" class="row g-3"></div>
        
        <div id="error" class="alert alert-danger" style="display: none;"></div>
    </div>

    <script>
        console.log('Script started');
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            alert('JavaScript is working!');
            
            const testBtn = document.getElementById('test-btn');
            const loadBtn = document.getElementById('load-btn');
            
            if (testBtn) {
                testBtn.addEventListener('click', function() {
                    alert('Test button clicked!');
                    console.log('Test button works');
                });
            }
            
            if (loadBtn) {
                loadBtn.addEventListener('click', loadConcerts);
            }
        });
        
        function loadConcerts() {
            console.log('Loading concerts...');
            
            const loading = document.getElementById('loading');
            const concerts = document.getElementById('concerts');
            const error = document.getElementById('error');
            
            loading.style.display = 'block';
            concerts.style.display = 'none';
            error.style.display = 'none';
            concerts.innerHTML = '';
            
            fetch('/api/concerts/homepage')
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    return response.json();
                })
                .then(data => {
                    console.log('API Data:', data);
                    loading.style.display = 'none';
                    
                    if (data.success && data.concerts) {
                        displayConcerts(data.concerts);
                    } else {
                        error.textContent = 'No concerts found';
                        error.style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    loading.style.display = 'none';
                    error.textContent = 'Error: ' + err.message;
                    error.style.display = 'block';
                });
        }
        
        function displayConcerts(concerts) {
            console.log('Displaying concerts:', concerts.length);
            
            const concertsDiv = document.getElementById('concerts');
            
            let html = '';
            concerts.forEach(concert => {
                html += '<div class="col-md-4">';
                html += '<div class="card">';
                html += '<img src="' + concert.image + '" class="card-img-top" alt="' + concert.name + '" style="height: 200px; object-fit: cover;">';
                html += '<div class="card-body">';
                html += '<h5 class="card-title">' + concert.name + '</h5>';
                html += '<p class="card-text"><strong>' + concert.artist + '</strong></p>';
                html += '<p class="card-text">';
                html += '<i class="fas fa-calendar"></i> ' + concert.date + '<br>';
                html += '<i class="fas fa-map-marker-alt"></i> ' + concert.venue.name + ', ' + concert.venue.city;
                html += '</p>';
                html += '<a href="' + concert.url + '" target="_blank" class="btn btn-primary w-100">Get Tickets</a>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            concertsDiv.innerHTML = html;
            concertsDiv.style.display = 'flex';
        }
    </script>
</body>
</html>
