<?php
require_once 'inc/database.php';
require_once 'inc/user_auth.php';

// Initialize authentication
$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();

// Get raffle ID from URL
$raffle_id = $_GET['id'] ?? null;

if (!$raffle_id) {
    header('Location: index.php');
    exit;
}

// Get main raffle details
$stmt = $pdo->prepare("SELECT r.*, c.name as category_name FROM raffles r LEFT JOIN categories c ON r.category_id = c.id WHERE r.id = ?");
$stmt->execute([$raffle_id]);
$raffle = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$raffle) {
    header('Location: index.php');
    exit;
}

// Calculate statistics
$total_tickets = (int)$raffle['total_tickets'];
$sold_tickets = (int)$raffle['sold_tickets'];
$remaining_tickets = $total_tickets - $sold_tickets;
$completion_percentage = $total_tickets > 0 ? ($sold_tickets / $total_tickets) * 100 : 0;

// Get user's tickets for this raffle if logged in
$userTickets = 0;
if ($currentUser) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE raffle_id = ? AND user_id = ?");
    $stmt->execute([$raffle_id, $currentUser['id']]);
    $userTickets = $stmt->fetchColumn();
}

// Get recent ticket purchases
$stmt = $pdo->prepare("SELECT t.ticket_number, t.created_at, u.name as user_name FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.raffle_id = ? ORDER BY t.created_at DESC LIMIT 5");
$stmt->execute([$raffle_id]);
$recentTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($raffle['title']) ?> - RaffLah!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-heading { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <a href="index.php" class="font-heading text-2xl font-bold text-blue-600">RaffLah!</a>
                <div class="flex items-center space-x-4">
                    <?php if ($currentUser): ?>
                        <span class="text-gray-700">Hi, <?= htmlspecialchars($currentUser['name']) ?>!</span>
                        <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Dashboard</a>
                    <?php else: ?>
                        <button onclick="openLoginModal()" class="text-blue-600 hover:text-blue-800">Login</button>
                        <button onclick="openRegisterModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Register</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Left Column - Product Info -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <div class="text-center mb-6">
                    <img src="images/<?= htmlspecialchars($raffle['image']) ?>" 
                         alt="<?= htmlspecialchars($raffle['title']) ?>" 
                         class="w-64 h-64 object-contain mx-auto rounded-lg bg-gray-50">
                </div>
                
                <h1 class="font-heading text-3xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($raffle['title']) ?></h1>
                
                <div class="flex items-center gap-4 mb-6">
                    <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold">
                        <?= htmlspecialchars($raffle['category_name'] ?? 'Electronics') ?>
                    </span>
                    <span class="text-gray-500">Draw Date: <?= date('M j, Y', strtotime($raffle['end_date'])) ?></span>
                </div>

                <!-- Progress Bar -->
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span><?= $sold_tickets ?> tickets sold</span>
                        <span><?= $remaining_tickets ?> remaining</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: <?= $completion_percentage ?>%"></div>
                    </div>
                    <div class="text-center mt-2 text-sm font-semibold text-blue-600"><?= number_format($completion_percentage, 1) ?>% Complete</div>
                </div>

                <!-- Description -->
                <div class="mb-6">
                    <h3 class="font-semibold text-lg mb-2">Description</h3>
                    <p class="text-gray-700"><?= htmlspecialchars($raffle['description'] ?? 'Amazing prize waiting for a lucky winner!') ?></p>
                </div>

                <!-- Recent Activity -->
                <div>
                    <h3 class="font-semibold text-lg mb-3">Recent Entries</h3>
                    <?php if (empty($recentTickets)): ?>
                        <p class="text-gray-500 text-sm">No entries yet. Be the first!</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($recentTickets as $ticket): ?>
                                <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fa-solid fa-ticket text-blue-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="font-medium text-sm"><?= substr($ticket['user_name'], 0, 1) . str_repeat('*', strlen($ticket['user_name']) - 2) . substr($ticket['user_name'], -1) ?></div>
                                            <div class="text-xs text-gray-500">Ticket #<?= $ticket['ticket_number'] ?></div>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-500"><?= date('M j, g:i A', strtotime($ticket['created_at'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Column - Enhanced Purchase Interface -->
            <?php 
            // Include enhanced purchase UI
            require_once 'inc/enhanced_purchase_ui.php';
            
            // Update raffle data to use RM1 per ticket
            $raffle['ticket_price'] = 1.00;
            $raffle['price'] = 1.00;
            $raffle['prize_value'] = $raffle['prize_value'] ?? 1000;
            
            renderEnhancedPurchaseUI($raffle, $currentUser, $userTickets);
            ?>
            
            <!-- Legacy Purchase Interface (Hidden) -->
            <div class="hidden bg-white rounded-xl shadow-lg p-8">
                <div class="text-center mb-6">
                    <div class="text-4xl font-bold text-blue-600 mb-2">RM 1.00</div>
                    <div class="text-gray-600">Per Ticket</div>
                </div>

                <?php if ($raffle['status'] !== 'active' || $remaining_tickets <= 0): ?>
                    <div class="text-center p-6 bg-gray-100 rounded-lg">
                        <i class="fa-solid fa-lock text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600 font-semibold">
                            <?= $remaining_tickets <= 0 ? 'Sold Out' : 'Raffle Closed' ?>
                        </p>
                    </div>
                <?php elseif (!$currentUser): ?>
                    <!-- Not logged in -->
                    <div class="text-center p-6 bg-blue-50 rounded-lg border border-blue-200">
                        <i class="fa-solid fa-user-lock text-4xl text-blue-500 mb-4"></i>
                        <p class="text-blue-800 font-semibold mb-4">Login required to enter raffle</p>
                        <div class="space-y-3">
                            <button onclick="openLoginModal()" 
                               class="block w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition">
                                Login
                            </button>
                            <button onclick="openRegisterModal()" 
                               class="block w-full bg-gray-100 text-blue-600 py-3 px-6 rounded-lg font-semibold hover:bg-gray-200 transition">
                                Create Account
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Purchase Form -->
                    <form id="purchaseForm" class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Number of Tickets</label>
                            <div class="flex items-center border border-gray-300 rounded-lg">
                                <button type="button" id="decreaseBtn" class="px-4 py-3 text-gray-600 hover:text-blue-600">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <input type="number" id="quantity" value="1" min="1" max="<?= min($remaining_tickets, 100) ?>" 
                                       class="flex-1 text-center py-3 border-0 outline-none font-semibold text-lg">
                                <button type="button" id="increaseBtn" class="px-4 py-3 text-gray-600 hover:text-blue-600">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">Max: <?= min($remaining_tickets, 100) ?> tickets</div>
                        </div>

                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-600">Subtotal:</span>
                                <span id="subtotal" class="font-semibold">RM <?= number_format($raffle['price'], 2) ?></span>
                            </div>
                            <div class="flex justify-between items-center text-lg font-bold">
                                <span>Total:</span>
                                <span id="total" class="text-blue-600">RM <?= number_format($raffle['price'], 2) ?></span>
                            </div>
                        </div>

                        <?php if ($userTickets > 0): ?>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center gap-2 text-green-800">
                                    <i class="fa-solid fa-ticket"></i>
                                    <span class="font-semibold">You have <?= $userTickets ?> ticket<?= $userTickets > 1 ? 's' : '' ?> in this raffle</span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Options -->
                        <div class="space-y-3">
                            <!-- Touch 'n Go Payment -->
                            <button type="button" onclick="purchaseWithPayment('touchngo')" 
                                    class="w-full bg-gradient-to-r from-blue-500 to-cyan-500 text-white py-4 px-6 rounded-lg font-heading font-bold text-lg hover:from-blue-600 hover:to-cyan-600 transition shadow-lg flex items-center justify-center space-x-3">
                                <i class="fa-solid fa-mobile-alt"></i>
                                <span>Pay with Touch 'n Go</span>
                            </button>
                            
                            <!-- Google Pay -->
                            <button type="button" onclick="purchaseWithPayment('googlepay')" 
                                    class="w-full bg-gradient-to-r from-red-500 to-yellow-500 text-white py-4 px-6 rounded-lg font-heading font-bold text-lg hover:from-red-600 hover:to-yellow-600 transition shadow-lg flex items-center justify-center space-x-3">
                                <i class="fab fa-google-pay"></i>
                                <span>Pay with Google Pay</span>
                            </button>
                            
                            <!-- Wallet Payment (if sufficient balance) -->
                            <?php if ($currentUser && $currentUser['wallet_balance'] >= $raffle['ticket_price']): ?>
                            <button type="button" onclick="purchaseWithWallet()" 
                                    class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-4 px-6 rounded-lg font-heading font-bold text-lg hover:from-purple-600 hover:to-pink-600 transition shadow-lg flex items-center justify-center space-x-3">
                                <i class="fa-solid fa-wallet"></i>
                                <span>Pay from Wallet (RM <?= number_format($currentUser['wallet_balance'], 2) ?>)</span>
                            </button>
                            <?php endif; ?>
                            
                            <!-- Wallet Top-up Option -->
                            <?php if ($currentUser && $currentUser['wallet_balance'] < $raffle['ticket_price']): ?>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center space-x-2 text-yellow-800 mb-2">
                                    <i class="fa-solid fa-info-circle"></i>
                                    <span class="font-semibold">Insufficient wallet balance</span>
                                </div>
                                <div class="text-sm text-yellow-700 mb-3">
                                    Current balance: RM <?= number_format($currentUser['wallet_balance'], 2) ?><br>
                                    Required: RM <?= number_format($raffle['ticket_price'], 2) ?>
                                </div>
                                <button type="button" onclick="openWalletTopupModal()" 
                                        class="w-full bg-yellow-500 text-white py-2 px-4 rounded-lg font-semibold hover:bg-yellow-600 transition">
                                    <i class="fa-solid fa-plus mr-2"></i>
                                    Top Up Wallet
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="text-xs text-gray-500 text-center mt-4">
                            <i class="fa-solid fa-shield-alt mr-1"></i>
                            All payments are secured with bank-level encryption
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const pricePerTicket = <?= $raffle['price'] ?>;
        const maxTickets = <?= min($remaining_tickets, 100) ?>;
        
        function updatePricing() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const subtotal = quantity * pricePerTicket;
            
            document.getElementById('subtotal').textContent = 'RM ' + subtotal.toFixed(2);
            document.getElementById('total').textContent = 'RM ' + subtotal.toFixed(2);
        }

        // Quantity controls
        document.getElementById('decreaseBtn').addEventListener('click', function() {
            const input = document.getElementById('quantity');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
                updatePricing();
            }
        });

        document.getElementById('increaseBtn').addEventListener('click', function() {
            const input = document.getElementById('quantity');
            if (input.value < maxTickets) {
                input.value = parseInt(input.value) + 1;
                updatePricing();
            }
        });

        document.getElementById('quantity').addEventListener('input', updatePricing);

        <?php if ($currentUser): ?>
        // Payment Functions
        function purchaseWithPayment(paymentMethod) {
            const quantity = parseInt(document.getElementById('quantity').value);
            const totalAmount = quantity * pricePerTicket;
            
            openPaymentModal(totalAmount, 'ticket_purchase', {
                raffle_id: <?= $raffle_id ?>,
                quantity: quantity,
                payment_method: paymentMethod
            });
        }
        
        function purchaseWithWallet() {
            const quantity = parseInt(document.getElementById('quantity').value);
            
            // Call wallet payment directly
            processWalletPaymentDirect(<?= $raffle_id ?>, quantity);
        }
        
        async function processWalletPaymentDirect(raffleId, quantity) {
            try {
                const formData = new FormData();
                formData.append('action', 'wallet_payment');
                formData.append('raffle_id', raffleId);
                formData.append('quantity', quantity);
                
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
        <?php endif; ?>
    </script>

    <!-- Authentication Modals -->
    <?php include 'inc/auth_modals.php'; ?>
    
    <!-- Payment Components -->
    <?php include 'inc/payment_components.php'; ?>

</body>
</html> 