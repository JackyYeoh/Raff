<?php
define('BASE_URL', '/raffle-demo');
require_once 'inc/database.php';
require_once 'inc/user_auth.php';

// Initialize authentication
$auth = new UserAuth();
$currentUser = $auth->getCurrentUser();

// Get search parameters
$query = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$price_range = $_GET['price_range'] ?? '';
$availability = $_GET['availability'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'relevance';

// Get search results via API
$search_url = BASE_URL . '/api/search.php?' . http_build_query([
    'q' => $query,
    'category' => $category,
    'brand' => $brand,
    'price_range' => $price_range,
    'availability' => $availability,
    'sort_by' => $sort_by,
    'limit' => 50
]);

$search_results = [];
$suggestions = [];
$filters = [];
$total_results = 0;

try {
    $response = file_get_contents($search_url);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            $search_results = $data['data']['results'];
            $suggestions = $data['data']['suggestions'];
            $filters = $data['data']['filters'];
            $total_results = $data['data']['total_results'];
        }
    }
} catch (Exception $e) {
    // Handle error silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Search Results - RaffLah!</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Poppins:wght@600;700&display=swap" rel="stylesheet">
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
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/frontend.css">
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-purple-50 min-h-screen">
    <!-- Header -->
    <?php include 'inc/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Search Header -->
        <div class="mb-8">
            <div class="flex items-center gap-4 mb-4">
                <a href="index.php" class="text-ps-blue hover:text-ps-light">
                    <i class="fa-solid fa-arrow-left"></i> Back to Home
                </a>
                <h1 class="font-heading text-2xl font-bold text-gray-900">
                    Search Results
                    <?php if ($query): ?>
                        for "<?php echo htmlspecialchars($query); ?>"
                    <?php endif; ?>
                </h1>
            </div>
            
            <!-- Search Stats -->
            <div class="flex items-center gap-4 text-sm text-gray-600">
                <span><?php echo $total_results; ?> results found</span>
                <?php if ($query || $category || $brand || $price_range || $availability): ?>
                    <span>•</span>
                    <span>Filters applied</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:w-80 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-ps p-6 sticky top-4">
                    <h3 class="font-heading text-lg font-bold text-gray-900 mb-4">Filters</h3>
                    
                    <form method="GET" action="search-results.php" class="space-y-6">
                        <!-- Search Query -->
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                        
                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ps-blue focus:border-transparent">
                                <option value="">All Categories</option>
                                <?php foreach ($filters['categories'] ?? [] as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                            <?php echo $category === $cat['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Brand Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Brand</label>
                            <select name="brand" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ps-blue focus:border-transparent">
                                <option value="">All Brands</option>
                                <?php foreach ($filters['brands'] ?? [] as $brand_item): ?>
                                    <option value="<?php echo htmlspecialchars($brand_item['name']); ?>" 
                                            <?php echo $brand === $brand_item['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($brand_item['name']); ?> (<?php echo $brand_item['count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Price Range Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Price Range</label>
                            <select name="price_range" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ps-blue focus:border-transparent">
                                <option value="">All Prices</option>
                                <option value="rm1_only" <?php echo $price_range === 'rm1_only' ? 'selected' : ''; ?>>RM1 Only</option>
                                <option value="under_5" <?php echo $price_range === 'under_5' ? 'selected' : ''; ?>>Under RM5</option>
                                <option value="5_10" <?php echo $price_range === '5_10' ? 'selected' : ''; ?>>RM5 - RM10</option>
                                <option value="over_10" <?php echo $price_range === 'over_10' ? 'selected' : ''; ?>>Over RM10</option>
                            </select>
                        </div>
                        
                        <!-- Availability Filter -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Availability</label>
                            <select name="availability" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ps-blue focus:border-transparent">
                                <option value="">All Availability</option>
                                <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="selling_fast" <?php echo $availability === 'selling_fast' ? 'selected' : ''; ?>>Selling Fast</option>
                                <option value="almost_sold_out" <?php echo $availability === 'almost_sold_out' ? 'selected' : ''; ?>>Almost Sold Out</option>
                                <option value="new" <?php echo $availability === 'new' ? 'selected' : ''; ?>>New Arrivals</option>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sort By</label>
                            <select name="sort_by" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-ps-blue focus:border-transparent">
                                <option value="relevance" <?php echo $sort_by === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                                <option value="popularity" <?php echo $sort_by === 'popularity' ? 'selected' : ''; ?>>Most Popular</option>
                                <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="ending_soon" <?php echo $sort_by === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                                <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>
                        
                        <!-- Apply Filters Button -->
                        <button type="submit" class="w-full bg-ps-blue hover:bg-ps-light text-white font-bold py-2 px-4 rounded-lg transition">
                            Apply Filters
                        </button>
                        
                        <!-- Clear Filters -->
                        <?php if ($category || $brand || $price_range || $availability): ?>
                            <a href="search-results.php?q=<?php echo urlencode($query); ?>" 
                               class="block text-center text-ps-blue hover:text-ps-light text-sm">
                                Clear All Filters
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Search Results -->
            <div class="flex-1">
                <?php if (empty($search_results)): ?>
                    <!-- No Results -->
                    <div class="bg-white rounded-2xl shadow-ps p-8 text-center">
                        <i class="fa-solid fa-search text-6xl text-gray-300 mb-4"></i>
                        <h3 class="font-heading text-xl font-bold text-gray-900 mb-2">No results found</h3>
                        <p class="text-gray-600 mb-6">
                            <?php if ($query): ?>
                                We couldn't find any raffles matching "<?php echo htmlspecialchars($query); ?>"
                            <?php else: ?>
                                No raffles match your current filters
                            <?php endif; ?>
                        </p>
                        
                        <!-- Suggestions -->
                        <?php if (!empty($suggestions)): ?>
                            <div class="text-left">
                                <h4 class="font-semibold text-gray-900 mb-3">Try searching for:</h4>
                                <div class="space-y-2">
                                    <?php foreach ($suggestions['categories'] ?? [] as $suggestion): ?>
                                        <a href="search-results.php?q=<?php echo urlencode($suggestion['name']); ?>" 
                                           class="block text-ps-blue hover:text-ps-light">
                                            <i class="fa-solid fa-tag mr-2"></i>
                                            <?php echo htmlspecialchars($suggestion['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <?php foreach ($suggestions['brands'] ?? [] as $suggestion): ?>
                                        <a href="search-results.php?q=<?php echo urlencode($suggestion['name']); ?>" 
                                           class="block text-ps-blue hover:text-ps-light">
                                            <i class="fa-solid fa-building mr-2"></i>
                                            <?php echo htmlspecialchars($suggestion['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-6">
                            <a href="index.php" class="bg-ps-blue hover:bg-ps-light text-white font-bold py-2 px-6 rounded-lg transition">
                                Browse All Raffles
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Results Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php foreach ($search_results as $raffle): ?>
                            <div class="bg-white rounded-2xl shadow-ps overflow-hidden hover:shadow-ps-lg transition-all duration-300 transform hover:-translate-y-1">
                                <!-- Badges -->
                                <?php if (!empty($raffle['badges'])): ?>
                                    <div class="absolute top-3 left-3 z-10 flex flex-col gap-1">
                                        <?php foreach (array_slice($raffle['badges'], 0, 2) as $badge): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-bold text-white
                                                <?php 
                                                switch ($badge['color']) {
                                                    case 'red': echo 'bg-red-500'; break;
                                                    case 'orange': echo 'bg-orange-500'; break;
                                                    case 'green': echo 'bg-green-500'; break;
                                                    case 'blue': echo 'bg-blue-500'; break;
                                                    default: echo 'bg-gray-500';
                                                }
                                                ?>">
                                                <?php echo htmlspecialchars($badge['text']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Image -->
                                <div class="relative h-48 bg-gray-100">
                                    <img src="<?php echo htmlspecialchars($raffle['image_url'] ?: 'images/placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($raffle['title']); ?>"
                                         class="w-full h-full object-cover"
                                         onerror="this.src='images/placeholder.jpg';">
                                </div>
                                
                                <!-- Content -->
                                <div class="p-4">
                                    <div class="text-xs text-gray-500 mb-1">
                                        <?php echo htmlspecialchars($raffle['category']); ?>
                                        <?php if ($raffle['brand_name']): ?>
                                            • <?php echo htmlspecialchars($raffle['brand_name']); ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="font-heading font-bold text-gray-900 mb-2 line-clamp-2">
                                        <?php echo htmlspecialchars($raffle['title']); ?>
                                    </h3>
                                    
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="bg-ps-blue text-white font-bold px-3 py-1 rounded-full text-sm">
                                            RM<?php echo number_format($raffle['ticket_price'], 2); ?>
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            <?php echo $raffle['sold_tickets']; ?>/<?php echo $raffle['total_tickets']; ?> sold
                                        </span>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                                        <div class="bg-gradient-to-r from-ps-blue to-ps-light h-2 rounded-full" 
                                             style="width: <?php echo $raffle['completion_percentage']; ?>%"></div>
                                    </div>
                                    
                                    <!-- Action Button -->
                                    <a href="raffle_details.php?id=<?php echo $raffle['id']; ?>" 
                                       class="block w-full bg-ps-blue hover:bg-ps-light text-white font-bold py-2 px-4 rounded-lg text-center transition">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Load More Button -->
                    <?php if (count($search_results) >= 50): ?>
                        <div class="text-center mt-8">
                            <button onclick="loadMoreResults()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-6 rounded-lg transition">
                                Load More Results
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'inc/footer.php'; ?>

    <script>
        // Load more results functionality
        function loadMoreResults() {
            // Implementation for pagination
            console.log('Load more results');
        }
        
        // Auto-submit form when filters change
        document.querySelectorAll('select[name="category"], select[name="brand"], select[name="price_range"], select[name="availability"], select[name="sort_by"]').forEach(select => {
            select.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });
    </script>
</body>
</html> 