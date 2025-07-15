<?php
session_start();
require_once 'inc/database.php';
require_once 'inc/user_auth.php';

$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment System Demo - RaffLah!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ps-blue': '#007aff',
                        'ps-light': '#5ac8fa',
                        'ps-text': '#2c3e50',
                        'ps-bg': '#f8f9fa'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-2xl font-heading font-bold text-ps-blue">RaffLah!</a>
                    <span class="text-gray-500">Payment System Demo</span>
                </div>
                
                <?php if ($currentUser): ?>
                <div class="flex items-center space-x-4">
                    <div class="text-sm">
                        <span class="text-gray-600">Wallet:</span>
                        <span class="font-bold text-ps-blue">RM <?= number_format($currentUser['wallet_balance'], 2) ?></span>
                    </div>
                    <div class="text-sm">
                        <span class="text-gray-600">Hi,</span>
                        <span class="font-semibold"><?= htmlspecialchars($currentUser['full_name']) ?></span>
                    </div>
                </div>
                <?php else: ?>
                <div class="space-x-2">
                    <button onclick="openLoginModal()" class="bg-ps-blue text-white px-4 py-2 rounded-lg hover:bg-ps-light transition">
                        Login
                    </button>
                    <button onclick="openRegisterModal()" class="border border-ps-blue text-ps-blue px-4 py-2 rounded-lg hover:bg-ps-blue hover:text-white transition">
                        Register
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Demo Title -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-heading font-bold text-gray-900 mb-4">
                🚀 Touch 'n Go & Google Pay Integration Demo
            </h1>
            <p class="text-xl text-gray-600">
                Experience fast and secure payments for your raffle platform
            </p>
        </div>

        <!-- Payment Methods Overview -->
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <!-- Touch 'n Go Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-blue-100">
                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-xl mb-6 mx-auto">
                    <i class="fa-solid fa-mobile-alt text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-heading font-bold text-center mb-4">Touch 'n Go eWallet</h3>
                <ul class="space-y-3 text-gray-600 mb-6">
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        QR Code Payment
                    </li>
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        Instant Processing
                    </li>
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        No Transaction Fees
                    </li>
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        15-Minute Session
                    </li>
                </ul>
                <div class="text-center">
                    <div class="text-sm text-gray-500 mb-2">Perfect for Malaysian users</div>
                    <div class="text-2xl font-bold text-blue-600">Most Popular</div>
                </div>
            </div>

            <!-- Google Pay Card -->
            <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-red-100">
                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-500 to-yellow-500 rounded-xl mb-6 mx-auto">
                    <i class="fab fa-google-pay text-white text-2xl"></i>
                </div>
                <h3 class="text-2xl font-heading font-bold text-center mb-4">Google Pay</h3>
                <ul class="space-y-3 text-gray-600 mb-6">
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        One-Click Payment
                    </li>
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        Global Acceptance
                    </li>
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        Secure Tokenization
                    </li>
                    <li class="flex items-center">
                        <i class="fa-solid fa-check text-green-500 mr-3"></i>
                        Multi-Currency Support
                    </li>
                </ul>
                <div class="text-center">
                    <div class="text-sm text-gray-500 mb-2">International users</div>
                    <div class="text-2xl font-bold text-red-600">Global Reach</div>
                </div>
            </div>
        </div>

        <?php if ($currentUser): ?>
        <!-- Demo Actions -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-heading font-bold text-center mb-8">Try the Payment System</h2>
            
            <!-- Wallet Top-up Demo -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fa-solid fa-wallet text-purple-500 mr-3"></i>
                    Wallet Top-up Demo
                </h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <button onclick="demoWalletTopup(10, 'touchngo')" class="bg-blue-500 text-white p-4 rounded-xl hover:bg-blue-600 transition">
                        <div class="text-lg font-bold">RM 10</div>
                        <div class="text-sm opacity-90">via Touch 'n Go</div>
                    </button>
                    <button onclick="demoWalletTopup(50, 'googlepay')" class="bg-red-500 text-white p-4 rounded-xl hover:bg-red-600 transition">
                        <div class="text-lg font-bold">RM 50</div>
                        <div class="text-sm opacity-90">via Google Pay</div>
                    </button>
                    <button onclick="demoWalletTopup(100, 'touchngo')" class="bg-blue-500 text-white p-4 rounded-xl hover:bg-blue-600 transition">
                        <div class="text-lg font-bold">RM 100</div>
                        <div class="text-sm opacity-90">via Touch 'n Go</div>
                    </button>
                    <button onclick="openWalletTopupModal()" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-4 rounded-xl hover:from-purple-600 hover:to-pink-600 transition">
                        <div class="text-lg font-bold">Custom</div>
                        <div class="text-sm opacity-90">Choose Amount</div>
                    </button>
                </div>
            </div>

            <!-- Ticket Purchase Demo -->
            <div class="mb-8">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fa-solid fa-ticket text-green-500 mr-3"></i>
                    Ticket Purchase Demo
                </h3>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button onclick="demoTicketPurchase(5, 1, 'touchngo')" class="bg-green-500 text-white p-4 rounded-xl hover:bg-green-600 transition">
                        <div class="text-lg font-bold">1 Ticket</div>
                        <div class="text-sm opacity-90">RM 5.00 via Touch 'n Go</div>
                    </button>
                    <button onclick="demoTicketPurchase(25, 5, 'googlepay')" class="bg-yellow-500 text-white p-4 rounded-xl hover:bg-yellow-600 transition">
                        <div class="text-lg font-bold">5 Tickets</div>
                        <div class="text-sm opacity-90">RM 25.00 via Google Pay</div>
                    </button>
                    <button onclick="demoTicketPurchase(50, 10, 'wallet')" class="bg-purple-500 text-white p-4 rounded-xl hover:bg-purple-600 transition">
                        <div class="text-lg font-bold">10 Tickets</div>
                        <div class="text-sm opacity-90">RM 50.00 via Wallet</div>
                    </button>
                </div>
            </div>

            <!-- Payment History -->
            <div>
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fa-solid fa-history text-blue-500 mr-3"></i>
                    Payment History
                </h3>
                <div class="space-y-2">
                    <button onclick="loadPaymentHistory()" class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg hover:bg-blue-200 transition">
                        <i class="fa-solid fa-refresh mr-2"></i>
                        Load Payment History
                    </button>
                    <div id="paymentHistory" class="mt-4"></div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Login Required -->
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-ps-blue/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-user-lock text-ps-blue text-2xl"></i>
            </div>
            <h2 class="text-2xl font-heading font-bold text-gray-900 mb-4">Login Required</h2>
            <p class="text-gray-600 mb-6">Please login to test the payment system functionality</p>
            <div class="space-x-4">
                <button onclick="openLoginModal()" class="bg-ps-blue text-white px-6 py-3 rounded-xl hover:bg-ps-light transition">
                    Login
                </button>
                <button onclick="openRegisterModal()" class="border border-ps-blue text-ps-blue px-6 py-3 rounded-xl hover:bg-ps-blue hover:text-white transition">
                    Register
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Features Overview -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
            <h2 class="text-2xl font-heading font-bold text-center mb-8">Payment System Features</h2>
            
            <div class="grid md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-shield-check text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Secure Processing</h3>
                    <p class="text-gray-600">Bank-level encryption and secure tokenization for all transactions</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-bolt text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Lightning Fast</h3>
                    <p class="text-gray-600">Instant payment processing with real-time status updates</p>
                </div>
                
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-mobile-alt text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-2">Mobile Optimized</h3>
                    <p class="text-gray-600">Seamless mobile experience with native app integration</p>
                </div>
            </div>
        </div>

        <!-- Technical Details -->
        <div class="bg-gray-900 text-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-heading font-bold text-center mb-8">Technical Implementation</h2>
            
            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="text-xl font-semibold mb-4 text-blue-400">Backend Architecture</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>• PHP Payment Gateway Class</li>
                        <li>• MySQL Database with Transactions</li>
                        <li>• RESTful API Endpoints</li>
                        <li>• Webhook Processing</li>
                        <li>• Real-time Status Updates</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-semibold mb-4 text-green-400">Frontend Features</h3>
                    <ul class="space-y-2 text-gray-300">
                        <li>• Modern UI Components</li>
                        <li>• QR Code Generation</li>
                        <li>• Payment Status Polling</li>
                        <li>• Error Handling</li>
                        <li>• Mobile Responsive</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Authentication Modals -->
    <?php include 'inc/auth_modals.php'; ?>
    
    <!-- Payment Components -->
    <?php include 'inc/payment_components.php'; ?>

    <script>
        // Demo Functions
        function demoWalletTopup(amount, method) {
            openPaymentModal(amount, 'wallet_topup', { method: method });
        }
        
        function demoTicketPurchase(amount, quantity, method) {
            if (method === 'wallet') {
                // Simulate wallet payment
                processWalletPaymentDemo(amount, quantity);
            } else {
                openPaymentModal(amount, 'ticket_purchase', { 
                    raffle_id: 1, 
                    quantity: quantity, 
                    payment_method: method 
                });
            }
        }
        
        async function processWalletPaymentDemo(amount, quantity) {
            // Simulate wallet payment
            const walletBalance = <?= $currentUser ? $currentUser['wallet_balance'] : 0 ?>;
            
            if (walletBalance < amount) {
                alert(`Insufficient wallet balance. You have RM ${walletBalance.toFixed(2)} but need RM ${amount.toFixed(2)}`);
                return;
            }
            
            // Simulate successful payment
            setTimeout(() => {
                showPaymentSuccess({
                    id: 'DEMO_WALLET_' + Date.now(),
                    amount: amount,
                    method: 'wallet',
                    tickets: Array.from({length: quantity}, (_, i) => `DEMO_TKT_${i + 1}`),
                    loyalty_points: Math.floor(amount)
                });
            }, 1000);
        }
        
        async function loadPaymentHistory() {
            try {
                const response = await fetch('api/payment.php?action=payment_history&limit=10');
                const result = await response.json();
                
                const historyDiv = document.getElementById('paymentHistory');
                
                if (result.success && result.payments.length > 0) {
                    historyDiv.innerHTML = `
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-semibold mb-3">Recent Payments</h4>
                            <div class="space-y-2">
                                ${result.payments.map(payment => `
                                    <div class="flex justify-between items-center p-3 bg-white rounded-lg">
                                        <div>
                                            <div class="font-medium">${payment.description || 'Payment'}</div>
                                            <div class="text-sm text-gray-500">${payment.created_at}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-bold">RM ${payment.amount}</div>
                                            <div class="text-sm text-${payment.status === 'completed' ? 'green' : 'orange'}-600 capitalize">
                                                ${payment.status}
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    `;
                } else {
                    historyDiv.innerHTML = `
                        <div class="bg-gray-50 rounded-lg p-4 text-center text-gray-500">
                            No payment history found. Make a test payment to see it here!
                        </div>
                    `;
                }
            } catch (error) {
                document.getElementById('paymentHistory').innerHTML = `
                    <div class="bg-red-50 rounded-lg p-4 text-center text-red-600">
                        Error loading payment history: ${error.message}
                    </div>
                `;
            }
        }
        
        // Auto-load payment history on page load
        <?php if ($currentUser): ?>
        document.addEventListener('DOMContentLoaded', function() {
            loadPaymentHistory();
        });
        <?php endif; ?>
    </script>
</body>
</html> 