<?php
require_once 'inc/database.php';
require_once 'inc/user_auth.php';
require_once 'inc/loyalty_system.php';

// Get current user from session
$currentUser = getCurrentUser();

// For demo purposes if no logged in user
$userId = $currentUser ? $currentUser['id'] : ($_GET['user_id'] ?? 1);
$loyaltySystem = new LoyaltySystem();
$userData = $loyaltySystem->getUserLoyaltyData($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Store - RaffLah!</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        ps: {
                            blue: '#007aff',
                            light: '#5ac8fa', 
                            yellow: '#ffcc00',
                            pink: '#ff2d55',
                            silver: '#8a99b5',
                            bg: '#f2f2f2',
                            text: '#2c3e50',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #2c3e50; }
        h1, h2, h3, .font-heading { font-family: 'Poppins', 'Inter', ui-sans-serif, system-ui, sans-serif; }
        
        /* PlayStation Shadow System */
        .shadow-ps { box-shadow: 0 4px 12px rgba(0, 122, 255, 0.15); }
        .shadow-ps-lg { box-shadow: 0 8px 25px rgba(0, 122, 255, 0.2); }
        .shadow-ps-hover { box-shadow: 0 10px 30px rgba(0, 122, 255, 0.25); }
        .shadow-ps-yellow-hover { box-shadow: 0 8px 20px rgba(255, 204, 0, 0.3); }
        
        .store-item {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .store-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 122, 255, 0.15);
        }
        
        .store-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .store-item:hover::before {
            left: 100%;
        }
        
        .points-badge {
            background: linear-gradient(135deg, #ff2d55, #ffcc00);
            color: white;
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
        }
        
        .vip-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .vip-bronze { background: #CD7F32; color: white; }
        .vip-silver { background: #C0C0C0; color: white; }
        .vip-gold { background: #FFD700; color: black; }
        .vip-diamond { background: #B9F2FF; color: black; }
        
        .purchase-animation {
            animation: purchaseSuccess 0.8s ease-out;
        }
        
        @keyframes purchaseSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); background-color: #10B981; }
            100% { transform: scale(1); }
        }
        
        .shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body class="bg-ps-bg min-h-screen flex flex-col">
    <!-- Sticky Navbar -->
    <nav class="sticky top-0 z-20 bg-white/80 backdrop-blur shadow-sm border-b border-ps-silver font-sans">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-4 md:px-8 py-3 md:py-4">
            <!-- Logo -->
            <a href="index.php" class="font-heading text-ps-blue text-2xl font-bold tracking-tight">RaffLah!</a>
            
            <!-- Centered Search Bar -->
            <div class="flex items-center bg-white rounded-full shadow-inner px-4 py-2 gap-2 w-full max-w-md mx-auto">
                <i class="fa-solid fa-magnifying-glass text-ps-silver"></i>
                <input class="flex-1 bg-transparent text-sm outline-none" placeholder="Search raffles & prizes"/>
                <button class="relative">
                    <i class="fa-solid fa-ticket text-ps-blue"></i>
                    <span class="absolute -top-1 -right-1 text-[10px] bg-ps-yellow text-ps-text rounded-full px-1"></span>
                </button>
            </div>
            
            <!-- Right Side Icons & User -->
            <div class="flex items-center gap-4 ml-2">

                <a href="loyalty-store.php" class="relative text-ps-blue hover:text-ps-light transition" title="Loyalty Store">
                    <i class="fa-solid fa-store text-2xl"></i>
                </a>
                <button class="relative text-ps-blue hover:text-ps-light transition">
                    <i class="fa-regular fa-bell text-2xl"></i>
                    <span class="absolute -top-1 -right-1 bg-ps-blue text-white text-xs rounded-full px-1.5 py-0.5 font-bold border-2 border-white">3</span>
                </button>
                <button class="text-ps-blue hover:text-ps-light transition">
                    <i class="fa-solid fa-globe text-2xl"></i>
                </button>
                
                <?php if ($currentUser): ?>
                    <!-- Logged in user -->
                    <div class="relative">
                        <button id="userDropdown" class="flex items-center gap-2 group">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User" class="w-8 h-8 rounded-full border-2 border-ps-blue group-hover:border-ps-light transition" />
                            <span class="hidden md:inline text-ps-text font-semibold"><?= htmlspecialchars($currentUser['name']) ?></span>
                            <i class="fa-solid fa-chevron-down text-ps-silver group-hover:text-ps-blue transition"></i>
                        </button>
                        <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border">
                            <a href="dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-gauge mr-2"></i>Dashboard
                            </a>
                            
                            <a href="loyalty-store.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                <i class="fa-solid fa-store mr-2"></i>Loyalty Store
                            </a>
                            <div class="border-t border-gray-200"></div>
                            <button onclick="logout()" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                <i class="fa-solid fa-sign-out-alt mr-2"></i>Logout
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Not logged in -->
                    <div class="flex items-center gap-2">
                        <button onclick="openLoginModal()" class="text-ps-blue hover:text-ps-light font-semibold">Sign In</button>
                        <span class="text-ps-silver">|</span>
                        <button onclick="openRegisterModal()" class="bg-ps-blue hover:bg-ps-light text-white px-4 py-2 rounded-full font-semibold transition">Register</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="w-full max-w-7xl mx-auto px-2 md:px-8 mt-10">
        <div class="relative rounded-2xl overflow-hidden shadow-ps bg-gradient-to-br from-ps-blue via-ps-light to-ps-blue p-8 md:p-12 flex flex-col md:flex-row items-center min-h-[260px] md:min-h-[320px]">
            <!-- Left: Text & CTA -->
            <div class="flex-1 z-10 flex flex-col justify-center items-start gap-4">
                <span class="inline-block bg-ps-yellow text-ps-text font-heading font-bold px-4 py-1 rounded-full text-xs mb-2 tracking-wide">LOYALTY STORE</span>
                <h1 class="text-white font-heading text-3xl md:text-5xl font-extrabold leading-tight mb-2 drop-shadow" style="line-height:1.15;">Redeem amazing rewards –<br class='hidden md:block'> spend your points today!</h1>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full">
                    <span class="flex items-center gap-2 bg-white/20 rounded-full px-5 py-2 text-white font-bold text-lg">
                        <i class="fa-solid fa-star text-ps-yellow"></i>
                        Points: <span class="font-mono text-ps-yellow"><?php echo number_format($userData['loyalty_points']); ?></span>
                    </span>
                    <a href="#storeItems" class="flex items-center gap-2 bg-ps-pink hover:bg-ps-yellow text-white hover:text-ps-text font-heading font-bold text-lg px-8 py-3 rounded-full shadow-ps transition focus:ring-2 focus:ring-ps-yellow">
                        <i class="fa-solid fa-shopping-cart text-ps-yellow"></i> Shop Now
                    </a>
                </div>
                <div class="mt-2 text-white/90 text-xs md:text-sm">VIP Tier: <span class="font-bold text-ps-yellow"><?php echo ucfirst($userData['vip_tier']); ?></span> • Wallet: <span class="font-bold text-ps-yellow">RM <?php echo number_format($userData['wallet_balance'], 2); ?></span></div>
            </div>
            <!-- Right: Store Visual -->
            <div class="flex-1 flex justify-end items-center relative h-40 md:h-64">
                <div class="relative bg-white/10 rounded-xl border-4 border-white/30 p-6 backdrop-blur-sm text-center">
                    <div class="text-6xl mb-4">🛍️</div>
                    <div class="text-white font-bold text-lg mb-2">Exclusive Rewards</div>
                    <div class="text-ps-yellow font-heading font-bold text-2xl">Available Now</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Store Categories -->
    <section class="max-w-7xl mx-auto px-4 md:px-8 py-12">
        <div class="flex flex-wrap gap-4 mb-12 justify-center">
            <button class="category-btn bg-white hover:bg-ps-blue hover:text-white px-6 py-3 rounded-full border border-ps-blue text-ps-blue font-semibold transition-all shadow-ps hover:shadow-ps-hover active" data-category="all">
                <i class="fa-solid fa-store mr-2"></i>All Items
            </button>
            <button class="category-btn bg-white hover:bg-ps-blue hover:text-white px-6 py-3 rounded-full border border-ps-blue text-ps-blue font-semibold transition-all shadow-ps hover:shadow-ps-hover" data-category="cash_reward">
                <i class="fa-solid fa-wallet mr-2"></i>Cash Rewards
            </button>
            <button class="category-btn bg-white hover:bg-ps-blue hover:text-white px-6 py-3 rounded-full border border-ps-blue text-ps-blue font-semibold transition-all shadow-ps hover:shadow-ps-hover" data-category="ticket_discount">
                <i class="fa-solid fa-percent mr-2"></i>Discounts
            </button>
            <button class="category-btn bg-white hover:bg-ps-blue hover:text-white px-6 py-3 rounded-full border border-ps-blue text-ps-blue font-semibold transition-all shadow-ps hover:shadow-ps-hover" data-category="free_ticket">
                <i class="fa-solid fa-ticket mr-2"></i>Free Tickets
            </button>
            <button class="category-btn bg-white hover:bg-ps-blue hover:text-white px-6 py-3 rounded-full border border-ps-blue text-ps-blue font-semibold transition-all shadow-ps hover:shadow-ps-hover" data-category="vip_upgrade">
                <i class="fa-solid fa-crown mr-2"></i>VIP Upgrades
            </button>
        </div>

        <!-- Store Items Grid -->
        <div id="storeItems" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
            <!-- Loading placeholders -->
            <div class="store-item bg-white rounded-2xl p-6 shadow-ps-lg">
                <div class="shimmer h-32 rounded-xl mb-4"></div>
                <div class="shimmer h-4 rounded mb-2"></div>
                <div class="shimmer h-3 rounded w-3/4 mb-4"></div>
                <div class="shimmer h-10 rounded"></div>
            </div>
            <div class="store-item bg-white rounded-2xl p-6 shadow-lg">
                <div class="shimmer h-32 rounded-xl mb-4"></div>
                <div class="shimmer h-4 rounded mb-2"></div>
                <div class="shimmer h-3 rounded w-3/4 mb-4"></div>
                <div class="shimmer h-10 rounded"></div>
            </div>
            <div class="store-item bg-white rounded-2xl p-6 shadow-lg">
                <div class="shimmer h-32 rounded-xl mb-4"></div>
                <div class="shimmer h-4 rounded mb-2"></div>
                <div class="shimmer h-3 rounded w-3/4 mb-4"></div>
                <div class="shimmer h-10 rounded"></div>
            </div>
            <div class="store-item bg-white rounded-2xl p-6 shadow-lg">
                <div class="shimmer h-32 rounded-xl mb-4"></div>
                <div class="shimmer h-4 rounded mb-2"></div>
                <div class="shimmer h-3 rounded w-3/4 mb-4"></div>
                <div class="shimmer h-10 rounded"></div>
            </div>
        </div>
    </section>

    <!-- Purchase History -->
    <section class="max-w-7xl mx-auto px-4 md:px-8 py-12">
        <div class="bg-white rounded-3xl shadow-lg p-8">
            <div class="flex items-center justify-between mb-8">
                <h2 class="font-heading text-3xl font-bold text-ps-text">
                    <i class="fa-solid fa-history text-ps-blue mr-3"></i>
                    Purchase History
                </h2>
                <button id="refreshHistory" class="text-ps-blue hover:text-ps-light transition-colors">
                    <i class="fa-solid fa-refresh"></i>
                </button>
            </div>
            
            <div id="purchaseHistory" class="space-y-4">
                <!-- Will be populated by JavaScript -->
                <div class="text-center text-ps-silver py-8">
                    <i class="fa-solid fa-spinner fa-spin text-2xl mb-4"></i>
                    <div>Loading purchase history...</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Purchase Confirmation Modal -->
    <div id="purchaseModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md mx-4">
            <div class="text-center">
                <div class="w-20 h-20 bg-ps-blue/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fa-solid fa-shopping-cart text-3xl text-ps-blue"></i>
                </div>
                <h3 class="font-heading text-2xl font-bold text-ps-text mb-4">Confirm Purchase</h3>
                <div id="purchaseDetails" class="mb-6">
                    <!-- Purchase details will be inserted here -->
                </div>
                <div class="flex gap-4">
                    <button onclick="closePurchaseModal()" class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button id="confirmPurchase" class="flex-1 bg-ps-blue text-white font-bold py-3 rounded-xl hover:bg-ps-light transition-colors">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-3xl p-8 max-w-md mx-4 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-check text-3xl text-green-600"></i>
            </div>
            <h3 class="font-heading text-2xl font-bold text-ps-text mb-4">Purchase Successful!</h3>
            <div id="successDetails" class="mb-6">
                <!-- Success details will be inserted here -->
            </div>
            <button onclick="closeSuccessModal()" class="bg-ps-blue text-white font-bold px-8 py-3 rounded-xl hover:bg-ps-light transition-colors">
                Awesome!
            </button>
        </div>
    </div>

    <script>
        let storeItems = [];
        let currentCategory = 'all';
        let currentPurchaseItem = null;

        // Load store items
        async function loadStoreItems() {
            try {
                const response = await fetch('api/loyalty_store.php?action=items&user_id=<?php echo $userId; ?>');
                const result = await response.json();
                
                if (result.success) {
                    storeItems = result.data.items;
                    renderStoreItems();
                }
            } catch (error) {
                console.error('Error loading store items:', error);
            }
        }

        // Render store items
        function renderStoreItems() {
            const container = document.getElementById('storeItems');
            const filteredItems = currentCategory === 'all' 
                ? storeItems 
                : storeItems.filter(item => item.item_type === currentCategory);
            
            if (filteredItems.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <i class="fa-solid fa-box-open text-4xl text-ps-silver mb-4"></i>
                        <div class="text-ps-silver">No items found in this category</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = filteredItems.map(item => {
                const canAfford = item.can_afford;
                const isOutOfStock = item.stock_status === 'out_of_stock';
                const isDisabled = !canAfford || isOutOfStock;
                
                return `
                    <div class="store-item bg-white rounded-2xl p-6 shadow-lg relative ${isDisabled ? 'opacity-60' : ''}">
                        <div class="vip-badge vip-${item.min_vip_tier}">${item.min_vip_tier}</div>
                        
                        <div class="h-32 bg-gradient-to-br from-ps-blue/10 to-ps-pink/10 rounded-xl mb-4 flex items-center justify-center">
                            <i class="fa-solid ${getItemIcon(item.item_type)} text-4xl text-ps-blue"></i>
                        </div>
                        
                        <h3 class="font-bold text-lg text-ps-text mb-2">${item.name}</h3>
                        <p class="text-ps-silver text-sm mb-4 h-10">${item.description}</p>
                        
                        <div class="flex items-center justify-between mb-4">
                            <div class="points-badge">
                                ${item.points_cost} points
                            </div>
                            ${item.stock_status === 'limited' ? `<div class="text-xs text-orange-600 font-semibold">Limited Stock</div>` : ''}
                        </div>
                        
                        <button 
                            onclick="purchaseItem(${item.id})" 
                            class="w-full py-3 rounded-xl font-bold transition-all ${
                                isDisabled 
                                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed' 
                                    : 'bg-gradient-to-r from-ps-blue to-ps-light text-white hover:shadow-lg'
                            }"
                            ${isDisabled ? 'disabled' : ''}
                        >
                            ${isOutOfStock ? 'Out of Stock' : !canAfford ? 'Not Enough Points' : 'Purchase'}
                        </button>
                    </div>
                `;
            }).join('');
        }

        // Get icon for item type
        function getItemIcon(type) {
            const icons = {
                'cash_reward': 'fa-wallet',
                'ticket_discount': 'fa-percent',
                'free_ticket': 'fa-ticket',
                'vip_upgrade': 'fa-crown',
                'exclusive_raffle': 'fa-star'
            };
            return icons[type] || 'fa-gift';
        }

        // Category filtering
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.category-btn').forEach(b => {
                    b.classList.remove('active', 'bg-ps-blue', 'text-white');
                    b.classList.add('bg-white', 'text-ps-blue');
                });
                
                this.classList.add('active', 'bg-ps-blue', 'text-white');
                this.classList.remove('bg-white', 'text-ps-blue');
                
                currentCategory = this.dataset.category;
                renderStoreItems();
            });
        });

        // Purchase item
        function purchaseItem(itemId) {
            const item = storeItems.find(i => i.id === itemId);
            if (!item) return;
            
            currentPurchaseItem = item;
            
            document.getElementById('purchaseDetails').innerHTML = `
                <div class="text-left">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-ps-silver">Item:</span>
                        <span class="font-bold">${item.name}</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-ps-silver">Cost:</span>
                        <span class="font-bold text-ps-pink">${item.points_cost} points</span>
                    </div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-ps-silver">After Purchase:</span>
                        <span class="font-bold text-ps-blue">${<?php echo $userData['loyalty_points']; ?> - item.points_cost} points</span>
                    </div>
                </div>
            `;
            
            document.getElementById('purchaseModal').classList.remove('hidden');
        }

        // Confirm purchase
        document.getElementById('confirmPurchase').addEventListener('click', async function() {
            if (!currentPurchaseItem) return;
            
            this.disabled = true;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'purchase');
                formData.append('item_id', currentPurchaseItem.id);
                formData.append('user_id', <?php echo $userId; ?>);
                
                const response = await fetch('api/loyalty_store.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    closePurchaseModal();
                    showSuccessModal(result.data);
                    updateUserPoints(result.data.remaining_points);
                    loadStoreItems(); // Refresh items
                    loadPurchaseHistory(); // Refresh history
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Network error. Please try again.');
            } finally {
                this.disabled = false;
                this.innerHTML = 'Confirm';
            }
        });

        // Show success modal
        function showSuccessModal(data) {
            document.getElementById('successDetails').innerHTML = `
                <div class="bg-green-50 rounded-xl p-4 mb-4">
                    <div class="text-lg font-bold text-green-800 mb-2">${data.item.name}</div>
                    <div class="text-green-600">${data.reward_applied.message}</div>
                </div>
                <div class="text-ps-silver">
                    Remaining Points: <span class="font-bold text-ps-blue">${data.remaining_points}</span>
                </div>
            `;
            
            document.getElementById('successModal').classList.remove('hidden');
        }

        // Update user points display
        function updateUserPoints(newPoints) {
            document.getElementById('userPoints').textContent = newPoints.toLocaleString();
        }

        // Load purchase history
        async function loadPurchaseHistory() {
            try {
                const response = await fetch('api/loyalty_store.php?action=history&user_id=<?php echo $userId; ?>');
                const result = await response.json();
                
                if (result.success) {
                    renderPurchaseHistory(result.data);
                }
            } catch (error) {
                console.error('Error loading purchase history:', error);
            }
        }

        // Render purchase history
        function renderPurchaseHistory(history) {
            const container = document.getElementById('purchaseHistory');
            
            if (history.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fa-solid fa-shopping-bag text-4xl text-ps-silver mb-4"></i>
                        <div class="text-ps-silver">No purchases yet</div>
                        <div class="text-sm text-ps-silver">Start shopping to see your history here</div>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = history.map(purchase => `
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-ps-blue/10 rounded-full flex items-center justify-center">
                            <i class="fa-solid ${getItemIcon(purchase.item_type)} text-ps-blue"></i>
                        </div>
                        <div>
                            <div class="font-bold text-ps-text">${purchase.item_name}</div>
                            <div class="text-sm text-ps-silver">${new Date(purchase.purchased_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-ps-pink">-${purchase.points_spent} points</div>
                        <div class="text-sm text-green-600">${purchase.status}</div>
                    </div>
                </div>
            `).join('');
        }

        // Modal controls
        function closePurchaseModal() {
            document.getElementById('purchaseModal').classList.add('hidden');
            currentPurchaseItem = null;
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        // Refresh history
        document.getElementById('refreshHistory').addEventListener('click', loadPurchaseHistory);

        // Initialize page
        loadStoreItems();
        loadPurchaseHistory();
        
        // Authentication functionality
        function initializeAuth() {
            // User dropdown toggle
            const userDropdown = document.getElementById('userDropdown');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userDropdown && userDropdownMenu) {
                userDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDropdownMenu.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!userDropdown.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                        userDropdownMenu.classList.add('hidden');
                    }
                });
            }
        }

        // Logout functionality
        async function logout() {
            try {
                const response = await fetch('api/auth.php?action=logout', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'index.php';
                }
            } catch (error) {
                console.error('Logout error:', error);
                window.location.href = 'index.php';
            }
        }
        
        // Initialize auth on page load
        document.addEventListener('DOMContentLoaded', initializeAuth);
    </script>

    <!-- Authentication Modals -->
    <?php include 'inc/auth_modals.php'; ?>

</body>
</html> 