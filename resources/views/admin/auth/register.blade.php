<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration - {{ App\Models\Footer::getFooter()->brand_name ?? config('app.name') }}</title>
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
        
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        
        .register-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .register-header .logo {
            margin-bottom: 15px;
        }
        
        .register-header .logo img {
            max-width: 180px;
            height: auto;
        }
        
        .register-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            font-family: 'Righteous', cursive;
        }
        
        .register-header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .register-body {
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
        
        .btn-register {
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
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(44, 62, 80, 0.4);
            color: white;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .login-link a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
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
        
        .password-strength-meter {
            height: 5px;
            border-radius: 3px;
            margin-top: 8px;
            transition: all 0.3s ease;
        }
        
        .password-strength-meter.weak {
            background: linear-gradient(to right, #e74c3c, #c0392b);
        }
        
        .password-strength-meter.medium {
            background: linear-gradient(to right, #f39c12, #e67e22);
        }
        
        .password-strength-meter.strong {
            background: linear-gradient(to right, #27ae60, #2c3e50);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="logo">
                <img src="{{ asset('/Landscape_Logo.png') }}" alt="{{ App\Models\Footer::getFooter()->brand_name ?? config('app.name') }} Logo">
            </div>
            <p>Admin Panel Registration</p>
        </div>
        
        <div class="register-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
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
            
            <form method="POST" action="{{ route('admin.register.submit') }}">
                @csrf
                
                <div class="form-group">
                    <label for="name" class="form-label">
                        <i class="fas fa-user me-2"></i>Full Name
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required 
                               autocomplete="name" 
                               autofocus 
                               placeholder="Enter your full name">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope me-2"></i>Email Address
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               id="email" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autocomplete="email" 
                               placeholder="Enter your email">
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
                               autocomplete="new-password" 
                               placeholder="Create a password"
                               oninput="checkPasswordStrength(this.value)">
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">
                        <i class="fas fa-lock me-2"></i>Confirm Password
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" 
                               class="form-control @error('password_confirmation') is-invalid @enderror" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required 
                               autocomplete="new-password" 
                               placeholder="Confirm your password">
                    </div>
                </div>
                
                <div class="form-group">
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
                    <small class="form-text text-muted">Required secret code for admin registration</small>
                </div>
                
                <button type="submit" class="btn btn-register">
                    <i class="fas fa-user-plus me-2"></i>Register Account
                </button>
            </form>
            
            <div class="login-link">
                <p>Already have an account? <a href="{{ route('admin.login') }}">Login here</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('password-strength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength < 2) {
                strengthDiv.textContent = 'Weak password';
                strengthDiv.className = 'password-strength weak';
            } else if (strength < 3) {
                strengthDiv.textContent = 'Medium password';
                strengthDiv.className = 'password-strength medium';
            } else {
                strengthDiv.textContent = 'Strong password';
                strengthDiv.className = 'password-strength strong';
            }
        }
    </script>
</body>
</html>
