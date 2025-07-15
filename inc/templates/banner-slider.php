<?php
// Banner slider template component
// Usage: include this file after setting $bannerSlides variable

// Get banner slides from database
$bannerSlides = [];
try {
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
    error_log("Banner slides query failed: " . $e->getMessage());
}

// If no slides from database, show default promotional slides
if (empty($bannerSlides)) {
    $bannerSlides = [
        [
            'id' => 1,
            'title' => 'Welcome to RaffLah!',
            'subtitle' => 'Your chance to win amazing prizes',
            'image_url' => 'images/banner-default.jpg',
            'link_url' => '#',
            'background_color' => 'from-ps-blue to-ps-light'
        ],
        [
            'id' => 2,
            'title' => 'Big Prizes Await',
            'subtitle' => 'Enter our exciting raffles today',
            'image_url' => 'images/banner-prizes.jpg',
            'link_url' => '#',
            'background_color' => 'from-ps-yellow to-orange-400'
        ]
    ];
}
?>

<?php if (count($bannerSlides) > 0): ?>
<!-- Banner Slider -->
<div id="bannerSlider" class="relative w-full h-64 md:h-80 lg:h-96 overflow-hidden rounded-2xl shadow-ps-lg mb-8 banner-slider">
    <!-- Slides -->
    <?php foreach ($bannerSlides as $index => $slide): ?>
        <div class="banner-slide absolute inset-0 flex items-center justify-center <?php echo $index === 0 ? 'active' : ''; ?>" 
             style="background: linear-gradient(135deg, <?php echo $slide['background_color'] ?? 'var(--ps-blue), var(--ps-light)'; ?>);">
            
            <!-- Slide Content -->
            <div class="relative z-10 text-center text-white px-6 md:px-12">
                <h2 class="font-heading text-2xl md:text-4xl lg:text-5xl font-bold mb-2 md:mb-4 drop-shadow-lg">
                    <?php echo htmlspecialchars($slide['title']); ?>
                </h2>
                
                <?php if (!empty($slide['subtitle'])): ?>
                    <p class="text-lg md:text-xl lg:text-2xl mb-4 md:mb-6 drop-shadow-md opacity-90">
                        <?php echo htmlspecialchars($slide['subtitle']); ?>
                    </p>
                <?php endif; ?>
                
                <?php if (!empty($slide['link_url']) && $slide['link_url'] !== '#'): ?>
                    <a href="<?php echo htmlspecialchars($slide['link_url']); ?>" 
                       class="inline-block bg-white text-ps-blue px-6 md:px-8 py-3 md:py-4 rounded-full font-heading font-bold text-sm md:text-base hover:bg-gray-100 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        Explore Now
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Background Image Overlay -->
            <?php if (!empty($slide['image_url'])): ?>
                <div class="absolute inset-0 bg-black/20"></div>
                <img src="<?php echo htmlspecialchars($slide['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($slide['title']); ?>"
                     class="absolute inset-0 w-full h-full object-cover mix-blend-overlay">
            <?php endif; ?>
            
            <!-- Decorative Elements -->
            <div class="absolute top-4 left-4 w-20 h-20 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute bottom-4 right-4 w-32 h-32 bg-white/5 rounded-full blur-2xl"></div>
        </div>
    <?php endforeach; ?>
    
    <!-- Navigation Controls -->
    <?php if (count($bannerSlides) > 1): ?>
        <!-- Previous/Next Buttons -->
        <button id="prevSlide" class="absolute left-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white p-3 rounded-full transition-all duration-300 z-20">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button id="nextSlide" class="absolute right-4 top-1/2 transform -translate-y-1/2 bg-white/20 hover:bg-white/30 backdrop-blur-sm text-white p-3 rounded-full transition-all duration-300 z-20">
            <i class="fa-solid fa-chevron-right"></i>
        </button>
        
        <!-- Dots Navigation -->
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex space-x-2 z-20">
            <?php foreach ($bannerSlides as $index => $slide): ?>
                <button class="slide-dot w-3 h-3 rounded-full bg-white/40 hover:bg-white/60 transition-all duration-300 <?php echo $index === 0 ? 'active bg-white' : ''; ?>"
                        data-slide="<?php echo $index; ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Loading Indicator -->
    <div class="absolute inset-0 bg-gradient-to-r from-gray-200 to-gray-300 animate-pulse rounded-2xl hidden" id="bannerLoading">
        <div class="absolute inset-0 flex items-center justify-center">
            <div class="w-8 h-8 border-4 border-ps-blue border-t-transparent rounded-full animate-spin"></div>
        </div>
    </div>
</div>

<!-- Banner Slider Styles -->
<style>
.banner-slide {
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);
}

.banner-slide.active {
    opacity: 1;
    transform: translateX(0);
}

.banner-slide.fade-in {
    animation: bannerSlideIn 0.8s ease-out;
}

.banner-slide.fade-out {
    animation: bannerSlideOut 0.8s ease-out;
}

@keyframes bannerSlideIn {
    from {
        opacity: 0;
        transform: translateX(50px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes bannerSlideOut {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(-50px);
    }
}

.slide-dot.active {
    background: white !important;
    transform: scale(1.2);
}

.slide-dot:hover {
    transform: scale(1.1);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .banner-slide {
        border-radius: 16px;
    }
    
    #prevSlide, #nextSlide {
        display: none;
    }
    
    .slide-dot {
        width: 8px;
        height: 8px;
    }
}
</style>
<?php endif; ?> 