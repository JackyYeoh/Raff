<?php
require_once 'purchase_strategies.php';

class EnhancedPurchaseUI {
    
    public function renderPurchaseModals() {
        return ''; // Placeholder for now
    }
    
    public function renderAchievementSystem($userId) {
        return ''; // Placeholder for now
    }
}

function renderEnhancedPurchaseUI($raffle, $currentUser = null, $userTickets = 0) {
    $strategies = PurchaseStrategies::getRecommendedQuantities($raffle, $userTickets);
    $motivators = PurchaseStrategies::getPurchaseMotivators($raffle, $userTickets);
    $socialProof = PurchaseStrategies::getSocialProof($raffle);
    
    $remaining = $raffle['total_tickets'] - $raffle['sold_tickets'];
    $completionRate = ($raffle['sold_tickets'] / $raffle['total_tickets']) * 100;
?>

<!-- Enhanced Purchase Interface -->
<div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <!-- Header with Prize and Urgency -->
    <div class="bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 p-6 text-white relative overflow-hidden">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative z-10">
            <!-- Prize Value -->
            <div class="text-center mb-4">
                <div class="text-4xl font-bold mb-2">🏆 <?= htmlspecialchars($raffle['title']) ?></div>
                <div class="text-2xl font-semibold opacity-90">Worth RM <?= number_format($raffle['prize_value'] ?? 1000) ?></div>
            </div>
            
            <!-- Progress Bar -->
            <div class="mb-4">
                <div class="flex justify-between text-sm mb-2">
                    <span><?= number_format($raffle['sold_tickets']) ?> sold</span>
                    <span><?= number_format($remaining) ?> left</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-3">
                    <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-3 rounded-full transition-all duration-500" 
                         style="width: <?= $completionRate ?>%"></div>
                </div>
                <div class="text-center mt-2 text-sm opacity-90">
                    <?= number_format($completionRate, 1) ?>% Complete
                </div>
            </div>
            
            <!-- Motivators -->
            <div class="flex flex-wrap gap-2 justify-center">
                <?php foreach (array_slice($motivators, 0, 2) as $motivator): ?>
                <div class="bg-white/20 backdrop-blur-sm rounded-full px-3 py-1 text-sm flex items-center space-x-1">
                    <i class="fa-solid <?= $motivator['icon'] ?>"></i>
                    <span><?= $motivator['message'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Purchase Strategies -->
    <div class="p-6">
        <!-- Quick Entry Options -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <?= $strategies['quick_entry']['title'] ?>
                <span class="ml-2 text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Most Popular</span>
            </h3>
            <div class="grid grid-cols-3 gap-3">
                <?php foreach ($strategies['quick_entry']['options'] as $option): ?>
                <button onclick="selectQuantity(<?= $option['qty'] ?>)" 
                        class="purchase-option-btn border-2 border-gray-200 rounded-xl p-4 text-center hover:border-blue-500 hover:bg-blue-50 transition-all group">
                    <div class="text-2xl font-bold text-gray-900 group-hover:text-blue-600">
                        <?= $option['qty'] ?>
                    </div>
                    <div class="text-sm font-semibold text-gray-700 group-hover:text-blue-600">
                        <?= $option['label'] ?>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        RM <?= $option['price'] ?>
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        <?= $option['desc'] ?>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Value Bundles -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <?= $strategies['value_bundles']['title'] ?>
                <span class="ml-2 text-sm bg-green-100 text-green-800 px-2 py-1 rounded-full">Better Odds</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($strategies['value_bundles']['options'] as $option): ?>
                <button onclick="selectQuantity(<?= $option['qty'] ?>)" 
                        class="purchase-option-btn border-2 border-gray-200 rounded-xl p-4 text-center hover:border-green-500 hover:bg-green-50 transition-all group relative">
                    <?php if (isset($option['badge'])): ?>
                    <div class="absolute -top-2 -right-2 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                        <?= $option['badge'] ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="text-3xl font-bold text-gray-900 group-hover:text-green-600">
                        <?= $option['qty'] ?>
                    </div>
                    <div class="text-lg font-semibold text-gray-700 group-hover:text-green-600">
                        <?= $option['label'] ?>
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        RM <?= $option['price'] ?>
                    </div>
                    <div class="text-xs text-gray-400 mt-2">
                        <?= $option['desc'] ?>
                    </div>
                    
                    <!-- Show bonus entries -->
                    <?php if ($option['qty'] >= 10): ?>
                    <div class="mt-2 text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">
                        +<?= floor($option['qty'] / 10) ?> Bonus Entries!
                    </div>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Custom Amount -->
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">🎯 Custom Amount</h3>
            <div class="flex items-center space-x-4">
                <div class="flex-1">
                    <div class="flex items-center border-2 border-gray-300 rounded-xl overflow-hidden">
                        <button type="button" onclick="decreaseQuantity()" 
                                class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition">
                            <i class="fa-solid fa-minus"></i>
                        </button>
                        <input type="number" id="customQuantity" value="1" min="1" max="<?= min($remaining, 100) ?>" 
                               onchange="updateCustomPurchase()"
                               class="flex-1 text-center py-3 border-0 outline-none font-bold text-xl">
                        <button type="button" onclick="increaseQuantity()" 
                                class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-gray-800 transition">
                            <i class="fa-solid fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-blue-600" id="customTotal">RM 1</div>
                    <div class="text-sm text-gray-500">Total</div>
                </div>
            </div>
            
            <!-- Dynamic Feedback -->
            <div id="purchaseFeedback" class="mt-4 p-3 bg-gray-50 rounded-lg">
                <div class="text-sm text-gray-600">
                    <span id="oddsCalculation">Your odds: 0.1%</span> • 
                    <span id="loyaltyPoints">+1 loyalty point</span>
                </div>
            </div>
        </div>
        
        <!-- Social Proof -->
        <div class="mb-6 bg-blue-50 rounded-xl p-4">
            <div class="flex items-center space-x-2 text-blue-800 mb-2">
                <i class="fa-solid fa-users"></i>
                <span class="font-semibold">Live Activity</span>
            </div>
            <div class="text-sm text-blue-700">
                🔥 <?= $socialProof['recent_purchases']['count'] ?> <?= $socialProof['recent_purchases']['message'] ?>
            </div>
            <div class="text-sm text-blue-600 mt-1">
                💡 <?= $socialProof['popular_quantities']['message'] ?>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <?php if ($currentUser): ?>
        <div class="space-y-3">
            <!-- Touch 'n Go -->
            <button onclick="purchaseWithSelectedQuantity('touchngo')" 
                    class="w-full bg-gradient-to-r from-blue-500 to-cyan-500 text-white py-4 px-6 rounded-xl font-bold text-lg hover:from-blue-600 hover:to-cyan-600 transition shadow-lg flex items-center justify-center space-x-3">
                <i class="fa-solid fa-mobile-alt text-xl"></i>
                <span>Pay with Touch 'n Go</span>
                <div class="ml-auto text-sm opacity-90">Instant</div>
            </button>
            
            <!-- Google Pay -->
            <button onclick="purchaseWithSelectedQuantity('googlepay')" 
                    class="w-full bg-gradient-to-r from-red-500 to-yellow-500 text-white py-4 px-6 rounded-xl font-bold text-lg hover:from-red-600 hover:to-yellow-600 transition shadow-lg flex items-center justify-center space-x-3">
                <i class="fab fa-google-pay text-xl"></i>
                <span>Pay with Google Pay</span>
                <div class="ml-auto text-sm opacity-90">Global</div>
            </button>
            
            <!-- Wallet Payment -->
            <?php if ($currentUser['wallet_balance'] >= 1): ?>
            <button onclick="purchaseWithSelectedQuantity('wallet')" 
                    class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-4 px-6 rounded-xl font-bold text-lg hover:from-purple-600 hover:to-pink-600 transition shadow-lg flex items-center justify-center space-x-3">
                <i class="fa-solid fa-wallet text-xl"></i>
                <span>Pay from Wallet</span>
                <div class="ml-auto text-sm opacity-90">
                    RM <?= number_format($currentUser['wallet_balance'], 2) ?>
                </div>
            </button>
            <?php else: ?>
            <button onclick="openWalletTopupModal()" 
                    class="w-full border-2 border-purple-300 text-purple-600 py-4 px-6 rounded-xl font-bold text-lg hover:bg-purple-50 transition flex items-center justify-center space-x-3">
                <i class="fa-solid fa-plus text-xl"></i>
                <span>Top Up Wallet First</span>
            </button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <!-- Login Required -->
        <div class="text-center p-6 bg-gray-100 rounded-xl">
            <i class="fa-solid fa-user-lock text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-700 mb-2">Login to Purchase</h3>
            <p class="text-gray-600 mb-4">Join thousands of players and start winning!</p>
            <div class="space-x-3">
                <button onclick="openLoginModal()" class="bg-blue-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-blue-700 transition">
                    Login
                </button>
                <button onclick="openRegisterModal()" class="border border-blue-600 text-blue-600 px-6 py-3 rounded-xl font-bold hover:bg-blue-600 hover:text-white transition">
                    Register
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Trust Badges -->
        <div class="mt-6 flex justify-center space-x-6 text-xs text-gray-500">
            <div class="flex items-center space-x-1">
                <i class="fa-solid fa-shield-check text-green-500"></i>
                <span>Secure Payment</span>
            </div>
            <div class="flex items-center space-x-1">
                <i class="fa-solid fa-bolt text-yellow-500"></i>
                <span>Instant Processing</span>
            </div>
            <div class="flex items-center space-x-1">
                <i class="fa-solid fa-trophy text-blue-500"></i>
                <span>Fair Draw</span>
            </div>
        </div>
    </div>
</div>

<!-- Floating Achievement Notifications -->
<div id="achievementNotifications" class="fixed top-4 right-4 z-50 space-y-2">
    <!-- Notifications will be dynamically added here -->
</div>

<style>
.purchase-option-btn.selected {
    border-color: #3b82f6;
    background-color: #eff6ff;
    transform: scale(1.02);
}

.purchase-option-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.achievement-notification {
    animation: slideInRight 0.5s ease-out;
}
</style>

<script>
let selectedQuantity = 1;
const maxTickets = <?= min($remaining, 100) ?>;
const totalTickets = <?= $raffle['total_tickets'] ?>;

function selectQuantity(qty) {
    selectedQuantity = qty;
    document.getElementById('customQuantity').value = qty;
    updateCustomPurchase();
    
    // Update visual selection
    document.querySelectorAll('.purchase-option-btn').forEach(btn => {
        btn.classList.remove('selected');
    });
    event.target.closest('.purchase-option-btn').classList.add('selected');
    
    // Show achievement if applicable
    showAchievementNotification(qty);
}

function increaseQuantity() {
    const input = document.getElementById('customQuantity');
    if (parseInt(input.value) < maxTickets) {
        input.value = parseInt(input.value) + 1;
        updateCustomPurchase();
    }
}

function decreaseQuantity() {
    const input = document.getElementById('customQuantity');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateCustomPurchase();
    }
}

function updateCustomPurchase() {
    const quantity = parseInt(document.getElementById('customQuantity').value);
    selectedQuantity = quantity;
    
    // Update total
    document.getElementById('customTotal').textContent = `RM ${quantity}`;
    
    // Update odds calculation
    const odds = ((quantity / totalTickets) * 100).toFixed(3);
    document.getElementById('oddsCalculation').textContent = `Your odds: ${odds}%`;
    
    // Update loyalty points
    let loyaltyPoints = quantity;
    if (quantity >= 20) loyaltyPoints = Math.floor(quantity * 1.5); // 1.5x multiplier
    document.getElementById('loyaltyPoints').textContent = `+${loyaltyPoints} loyalty points`;
    
    // Update feedback with bonuses
    updatePurchaseFeedback(quantity);
}

function updatePurchaseFeedback(quantity) {
    const feedback = document.getElementById('purchaseFeedback');
    let feedbackHTML = `
        <div class="text-sm text-gray-600">
            <span id="oddsCalculation">Your odds: ${((quantity / totalTickets) * 100).toFixed(3)}%</span> • 
            <span id="loyaltyPoints">+${quantity >= 20 ? Math.floor(quantity * 1.5) : quantity} loyalty points</span>
    `;
    
    // Add bonus entries
    if (quantity >= 10) {
        const bonusEntries = Math.floor(quantity / 10);
        feedbackHTML += ` • <span class="text-yellow-600 font-semibold">+${bonusEntries} bonus entries!</span>`;
    }
    
    // Add milestone achievements
    const milestones = {
        5: '🌟 Starter Badge',
        10: '🎯 Committed Badge', 
        20: '🎲 High Roller Badge',
        50: '🏆 Champion Badge',
        100: '👑 Legend Badge'
    };
    
    for (const [threshold, badge] of Object.entries(milestones)) {
        if (quantity >= threshold) {
            feedbackHTML += ` • <span class="text-purple-600 font-semibold">${badge}</span>`;
        }
    }
    
    feedbackHTML += '</div>';
    feedback.innerHTML = feedbackHTML;
    
    // Change background color based on quantity
    if (quantity >= 20) {
        feedback.className = 'mt-4 p-3 bg-purple-50 rounded-lg border border-purple-200';
    } else if (quantity >= 10) {
        feedback.className = 'mt-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200';
    } else if (quantity >= 5) {
        feedback.className = 'mt-4 p-3 bg-green-50 rounded-lg border border-green-200';
    } else {
        feedback.className = 'mt-4 p-3 bg-gray-50 rounded-lg';
    }
}

function purchaseWithSelectedQuantity(paymentMethod) {
    const quantity = selectedQuantity;
    const totalAmount = quantity; // RM1 per ticket
    
    if (paymentMethod === 'wallet') {
        processWalletPaymentDirect(<?= $raffle['id'] ?>, quantity);
    } else {
        openPaymentModal(totalAmount, 'ticket_purchase', {
            raffle_id: <?= $raffle['id'] ?>,
            quantity: quantity,
            payment_method: paymentMethod
        });
    }
}

function showAchievementNotification(quantity) {
    const milestones = {
        5: { badge: 'Starter', emoji: '🌟', message: 'You\'re getting serious!' },
        10: { badge: 'Committed', emoji: '🎯', message: 'Perfect 10 achievement!' },
        20: { badge: 'High Roller', emoji: '🎲', message: 'Big player status!' },
        50: { badge: 'Champion', emoji: '🏆', message: 'Champion level entry!' },
        100: { badge: 'Legend', emoji: '👑', message: 'Legendary commitment!' }
    };
    
    if (milestones[quantity]) {
        const achievement = milestones[quantity];
        const notification = document.createElement('div');
        notification.className = 'achievement-notification bg-gradient-to-r from-purple-500 to-pink-500 text-white p-4 rounded-xl shadow-lg max-w-sm';
        notification.innerHTML = `
            <div class="flex items-center space-x-3">
                <div class="text-2xl">${achievement.emoji}</div>
                <div>
                    <div class="font-bold">${achievement.badge} Unlocked!</div>
                    <div class="text-sm opacity-90">${achievement.message}</div>
                </div>
            </div>
        `;
        
        document.getElementById('achievementNotifications').appendChild(notification);
        
        // Remove after 4 seconds
        setTimeout(() => {
            notification.remove();
        }, 4000);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCustomPurchase();
});
</script>

<?php
}
?> 