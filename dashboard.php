<?php
require_once 'inc/database.php';
require_once 'inc/user_auth.php';
require_once 'inc/loyalty_system.php';

// Get current user from session
$currentUser = getCurrentUser();

// Redirect to login if not logged in
if (!$currentUser) {
    header('Location: index.php');
    exit;
}

$userId = $currentUser['id'];
$loyaltySystem = new LoyaltySystem();

// Fetch user loyalty data
$userData = $loyaltySystem->getUserLoyaltyData($userId);
$canCheckin = $loyaltySystem->canCheckinToday($userId);

// Fetch active tickets
$stmt = $pdo->prepare("
    SELECT t.*, r.title, r.image_url, r.draw_date, r.retail_value,
           CASE WHEN w.id IS NOT NULL THEN 'won' ELSE 'active' END as ticket_status
    FROM tickets t
    JOIN raffles r ON t.raffle_id = r.id
    LEFT JOIN winners w ON t.id = w.ticket_id
    WHERE t.user_id = ? AND r.status = 'active'
    ORDER BY r.draw_date ASC
    LIMIT 10
");
$stmt->execute([$userId]);
$activeTickets = $stmt->fetchAll();

// Fetch user wins
$stmt = $pdo->prepare("
    SELECT w.*, r.title, r.image_url, r.retail_value
    FROM winners w
    JOIN raffles r ON w.raffle_id = r.id
    WHERE w.user_id = ?
    ORDER BY w.win_date DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$userWins = $stmt->fetchAll();

// Fetch spending analytics (this month)
$stmt = $pdo->prepare("
    SELECT SUM(final_price) as monthly_spending,
           COUNT(*) as tickets_bought
    FROM tickets 
    WHERE user_id = ? AND DATE_FORMAT(purchase_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
");
$stmt->execute([$userId]);
$monthlyStats = $stmt->fetch();

// Fetch recommended raffles
$stmt = $pdo->prepare("
    SELECT r.*, c.name as category, c.icon as category_icon,
           (r.total_tickets - r.sold_tickets) as remaining,
           ((r.sold_tickets / r.total_tickets) * 100) as progress_percentage
    FROM raffles r
    LEFT JOIN categories c ON r.category_id = c.id
    WHERE r.status = 'active' AND r.draw_date > NOW()
    ORDER BY r.draw_date ASC
    LIMIT 6
");
$stmt->execute();
$recommendedRaffles = $stmt->fetchAll();

// Get check-in calendar (last 7 days)
$checkinCalendar = $loyaltySystem->getCheckinCalendar($userId, 7);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - RaffLah!</title>
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
        
        /* Progress bars */
        .progress-bar {
            background: linear-gradient(90deg, #007aff, #5ac8fa);
            transition: width 0.5s ease;
        }
        
        /* Pulse animation for notifications */
        .pulse-notification {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        /* Mini calendar for check-in */
        .mini-calendar-day {
            transition: all 0.2s ease;
        }
        
        .mini-calendar-day:hover {
            transform: scale(1.1);
        }
        
        /* Ticket card animations */
        .ticket-card {
            transition: all 0.3s ease;
        }
        
        .ticket-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(0, 122, 255, 0.3);
        }
        
        /* Countdown timer styling */
        .countdown-timer {
            font-family: 'Monaco', 'Consolas', monospace;
            font-weight: bold;
        }

        /* Liquid Glass Card (copied from homepage, strong effect) */
        .liquid-glass-card {
          background: linear-gradient(135deg, 
            rgba(255, 255, 255, 0.28) 0%, 
            rgba(255, 255, 255, 0.18) 50%, 
            rgba(255, 255, 255, 0.28) 100%);
          backdrop-filter: blur(32px) saturate(1.2);
          border: 2px solid rgba(255, 255, 255, 0.45);
          box-shadow: 
            0 12px 40px rgba(0, 112, 209, 0.22),
            0 2px 8px rgba(0,0,0,0.10),
            inset 0 2px 0 rgba(255, 255, 255, 0.35),
            inset 0 -2px 0 rgba(255, 255, 255, 0.18);
          position: relative;
          overflow: hidden;
          transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .floating-card {
          animation: subtleFloat 8s ease-in-out infinite;
        }
        @keyframes subtleFloat {
          0%, 100% { transform: translateY(0px); }
          50% { transform: translateY(-2px); }
        }
    </style>
</head>
<body class="bg-ps-bg min-h-screen">
    <!-- Navigation -->
    <nav class="sticky top-0 z-20 bg-white/80 backdrop-blur shadow-sm border-b border-ps-silver font-sans">
        <div class="max-w-7xl mx-auto flex items-center justify-between px-4 md:px-8 py-3 md:py-4">
            <a href="index.php" class="font-heading text-ps-blue text-2xl font-bold tracking-tight">RaffLah!</a>
            
            <div class="flex items-center bg-white rounded-full shadow-inner px-4 py-2 gap-2 w-full max-w-md mx-auto">
                <i class="fa-solid fa-magnifying-glass text-ps-silver"></i>
                <input class="flex-1 bg-transparent text-sm outline-none" placeholder="Search raffles & prizes"/>
                <button class="relative">
                    <i class="fa-solid fa-ticket text-ps-blue"></i>
                    <span class="absolute -top-1 -right-1 text-[10px] bg-ps-yellow text-ps-text rounded-full px-1"><?= count($activeTickets) ?></span>
                </button>
            </div>
            
            <div class="flex items-center gap-4 ml-2">

                <a href="loyalty-store.php" class="relative text-ps-blue hover:text-ps-light transition" title="Loyalty Store">
                    <i class="fa-solid fa-store text-2xl"></i>
                </a>
                <button class="relative text-ps-blue hover:text-ps-light transition">
                    <i class="fa-regular fa-bell text-2xl"></i>
                    <span class="absolute -top-1 -right-1 bg-ps-blue text-white text-xs rounded-full px-1.5 py-0.5 font-bold border-2 border-white">3</span>
                </button>
                
                <div class="relative">
                    <button id="userDropdown" class="flex items-center gap-2 group">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User" class="w-8 h-8 rounded-full border-2 border-ps-blue group-hover:border-ps-light transition" />
                        <span class="hidden md:inline text-ps-text font-semibold"><?= htmlspecialchars($currentUser['name']) ?></span>
                        <i class="fa-solid fa-chevron-down text-ps-silver group-hover:text-ps-blue transition"></i>
                    </button>
                    <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-1 z-50 border">
                        <a href="dashboard.php" class="block px-4 py-2 text-sm text-ps-blue bg-ps-blue/10 font-semibold">
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
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
        
        <!-- Welcome Banner -->
        <div class="bg-gradient-to-r from-ps-blue to-ps-light rounded-xl shadow-ps-lg p-6 mb-8 text-white liquid-glass-card floating-card">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="font-heading text-2xl md:text-3xl font-bold mb-2">
                        Welcome back, <?= htmlspecialchars($currentUser['name']) ?>! 👋
                    </h1>
                    <p class="text-white/90">Here's your raffle activity and rewards overview</p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-white/80">VIP Status</div>
                    <div class="font-heading text-xl font-bold capitalize flex items-center gap-2">
                        <i class="fa-solid fa-crown text-ps-yellow"></i>
                        <?= $userData['vip_tier'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Stats Row -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <!-- Total Tickets Purchased -->
            <div class="bg-white rounded-xl shadow-ps-lg p-4 text-center liquid-glass-card floating-card">
                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-tickets text-xl text-white"></i>
                </div>
                <div class="font-heading text-xl font-bold text-ps-text"><?= $monthlyStats['tickets_bought'] ?? 0 ?></div>
                <div class="text-sm text-ps-silver">Tickets This Month</div>
                <button onclick="window.location.href='index.php'" class="mt-2 bg-ps-blue text-white text-xs font-bold px-3 py-1 rounded-full hover:bg-ps-light transition">Buy More</button>
            </div>
            
            <!-- Active Tickets -->
            <div class="bg-white rounded-xl shadow-ps-lg p-4 text-center liquid-glass-card floating-card">
                <div class="w-12 h-12 bg-ps-blue rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-ticket text-xl text-white"></i>
                </div>
                <div class="font-heading text-xl font-bold text-ps-text"><?= count($activeTickets) ?></div>
                <div class="text-sm text-ps-silver">Active Tickets</div>
            </div>
            
            <!-- Loyalty Points -->
            <div class="bg-white rounded-xl shadow-ps-lg p-4 text-center liquid-glass-card floating-card">
                <div class="w-12 h-12 bg-ps-yellow rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-star text-xl text-white"></i>
                </div>
                <div class="font-heading text-xl font-bold text-ps-text"><?= number_format($userData['loyalty_points']) ?></div>
                <div class="text-sm text-ps-silver">Loyalty Points</div>
            </div>
            
            <!-- Total Wins -->
            <div class="bg-white rounded-xl shadow-ps-lg p-4 text-center liquid-glass-card floating-card">
                <div class="w-12 h-12 bg-ps-pink rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fa-solid fa-trophy text-xl text-white"></i>
                </div>
                <div class="font-heading text-xl font-bold text-ps-text"><?= count($userWins) ?></div>
                <div class="text-sm text-ps-silver">Total Wins</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Main Content -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <h2 class="font-heading text-xl font-bold text-ps-text mb-4">
                        <i class="fa-solid fa-bolt text-ps-blue mr-2"></i>
                        Quick Actions
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <button onclick="window.location.href='index.php'" class="bg-gradient-to-r from-ps-blue to-ps-light text-white font-heading font-bold py-3 rounded-lg text-center hover:from-ps-light hover:to-ps-blue transition flex flex-col items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-ticket text-xl"></i>
                            <span class="text-sm">Buy Tickets</span>
                        </button>
                        <button onclick="window.location.href='index.php#hot-products'" class="bg-gradient-to-r from-orange-500 to-red-500 text-white font-heading font-bold py-3 rounded-lg text-center hover:from-red-500 hover:to-orange-500 transition flex flex-col items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-fire text-xl"></i>
                            <span class="text-sm">Hot Products</span>
                        </button>
                        <button onclick="<?= $canCheckin ? 'performCheckin()' : 'alert(\"Already checked in today!\")' ?>" class="<?= $canCheckin ? 'bg-gradient-to-r from-ps-pink to-ps-yellow hover:from-ps-yellow hover:to-ps-pink' : 'bg-gray-400 cursor-not-allowed' ?> text-white font-heading font-bold py-3 rounded-lg text-center transition flex flex-col items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-gift text-xl"></i>
                            <span class="text-sm">Daily Reward</span>
                        </button>
                        <button onclick="window.location.href='loyalty-store.php'" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white font-heading font-bold py-3 rounded-lg text-center hover:from-pink-500 hover:to-purple-500 transition flex flex-col items-center gap-2 shadow-lg">
                            <i class="fa-solid fa-store text-xl"></i>
                            <span class="text-sm">Loyalty Store</span>
                        </button>
                    </div>
                </div>

                <!-- Active Tickets -->
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="font-heading text-xl font-bold text-ps-text">
                            <i class="fa-solid fa-clock text-ps-blue mr-2"></i>
                            Active Tickets & Draws
                        </h2>
                        <a href="#" class="text-ps-blue hover:text-ps-light text-sm font-semibold">View All</a>
                    </div>
                    
                    <?php if (empty($activeTickets)): ?>
                        <div class="text-center py-8 text-ps-silver">
                            <i class="fa-solid fa-ticket text-4xl mb-4"></i>
                            <p class="text-lg font-semibold mb-2">No active tickets</p>
                            <p class="text-sm">Purchase tickets to participate in upcoming draws!</p>
                            <button onclick="window.location.href='index.php'" class="mt-4 bg-ps-blue text-white font-heading font-bold px-6 py-2 rounded-lg hover:bg-ps-light transition">
                                Browse Raffles
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($activeTickets, 0, 3) as $ticket): ?>
                                <div class="ticket-card border border-ps-blue/20 rounded-lg p-4 bg-gradient-to-r from-blue-50 to-purple-50 liquid-glass-card floating-card">
                                    <div class="flex items-center gap-4">
                                        <img src="<?= $ticket['image_url'] ?? 'images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($ticket['title']) ?>" class="w-16 h-16 rounded-lg object-cover bg-gray-100">
                                        <div class="flex-1">
                                            <h3 class="font-heading font-bold text-ps-text mb-1"><?= htmlspecialchars($ticket['title']) ?></h3>
                                            <div class="flex items-center gap-4 text-sm text-ps-silver mb-2">
                                                <span>Ticket: <?= htmlspecialchars($ticket['ticket_number']) ?></span>
                                                <span>Prize: RM<?= number_format($ticket['retail_value'], 2) ?></span>
                                            </div>
                                            <div class="countdown-timer text-ps-blue font-bold" data-draw-date="<?= $ticket['draw_date'] ?>">
                                                Draw in: <span class="countdown-value">Loading...</span>
                                            </div>
                                        </div>
                                        <?php if ($ticket['ticket_status'] === 'won'): ?>
                                            <div class="text-center">
                                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mb-2">
                                                    <i class="fa-solid fa-trophy text-xl text-white"></i>
                                                </div>
                                                <span class="text-xs font-bold text-green-600">WON!</span>
                                            </div>
                                        <?php else: ?>
                                            <button class="bg-ps-blue text-white font-bold px-4 py-2 rounded-lg hover:bg-ps-light transition text-sm">
                                                Buy More
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Smart Recommendations -->
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <h2 class="font-heading text-xl font-bold text-ps-text mb-4">
                        <i class="fa-solid fa-sparkles text-ps-blue mr-2"></i>
                        Recommended for You
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach (array_slice($recommendedRaffles, 0, 4) as $raffle): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-ps transition liquid-glass-card floating-card">
                                <div class="flex items-center gap-3 mb-3">
                                    <img src="<?= $raffle['image_url'] ?? 'images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($raffle['title']) ?>" class="w-12 h-12 rounded-lg object-cover bg-gray-100">
                                    <div class="flex-1">
                                        <h4 class="font-heading font-bold text-ps-text text-sm mb-1"><?= htmlspecialchars($raffle['title']) ?></h4>
                                        <div class="text-xs text-ps-silver"><?= $raffle['category'] ?> • RM1.00 per ticket</div>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                    <div class="progress-bar h-2 rounded-full" style="width: <?= $raffle['progress_percentage'] ?>%"></div>
                                </div>
                                <div class="flex justify-between items-center text-xs text-ps-silver mb-3">
                                    <span><?= $raffle['remaining'] ?> left</span>
                                    <span><?= round($raffle['progress_percentage']) ?>% sold</span>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="quickBuyTickets(<?= $raffle['id'] ?>, 1, '<?= htmlspecialchars($raffle['title']) ?>')" class="flex-1 bg-ps-blue text-white font-bold py-2 rounded-lg hover:bg-ps-light transition text-sm">
                                        RM1 - Try Luck
                                    </button>
                                    <button onclick="quickBuyTickets(<?= $raffle['id'] ?>, 5, '<?= htmlspecialchars($raffle['title']) ?>')" class="flex-1 bg-yellow-500 text-white font-bold py-2 rounded-lg hover:bg-yellow-600 transition text-sm">
                                        RM5 - Lucky Five
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Ticket Purchase Stats -->
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <h2 class="font-heading text-xl font-bold text-ps-text mb-4">
                        <i class="fa-solid fa-chart-line text-ps-blue mr-2"></i>
                        Your Ticket Activity
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg liquid-glass-card floating-card">
                            <div class="text-2xl font-bold text-ps-blue"><?= $monthlyStats['tickets_bought'] ?? 0 ?></div>
                            <div class="text-sm text-gray-600">This Month</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg liquid-glass-card floating-card">
                            <div class="text-2xl font-bold text-green-600"><?= count($activeTickets) ?></div>
                            <div class="text-sm text-gray-600">Active Tickets</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg liquid-glass-card floating-card">
                            <div class="text-2xl font-bold text-yellow-600"><?= count($userWins) ?></div>
                            <div class="text-sm text-gray-600">Total Wins</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg liquid-glass-card floating-card">
                            <div class="text-2xl font-bold text-purple-600">RM<?= number_format($monthlyStats['monthly_spending'] ?? 0, 2) ?></div>
                            <div class="text-sm text-gray-600">Spent This Month</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sidebar -->
            <div class="space-y-6">
                
                <!-- Daily Check-in Widget -->
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-heading text-lg font-bold text-ps-text">
                            <i class="fa-solid fa-fire text-orange-500 mr-2"></i>
                            Daily Streak
                        </h3>
                        <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded-full text-xs font-bold">
                            <?= $userData['current_streak'] ?> days
                        </span>
                    </div>
                    
                    <!-- Mini Calendar (Last 7 days) -->
                    <div class="grid grid-cols-7 gap-1 mb-4">
                        <?php
                        $today = date('j');
                        $checkedInDays = [];
                        foreach ($checkinCalendar as $checkin) {
                            $day = date('j', strtotime($checkin['checkin_date']));
                            $checkedInDays[$day] = true;
                        }
                        
                        // Show last 7 days
                        for ($i = 6; $i >= 0; $i--) {
                            $checkDate = date('j', strtotime("-$i days"));
                            $isToday = $checkDate == $today;
                            $isChecked = isset($checkedInDays[$checkDate]);
                            
                            $classes = "mini-calendar-day w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all";
                            if ($isToday && !$isChecked) {
                                $classes .= " bg-ps-blue text-white";
                            } elseif ($isChecked) {
                                $classes .= " bg-green-100 text-green-700";
                            } else {
                                $classes .= " bg-gray-100 text-gray-400";
                            }
                            
                            echo "<div class='{$classes}'>{$checkDate}</div>";
                        }
                        ?>
                    </div>
                    
                    <div class="text-center">
                        <?php if ($canCheckin): ?>
                            <button onclick="performCheckin()" class="w-full bg-ps-pink text-white font-heading font-bold py-3 rounded-lg hover:bg-ps-yellow transition">
                                <i class="fa-solid fa-gift mr-2"></i>
                                Claim Daily Reward
                            </button>
                            <p class="text-xs text-ps-silver mt-2">+200 loyalty points</p>
                        <?php else: ?>
                            <button disabled class="w-full bg-green-500 text-white font-heading font-bold py-3 rounded-lg cursor-not-allowed">
                                <i class="fa-solid fa-check mr-2"></i>
                                Reward Claimed Today
                            </button>
                            <p class="text-xs text-green-600 mt-2">Come back tomorrow!</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Financial Overview -->
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <h3 class="font-heading text-lg font-bold text-ps-text mb-4">
                        <i class="fa-solid fa-chart-line text-ps-blue mr-2"></i>
                        Monthly Overview
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-ps-silver text-sm">This Month</span>
                            <span class="font-bold text-ps-text">RM<?= number_format($monthlyStats['monthly_spending'] ?? 0, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-ps-silver text-sm">Tickets Bought</span>
                            <span class="font-bold text-ps-text"><?= $monthlyStats['tickets_bought'] ?? 0 ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-ps-silver text-sm">Average per Ticket</span>
                            <span class="font-bold text-ps-text">
                                RM<?= $monthlyStats['tickets_bought'] > 0 ? number_format($monthlyStats['monthly_spending'] / $monthlyStats['tickets_bought'], 2) : '0.00' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <button class="w-full text-ps-blue hover:text-ps-light font-semibold text-sm">
                            View Full Transaction History →
                        </button>
                    </div>
                </div>

                <!-- Recent Wins -->
                <?php if (!empty($userWins)): ?>
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <h3 class="font-heading text-lg font-bold text-ps-text mb-4">
                        <i class="fa-solid fa-trophy text-ps-yellow mr-2"></i>
                        Recent Wins
                    </h3>
                    <div class="space-y-3">
                        <?php foreach (array_slice($userWins, 0, 3) as $win): ?>
                            <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                                <img src="<?= $win['image_url'] ?? 'images/placeholder.jpg' ?>" alt="<?= htmlspecialchars($win['title']) ?>" class="w-10 h-10 rounded-lg object-cover">
                                <div class="flex-1">
                                    <div class="font-bold text-ps-text text-sm"><?= htmlspecialchars($win['title']) ?></div>
                                    <div class="text-xs text-ps-silver">RM<?= number_format($win['retail_value'], 2) ?></div>
                                </div>
                                <i class="fa-solid fa-trophy text-ps-yellow"></i>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- VIP Progress -->
                <div class="bg-white rounded-xl shadow-ps-lg p-6 liquid-glass-card floating-card">
                    <h3 class="font-heading text-lg font-bold text-ps-text mb-4">
                        <i class="fa-solid fa-crown text-ps-blue mr-2"></i>
                        VIP Progress
                    </h3>
                    <div class="text-center mb-4">
                        <div class="font-heading text-2xl font-bold text-ps-blue capitalize"><?= $userData['vip_tier'] ?></div>
                        <div class="text-sm text-ps-silver">Current Tier</div>
                    </div>
                    
                    <?php if ($userData['vip_tier'] != 'diamond'): ?>
                        <?php 
                        $nextTierPoints = ['bronze' => 1000, 'silver' => 5000, 'gold' => 10000][$userData['vip_tier']];
                        $progress = min(($userData['vip_points'] / $nextTierPoints) * 100, 100);
                        ?>
                        <div class="mb-3">
                            <div class="flex justify-between text-sm text-ps-silver mb-1">
                                <span><?= number_format($userData['vip_points']) ?> VIP points</span>
                                <span><?= number_format($nextTierPoints) ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="progress-bar h-2 rounded-full" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>
                        <div class="text-center text-xs text-ps-blue">
                            <?= number_format($nextTierPoints - $userData['vip_points']) ?> points to next tier
                        </div>
                    <?php else: ?>
                        <div class="text-center text-ps-yellow">
                            <i class="fa-solid fa-crown text-2xl mb-2"></i>
                            <div class="text-sm font-bold">Highest Tier Achieved!</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal for Check-in -->
    <div id="successModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 max-w-md mx-4 text-center shadow-ps-lg liquid-glass-card floating-card">
            <div class="w-16 h-16 bg-ps-blue rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-check text-2xl text-white"></i>
            </div>
            <h3 class="font-heading text-xl font-bold text-ps-text mb-4">Daily Reward Claimed!</h3>
            <div id="rewardDetails" class="mb-6">
                <!-- Reward details will be inserted here -->
            </div>
            <button onclick="closeModal()" class="bg-ps-blue text-white font-heading font-bold px-6 py-3 rounded-lg shadow-ps hover:bg-ps-light transition-all">
                Continue
            </button>
        </div>
    </div>

    <script>
        // Countdown timers for draws
        function updateCountdowns() {
            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const drawDate = new Date(timer.dataset.drawDate);
                const now = new Date();
                const diff = drawDate - now;
                
                if (diff > 0) {
                    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    
                    let timeString = '';
                    if (days > 0) timeString += `${days}d `;
                    timeString += `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
                    
                    timer.querySelector('.countdown-value').textContent = timeString;
                } else {
                    timer.querySelector('.countdown-value').textContent = 'Draw completed';
                }
            });
        }
        
        setInterval(updateCountdowns, 60000); // Update every minute
        updateCountdowns(); // Initial update

        // Check-in functionality
        async function performCheckin() {
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Processing...';
            
            try {
                const response = await fetch('api/checkin.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'checkin', user_id: <?= $userId ?> })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showSuccessModal(result.data);
                    setTimeout(() => location.reload(), 3000);
                } else {
                    alert('Error: ' + result.error);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (error) {
                alert('Network error. Please try again.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }

        function showSuccessModal(data) {
            const modal = document.getElementById('successModal');
            const details = document.getElementById('rewardDetails');
            
            details.innerHTML = `
                <div class="text-3xl font-bold text-ps-blue mb-2">+${data.points_earned || 200}</div>
                <div class="text-ps-silver mb-4">Loyalty Points Earned</div>
                <div class="bg-ps-blue/10 rounded-lg p-4">
                    <div class="text-lg font-bold text-ps-blue">🔥 ${data.new_streak || (<?= $userData['current_streak'] ?> + 1)} Day Streak!</div>
                </div>
            `;
            
            modal.classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        // User dropdown functionality
        document.getElementById('userDropdown')?.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('userDropdownMenu').classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const menu = document.getElementById('userDropdownMenu');
            if (dropdown && menu && !dropdown.contains(event.target) && !menu.contains(event.target)) {
                menu.classList.add('hidden');
            }
        });

        // Logout functionality
        async function logout() {
            try {
                const response = await fetch('api/auth.php?action=logout', { method: 'POST' });
                const result = await response.json();
                if (result.success) {
                    window.location.href = 'index.php';
                }
            } catch (error) {
                window.location.href = 'index.php';
            }
        }
    </script>

    <!-- Authentication Modals -->
    <?php include 'inc/auth_modals.php'; ?>

</body>
</html> 