<?php
// Initialize the application
session_start();
require_once 'inc/database.php';
require_once 'inc/user_auth.php';
require_once 'inc/controllers/HomeController.php';

// Initialize controller and get page data
$controller = new HomeController($pdo);
$pageData = $controller->getPageData();

// Extract data for easier access
$currentUser = $pageData['currentUser'];
$categoriesWithRaffles = $pageData['categories'];
$raffles = $pageData['raffles'];
$bannerSlides = $pageData['bannerSlides'];
$achievements = $pageData['achievements'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Raffle Lab - Where Dreams Meet Tickets</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/liquid-glass.css">
  <link rel="stylesheet" href="css/premium-components.css">
  <link rel="stylesheet" href="css/admin-components.css">
  <link rel="stylesheet" href="css/enhanced-modals.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'sans': ['Inter', 'sans-serif'],
            'heading': ['Lexend', 'sans-serif'],
          },
          colors: {
            'ps-blue': '#0070d1',
            'ps-light': '#4a9eff',
            'ps-yellow': '#ffcc00',
            'ps-pink': '#ff6b9d',
            'ps-text': '#2c3e50',
            'ps-silver': '#7f8c8d',
            'ps-bg': '#f8fafc',
          },
          boxShadow: {
            'ps': '0 4px 20px rgba(0, 112, 209, 0.1)',
            'ps-lg': '0 8px 30px rgba(0, 112, 209, 0.15)',
            'ps-xl': '0 12px 40px rgba(0, 112, 209, 0.2)',
          },
          animation: {
            'subtleFloat': 'subtleFloat 8s ease-in-out infinite',
            'liquid-wave': 'liquid-wave 3s ease-in-out infinite',
            'slide-in': 'slide-in 0.5s ease-out',
          },
        }
      }
    }
  </script>
</head>
<body class="bg-ps-bg overflow-x-hidden">
  <?php include 'inc/templates/header.php'; ?>

  <!-- 1. Hero Banner Section -->
  <section class="w-full max-w-7xl mx-auto px-2 md:px-8 mt-6">
    <?php include 'inc/templates/banner-slider.php'; ?>
  </section>

  <!-- 2. Stats + Daily Check-in Section -->
  <?php if ($currentUser): ?>
  <section class="w-full max-w-7xl mx-auto px-2 md:px-8 mt-8 mb-8">
    <div class="flex flex-col md:flex-row gap-4">
      <!-- User Info Card -->
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
            Hi, <?= htmlspecialchars($currentUser['name']) ?>!
          </div>
          <a href="dashboard.php" class="ml-auto text-xs text-ps-blue hover:underline font-semibold">My Dashboard</a>
        </div>

        <!-- Wallet Row -->
        <div class="flex items-center gap-2 mb-1 relative z-10">
          <span class="text-lg font-bold text-ps-blue flex items-center gap-1">
            <i class="fa-solid fa-wallet mr-1"></i>
            RM<?= number_format($currentUser['wallet_balance'] ?? 0, 2) ?>
          </span>
          <button onclick="openWalletTopupModal()" class="bg-ps-blue hover:bg-ps-light text-white text-xs font-bold px-2 py-0.5 rounded-full shadow transition">Top Up</button>
          <button class="bg-gray-100 hover:bg-ps-blue/10 text-ps-blue text-xs font-bold px-2 py-0.5 rounded-full shadow transition">Withdraw</button>
          <span class="text-xs text-ps-silver cursor-pointer ml-1" title="Use your wallet to buy tickets instantly."><i class="fa-solid fa-circle-info"></i></span>
        </div>

        <hr class="my-1 border-gray-100 relative z-10">

        <!-- Stats Row -->
        <div class="flex justify-between items-end mb-2 mt-2 relative z-10">
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
        </div>

        <!-- Action Row -->
        <div class="flex gap-2 mt-1 relative z-10">
          <a href="#" class="bg-ps-blue hover:bg-ps-light text-white font-bold px-3 py-1 rounded-full shadow transition text-xs flex items-center gap-1"><i class="fa-solid fa-ticket"></i> Buy</a>
          <a href="#" class="bg-gray-100 hover:bg-ps-blue/10 text-ps-blue font-bold px-3 py-1 rounded-full shadow transition text-xs flex items-center gap-1"><i class="fa-solid fa-clock-rotate-left"></i> History</a>
        </div>
      </div>

      <!-- Daily Check-in Card -->
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
        
        <!-- Days Row -->
        <div class="grid grid-cols-7 gap-1 w-full mb-3 mt-2 relative z-10" id="checkin-calendar">
          <!-- Will be populated by JavaScript -->
        </div>
        
        <!-- Progress bar for week completion -->
        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden mb-3 relative z-10">
          <div class="h-full bg-red-500 rounded-full transition-all duration-700 relative" id="streak-progress" style="width: 71%">
            <div class="absolute inset-0 bg-white/20 rounded-full animate-pulse"></div>
          </div>
          <div class="text-xs text-ps-silver mt-1 text-center">
            <span id="progress-text">5 of 7 days this week</span>
          </div>
        </div>
        
        <!-- Reward and Check-in Row -->
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
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- 3. Category & Items Section -->
  <div id="raffle-section" class="max-w-7xl mx-auto px-2 md:px-8 mt-10 mb-24">
    <!-- Section Header -->
    <div class="text-center mb-8">
      <h2 class="font-heading text-3xl md:text-4xl font-bold text-gray-900 mb-2">Explore Categories</h2>
      <p class="text-gray-600 text-lg">Discover amazing prizes across different categories</p>
    </div>

    <!-- Premium Category Tab Bar -->
    <nav id="category-bar" class="premium-category-bar relative flex gap-2 overflow-x-auto snap-x snap-mandatory mb-8 w-full scrollbar-hide" role="tablist">
      <!-- left fade -->
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
        <button class="premium-category-btn category-btn flex items-center px-4 md:px-6 py-3 md:py-4 relative flex-shrink-0 snap-start focus:outline-none focus:ring-2 focus:ring-ps-blue"
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
      <!-- right fade -->
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

          <!-- Wishlist Heart -->
          <button class="wishlist-btn absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-white/20 backdrop-blur-md flex items-center justify-center text-white hover:bg-white/30 transition-all duration-200" 
                  data-raffle-id="<?php echo $raffle['id']; ?>" 
                  data-raffle-title="<?php echo htmlspecialchars($raffle['title']); ?>"
                  title="Add to Wishlist">
            <i class="fa-solid fa-heart text-sm"></i>
          </button>

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

            <div class="text-xs text-ps-silver mb-3 text-center">
              <i class="fa-solid fa-eye text-ps-blue mr-1"></i>
              <span class="text-ps-blue font-semibold"><?php echo rand(15, 85); ?> people viewing</span>
            </div>

            <div class="flex items-center gap-2 w-full">
              <div class="flex items-center border rounded-full overflow-hidden bg-white">
                <button class="qty-btn minus px-2 py-1 hover:bg-gray-100 transition-colors" data-action="minus">
                  <i class="fa-solid fa-minus text-xs"></i>
                </button>
                <input type="number" class="qty-input w-12 text-center border-0 outline-none font-bold text-sm" value="1" min="1" max="10">
                <button class="qty-btn plus px-2 py-1 hover:bg-gray-100 transition-colors" data-action="plus">
                  <i class="fa-solid fa-plus text-xs"></i>
                </button>
              </div>
              <button class="buy-btn flex-1 bg-ps-blue hover:bg-ps-light text-white font-bold py-2 px-4 rounded-full transition-all duration-200 text-sm flex items-center justify-center gap-2 shadow-ps group"
                      data-raffle-id="<?php echo $raffle['id']; ?>" 
                      data-raffle-title="<?php echo htmlspecialchars($raffle['title']); ?>"
                      data-raffle-price="<?php echo $raffle['price']; ?>">
                <i class="fa-solid fa-ticket group-hover:scale-110 transition-transform"></i>
                <span>Buy</span>
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Include authentication modals -->
  <?php include 'inc/auth_modals.php'; ?>

  <!-- JavaScript -->
  <script>
    // Pass PHP data to JavaScript
    window.pageData = <?php echo json_encode($pageData); ?>;
    window.currentUser = <?php echo json_encode($currentUser); ?>;
  </script>
  <script src="js/wishlist-manager.js"></script>
  <script src="js/app.js"></script>
</body>
</html> 