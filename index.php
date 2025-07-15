<?php
define('BASE_URL', '/raffle-demo'); // Define the base URL for the project
require_once 'inc/database.php';
require_once 'inc/user_auth.php';
require_once 'inc/purchase_strategies.php';
require_once 'inc/enhanced_purchase_ui.php';
require_once 'inc/page_components.php';

// Initialize authentication and purchase strategies
$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();
$purchaseStrategies = new PurchaseStrategies();
$enhancedUI = new EnhancedPurchaseUI();

// Fetch all active categories with brand layout settings
$stmt = $pdo->query("SELECT *, COALESCE(is_active, 1) as is_active, COALESCE(show_brands, 1) as show_brands FROM categories WHERE COALESCE(is_active, 1) = 1 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if brands table exists
$brands_table_exists = false;
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'brands'");
    $brands_table_exists = $stmt->rowCount() > 0;
} catch (Exception $e) {
    $brands_table_exists = false;
}

// Fetch live activity for social proof
$stmt = $pdo->query("
    SELECT * FROM live_activity 
    WHERE is_visible = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY created_at DESC 
    LIMIT 10
");
$liveActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user achievements if logged in
$userAchievements = [];
if ($currentUser) {
    $stmt = $pdo->prepare("
        SELECT a.name, a.icon, a.badge_color, ua.earned_at
        FROM user_achievements ua
        JOIN achievements a ON ua.achievement_id = a.id
        WHERE ua.user_id = ?
        ORDER BY ua.earned_at DESC
    ");
    $stmt->execute([$currentUser['id']]);
    $userAchievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all raffles with category info (and brand info if available) - Enhanced for RM1 strategy
if ($brands_table_exists) {
    $stmt = $pdo->query("
      SELECT 
        r.id,
        r.title,
        r.description,
        r.image_url,
        COALESCE(r.ticket_price, 1.00) as ticket_price,
        r.sold_tickets,
        r.total_tickets,
        r.status,
        r.early_bird_bonus,
        r.lucky_numbers,
        r.social_proof_enabled,
        r.urgency_threshold,
        c.name AS category,
        c.icon AS category_icon,
        b.name AS brand_name,
        b.slug AS brand_slug,
        COALESCE(b.image_url, '') AS brand_image_url,
        b.is_featured,
        b.sort_order,
        bc.category_sort_order,
        TIMESTAMPDIFF(HOUR, NOW(), r.draw_date) as hours_remaining
      FROM raffles r
      LEFT JOIN categories c ON r.category_id = c.id
      LEFT JOIN brands b ON r.brand_id = b.id
      LEFT JOIN brand_categories bc ON b.id = bc.brand_id AND c.id = bc.category_id
      ORDER BY r.id ASC
    ");
} else {
    $stmt = $pdo->query("
      SELECT 
        r.id,
        r.title,
        r.description,
        r.image_url,
        COALESCE(r.ticket_price, 1.00) as ticket_price,
        r.sold_tickets,
        r.total_tickets,
        r.status,
        r.early_bird_bonus,
        r.lucky_numbers,
        r.social_proof_enabled,
        r.urgency_threshold,
        c.name AS category,
        c.icon AS category_icon,
        NULL AS brand_name,
        NULL AS brand_slug,
        TIMESTAMPDIFF(HOUR, NOW(), r.draw_date) as hours_remaining
      FROM raffles r
      LEFT JOIN categories c ON r.category_id = c.id
      ORDER BY r.id ASC
    ");
}
$raffles = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    // Only show raffles with status 'active' and total tickets > 0
    $total = (int)$row['total_tickets'];
    $sold = (int)$row['sold_tickets'];
    
    if ($row['status'] !== 'active' || $total <= 0) continue;
    
    // Calculate psychological triggers for RM1 strategy
    $hoursRemaining = $row['hours_remaining'] ?? 72;
    $soldPercentage = $total > 0 ? ($sold / $total) * 100 : 0;
    $isUrgent = $hoursRemaining <= ($row['urgency_threshold'] ?? 72);
    $isSellingFast = $soldPercentage >= 70;
    $isEarlyBird = $row['early_bird_bonus'] && $soldPercentage < 30;
    
    // Generate psychological badges
    $badges = [];
    if ($isUrgent) $badges[] = ['text' => 'URGENT', 'color' => 'red'];
    if ($isSellingFast) $badges[] = ['text' => 'SELLING FAST', 'color' => 'orange'];
    if ($isEarlyBird) $badges[] = ['text' => 'EARLY BIRD', 'color' => 'green'];
    if ($row['lucky_numbers']) $badges[] = ['text' => 'LUCKY NUMBERS', 'color' => 'purple'];
    
    $raffles[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'] ?? '',
        'image_url' => isset($row['image_url']) ? $row['image_url'] : '',
        'price' => 1.00, // Force RM1 pricing
        'sold' => $sold,
        'total' => $total,
        'category' => $row['category'],
        'category_icon' => $row['category_icon'],
        'brand_name' => $row['brand_name'] ?: 'Other',
        'brand_slug' => $row['brand_slug'] ?: 'other',
        'brand_image_url' => $row['brand_image_url'] ?: '',
        'brand_featured' => isset($row['is_featured']) ? $row['is_featured'] : 0,
        'brand_sort_order' => isset($row['sort_order']) ? $row['sort_order'] : 999,
        'category_sort_order' => isset($row['category_sort_order']) ? $row['category_sort_order'] : null,
        'badge' => null,
        'badges' => $badges,
        'hours_remaining' => $hoursRemaining,
        'sold_percentage' => $soldPercentage,
        'is_urgent' => $isUrgent,
        'is_selling_fast' => $isSellingFast,
        'is_early_bird' => $isEarlyBird,
        'social_proof_enabled' => $row['social_proof_enabled'] ?? true,
        'lucky_numbers' => $row['lucky_numbers'],
        'rm1_strategy' => $purchaseStrategies->getOptimalStrategy($currentUser, $row, $soldPercentage, $hoursRemaining)
    ];
}

$targetDate = '2025-07-01T18:00:00+08:00';


// Seed special categories (functions moved to inc/page_components.php)


$specialRaffles = [
  'Just For U'     => getJustForU($raffles, $currentUser, $pdo),
  'Hot Products'   => getHotProducts($raffles),
  'Selling Fast'   => getSellingFast($raffles),
];





// Get categories that have active raffles
$categoriesWithRaffles = getCategoriesWithRaffles($categories, $raffles);

// Group raffles by brand within categories
$groupedRaffles = groupRafflesByBrand($raffles, $categories);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>RaffLah! Raffle Platform</title>
  <!-- Google Fonts: Poppins & Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'ui-sans-serif', 'system-ui'],
            heading: ['Poppins', 'Inter', 'ui-sans-serif', 'system-ui'],
          },
          colors: {
            ps: {
              blue: '#0070D1',
              light: '#66A9FF',
              yellow: '#FFD600',
              silver: '#B0B0B0',
              bg: '#F2F2F2',
              text: '#1E1E1E',
            }
          },
          boxShadow: {
            'ps': '0 4px 24px 0 rgba(0,112,209,0.12)',
            'ps-hover': '0 8px 32px 0 rgba(0,112,209,0.20)',
            'ps-yellow': '0 4px 24px 0 rgba(255,214,0,0.12)',
            'ps-yellow-hover': '0 8px 32px 0 rgba(255,214,0,0.20)'
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/frontend.css">
  <style>
    /* Frontend-specific fonts and minimal overrides */
    body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    h1, h2, h3, .font-heading { font-family: 'Poppins', 'Inter', ui-sans-serif, system-ui, sans-serif; }
    
    /* Tailwind CSS utility extensions */
    .shadow-ps { box-shadow: 0 4px 12px rgba(0, 112, 209, 0.15); }
    .shadow-ps-lg { box-shadow: 0 8px 25px rgba(0, 112, 209, 0.2); }
    .shadow-ps-hover { box-shadow: 0 10px 30px rgba(0, 112, 209, 0.25); }
    .shadow-ps-yellow-hover { box-shadow: 0 8px 20px rgba(255, 215, 0, 0.3); }
    
    /* Enhanced 3D Liquid Glass Effects */
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
    
    .liquid-glass-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, 
        transparent, 
        rgba(255, 255, 255, 0.05), 
        transparent);
      transition: left 0.8s ease;
    }
    
    .liquid-glass-card:hover::before {
      left: 100%;
    }
    
    .liquid-glass-card:hover {
      transform: translateY(-4px);
      box-shadow: 
        0 12px 24px rgba(0, 112, 209, 0.15),
        0 4px 8px rgba(0, 0, 0, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.3),
        inset 0 -1px 0 rgba(255, 255, 255, 0.1);
    }
    
    /* Subtle Floating Animation for Cards */
    .floating-card {
      animation: subtleFloat 8s ease-in-out infinite;
    }
    
    .floating-card:nth-child(2n) {
      animation-delay: -3s;
    }
    
    .floating-card:nth-child(3n) {
      animation-delay: -6s;
    }
    
    @keyframes subtleFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-2px); }
    }
    
    /* Enhanced Slider with Liquid Effects */
    .liquid-slider {
      background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.1) 0%, 
        rgba(255, 255, 255, 0.05) 50%, 
        rgba(255, 255, 255, 0.1) 100%);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 
        0 4px 20px rgba(0, 112, 209, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
      position: relative;
      overflow: hidden;
    }
    
    .liquid-slider::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, 
        transparent, 
        rgba(0, 112, 209, 0.6), 
        transparent);
      animation: shimmer 2s ease-in-out infinite;
    }
    
    @keyframes shimmer {
      0%, 100% { transform: translateX(-100%); }
      50% { transform: translateX(100%); }
    }
    
    /* Enhanced Progress Bar with Liquid Effect */
    .liquid-progress {
      background: linear-gradient(90deg, 
        rgba(0, 112, 209, 0.1) 0%, 
        rgba(0, 112, 209, 0.2) 50%, 
        rgba(0, 112, 209, 0.1) 100%);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(0, 112, 209, 0.2);
      position: relative;
      overflow: hidden;
    }
    
    .liquid-progress > div {
      position: relative;
      z-index: 2;
    }
    
    .liquid-progress > div::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(255, 255, 255, 0.3) 50%, 
        transparent 100%);
      animation: progressShimmer 2s ease-in-out infinite;
    }
    
    @keyframes progressShimmer {
      0%, 100% { transform: translateX(-100%); }
      50% { transform: translateX(100%); }
    }
    
    @keyframes liquidFlow {
      0%, 100% { 
        transform: translateX(-10px) scaleX(1);
        filter: brightness(1);
      }
      50% { 
        transform: translateX(10px) scaleX(1.1);
        filter: brightness(1.2);
      }
    }
    
    /* Glass Morphism Buttons */
    .glass-button {
      background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.1) 0%, 
        rgba(255, 255, 255, 0.05) 100%);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 
        0 4px 15px rgba(0, 112, 209, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .glass-button:hover {
      transform: translateY(-2px);
      box-shadow: 
        0 8px 25px rgba(0, 112, 209, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
      border-color: rgba(0, 112, 209, 0.3);
    }
    
    /* Premium Business Category Bar */
    .premium-category-bar {
      background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #ffffff 100%);
      border: 1px solid #e2e8f0;
      box-shadow: 
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      border-radius: 16px;
      position: relative;
      overflow: hidden;
    }
    
    .premium-category-bar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, 
        transparent 0%, 
        rgba(0, 112, 209, 0.3) 50%, 
        transparent 100%);
    }
    
    .premium-category-btn {
      background: transparent;
      border: none;
      color: #64748b;
      font-weight: 500;
      font-size: 14px;
      padding: 16px 24px;
      border-radius: 12px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      white-space: nowrap;
    }
    
    .premium-category-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, 
        rgba(0, 112, 209, 0.05) 0%, 
        rgba(0, 112, 209, 0.02) 100%);
      opacity: 0;
      transition: opacity 0.3s ease;
      border-radius: 12px;
    }
    
    .premium-category-btn:hover::before {
      opacity: 1;
    }
    
    .premium-category-btn:hover {
      color: #0070D1;
      transform: translateY(-1px);
    }
    
    .premium-category-btn.active {
      color: #0070D1;
      background: linear-gradient(135deg, 
        rgba(0, 112, 209, 0.08) 0%, 
        rgba(0, 112, 209, 0.04) 100%);
      box-shadow: 
        0 2px 8px rgba(0, 112, 209, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.8);
      font-weight: 600;
    }
    
    .premium-category-btn.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 24px;
      height: 3px;
      border-radius: 2px;
      box-shadow: 0 0 8px rgba(0, 112, 209, 0.4);
    }
    
    .premium-category-btn i {
      margin-right: 8px;
      font-size: 16px;
      transition: transform 0.3s ease;
    }
    
    .premium-category-btn:hover i {
      transform: scale(1.1);
    }
    
    .premium-category-btn.active i {
      transform: scale(1.1);
    }
    
    /* Premium Underline Animation */
    .premium-underline {
      background: linear-gradient(90deg, 
        transparent 0%, 
        #0070D1 50%, 
        transparent 100%);
      height: 3px;
      border-radius: 2px;
      box-shadow: 0 0 12px rgba(0, 112, 209, 0.6);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Enhanced Badge Effects */
    .liquid-badge {
      background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.1) 0%, 
        rgba(255, 255, 255, 0.05) 100%);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 
        0 2px 10px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
      animation: subtleBadgeFloat 4s ease-in-out infinite;
    }
    
    @keyframes subtleBadgeFloat {
      0%, 100% { transform: translateY(0px); }
      50% { transform: translateY(-1px); }
    }
    
    /* Enhanced Image Container */
    .liquid-image-container {
      background: linear-gradient(135deg, 
        rgba(255, 255, 255, 0.1) 0%, 
        rgba(255, 255, 255, 0.05) 100%);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 
        0 8px 25px rgba(0, 112, 209, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
      position: relative;
      overflow: hidden;
    }
    
    .liquid-image-container::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: linear-gradient(45deg, 
        transparent 30%, 
        rgba(255, 255, 255, 0.03) 50%, 
        transparent 70%);
      animation: subtleImageShimmer 6s ease-in-out infinite;
    }
    
    @keyframes subtleImageShimmer {
      0%, 100% { transform: rotate(0deg) translateX(-100%); }
      50% { transform: rotate(180deg) translateX(100%); }
    }
    
    /* Enhanced Scrollbar */
    .scrollbar-hide::-webkit-scrollbar {
      display: none;
    }
    
    .scrollbar-hide {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    
    /* Premium Fade Effects */
    .cat-fade {
      position: absolute;
      top: 0;
      bottom: 0;
      width: 40px;
      pointer-events: none;
      transition: opacity 0.3s ease;
      z-index: 10;
    }
    
    .fade-left {
      left: 0;
      background: linear-gradient(90deg, 
        rgba(255, 255, 255, 1) 0%, 
        rgba(255, 255, 255, 0.8) 30%, 
        transparent 100%);
    }
    
    .fade-right {
      right: 0;
      background: linear-gradient(270deg, 
        rgba(255, 255, 255, 1) 0%, 
        rgba(255, 255, 255, 0.8) 30%, 
        transparent 100%);
    }
    
    /* Banner Slider Styles */
    .banner-slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      transition: opacity 0.8s ease-in-out;
      background-size: contain;
      background-position: center;
      background-repeat: no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .banner-slide.active {
      opacity: 1;
    }
    
    .banner-slide.fade-out {
      opacity: 0;
    }
    
    .banner-slide.fade-in {
      opacity: 1;
    }
    
    /* Banner Navigation */
    .slide-dot {
      transition: all 0.3s ease;
    }
    
    .slide-dot:hover {
      transform: scale(1.2);
    }
    
    .slide-dot.active {
      background: white !important;
      transform: scale(1.3);
    }
    
    /* Banner-specific stronger floating effect */
    .banner-float {
      animation: bannerFloat 5s ease-in-out infinite;
    }
    @keyframes bannerFloat {
      0%, 100% { transform: translateY(0px); }
      20% { transform: translateY(-1px); }
      50% { transform: translateY(-2px); }
      80% { transform: translateY(-1px); }
    }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen flex flex-col relative overflow-x-hidden">
  <!-- Animated Background Elements -->
  <div class="fixed inset-0 pointer-events-none overflow-hidden">
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-200/20 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-1/4 right-1/4 w-80 h-80 bg-purple-200/20 rounded-full blur-3xl animate-pulse" style="animation-delay: -2s;"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-yellow-200/20 rounded-full blur-3xl animate-pulse" style="animation-delay: -4s;"></div>
  </div>
  <!-- 1. Sticky Navbar -->
  <nav class="sticky top-0 z-20 bg-white/80 backdrop-blur shadow-sm border-b border-ps-silver font-sans">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-4 md:px-8 py-3 md:py-4">
      <!-- Logo (text only, one-word brand) -->
      <a href="#" class="font-heading text-ps-blue text-2xl font-bold tracking-tight">RaffLah!</a>
      <!-- Centered Search Bar -->
      <div class="flex items-center bg-white rounded-full shadow-inner px-4 py-2 gap-2 w-full max-w-md mx-auto">
        <i class="fa-solid fa-magnifying-glass text-ps-silver"></i>
        <input class="flex-1 bg-transparent text-sm outline-none" placeholder="Search raffles & prizes"/>
        <button class="relative">
          <i class="fa-solid fa-ticket text-ps-blue"></i>
          <span id="ticket-count" class="absolute -top-1 -right-1 text-[10px] bg-ps-yellow text-ps-text rounded-full px-1"></span>
        </button>
      </div>
      <!-- Right Side: User, Notifications, Language/Currency -->
      <div class="flex items-center gap-4 ml-2">

        <a href="loyalty-store.php" class="relative text-ps-blue hover:text-ps-light transition" title="Loyalty Store">
          <i class="fa-solid fa-store text-2xl"></i>
        </a>
        <a href="#" onclick="showWishlist()" class="relative text-ps-blue hover:text-ps-light transition" title="My Wishlist">
          <i class="fa-solid fa-heart text-2xl"></i>
          <span id="wishlist-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5 font-bold border-2 border-white">0</span>
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

  <!-- Live Activity Feed (Social Proof) -->
  <?php if (!empty($liveActivity)): ?>
  <div class="w-full bg-gradient-to-r from-green-50 to-blue-50 border-b border-green-200 py-2 overflow-hidden">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex items-center gap-2 text-sm">
        <i class="fa-solid fa-pulse text-green-500"></i>
        <span class="font-semibold text-green-700">Live Activity:</span>
        <div class="flex-1 overflow-hidden">
          <div class="animate-scroll whitespace-nowrap">
            <?php foreach ($liveActivity as $activity): ?>
              <span class="inline-block mr-8 text-gray-700">
                <?php if ($activity['activity_type'] === 'purchase'): ?>
                  <i class="fa-solid fa-ticket text-blue-500"></i>
                  <strong><?= htmlspecialchars($activity['user_name']) ?></strong> just bought <?= $activity['tickets_count'] ?> tickets for <?= htmlspecialchars($activity['raffle_title']) ?>
                <?php elseif ($activity['activity_type'] === 'achievement'): ?>
                  <i class="fa-solid fa-trophy text-yellow-500"></i>
                  <strong><?= htmlspecialchars($activity['user_name']) ?></strong> earned <?= htmlspecialchars($activity['achievement_name']) ?> badge!
                <?php endif; ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Achievement Notifications -->
  <?php if ($currentUser && !empty($userAchievements)): ?>
  <div class="w-full bg-gradient-to-r from-purple-50 to-pink-50 border-b border-purple-200 py-3">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-trophy text-purple-500"></i>
          <span class="font-semibold text-purple-700">Your Latest Achievements:</span>
        </div>
        <div class="flex gap-2 flex-wrap">
          <?php foreach (array_slice($userAchievements, 0, 3) as $achievement): ?>
            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-<?= $achievement['badge_color'] ?>-100 text-<?= $achievement['badge_color'] ?>-800">
              <i class="<?= $achievement['icon'] ?>"></i>
              <?= htmlspecialchars($achievement['name']) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- 2. Hero Banner Slider -->
  <section class="w-full max-w-7xl mx-auto px-2 md:px-8 mt-10">
    <div class="relative rounded-2xl overflow-hidden shadow-ps h-[400px] md:h-[500px] floating-card liquid-glass-card banner-float">
      <!-- Banner Slider -->
      <div id="bannerSlider" class="relative w-full h-full">
        <?php
        // Fetch active banner slides with error handling
        $bannerSlides = [];
        try {
            // Check if banner_slides table exists
            $stmt = $pdo->query("SHOW TABLES LIKE 'banner_slides'");
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                $stmt = $pdo->query("
                    SELECT * FROM banner_slides 
                    WHERE is_active = 1 
                    AND (start_date IS NULL OR start_date <= CURDATE())
                    AND (end_date IS NULL OR end_date >= CURDATE())
                    ORDER BY sort_order ASC, created_at DESC
                ");
                $bannerSlides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // Table doesn't exist or other database error
            $bannerSlides = [];
            error_log("Banner slides query failed: " . $e->getMessage());
        }
        
        if (empty($bannerSlides)) {
            // Default banner if no slides configured
            echo '<div class="banner-slide active" style="background-image: url(\'images/iphone.png\');">
                <div class="absolute inset-0"></div>
                <div class="relative z-10 flex flex-col md:flex-row items-center p-8 md:p-12 w-full h-full">
                    <div class="flex-1 flex flex-col justify-center items-start gap-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-block bg-ps-yellow text-ps-text font-heading font-bold px-4 py-1 rounded-full text-xs tracking-wide">FLASH DEAL</span>
                            <span class="inline-block bg-red-500 text-white font-heading font-bold px-3 py-1 rounded-full text-xs tracking-wide animate-pulse">RM1 ONLY!</span>
                        </div>
                        <h1 class="text-white font-heading text-3xl md:text-5xl font-extrabold leading-tight mb-2 drop-shadow" style="line-height:1.15;">Win an iPhone 14 –<br class="hidden md:block"> Just RM1 per ticket!</h1>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full">
                            <span class="flex items-center gap-2 bg-white/20 rounded-full px-5 py-2 text-white font-bold text-base md:text-lg">
                                <i class="fa-solid fa-clock text-ps-yellow"></i>
                                Draw in <span id="hero-countdown" class="font-mono text-ps-yellow"></span>
                            </span>
                            <div class="flex gap-2">
                                <button onclick="quickBuy(1, \'iPhone 14\')" class="flex items-center gap-2 bg-ps-yellow hover:bg-yellow-400 text-ps-text font-heading font-bold text-base md:text-lg px-6 py-2.5 rounded-full shadow-ps transition focus:ring-2 focus:ring-ps-yellow">
                                    <i class="fa-solid fa-ticket"></i> RM1 - Try Luck
                               
                            </div>
                        </div>
                        <div class="mt-2 text-white/90 text-xs md:text-sm">
                            <span class="inline-block bg-white/20 rounded px-2 py-1 mr-2">🎯 87% buy 1-5 tickets</span>
                            <span class="inline-block bg-white/20 rounded px-2 py-1">⚡ 234 people viewing</span>
                        </div>
                    </div>
                    <div class="flex-1 flex justify-end items-center relative h-full">
                        <img src="images/iphone.png" alt="iPhone 14" class="relative h-48 md:h-64 w-auto max-w-xs md:max-w-sm rounded-xl border-4 border-white/30 object-contain bg-white/10 z-10" />
                    </div>
                </div>
            </div>';
        } else {
            foreach ($bannerSlides as $index => $slide) {
                $isActive = $index === 0 ? 'active' : '';
                $badgeClass = 'bg-' . $slide['badge_color'] . ($slide['badge_color'] === 'yellow' ? ' text-ps-text' : ' text-white');
                if ($slide['badge_color'] === 'red') $badgeClass = 'bg-red-500 text-white';
                if ($slide['badge_color'] === 'blue') $badgeClass = 'bg-ps-blue text-white';
                if ($slide['badge_color'] === 'green') $badgeClass = 'bg-green-500 text-white';
                if ($slide['badge_color'] === 'purple') $badgeClass = 'bg-purple-500 text-white';
                
                echo '<div class="banner-slide ' . $isActive . '" style="background-image: url(\'' . htmlspecialchars($slide['background_image']) . '\');">
                    <div class="absolute inset-0"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-center p-8 md:p-12 w-full h-full">
                        <div class="flex-1 flex flex-col justify-center items-start gap-4">
                            ' . (!empty($slide['badge_text']) ? '<div class="flex items-center gap-2 mb-2">
                                <span class="inline-block ' . $badgeClass . ' font-heading font-bold px-4 py-1 rounded-full text-xs tracking-wide">' . htmlspecialchars($slide['badge_text']) . '</span>
                            </div>' : '') . '
                            <h1 class="text-white font-heading text-3xl md:text-5xl font-extrabold leading-tight mb-2 drop-shadow" style="line-height:1.15;">' . htmlspecialchars($slide['title']) . '</h1>
                            ' . (!empty($slide['subtitle']) ? '<p class="text-white/90 text-base md:text-lg mb-2">' . htmlspecialchars($slide['subtitle']) . '</p>' : '') . '
                            ' . (!empty($slide['description']) ? '<p class="text-white/80 text-sm md:text-base mb-2">' . htmlspecialchars($slide['description']) . '</p>' : '') . '
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 w-full">
                                <span class="flex items-center gap-2 bg-white/20 rounded-full px-5 py-2 text-white font-bold text-base md:text-lg">
                                    <i class="fa-solid fa-clock text-ps-yellow"></i>
                                    Draw in <span class="font-mono text-ps-yellow">24:00:00</span>
                                </span>
                                <div class="flex gap-2">
                                    ' . (!empty($slide['button_text']) ? '<a href="' . htmlspecialchars($slide['button_url'] ?: '#') . '" class="flex items-center gap-2 bg-ps-yellow hover:bg-yellow-400 text-ps-text font-heading font-bold text-base md:text-lg px-6 py-2.5 rounded-full shadow-ps transition focus:ring-2 focus:ring-ps-yellow">
                                        <i class="fa-solid fa-ticket"></i> ' . htmlspecialchars($slide['button_text']) . '
                                    </a>' : '') . '
                                    <button onclick="quickBuy(5, \'Featured Prize\')" class="flex items-center gap-2 bg-white/20 hover:bg-white/30 text-white font-heading font-bold text-base md:text-lg px-6 py-2.5 rounded-full shadow-ps transition focus:ring-2 focus:ring-white">
                                        <i class="fa-solid fa-tickets"></i> RM5 - Better Odds
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 text-white/90 text-xs md:text-sm">
                                <span class="inline-block bg-white/20 rounded px-2 py-1 mr-2">🎯 87% buy 1-5 tickets</span>
                                <span class="inline-block bg-white/20 rounded px-2 py-1">⚡ 234 people viewing</span>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        }
        ?>
      </div>
      
      <!-- Navigation Arrows -->
      <?php if (count($bannerSlides) > 1): ?>
      <button id="prevSlide" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/30 text-white rounded-full w-12 h-12 flex items-center justify-center backdrop-blur-sm transition-all z-20">
        <i class="fa-solid fa-chevron-left text-xl"></i>
      </button>
      <button id="nextSlide" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/30 text-white rounded-full w-12 h-12 flex items-center justify-center backdrop-blur-sm transition-all z-20">
        <i class="fa-solid fa-chevron-right text-xl"></i>
      </button>
      <?php endif; ?>
      
      <!-- Dots Indicator -->
      <?php if (count($bannerSlides) > 1): ?>
      <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2 z-20">
        <?php foreach ($bannerSlides as $index => $slide): ?>
        <button class="slide-dot w-3 h-3 rounded-full bg-white/50 hover:bg-white/80 transition-all <?php echo $index === 0 ? 'bg-white' : ''; ?>" data-slide="<?php echo $index; ?>"></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- 3. Stats + Daily Check-in (Compact Enhanced Two-Zone Layout, Improved Readability) -->
  <?php if ($currentUser): ?>
  <section class="w-full max-w-7xl mx-auto px-2 md:px-8 mt-8 mb-8">
    <div class="flex flex-col md:flex-row gap-4">
      <!-- User Info Card (Compact) -->
      <div class="flex-1 bg-white rounded-2xl shadow-ps-lg flex flex-col p-4 gap-2 min-w-0 relative overflow-hidden liquid-glass-card">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-5">
          <div class="absolute top-0 left-0 w-28 h-28 bg-ps-blue rounded-full -translate-y-14 -translate-x-14"></div>
          <div class="absolute bottom-0 right-0 w-20 h-20 bg-ps-yellow rounded-full translate-y-10 translate-x-10"></div>
          <div class="absolute top-1/2 right-0 w-16 h-16 bg-ps-pink rounded-full translate-x-8 -translate-y-8"></div>
        </div>
        
        <!-- Header Row -->
        <div class="flex items-center gap-3 mb-1 relative z-10">
          <div class="w-10 h-10 rounded-full bg-ps-blue/10 flex items-center justify-center text-xl text-ps-blue">
            <i class="fa-solid fa-user"></i>
          </div>
          <div class="font-heading font-bold text-base text-ps-text truncate">
            <?php if ($currentUser): ?>
              Hi, <?= htmlspecialchars($currentUser['name']) ?>!
            <?php else: ?>
              Welcome, Guest!
            <?php endif; ?>
          </div>
          <?php if ($currentUser): ?>
            <a href="dashboard.php" class="ml-auto text-xs text-ps-blue hover:underline font-semibold">My Dashboard</a>
          <?php else: ?>
            <button onclick="openLoginModal()" class="ml-auto text-xs text-ps-blue hover:underline font-semibold">Sign In</button>
          <?php endif; ?>
        </div>
        <!-- Wallet Row -->
        <div class="flex items-center gap-2 mb-1 relative z-10">
          <span class="text-lg font-bold text-ps-blue flex items-center gap-1">
            <i class="fa-solid fa-wallet mr-1"></i>
            RM<?= $currentUser ? number_format($currentUser['wallet_balance'] ?? 0, 2) : '0.00' ?>
          </span>
          <?php if ($currentUser): ?>
            <button onclick="openWalletTopupModal()" class="bg-ps-blue hover:bg-ps-light text-white text-xs font-bold px-2 py-0.5 rounded-full shadow transition">Top Up</button>
            <button class="bg-gray-100 hover:bg-ps-blue/10 text-ps-blue text-xs font-bold px-2 py-0.5 rounded-full shadow transition">Withdraw</button>
          <?php else: ?>
            <button onclick="openLoginModal()" class="bg-ps-blue hover:bg-ps-light text-white text-xs font-bold px-2 py-0.5 rounded-full shadow transition">Login to Top Up</button>
          <?php endif; ?>
          <span class="text-xs text-ps-silver cursor-pointer ml-1" title="Use your wallet to buy tickets instantly."><i class="fa-solid fa-circle-info"></i></span>
        </div>
        <hr class="my-1 border-gray-100 relative z-10">
        <!-- Stats Row (Larger) -->
        <div class="flex justify-between items-end mb-2 mt-2 relative z-10">
          <?php if ($currentUser): ?>
            <div class="flex flex-col items-center group cursor-pointer" title="Tickets you've bought">
              <i class="fa-solid fa-ticket text-2xl text-ps-blue mb-1"></i>
              <span class="font-bold text-ps-blue text-lg leading-tight">
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
                $stmt->execute([$currentUser['id']]);
                echo $stmt->fetchColumn() ?: 0;
                ?>
              </span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Entries</span>
            </div>
            <div class="flex flex-col items-center group cursor-pointer" title="Prizes you've won">
              <i class="fa-solid fa-trophy text-2xl text-ps-blue mb-1"></i>
              <span class="font-bold text-ps-blue text-lg leading-tight">
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM winners WHERE user_id = ?");
                $stmt->execute([$currentUser['id']]);
                echo $stmt->fetchColumn() ?: 0;
                ?>
              </span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Wins</span>
            </div>
            <div class="flex flex-col items-center group cursor-pointer" title="Current check-in streak">
              <i class="fa-solid fa-fire text-2xl text-orange-500 mb-1"></i>
              <span class="font-bold text-orange-500 text-lg leading-tight"><?= $currentUser['current_streak'] ?? 0 ?></span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Streak</span>
            </div>
            <div class="flex flex-col items-center group cursor-pointer" title="Points for exclusive rewards">
              <i class="fa-solid fa-star text-2xl text-ps-yellow mb-1"></i>
              <span class="font-bold text-ps-yellow text-lg leading-tight"><?= number_format($currentUser['loyalty_points']) ?></span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Loyalty</span>
            </div>
          <?php else: ?>
            <div class="flex flex-col items-center opacity-50">
              <i class="fa-solid fa-ticket text-2xl text-ps-blue mb-1"></i>
              <span class="font-bold text-ps-blue text-lg leading-tight">--</span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Entries</span>
            </div>
            <div class="flex flex-col items-center opacity-50">
              <i class="fa-solid fa-trophy text-2xl text-ps-blue mb-1"></i>
              <span class="font-bold text-ps-blue text-lg leading-tight">--</span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Wins</span>
            </div>
            <div class="flex flex-col items-center opacity-50">
              <i class="fa-solid fa-fire text-2xl text-orange-500 mb-1"></i>
              <span class="font-bold text-orange-500 text-lg leading-tight">--</span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Streak</span>
            </div>
            <div class="flex flex-col items-center opacity-50">
              <i class="fa-solid fa-star text-2xl text-ps-yellow mb-1"></i>
              <span class="font-bold text-ps-yellow text-lg leading-tight">--</span>
              <span class="text-xs text-ps-silver font-semibold mt-0.5">Loyalty</span>
            </div>
          <?php endif; ?>
        </div>
        <!-- Action Row -->
        <div class="flex gap-2 mt-1 relative z-10">
          <a href="#" class="bg-ps-blue hover:bg-ps-light text-white font-bold px-3 py-1 rounded-full shadow transition text-xs flex items-center gap-1"><i class="fa-solid fa-ticket"></i> Buy</a>
          <a href="#" class="bg-gray-100 hover:bg-ps-blue/10 text-ps-blue font-bold px-3 py-1 rounded-full shadow transition text-xs flex items-center gap-1"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        </div>
      </div>
              <!-- Daily Check-in Card (Enhanced UI/UX) -->
        <div class="flex-1 bg-white rounded-2xl shadow-ps-lg flex flex-col p-4 gap-3 min-w-0 relative overflow-hidden liquid-glass-card">
          <!-- Background Pattern -->
          <div class="absolute inset-0 opacity-5">
            <div class="absolute top-0 right-0 w-32 h-32 bg-ps-pink rounded-full -translate-y-16 translate-x-16"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-ps-blue rounded-full translate-y-12 -translate-x-12"></div>
          </div>
          
          <!-- Header Row -->
          <div class="flex items-center gap-2 mb-1 relative z-10">
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 bg-red-500 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-gift text-white text-sm"></i>
              </div>
              <span class="font-heading font-bold text-base">Daily <span class="text-red-600">Check-in</span> Rewards</span>
            </div>
            <div class="ml-auto flex items-center gap-2">
              <span class="bg-red-50 text-red-600 font-bold px-3 py-1 rounded-full text-xs flex items-center gap-1 border border-red-200">
                <i class="fa-solid fa-fire animate-pulse"></i> 
                <span id="current-streak">5</span>d streak
              </span>
              <span class="bg-gray-100 text-xs text-ps-text px-2 py-1 rounded-full font-mono">
                <i class="fa-solid fa-clock text-ps-silver mr-1"></i>
                <span id="checkin-countdown">00:00:00</span>
              </span>
            </div>
          </div>
          
          <!-- Days Row (Enhanced with better visual states) -->
          <div class="grid grid-cols-7 gap-1 w-full mb-3 mt-2 relative z-10" id="checkin-calendar">
            <!-- Will be populated by JavaScript -->
          </div>
          
          <!-- Progress bar for week completion (Enhanced) -->
          <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden mb-3 relative z-10">
            <div class="h-full bg-red-500 rounded-full transition-all duration-700 relative" id="streak-progress" style="width: 71%">
              <div class="absolute inset-0 bg-white/20 rounded-full animate-pulse"></div>
            </div>
            <div class="text-xs text-ps-silver mt-1 text-center">
              <span id="progress-text">5 of 7 days this week</span>
            </div>
          </div>
          
          <!-- Reward and Check-in Row (Enhanced) -->
          <div class="w-full flex items-center justify-between gap-3 relative z-10">
            <div class="flex flex-col">
              <span class="text-red-600 font-heading font-bold text-sm flex items-center gap-1">
                <i class="fa-solid fa-star text-ps-yellow"></i> 
                Today's Reward
              </span>
              <span class="font-bold text-ps-text" id="today-reward">200 Loyalty Points</span>
              <span class="text-xs text-ps-silver" id="next-reward-preview">Tomorrow: 25 pts + Free Spin</span>
            </div>
            <div class="flex flex-col items-end gap-1">
              <button id="checkin-btn" class="bg-ps-pink hover:bg-ps-blue text-white font-heading font-bold px-6 py-2.5 rounded-xl shadow-ps transition-all duration-300 text-sm flex items-center gap-2 min-w-[8rem] justify-center transform hover:scale-105">
                <i class="fa-solid fa-gift text-white"></i>
                <span class="text-white">Check-in</span>
              </button>
              <a href="dashboard.php" class="text-xs text-ps-blue hover:text-ps-pink transition-colors">View History →</a>
            </div>
          </div>
          
          <!-- Missed Day Recovery Hint (Hidden by default) -->
          <div id="recovery-hint" class="hidden bg-yellow-50 border border-yellow-200 rounded-lg p-3 relative z-10">
            <div class="flex items-start gap-2">
              <i class="fa-solid fa-lightbulb text-yellow-500 mt-0.5"></i>
              <div class="flex-1">
                <p class="text-sm font-medium text-yellow-800">Missed a day? No worries!</p>
                <p class="text-xs text-yellow-700 mt-1">Your streak resets, but you can start building it again today. Consistency is key! 🎯</p>
              </div>
            </div>
          </div>
        </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- 4. How It Works Trio (REMOVED) -->

  <!-- 5. Category & Items Section (Premium Business Design) -->
  <div id="raffle-section" class="max-w-7xl mx-auto px-2 md:px-8 mt-10 mb-24">
    <!-- Section Header -->
    <div class="text-center mb-8">
      <h2 class="font-heading text-3xl md:text-4xl font-bold text-gray-900 mb-2">Explore Categories</h2>
      <p class="text-gray-600 text-lg">Discover amazing prizes across different categories</p>
    </div>
    <!-- Premium Business Category Tab Bar -->
    <nav id="category-bar" class="premium-category-bar relative flex gap-2 overflow-x-auto snap-x snap-mandatory mb-8 w-full scrollbar-hide" role="tablist">
      <!-- ← left fade -->
      <span class="cat-fade fade-left"></span>
      <!-- underline marker -->
      <span class="premium-underline cat-underline"></span>
      <?php
        $specialCats = [
          ['name' => 'Just For U', 'icon' => 'fa-heart'],
          ['name' => 'Hot Products', 'icon' => 'fa-fire'],
          ['name' => 'Selling Fast', 'icon' => 'fa-bolt']
        ];
        $allCats = array_merge($specialCats, $categoriesWithRaffles);
        foreach ($allCats as $i => $cat):
      ?>
        <button class="premium-category-btn category-btn flex items-center px-4 md:px-6 py-3 md:py-4 relative flex-shrink-0 snap-start
          focus:outline-none focus:ring-2 focus:ring-ps-blue"
          data-category="<?php echo htmlspecialchars($cat['name']); ?>"
          data-index="<?php echo $i; ?>"
          role="tab"
          aria-selected="<?php echo ($i === 0) ? 'true' : 'false'; ?>"
          aria-label="Category: <?php echo htmlspecialchars($cat['name']); ?>"
          tabindex="<?php echo ($i === 0) ? '0' : '-1'; ?>">
          <i class="fa-solid <?php echo $cat['icon']; ?>"></i>
          <span class="font-heading"><?php echo htmlspecialchars($cat['name']); ?></span>
        </button>
      <?php endforeach; ?>
      <!-- → right fade -->
      <span class="cat-fade fade-right"></span>
    </nav>
    <!-- Items Grid -->
    <div class="w-full">
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
          <div class="w-1 h-8 bg-gradient-to-b from-ps-blue to-ps-light rounded-full"></div>
          <h3 class="font-heading text-xl font-semibold text-gray-800">Featured Raffles</h3>
        </div>
        <div class="flex items-center gap-2 text-sm text-gray-500">
          <i class="fa-solid fa-fire text-orange-500"></i>
          <span>Live & Active</span>
        </div>
      </div>
      <div id="raffle-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 lg:gap-8">
        <?php
        foreach ($raffles as $raffle):
          if ($raffle['sold'] >= $raffle['total']) continue; // Skip sold out
          $percent = ($raffle['sold'] / $raffle['total']) * 100;
          $remaining = $raffle['total'] - $raffle['sold'];
          $almostSoldOut = $remaining > 0 && $remaining <= 50;
          $badge = $raffle['badge'] ?? null;
          $badgeClass = '';
          $badgeText = '';
          $badgeIcon = '';
          if ($badge === 'sellingFast') {
            $badgeClass = 'bg-ps-yellow/80 text-ps-text'; $badgeText = 'Selling Fast'; $badgeIcon = 'fa-bolt';
          } elseif ($badge === 'promo') {
            $badgeClass = 'bg-ps-light/90 text-white'; $badgeText = 'Promo'; $badgeIcon = 'fa-tag';
          } elseif ($badge === 'limited') {
            $badgeClass = 'bg-red-600/90 text-white'; $badgeText = 'Limited'; $badgeIcon = 'fa-fire';
          } elseif ($badge === 'new') {
            $badgeClass = 'bg-green-500/90 text-white'; $badgeText = 'New'; $badgeIcon = 'fa-star';
          }
          $img = (isset($raffle['image_url']) && $raffle['image_url'] && is_file(__DIR__.'/'.$raffle['image_url'])) ? $raffle['image_url'] : 'images/placeholder.jpg';
        ?>
        <div class="raffle-card liquid-glass-card floating-card group relative rounded-3xl overflow-hidden flex flex-col items-center min-h-[400px] cursor-pointer" role="group" aria-label="<?php echo htmlspecialchars($raffle['title']); ?>, ticket RM<?php echo number_format($raffle['price'],2); ?>">
                      <?php if ($badge): ?>
              <span class="liquid-badge absolute top-4 left-4 z-10 px-4 py-1 rounded-full font-bold text-xs <?php echo $badgeClass; ?> flex items-center gap-1" style="font-family: 'Inter',sans-serif;">
                <i class="fa-solid <?php echo $badgeIcon; ?> mr-1"></i>
                <?php echo $badgeText; ?>
              </span>
            <?php endif; ?>
          <div class="w-full flex justify-center items-center mt-8 mb-3">
            <div class="liquid-image-container product-img-container rounded-2xl h-32 w-32 transition-all duration-200 overflow-hidden" data-product="<?php echo htmlspecialchars(json_encode([
                "id" => $raffle["id"],
                "title" => $raffle["title"],
                "image_url" => $img,
                "price" => $raffle["price"],
                "sold" => $raffle["sold"],
                "total" => $raffle["total"],
                "category" => $raffle["category"],
                "category_icon" => $raffle["category_icon"],
                "badge" => $raffle["badge"],
                "desc" => ""
            ], JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8"); ?>">
              <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($raffle['title']); ?>" class="product-img w-full h-full object-cover group-hover:scale-105 transition-all duration-200" onerror="this.onerror=null;this.src='images/placeholder.jpg';">
              <span class="info-icon-overlay"><i class="fa-solid fa-circle-info"></i></span>
            </div>
          </div>
          <div class="flex-1 flex flex-col items-center justify-center w-full px-3 pb-4">
            <span class="text-xs text-ps-silver mb-1 font-medium tracking-wide"><?php echo htmlspecialchars($raffle['category']); ?></span>
            <h3 class="text-lg md:text-xl font-extrabold mb-1 text-ps-text font-heading text-center leading-tight"><?php echo htmlspecialchars($raffle['title']); ?></h3>
            <div class="w-full flex items-center gap-2 mb-2">
                              <span class="bg-white text-ps-blue font-bold rounded-full px-4 py-1 text-xs md:text-sm border border-ps-blue shadow-sm">RM1</span>
            </div>
            <div class="w-full flex items-center gap-2 mb-1">
              <div class="liquid-progress flex-1 rounded-full h-3 overflow-hidden relative">
                <div class="bg-gradient-to-r from-ps-blue to-ps-light h-3 rounded-full shadow-ps transition-all duration-700" style="width: <?php echo $percent; ?>%;"></div>
              </div>
              <span class="text-xs font-bold text-ps-blue ml-2 min-w-[32px]"><?php echo round($percent); ?>%</span>
            </div>
            <p class="text-xs text-ps-silver mb-1 text-center font-medium">
              <?php echo $raffle['sold']; ?> of <?php echo $raffle['total']; ?> sold
              <?php if ($almostSoldOut): ?>
                <span class="ml-2 text-ps-blue font-bold">Only <?php echo $remaining; ?> left!</span>
              <?php endif; ?>
            </p>
            <div class="w-full px-4 pb-4 mt-auto">
              <?php if ($currentUser): ?>
              <form action="buy.php" method="POST"
                    class="w-full grid grid-cols-[30%_70%] gap-2 px-4 py-3 bg-slate-50/60 backdrop-blur border-t border-slate-100">
                <input type="hidden" name="raffle_id" value="<?php echo $raffle['id']; ?>">

                <!-- Quantity input, full height, 30% width -->
                <input name="quantity" type="number" value="1" min="1" max="<?php echo $remaining; ?>"
                       class="w-full h-full rounded-xl border border-ps-blue/30 text-center text-sm font-bold text-ps-blue py-0.5 focus:ring-2 focus:ring-ps-blue/30 outline-none"/>

                <!-- Button stack, 70% width -->
                <div class="flex flex-col gap-2 h-full">
                  <button type="submit"
                          class="glass-button w-full flex items-center justify-center gap-1 bg-ps-blue text-white rounded-xl font-heading font-bold text-sm py-1 shadow hover:bg-ps-light/90 transition">
                    <i class="fa-solid fa-ticket"></i> Buy
                  </button>
                  <button type="submit" name="quantity" value="<?php echo $remaining; ?>"
                          class="glass-button w-full flex items-center justify-center gap-1 bg-yellow-400 text-gray-900 font-heading font-bold text-sm py-1 rounded-xl shadow hover:bg-yellow-500 transition">
                    <i class="fa-solid fa-bolt"></i> Buy All (<?php echo $remaining; ?>)
                  </button>
                </div>
              </form>
              <?php else: ?>
              <!-- Guest CTA Buttons -->
              <div class="w-full px-4 py-3 bg-slate-50/60 backdrop-blur border-t border-slate-100">
                <div class="flex flex-col gap-2">
                  <button onclick="openLoginModal()" 
                          class="w-full flex items-center justify-center gap-1 bg-ps-blue text-white rounded-xl font-heading font-bold text-sm py-2 shadow hover:bg-ps-light/90 transition">
                    <i class="fa-solid fa-sign-in-alt"></i> Login to Buy
                  </button>
                  <button onclick="openRegisterModal()" 
                          class="w-full flex items-center justify-center gap-1 bg-ps-yellow text-ps-text rounded-xl font-heading font-bold text-sm py-2 shadow hover:bg-yellow-400 transition">
                    <i class="fa-solid fa-user-plus"></i> Sign Up & Get Bonus!
                  </button>
                </div>
                <p class="text-xs text-center text-ps-silver mt-2">Join free to start winning amazing prizes!</p>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- 6. Trending & Your History -->
  <section class="w-full max-w-7xl mx-auto px-2 md:px-8 mb-16">
    <div class="flex flex-col md:flex-row gap-8">
      <!-- Trending Raffles -->
      <div class="flex-1 bg-white rounded-2xl shadow-ps p-6 flex flex-col mb-6 md:mb-0">
        <h2 class="font-heading text-xl md:text-2xl font-bold text-ps-text mb-4 flex items-center gap-2">
          <i class="fa-solid fa-fire text-ps-blue"></i> Trending Raffles
        </h2>
        <ul class="divide-y divide-ps-silver/30">
          <li class="py-4 flex items-center gap-4">
            <img src="images/ps5.jpg" alt="PS5" class="w-14 h-14 rounded-xl object-cover bg-gray-100 border border-ps-silver/30" />
            <div class="flex-1">
              <div class="font-heading font-bold text-ps-blue text-base">PlayStation 5</div>
              <div class="text-xs text-ps-silver">RM3,000 • 800/1000 sold</div>
            </div>
            <a href="#" class="bg-ps-blue hover:bg-ps-light text-white font-heading font-bold px-4 py-2 rounded-xl shadow-ps text-xs transition">View</a>
          </li>
          <li class="py-4 flex items-center gap-4">
            <img src="images/iphone.jpg" alt="iPhone" class="w-14 h-14 rounded-xl object-cover bg-gray-100 border border-ps-silver/30" />
            <div class="flex-1">
              <div class="font-heading font-bold text-ps-blue text-base">iPhone 14</div>
              <div class="text-xs text-ps-silver">RM2,000 • 1200/2000 sold</div>
            </div>
            <a href="#" class="bg-ps-blue hover:bg-ps-light text-white font-heading font-bold px-4 py-2 rounded-xl shadow-ps text-xs transition">View</a>
          </li>
          <li class="py-4 flex items-center gap-4">
            <img src="images/popmart.jpg" alt="Popmart" class="w-14 h-14 rounded-xl object-cover bg-gray-100 border border-ps-silver/30" />
            <div class="flex-1">
              <div class="font-heading font-bold text-ps-blue text-base">Popmart Molly</div>
              <div class="text-xs text-ps-silver">RM150 • 90/200 sold</div>
            </div>
            <a href="#" class="bg-ps-blue hover:bg-ps-light text-white font-heading font-bold px-4 py-2 rounded-xl shadow-ps text-xs transition">View</a>
          </li>
        </ul>
      </div>
      <!-- User's Recent Entries -->
      <div class="flex-1 bg-white rounded-2xl shadow-ps p-6 flex flex-col">
        <h2 class="font-heading text-xl md:text-2xl font-bold text-ps-text mb-4 flex items-center gap-2">
          <i class="fa-solid fa-clock-rotate-left text-ps-light"></i> Your History
        </h2>
        <ul class="divide-y divide-ps-silver/30">
          <li class="py-4 flex items-center gap-4">
            <img src="images/ps5.jpg" alt="PS5" class="w-12 h-12 rounded-xl object-cover bg-gray-100 border border-ps-silver/30" />
            <div class="flex-1">
              <div class="font-heading font-bold text-ps-text text-base">PlayStation 5</div>
              <div class="text-xs text-ps-silver">2 tickets • 3 days ago</div>
            </div>
            <a href="#" class="bg-ps-blue hover:bg-ps-light text-white font-heading font-bold px-4 py-2 rounded-xl shadow-ps text-xs transition">Buy Again</a>
          </li>
          <li class="py-4 flex items-center gap-4">
            <img src="images/iphone.jpg" alt="iPhone" class="w-12 h-12 rounded-xl object-cover bg-gray-100 border border-ps-silver/30" />
            <div class="flex-1">
              <div class="font-heading font-bold text-ps-text text-base">iPhone 14</div>
              <div class="text-xs text-ps-silver">1 ticket • 1 week ago</div>
            </div>
            <a href="#" class="bg-ps-blue hover:bg-ps-light text-white font-heading font-bold px-4 py-2 rounded-xl shadow-ps text-xs transition">Buy Again</a>
          </li>
        </ul>
      </div>
    </div>
  </section>

  <!-- 7. Live Draw Teaser -->
  <section id="live-draw-section" class="w-full max-w-7xl mx-auto px-2 md:px-8 mb-16">
    <div class="bg-[#10182A] rounded-2xl shadow-ps p-8 flex flex-col md:flex-row items-center justify-between gap-8 relative overflow-hidden">
      <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-ps-blue/30 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 right-0 w-32 h-32 bg-ps-light/20 rounded-full blur-2xl"></div>
        </div>
      <div class="flex-1 z-10 flex flex-col items-start">
        <h2 class="font-heading text-2xl md:text-3xl font-bold text-white mb-3 flex items-center gap-2 drop-shadow-[0_2px_8px_rgba(0,112,209,0.4)]">
          <i class="fa-solid fa-bolt text-ps-light"></i> Live Draw Happening Soon!
        </h2>
        <div class="flex items-center gap-3 mb-4">
          <i class="fa-regular fa-clock text-ps-light text-xl"></i>
          <span class="text-white/80 text-base md:text-lg font-semibold">Next Draw In</span>
          <span id="live-draw-countdown" class="font-mono text-ps-light text-lg md:text-2xl font-bold"></span>
        </div>
        <a href="#" class="mt-2 bg-ps-blue hover:bg-ps-light text-white font-heading font-bold text-lg px-8 py-3 rounded-2xl shadow-ps transition focus:ring-2 focus:ring-ps-light flex items-center gap-2 animate-pulse">
          <i class="fa-solid fa-play text-white"></i> Watch Live
        </a>
        <div class="mt-5 flex gap-4 items-center">
          <span class="text-white/60 text-sm">Share:</span>
          <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-facebook-f text-xl"></i></a>
          <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-twitter text-xl"></i></a>
          <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-telegram text-xl"></i></a>
          <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-whatsapp text-xl"></i></a>
        </div>
      </div>
      <div class="flex-1 flex justify-center items-center z-10">
        <img src="images/ps5.jpg" alt="Live Draw" class="h-40 md:h-56 w-auto rounded-xl shadow-ps border-4 border-ps-blue/30 object-contain bg-white/5" />
      </div>
    </div>
  </section>

  <!-- 8. Testimonials & Trust (remove testimonials carousel, keep trust badges and certificate link) -->
  <section class="w-full max-w-7xl mx-auto px-2 md:px-8 mb-20">
    <div class="flex flex-col items-center justify-center">
      <h2 class="font-heading text-2xl md:text-3xl font-bold text-ps-text mb-6 flex items-center gap-2">
        <i class="fa-solid fa-shield-halved text-ps-blue"></i> Trust & Security
      </h2>
      <div class="flex flex-wrap gap-6 justify-center items-center mb-4">
        <div class="flex flex-col items-center">
          <i class="fa-solid fa-lock text-3xl text-ps-blue mb-1"></i>
          <span class="text-xs text-ps-silver">SSL Secured</span>
            </div>
        <div class="flex flex-col items-center">
          <i class="fa-solid fa-certificate text-3xl text-ps-light mb-1"></i>
          <span class="text-xs text-ps-silver">Licensed</span>
            </div>
        <div class="flex flex-col items-center">
          <i class="fa-solid fa-shield text-3xl text-ps-blue mb-1"></i>
          <span class="text-xs text-ps-silver">PCI DSS</span>
            </div>
        <div class="flex flex-col items-center">
          <i class="fa-solid fa-medal text-3xl text-ps-light mb-1"></i>
          <span class="text-xs text-ps-silver">Fairness</span>
        </div>
      </div>
      <a href="#" class="text-ps-blue font-heading font-bold underline hover:text-ps-light transition text-sm">View Certificate of Fairness</a>
    </div>
  </section>

  <!-- 9. Footer -->
  <footer class="w-full bg-white border-t border-ps-silver mt-16 pt-12 pb-8 px-2 md:px-8 font-sans">
    <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-10 mb-8">
      <!-- About -->
      <div>
        <h3 class="font-heading text-lg font-bold text-ps-blue mb-3 tracking-tight">RaffLah!</h3>
        <p class="text-ps-text text-sm mb-4">Your trusted platform for exciting raffles and amazing prizes. Play fair, win big, and join our community of happy winners!</p>
        <div class="flex gap-3 mt-2">
          <a href="#" class="text-ps-blue hover:text-ps-light transition"><i class="fa-brands fa-facebook-f text-xl"></i></a>
          <a href="#" class="text-ps-blue hover:text-ps-light transition"><i class="fa-brands fa-twitter text-xl"></i></a>
          <a href="#" class="text-ps-blue hover:text-ps-light transition"><i class="fa-brands fa-instagram text-xl"></i></a>
          <a href="#" class="text-ps-blue hover:text-ps-light transition"><i class="fa-brands fa-telegram text-xl"></i></a>
                </div>
                </div>
      <!-- Quick Links -->
      <div>
        <h4 class="font-heading text-base font-bold text-ps-text mb-3">Quick Links</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">About</a></li>
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">FAQ</a></li>
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">Support</a></li>
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">Terms & Conditions</a></li>
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">Privacy Policy</a></li>
        </ul>
                </div>
      <!-- Support -->
      <div>
        <h4 class="font-heading text-base font-bold text-ps-text mb-3">Support</h4>
        <ul class="space-y-2 text-sm">
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">Contact Us</a></li>
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">Live Chat</a></li>
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">Responsible Gaming</a></li>
          <li><a href="#" class="text-ps-silver hover:text-ps-blue transition">Refund Policy</a></li>
        </ul>
                </div>
      <!-- Newsletter Signup -->
      <div>
        <h4 class="font-heading text-base font-bold text-ps-text mb-3">Newsletter</h4>
        <p class="text-ps-silver text-sm mb-3">Get the latest raffles, winners, and deals. No spam, ever.</p>
        <form class="flex gap-2">
          <input type="email" placeholder="Your email" class="flex-1 px-3 py-2 rounded-full border border-ps-silver focus:ring-2 focus:ring-ps-blue text-sm outline-none" required />
          <button type="submit" class="bg-ps-blue hover:bg-ps-light text-white font-heading font-bold px-5 py-2 rounded-full shadow-ps transition">Subscribe</button>
        </form>
            </div>
        </div>
    <div class="max-w-7xl mx-auto text-center text-xs text-ps-silver pt-4 border-t border-ps-silver/30">&copy; <?php echo date('Y'); ?> RaffLah!. All rights reserved.</div>
  </footer>

  <!-- 10. Mobile Bottom Navigation -->
  <nav class="fixed bottom-0 left-0 right-0 z-30 bg-white/80 border-t border-ps-silver shadow-lg flex justify-around items-center py-2 md:hidden">
    <a href="#" class="flex flex-col items-center text-ps-blue">
      <i class="fa-solid fa-house text-xl"></i>
      <span class="text-xs mt-1">Home</span>
    </a>
    <a href="#" class="flex flex-col items-center text-ps-silver">
      <i class="fa-regular fa-compass text-xl"></i>
      <span class="text-xs mt-1">Nearby</span>
    </a>
    <a href="#" class="flex flex-col items-center">
      <div class="bg-ps-blue rounded-full p-3 -mt-6 shadow-ps"><i class="fa-solid fa-qrcode text-white text-2xl"></i></div>
      <span class="text-xs mt-1 text-ps-blue font-bold">Scan & Pay</span>
    </a>
    <a href="#" class="flex flex-col items-center text-ps-silver">
      <i class="fa-regular fa-heart text-xl"></i>
      <span class="text-xs mt-1">My Faves</span>
    </a>
    <a href="#" class="flex flex-col items-center text-ps-silver">
      <i class="fa-regular fa-user text-xl"></i>
      <span class="text-xs mt-1">Me</span>
    </a>
  </nav>

  <!-- 11. Live Draw Popup Modal -->
  <div id="live-draw-popup" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
    <div class="bg-[#10182A] rounded-2xl shadow-2xl max-w-6xl w-full mx-4 relative overflow-hidden">
      <!-- Close button -->
      <button id="live-popup-close" class="absolute top-4 right-4 z-20 bg-white/10 hover:bg-white/20 text-white rounded-full w-10 h-10 flex items-center justify-center transition backdrop-blur-sm">
        <i class="fa-solid fa-times text-lg"></i>
      </button>
      
      <!-- Background effects (same as original) -->
      <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-10 -left-10 w-40 h-40 bg-ps-blue/30 rounded-full blur-2xl"></div>
        <div class="absolute bottom-0 right-0 w-32 h-32 bg-ps-light/20 rounded-full blur-2xl"></div>
      </div>
      
      <div class="p-8 flex flex-col md:flex-row items-center justify-between gap-8 relative">
        <div class="flex-1 z-10 flex flex-col items-start">
          <!-- Live indicator -->
          <div class="flex items-center gap-2 mb-4">
            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
            <span class="text-white/80 text-sm font-bold uppercase tracking-wide">LIVE NOW</span>
          </div>
          
          <h2 class="font-heading text-2xl md:text-3xl font-bold text-white mb-3 flex items-center gap-2 drop-shadow-[0_2px_8px_rgba(0,112,209,0.4)]">
            <i class="fa-solid fa-bolt text-ps-light"></i> Live Draw Happening Soon!
          </h2>
          
          <div class="flex items-center gap-3 mb-4">
            <i class="fa-regular fa-clock text-ps-light text-xl"></i>
            <span class="text-white/80 text-base md:text-lg font-semibold">Next Draw In</span>
            <span id="live-draw-countdown" class="font-mono text-ps-light text-lg md:text-2xl font-bold"></span>
          </div>
          
          <!-- Additional info -->
          <div class="flex items-center gap-3 mb-6">
            <i class="fa-solid fa-users text-ps-light text-xl"></i>
            <span class="text-white/80 text-base"><span id="live-viewers" class="font-bold text-white">2,847</span> people watching</span>
          </div>
          
          <a href="#" id="join-live-btn" class="mt-2 bg-ps-blue hover:bg-ps-light text-white font-heading font-bold text-lg px-8 py-3 rounded-2xl shadow-ps transition focus:ring-2 focus:ring-ps-light flex items-center gap-2 animate-pulse">
            <i class="fa-solid fa-play text-white"></i> Watch Live
          </a>
          
          <button id="maybe-later-btn" class="mt-3 text-white/60 hover:text-white text-sm transition">
            Maybe later
          </button>
          
          <div class="mt-5 flex gap-4 items-center">
            <span class="text-white/60 text-sm">Share:</span>
            <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-facebook-f text-xl"></i></a>
            <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-twitter text-xl"></i></a>
            <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-telegram text-xl"></i></a>
            <a href="#" class="text-ps-light hover:text-white transition"><i class="fa-brands fa-whatsapp text-xl"></i></a>
          </div>
        </div>
        
        <div class="flex-1 flex justify-center items-center z-10">
          <img src="images/ps5.jpg" alt="Live Draw" class="h-40 md:h-56 w-auto rounded-xl shadow-ps border-4 border-ps-blue/30 object-contain bg-white/5" />
        </div>
      </div>
    </div>
  </div>

  <!-- 12. Brand Modal -->
  <div id="brand-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl shadow-ps-lg max-w-6xl w-full h-[80vh] mx-4 relative flex flex-col">
      <div class="flex items-center justify-between p-6 border-b border-gray-200">
        <div class="flex items-center gap-3">
          <h2 id="brand-modal-title" class="font-heading text-2xl font-bold text-ps-text">Sony Products</h2>
          <span id="brand-modal-count" class="bg-ps-blue/10 text-ps-blue px-3 py-1 rounded-full text-sm font-semibold">8 items</span>
        </div>
        <button id="brand-modal-close" class="text-ps-blue hover:text-ps-pink text-2xl">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      
      <div class="flex-1 overflow-y-auto p-6">
                 <div id="brand-modal-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 lg:gap-8">
          <!-- Brand items will be populated here -->
        </div>
      </div>
    </div>
  </div>

  <!-- 13. Product Modal -->
  <div id="product-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl shadow-ps-lg max-w-md w-full p-6 relative animate-fade-in">
      <button id="modal-close" class="absolute top-3 right-3 text-ps-blue hover:text-ps-pink text-2xl"><i class="fa-solid fa-xmark"></i></button>
      <div class="flex flex-col items-center">
        <img id="modal-img" src="" alt="Product" class="w-40 h-40 object-contain rounded-xl mb-4 bg-gray-100" />
        <h2 id="modal-title" class="font-heading text-xl font-bold mb-2 text-center"></h2>
        <div class="flex items-center gap-2 mb-2">
          <span id="modal-badge" class="hidden px-3 py-1 rounded-full text-xs font-semibold"></span>
          <span id="modal-category" class="flex items-center gap-1 text-ps-silver text-xs"><i id="modal-category-icon" class="fa-solid"></i> <span></span></span>
                        </div>
        <div class="flex items-center gap-3 mb-2">
          <span id="modal-price" class="bg-white text-ps-blue font-bold rounded-full px-4 py-1 text-sm border border-ps-blue shadow-sm"></span>
          <span id="modal-sold" class="text-xs text-ps-silver"></span>
        </div>
        <p id="modal-desc" class="text-sm text-ps-text text-center mb-2"></p>
        <button class="mt-4 bg-ps-blue hover:bg-ps-pink text-white font-heading font-bold px-6 py-2 rounded-full shadow-ps transition">Buy Now</button>
      </div>
                        </div>
                    </div>

  <script>
const modal = document.getElementById('product-modal');
const closeBtn = document.getElementById('modal-close');

/* ---------- GLOBAL DATA ---------- */
const raffles = <?php echo json_encode($raffles); ?>;           // full raffle list
const catNames = <?php echo json_encode(array_column($categories,'name')); ?>;
const specialRaffles = <?php echo json_encode($specialRaffles); ?>;
const groupedRaffles = <?php echo json_encode($groupedRaffles); ?>;    // raffles grouped by brand

/* ---------- DOM HOOKS ---------- */
const grid     = document.getElementById('raffle-grid');
const catBtns  = [...document.querySelectorAll('.category-btn')];
const catBar   = document.getElementById('category-bar');
const underline = catBar.querySelector('.cat-underline');
const fadeLeft = catBar.querySelector('.fade-left');
const fadeRight = catBar.querySelector('.fade-right');

/* ---------- RENDER ONE CARD ---------- */
function cardHTML(r) {
  const pct       = Math.round((r.sold / r.total) * 100);
  const remain    = r.total - r.sold;
  const almostOut = remain > 0 && remain <= 50;
  const isUrgent = r.hours_remaining <= 24;
  const isSellingFast = pct >= 70;
  const isEarlyBird = pct < 30;

  // --- Enhanced badge system (blue/yellow/red only) ---
  let badges = [];
  if (r.badges && r.badges.length > 0) {
    badges = r.badges.map(badge => {
      const colors = {
        red: 'bg-red-500 text-white',
        yellow: 'bg-yellow-400 text-gray-900',
        blue: 'bg-ps-blue text-white',
      };
      // Only allow red, yellow, blue
      let colorClass = colors[badge.color] || colors.blue;
      // URGENT always red, RM1 ONLY always yellow
      if (badge.text === 'URGENT' || badge.text === 'Closing Soon') colorClass = colors.red;
      if (badge.text === 'RM1 ONLY!') colorClass = colors.yellow;
      return `<span class=\"liquid-badge px-3 py-1 rounded-full text-xs font-bold shadow-sm ${colorClass} mb-1 flex items-center gap-1\">${badge.text}</span>`;
    });
  }

  // --- RM1 Strategy triggers (blue/yellow/red only) ---
  const strategyTriggers = [];
  if (isUrgent) strategyTriggers.push('<span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class=\'fa-solid fa-clock\'></i> Closing Soon</span>');
  if (isSellingFast) strategyTriggers.push('<span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class=\'fa-solid fa-bolt\'></i> Selling Fast</span>');
  if (isEarlyBird) strategyTriggers.push('<span class="bg-yellow-50 text-yellow-700 px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class=\'fa-solid fa-seedling\'></i> Early Bird</span>');
  if (almostOut) strategyTriggers.push(`<span class="bg-ps-blue/10 text-ps-blue px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1"><i class=\'fa-solid fa-fire\'></i> Only ${remain} left</span>`);

  // --- Quick buy options (blue/yellow only) ---
  const quickBuyOptions = [
    { qty: 1, price: 1, label: 'Try Luck', color: 'bg-ps-blue hover:bg-ps-light', icon: 'fa-ticket' },
    { qty: 3, price: 3, label: 'Triple Chance', color: 'bg-yellow-400 hover:bg-yellow-300 text-ps-text', icon: 'fa-tickets' },
    { qty: 5, price: 5, label: 'Lucky Five', color: 'bg-ps-blue hover:bg-ps-light', icon: 'fa-star' }
  ];

  // Check if this raffle is in user's wishlist
  const wishlist = JSON.parse(localStorage.getItem('user_wishlist') || '[]');
  const isWishlisted = wishlist.includes(r.id);
  const heartIcon = isWishlisted ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
  const heartColor = isWishlisted ? 'text-red-500' : 'text-gray-400 hover:text-red-500';

  return `
  <article class="raffle-card liquid-glass-card floating-card group relative flex flex-col rounded-3xl overflow-hidden">
    <!-- Heart Icon (Top Right) -->
    <button class="wishlist-btn absolute top-4 right-4 z-20 bg-white/80 backdrop-blur-sm hover:bg-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg transition-all duration-300 transform hover:scale-110" 
            data-id="${r.id}" 
            data-raffle-title="${r.title}"
            onclick="toggleWishlist(${r.id}, '${r.title}')"
            title="${isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'}">
      <i class="${heartIcon} ${heartColor} text-lg transition-all duration-300"></i>
    </button>
    
    <!-- Badges -->
    ${badges.length > 0 ? `<div class="absolute top-4 left-4 z-10 flex flex-col gap-1">${badges.slice(0, 2).join('')}</div>` : ''}
    
    <!-- Image -->
    <div class="relative flex items-center justify-center mt-8 mb-4">
      <div class="liquid-image-container h-24 w-24 rounded-2xl grid place-content-center">
        <div class="product-img-container" data-product-id="${r.id}">
          <img src="${r.image_url || 'images/placeholder.jpg'}" alt="${r.title}" onerror="this.onerror=null;this.src='images/placeholder.jpg';" class="product-img object-cover h-20 w-20 rounded-xl transition-transform duration-200 group-hover:scale-105">
          <span class="info-icon-overlay"><i class="fa-solid fa-circle-info"></i></span>
        </div>
      </div>
    </div>
    <!-- Body -->
    <div class="flex-1 flex flex-col px-4 w-full">
      <p class="text-xs text-ps-silver mb-1 tracking-wide text-center font-medium">${r.brand_name}</p>
      <h3 class="text-lg font-heading font-extrabold text-ps-text text-center leading-snug mb-2 line-clamp-2">${r.title}</h3>
      <!-- Strategy Triggers -->
      ${strategyTriggers.length > 0 ? `<div class="flex flex-wrap gap-1 justify-center mb-2">${strategyTriggers.join('')}</div>` : ''}
      <!-- Progress Bar -->
      <div class="flex items-center gap-2 mb-1">
        <div class="liquid-progress flex-1 h-3 rounded-full overflow-hidden relative">
          <div class="h-full bg-gradient-to-r from-ps-blue to-ps-light rounded-full shadow-ps transition-all duration-700" style="width:${pct}%"></div>
        </div>
        <span class="text-xs font-bold text-ps-blue ml-2 min-w-[32px]">${pct}%</span>
      </div>
      <p class="text-xs text-ps-silver mb-1 text-center font-medium">${r.sold} of ${r.total} sold${almostOut ? `<span class=\"ml-2 text-ps-blue font-bold\">Only ${remain} left!</span>` : ''}</p>
      <!-- Social Proof -->
      ${r.social_proof_enabled ? `<div class="text-center mb-3"><span class="text-xs text-ps-blue bg-ps-blue/10 rounded-full px-2 py-1 font-semibold flex items-center gap-1 justify-center"><i class=\'fa-solid fa-users\'></i> ${Math.floor(Math.random() * 50) + 10} people viewing</span></div>` : ''}
    </div>
    <!-- Quick Buy & Custom Buy -->
    <div class="w-full px-4 py-3 bg-slate-50/60 backdrop-blur border-t border-slate-100">
      <div class="flex gap-2">
        <input type="number" id="qty-${r.id}" value="1" min="1" max="${remain}" class="w-16 text-center text-sm font-bold text-ps-blue border border-ps-blue/30 rounded-xl py-1 focus:ring-2 focus:ring-ps-blue/30 outline-none"/>
        <button onclick=\"customBuyTickets(${r.id}, document.getElementById('qty-${r.id}').value, '${r.title}')\" class=\"glass-button flex-1 flex items-center justify-center gap-1 bg-ps-blue text-white rounded-xl font-heading font-bold text-sm py-1 shadow hover:bg-ps-light/90 transition\"><i class=\"fa-solid fa-ticket\"></i> Buy</button>
      </div>
    </div>
  </article>`;
}

function render(cat){
  grid.innerHTML = Array(8).fill('<div class="animate-pulse bg-gray-200 rounded-3xl aspect-[3/4]"></div>').join('');
  setTimeout(()=>{
    if (specialRaffles[cat]) {
      // Special categories - show all items in grid
      let items = specialRaffles[cat].filter(r => r.sold < r.total);
      grid.innerHTML = items.map(cardHTML).join('');
    } else {
      // Regular categories - show by brands
      grid.innerHTML = renderBrandSections(cat);
    }
    bindProductCards();
  },150);
}

function getBrandImage(brandName, raffleData) {
  // Find the brand image from the raffle data
  const brandImageUrl = raffleData?.brand_image_url || '';
  
  if (brandImageUrl) {
    return `<img src="/raffle-demo/${brandImageUrl}" alt="${brandName} logo" class="w-8 h-8 object-contain rounded-lg border border-gray-200">`;
  } else {
    return `<div class="w-8 h-8 bg-gray-100 rounded-lg border border-gray-200 flex items-center justify-center">
      <i class="fa-solid fa-building text-gray-400 text-sm"></i>
    </div>`;
  }
}

function renderBrandSections(category) {
  if (!groupedRaffles[category]) {
    return '<div class="col-span-full text-center text-gray-500 py-8">No raffles available in this category</div>';
  }
  
  let html = '';
  const brands = groupedRaffles[category];
  
  // Check if this category has brand layout disabled (will have "All Items" group)
  const hasAllItemsGroup = brands.hasOwnProperty('All Items');
  
  Object.keys(brands).forEach(brand => {
    const brandData = brands[brand];
    const brandRaffles = brandData.raffles.filter(r => r.sold < r.total);
    if (brandRaffles.length === 0) return;
    
    if (hasAllItemsGroup && brand === 'All Items') {
      // For disabled brand layout, show all items in a simple grid without brand headers or view more
      html += `
        <div class="col-span-full">
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 lg:gap-8">
            ${brandRaffles.map(cardHTML).join('')}
          </div>
        </div>
      `;
    } else {
      // For enabled brand layout, show with brand headers and view more functionality
      const displayItems = brandRaffles.slice(0, 5);
      const hasMore = brandRaffles.length > 4;
      const isFeatured = brandData.featured;
      
      html += `
        <div class="col-span-full mb-8">
          <!-- Brand Header -->
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              ${getBrandImage(brand, brandRaffles[0])}
              <h3 class="font-heading text-xl font-bold text-ps-text">${brand}</h3>
              ${isFeatured ? '<span class="bg-yellow-400 text-yellow-900 px-2 py-1 rounded-full text-xs font-semibold">⭐ Featured</span>' : ''}
              <span class="bg-ps-blue/10 text-ps-blue px-2 py-1 rounded-full text-xs font-semibold">
                ${brandRaffles.length} item${brandRaffles.length > 1 ? 's' : ''}
              </span>
            </div>
            ${hasMore ? `
              <button onclick="openBrandModal('${category}', '${brand}')" 
                      class="text-ps-blue hover:text-ps-light font-semibold text-sm flex items-center gap-1 transition">
                View More <i class="fa-solid fa-arrow-right"></i>
              </button>
            ` : ''}
          </div>
          
          <!-- Brand Items Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6 lg:gap-8">
            ${displayItems.map(cardHTML).join('')}
          </div>
        </div>
      `;
    }
  });
  
  return html;
}

/* ---------- UNDERLINE ANIMATION ---------- */
function updateUnderline(btn) {
  const rect = btn.getBoundingClientRect();
  const barRect = catBar.getBoundingClientRect();
  const left = rect.left - barRect.left + catBar.scrollLeft;
  const width = rect.width;
  underline.style.width = `${width}px`;
  underline.style.transform = `translateX(${left}px)`;
  underline.style.top = '';
  underline.style.bottom = '0px';
}

/* ---------- OVERFLOW FADE CONTROL ---------- */
function updateFades() {
  const scrollLeft = catBar.scrollLeft;
  const maxScroll = catBar.scrollWidth - catBar.clientWidth;
  
  fadeLeft.style.opacity = scrollLeft > 0 ? '1' : '0';
  fadeRight.style.opacity = scrollLeft < maxScroll ? '1' : '0';
}

/* ---------- SIDEBAR STATE ---------- */
function activate(btn){
  /* remove active styling from all */
  catBtns.forEach(b=>{
    b.classList.remove('active','text-white','shadow-ps');
    b.classList.add('text-ps-silver');
    b.setAttribute('aria-selected', 'false');
    b.setAttribute('tabindex', '-1');
  });
  
  /* apply to clicked */
  btn.classList.remove('text-ps-silver');
  btn.classList.add('active','text-white','shadow-ps');
  btn.setAttribute('aria-selected', 'true');
  btn.setAttribute('tabindex', '0');
  
  /* animate underline */
  updateUnderline(btn);
  
  /* smooth scroll into view */
  btn.scrollIntoView({behavior:'smooth',block:'nearest',inline:'center'});
}

/* ---------- EVENT BIND ---------- */
catBtns.forEach(btn=>{
  btn.addEventListener('click',()=>{
    const cat = btn.dataset.category;
    window.currentCategory = cat;
    activate(btn);
    render(cat);
  });
  
  /* Keyboard navigation */
  btn.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      btn.click();
    }
  });
});

/* ---------- SCROLL EVENT ---------- */
catBar.addEventListener('scroll', updateFades);

/* ---------- FIRST LOAD ---------- */
window.addEventListener('load',()=>{
  activate(catBtns[0]);
  render('Just For U');
  updateFades();
  handleSidebar();           // keep your responsive helper
});

/* ---------- RESPONSIVE (unchanged) ---------- */
window.addEventListener('resize',handleSidebar);

/* ---------- RESPONSIVE HELPER ---------- */
function handleSidebar() {
  // Responsive behavior for category bar
  if (window.innerWidth < 768) {
    catBar.classList.add('snap-x', 'snap-mandatory');
  } else {
    catBar.classList.remove('snap-x', 'snap-mandatory');
  }
}

// Wishlist heart toggle logic
function bindWishlistBtns() {
  document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      const id = Number(btn.dataset.id);
      let watchlist = JSON.parse(localStorage.getItem('watchlist')||'[]');
      if (watchlist.includes(id)) {
        watchlist = watchlist.filter(x=>x!==id);
        btn.querySelector('i').classList.remove('fa-solid');
        btn.querySelector('i').classList.add('fa-regular');
      } else {
        watchlist.push(id);
        btn.querySelector('i').classList.remove('fa-regular');
        btn.querySelector('i').classList.add('fa-solid');
      }
      localStorage.setItem('watchlist', JSON.stringify(watchlist));
    };
  });
}

// Enhanced user loyalty data loader with better reward preview
async function loadUserLoyaltyData() {
  try {
    const response = await fetch('api/checkin.php?action=status&user_id=1');
    const result = await response.json();
    
    if (result.success) {
      const userData = result.data.user;
      const canCheckin = result.data.can_checkin_today;
      const nextReward = result.data.next_reward;
      
      // Update streak display
      const currentStreakElement = document.getElementById('current-streak');
      if (currentStreakElement) {
        currentStreakElement.textContent = userData.current_streak;
      }
      
      // Update today's reward display with bonus info
      const todayRewardElement = document.getElementById('today-reward');
      if (todayRewardElement && nextReward && nextReward.points) {
        todayRewardElement.textContent = `${nextReward.points} Loyalty Points`;
        if (nextReward.bonus_reward && nextReward.bonus_reward !== 'none') {
          todayRewardElement.textContent += ` + ${formatBonusReward(nextReward.bonus_reward, nextReward.bonus_value)}`;
        }
      }
      
      // Update next reward preview
      const nextRewardPreview = document.getElementById('next-reward-preview');
      if (nextRewardPreview && nextReward && nextReward.day) {
        const tomorrowReward = await getRewardPreview(nextReward.day + 1);
        if (tomorrowReward) {
          nextRewardPreview.textContent = `Tomorrow: ${tomorrowReward.points} pts`;
          if (tomorrowReward.bonus_reward && tomorrowReward.bonus_reward !== 'none') {
            nextRewardPreview.textContent += ` + ${formatBonusReward(tomorrowReward.bonus_reward, tomorrowReward.bonus_value)}`;
          }
        }
      }
      
      // Update check-in button with enhanced states
      const checkinBtn = document.getElementById('checkin-btn');
      if (!checkinBtn) {
        console.warn('Check-in button not found in DOM');
        return;
      }
      const checkinBtnIcon = checkinBtn.querySelector('i');
      const checkinBtnText = checkinBtn.querySelector('span');
      
      if (canCheckin) {
        checkinBtn.onclick = performCheckin;
        checkinBtn.disabled = false;
        checkinBtn.className = 'bg-red-500 hover:bg-red-600 text-white font-heading font-bold px-6 py-2.5 rounded-xl shadow-ps transition-all duration-300 text-sm flex items-center gap-2 min-w-[8rem] justify-center transform hover:scale-105';
        checkinBtn.style.background = '';
        checkinBtn.style.color = '';
        if (checkinBtnIcon) {
          checkinBtnIcon.className = 'fa-solid fa-gift text-white';
        }
        if (checkinBtnText) {
          checkinBtnText.textContent = 'Check-in';
          checkinBtnText.className = 'text-white';
        }
      } else {
        checkinBtn.disabled = true;
        checkinBtn.onclick = null;
        checkinBtn.className = 'bg-green-500 text-white font-heading font-bold px-6 py-2.5 rounded-xl shadow-lg text-sm flex items-center gap-2 min-w-[8rem] justify-center cursor-not-allowed';
        checkinBtn.style.background = '';
        checkinBtn.style.color = '';
        if (checkinBtnIcon) {
          checkinBtnIcon.className = 'fa-solid fa-check text-white';
        }
        if (checkinBtnText) {
          checkinBtnText.textContent = 'Completed';
          checkinBtnText.className = 'text-white';
        }
      }
      
      // Update calendar with enhanced visuals
      const calendarStats = await updateCheckinCalendar(userData);
      
      // Update loyalty points in header
      const pointsDisplay = document.getElementById('ticket-count');
      if (pointsDisplay) {
        pointsDisplay.textContent = userData.loyalty_points.toLocaleString();
      }
      
      // Check for milestones and achievements
      checkForMilestones(userData, calendarStats);
      
    }
  } catch (error) {
    console.error('Error loading loyalty data:', error);
    // Show user-friendly error
    showNotification('Unable to load check-in data. Please refresh the page.', 'error');
  }
}

// Helper function to format bonus rewards
function formatBonusReward(type, value) {
  switch (type) {
    case 'ticket':
      return `${value} Free Ticket${value > 1 ? 's' : ''}`;
    case 'spin':
      return `${value} Lucky Spin${value > 1 ? 's' : ''}`;
    case 'discount':
      return `${value} Discount`;
    case 'multiplier':
      return `${value}x Points`;
    default:
      return 'Bonus Reward';
  }
}

// Get reward preview for future days
async function getRewardPreview(day) {
  try {
    const response = await fetch('api/checkin.php?action=rewards_config');
    const result = await response.json();
    if (result.success) {
      const cycleDay = ((day - 1) % 30) + 1;
      const config = result.data.find(r => r.day_number === cycleDay);
      return config ? {
        points: config.base_points,
        bonus_reward: config.bonus_reward_type,
        bonus_value: config.bonus_reward_value
      } : null;
    }
  } catch (error) {
    console.warn('Could not fetch reward preview:', error);
  }
  return null;
}

// Check for milestones and achievements
function checkForMilestones(userData, calendarStats) {
  const milestones = [
    { streak: 7, message: '🔥 One week streak! Keep it up!', color: 'text-orange-600' },
    { streak: 14, message: '🚀 Two weeks strong! You\'re on fire!', color: 'text-red-600' },
    { streak: 30, message: '👑 One month streak! You\'re a legend!', color: 'text-purple-600' }
  ];
  
  const currentStreak = userData.current_streak;
  const milestone = milestones.find(m => m.streak === currentStreak);
  
  if (milestone) {
    setTimeout(() => {
      showNotification(milestone.message, 'success');
    }, 1000);
  }
  
  // Perfect week achievement
  if (calendarStats && calendarStats.checkedInCount === 7) {
    setTimeout(() => {
      showNotification('🎉 Perfect week completed! Bonus rewards unlocked!', 'success');
    }, 1500);
  }
}

// Animate today box when check-in is successful
function animateTodayBoxSuccess() {
  const todayBtn = document.getElementById('today-checkin-btn');
  if (!todayBtn) return;
  
  const iconContainer = todayBtn.querySelector('.today-icon');
  if (!iconContainer) return;
  
  // Step 1: Scale up and rotate the gift icon
  iconContainer.style.transform = 'scale(1.3) rotate(15deg)';
  iconContainer.style.transition = 'all 0.3s ease-out';
  
  setTimeout(() => {
    // Step 2: Fade out the gift icon
    iconContainer.style.opacity = '0';
    iconContainer.style.transform = 'scale(0.8) rotate(90deg)';
    
    setTimeout(() => {
      // Step 3: Change to check icon and fade in
      iconContainer.innerHTML = '<i class="fa-solid fa-check text-lg text-white"></i>';
      iconContainer.style.opacity = '1';
      iconContainer.style.transform = 'scale(1.2) rotate(0deg)';
      
      // Step 4: Update button styling to success state
      todayBtn.className = 'h-16 flex flex-col items-center justify-center rounded-xl border-2 transition-all duration-300 text-sm font-bold relative transform hover:scale-105 w-full bg-green-500 text-white border-green-500 shadow-lg';
      
      // Step 5: Add success badge
      const badgeHtml = '<div class="absolute -top-1 -right-1 w-4 h-4 bg-green-600 rounded-full flex items-center justify-center animate-bounce"><i class="fa-solid fa-check text-white text-xs"></i></div>';
      
      // Remove existing badge if any
      const existingBadge = todayBtn.parentElement.querySelector('.absolute.-top-1');
      if (existingBadge && existingBadge.classList.contains('bg-green-600')) {
        existingBadge.remove();
      }
      
      // Add new badge
      todayBtn.parentElement.insertAdjacentHTML('beforeend', badgeHtml);
      
      setTimeout(() => {
        // Step 6: Final settle animation
        iconContainer.style.transform = 'scale(1) rotate(0deg)';
        iconContainer.style.transition = 'all 0.2s ease-in';
        
        // Remove the ping indicator
        const pingIndicator = todayBtn.parentElement.querySelector('.animate-ping');
        if (pingIndicator) {
          pingIndicator.style.opacity = '0';
          setTimeout(() => pingIndicator.remove(), 300);
        }
        
        // Update the labels inside the button
        const dateLabel = todayBtn.querySelector('span:first-of-type');
        const dayLabel = todayBtn.querySelector('span:last-of-type');
        if (dateLabel) {
          dateLabel.textContent = 'Done!';
          dateLabel.className = 'text-xs font-medium mt-1 text-white';
        }
        if (dayLabel) {
          dayLabel.textContent = '✓';
          dayLabel.className = 'text-xs opacity-70 text-white';
        }
        
      }, 300);
    }, 200);
  }, 300);
}

// Enhanced notification system
function showNotification(message, type = 'info') {
  const notification = document.createElement('div');
  notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform transition-all duration-300 translate-x-full`;
  
  const colors = {
    success: 'bg-green-500 text-white',
    error: 'bg-red-500 text-white',
    info: 'bg-blue-500 text-white',
    warning: 'bg-yellow-500 text-black'
  };
  
  notification.className += ` ${colors[type] || colors.info}`;
  notification.innerHTML = `
    <div class="flex items-center gap-2">
      <span class="flex-1">${message}</span>
      <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
        <i class="fa-solid fa-times"></i>
      </button>
    </div>
  `;
  
  document.body.appendChild(notification);
  
  // Animate in
  setTimeout(() => {
    notification.classList.remove('translate-x-full');
  }, 100);
  
  // Auto remove after 5 seconds
  setTimeout(() => {
    notification.classList.add('translate-x-full');
    setTimeout(() => {
      if (notification.parentElement) {
        notification.remove();
      }
    }, 300);
  }, 5000);
}

// Enhanced check-in calendar with real data integration
async function updateCheckinCalendar(userData) {
  const calendar = document.getElementById('checkin-calendar');
  const today = new Date();
  const todayDayOfWeek = today.getDay(); // 0 = Sunday, 6 = Saturday
  const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
  const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  
  // Calculate the start of current week (Sunday)
  const startOfWeek = new Date(today);
  startOfWeek.setDate(today.getDate() - todayDayOfWeek);
  
  // Get current week's check-ins from API
  let checkinData = [];
  try {
    const response = await fetch(`api/checkin.php?action=status&user_id=1`);
    const result = await response.json();
    if (result.success) {
      checkinData = result.data.calendar || [];
    }
  } catch (error) {
    console.warn('Could not fetch check-in data:', error);
  }
  
  // Create a map of checked-in dates for this week
  const checkedInDates = new Set();
  
  checkinData.forEach(checkin => {
    const checkinDate = new Date(checkin.checkin_date);
    if (checkinDate >= startOfWeek && checkinDate < new Date(startOfWeek.getTime() + 7 * 24 * 60 * 60 * 1000)) {
      checkedInDates.add(checkinDate.getDay());
    }
  });
  
  let html = '';
  let checkedInCount = 0;
  let missedCount = 0;
  let hasMissedDays = false;
  
  for (let i = 0; i < 7; i++) {
    // Calculate the actual date for this day
    const currentDayDate = new Date(startOfWeek);
    currentDayDate.setDate(startOfWeek.getDate() + i);
    
    const isToday = i === todayDayOfWeek;
    const isCheckedIn = checkedInDates.has(i);
    const isFuture = i > todayDayOfWeek;
    const isMissed = i < todayDayOfWeek && !isCheckedIn;
    
    // Format the date
    const dateNumber = currentDayDate.getDate();
    const monthName = currentDayDate.toLocaleDateString('en-US', { month: 'short' });
    
    if (isCheckedIn) checkedInCount++;
    if (isMissed) {
      missedCount++;
      hasMissedDays = true;
    }
    
    let classes = 'h-16 flex flex-col items-center justify-center rounded-xl border-2 transition-all duration-300 text-sm font-bold relative transform hover:scale-105 w-full';
    let icon = '';
    let label = '';
    let dateLabel = '';
    let tooltip = '';
    let badgeContent = '';
    
    if (isCheckedIn) {
      classes += ' bg-green-500 text-white border-green-500 shadow-lg';
      icon = '<i class="fa-solid fa-check text-base"></i>';
      label = days[i];
      dateLabel = dateNumber;
      tooltip = `✅ Checked in on ${dayNames[i]}, ${monthName} ${dateNumber}`;
      badgeContent = '<div class="absolute -top-1 -right-1 w-4 h-4 bg-green-600 rounded-full flex items-center justify-center"><i class="fa-solid fa-check text-white text-xs"></i></div>';
    } else if (isToday) {
      classes += ' bg-red-500 text-white border-red-500 shadow-lg';
      icon = '<i class="fa-solid fa-gift text-base"></i>';
      label = 'Today';
      dateLabel = dateNumber;
      tooltip = '🎁 Check in today to earn rewards!';
    } else if (isFuture) {
      classes += ' bg-gray-50 text-gray-400 border-gray-200 cursor-not-allowed';
      icon = '<i class="fa-solid fa-lock text-sm"></i>';
      label = days[i];
      dateLabel = dateNumber;
      tooltip = `🔒 ${dayNames[i]}, ${monthName} ${dateNumber} - Come back later`;
    } else if (isMissed) {
      classes += ' bg-red-50 text-red-400 border-red-200';
      icon = '<i class="fa-solid fa-times text-sm"></i>';
      label = days[i];
      dateLabel = dateNumber;
      tooltip = `❌ Missed ${dayNames[i]}, ${monthName} ${dateNumber} - Streak was broken`;
      badgeContent = '<div class="absolute -top-1 -right-1 w-4 h-4 bg-red-400 rounded-full flex items-center justify-center"><i class="fa-solid fa-times text-white text-xs"></i></div>';
    }
    
    html += `
      <div class="relative group">
        <button class="${classes}" title="${tooltip}" ${isFuture ? 'disabled' : ''} ${isToday ? 'id="today-checkin-btn"' : ''}>
          <div class="transition-all duration-500 ease-in-out ${isToday ? 'today-icon' : ''}">
            ${icon}
          </div>
          <span class="text-xs font-medium mt-1 ${isCheckedIn ? 'text-white' : isMissed ? 'text-red-400' : isToday ? 'text-white' : 'text-gray-400'}">${isToday ? 'Today' : dateLabel}</span>
          <span class="text-xs opacity-70 ${isCheckedIn ? 'text-white' : isMissed ? 'text-red-300' : isToday ? 'text-white' : 'text-gray-300'}">${days[i]}</span>
        </button>
        ${badgeContent}
        ${isToday ? '<div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-2 h-2 bg-ps-yellow rounded-full animate-ping"></div>' : ''}
      </div>
    `;
  }
  
  calendar.innerHTML = html;
  
  // Update progress bar and text
  const progressBar = document.getElementById('streak-progress');
  const progressText = document.getElementById('progress-text');
  const progressPercentage = (checkedInCount / 7) * 100;
  
  if (progressBar) {
    progressBar.style.width = `${progressPercentage}%`;
  }
  
  if (progressText) {
    progressText.textContent = `${checkedInCount} of 7 days this week`;
    if (checkedInCount === 7) {
      progressText.innerHTML = `🎉 Perfect week! ${checkedInCount}/7 days completed`;
      progressText.className = 'text-xs text-green-600 mt-1 text-center font-medium';
    } else if (checkedInCount >= 5) {
      progressText.innerHTML = `🔥 Great streak! ${checkedInCount}/7 days completed`;
      progressText.className = 'text-xs text-red-600 mt-1 text-center font-medium';
    }
  }
  
  // Show/hide recovery hint for missed days
  const recoveryHint = document.getElementById('recovery-hint');
  if (recoveryHint) {
    if (hasMissedDays && userData.current_streak === 0) {
      recoveryHint.classList.remove('hidden');
      setTimeout(() => {
        recoveryHint.classList.add('animate-pulse');
      }, 100);
    } else {
      recoveryHint.classList.add('hidden');
    }
  }
  
  return { checkedInCount, missedCount, hasMissedDays };
}

// Enhanced check-in function with better UX
async function performCheckin() {
  const checkinBtn = document.getElementById('checkin-btn');
  const checkinBtnIcon = checkinBtn.querySelector('i');
  const checkinBtnText = checkinBtn.querySelector('span');
  
  // Disable button and show loading state
  checkinBtn.disabled = true;
  checkinBtn.classList.add('cursor-not-allowed');
  if (checkinBtnIcon) checkinBtnIcon.className = 'fa-solid fa-spinner fa-spin';
  if (checkinBtnText) checkinBtnText.textContent = 'Processing...';
  
  try {
    const response = await fetch('api/checkin.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'checkin', user_id: 1 })
    });
    
    const result = await response.json();
    
    if (result.success) {
      // Show success animation
      checkinConfetti();
      
      // Animate today box transformation
      animateTodayBoxSuccess();
      
      // Update button to success state immediately
      checkinBtn.className = 'bg-green-500 text-white font-heading font-bold px-6 py-2.5 rounded-xl shadow-lg text-sm flex items-center gap-2 min-w-[8rem] justify-center cursor-not-allowed';
      checkinBtn.style.background = '';
      checkinBtn.style.color = '';
      if (checkinBtnIcon) {
        checkinBtnIcon.className = 'fa-solid fa-check text-white';
      }
      if (checkinBtnText) {
        checkinBtnText.textContent = 'Completed';
        checkinBtnText.className = 'text-white';
      }
      
      // Build success message with bonus info
      let successMessage = `🎉 Check-in successful! You earned ${result.data.points_earned} loyalty points!`;
      if (result.data.new_streak) {
        successMessage += ` Your streak is now ${result.data.new_streak} days!`;
      }
      
      // Add bonus rewards info
      if (result.data.bonus_rewards && result.data.bonus_rewards.length > 0) {
        const bonuses = result.data.bonus_rewards.map(bonus => bonus.description).join(', ');
        successMessage += ` Bonus: ${bonuses}`;
      }
      
      // Show enhanced success notification
      setTimeout(() => {
        showNotification(successMessage, 'success');
      }, 500);
      
      // Update displays after animation
      setTimeout(() => {
        loadUserLoyaltyData();
      }, 1000);
      
      // Show milestone achievements if any
      if (result.data.new_streak && [7, 14, 21, 30].includes(result.data.new_streak)) {
        setTimeout(() => {
          showMilestoneAchievement(result.data.new_streak);
        }, 2000);
      }
      
    } else {
      // Reset button state on error
      checkinBtn.disabled = false;
      checkinBtn.classList.remove('cursor-not-allowed');
      checkinBtn.className = 'bg-red-500 hover:bg-red-600 text-white font-heading font-bold px-6 py-2.5 rounded-xl shadow-ps transition-all duration-300 text-sm flex items-center gap-2 min-w-[8rem] justify-center transform hover:scale-105';
      checkinBtn.style.background = '';
      checkinBtn.style.color = '';
      if (checkinBtnIcon) {
        checkinBtnIcon.className = 'fa-solid fa-gift text-white';
      }
      if (checkinBtnText) {
        checkinBtnText.textContent = 'Check-in';
        checkinBtnText.className = 'text-white';
      }
      
      showNotification('Error: ' + result.error, 'error');
    }
  } catch (error) {
    // Reset button state on network error
    checkinBtn.disabled = false;
    checkinBtn.classList.remove('cursor-not-allowed');
    checkinBtn.className = 'bg-red-500 hover:bg-red-600 text-white font-heading font-bold px-6 py-2.5 rounded-xl shadow-ps transition-all duration-300 text-sm flex items-center gap-2 min-w-[8rem] justify-center transform hover:scale-105';
    checkinBtn.style.background = '';
    checkinBtn.style.color = '';
    if (checkinBtnIcon) {
      checkinBtnIcon.className = 'fa-solid fa-gift text-white';
    }
    if (checkinBtnText) {
      checkinBtnText.textContent = 'Check-in';
      checkinBtnText.className = 'text-white';
    }
    
    showNotification('Network error. Please try again.', 'error');
  }
}

// Show special milestone achievement
function showMilestoneAchievement(streak) {
  const milestoneMessages = {
    7: { title: '🔥 Week Warrior!', desc: 'You\'ve completed your first week!' },
    14: { title: '🚀 Streak Master!', desc: 'Two weeks of dedication!' },
    21: { title: '💎 Commitment Champion!', desc: 'Three weeks strong!' },
    30: { title: '👑 Legendary Streaker!', desc: 'One month of perfect check-ins!' }
  };
  
  const milestone = milestoneMessages[streak];
  if (!milestone) return;
  
  // Create special milestone modal
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-2xl p-8 max-w-md w-full text-center transform animate-bounce">
      <div class="text-6xl mb-4">🎉</div>
      <h2 class="text-2xl font-bold text-red-600 mb-2">${milestone.title}</h2>
      <p class="text-ps-text mb-6">${milestone.desc}</p>
      <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-3 rounded-xl font-bold mb-4">
        ${streak} Day Streak Achievement
      </div>
      <button onclick="this.parentElement.parentElement.remove()" class="bg-ps-blue text-white px-6 py-2 rounded-lg hover:bg-ps-light transition">
        Awesome! 🎊
      </button>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Auto-remove after 10 seconds
  setTimeout(() => {
    if (modal.parentElement) {
      modal.remove();
    }
  }, 10000);
}

// Enhanced confetti animation
function checkinConfetti() {
  for (let i = 0; i < 100; i++) {
    setTimeout(() => {
      const confetti = document.createElement('div');
      confetti.innerHTML = ['🎉', '⭐', '🎊', '💫', '🏆', '🎁'][Math.floor(Math.random() * 6)];
      confetti.style.position = 'fixed';
      confetti.style.left = Math.random() * window.innerWidth + 'px';
      confetti.style.top = '-50px';
      confetti.style.fontSize = '20px';
      confetti.style.zIndex = '9999';
      confetti.style.pointerEvents = 'none';
      confetti.style.animation = 'fall 3s linear forwards';
      document.body.appendChild(confetti);
      
      setTimeout(() => confetti.remove(), 3000);
    }, i * 20);
  }
}

// Add confetti animation styles
const confettiStyle = document.createElement('style');
confettiStyle.textContent = `
  @keyframes fall {
    to {
      transform: translateY(calc(100vh + 50px)) rotate(360deg);
      opacity: 0;
    }
  }
`;
document.head.appendChild(confettiStyle);

// --- Modal logic ---
function openProductModal(product) {
  // Update user tag preferences when viewing raffle
  updateUserTagPreferences(product.id, 'view');
  
  document.getElementById('modal-img').src = product.image_url || 'images/placeholder.jpg';
  document.getElementById('modal-title').textContent = product.title;
  document.getElementById('modal-price').textContent = 'RM' + Number(product.price).toLocaleString(undefined, {minimumFractionDigits:2});
  document.getElementById('modal-sold').textContent = (product.sold && product.total) ? `${product.sold} of ${product.total} sold` : '';
  
  // Use the description from the product object directly
  const description = product.description || '';
  document.getElementById('modal-desc').innerHTML = description;
  // Badge
  const badge = document.getElementById('modal-badge');
  if (product.badge) {
    badge.textContent = product.badge.charAt(0).toUpperCase() + product.badge.slice(1);
    badge.className = 'px-3 py-1 rounded-full text-xs font-semibold';
    if (product.badge === 'sellingFast') badge.classList.add('bg-ps-yellow/80','text-ps-text');
    else if (product.badge === 'promo') badge.classList.add('bg-ps-light/90','text-white');
    else if (product.badge === 'limited') badge.classList.add('bg-red-600/90','text-white');
    else if (product.badge === 'new') badge.classList.add('bg-green-500/90','text-white');
    else badge.classList.add('bg-gray-200','text-ps-text');
    badge.classList.remove('hidden');
  } else {
    badge.classList.add('hidden');
  }
  // Category
  const catIcon = document.getElementById('modal-category-icon');
  const catText = document.querySelector('#modal-category span');
  if (product.category && product.category_icon) {
    catIcon.className = 'fa-solid ' + product.category_icon;
    catText.textContent = product.category;
    document.getElementById('modal-category').classList.remove('hidden');
  } else {
    document.getElementById('modal-category').classList.add('hidden');
  }
  modal.classList.remove('hidden');
  document.body.classList.add('overflow-hidden');
}

// Modal close logic
if (closeBtn && modal) {
  closeBtn.onclick = function(e) {
    e.preventDefault();
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  };
  window.addEventListener('keydown', function(e) {
    if (!modal.classList.contains('hidden') && e.key === 'Escape') closeBtn.onclick(e);
  });
  modal.addEventListener('click', function(e) {
    if (e.target === modal) closeBtn.onclick(e);
  });
}

// --- Card hover/overlay logic ---
function bindProductCards() {
  document.querySelectorAll('.product-img-container').forEach(container => {
    const infoIcon = container.querySelector('.info-icon-overlay');
    container.onclick = function(e) {
      e.preventDefault();
      e.stopPropagation();
      const productId = container.dataset.productId;
      const product = raffles.find(r => r.id == productId);
      if (product) {
        openProductModal(product);
      }
    };
    if (infoIcon) {
      infoIcon.onclick = container.onclick;
    }
  });
}
window.addEventListener('DOMContentLoaded', function() {
  bindProductCards();
  loadUserLoyaltyData();
  loadUserWishlist(); // Add this line
  initializeAuth();
  initializeLiveDrawPopup();
  initializeBrandModal();
});

// Brand Modal functionality
function initializeBrandModal() {
  const brandModal = document.getElementById('brand-modal');
  const brandModalClose = document.getElementById('brand-modal-close');
  
  if (brandModalClose) {
    brandModalClose.onclick = closeBrandModal;
  }
  
  // Close on background click
  brandModal?.addEventListener('click', function(e) {
    if (e.target === brandModal) {
      closeBrandModal();
    }
  });
  
  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !brandModal.classList.contains('hidden')) {
      closeBrandModal();
    }
  });
}

function openBrandModal(category, brand) {
  const modal = document.getElementById('brand-modal');
  const title = document.getElementById('brand-modal-title');
  const count = document.getElementById('brand-modal-count');
  const grid = document.getElementById('brand-modal-grid');
  
  if (!groupedRaffles[category] || !groupedRaffles[category][brand]) return;
  
  const brandData = groupedRaffles[category][brand];
  const brandRaffles = brandData.raffles.filter(r => r.sold < r.total);
  
  // Update modal content
  title.textContent = `${brand} Products`;
  count.textContent = `${brandRaffles.length} item${brandRaffles.length > 1 ? 's' : ''}`;
  grid.innerHTML = brandRaffles.map(cardHTML).join('');
  
  // Show modal
  modal.classList.remove('hidden');
  document.body.classList.add('overflow-hidden');
  
  // Bind product cards in modal
  bindProductCards();
}

function closeBrandModal() {
  const modal = document.getElementById('brand-modal');
  modal.classList.add('hidden');
  document.body.classList.remove('overflow-hidden');
}

// Live Draw Popup functionality
function initializeLiveDrawPopup() {
  const popup = document.getElementById('live-draw-popup');
  const closeBtn = document.getElementById('live-popup-close');
  const maybeLaterBtn = document.getElementById('maybe-later-btn');
  const joinLiveBtn = document.getElementById('join-live-btn');
  
  // For testing - show popup every time (remove the date check)
  // Show popup after 3 seconds
  setTimeout(() => {
    showLiveDrawPopup();
  }, 3000);
  
  // Close button functionality
  if (closeBtn) {
    closeBtn.onclick = closeLiveDrawPopup;
  }
  
  // Maybe later button
  if (maybeLaterBtn) {
    maybeLaterBtn.onclick = closeLiveDrawPopup;
  }
  
  // Join live button
  if (joinLiveBtn) {
    joinLiveBtn.onclick = function(e) {
      e.preventDefault();
      // Here you would redirect to your actual live stream
      // For now, we'll just close the popup and scroll to the live section
      closeLiveDrawPopup();
      document.querySelector('#live-draw-section')?.scrollIntoView({ behavior: 'smooth' });
    };
  }
  
  // Close on background click
  popup?.addEventListener('click', function(e) {
    if (e.target === popup) {
      closeLiveDrawPopup();
    }
  });
  
  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !popup.classList.contains('hidden')) {
      closeLiveDrawPopup();
    }
  });
  
  // Start viewer count animation
  animateViewerCount();
  
  // Start countdown
  startPopupCountdown();
}

function showLiveDrawPopup() {
  const popup = document.getElementById('live-draw-popup');
  if (popup) {
    popup.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
  }
}

function closeLiveDrawPopup() {
  const popup = document.getElementById('live-draw-popup');
  if (popup) {
    popup.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    
    // Remember that popup was shown today
    const today = new Date().toDateString();
    localStorage.setItem('livePopupLastShown', today);
  }
}

function animateViewerCount() {
  const liveViewers = document.getElementById('live-viewers');
  
  if (liveViewers) {
    let baseCount = 2847;
    
    setInterval(() => {
      // Simulate viewer count changes (±50)
      const change = Math.floor(Math.random() * 100) - 50;
      baseCount = Math.max(1000, baseCount + change);
      
      liveViewers.textContent = baseCount.toLocaleString();
    }, 5000); // Update every 5 seconds
  }
}

function startPopupCountdown() {
  const countdownElement = document.getElementById('popup-countdown');
  if (!countdownElement) return;
  
  // Set countdown to 10 minutes from now
  let seconds = 10 * 60;
  
  const updateCountdown = () => {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    
    countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`;
    
    if (seconds > 0) {
      seconds--;
    } else {
      // Reset to 10 minutes when it reaches 0
      seconds = 10 * 60;
    }
  };
  
  updateCountdown();
  setInterval(updateCountdown, 1000);
}

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

// RM1 Strategy Functions
function quickBuyTickets(raffleId, quantity, title) {
  // Check if user is logged in
  <?php if (!$currentUser): ?>
    showNotification('Please log in to purchase tickets', 'error');
    openLoginModal();
    return;
  <?php endif; ?>
  
  // Show quick buy confirmation
  showQuickBuyModal(raffleId, quantity, title);
}

function customBuyTickets(raffleId, quantity, title) {
  // Check if user is logged in
  <?php if (!$currentUser): ?>
    showNotification('Please log in to purchase tickets', 'error');
    openLoginModal();
    return;
  <?php endif; ?>
  
  const qty = parseInt(quantity);
  if (qty < 1 || qty > 100) {
    showNotification('Please enter a valid quantity (1-100)', 'error');
    return;
  }
  
  showQuickBuyModal(raffleId, qty, title);
}

function showQuickBuyModal(raffleId, quantity, title) {
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-ps-blue to-ps-light p-6 text-white relative">
        <button onclick="closeQuickBuyModal()" class="absolute top-4 right-4 text-white hover:text-gray-200 text-xl">
          <i class="fa-solid fa-times"></i>
        </button>
        <div class="text-center">
          <h3 class="font-heading text-xl font-bold mb-1">Quick Purchase</h3>
          <p class="text-white/90 text-sm">${title}</p>
        </div>
      </div>
      
      <!-- Step Indicator -->
      <div class="flex items-center justify-center p-4 bg-gray-50">
        <div class="flex items-center space-x-2">
          <div class="step-indicator active" data-step="1">
            <div class="w-8 h-8 bg-ps-blue text-white rounded-full flex items-center justify-center text-sm font-bold">1</div>
            <span class="text-xs text-ps-blue font-medium">Entry</span>
          </div>
          <div class="w-8 h-1 bg-gray-300 rounded"></div>
          <div class="step-indicator" data-step="2">
            <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-bold">2</div>
            <span class="text-xs text-gray-500 font-medium">Payment</span>
          </div>
          <div class="w-8 h-1 bg-gray-300 rounded"></div>
          <div class="step-indicator" data-step="3">
            <div class="w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center text-sm font-bold">3</div>
            <span class="text-xs text-gray-500 font-medium">Confirm</span>
          </div>
        </div>
      </div>
      
      <!-- Step 1: Entry Selection -->
      <div id="step-1" class="step-content p-6">
        <div class="text-center mb-6">
          <h4 class="font-heading text-lg font-bold text-ps-text mb-2">Choose Your Entry</h4>
          <p class="text-gray-600 text-sm">Select how many tickets you'd like to purchase</p>
        </div>
        
        <!-- Quick Entry Options -->
        <div class="grid grid-cols-3 gap-3 mb-6">
          <button onclick="selectEntryOption(1)" class="entry-option-btn selected" data-qty="1">
            <div class="text-2xl font-bold text-ps-blue">1</div>
            <div class="text-sm font-semibold text-gray-700">Try Luck</div>
            <div class="text-xs text-gray-500">RM1.00</div>
          </button>
          <button onclick="selectEntryOption(3)" class="entry-option-btn" data-qty="3">
            <div class="text-2xl font-bold text-ps-blue">3</div>
            <div class="text-sm font-semibold text-gray-700">Triple</div>
            <div class="text-xs text-gray-500">RM3.00</div>
          </button>
          <button onclick="selectEntryOption(5)" class="entry-option-btn" data-qty="5">
            <div class="text-2xl font-bold text-ps-blue">5</div>
            <div class="text-sm font-semibold text-gray-700">Lucky Five</div>
            <div class="text-xs text-gray-500">RM5.00</div>
          </button>
        </div>
        
        <!-- Custom Quantity -->
        <div class="bg-gray-50 rounded-xl p-4 mb-6">
          <label class="block text-sm font-semibold text-gray-700 mb-2">Custom Quantity</label>
          <div class="flex items-center gap-3">
            <button onclick="adjustQuantity(-1)" class="w-10 h-10 bg-white border border-gray-300 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-50">
              <i class="fa-solid fa-minus"></i>
            </button>
            <input type="number" id="customQuantity" value="1" min="1" max="100" 
                   class="flex-1 text-center border border-gray-300 rounded-lg py-2 font-bold text-ps-blue"
                   onchange="updateCustomQuantity()">
            <button onclick="adjustQuantity(1)" class="w-10 h-10 bg-white border border-gray-300 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-50">
              <i class="fa-solid fa-plus"></i>
            </button>
          </div>
        </div>
        
        <!-- Summary -->
        <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-xl p-4 mb-6">
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-gray-700">Tickets:</span>
            <span class="font-bold text-ps-blue" id="summaryTickets">1</span>
          </div>
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-gray-700">Price per ticket:</span>
            <span class="font-bold text-green-600">RM1.00</span>
          </div>
          <div class="flex items-center justify-between mb-2">
            <span class="font-semibold text-gray-700">Loyalty points:</span>
            <span class="font-bold text-ps-yellow" id="summaryPoints">+1</span>
          </div>
          <div class="border-t pt-2 flex items-center justify-between">
            <span class="font-bold text-lg">Total:</span>
            <span class="text-xl font-bold text-ps-blue" id="summaryTotal">RM1.00</span>
          </div>
        </div>
        
        <!-- Next Button -->
        <button onclick="nextStep()" class="w-full bg-ps-blue hover:bg-ps-light text-white font-heading font-bold py-3 px-6 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-105">
          Continue to Payment
        </button>
      </div>
      
      <!-- Step 2: Payment Method -->
      <div id="step-2" class="step-content hidden p-6">
        <div class="text-center mb-6">
          <h4 class="font-heading text-lg font-bold text-ps-text mb-2">Choose Payment Method</h4>
          <p class="text-gray-600 text-sm">Select your preferred payment option</p>
        </div>
        
        <!-- Payment Options -->
        <div class="space-y-3 mb-6">
          <button onclick="selectPaymentMethod('tng')" class="payment-option-btn w-full" data-method="tng">
            <div class="flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-ps-blue hover:bg-blue-50 transition-all">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center">
                  <i class="fa-solid fa-mobile-alt text-white text-xl"></i>
                </div>
                <div class="text-left">
                  <div class="font-bold text-gray-900">Touch 'n Go</div>
                  <div class="text-sm text-gray-600">Pay with eWallet</div>
                </div>
              </div>
              <i class="fa-solid fa-chevron-right text-gray-400"></i>
            </div>
          </button>
          
          <button onclick="selectPaymentMethod('gpay')" class="payment-option-btn w-full" data-method="gpay">
            <div class="flex items-center justify-between p-4 border-2 border-gray-200 rounded-xl hover:border-ps-blue hover:bg-blue-50 transition-all">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center">
                  <i class="fa-brands fa-google text-white text-xl"></i>
                </div>
                <div class="text-left">
                  <div class="font-bold text-gray-900">Google Pay</div>
                  <div class="text-sm text-gray-600">Fast & Secure</div>
                </div>
              </div>
              <i class="fa-solid fa-chevron-right text-gray-400"></i>
            </div>
          </button>
        </div>
        
        <!-- Security Badges -->
        <div class="flex items-center justify-center gap-4 text-xs text-gray-500 mb-6">
          <div class="flex items-center gap-1">
            <i class="fa-solid fa-shield-check text-green-500"></i>
            <span>SSL Secured</span>
          </div>
          <div class="flex items-center gap-1">
            <i class="fa-solid fa-lock text-blue-500"></i>
            <span>PCI DSS</span>
          </div>
          <div class="flex items-center gap-1">
            <i class="fa-solid fa-bolt text-yellow-500"></i>
            <span>Instant</span>
          </div>
        </div>
        
        <!-- Navigation -->
        <div class="flex gap-3">
          <button onclick="prevStep()" class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-xl hover:bg-gray-300 transition">
            Back
          </button>
          <button onclick="nextStep()" class="flex-1 bg-ps-blue hover:bg-ps-light text-white font-bold py-3 px-6 rounded-xl transition" id="paymentNextBtn" disabled>
            Continue
          </button>
        </div>
      </div>
      
      <!-- Step 3: Confirmation -->
      <div id="step-3" class="step-content hidden p-6">
        <div class="text-center mb-6">
          <h4 class="font-heading text-lg font-bold text-ps-text mb-2">Confirm Purchase</h4>
          <p class="text-gray-600 text-sm">Review your order details</p>
        </div>
        
        <!-- Order Summary -->
        <div class="bg-gray-50 rounded-xl p-4 mb-6">
          <div class="flex items-center gap-3 mb-4">
            <img src="images/placeholder.jpg" alt="Product" class="w-16 h-16 rounded-lg object-cover">
            <div class="flex-1">
              <div class="font-bold text-gray-900">${title}</div>
              <div class="text-sm text-gray-600">Raffle Entry</div>
            </div>
          </div>
          
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-600">Tickets:</span>
              <span class="font-semibold" id="confirmTickets">1</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Price per ticket:</span>
              <span class="font-semibold">RM1.00</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Payment method:</span>
              <span class="font-semibold" id="confirmPayment">Touch 'n Go</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Loyalty points:</span>
              <span class="font-semibold text-ps-yellow" id="confirmPoints">+1</span>
            </div>
            <div class="border-t pt-2 flex justify-between font-bold text-lg">
              <span>Total:</span>
              <span class="text-ps-blue" id="confirmTotal">RM1.00</span>
            </div>
          </div>
        </div>
        
        <!-- Navigation -->
        <div class="flex gap-3">
          <button onclick="prevStep()" class="flex-1 bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-xl hover:bg-gray-300 transition">
            Back
          </button>
          <button onclick="processPurchase(${raffleId})" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl transition flex items-center justify-center gap-2">
            <i class="fa-solid fa-check"></i>
            Confirm Purchase
          </button>
        </div>
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
  
  // Initialize modal state
  window.currentStep = 1;
  window.selectedQuantity = 1;
  window.selectedPaymentMethod = null;
  
  // Auto-remove after 5 minutes
  setTimeout(() => {
    if (modal.parentElement) {
      modal.remove();
    }
  }, 300000);
}

// Modal helper functions
function closeQuickBuyModal() {
  const modal = document.querySelector('.fixed.inset-0');
  if (modal) modal.remove();
}

function selectEntryOption(qty) {
  window.selectedQuantity = qty;
  
  // Update visual selection
  document.querySelectorAll('.entry-option-btn').forEach(btn => {
    btn.classList.remove('selected');
  });
  event.target.closest('.entry-option-btn').classList.add('selected');
  
  // Update custom quantity input
  document.getElementById('customQuantity').value = qty;
  
  // Update summary
  updateSummary();
}

function adjustQuantity(delta) {
  const input = document.getElementById('customQuantity');
  const newValue = Math.max(1, Math.min(100, parseInt(input.value) + delta));
  input.value = newValue;
  window.selectedQuantity = newValue;
  
  // Update visual selection
  document.querySelectorAll('.entry-option-btn').forEach(btn => {
    btn.classList.remove('selected');
  });
  
  updateSummary();
}

function updateCustomQuantity() {
  const input = document.getElementById('customQuantity');
  window.selectedQuantity = parseInt(input.value) || 1;
  updateSummary();
}

function updateSummary() {
  const qty = window.selectedQuantity;
  const total = qty;
  const points = qty >= 10 ? Math.floor(qty * 1.5) : qty;
  
  document.getElementById('summaryTickets').textContent = qty;
  document.getElementById('summaryTotal').textContent = `RM${total}.00`;
  document.getElementById('summaryPoints').textContent = `+${points}`;
  
  // Update confirmation step
  document.getElementById('confirmTickets').textContent = qty;
  document.getElementById('confirmTotal').textContent = `RM${total}.00`;
  document.getElementById('confirmPoints').textContent = `+${points}`;
}

function selectPaymentMethod(method) {
  window.selectedPaymentMethod = method;
  
  // Update visual selection
  document.querySelectorAll('.payment-option-btn').forEach(btn => {
    btn.querySelector('.border-2').classList.remove('border-ps-blue', 'bg-blue-50');
    btn.querySelector('.border-2').classList.add('border-gray-200');
  });
  event.target.closest('.payment-option-btn').querySelector('.border-2').classList.remove('border-gray-200');
  event.target.closest('.payment-option-btn').querySelector('.border-2').classList.add('border-ps-blue', 'bg-blue-50');
  
  // Enable next button
  document.getElementById('paymentNextBtn').disabled = false;
  
  // Update confirmation
  const methodNames = {
    'tng': 'Touch \'n Go',
    'gpay': 'Google Pay'
  };
  document.getElementById('confirmPayment').textContent = methodNames[method];
}

function nextStep() {
  if (window.currentStep === 1) {
    // Validate quantity
    if (window.selectedQuantity < 1) {
      showNotification('Please select a valid quantity', 'error');
      return;
    }
  } else if (window.currentStep === 2) {
    // Validate payment method
    if (!window.selectedPaymentMethod) {
      showNotification('Please select a payment method', 'error');
      return;
    }
  }
  
  // Hide current step
  document.getElementById(`step-${window.currentStep}`).classList.add('hidden');
  
  // Show next step
  window.currentStep++;
  document.getElementById(`step-${window.currentStep}`).classList.remove('hidden');
  
  // Update step indicator
  updateStepIndicator();
}

function prevStep() {
  // Hide current step
  document.getElementById(`step-${window.currentStep}`).classList.add('hidden');
  
  // Show previous step
  window.currentStep--;
  document.getElementById(`step-${window.currentStep}`).classList.remove('hidden');
  
  // Update step indicator
  updateStepIndicator();
}

function updateStepIndicator() {
  document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
    const stepNumber = index + 1;
    const circle = indicator.querySelector('div');
    const text = indicator.querySelector('span');
    
    if (stepNumber <= window.currentStep) {
      circle.classList.remove('bg-gray-300', 'text-gray-600');
      circle.classList.add('bg-ps-blue', 'text-white');
      text.classList.remove('text-gray-500');
      text.classList.add('text-ps-blue');
    } else {
      circle.classList.remove('bg-ps-blue', 'text-white');
      circle.classList.add('bg-gray-300', 'text-gray-600');
      text.classList.remove('text-ps-blue');
      text.classList.add('text-gray-500');
    }
  });
}

function processPurchase(raffleId) {
  const modal = document.querySelector('.fixed.inset-0');
  if (modal) modal.remove();
  
  // Show processing notification
  showNotification('Processing your purchase...', 'info');
  
  // Simulate payment processing
  setTimeout(() => {
    const qty = window.selectedQuantity;
    const method = window.selectedPaymentMethod;
    
    // Show success with payment method
    showNotification(`🎉 Success! ${qty} tickets purchased via ${method === 'tng' ? 'Touch \'n Go' : 'Google Pay'}`, 'success');
    
    // Show achievement if applicable
    if (qty >= 5) {
      setTimeout(() => {
        showAchievementNotification('Starter', 'You earned the Starter badge!');
      }, 1000);
    }
    
    // Update UI
    updateTicketCount(qty);
  }, 2000);
}

// Initialize scrolling animation for live activity
function initializeLiveActivity() {
  const scrollContainer = document.querySelector('.animate-scroll');
  if (scrollContainer) {
    scrollContainer.style.animation = 'scroll-left 30s linear infinite';
  }
}

// Add CSS for enhanced modal and animations
const style = document.createElement('style');
style.textContent = `
  @keyframes scroll-left {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
  }
  
  .animate-scroll {
    animation: scroll-left 30s linear infinite;
  }
  
  /* Enhanced Modal Styles */
  .entry-option-btn {
    transition: all 0.3s ease;
    border: 2px solid transparent;
    background: white;
    border-radius: 12px;
    padding: 16px 8px;
    cursor: pointer;
  }
  
  .entry-option-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 112, 209, 0.15);
  }
  
  .entry-option-btn.selected {
    border-color: #007aff;
    background: linear-gradient(135deg, #f0f8ff, #e6f3ff);
    transform: scale(1.05);
  }
  
  .payment-option-btn {
    transition: all 0.3s ease;
  }
  
  .payment-option-btn:hover {
    transform: translateY(-1px);
  }
  
  .step-content {
    transition: all 0.3s ease;
  }
  
  .step-indicator {
    transition: all 0.3s ease;
  }
  
  /* Modal animations */
  @keyframes slideInUp {
    from {
      opacity: 0;
      transform: translateY(20px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
  
  .step-content:not(.hidden) {
    animation: slideInUp 0.3s ease-out;
  }
`;
document.head.appendChild(style);

// Initialize live activity on page load
document.addEventListener('DOMContentLoaded', function() {
  initializeLiveActivity();
  initializeBannerSlider();
});

// Enhanced wishlist functionality
function getBaseProductName(title) {
  // Remove trailing #number or similar patterns (e.g., 'Mario Kart World #8' -> 'Mario Kart World')
  return title.replace(/\s*#\d+$/, '').trim();
}

function toggleWishlist(raffleId, raffleTitle) {
  <?php if (!$currentUser): ?>
    showNotification('Please log in to save items to your wishlist', 'info');
    openLoginModal();
    return;
  <?php endif; ?>

  // Find all raffles with the exact same title
  const exactMatches = raffles.filter(r => r.title === raffleTitle);
  let wishlist = JSON.parse(localStorage.getItem('user_wishlist') || '[]');

  // If ANY of the exact matches are wishlisted, remove all with that title
  const anyWishlisted = exactMatches.some(r => wishlist.includes(r.id));
  if (anyWishlisted) {
    wishlist = wishlist.filter(id => !exactMatches.some(r => r.id === id));
  } else {
    // Add all exact matches
    exactMatches.forEach(r => {
      if (!wishlist.includes(r.id)) {
        wishlist.push(r.id);
      }
    });
    
    // Update user tag preferences when adding to wishlist
    updateUserTagPreferences(raffleId, 'wishlist');
  }

  localStorage.setItem('user_wishlist', JSON.stringify(wishlist));
  updateWishlistCount(wishlist.length);

  // Instantly update all heart icons for this title in the DOM
  document.querySelectorAll('.wishlist-btn').forEach(btn => {
    const cardTitle = btn.getAttribute('data-raffle-title');
    if (cardTitle === raffleTitle) {
      const isNowWishlisted = wishlist.includes(Number(btn.getAttribute('data-id')));
      const heartIcon = btn.querySelector('i');
      if (isNowWishlisted) {
        heartIcon.className = 'fa-solid fa-heart text-red-500 text-lg transition-all duration-300';
        btn.title = 'Remove from wishlist';
      } else {
        heartIcon.className = 'fa-regular fa-heart text-gray-400 hover:text-red-500 text-lg transition-all duration-300';
        btn.title = 'Add to wishlist';
      }
    }
  });

  // Re-render the current category for seamless update
  if (window.currentCategory) {
    render(window.currentCategory);
  }
  saveWishlistToServer(wishlist);
}

// Function to update user tag preferences
function updateUserTagPreferences(raffleId, interactionType) {
  <?php if ($currentUser): ?>
    // Get tags for this raffle and update preferences
    fetch(`api/tags.php?action=get_raffle_tags&raffle_id=${raffleId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success && data.data.length > 0) {
          // Update preferences for each tag
          data.data.forEach(tag => {
            fetch('api/tags.php?action=update_user_preference', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                tag_name: tag.tag_name,
                interaction_type: interactionType
              })
            }).catch(error => console.warn('Could not update tag preference:', error));
          });
        }
      })
      .catch(error => console.warn('Could not get raffle tags:', error));
  <?php endif; ?>
}

function updateHeartIcon(raffleId, isWishlisted) {
  const heartBtn = document.querySelector(`.wishlist-btn[data-id="${raffleId}"]`);
  if (heartBtn) {
    const heartIcon = heartBtn.querySelector('i');
    if (isWishlisted) {
      heartIcon.className = 'fa-solid fa-heart text-red-500 text-lg transition-all duration-300';
      heartBtn.title = 'Remove from wishlist';
    } else {
      heartIcon.className = 'fa-regular fa-heart text-gray-400 hover:text-red-500 text-lg transition-all duration-300';
      heartBtn.title = 'Add to wishlist';
    }
  }
}

function animateHeartIcon(raffleId) {
  const heartBtn = document.querySelector(`.wishlist-btn[data-id="${raffleId}"]`);
  if (heartBtn) {
    heartBtn.style.transform = 'scale(1.3)';
    setTimeout(() => {
      heartBtn.style.transform = 'scale(1)';
    }, 200);
  }
}

async function saveWishlistToServer(wishlist) {
  try {
    console.log('Saving wishlist to server:', wishlist);
    const response = await fetch('api/wishlist.php?action=update', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        wishlist: wishlist
      })
    });
    
    const result = await response.json();
    console.log('Server response:', result);
    if (!result.success) {
      console.warn('Failed to save wishlist to server:', result.error);
    } else {
      console.log('Wishlist saved successfully');
    }
  } catch (error) {
    console.warn('Could not save wishlist to server:', error);
  }
}

// Load user's wishlist on page load
function loadUserWishlist() {
  <?php if ($currentUser): ?>
    // Load from server if user is logged in
    fetch('api/wishlist.php?action=get')
      .then(response => response.json())
      .then(result => {
        if (result.success && result.data) {
          localStorage.setItem('user_wishlist', JSON.stringify(result.data));
          updateWishlistCount(result.data.length);
        }
      })
      .catch(error => {
        console.warn('Could not load wishlist from server:', error);
      });
  <?php else: ?>
    // For guests, load from localStorage
    const wishlist = JSON.parse(localStorage.getItem('user_wishlist') || '[]');
    updateWishlistCount(wishlist.length);
  <?php endif; ?>
}

// Update wishlist count in navigation
function updateWishlistCount(count) {
  const countElement = document.getElementById('wishlist-count');
  if (countElement) {
    countElement.textContent = count;
    if (count === 0) {
      countElement.style.display = 'none';
    } else {
      countElement.style.display = 'block';
    }
  }
}

// Show wishlist modal
function showWishlist() {
  <?php if (!$currentUser): ?>
    showNotification('Please log in to view your wishlist', 'info');
    openLoginModal();
    return;
  <?php endif; ?>
  
  const wishlist = JSON.parse(localStorage.getItem('user_wishlist') || '[]');
  
  if (wishlist.length === 0) {
    showNotification('Your wishlist is empty. Start adding items you love! 💖', 'info');
    return;
  }
  
  // Filter raffles to show only wishlisted items
  const wishlistRaffles = raffles.filter(r => wishlist.includes(r.id));
  
  // Create modal content
  const modal = document.createElement('div');
  modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4';
  modal.innerHTML = `
    <div class="bg-white rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="bg-gradient-to-r from-red-500 to-pink-500 p-6 text-white relative">
        <button onclick="closeWishlistModal()" class="absolute top-4 right-4 text-white hover:text-gray-200 text-xl">
          <i class="fa-solid fa-times"></i>
        </button>
        <div class="text-center">
          <h3 class="font-heading text-2xl font-bold mb-1">My Wishlist</h3>
          <p class="text-white/90 text-sm">${wishlistRaffles.length} items you love</p>
        </div>
      </div>
      
      <!-- Wishlist Items -->
      <div class="p-6 overflow-y-auto max-h-[60vh]">
        ${wishlistRaffles.length > 0 ? `
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            ${wishlistRaffles.map(r => `
              <div class="bg-gray-50 rounded-xl p-4 flex items-center gap-3">
                <img src="${r.image_url || 'images/placeholder.jpg'}" alt="${r.title}" class="w-16 h-16 rounded-lg object-cover">
                <div class="flex-1 min-w-0">
                  <h4 class="font-bold text-gray-900 truncate">${r.title}</h4>
                  <p class="text-sm text-gray-600">${r.category}</p>
                  <p class="text-xs text-gray-500">${r.sold}/${r.total} sold</p>
                </div>
                <div class="flex flex-col gap-2">
                  <button onclick="removeFromWishlist(${r.id}, '${r.title}')" class="text-red-500 hover:text-red-700">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                  <button onclick="viewRaffle(${r.id})" class="text-ps-blue hover:text-ps-light">
                    <i class="fa-solid fa-eye"></i>
                  </button>
                </div>
              </div>
            `).join('')}
          </div>
        ` : `
          <div class="text-center py-8">
            <i class="fa-regular fa-heart text-6xl text-gray-300 mb-4"></i>
            <h4 class="text-xl font-bold text-gray-600 mb-2">Your wishlist is empty</h4>
            <p class="text-gray-500 mb-4">Start adding items you love by clicking the heart icon on any raffle!</p>
            <button onclick="closeWishlistModal()" class="bg-ps-blue text-white px-6 py-2 rounded-lg hover:bg-ps-light transition">
              Browse Raffles
            </button>
          </div>
        `}
      </div>
      
      <!-- Footer -->
      <div class="border-t border-gray-200 p-4 flex justify-between items-center">
        <span class="text-sm text-gray-600">${wishlistRaffles.length} items in wishlist</span>
        <button onclick="closeWishlistModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
          Close
        </button>
      </div>
    </div>
  `;
  
  document.body.appendChild(modal);
}

function closeWishlistModal() {
  const modal = document.querySelector('.fixed.inset-0');
  if (modal) modal.remove();
}

function removeFromWishlist(raffleId, raffleTitle) {
  let wishlist = JSON.parse(localStorage.getItem('user_wishlist') || '[]');
  wishlist = wishlist.filter(id => id !== raffleId);
  localStorage.setItem('user_wishlist', JSON.stringify(wishlist));
  
  updateWishlistCount(wishlist.length);
  showNotification(`Removed "${raffleTitle}" from wishlist`, 'info');
  
  // Refresh wishlist modal
  closeWishlistModal();
  showWishlist();
  
  // Update "Just For U" category if we're on it
  const activeCategory = document.querySelector('.category-btn.active');
  if (activeCategory && activeCategory.dataset.category === 'Just For U') {
    render('Just For U');
  }
  
  // Save to server
  saveWishlistToServer(wishlist);
}

function viewRaffle(raffleId) {
  // Find the raffle and open product modal
  const raffle = raffles.find(r => r.id === raffleId);
  if (raffle) {
    closeWishlistModal();
    openProductModal(raffle);
  }
}

// Banner Slider Functionality
function initializeBannerSlider() {
  const slides = document.querySelectorAll('.banner-slide');
  const dots = document.querySelectorAll('.slide-dot');
  const prevBtn = document.getElementById('prevSlide');
  const nextBtn = document.getElementById('nextSlide');
  
  if (slides.length <= 1) return;
  
  let currentSlide = 0;
  let slideInterval;
  
  function showSlide(index) {
    // Hide all slides
    slides.forEach(slide => {
      slide.classList.remove('active', 'fade-in');
      slide.classList.add('fade-out');
    });
    
    // Remove active class from all dots
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Show current slide
    slides[index].classList.remove('fade-out');
    slides[index].classList.add('active', 'fade-in');
    
    // Activate current dot
    if (dots[index]) {
      dots[index].classList.add('active');
    }
    
    currentSlide = index;
  }
  
  function nextSlide() {
    const next = (currentSlide + 1) % slides.length;
    showSlide(next);
  }
  
  function prevSlide() {
    const prev = (currentSlide - 1 + slides.length) % slides.length;
    showSlide(prev);
  }
  
  // Event listeners for navigation
  if (prevBtn) {
    prevBtn.addEventListener('click', () => {
      prevSlide();
      resetInterval();
    });
  }
  
  if (nextBtn) {
    nextBtn.addEventListener('click', () => {
      nextSlide();
      resetInterval();
    });
  }
  
  // Event listeners for dots
  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      showSlide(index);
      resetInterval();
    });
  });
  
  // Auto-advance slides
  function startInterval() {
    slideInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
  }
  
  function resetInterval() {
    clearInterval(slideInterval);
    startInterval();
  }
  
  // Start auto-advance
  startInterval();
  
  // Pause on hover
  const slider = document.getElementById('bannerSlider');
  if (slider) {
    slider.addEventListener('mouseenter', () => {
      clearInterval(slideInterval);
    });
    
    slider.addEventListener('mouseleave', () => {
      startInterval();
    });
  }
  
  // Keyboard navigation
  document.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowLeft') {
      prevSlide();
      resetInterval();
    } else if (e.key === 'ArrowRight') {
      nextSlide();
      resetInterval();
    }
  });
}
    </script>

    <!-- Authentication Modals -->
    <?php include 'inc/auth_modals.php'; ?>
    
    <!-- Payment Components -->
    <?php include 'inc/payment_components.php'; ?>
    
    <!-- Enhanced Purchase UI -->
    <?php 
    if ($currentUser) {
        echo $enhancedUI->renderPurchaseModals();
        echo $enhancedUI->renderAchievementSystem($currentUser['id']);
    }
    ?>

</body>
</html> 