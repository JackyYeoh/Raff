<?php
// Payment Components - Reusable UI components for payment processing
?>

<!-- Payment Method Selection Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-ps-blue to-ps-light p-6 text-white">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-heading font-bold">Choose Payment Method</h3>
                <button onclick="closePaymentModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div id="paymentAmount" class="mt-2 text-2xl font-bold"></div>
        </div>
        
        <!-- Payment Methods -->
        <div class="p-6 space-y-4">
            <!-- Touch 'n Go -->
            <button onclick="selectPaymentMethod('touchngo')" class="payment-method-btn w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-ps-blue hover:bg-ps-blue/5 transition-all group">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-mobile-alt text-white text-xl"></i>
                    </div>
                    <div class="text-left">
                        <div class="font-semibold text-gray-900 group-hover:text-ps-blue">Touch 'n Go eWallet</div>
                        <div class="text-sm text-gray-500">Fast & secure e-wallet</div>
                    </div>
                </div>
                <div class="text-ps-blue">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </button>
            
            <!-- Google Pay -->
            <button onclick="selectPaymentMethod('googlepay')" class="payment-method-btn w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-ps-blue hover:bg-ps-blue/5 transition-all group">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-500 to-yellow-500 rounded-xl flex items-center justify-center">
                        <i class="fab fa-google-pay text-white text-xl"></i>
                    </div>
                    <div class="text-left">
                        <div class="font-semibold text-gray-900 group-hover:text-ps-blue">Google Pay</div>
                        <div class="text-sm text-gray-500">Pay with Google Pay</div>
                    </div>
                </div>
                <div class="text-ps-blue">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </button>
            
            <!-- Wallet Payment (if sufficient balance) -->
            <button id="walletPaymentBtn" onclick="selectPaymentMethod('wallet')" class="payment-method-btn w-full flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-ps-blue hover:bg-ps-blue/5 transition-all group hidden">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-500 rounded-xl flex items-center justify-center">
                        <i class="fa-solid fa-wallet text-white text-xl"></i>
                    </div>
                    <div class="text-left">
                        <div class="font-semibold text-gray-900 group-hover:text-ps-blue">RaffLah! Wallet</div>
                        <div id="walletBalance" class="text-sm text-gray-500">Balance: RM 0.00</div>
                    </div>
                </div>
                <div class="text-ps-blue">
                    <i class="fa-solid fa-chevron-right"></i>
                </div>
            </button>
        </div>
        
        <!-- Footer -->
        <div class="px-6 pb-6">
            <div class="text-xs text-gray-500 text-center">
                <i class="fa-solid fa-shield-alt mr-1"></i>
                Your payment is secured with bank-level encryption
            </div>
        </div>
    </div>
</div>

<!-- Touch 'n Go Payment Modal -->
<div id="touchngoModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-500 to-cyan-500 p-6 text-white text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-mobile-alt text-2xl"></i>
            </div>
            <h3 class="text-xl font-heading font-bold">Touch 'n Go eWallet</h3>
            <div id="touchngoAmount" class="mt-2 text-2xl font-bold"></div>
        </div>
        
        <!-- QR Code Section -->
        <div class="p-6 text-center">
            <div class="mb-4">
                <div class="text-lg font-semibold text-gray-900 mb-2">Scan QR Code to Pay</div>
                <div class="text-sm text-gray-500">Open Touch 'n Go eWallet app and scan the QR code below</div>
            </div>
            
            <!-- QR Code Placeholder -->
            <div id="qrCodeContainer" class="w-48 h-48 mx-auto bg-gray-100 rounded-xl flex items-center justify-center mb-4">
                <div class="text-center">
                    <i class="fa-solid fa-qrcode text-4xl text-gray-400 mb-2"></i>
                    <div class="text-sm text-gray-500">Generating QR Code...</div>
                </div>
            </div>
            
            <!-- Payment Status -->
            <div id="paymentStatus" class="mb-4">
                <div class="flex items-center justify-center space-x-2 text-orange-600">
                    <i class="fa-solid fa-clock"></i>
                    <span>Waiting for payment...</span>
                </div>
            </div>
            
            <!-- Timer -->
            <div id="paymentTimer" class="text-sm text-gray-500 mb-4">
                Payment expires in: <span id="timerDisplay" class="font-mono font-bold">15:00</span>
            </div>
            
            <!-- Instructions -->
            <div class="bg-blue-50 rounded-xl p-4 mb-4">
                <div class="text-sm text-blue-800">
                    <div class="font-semibold mb-2">How to pay:</div>
                    <ol class="text-left space-y-1">
                        <li>1. Open Touch 'n Go eWallet app</li>
                        <li>2. Tap "Scan & Pay"</li>
                        <li>3. Scan the QR code above</li>
                        <li>4. Confirm payment in the app</li>
                    </ol>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-3">
                <button onclick="closeTouchNGoModal()" class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button onclick="checkPaymentStatus()" class="flex-1 bg-blue-500 text-white font-bold py-3 rounded-xl hover:bg-blue-600 transition">
                    Check Status
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Google Pay Modal -->
<div id="googlepayModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-500 to-yellow-500 p-6 text-white text-center">
            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fab fa-google-pay text-2xl"></i>
            </div>
            <h3 class="text-xl font-heading font-bold">Google Pay</h3>
            <div id="googlepayAmount" class="mt-2 text-2xl font-bold"></div>
        </div>
        
        <!-- Payment Section -->
        <div class="p-6 text-center">
            <div class="mb-6">
                <div class="text-lg font-semibold text-gray-900 mb-2">Complete Payment</div>
                <div class="text-sm text-gray-500">You'll be redirected to Google Pay to complete your payment</div>
            </div>
            
            <!-- Google Pay Button -->
            <div id="googlePayButton" class="mb-6">
                <button onclick="processGooglePayPayment()" class="w-full bg-black text-white font-bold py-4 rounded-xl flex items-center justify-center space-x-3 hover:bg-gray-800 transition">
                    <i class="fab fa-google-pay text-2xl"></i>
                    <span>Pay with Google Pay</span>
                </button>
            </div>
            
            <!-- Payment Status -->
            <div id="googlepayStatus" class="mb-4 hidden">
                <div class="flex items-center justify-center space-x-2 text-orange-600">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <span>Processing payment...</span>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="bg-gray-50 rounded-xl p-4 mb-4">
                <div class="text-sm text-gray-600">
                    <i class="fa-solid fa-shield-alt text-green-600 mr-2"></i>
                    Your payment is processed securely by Google Pay
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex space-x-3">
                <button onclick="closeGooglePayModal()" class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Wallet Top-up Modal -->
<div id="walletTopupModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-500 to-pink-500 p-6 text-white">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-heading font-bold">Top Up Wallet</h3>
                <button onclick="closeWalletTopupModal()" class="text-white hover:text-gray-200 transition">
                    <i class="fa-solid fa-times text-xl"></i>
                </button>
            </div>
            <div class="mt-2 text-sm opacity-90">Add money to your RaffLah! wallet</div>
        </div>
        
        <!-- Amount Selection -->
        <div class="p-6">
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Select Amount</label>
                <div class="grid grid-cols-3 gap-3 mb-4">
                    <button onclick="setTopupAmount(10)" class="topup-amount-btn border-2 border-gray-200 rounded-xl p-3 text-center hover:border-ps-blue hover:bg-ps-blue/5 transition">
                        <div class="font-bold text-gray-900">RM 10</div>
                    </button>
                    <button onclick="setTopupAmount(50)" class="topup-amount-btn border-2 border-gray-200 rounded-xl p-3 text-center hover:border-ps-blue hover:bg-ps-blue/5 transition">
                        <div class="font-bold text-gray-900">RM 50</div>
                    </button>
                    <button onclick="setTopupAmount(100)" class="topup-amount-btn border-2 border-gray-200 rounded-xl p-3 text-center hover:border-ps-blue hover:bg-ps-blue/5 transition">
                        <div class="font-bold text-gray-900">RM 100</div>
                    </button>
                    <button onclick="setTopupAmount(200)" class="topup-amount-btn border-2 border-gray-200 rounded-xl p-3 text-center hover:border-ps-blue hover:bg-ps-blue/5 transition">
                        <div class="font-bold text-gray-900">RM 200</div>
                    </button>
                    <button onclick="setTopupAmount(500)" class="topup-amount-btn border-2 border-gray-200 rounded-xl p-3 text-center hover:border-ps-blue hover:bg-ps-blue/5 transition">
                        <div class="font-bold text-gray-900">RM 500</div>
                    </button>
                    <button onclick="setTopupAmount(1000)" class="topup-amount-btn border-2 border-gray-200 rounded-xl p-3 text-center hover:border-ps-blue hover:bg-ps-blue/5 transition">
                        <div class="font-bold text-gray-900">RM 1000</div>
                    </button>
                </div>
                
                <!-- Custom Amount -->
                <div class="mb-4">
                    <input type="number" id="customTopupAmount" placeholder="Enter custom amount" min="1" max="5000" 
                           class="w-full p-3 border-2 border-gray-200 rounded-xl focus:border-ps-blue focus:outline-none"
                           onchange="setTopupAmount(this.value)">
                </div>
                
                <!-- Selected Amount Display -->
                <div class="bg-ps-blue/10 rounded-xl p-4 mb-6">
                    <div class="text-center">
                        <div class="text-sm text-ps-blue font-semibold">Amount to Top Up</div>
                        <div id="selectedTopupAmount" class="text-2xl font-bold text-ps-blue">RM 0.00</div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Method Selection for Top-up -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-3">Payment Method</label>
                <div class="space-y-3">
                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-ps-blue transition">
                        <input type="radio" name="topupMethod" value="touchngo" class="mr-3" checked>
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-mobile-alt text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="font-semibold">Touch 'n Go eWallet</div>
                                <div class="text-xs text-gray-500">No fees</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer hover:border-ps-blue transition">
                        <input type="radio" name="topupMethod" value="googlepay" class="mr-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                                <i class="fab fa-google-pay text-white text-sm"></i>
                            </div>
                            <div>
                                <div class="font-semibold">Google Pay</div>
                                <div class="text-xs text-gray-500">No fees</div>
                            </div>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Top-up Button -->
            <button onclick="processWalletTopup()" id="topupBtn" class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white font-bold py-4 rounded-xl hover:from-purple-600 hover:to-pink-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fa-solid fa-plus mr-2"></i>
                Top Up Wallet
            </button>
        </div>
    </div>
</div>

<!-- Payment Success Modal -->
<div id="paymentSuccessModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 overflow-hidden">
        <div class="p-8 text-center">
            <!-- Success Icon -->
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-check text-3xl text-green-600"></i>
            </div>
            
            <!-- Success Message -->
            <h3 class="text-2xl font-heading font-bold text-gray-900 mb-2">Payment Successful!</h3>
            <div id="successMessage" class="text-gray-600 mb-6"></div>
            
            <!-- Payment Details -->
            <div id="paymentDetails" class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
                <!-- Details will be populated by JavaScript -->
            </div>
            
            <!-- Action Button -->
            <button onclick="closePaymentSuccessModal()" class="w-full bg-ps-blue text-white font-bold py-3 rounded-xl hover:bg-ps-light transition">
                Continue
            </button>
        </div>
    </div>
</div>

<style>
/* Payment Component Styles */
.payment-method-btn.selected {
    border-color: var(--ps-blue);
    background-color: rgba(0, 122, 255, 0.05);
}

.topup-amount-btn.selected {
    border-color: var(--ps-blue);
    background-color: rgba(0, 122, 255, 0.1);
    color: var(--ps-blue);
}

.payment-modal {
    backdrop-filter: blur(10px);
}

.qr-code-animation {
    animation: qrPulse 2s ease-in-out infinite;
}

@keyframes qrPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.payment-timer {
    font-family: 'Monaco', 'Consolas', monospace;
}

/* Loading states */
.payment-loading {
    opacity: 0.7;
    pointer-events: none;
}

.payment-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #007aff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
// Global payment variables
let currentPaymentData = null;
let paymentTimer = null;
let selectedTopupAmount = 0;

// Payment Modal Functions
function openPaymentModal(amount, type = 'wallet_topup', metadata = {}) {
    currentPaymentData = { amount, type, metadata };
    document.getElementById('paymentAmount').textContent = `RM ${amount.toFixed(2)}`;
    
    // Show/hide wallet payment option based on balance
    const walletBtn = document.getElementById('walletPaymentBtn');
    const walletBalance = parseFloat(document.getElementById('walletBalance')?.textContent?.replace(/[^\d.]/g, '') || '0');
    
    if (type === 'ticket_purchase' && walletBalance >= amount) {
        walletBtn.classList.remove('hidden');
        document.getElementById('walletBalance').textContent = `Balance: RM ${walletBalance.toFixed(2)}`;
    } else {
        walletBtn.classList.add('hidden');
    }
    
    document.getElementById('paymentModal').classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    currentPaymentData = null;
}

function selectPaymentMethod(method) {
    if (!currentPaymentData) return;
    
    closePaymentModal();
    
    switch (method) {
        case 'touchngo':
            initiateTouchNGoPayment();
            break;
        case 'googlepay':
            initiateGooglePayPayment();
            break;
        case 'wallet':
            processWalletPayment();
            break;
    }
}

// Touch 'n Go Payment Functions
function initiateTouchNGoPayment() {
    document.getElementById('touchngoAmount').textContent = `RM ${currentPaymentData.amount.toFixed(2)}`;
    document.getElementById('touchngoModal').classList.remove('hidden');
    
    // Create payment intent
    createPaymentIntent('touchngo');
}

function closeTouchNGoModal() {
    document.getElementById('touchngoModal').classList.add('hidden');
    if (paymentTimer) {
        clearInterval(paymentTimer);
        paymentTimer = null;
    }
}

// Google Pay Payment Functions
function initiateGooglePayPayment() {
    document.getElementById('googlepayAmount').textContent = `RM ${currentPaymentData.amount.toFixed(2)}`;
    document.getElementById('googlepayModal').classList.remove('hidden');
}

function closeGooglePayModal() {
    document.getElementById('googlepayModal').classList.add('hidden');
}

function processGooglePayPayment() {
    document.getElementById('googlepayStatus').classList.remove('hidden');
    createPaymentIntent('googlepay');
}

// Wallet Top-up Functions
function openWalletTopupModal() {
    document.getElementById('walletTopupModal').classList.remove('hidden');
}

function closeWalletTopupModal() {
    document.getElementById('walletTopupModal').classList.add('hidden');
    selectedTopupAmount = 0;
    document.getElementById('selectedTopupAmount').textContent = 'RM 0.00';
    document.getElementById('customTopupAmount').value = '';
    
    // Reset amount buttons
    document.querySelectorAll('.topup-amount-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
}

function setTopupAmount(amount) {
    selectedTopupAmount = parseFloat(amount) || 0;
    document.getElementById('selectedTopupAmount').textContent = `RM ${selectedTopupAmount.toFixed(2)}`;
    
    // Update button states
    document.querySelectorAll('.topup-amount-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    
    // Highlight selected button if it's a preset amount
    const presetAmounts = [10, 50, 100, 200, 500, 1000];
    if (presetAmounts.includes(selectedTopupAmount)) {
        document.querySelector(`button[onclick="setTopupAmount(${selectedTopupAmount})"]`)?.classList.add('selected');
    }
    
    // Enable/disable top-up button
    const topupBtn = document.getElementById('topupBtn');
    topupBtn.disabled = selectedTopupAmount <= 0;
}

function processWalletTopup() {
    if (selectedTopupAmount <= 0) {
        alert('Please select an amount to top up');
        return;
    }
    
    const selectedMethod = document.querySelector('input[name="topupMethod"]:checked').value;
    
    closeWalletTopupModal();
    openPaymentModal(selectedTopupAmount, 'wallet_topup', { method: selectedMethod });
}

// Payment Processing Functions
async function createPaymentIntent(paymentMethod) {
    try {
        const formData = new FormData();
        formData.append('action', 'create_payment');
        formData.append('amount', currentPaymentData.amount);
        formData.append('payment_method', paymentMethod);
        formData.append('type', currentPaymentData.type);
        
        // Add metadata for ticket purchases
        if (currentPaymentData.type === 'ticket_purchase') {
            formData.append('raffle_id', currentPaymentData.metadata.raffle_id);
            formData.append('quantity', currentPaymentData.metadata.quantity);
        }
        
        const response = await fetch('api/payment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            handlePaymentIntentSuccess(result.payment_data, paymentMethod);
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        alert('Payment initialization failed: ' + error.message);
        closeTouchNGoModal();
        closeGooglePayModal();
    }
}

function handlePaymentIntentSuccess(paymentData, paymentMethod) {
    switch (paymentMethod) {
        case 'touchngo':
            displayTouchNGoQR(paymentData);
            startPaymentTimer(15 * 60); // 15 minutes
            pollPaymentStatus(paymentData.payment_id);
            break;
            
        case 'googlepay':
            // In a real implementation, you would redirect to Google Pay
            // For demo, we'll simulate the payment
            setTimeout(() => {
                simulatePaymentCompletion(paymentData.payment_id);
            }, 2000);
            break;
    }
}

function displayTouchNGoQR(paymentData) {
    const qrContainer = document.getElementById('qrCodeContainer');
    qrContainer.innerHTML = `
        <div class="qr-code-animation">
            <img src="${paymentData.qr_code}" alt="QR Code" class="w-full h-full object-contain rounded-lg">
        </div>
    `;
}

function startPaymentTimer(seconds) {
    let timeLeft = seconds;
    const timerDisplay = document.getElementById('timerDisplay');
    
    paymentTimer = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        timerDisplay.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(paymentTimer);
            handlePaymentTimeout();
        }
        
        timeLeft--;
    }, 1000);
}

function handlePaymentTimeout() {
    document.getElementById('paymentStatus').innerHTML = `
        <div class="flex items-center justify-center space-x-2 text-red-600">
            <i class="fa-solid fa-times"></i>
            <span>Payment expired</span>
        </div>
    `;
}

async function pollPaymentStatus(paymentId) {
    const pollInterval = setInterval(async () => {
        try {
            const response = await fetch(`api/payment.php?action=payment_status&payment_id=${paymentId}`);
            const result = await response.json();
            
            if (result.success && result.payment.status === 'completed') {
                clearInterval(pollInterval);
                handlePaymentSuccess(result.payment);
            } else if (result.success && ['failed', 'cancelled'].includes(result.payment.status)) {
                clearInterval(pollInterval);
                handlePaymentFailure(result.payment);
            }
        } catch (error) {
            console.error('Payment status check failed:', error);
        }
    }, 3000); // Check every 3 seconds
    
    // Stop polling after 20 minutes
    setTimeout(() => {
        clearInterval(pollInterval);
    }, 20 * 60 * 1000);
}

async function checkPaymentStatus() {
    // Manual status check
    if (currentPaymentData?.payment_id) {
        try {
            const response = await fetch(`api/payment.php?action=payment_status&payment_id=${currentPaymentData.payment_id}`);
            const result = await response.json();
            
            if (result.success) {
                updatePaymentStatus(result.payment.status);
            }
        } catch (error) {
            alert('Failed to check payment status');
        }
    }
}

function updatePaymentStatus(status) {
    const statusElement = document.getElementById('paymentStatus');
    
    switch (status) {
        case 'completed':
            statusElement.innerHTML = `
                <div class="flex items-center justify-center space-x-2 text-green-600">
                    <i class="fa-solid fa-check"></i>
                    <span>Payment successful!</span>
                </div>
            `;
            break;
        case 'failed':
            statusElement.innerHTML = `
                <div class="flex items-center justify-center space-x-2 text-red-600">
                    <i class="fa-solid fa-times"></i>
                    <span>Payment failed</span>
                </div>
            `;
            break;
        case 'pending':
            statusElement.innerHTML = `
                <div class="flex items-center justify-center space-x-2 text-orange-600">
                    <i class="fa-solid fa-clock"></i>
                    <span>Waiting for payment...</span>
                </div>
            `;
            break;
    }
}

// Wallet Payment Function
async function processWalletPayment() {
    try {
        const formData = new FormData();
        formData.append('action', 'wallet_payment');
        formData.append('raffle_id', currentPaymentData.metadata.raffle_id);
        formData.append('quantity', currentPaymentData.metadata.quantity);
        
        const response = await fetch('api/payment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showPaymentSuccess({
                id: result.payment_id,
                amount: result.total_amount,
                method: 'wallet',
                tickets: result.tickets,
                loyalty_points: result.loyalty_points_earned
            });
        } else {
            alert('Wallet payment failed: ' + result.error);
        }
    } catch (error) {
        alert('Wallet payment failed: ' + error.message);
    }
}

// Success/Failure Handlers
function handlePaymentSuccess(payment) {
    closeTouchNGoModal();
    closeGooglePayModal();
    
    if (paymentTimer) {
        clearInterval(paymentTimer);
        paymentTimer = null;
    }
    
    showPaymentSuccess(payment);
}

function handlePaymentFailure(payment) {
    alert(`Payment ${payment.status}. Please try again.`);
    closeTouchNGoModal();
    closeGooglePayModal();
}

function showPaymentSuccess(payment) {
    const modal = document.getElementById('paymentSuccessModal');
    const messageEl = document.getElementById('successMessage');
    const detailsEl = document.getElementById('paymentDetails');
    
    let message = 'Your payment has been processed successfully!';
    let details = `
        <div class="flex justify-between py-2">
            <span class="text-gray-600">Payment ID:</span>
            <span class="font-mono text-sm">${payment.id}</span>
        </div>
        <div class="flex justify-between py-2">
            <span class="text-gray-600">Amount:</span>
            <span class="font-bold">RM ${payment.amount}</span>
        </div>
        <div class="flex justify-between py-2">
            <span class="text-gray-600">Method:</span>
            <span class="capitalize">${payment.method || payment.payment_method}</span>
        </div>
    `;
    
    if (payment.tickets) {
        message = `Successfully purchased ${payment.tickets.length} ticket(s)!`;
        details += `
            <div class="flex justify-between py-2">
                <span class="text-gray-600">Tickets:</span>
                <span class="font-bold">${payment.tickets.length}</span>
            </div>
        `;
        
        if (payment.loyalty_points) {
            details += `
                <div class="flex justify-between py-2">
                    <span class="text-gray-600">Loyalty Points:</span>
                    <span class="font-bold text-ps-blue">+${payment.loyalty_points}</span>
                </div>
            `;
        }
    }
    
    messageEl.textContent = message;
    detailsEl.innerHTML = details;
    modal.classList.remove('hidden');
    
    // Refresh page data
    setTimeout(() => {
        window.location.reload();
    }, 3000);
}

function closePaymentSuccessModal() {
    document.getElementById('paymentSuccessModal').classList.add('hidden');
    window.location.reload();
}

// Demo function to simulate payment completion
async function simulatePaymentCompletion(paymentId) {
    try {
        const formData = new FormData();
        formData.append('action', 'simulate_payment');
        formData.append('payment_id', paymentId);
        formData.append('status', 'completed');
        
        const response = await fetch('api/payment.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            handlePaymentSuccess({
                id: paymentId,
                amount: currentPaymentData.amount,
                payment_method: 'googlepay'
            });
        } else {
            throw new Error(result.error);
        }
    } catch (error) {
        alert('Payment simulation failed: ' + error.message);
        closeGooglePayModal();
    }
}

// Initialize payment components
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for payment method radio buttons
    document.querySelectorAll('input[name="topupMethod"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Update visual selection
            document.querySelectorAll('label').forEach(label => {
                label.classList.remove('border-ps-blue', 'bg-ps-blue/5');
            });
            this.closest('label').classList.add('border-ps-blue', 'bg-ps-blue/5');
        });
    });
});
</script> 