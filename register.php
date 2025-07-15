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
    <title>Register - RaffLah!</title>
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
            background: linear-gradient(135deg, #FF1177 0%, #FFD600 50%, #0070D1 100%);
            min-height: 100vh;
        }
        
        .auth-card {
            backdrop-filter: blur(20px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(255, 17, 119, 0.1);
        }
        
        .input-group {
            position: relative;
        }
        
        .form-input {
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 17, 119, 0.2);
        }
        
        .register-btn {
            background: linear-gradient(135deg, #0070D1, #66A9FF);
            transition: all 0.3s ease;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(0, 112, 209, 0.4);
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background: #ef4444; width: 25%; }
        .strength-fair { background: #f59e0b; width: 50%; }
        .strength-good { background: #10b981; width: 75%; }
        .strength-strong { background: #059669; width: 100%; }
    </style>
</head>
<body class="bg-gradient-to-br from-pink-500 via-yellow-400 to-blue-500 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-3xl p-8 shadow-2xl w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-blue-600 mb-2">RaffLah!</h1>
            <h2 class="text-2xl font-bold text-gray-800">Create Account</h2>
        </div>

        <div id="alert" class="hidden mb-6 p-4 rounded-xl"></div>

        <form id="registerForm" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Full Name</label>
                <input type="text" id="name" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-pink-500 outline-none" placeholder="Enter your full name">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input type="email" id="email" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-pink-500 outline-none" placeholder="your@email.com">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Phone (Optional)</label>
                <input type="tel" id="phone" class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-pink-500 outline-none" placeholder="+60123456789">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                <input type="password" id="password" required class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-pink-500 outline-none" placeholder="Create password">
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="terms" required class="mr-2">
                <label for="terms" class="text-sm text-gray-700">I agree to Terms & Conditions</label>
            </div>

            <button type="submit" id="registerBtn" class="w-full bg-gradient-to-r from-pink-500 to-blue-500 text-white py-3 rounded-xl font-bold hover:shadow-lg transition-all">
                Create Account
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="login.php" class="text-blue-600 hover:text-pink-500">Already have an account? Sign in</a>
        </div>
    </div>

    <script>
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = document.getElementById('registerBtn');
            btn.innerHTML = 'Creating...';
            btn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'register');
            formData.append('name', document.getElementById('name').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('phone', document.getElementById('phone').value);
            formData.append('password', document.getElementById('password').value);
            
            try {
                const response = await fetch('api/auth.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('alert').className = 'mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700';
                    document.getElementById('alert').textContent = 'Registration successful! Redirecting to login...';
                    document.getElementById('alert').classList.remove('hidden');
                    
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 2000);
                } else {
                    document.getElementById('alert').className = 'mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700';
                    document.getElementById('alert').textContent = result.error;
                    document.getElementById('alert').classList.remove('hidden');
                }
            } catch (error) {
                document.getElementById('alert').className = 'mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-700';
                document.getElementById('alert').textContent = 'Registration failed. Please try again.';
                document.getElementById('alert').classList.remove('hidden');
            } finally {
                btn.innerHTML = 'Create Account';
                btn.disabled = false;
            }
        });
    </script>
</body>
</html> 