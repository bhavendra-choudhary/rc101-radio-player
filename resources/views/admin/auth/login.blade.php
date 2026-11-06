<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - {{ App\Models\Footer::getFooter()->brand_name ?? config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Righteous&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #FFD700 0%, #FFD700 40%, #1E3C72 60%, #1E3C72 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header .logo {
            margin-bottom: 15px;
        }
        
        .login-header .logo img {
            max-width: 180px;
            height: auto;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            font-family: 'Righteous', cursive;
        }
        
        .login-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #2c3e50;
            box-shadow: 0 0 0 0.2rem rgba(44, 62, 80, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            border: none;
            border-radius: 8px;
            color: white;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
            color: white;
        }
        
        .form-check {
            margin-bottom: 20px;
        }
        
        .form-check-input:checked {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .register-link a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-right: none;
            border-radius: 8px 0 0 8px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 8px 8px 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="{{ asset('/Landscape_Logo.png') }}" alt="{{ App\Models\Footer::getFooter()->brand_name ?? config('app.name') }} Logo">
            </div>
            <p>Admin Panel Login</p>
        </div>
        
        <div class="login-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session('error') || request('expired'))
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-clock me-2"></i>
                    {{ session('error') ?? 'Your session has expired. Please log in again to continue.' }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <form method="POST" action="{{ route('admin.login.submit') }}">
                @csrf
                
                <div class="form-group">
                    <label for="login" class="form-label">
                        <i class="fas fa-user me-2"></i>Username or Email Address
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" 
                               class="form-control @error('login') is-invalid @enderror @error('name') is-invalid @enderror @error('email') is-invalid @enderror" 
                               id="login" 
                               name="login" 
                               value="{{ old('login', old('name', old('email'))) }}" 
                               required 
                               autocomplete="username" 
                               autofocus 
                               placeholder="Enter username or email">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               id="password" 
                               name="password" 
                               required 
                               autocomplete="current-password" 
                               placeholder="Enter your password">
                    </div>
                </div>
                
                <!-- <div class="form-group">
                    <label for="admin_access_code" class="form-label">
                        <i class="fas fa-key me-2"></i>Admin Access Code
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-key"></i>
                        </span>
                        <input type="password" 
                               class="form-control @error('admin_access_code') is-invalid @enderror" 
                               id="admin_access_code" 
                               name="admin_access_code" 
                               required 
                               autocomplete="off" 
                               placeholder="Enter admin access code">
                    </div>
                    <small class="form-text text-muted">Required secret code for admin access</small>
                </div> -->
                
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <div class="register-link">
                <p>Don't have an account? <a href="{{ route('admin.register') }}">Register here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
