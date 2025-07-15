<!-- Authentication Modals -->
<div id="authModalOverlay" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm transition-all duration-300 hidden">
    <!-- Login Modal -->
    <div id="loginModal" class="w-full max-w-md mx-auto rounded-3xl shadow-2xl bg-white/90 border border-white/70 p-8 flex flex-col gap-6 relative animate-fade-in">
        <!-- Close Button -->
        <button class="absolute top-4 right-4 text-2xl text-ps-blue hover:text-ps-pink transition" onclick="closeAuthModals()"><i class="fa-solid fa-xmark"></i></button>
        <!-- Header -->
        <div class="flex flex-col items-center gap-2 mb-2">
            <div class="w-14 h-14 rounded-full bg-ps-blue/20 flex items-center justify-center mb-2"><i class="fa-solid fa-user text-3xl text-ps-blue"></i></div>
            <h2 class="font-heading text-2xl font-bold text-ps-blue">Welcome Back!</h2>
            <p class="text-gray-700 text-sm">Sign in to continue your raffle journey</p>
        </div>
        <form id="loginModalForm" class="flex flex-col gap-4">
            <div id="loginAlert" class="hidden rounded-lg px-4 py-3 text-sm font-semibold"></div>
            <div class="flex flex-col gap-2">
                <label for="loginEmail" class="text-sm font-semibold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-envelope"></i> Email Address</label>
                <div class="relative">
                    <input type="email" id="loginEmail" required placeholder="Enter your email address" class="w-full px-4 py-3 rounded-xl border border-ps-blue/20 focus:ring-2 focus:ring-ps-blue outline-none text-base bg-white/95 pl-12 text-gray-900 placeholder-gray-400" />
                    <i class="fa-solid fa-envelope absolute left-4 top-1/2 -translate-y-1/2 text-ps-blue/60"></i>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <label for="loginPassword" class="text-sm font-semibold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-lock"></i> Password</label>
                <div class="relative">
                    <input type="password" id="loginPassword" required placeholder="Enter your password" class="w-full px-4 py-3 rounded-xl border border-ps-blue/20 focus:ring-2 focus:ring-ps-blue outline-none text-base bg-white/95 pl-12 pr-12 text-gray-900 placeholder-gray-400" />
                    <i class="fa-solid fa-lock absolute left-4 top-1/2 -translate-y-1/2 text-ps-blue/60"></i>
                    <button type="button" onclick="togglePassword('loginPassword', 'loginPasswordIcon')" class="absolute right-4 top-1/2 -translate-y-1/2 text-ps-blue/60 hover:text-ps-blue" tabindex="-1"><i id="loginPasswordIcon" class="fa-solid fa-eye"></i></button>
                </div>
            </div>
            <div class="flex items-center justify-between text-xs mt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none text-gray-700">
                    <input type="checkbox" id="rememberMe" class="rounded border-gray-300 focus:ring-ps-blue" />
                    Remember me for 30 days
                </label>
                <a href="#" class="text-ps-blue hover:text-ps-pink font-semibold">Forgot password?</a>
            </div>
            <button type="submit" id="loginModalBtn" class="w-full bg-gradient-to-r from-ps-blue to-ps-light text-white font-heading font-bold py-3 rounded-xl shadow-lg hover:from-ps-light hover:to-ps-blue transition flex items-center justify-center gap-2 text-base"><i class="fa-solid fa-sign-in-alt"></i> Sign In to Your Account</button>
            <!-- Demo Credentials -->
            <div class="bg-white border border-ps-blue/20 rounded-xl p-4 flex flex-col gap-2 items-center mt-2 shadow-sm">
                <div class="flex items-center gap-2 text-ps-blue font-bold"><i class="fa-solid fa-rocket"></i> Demo Account</div>
                <div class="text-xs text-gray-700">john@example.com / password</div>
                <button type="button" onclick="fillDemoLogin()" class="bg-ps-blue/10 text-ps-blue font-semibold px-4 py-2 rounded-lg hover:bg-ps-blue/20 transition">Use Demo</button>
            </div>
            <div class="flex items-center my-2"><div class="flex-1 h-px bg-gray-200"></div><span class="mx-2 text-xs text-gray-400">New to RaffLah?</span><div class="flex-1 h-px bg-gray-200"></div></div>
            <button type="button" onclick="switchToRegister()" class="w-full border border-ps-blue text-ps-blue font-heading font-bold py-3 rounded-xl shadow-sm hover:bg-ps-blue/10 transition flex items-center justify-center gap-2 text-base"><i class="fa-solid fa-user-plus"></i> Create New Account</button>
        </form>
    </div>
    <!-- Register Modal -->
    <div id="registerModal" class="w-full max-w-lg mx-auto rounded-3xl shadow-2xl bg-white/90 border border-white/70 p-6 flex flex-col gap-4 relative animate-fade-in">
        <button class="absolute top-4 right-4 text-2xl text-ps-pink hover:text-ps-blue transition" onclick="closeAuthModals()"><i class="fa-solid fa-xmark"></i></button>
        <div class="flex flex-col items-center gap-2 mb-1">
            <div class="w-14 h-14 rounded-full bg-ps-pink/20 flex items-center justify-center mb-2"><i class="fa-solid fa-user-plus text-3xl text-ps-pink"></i></div>
            <h2 class="font-heading text-2xl font-bold text-ps-pink">Join RaffLah!</h2>
            <p class="text-gray-700 text-sm">Create your account and start winning</p>
        </div>
        <form id="registerModalForm" class="flex flex-col gap-3 max-h-[70vh] overflow-y-auto">
            <div id="registerAlert" class="hidden rounded-lg px-4 py-3 text-sm font-semibold"></div>
            <div class="flex flex-col gap-1">
                <label for="registerName" class="text-sm font-semibold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-user"></i> Full Name</label>
                <div class="relative">
                    <input type="text" id="registerName" required placeholder="Enter your full name" class="w-full px-3 py-2 rounded-xl border border-ps-pink/20 focus:ring-2 focus:ring-ps-pink outline-none text-base bg-white/95 pl-10 text-gray-900 placeholder-gray-400" />
                    <i class="fa-solid fa-user absolute left-3 top-1/2 -translate-y-1/2 text-ps-pink/60"></i>
                </div>
            </div>
            <div class="flex flex-col gap-1">
                <label for="registerEmail" class="text-sm font-semibold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-envelope"></i> Email Address</label>
                <div class="relative">
                    <input type="email" id="registerEmail" required placeholder="Enter your email address" class="w-full px-3 py-2 rounded-xl border border-ps-pink/20 focus:ring-2 focus:ring-ps-pink outline-none text-base bg-white/95 pl-10 text-gray-900 placeholder-gray-400" />
                    <i class="fa-solid fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-ps-pink/60"></i>
                </div>
                <div id="emailStatus" class="helper-text helper-text-info hidden text-xs mt-1 text-gray-700"></div>
            </div>
            <div class="flex flex-col gap-1">
                <label for="registerPhone" class="text-sm font-semibold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-phone"></i> Phone Number <span class="text-xs text-gray-400">(Optional)</span></label>
                <div class="relative">
                    <input type="tel" id="registerPhone" placeholder="+60 123 456 789" class="w-full px-3 py-2 rounded-xl border border-ps-pink/20 focus:ring-2 focus:ring-ps-pink outline-none text-base bg-white/95 pl-10 text-gray-900 placeholder-gray-400" />
                    <i class="fa-solid fa-phone absolute left-3 top-1/2 -translate-y-1/2 text-ps-pink/60"></i>
                </div>
                <div class="helper-text helper-text-info text-xs text-gray-500"><i class="fa-solid fa-shield-alt"></i> Optional for SMS notifications and account recovery</div>
            </div>
            <div class="flex flex-col gap-1">
                <label for="registerPassword" class="text-sm font-semibold text-gray-800 flex items-center gap-2"><i class="fa-solid fa-lock"></i> Create Password</label>
                <div class="relative">
                    <input type="password" id="registerPassword" required placeholder="Create a strong password" class="w-full px-3 py-2 rounded-xl border border-ps-pink/20 focus:ring-2 focus:ring-ps-pink outline-none text-base bg-white/95 pl-10 pr-10 text-gray-900 placeholder-gray-400" />
                    <i class="fa-solid fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-ps-pink/60"></i>
                    <button type="button" onclick="togglePassword('registerPassword', 'registerPasswordIcon')" class="absolute right-3 top-1/2 -translate-y-1/2 text-ps-pink/60 hover:text-ps-pink" tabindex="-1"><i id="registerPasswordIcon" class="fa-solid fa-eye"></i></button>
                </div>
                <div class="helper-text helper-text-info text-xs text-gray-500"><i class="fa-solid fa-key"></i> Must be at least 8 characters with letters and numbers</div>
            </div>
            <div class="flex items-center gap-2 text-xs mt-1">
                <input type="checkbox" id="agreeTerms" required class="rounded border-gray-300 focus:ring-ps-pink" />
                <label for="agreeTerms" class="text-xs text-gray-700">I agree to the <a href="#" class="text-ps-pink hover:underline">Terms & Conditions</a> and <a href="#" class="text-ps-pink hover:underline">Privacy Policy</a></label>
            </div>
            <button type="submit" id="registerModalBtn" class="w-full bg-gradient-to-r from-ps-pink to-ps-yellow text-ps-text font-heading font-bold py-3 rounded-xl shadow-lg hover:from-ps-yellow hover:to-ps-pink transition flex items-center justify-center gap-2 text-base"><i class="fa-solid fa-user-plus"></i> Create Your Account</button>
            <!-- Collapsible Welcome Rewards -->
            <div class="bg-white border border-ps-pink/20 rounded-xl p-3 flex flex-col gap-2 items-center mt-2 shadow-sm">
                <div class="flex items-center gap-2 text-ps-pink font-bold cursor-pointer select-none" onclick="toggleRewardsDetails()"><i class="fa-solid fa-gift"></i> Welcome Rewards <span id="rewardsToggle" class="text-xs text-ps-pink underline ml-2">Show details</span></div>
                <div id="rewardsDetails" class="text-xs text-gray-700 mt-1 hidden">�� 100 loyalty points welcome bonus • 🏆 Exclusive member raffles • 🎯 Early access to premium events • ⚡ Real-time win notifications</div>
            </div>
            <div class="flex items-center my-2"><div class="flex-1 h-px bg-gray-200"></div><span class="mx-2 text-xs text-gray-400">Already a member?</span><div class="flex-1 h-px bg-gray-200"></div></div>
            <button type="button" onclick="switchToLogin()" class="w-full border border-ps-pink text-ps-pink font-heading font-bold py-3 rounded-xl shadow-sm hover:bg-ps-pink/10 transition flex items-center justify-center gap-2 text-base"><i class="fa-solid fa-sign-in-alt"></i> Sign In to Existing Account</button>
        </form>
    </div>
</div>

<script>
// Modal control functions - Updated to use unified modal system
function openLoginModal() {
    document.getElementById('authModalOverlay').classList.remove('hidden');
    document.getElementById('authModalOverlay').classList.add('active');
    document.getElementById('loginModal').style.display = 'flex';
    document.getElementById('registerModal').style.display = 'none';
    
    // Focus on email input
    setTimeout(() => {
        document.getElementById('loginEmail').focus();
    }, 100);
}

function openRegisterModal() {
    document.getElementById('authModalOverlay').classList.remove('hidden');
    document.getElementById('authModalOverlay').classList.add('active');
    document.getElementById('registerModal').style.display = 'flex';
    document.getElementById('loginModal').style.display = 'none';
    
    // Focus on name input
    setTimeout(() => {
        document.getElementById('registerName').focus();
    }, 100);
}

function closeAuthModals() {
    document.getElementById('authModalOverlay').classList.remove('active');
    document.getElementById('authModalOverlay').classList.add('hidden');
    document.getElementById('loginModal').style.display = 'none';
    document.getElementById('registerModal').style.display = 'none';
    
    // Clear forms
    document.getElementById('loginModalForm').reset();
    document.getElementById('registerModalForm').reset();
    hideAlert('loginAlert');
    hideAlert('registerAlert');
}

function switchToRegister() {
    document.getElementById('loginModal').style.display = 'none';
    document.getElementById('registerModal').style.display = 'flex';
    hideAlert('loginAlert');
    
    // Focus on name input
    setTimeout(() => {
        document.getElementById('registerName').focus();
    }, 100);
}

function switchToLogin() {
    document.getElementById('registerModal').style.display = 'none';
    document.getElementById('loginModal').style.display = 'flex';
    hideAlert('registerAlert');
    
    // Focus on email input
    setTimeout(() => {
        document.getElementById('loginEmail').focus();
    }, 100);
}

// Password toggle
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Demo login
function fillDemoLogin() {
    document.getElementById('loginEmail').value = 'john@example.com';
    document.getElementById('loginPassword').value = 'password';
}

// Alert functions
function showAlert(alertId, message, type = 'error') {
    const alert = document.getElementById(alertId);
    alert.className = `modal-alert ${type === 'error' ? 'modal-alert-error' : type === 'success' ? 'modal-alert-success' : 'modal-alert-info'}`;
    alert.innerHTML = `<i class="fa-solid ${type === 'error' ? 'fa-exclamation-triangle' : type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i> ${message}`;
    alert.classList.remove('hidden');
}

function hideAlert(alertId) {
    const alertElement = document.getElementById(alertId);
    if (alertElement) {
        alertElement.classList.add('hidden');
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAuthModals();
    }
});

// Close modal on overlay click
document.getElementById('authModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAuthModals();
    }
});

// Form submissions
document.getElementById('loginModalForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('loginModalBtn');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Signing In...';
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('email', document.getElementById('loginEmail').value);
        formData.append('password', document.getElementById('loginPassword').value);
        
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('loginAlert', result.message, 'success');
            
            setTimeout(() => {
                closeAuthModals();
                window.location.reload(); // Refresh to show logged in state
            }, 1000);
        } else {
            showAlert('loginAlert', result.error);
        }
    } catch (error) {
        showAlert('loginAlert', 'Network error. Please try again.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

document.getElementById('registerModalForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('registerModalBtn');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Creating Account...';
    btn.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('action', 'register');
        formData.append('name', document.getElementById('registerName').value);
        formData.append('email', document.getElementById('registerEmail').value);
        formData.append('phone', document.getElementById('registerPhone').value);
        formData.append('password', document.getElementById('registerPassword').value);
        
        const response = await fetch('api/auth.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('registerAlert', result.message, 'success');
            
            setTimeout(() => {
                // Auto switch to login after successful registration
                switchToLogin();
                document.getElementById('loginEmail').value = document.getElementById('registerEmail').value;
                showAlert('loginAlert', 'Registration successful! Please log in with your credentials.', 'success');
            }, 1500);
        } else {
            showAlert('registerAlert', result.error);
        }
    } catch (error) {
        showAlert('registerAlert', 'Network error. Please try again.');
    } finally {
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});

// Email availability check for register
let emailCheckTimeout;
document.getElementById('registerEmail').addEventListener('input', function() {
    const email = this.value;
    const emailStatus = document.getElementById('emailStatus');
    
    clearTimeout(emailCheckTimeout);
    
    if (email.length < 3 || !email.includes('@')) {
        emailStatus.classList.add('hidden');
        return;
    }
    
    emailCheckTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`api/auth.php?action=check_email&email=${encodeURIComponent(email)}`);
            const result = await response.json();
            
            if (result.available) {
                emailStatus.innerHTML = '<i class="fa-solid fa-check-circle"></i> Email is available';
                emailStatus.className = 'helper-text helper-text-success';
                emailStatus.classList.remove('hidden');
            } else {
                emailStatus.innerHTML = '<i class="fa-solid fa-times-circle"></i> Email is already taken';
                emailStatus.className = 'helper-text helper-text-error';
                emailStatus.classList.remove('hidden');
            }
        } catch (error) {
            emailStatus.classList.add('hidden');
        }
    }, 500);
});

function toggleRewardsDetails() {
    var details = document.getElementById('rewardsDetails');
    var toggle = document.getElementById('rewardsToggle');
    if (details.classList.contains('hidden')) {
        details.classList.remove('hidden');
        toggle.textContent = 'Hide details';
    } else {
        details.classList.add('hidden');
        toggle.textContent = 'Show details';
    }
}
</script>

<style>
/* Enhanced Modal Utilities */
.hidden {
    display: none !important;
}

/* Loading spinner animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.fa-spin {
    animation: spin 1s linear infinite;
}

/* Mobile Responsiveness */
@media (max-width: 640px) {
    .modal-container {
        max-width: 95%;
        margin: 20px;
        border-radius: 20px;
    }
    
    .modal-header {
        padding: 24px 20px;
    }
    
    .modal-header h2 {
        font-size: 24px;
    }
    
    .modal-container form {
        padding: 24px 20px;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 14px 16px;
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    .password-field input {
        padding-right: 45px !important;
    }
    
    .password-toggle {
        right: 12px !important;
        padding: 6px !important;
    }
    
    .modal-btn {
        padding: 14px 20px;
        font-size: 15px;
    }
    
    .demo-content {
        flex-direction: column;
        gap: 12px;
        align-items: stretch;
    }
    
    .demo-credentials {
        text-align: center;
    }
    
    .checkbox-group {
        gap: 10px;
    }
    
    .benefits-content {
        font-size: 12px;
    }
}

/* Focus states for form elements */
.modal-container input:focus,
.modal-container select:focus,
.modal-container textarea:focus {
    outline: none !important;
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
}

/* Button hover effects */
.modal-container button:hover {
    transform: translateY(-1px);
}

.modal-container button:active {
    transform: translateY(0);
}

/* Loading spinner animation */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.fa-spin {
    animation: spin 1s linear infinite;
}
</style> 