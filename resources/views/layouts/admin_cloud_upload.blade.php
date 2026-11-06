<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Radio Station | Admin')</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Righteous&display=swap" rel="stylesheet">
   
    <!-- Google Drive API -->
    <script src="https://apis.google.com/js/api.js"></script>
    <script src="https://accounts.google.com/gsi/client"></script>
    
    <!-- Microsoft OneDrive API -->
    <script type="text/javascript" src="https://js.live.net/v7.2/OneDrive.js"></script>
    
    <!-- Custom styles from app.blade.php -->
    <style>
        :root {
            /* Professional & Subtle Color Palette */
            --primary-blue: #2c3e50;        /* Sophisticated slate blue */
            --secondary-blue: #34495e;      /* Muted blue-gray */
            --accent-blue: #3498db;         /* Subtle professional blue */
            --accent-orange: #e67e22;       /* Muted orange - professional warmth */
            --accent-orange-light: #f39c12; /* Subtle golden orange */
            --accent-teal: #16a085;         /* Professional teal */
            --accent-teal-light: #1abc9c;   /* Soft mint teal */
            --warm-white: #fdfdfd;          /* Ultra-soft white */
            --light-gray: #f8f9fa;          /* Very light background */
            --soft-yellow: #fffef7;         /* Very soft yellow */
            --light-yellow: #fefcf0;        /* Light yellow for gradient */
            --medium-gray: #6c757d;         /* Professional gray text */
            --dark-gray: #2c3e50;           /* Consistent with primary */
            --text-color: #495057;          /* Subtle dark text */
            --border-color: #e9ecef;        /* Soft borders */
            --success-green: #27ae60;       /* Professional green */
            --player-height: 80px;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: var(--text-color);
            background: linear-gradient(135deg, #FFD700 0%, #FFD700 49.9%, #1E3C72 50.1%, #1E3C72 100%);
            background-size: 200% 200%;
            animation: gradientShift 15s ease infinite;
            background-attachment: fixed;
            overflow-x: hidden;
            padding-bottom: var(--player-height);
            margin: 0;
            line-height: 1.6;
            min-height: 100vh;
        }
    </style>
    
    @yield('head')
</head>
<body>
    @include('partials.header') {{-- Shared header --}}
    
    <main class="py-4">
        @yield('content') {{-- Page-specific content --}}
    </main>

    @include('partials.footer') {{-- Shared footer --}}
    
    <!-- Bootstrap JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    @yield('scripts')
</body>
</html>