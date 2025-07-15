<?php
session_start();
require_once 'inc/database.php';
require_once 'inc/user_auth.php';
require_once 'inc/purchase_strategies.php';

$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();

// Sample raffle data for demo
$sampleRaffle = [
    'id' => 1,
    'title' => 'iPhone 15 Pro Max',
    'description' => 'Brand new iPhone 15 Pro Max 256GB in Space Black',
    'prize_value' => 5999,
    'total_tickets' => 1000,
    'sold_tickets' => 650,
    'ticket_price' => 1.00,
    'price' => 1.00,
    'status' => 'active'
];

$userTickets = 5; // Sample user tickets
$strategies = PurchaseStrategies::getRecommendedQuantities($sampleRaffle, $userTickets);
$motivators = PurchaseStrategies::getPurchaseMotivators($sampleRaffle, $userTickets);
$socialProof = PurchaseStrategies::getSocialProof($sampleRaffle);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RM1 Ticket Purchase Strategies - RaffLah!</title>
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
                    <span class="text-gray-500">Purchase Strategies Demo</span>
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
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-heading font-bold text-gray-900 mb-4">
                🎯 RM1 Ticket Strategy Guide
            </h1>
            <p class="text-xl text-gray-600 mb-6">
                Discover the psychology behind our RM1 ticket system and how it maximizes user engagement
            </p>
            <div class="bg-gradient-to-r from-green-100 to-blue-100 rounded-2xl p-6 max-w-4xl mx-auto">
                <div class="text-2xl font-bold text-gray-900 mb-2">Why RM1 Per Ticket Works</div>
                <div class="grid md:grid-cols-3 gap-6 text-sm">
                    <div class="text-center">
                        <div class="text-3xl mb-2">🎯</div>
                        <div class="font-semibold">Low Barrier to Entry</div>
                        <div class="text-gray-600">Anyone can afford to play</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-2">🔥</div>
                        <div class="font-semibold">Encourages Multiple Purchases</div>
                        <div class="text-gray-600">Easy to buy 5, 10, or 20 tickets</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl mb-2">🧠</div>
                        <div class="font-semibold">Psychological Comfort</div>
                        <div class="text-gray-600">Feels like small change, not gambling</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Purchase Strategies Breakdown -->
        <div class="grid lg:grid-cols-2 gap-8 mb-12">
            <!-- Strategy Analysis -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-heading font-bold text-gray-900 mb-6">🧠 Purchase Psychology</h2>
                
                <!-- Quick Entry Strategy -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-blue-600 mb-4">1. Quick Entry Options (1-5 tickets)</h3>
                    <div class="bg-blue-50 rounded-lg p-4 mb-4">
                        <div class="font-semibold text-blue-800 mb-2">Target: First-time users & casual players</div>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• <strong>RM1</strong> - "Try Your Luck" - Zero commitment, just testing</li>
                            <li>• <strong>RM3</strong> - "Triple Chance" - Small increase, 3x better odds</li>
                            <li>• <strong>RM5</strong> - "Lucky Five" - Popular psychological number</li>
                        </ul>
                    </div>
                    <div class="text-sm text-gray-600">
                        <strong>Psychology:</strong> Low risk, instant gratification, easy decision-making
                    </div>
                </div>

                <!-- Value Bundle Strategy -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-green-600 mb-4">2. Value Bundles (10-50 tickets)</h3>
                    <div class="bg-green-50 rounded-lg p-4 mb-4">
                        <div class="font-semibold text-green-800 mb-2">Target: Engaged users & repeat players</div>
                        <ul class="text-sm text-green-700 space-y-1">
                            <li>• <strong>RM10</strong> - "Perfect 10" - Round number, bonus entries</li>
                            <li>• <strong>RM20</strong> - "Power Pack" - Serious commitment level</li>
                            <li>• <strong>RM50</strong> - "Champion Bundle" - VIP status feeling</li>
                        </ul>
                    </div>
                    <div class="text-sm text-gray-600">
                        <strong>Psychology:</strong> Better value perception, achievement unlocks, social status
                    </div>
                </div>

                <!-- Dynamic Triggers -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-purple-600 mb-4">3. Dynamic Triggers</h3>
                    <div class="bg-purple-50 rounded-lg p-4 mb-4">
                        <div class="font-semibold text-purple-800 mb-2">Target: Contextual motivation</div>
                        <ul class="text-sm text-purple-700 space-y-1">
                            <li>• <strong>Urgency:</strong> "Only 350 tickets left!" (when 70%+ sold)</li>
                            <li>• <strong>Early Bird:</strong> "Get in early!" (when <30% sold)</li>
                            <li>• <strong>Lucky Numbers:</strong> "Lucky 8" - Cultural significance</li>
                            <li>• <strong>Social Proof:</strong> "15 people bought in last hour"</li>
                        </ul>
                    </div>
                    <div class="text-sm text-gray-600">
                        <strong>Psychology:</strong> FOMO, social validation, cultural preferences
                    </div>
                </div>
            </div>

            <!-- Live Demo -->
            <div class="bg-white rounded-2xl shadow-lg p-8">
                <h2 class="text-2xl font-heading font-bold text-gray-900 mb-6">🎮 Interactive Demo</h2>
                
                <!-- Sample Raffle -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl p-6 text-white mb-6">
                    <h3 class="text-xl font-bold mb-2">🏆 <?= $sampleRaffle['title'] ?></h3>
                    <div class="text-lg">Worth RM <?= number_format($sampleRaffle['prize_value']) ?></div>
                    <div class="mt-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span><?= $sampleRaffle['sold_tickets'] ?> sold</span>
                            <span><?= $sampleRaffle['total_tickets'] - $sampleRaffle['sold_tickets'] ?> left</span>
                        </div>
                        <div class="w-full bg-white/20 rounded-full h-2">
                            <div class="bg-yellow-400 h-2 rounded-full" style="width: <?= ($sampleRaffle['sold_tickets'] / $sampleRaffle['total_tickets']) * 100 ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Entry Demo -->
                <div class="mb-6">
                    <h4 class="font-semibold mb-3">🎯 Quick Entry Options</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <?php foreach ($strategies['quick_entry']['options'] as $option): ?>
                        <button onclick="selectDemoQuantity(<?= $option['qty'] ?>)" 
                                class="demo-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 hover:bg-blue-50 transition text-sm">
                            <div class="font-bold"><?= $option['qty'] ?></div>
                            <div class="text-xs text-gray-600"><?= $option['label'] ?></div>
                            <div class="text-xs text-blue-600">RM<?= $option['price'] ?></div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Value Bundles Demo -->
                <div class="mb-6">
                    <h4 class="font-semibold mb-3">💰 Value Bundles</h4>
                    <div class="space-y-2">
                        <?php foreach ($strategies['value_bundles']['options'] as $option): ?>
                        <button onclick="selectDemoQuantity(<?= $option['qty'] ?>)" 
                                class="demo-btn w-full border-2 border-gray-200 rounded-lg p-3 text-left hover:border-green-500 hover:bg-green-50 transition flex justify-between items-center">
                            <div>
                                <div class="font-semibold"><?= $option['label'] ?></div>
                                <div class="text-sm text-gray-600"><?= $option['desc'] ?></div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold text-green-600">RM<?= $option['price'] ?></div>
                                <?php if (isset($option['badge'])): ?>
                                <div class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded"><?= $option['badge'] ?></div>
                                <?php endif; ?>
                            </div>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Demo Feedback -->
                <div id="demoFeedback" class="bg-gray-100 rounded-lg p-4">
                    <div class="text-sm text-gray-600">Select a quantity to see the psychology in action!</div>
                </div>
            </div>
        </div>

        <!-- Gamification Elements -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
            <h2 class="text-2xl font-heading font-bold text-gray-900 mb-6">🎮 Gamification Elements</h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Achievements -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-trophy text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Achievement Badges</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div>🌟 Starter (5 tickets)</div>
                        <div>🎯 Committed (10 tickets)</div>
                        <div>🎲 High Roller (20 tickets)</div>
                        <div>🏆 Champion (50 tickets)</div>
                        <div>👑 Legend (100 tickets)</div>
                    </div>
                </div>

                <!-- Bonus System -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-gift text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Bonus Entries</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div>+1 bonus per 10 tickets</div>
                        <div>Friday bonus: +2 entries</div>
                        <div>Lucky numbers bonus</div>
                        <div>VIP multipliers</div>
                    </div>
                </div>

                <!-- Social Elements -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Social Proof</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div>Live purchase activity</div>
                        <div>Popular quantities</div>
                        <div>Recent winners</div>
                        <div>User testimonials</div>
                    </div>
                </div>

                <!-- Instant Rewards -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-bolt text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="font-semibold mb-2">Instant Gratification</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div>Immediate loyalty points</div>
                        <div>Instant ticket numbers</div>
                        <div>Real-time odds update</div>
                        <div>Achievement notifications</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Journey -->
        <div class="bg-white rounded-2xl shadow-lg p-8 mb-12">
            <h2 class="text-2xl font-heading font-bold text-gray-900 mb-6">🛤️ User Journey Optimization</h2>
            
            <div class="space-y-8">
                <!-- New User Journey -->
                <div>
                    <h3 class="text-lg font-semibold text-blue-600 mb-4">New User (First Visit)</h3>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-48 bg-blue-50 rounded-lg p-4">
                            <div class="font-semibold text-blue-800">1. Landing</div>
                            <div class="text-sm text-blue-700">See "RM1" prominently displayed</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-blue-50 rounded-lg p-4">
                            <div class="font-semibold text-blue-800">2. Interest</div>
                            <div class="text-sm text-blue-700">"Try Your Luck" for RM1</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-blue-50 rounded-lg p-4">
                            <div class="font-semibold text-blue-800">3. Action</div>
                            <div class="text-sm text-blue-700">Quick Touch 'n Go payment</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-blue-50 rounded-lg p-4">
                            <div class="font-semibold text-blue-800">4. Success</div>
                            <div class="text-sm text-blue-700">Instant ticket + achievement</div>
                        </div>
                    </div>
                </div>

                <!-- Returning User Journey -->
                <div>
                    <h3 class="text-lg font-semibold text-green-600 mb-4">Returning User (Engaged)</h3>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-48 bg-green-50 rounded-lg p-4">
                            <div class="font-semibold text-green-800">1. Recognition</div>
                            <div class="text-sm text-green-700">Personalized recommendations</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-green-50 rounded-lg p-4">
                            <div class="font-semibold text-green-800">2. Escalation</div>
                            <div class="text-sm text-green-700">"Perfect 10" bundle suggestion</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-green-50 rounded-lg p-4">
                            <div class="font-semibold text-green-800">3. Commitment</div>
                            <div class="text-sm text-green-700">Wallet top-up + bulk purchase</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-green-50 rounded-lg p-4">
                            <div class="font-semibold text-green-800">4. Loyalty</div>
                            <div class="text-sm text-green-700">VIP status + bonus entries</div>
                        </div>
                    </div>
                </div>

                <!-- High-Value User Journey -->
                <div>
                    <h3 class="text-lg font-semibold text-purple-600 mb-4">High-Value User (Champion)</h3>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-48 bg-purple-50 rounded-lg p-4">
                            <div class="font-semibold text-purple-800">1. VIP Treatment</div>
                            <div class="text-sm text-purple-700">Exclusive champion bundles</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-purple-50 rounded-lg p-4">
                            <div class="font-semibold text-purple-800">2. Maximum Value</div>
                            <div class="text-sm text-purple-700">50-100 ticket purchases</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-purple-50 rounded-lg p-4">
                            <div class="font-semibold text-purple-800">3. Social Status</div>
                            <div class="text-sm text-purple-700">Legend badge + leaderboard</div>
                        </div>
                        <div class="flex-1 min-w-48 bg-purple-50 rounded-lg p-4">
                            <div class="font-semibold text-purple-800">4. Retention</div>
                            <div class="text-sm text-purple-700">Priority access + bonuses</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="bg-gray-900 text-white rounded-2xl shadow-lg p-8">
            <h2 class="text-2xl font-heading font-bold mb-6">📊 Expected Impact Metrics</h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-400 mb-2">+150%</div>
                    <div class="text-lg font-semibold">Conversion Rate</div>
                    <div class="text-sm text-gray-300">Lower barrier = more players</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-400 mb-2">+200%</div>
                    <div class="text-lg font-semibold">Average Order Value</div>
                    <div class="text-sm text-gray-300">Easy to buy multiple tickets</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-yellow-400 mb-2">+80%</div>
                    <div class="text-lg font-semibold">User Retention</div>
                    <div class="text-sm text-gray-300">Gamification hooks</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-400 mb-2">+300%</div>
                    <div class="text-lg font-semibold">Payment Success</div>
                    <div class="text-sm text-gray-300">Touch 'n Go integration</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Authentication Modals -->
    <?php include 'inc/auth_modals.php'; ?>

    <script>
        function selectDemoQuantity(qty) {
            // Update visual selection
            document.querySelectorAll('.demo-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'bg-blue-50', 'border-green-500', 'bg-green-50');
            });
            event.target.closest('.demo-btn').classList.add('border-blue-500', 'bg-blue-50');
            
            // Update feedback
            const feedback = document.getElementById('demoFeedback');
            const odds = ((qty / 1000) * 100).toFixed(2);
            let bonusText = '';
            let achievementText = '';
            
            // Calculate bonuses
            if (qty >= 10) {
                const bonusEntries = Math.floor(qty / 10);
                bonusText = ` + ${bonusEntries} bonus entries`;
            }
            
            // Show achievements
            const achievements = {
                5: '🌟 Starter Badge',
                10: '🎯 Committed Badge',
                20: '🎲 High Roller Badge',
                50: '🏆 Champion Badge',
                100: '👑 Legend Badge'
            };
            
            for (const [threshold, badge] of Object.entries(achievements)) {
                if (qty >= threshold) {
                    achievementText = ` • ${badge} unlocked!`;
                }
            }
            
            feedback.innerHTML = `
                <div class="text-sm">
                    <div class="font-semibold text-gray-900 mb-2">Purchase Analysis: ${qty} tickets for RM${qty}</div>
                    <div class="text-gray-600">
                        • Your odds: ${odds}%${bonusText}
                        <br>• Loyalty points: +${qty >= 20 ? Math.floor(qty * 1.5) : qty}${achievementText}
                    </div>
                </div>
            `;
            
            // Change background based on quantity
            if (qty >= 20) {
                feedback.className = 'bg-purple-50 border border-purple-200 rounded-lg p-4';
            } else if (qty >= 10) {
                feedback.className = 'bg-green-50 border border-green-200 rounded-lg p-4';
            } else if (qty >= 5) {
                feedback.className = 'bg-blue-50 border border-blue-200 rounded-lg p-4';
            } else {
                feedback.className = 'bg-gray-100 rounded-lg p-4';
            }
        }
    </script>
</body>
</html> 