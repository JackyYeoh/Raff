<?php 
include __DIR__ . '/../inc/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_brand':
                $name = trim($_POST['name']);
                $slug = strtolower(str_replace(' ', '-', $name));
                $description = trim($_POST['description']);
                $website_url = trim($_POST['website_url']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $category_ids = $_POST['category_ids'] ?? [];
                
                // Handle image upload
                $image_url = '';
                if (isset($_FILES['brand_image']) && $_FILES['brand_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/brands/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['brand_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = $slug . '_' . time() . '.' . $file_extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['brand_image']['tmp_name'], $filepath)) {
                            $image_url = 'images/brands/' . $filename;
                        }
                    }
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // Insert brand with image_url
                    $stmt = $pdo->prepare("INSERT INTO brands (name, slug, description, website_url, image_url, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $slug, $description, $website_url, $image_url, $is_featured]);
                    $brand_id = $pdo->lastInsertId();
                    
                    // Insert brand-category relationships with unique category_sort_order
                    if (!empty($category_ids)) {
                        foreach ($category_ids as $category_id) {
                            // Get next sort order for this category
                            $stmt = $pdo->prepare("SELECT COALESCE(MAX(category_sort_order), 0) + 1 FROM brand_categories WHERE category_id = ?");
                            $stmt->execute([$category_id]);
                            $next_order = $stmt->fetchColumn();
                            $stmt = $pdo->prepare("INSERT INTO brand_categories (brand_id, category_id, category_sort_order) VALUES (?, ?, ?)");
                            $stmt->execute([$brand_id, $category_id, $next_order]);
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['flash_message'] = "Brand '$name' added successfully!";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Error adding brand: " . $e->getMessage();
                }
                break;
                
            case 'edit_brand':
                $brand_id = $_POST['brand_id'];
                $name = trim($_POST['name']);
                $slug = strtolower(str_replace(' ', '-', $name));
                $description = trim($_POST['description']);
                $website_url = trim($_POST['website_url']);
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $category_ids = $_POST['category_ids'] ?? [];
                
                // Handle image upload
                $image_url = $_POST['current_image_url'] ?? '';
                if (isset($_FILES['brand_image']) && $_FILES['brand_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/brands/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['brand_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = $slug . '_' . time() . '.' . $file_extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['brand_image']['tmp_name'], $filepath)) {
                            // Delete old image if exists
                            if ($image_url && file_exists('../' . $image_url)) {
                                unlink('../' . $image_url);
                            }
                            $image_url = 'images/brands/' . $filename;
                        }
                    }
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // Update brand with image_url
                    $stmt = $pdo->prepare("UPDATE brands SET name = ?, slug = ?, description = ?, website_url = ?, image_url = ?, is_featured = ? WHERE id = ?");
                    $stmt->execute([$name, $slug, $description, $website_url, $image_url, $is_featured, $brand_id]);
                    
                    // Delete existing brand-category relationships
                    $stmt = $pdo->prepare("DELETE FROM brand_categories WHERE brand_id = ?");
                    $stmt->execute([$brand_id]);
                    
                    // Insert new brand-category relationships with unique category_sort_order
                    if (!empty($category_ids)) {
                        foreach ($category_ids as $category_id) {
                            $stmt = $pdo->prepare("SELECT COALESCE(MAX(category_sort_order), 0) + 1 FROM brand_categories WHERE category_id = ?");
                            $stmt->execute([$category_id]);
                            $next_order = $stmt->fetchColumn();
                            $stmt = $pdo->prepare("INSERT INTO brand_categories (brand_id, category_id, category_sort_order) VALUES (?, ?, ?)");
                            $stmt->execute([$brand_id, $category_id, $next_order]);
                        }
                    }
                    
                    $pdo->commit();
                    $_SESSION['flash_message'] = "Brand '$name' updated successfully!";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Error updating brand: " . $e->getMessage();
                }
                break;
                
            case 'delete_brand':
                $brand_id = $_POST['brand_id'];
                try {
                    $pdo->beginTransaction();
                    
                    // Check if brand has raffles
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM raffles WHERE brand_id = ?");
                    $stmt->execute([$brand_id]);
                    $raffle_count = $stmt->fetchColumn();
                    
                    if ($raffle_count > 0) {
                        $_SESSION['flash_error'] = "Cannot delete brand: It has $raffle_count raffle(s) associated with it.";
                    } else {
                        // Get brand image before deletion
                        $stmt = $pdo->prepare("SELECT image_url FROM brands WHERE id = ?");
                        $stmt->execute([$brand_id]);
                        $brand_image = $stmt->fetchColumn();
                        
                        // Delete brand-category relationships first
                        $stmt = $pdo->prepare("DELETE FROM brand_categories WHERE brand_id = ?");
                        $stmt->execute([$brand_id]);
                        
                        // Delete brand
                        $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
                        $stmt->execute([$brand_id]);
                        
                        // Delete brand image file if exists
                        if ($brand_image && file_exists('../' . $brand_image)) {
                            unlink('../' . $brand_image);
                        }
                        
                        $pdo->commit();
                        $_SESSION['flash_message'] = "Brand deleted successfully!";
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Error deleting brand: " . $e->getMessage();
                }
                break;
                
            case 'update_brand_order':
                $order_data = json_decode($_POST['order'], true);
                $category_id = $_POST['category_id'] ?? null;
                
                if (!$order_data || !$category_id) {
                    echo 'error: Invalid order data or missing category';
                    exit;
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    // Update category-specific ordering
                    $stmt = $pdo->prepare("UPDATE brand_categories SET category_sort_order = ? WHERE brand_id = ? AND category_id = ?");
                    $updated_count = 0;
                    
                    foreach ($order_data as $item) {
                        $stmt->execute([$item['sort_order'], $item['id'], $category_id]);
                        $updated_count++;
                    }
                    
                    $pdo->commit();
                    echo 'success: Updated ' . $updated_count . ' brands';
                    exit;
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    echo 'error: ' . $e->getMessage();
                    exit;
                }
                break;
                
            case 'bulk_category_action':
                $selected_brands = $_POST['selected_brands'] ?? [];
                $bulk_action = $_POST['bulk_action'] ?? '';
                $target_category = $_POST['target_category'] ?? '';
                
                if (empty($selected_brands)) {
                    $_SESSION['flash_error'] = "Please select at least one brand.";
                    break;
                }
                
                try {
                    $pdo->beginTransaction();
                    
                    switch ($bulk_action) {
                        case 'add_to_category':
                            $stmt = $pdo->prepare("INSERT IGNORE INTO brand_categories (brand_id, category_id) VALUES (?, ?)");
                            foreach ($selected_brands as $brand_id) {
                                $stmt->execute([$brand_id, $target_category]);
                            }
                            $_SESSION['flash_message'] = "Added " . count($selected_brands) . " brands to category.";
                            break;
                            
                        case 'remove_from_category':
                            $stmt = $pdo->prepare("DELETE FROM brand_categories WHERE brand_id = ? AND category_id = ?");
                            foreach ($selected_brands as $brand_id) {
                                $stmt->execute([$brand_id, $target_category]);
                            }
                            $_SESSION['flash_message'] = "Removed " . count($selected_brands) . " brands from category.";
                            break;
                            
                        case 'set_featured':
                            $stmt = $pdo->prepare("UPDATE brands SET is_featured = 1 WHERE id = ?");
                            foreach ($selected_brands as $brand_id) {
                                $stmt->execute([$brand_id]);
                            }
                            $_SESSION['flash_message'] = "Set " . count($selected_brands) . " brands as featured.";
                            break;
                            
                        case 'unset_featured':
                            $stmt = $pdo->prepare("UPDATE brands SET is_featured = 0 WHERE id = ?");
                            foreach ($selected_brands as $brand_id) {
                                $stmt->execute([$brand_id]);
                            }
                            $_SESSION['flash_message'] = "Unset " . count($selected_brands) . " brands as featured.";
                            break;
                    }
                    
                    $pdo->commit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $_SESSION['flash_error'] = "Error performing bulk action: " . $e->getMessage();
                }
                break;
        }
        
        header('Location: brands.php');
        exit;
    }
}

// Get all brands with category info
$stmt = $pdo->query("
    SELECT b.*, 
           GROUP_CONCAT(c.name SEPARATOR ', ') as category_names,
           GROUP_CONCAT(c.id SEPARATOR ',') as category_ids,
           (SELECT COUNT(*) FROM raffles WHERE brand_id = b.id) as raffle_count
    FROM brands b 
    LEFT JOIN brand_categories bc ON b.id = bc.brand_id 
    LEFT JOIN categories c ON bc.category_id = c.id 
    GROUP BY b.id 
    ORDER BY b.is_featured DESC, b.name ASC
");
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all categories for form and order management
$stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_brands = count($brands);
$featured_brands = count(array_filter($brands, fn($b) => $b['is_featured']));
$brands_with_raffles = count(array_filter($brands, fn($b) => $b['raffle_count'] > 0));
?>

<style>
    /* Simplified, clean design */
    .brand-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .brand-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .brand-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .brand-card.featured {
        border-color: #f59e0b;
        background: linear-gradient(135deg, #fef3c7 0%, #ffffff 100%);
    }
    
    .brand-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
    }
    
    .brand-name {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    
    .featured-badge {
        background: #f59e0b;
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .brand-categories {
        color: #6b7280;
        font-size: 14px;
        margin-bottom: 12px;
    }
    
    .brand-stats {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
        font-size: 13px;
        color: #6b7280;
    }
    
    .brand-actions {
        display: flex;
        gap: 8px;
    }
    
    .btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-primary {
        background: #3b82f6;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2563eb;
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .btn-success {
        background: #10b981;
        color: white;
    }
    
    .btn-success:hover {
        background: #059669;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #3b82f6;
        margin-bottom: 8px;
    }
    
    .stat-label {
        color: #6b7280;
        font-size: 14px;
    }
    
    /* Tab Navigation */
    .tab-navigation {
        display: flex;
        gap: 8px;
        margin-bottom: 30px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 0;
    }
    
    .tab-button {
        padding: 12px 24px;
        border: none;
        background: #f3f4f6;
        border-radius: 8px 8px 0 0;
        cursor: pointer;
        transition: all 0.2s;
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .tab-button:hover {
        background: #e5e7eb;
        color: #374151;
    }
    
    .tab-button.active {
        background: white;
        color: #3b82f6;
        border-bottom: 2px solid #3b82f6;
        margin-bottom: -1px;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1000;
    }
    
    .modal-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        border-radius: 12px;
        padding: 30px;
        width: 90%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .modal-header h2 {
        margin: 0;
        color: #1f2937;
        font-size: 20px;
    }
    
    .close {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #6b7280;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: all 0.2s;
    }
    
    .close:hover {
        background: #f3f4f6;
        color: #374151;
    }
    
    .category-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 16px;
    }
    
    .category-tab {
        padding: 8px 16px;
        border: none;
        background: #f3f4f6;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .category-tab.active {
        background: #3b82f6;
        color: white;
    }
    
    .order-list {
        min-height: 300px;
        border: 2px dashed #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        background: #f9fafb;
    }
    
    .order-item {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: move;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.2s;
    }
    
    .order-item:hover {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .order-item.featured {
        border-color: #f59e0b;
        background: #fef3c7;
    }
    
    .order-number {
        background: #3b82f6;
        color: white;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
    }
    
    .order-item.featured .order-number {
        background: #f59e0b;
    }
    
    .drag-handle {
        color: #9ca3af;
        font-size: 18px;
        cursor: move;
    }
    
    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    /* Form styles */
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .checkbox-group {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-top: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding: 15px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f9fafb;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    
    .checkbox-item:hover {
        background: #e5e7eb;
    }
    
    @media (max-width: 768px) {
        .brand-grid {
            grid-template-columns: 1fr;
        }
        
        .brand-actions {
            flex-direction: column;
        }
        
        .page-header {
            flex-direction: column;
            gap: 16px;
            align-items: stretch;
        }
    }
    
    .category-slider-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 24px;
        position: relative;
        justify-content: center;
        max-width: 1060px;
        margin-left: auto;
        margin-right: auto;
    }
    .category-slider {
        display: flex;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
        gap: 8px;
        flex: 1 1 auto;
        scroll-behavior: smooth;
        padding-bottom: 2px;
        position: relative;
        max-width: 90vw;
    }
    .category-slider::-webkit-scrollbar {
        display: none;
    }
    .category-tab {
        min-width: fit-content;
        padding: 10px 20px;
        border: none;
        background: #f3f4f6;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #6b7280;
        transition: all 0.2s;
        outline: none;
        white-space: nowrap;
    }
    .category-tab.active, .category-tab:focus {
        background: #3b82f6;
        color: white;
        box-shadow: 0 2px 8px rgba(59,130,246,0.08);
    }
    .slider-arrow {
        background: #f3f4f6;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: #6b7280;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
        z-index: 2;
    }
    .slider-arrow:hover {
        background: #e5e7eb;
        color: #3b82f6;
    }
    .slider-arrow:disabled {
        opacity: 0.3;
        cursor: default;
    }
    .category-slider-fade {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 36px;
        pointer-events: none;
        z-index: 1;
    }
    .category-slider-fade.left {
        left: 0;
        background: linear-gradient(to right, #fff 60%, rgba(255,255,255,0));
    }
    .category-slider-fade.right {
        right: 0;
        background: linear-gradient(to left, #fff 60%, rgba(255,255,255,0));
    }
    @media (max-width: 800px) {
        .category-slider-wrapper {
            max-width: 98vw;
        }
        .category-slider {
            max-width: 98vw;
        }
    }
    
    /* Quick Actions Bar */
    .quick-actions-bar {
        background: white;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .left-actions, .right-actions {
        display: flex;
        gap: 12px;
        align-items: center;
    }
</style>

<header class="main-header">
    <div class="time-info">
        <div id="time"></div>
        <div id="date"></div>
    </div>
    <div class="user-info">
        <span>Hello, <strong>Admin</strong></span>
        <div class="profile-pic"></div>
    </div>
</header>

<div class="content-wrapper">
    <div class="main-column">
        <div style="margin-bottom: 30px;">
            <h1>Brand Management</h1>
            <p style="color: var(--ps-text-light); margin-top: 5px; font-size: 14px;">
                Manage your brands and their category assignments. Use drag & drop to reorder brands within categories.
            </p>
        </div>
        
        <!-- Quick Actions Bar -->
        <div class="quick-actions-bar" style="margin-bottom: 30px;">
            <div class="left-actions">
                <button onclick="openAddModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New Brand
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <button class="tab-button active" onclick="switchTab('brands')" id="tab-brands">
                <i class="fas fa-list"></i> Brands
            </button>
            <button class="tab-button" onclick="switchTab('order')" id="tab-order">
                <i class="fas fa-arrows-alt"></i> Manage Order
            </button>
        </div>

        <!-- Tab Content -->
        <div id="tab-content-brands" class="tab-content active">
            <!-- Statistics Dashboard -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_brands; ?></div>
                    <div class="stat-label">Total Brands</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $featured_brands; ?></div>
                    <div class="stat-label">Featured Brands</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $brands_with_raffles; ?></div>
                    <div class="stat-label">Brands with Raffles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($categories); ?></div>
                    <div class="stat-label">Categories</div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div style="background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Search Brands</label>
                        <input type="text" id="brandSearch" placeholder="Search by name or description..." style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                    </div>
                    <div style="min-width: 150px;">
                        <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">Filter by</label>
                        <select id="brandFilter" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                            <option value="all">All Brands</option>
                            <option value="featured">Featured Only</option>
                            <option value="with_categories">With Categories</option>
                            <option value="no_categories">No Categories</option>
                            <option value="with_raffles">With Raffles</option>
                            <option value="no_raffles">No Raffles</option>
                        </select>
                    </div>
                    <div style="display: flex; gap: 10px; align-items: end;">
                        <button onclick="clearFilters()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Brands Grid -->
            <div class="brand-grid" id="brand-grid">
                <?php foreach ($brands as $brand): ?>
                <div class="brand-card <?php echo $brand['is_featured'] ? 'featured' : ''; ?>" 
                     data-brand-id="<?php echo $brand['id']; ?>" 
                     data-name="<?php echo htmlspecialchars(strtolower($brand['name'])); ?>"
                     data-description="<?php echo htmlspecialchars(strtolower($brand['description'] ?? '')); ?>"
                     data-featured="<?php echo $brand['is_featured'] ? '1' : '0'; ?>"
                     data-categories="<?php echo $brand['category_names'] ? '1' : '0'; ?>"
                     data-raffles="<?php echo $brand['raffle_count']; ?>">
                    
                    <div class="brand-header">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <?php if (isset($brand['image_url']) && $brand['image_url']): ?>
                                <img src="/raffle-demo/<?php echo htmlspecialchars($brand['image_url']); ?>" alt="<?php echo htmlspecialchars($brand['name']); ?> logo" style="width: 40px; height: 40px; object-fit: contain; border-radius: 6px; border: 1px solid #e5e7eb;">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; background: #f3f4f6; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid #e5e7eb;">
                                    <i class="fas fa-image" style="color: #9ca3af; font-size: 16px;"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="brand-name"><?php echo htmlspecialchars($brand['name']); ?></h3>
                        </div>
                        <?php if ($brand['is_featured']): ?>
                            <span class="featured-badge">⭐ Featured</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="brand-categories">
                        <?php if ($brand['category_names']): ?>
                            <strong>Categories:</strong> <?php echo htmlspecialchars($brand['category_names']); ?>
                        <?php else: ?>
                            <em style="color: #ef4444;">No categories assigned</em>
                        <?php endif; ?>
                    </div>
                    
                    <div class="brand-stats">
                        <span><i class="fas fa-ticket-alt"></i> <?php echo $brand['raffle_count']; ?> raffles</span>
                        <?php if ($brand['website_url']): ?>
                            <span><i class="fas fa-globe"></i> Website</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="brand-actions">
                        <button onclick="editBrand(<?php echo htmlspecialchars(json_encode($brand)); ?>)" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <?php if ($brand['raffle_count'] == 0): ?>
                            <button onclick="deleteBrand(<?php echo $brand['id']; ?>, '<?php echo htmlspecialchars($brand['name']); ?>')" class="btn btn-danger" style="flex: 1;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        <?php else: ?>
                            <button onclick="viewRaffles(<?php echo $brand['id']; ?>)" class="btn btn-secondary" style="flex: 1;">
                                <i class="fas fa-eye"></i> View Raffles
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Order Management Tab -->
        <div id="tab-content-order" class="tab-content">
            <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2><i class="fas fa-arrows-alt"></i> Manage Brand Order by Category</h2>
                </div>
                
                <!-- Category Slider -->
                <div class="category-slider-wrapper">
                    <span class="category-slider-fade left" id="cat-slider-fade-left"></span>
                    <button class="slider-arrow left" id="cat-slider-left" aria-label="Scroll left"><i class="fas fa-chevron-left"></i></button>
                    <div class="category-slider" id="categorySlider">
                        <?php foreach ($categories as $category): ?>
                        <button class="category-tab" data-category-id="<?php echo $category['id']; ?>" onclick="switchCategory(<?php echo $category['id']; ?>)">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <button class="slider-arrow right" id="cat-slider-right" aria-label="Scroll right"><i class="fas fa-chevron-right"></i></button>
                    <span class="category-slider-fade right" id="cat-slider-fade-right"></span>
                </div>
                
                <div id="orderList" class="order-list">
                    <div style="text-align: center; color: #6b7280; padding: 40px;">
                        <i class="fas fa-arrow-up" style="font-size: 24px; margin-bottom: 12px;"></i>
                        <p>Select a category above to manage brand order</p>
                    </div>
                </div>
                
                <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 16px; margin-top: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; color: #0c4a6e; font-weight: 600; margin-bottom: 8px;">
                        <i class="fas fa-info-circle"></i>
                        How to reorder brands
                    </div>
                    <p style="margin: 0; color: #0c4a6e; font-size: 14px;">
                        <strong>Featured brands always appear first.</strong> Drag and drop brands to reorder them within each category. 
                        Changes apply only to the selected category and won't affect other categories.
                    </p>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                    <button onclick="saveOrder()" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Brand Modal -->
<div id="brandModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Brand</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        
        <form id="brandForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add_brand">
            <input type="hidden" name="brand_id" id="brandId">
            <input type="hidden" name="current_image_url" id="currentImageUrl">
            
            <div class="form-group">
                <label for="brandName">Brand Name *</label>
                <input type="text" name="name" id="brandName" required>
            </div>
            
            <div class="form-group">
                <label for="brandDescription">Description</label>
                <textarea name="description" id="brandDescription" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="brandWebsite">Website URL</label>
                <input type="url" name="website_url" id="brandWebsite">
            </div>
            
            <div class="form-group">
                <label for="brandImage">Brand Logo</label>
                <div style="margin-bottom: 10px;">
                    <input type="file" name="brand_image" id="brandImage" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
                    <small style="color: #6b7280;">Recommended: 200x200px, max 2MB. Supported formats: JPG, PNG, GIF, WebP</small>
                </div>
                <div id="currentImagePreview" style="display: none; margin-top: 10px;">
                    <label style="font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">Current Image:</label>
                    <img id="imagePreview" src="" alt="Current brand logo" style="max-width: 100px; max-height: 100px; border: 1px solid #d1d5db; border-radius: 4px;">
                    <button type="button" onclick="removeCurrentImage()" style="margin-left: 10px; padding: 4px 8px; background: #ef4444; color: white; border: none; border-radius: 4px; font-size: 12px; cursor: pointer;">Remove</button>
                </div>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_featured" id="brandFeatured">
                    Featured Brand (appears first in all categories)
                </label>
            </div>
            
            <div class="form-group">
                <label>Categories * <small style="color: #6b7280; font-weight: normal;">(Select categories this brand belongs to)</small></label>
                <div style="margin-bottom: 10px;">
                    <button type="button" onclick="selectAllCategories()" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; margin-right: 8px;">
                        <i class="fas fa-check-square"></i> Select All
                    </button>
                    <button type="button" onclick="clearAllCategories()" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                        <i class="fas fa-square"></i> Clear All
                    </button>
                </div>
                <div class="checkbox-group" id="categoryCheckboxes">
                    <?php foreach ($categories as $category): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" name="category_ids[]" value="<?php echo $category['id']; ?>" id="cat_<?php echo $category['id']; ?>">
                        <label for="cat_<?php echo $category['id']; ?>" style="cursor: pointer; font-size: 14px;">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="categoryValidation" style="display: none; color: #ef4444; font-size: 12px; margin-top: 8px;">
                    <i class="fas fa-exclamation-circle"></i> Please select at least one category
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Brand</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Brand management functions
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Brand';
        document.getElementById('formAction').value = 'add_brand';
        document.getElementById('brandForm').reset();
        document.getElementById('currentImageUrl').value = '';
        document.getElementById('currentImagePreview').style.display = 'none';
        document.getElementById('brandModal').style.display = 'block';
    }
    
    function removeCurrentImage() {
        document.getElementById('currentImageUrl').value = '';
        document.getElementById('currentImagePreview').style.display = 'none';
        document.getElementById('imagePreview').src = '';
    }
    
    function editBrand(brand) {
        try {
            document.getElementById('modalTitle').textContent = 'Edit Brand: ' + brand.name;
            document.getElementById('formAction').value = 'edit_brand';
            document.getElementById('brandId').value = brand.id;
            document.getElementById('brandName').value = brand.name;
            document.getElementById('brandDescription').value = brand.description || '';
            document.getElementById('brandWebsite').value = brand.website_url || '';
            document.getElementById('brandFeatured').checked = brand.is_featured == 1;
            
            // Handle image preview
            const currentImageUrl = brand.image_url || '';
            document.getElementById('currentImageUrl').value = currentImageUrl;
            
            if (currentImageUrl) {
                document.getElementById('imagePreview').src = '/raffle-demo/' + currentImageUrl;
                document.getElementById('currentImagePreview').style.display = 'block';
            } else {
                document.getElementById('currentImagePreview').style.display = 'none';
            }
            
            // Clear all category checkboxes first
            document.querySelectorAll('input[name="category_ids[]"]').forEach(cb => {
                cb.checked = false;
            });
            
            // Check relevant categories
            if (brand.category_ids && brand.category_ids.trim()) {
                const categoryIds = brand.category_ids.split(',').map(id => id.trim()).filter(id => id);
                categoryIds.forEach(id => {
                    const checkbox = document.getElementById('cat_' + id);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            document.getElementById('brandModal').style.display = 'block';
        } catch (error) {
            console.error('Error opening edit modal:', error);
            alert('Error opening edit form. Please try again.');
        }
    }
    
    function deleteBrand(id, name) {
        if (confirm(`Are you sure you want to delete the brand "${name}"?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_brand">
                <input type="hidden" name="brand_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    function closeModal() {
        document.getElementById('brandModal').style.display = 'none';
    }
    
    function viewRaffles(brandId) {
        window.open(`raffles.php?brand_filter=${brandId}`, '_blank');
    }
    
    // Tab management functions
    function switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        document.getElementById('tab-' + tabName).classList.add('active');
        
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById('tab-content-' + tabName).classList.add('active');
        
        // If switching to order tab, auto-select first category
        if (tabName === 'order') {
            const firstCategoryTab = document.querySelector('.category-tab');
            if (firstCategoryTab) {
                firstCategoryTab.click();
            }
        }
    }
    
    // Order management functions
    let currentCategoryId = null;
    let brandOrderData = {};
    
    function switchCategory(categoryId) {
        currentCategoryId = categoryId;
        
        // Update active tab
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        event.target.classList.add('active');
        
        // Load brands for this category
        loadBrandsForCategory(categoryId);
    }
    
    function loadBrandsForCategory(categoryId) {
        fetch(`../api/get_brands_by_category.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderOrderList(data.brands);
                } else {
                    document.getElementById('orderList').innerHTML = '<div style="text-align: center; color: #6b7280; padding: 40px;">No brands found in this category</div>';
                }
            })
            .catch(error => {
                console.error('Error loading brands:', error);
                document.getElementById('orderList').innerHTML = '<div style="text-align: center; color: #ef4444; padding: 40px;">Error loading brands</div>';
            });
    }
    
    function renderOrderList(brands) {
        const orderList = document.getElementById('orderList');
        
        if (brands.length === 0) {
            orderList.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 40px;">No brands in this category</div>';
            return;
        }
        
        // Sort brands: featured first, then by category_sort_order
        brands.sort((a, b) => {
            if (a.is_featured !== b.is_featured) {
                return b.is_featured - a.is_featured;
            }
            return (a.category_sort_order || 999) - (b.category_sort_order || 999);
        });
        
        let html = '';
        brands.forEach((brand, index) => {
            const position = index + 1;
            const featuredClass = brand.is_featured ? 'featured' : '';
            const featuredIcon = brand.is_featured ? '⭐ ' : '';
            
            html += `
                <div class="order-item ${featuredClass}" data-brand-id="${brand.id}" draggable="true">
                    <div class="order-number">${position}</div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600;">${featuredIcon}${brand.name}</div>
                        <div style="font-size: 12px; color: #6b7280;">Sort Order: ${brand.category_sort_order || 'Auto'}</div>
                    </div>
                    <div class="drag-handle">⋮⋮</div>
                </div>
            `;
        });
        
        orderList.innerHTML = html;
        
        // Enable drag and drop
        enableDragAndDrop();
    }
    
    function enableDragAndDrop() {
        const orderItems = document.querySelectorAll('.order-item');
        
        orderItems.forEach(item => {
            item.addEventListener('dragstart', function(e) {
                e.dataTransfer.effectAllowed = 'move';
                this.style.opacity = '0.5';
            });
            
            item.addEventListener('dragend', function(e) {
                this.style.opacity = '1';
            });
            
            item.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });
            
            item.addEventListener('drop', function(e) {
                e.preventDefault();
                const draggedItem = document.querySelector('.order-item[style*="opacity: 0.5"]');
                if (draggedItem && draggedItem !== this) {
                    const container = this.parentNode;
                    const items = Array.from(container.children);
                    const draggedIndex = items.indexOf(draggedItem);
                    const dropIndex = items.indexOf(this);
                    
                    if (draggedIndex < dropIndex) {
                        container.insertBefore(draggedItem, this.nextSibling);
                    } else {
                        container.insertBefore(draggedItem, this);
                    }
                    
                    // Update position numbers
                    updatePositionNumbers();
                }
            });
        });
    }
    
    function updatePositionNumbers() {
        const orderItems = document.querySelectorAll('.order-item');
        orderItems.forEach((item, index) => {
            const numberElement = item.querySelector('.order-number');
            numberElement.textContent = index + 1;
        });
    }
    
    function saveOrder() {
        if (!currentCategoryId) {
            alert('Please select a category first.');
            return;
        }
        
        const orderItems = document.querySelectorAll('.order-item');
        const newOrder = Array.from(orderItems).map((item, index) => ({
            id: item.dataset.brandId,
            sort_order: index + 1
        }));
        
        // Send AJAX request to update order
        fetch('brands.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=update_brand_order&category_id=' + currentCategoryId + '&order=' + encodeURIComponent(JSON.stringify(newOrder))
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('success')) {
                alert('Brand order updated successfully!');
                location.reload(); // Refresh to show new order
            } else {
                alert('Error updating brand order. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating brand order. Please try again.');
        });
    }
    
    // Search and filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('brandSearch');
        const filterSelect = document.getElementById('brandFilter');
        const brandCards = document.querySelectorAll('.brand-card');

        function filterAndSortBrands() {
            const searchTerm = searchInput.value.toLowerCase();
            const filterValue = filterSelect.value;

            brandCards.forEach(card => {
                const name = card.dataset.name;
                const description = card.dataset.description;
                const featured = card.dataset.featured;
                const categories = card.dataset.categories;
                const raffles = parseInt(card.dataset.raffles);

                let showCard = true;

                // Search filter
                if (searchTerm && !name.includes(searchTerm) && !description.includes(searchTerm)) {
                    showCard = false;
                }

                // Category filter
                if (filterValue === 'featured' && featured !== '1') showCard = false;
                if (filterValue === 'with_categories' && categories !== '1') showCard = false;
                if (filterValue === 'no_categories' && categories === '1') showCard = false;
                if (filterValue === 'with_raffles' && raffles === 0) showCard = false;
                if (filterValue === 'no_raffles' && raffles > 0) showCard = false;

                card.style.display = showCard ? 'block' : 'none';
            });
        }

        searchInput.addEventListener('input', filterAndSortBrands);
        filterSelect.addEventListener('change', filterAndSortBrands);
    });

    function clearFilters() {
        document.getElementById('brandSearch').value = '';
        document.getElementById('brandFilter').value = 'all';
        
        // Trigger filter
        const event = new Event('input');
        document.getElementById('brandSearch').dispatchEvent(event);
    }
    
    // Category selection helpers
    function selectAllCategories() {
        document.querySelectorAll('input[name="category_ids[]"]').forEach(checkbox => {
            checkbox.checked = true;
        });
        validateCategories();
    }

    function clearAllCategories() {
        document.querySelectorAll('input[name="category_ids[]"]').forEach(checkbox => {
            checkbox.checked = false;
        });
        validateCategories();
    }

    function validateCategories() {
        const checkboxes = document.querySelectorAll('input[name="category_ids[]"]');
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        const validationDiv = document.getElementById('categoryValidation');
        
        if (checkedCount === 0) {
            validationDiv.style.display = 'block';
            return false;
        } else {
            validationDiv.style.display = 'none';
            return true;
        }
    }

    // Form validation
    document.addEventListener('DOMContentLoaded', function() {
        const brandForm = document.getElementById('brandForm');
        const categoryCheckboxes = document.querySelectorAll('input[name="category_ids[]"]');
        
        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', validateCategories);
        });
        
        brandForm.addEventListener('submit', function(e) {
            if (!validateCategories()) {
                e.preventDefault();
                alert('Please select at least one category.');
            }
        });
    });

    // Close modals when clicking outside
    window.onclick = function(event) {
        const brandModal = document.getElementById('brandModal');
        
        if (event.target === brandModal) {
            closeModal();
        }
    }
    
    // Close modals with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const brandModal = document.getElementById('brandModal');
            
            if (brandModal.style.display === 'block') {
                closeModal();
            }
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Category slider logic
        const slider = document.getElementById('categorySlider');
        const leftBtn = document.getElementById('cat-slider-left');
        const rightBtn = document.getElementById('cat-slider-right');
        const fadeLeft = document.getElementById('cat-slider-fade-left');
        const fadeRight = document.getElementById('cat-slider-fade-right');
        function updateSliderArrowsAndFade() {
            leftBtn.disabled = slider.scrollLeft <= 0;
            rightBtn.disabled = slider.scrollLeft + slider.offsetWidth >= slider.scrollWidth - 1;
            fadeLeft.style.opacity = slider.scrollLeft > 5 ? '1' : '0';
            fadeRight.style.opacity = (slider.scrollLeft + slider.offsetWidth < slider.scrollWidth - 5) ? '1' : '0';
        }
        leftBtn.addEventListener('click', function() {
            slider.scrollBy({ left: -200, behavior: 'smooth' });
        });
        rightBtn.addEventListener('click', function() {
            slider.scrollBy({ left: 200, behavior: 'smooth' });
        });
        slider.addEventListener('scroll', updateSliderArrowsAndFade);
        updateSliderArrowsAndFade();

        // Highlight active category and auto-center
        window.switchCategory = function(categoryId) {
            currentCategoryId = categoryId;
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('active');
                if (parseInt(tab.dataset.categoryId) === categoryId) {
                    tab.classList.add('active');
                    // Auto-center the tab
                    const tabRect = tab.getBoundingClientRect();
                    const sliderRect = slider.getBoundingClientRect();
                    const offset = tabRect.left - sliderRect.left - (sliderRect.width/2) + (tabRect.width/2);
                    slider.scrollBy({ left: offset, behavior: 'smooth' });
                }
            });
            loadBrandsForCategory(categoryId);
        };
    });
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?> 