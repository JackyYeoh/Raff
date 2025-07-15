<?php
require_once 'inc/user_auth.php';

// If already logged in, redirect to dashboard
$auth = new UserAuth();
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - RaffLah!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ps: {
                            blue: '#0070D1',
                            light: '#66A9FF', 
                            yellow: '#FFD600',
                            pink: '#FF1177',
                            silver: '#B0B0B0',
                            bg: '#F2F2F2',
                            text: '#1E1E1E',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Poppins', sans-serif; }
        
        .auth-bg {
            background: linear-gradient(135deg, #0070D1 0%, #66A9FF 50%, #FF1177 100%);
            min-height: 100vh;
        }
        
        .auth-card {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 112, 209, 0.1);
        }
        
        .input-group {
            position: relative;
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 112, 209, 0.2);
        }
        
        .login-btn {
            background: linear-gradient(135deg, #FF1177, #FFD600);
            transition: all 0.3s ease;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(255, 17, 119, 0.4);
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            pointer-events: none;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            right: 10%;
            animation-delay: 2s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
    </style>
</head>
<body class="auth-bg flex items-center justify-center">
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="w-full max-w-md mx-4 relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <h1 class="font-heading text-4xl font-bold text-white mb-2">RaffLah!</h1>
            <p class="text-white/80">Welcome back to amazing prizes</p>
        </div>

        <!-- Login Card -->
        <div class="auth-card rounded-3xl p-8 shadow-2xl">
            <div class="text-center mb-8">
                <h2 class="font-heading text-2xl font-bold text-ps-text mb-2">Sign In</h2>
                <p class="text-ps-silver">Enter your credentials to access your account</p>
            </div>

            <!-- Alert Messages -->
            <div id="alert" class="hidden mb-6 p-4 rounded-xl border"></div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-6">
                <!-- Email Field -->
                <div class="input-group">
                    <label class="block text-sm font-semibold text-ps-text mb-2">
                        <i class="fa-solid fa-envelope mr-2 text-ps-blue"></i>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ps-blue focus:border-transparent outline-none"
                        placeholder="your@email.com"
                    >
                </div>

                <!-- Password Field -->
                <div class="input-group">
                    <label class="block text-sm font-semibold text-ps-text mb-2">
                        <i class="fa-solid fa-lock mr-2 text-ps-blue"></i>
                        Password
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="form-input w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-ps-blue focus:border-transparent outline-none pr-12"
                            placeholder="Enter your password"
                        >
                        <button 
                            type="button" 
                            id="togglePassword"
                            class="absolute right-4 top-1/2 transform -translate-y-1/2 text-ps-silver hover:text-ps-blue transition-colors"
                        >
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" id="rememberMe" class="w-4 h-4 text-ps-blue border border-gray-300 rounded focus:ring-ps-blue">
                        <span class="ml-2 text-sm text-ps-text">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-ps-blue hover:text-ps-pink transition-colors">Forgot password?</a>
                </div>

                <!-- Login Button -->
                <button 
                    type="submit" 
                    id="loginBtn"
                    class="login-btn w-full py-3 px-6 rounded-xl text-white font-heading font-bold text-lg shadow-lg"
                >
                    <i class="fa-solid fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
                <h4 class="font-semibold text-blue-800 mb-2">Demo Credentials:</h4>
                <div class="text-sm text-blue-700 space-y-1">
                    <div><strong>Email:</strong> john@example.com</div>
                    <div><strong>Password:</strong> password</div>
                </div>
                <button onclick="fillDemoCredentials()" class="mt-2 text-xs bg-blue-600 text-white px-3 py-1 rounded-lg hover:bg-blue-700 transition-colors">
                    Use Demo Login
                </button>
            </div>

            <!-- Social Login -->
            <div class="mt-8">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-ps-silver">Or continue with</span>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-3">
                    <button class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors">
                        <i class="fab fa-google text-red-500 mr-2"></i>
                        Google
                    </button>
                    <button class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-500 hover:bg-gray-50 transition-colors">
                        <i class="fab fa-facebook text-blue-600 mr-2"></i>
                        Facebook
                    </button>
                </div>
            </div>

            <!-- Register Link -->
            <div class="mt-8 text-center">
                <p class="text-ps-silver">
                    Don't have an account?
                    <a href="register.php" class="text-ps-blue hover:text-ps-pink font-semibold transition-colors">
                        Sign up here
                    </a>
                </p>
            </div>
        </div>

        <!-- Back to Home -->
        <div class="text-center mt-6">
            <a href="index.php" class="text-white/80 hover:text-white transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });

        // Fill demo credentials
        function fillDemoCredentials() {
            document.getElementById('email').value = 'john@example.com';
            document.getElementById('password').value = 'password';
        }

        // Show alert message
        function showAlert(message, type = 'error') {
            const alert = document.getElementById('alert');
            alert.className = `mb-6 p-4 rounded-xl border ${type === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'}`;
            alert.textContent = message;
            alert.classList.remove('hidden');
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                alert.classList.add('hidden');
            }, 5000);
        }

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loginBtn = document.getElementById('loginBtn');
            const originalText = loginBtn.innerHTML;
            
            // Show loading state
            loginBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Signing In...';
            loginBtn.disabled = true;
            
            try {
                const formData = new FormData();
                formData.append('action', 'login');
                formData.append('email', document.getElementById('email').value);
                formData.append('password', document.getElementById('password').value);
                
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    
                    // Check for redirect parameter
                    const urlParams = new URLSearchParams(window.location.search);
                    const redirectUrl = urlParams.get('redirect');
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        window.location.href = redirectUrl || result.redirect || 'dashboard.php';
                    }, 1000);
                } else {
                    showAlert(result.error);
                }
            } catch (error) {
                showAlert('Network error. Please try again.');
                console.error('Login error:', error);
            } finally {
                // Reset button
                loginBtn.innerHTML = originalText;
                loginBtn.disabled = false;
            }
        });

        // Add enter key support for demo credentials
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                fillDemoCredentials();
            }
        });
    </script>
</body>
</html> 