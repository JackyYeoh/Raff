<?php

class HomeController {
    private $pdo;
    private $auth;
    private $purchaseStrategies;
    private $enhancedUI;
    
    public function __construct($pdo = null) {
        if ($pdo === null) {
            global $pdo;
        }
        require_once 'inc/user_auth.php';
        require_once 'inc/purchase_strategies.php';
        require_once 'inc/enhanced_purchase_ui.php';
        require_once 'inc/page_components.php';
        
        $this->pdo = $pdo;
        $this->auth = new UserAuth();
        $this->purchaseStrategies = new PurchaseStrategies();
        $this->enhancedUI = new EnhancedPurchaseUI();
    }
    
    public function getPageData() {
        $data = [
            'currentUser' => $this->auth->getCurrentUser(),
            'categories' => $this->getCategories(),
            'brands_table_exists' => $this->checkBrandsTable(),
            'liveActivity' => $this->getLiveActivity(),
            'userAchievements' => $this->getUserAchievements(),
            'raffles' => $this->processRaffles(),
            'bannerSlides' => $this->getBannerSlides(),
            'achievements' => $this->getAchievements(),
            'targetDate' => '2025-07-01T18:00:00+08:00'
        ];
        
        // Process special categories and grouped raffles
        $data['specialRaffles'] = $this->getSpecialRaffles($data['raffles'], $data['currentUser']);
        $data['categoriesWithRaffles'] = $this->getCategoriesWithRaffles($data['categories'], $data['raffles']);
        $data['groupedRaffles'] = $this->groupRafflesByBrand($data['raffles'], $data['categories']);
        
        return $data;
    }
    
    private function getCategories() {
        $stmt = $this->pdo->query("SELECT *, COALESCE(is_active, 1) as is_active, COALESCE(show_brands, 1) as show_brands FROM categories WHERE COALESCE(is_active, 1) = 1 ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function checkBrandsTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'brands'");
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function getLiveActivity() {
        $stmt = $this->pdo->query("
            SELECT * FROM live_activity 
            WHERE is_visible = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getUserAchievements() {
        $currentUser = $this->auth->getCurrentUser();
        if (!$currentUser) {
            return [];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT a.name, a.icon, a.badge_color, ua.earned_at
            FROM user_achievements ua
            JOIN achievements a ON ua.achievement_id = a.id
            WHERE ua.user_id = ?
            ORDER BY ua.earned_at DESC
        ");
        $stmt->execute([$currentUser['id']]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function processRaffles() {
        $currentUser = $this->auth->getCurrentUser();
        $brands_table_exists = $this->checkBrandsTable();
        
        if ($brands_table_exists) {
            $stmt = $this->pdo->query("
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
            $stmt = $this->pdo->query("
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
                'rm1_strategy' => $this->purchaseStrategies->getOptimalStrategy($currentUser, $row, $soldPercentage, $hoursRemaining)
            ];
        }
        
        return $raffles;
    }
    
    private function getSpecialRaffles($raffles, $currentUser) {
        return [
            'Just For U' => getJustForU($raffles, $currentUser, $this->pdo),
            'Hot Products' => getHotProducts($raffles),
            'Selling Fast' => getSellingFast($raffles),
        ];
    }
    
    private function getCategoriesWithRaffles($categories, $raffles) {
        return getCategoriesWithRaffles($categories, $raffles);
    }
    
    private function groupRafflesByBrand($raffles, $categories) {
        return groupRafflesByBrand($raffles, $categories);
    }
    
    public function getEnhancedUI() {
        return $this->enhancedUI;
    }
    
    public function getCurrentUser() {
        return $this->auth->getCurrentUser();
    }
    
    private function getBannerSlides() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, title, subtitle, description, image_url, button_text, button_url, is_active, sort_order
                FROM banner_slides 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, id ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If banner_slides table doesn't exist, return default slides
            return [
                [
                    'id' => 1,
                    'title' => 'LABUBU!',
                    'subtitle' => 'THE MONSTERS Big Into Energy Series',
                    'description' => 'Win amazing prizes with just RM1 tickets',
                    'image_url' => 'images/placeholder.jpg',
                    'button_text' => 'Play Now',
                    'button_url' => '#raffle-section',
                    'is_active' => 1,
                    'sort_order' => 1
                ]
            ];
        }
    }
    
    private function getAchievements() {
        try {
            $stmt = $this->pdo->query("
                SELECT id, name, description, icon, badge_color, condition_type, condition_value, reward_type, reward_value, is_active
                FROM achievements 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, id ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // If achievements table doesn't exist, return empty array
            return [];
        }
    }
} 