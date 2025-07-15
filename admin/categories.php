<?php 
include __DIR__ . '/../inc/header.php';

// First, ensure all required columns exist before any form processing
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN show_brands BOOLEAN DEFAULT TRUE");
} catch (Exception $e) {
    // Column probably already exists, ignore the error
}

try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN is_active BOOLEAN DEFAULT TRUE");
} catch (Exception $e) {
    // Column probably already exists, ignore the error
}

try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN icon VARCHAR(50) DEFAULT 'fa-tag'");
} catch (Exception $e) {
    // Column probably already exists, ignore the error
}

try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN description TEXT");
} catch (Exception $e) {
    // Column probably already exists, ignore the error
}

try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN sort_order INT DEFAULT 0");
} catch (Exception $e) {
    // Column probably already exists, ignore the error
}

// Update any NULL values
$pdo->exec("UPDATE categories SET is_active = 1 WHERE is_active IS NULL");
$pdo->exec("UPDATE categories SET show_brands = 1 WHERE show_brands IS NULL");
$pdo->exec("UPDATE categories SET icon = 'fa-tag' WHERE icon IS NULL OR icon = ''");
$pdo->exec("UPDATE categories SET description = '' WHERE description IS NULL");
$pdo->exec("UPDATE categories SET sort_order = 0 WHERE sort_order IS NULL");

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, icon, description, is_active, show_brands) VALUES (?, ?, ?, 1, 1)");
        $stmt->execute([$_POST['name'], $_POST['icon'] ?? 'fa-tag', $_POST['description'] ?? '']);
        $message = 'Category added successfully!';
    }
    
    if (isset($_POST['edit_category'])) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, icon = ?, description = ? WHERE id = ?");
        $stmt->execute([$_POST['name'], $_POST['icon'], $_POST['description'], $_POST['category_id']]);
        $message = 'Category updated successfully!';
    }
    
    if (isset($_POST['delete_category'])) {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$_POST['category_id']]);
        $message = 'Category deleted successfully!';
    }
    
    if (isset($_POST['bulk_action'])) {
        $action = $_POST['bulk_action'];
        $selected_categories = $_POST['selected_categories'] ?? [];
        
        // Handle JSON string from JavaScript
        if (is_string($selected_categories)) {
            $selected_categories = json_decode($selected_categories, true) ?? [];
        }
        
        if (!empty($selected_categories)) {
            $placeholders = str_repeat('?,', count($selected_categories) - 1) . '?';
            
            switch ($action) {
                case 'activate':
                    $stmt = $pdo->prepare("UPDATE categories SET is_active = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selected_categories);
                    $message = 'Categories activated successfully!';
                    break;
                case 'deactivate':
                    $stmt = $pdo->prepare("UPDATE categories SET is_active = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selected_categories);
                    $message = 'Categories deactivated successfully!';
                    break;
                case 'enable_brands':
                    $stmt = $pdo->prepare("UPDATE categories SET show_brands = 1 WHERE id IN ($placeholders)");
                    $stmt->execute($selected_categories);
                    $message = 'Brand layout enabled for selected categories!';
                    break;
                case 'disable_brands':
                    $stmt = $pdo->prepare("UPDATE categories SET show_brands = 0 WHERE id IN ($placeholders)");
                    $stmt->execute($selected_categories);
                    $message = 'Brand layout disabled for selected categories!';
                    break;
                case 'delete':
                    // Only delete categories with no raffles
                    $stmt = $pdo->prepare("DELETE FROM categories WHERE id IN ($placeholders) AND id NOT IN (SELECT DISTINCT category_id FROM raffles WHERE category_id IS NOT NULL)");
                    $stmt->execute($selected_categories);
                    $message = 'Categories deleted successfully (categories with raffles were skipped)!';
                    break;
            }
        }
    }
    
    if (isset($_POST['save_all_changes'])) {
        $changes = json_decode($_POST['changes'], true);
        $updated_count = 0;
        
        try {
            $pdo->beginTransaction();
            
            foreach ($changes as $category_id => $settings) {
                // Fetch current values from database to preserve unchanged settings
                $stmt = $pdo->prepare("SELECT is_active, show_brands, sort_order FROM categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $orig = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Only update settings that actually changed, preserve existing values for others
                $is_active = array_key_exists('is_active', $settings) ? $settings['is_active'] : $orig['is_active'];
                $show_brands = array_key_exists('show_brands', $settings) ? $settings['show_brands'] : $orig['show_brands'];
                $sort_order = array_key_exists('sort_order', $settings) ? $settings['sort_order'] : $orig['sort_order'];
                
                $stmt = $pdo->prepare("UPDATE categories SET is_active = ?, show_brands = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$is_active, $show_brands, $sort_order, $category_id]);
                $updated_count++;
            }
            
            $pdo->commit();
            $message = "Successfully updated {$updated_count} categories!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error saving changes: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['update_sort_order'])) {
        $sort_orders = json_decode($_POST['sort_orders'], true);
        $updated_count = 0;
        
        try {
            $pdo->beginTransaction();
            
            foreach ($sort_orders as $category_id => $sort_order) {
                $stmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
                $stmt->execute([$sort_order, $category_id]);
                $updated_count++;
            }
            
            $pdo->commit();
            $message = "Sort order updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error updating sort order: " . $e->getMessage();
        }
    }
}



// Handle search and filtering
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$brand_filter = $_GET['brand'] ?? 'all';

// Build query with filters
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter !== 'all') {
    $where_conditions[] = "COALESCE(c.is_active, 1) = ?";
    $params[] = ($status_filter === 'active') ? 1 : 0;
}

if ($brand_filter !== 'all') {
    $where_conditions[] = "COALESCE(c.show_brands, 1) = ?";
    $params[] = ($brand_filter === 'enabled') ? 1 : 0;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get categories with raffle counts and active status
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.icon, c.description,
           COALESCE(c.is_active, 1) as is_active,
           COALESCE(c.show_brands, 1) as show_brands,
           COALESCE(c.sort_order, 0) as sort_order,
           COUNT(r.id) as raffle_count 
    FROM categories c 
    LEFT JOIN raffles r ON c.id = r.category_id 
    $where_clause
    GROUP BY c.id, c.name, c.icon, c.description, c.is_active, c.show_brands, c.sort_order
    ORDER BY COALESCE(c.is_active, 1) DESC, COALESCE(c.sort_order, 0) ASC, c.name ASC
");
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_categories = count($categories);
$active_categories = count(array_filter($categories, fn($c) => $c['is_active']));
$inactive_categories = $total_categories - $active_categories;
$brand_enabled_categories = count(array_filter($categories, fn($c) => $c['show_brands']));

// Common icons for categories
$common_icons = [
    'fa-mobile-alt' => 'Electronics',
    'fa-gamepad' => 'Gaming',
    'fa-tshirt' => 'Fashion',
    'fa-home' => 'Home & Living',
    'fa-futbol' => 'Sports',
    'fa-spa' => 'Beauty',
    'fa-book' => 'Books',
    'fa-utensils' => 'Food & Beverages',
    'fa-car' => 'Automotive',
    'fa-heart' => 'Health & Wellness',
    'fa-music' => 'Music',
    'fa-camera' => 'Photography',
    'fa-gift' => 'Gifts',
    'fa-paw' => 'Pet Supplies',
    'fa-tag' => 'General'
];
?>

<style>
.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

input:checked + .toggle-slider {
    background-color: #4CAF50;
}

input:checked + .toggle-slider.brands {
    background-color: #2196F3;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.category-row.inactive {
    opacity: 0.7;
    background-color: #f9f9f9;
}

.category-row.changed {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107;
}

.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background-color: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background-color: #fee2e2;
    color: #991b1b;
}

.brands-enabled {
    background-color: #dbeafe;
    color: #1e40af;
}

.brands-disabled {
    background-color: #f3f4f6;
    color: #6b7280;
}

.toggle-column {
    text-align: center;
    width: 120px;
    padding: 15px 10px;
}

.info-section {
    background: #e3f2fd;
    border: 1px solid #2196f3;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
    color: #1565c0;
    font-size: 14px;
}

.info-section i {
    color: #2196f3;
    margin-right: 8px;
}

.feature-info {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 24px;
    color: #856404;
    font-size: 14px;
}

.feature-info i {
    color: #ffc107;
    margin-right: 8px;
}

.toggle-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    align-items: center;
}

.toggle-label {
    font-size: 11px;
    color: #6b7280;
    text-align: center;
    line-height: 1.2;
    font-weight: 500;
}

/* Enhanced table styling */
.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 30px;
}

.table-header {
    background: #f8f9fa;
    padding: 20px 24px;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-title {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.table-subtitle {
    color: #6c757d;
    font-size: 14px;
    margin-top: 4px;
}

.save-controls {
    display: flex;
    gap: 12px;
    align-items: center;
}

.save-indicator {
    display: none;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #ffc107;
    color: #856404;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    animation: pulse 2s infinite;
}

.save-indicator.show {
    display: flex;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 14px;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
    transform: translateY(-1px);
}

.btn-success:disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
    transform: none;
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}

.table th {
    background-color: #f8f9fa;
    padding: 16px 12px;
    text-align: left;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    color: #495057;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table td {
    padding: 16px 12px;
    border-bottom: 1px solid #e9ecef;
    vertical-align: middle;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.category-name {
    font-weight: 600;
    color: #495057;
    font-size: 15px;
}

.raffle-count-badge {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

/* Floating save bar */
.floating-save-bar {
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border: 2px solid #ffc107;
    border-radius: 12px;
    padding: 16px 24px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    display: none;
    align-items: center;
    gap: 16px;
    z-index: 1000;
    min-width: 350px;
}

.floating-save-bar.show {
    display: flex;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateX(-50%) translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateX(-50%) translateY(0);
        opacity: 1;
    }
}

.save-bar-text {
    flex: 1;
    font-weight: 500;
    color: #374151;
}

.changes-count {
    background: #ffc107;
    color: #856404;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

/* Add category form styling */
.add-category-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
}

.add-category-title {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 16px;
}

.form-row {
    display: flex;
    gap: 16px;
    align-items: center;
}

.form-group {
    flex: 1;
}

.form-label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    font-size: 14px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

@media (max-width: 768px) {
    .table-container {
        margin: 0 -10px;
        border-radius: 0;
    }
    
    .table {
        font-size: 12px;
    }
    
    .table th,
    .table td {
        padding: 12px 8px;
    }
    
    .toggle-column {
        width: 100px;
        padding: 12px 5px;
    }
    
    .form-row {
        flex-direction: column;
        gap: 12px;
    }
    
    .floating-save-bar {
        left: 10px;
        right: 10px;
        transform: none;
        min-width: auto;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-form {
        flex-direction: column;
        gap: 12px;
    }
    
    .quick-actions-bar {
        flex-direction: column;
        gap: 12px;
    }
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.stat-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    color: #6c757d;
    font-size: 20px;
}

.stat-icon.active {
    background: #d1fae5;
    color: #065f46;
}

.stat-icon.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.stat-icon.brands {
    background: #dbeafe;
    color: #1e40af;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    margin-top: 4px;
}

/* Search and Filter Bar */
.filter-bar {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.filter-form {
    display: flex;
    gap: 16px;
    align-items: end;
}

.search-group {
    flex: 1;
}

.search-input-wrapper {
    position: relative;
}

.search-input-wrapper i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6b7280;
    font-size: 14px;
}

.search-input {
    width: 100%;
    padding: 12px 16px 12px 40px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-select {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    background: white;
    transition: border-color 0.2s ease;
    min-width: 140px;
}

.filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
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

.bulk-actions {
    display: flex;
    gap: 12px;
    align-items: center;
    padding: 8px 16px;
    background: #fef3c7;
    border-radius: 8px;
    border: 1px solid #f59e0b;
}

.bulk-info {
    font-size: 14px;
    color: #92400e;
    font-weight: 600;
}

.bulk-select {
    padding: 6px 12px;
    border: 1px solid #d97706;
    border-radius: 6px;
    font-size: 12px;
    background: white;
    min-width: 120px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* Enhanced Table Layout Styles - Matching Raffles Table */
.category-table {
    background: #ffffff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    overflow-x: auto;
}

.category-table table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: var(--admin-bg-primary);
    table-layout: fixed;
}

.category-table th,
.category-table td {
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    padding: 10px;
}

.category-table th {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    letter-spacing: 0.05em;
    position: sticky;
    top: 0;
    z-index: 10;
}

.category-table tbody tr {
    transition: all 0.2s ease;
    cursor: pointer;
}

.category-table tbody tr:hover {
    background-color: #f9fafb;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.category-table tbody tr:nth-child(even) {
    background: rgba(249, 250, 251, 0.5);
}

.category-table tbody tr.inactive {
    opacity: 0.7;
    background-color: #f9f9f9;
}

.category-table tbody tr.changed {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107;
}

/* Checkbox Styles */
.checkbox {
    width: 16px;
    height: 16px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    cursor: pointer;
    accent-color: #3b82f6;
}

.checkbox-header {
    text-align: center;
    cursor: pointer;
}

.checkbox-column {
    text-align: center;
}

/* Responsive table */
@media (max-width: 1400px) {
    .category-table {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
}

/* Card Styles */
.card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.card-header {
    padding: 24px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
}

.card-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.card-subtitle {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.text-xs {
    font-size: 12px;
}

.text-muted {
    color: #6b7280;
}

/* Smart Row Edit Button Styles */
.smart-row-edit-btn {
    transition: all 0.2s ease;
}

.smart-row-edit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.smart-row-edit-btn:active {
    transform: translateY(0);
}

/* Status Badge Enhancements */
.status-badge.status-active {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    border: 1px solid #10b981;
}

.status-badge.status-inactive {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border: 1px solid #ef4444;
}

.status-badge.brands-enabled {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 1px solid #3b82f6;
}

.status-badge.brands-disabled {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #6b7280;
    border: 1px solid #d1d5db;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
}

.modal.show {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 24px 24px 0 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 0 24px 24px 24px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
}

.form-input, .form-select, .form-textarea {
    padding: 12px 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s ease;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-textarea {
    resize: vertical;
    min-height: 80px;
}

.icon-selector {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 8px;
    max-height: 200px;
    overflow-y: auto;
    padding: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
}

.icon-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 12px 8px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.icon-option:hover {
    background: #e5e7eb;
}

.icon-option.selected {
    background: #dbeafe;
    border-color: #3b82f6;
}

.icon-option i {
    font-size: 20px;
    color: #6b7280;
}

.icon-option.selected i {
    color: #3b82f6;
}

.icon-option span {
    font-size: 10px;
    color: #6b7280;
    text-align: center;
}

.preview-card {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-top: 20px;
}

.preview-title {
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

.preview-category {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.preview-category .category-icon {
    width: 48px;
    height: 48px;
    font-size: 20px;
}

.preview-category .category-name {
    font-size: 18px;
}

.help-indicator {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    cursor: help;
    transition: all 0.2s ease;
}

.help-indicator:hover {
    background: #e5e7eb;
    color: #374151;
    border-color: #d1d5db;
}

/* Sort Order Input Styles */
.sort-order-input {
    transition: all 0.2s ease;
}

.sort-order-input:focus {
    outline: none;
    border-color: #3b82f6 !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.sort-order-input.changed {
    background-color: #fff3cd !important;
    border-color: #ffc107 !important;
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
            <h1>Manage Categories</h1>
            <p style="color: var(--ps-text-light); margin-top: 5px; font-size: 14px;">
                Create categories and control which ones display brands on the frontend
            </p>
        </div>
        
        <!-- Workflow Status Indicator -->
        <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #0ea5e9; border-radius: var(--ps-radius-lg); padding: 20px; margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                <div style="background: #0ea5e9; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div>
                    <h3 style="color: #0c4a6e; font-size: 16px; font-weight: 600; margin: 0;">Category Setup Guide</h3>
                    <p style="color: #0c4a6e; font-size: 14px; margin: 5px 0 0 0;">Configure how categories work with brands</p>
                </div>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #bae6fd;">
                    <div style="font-weight: 600; color: #0c4a6e; font-size: 14px;">Category Status</div>
                    <div style="font-size: 12px; color: #0369a1; margin-top: 2px;">Active categories are visible to users</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #bae6fd;">
                    <div style="font-weight: 600; color: #0c4a6e; font-size: 14px;">Brand Display</div>
                    <div style="font-size: 12px; color: #0369a1; margin-top: 2px;">Toggle to show/hide brands in this category on frontend</div>
                </div>
                <div style="background: white; padding: 12px; border-radius: var(--ps-radius); border: 1px solid #bae6fd;">
                    <div style="font-weight: 600; color: #0c4a6e; font-size: 14px;">Next Step</div>
                    <div style="font-size: 12px; color: #0369a1; margin-top: 2px;">After setup, go to Brands to assign categories</div>
                </div>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $total_categories; ?></div>
                    <div class="stat-label">Total Categories</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $active_categories; ?></div>
                    <div class="stat-label">Active Categories</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon inactive">
                    <i class="fas fa-eye-slash"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $inactive_categories; ?></div>
                    <div class="stat-label">Inactive Categories</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon brands">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $brand_enabled_categories; ?></div>
                    <div class="stat-label">Brand Layout Enabled</div>
                </div>
            </div>
        </div>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Search and Filter Bar -->
        <div class="filter-bar">
            <form method="GET" class="filter-form">
                <div class="search-group">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Search categories..." value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                    </div>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Status</label>
                    <select name="status" class="filter-select">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Brand Layout</label>
                    <select name="brand" class="filter-select">
                        <option value="all" <?php echo $brand_filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="enabled" <?php echo $brand_filter === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="disabled" <?php echo $brand_filter === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="categories.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
                
                <div class="help-indicator" title="Keyboard shortcuts: Ctrl+S (Save), Ctrl+A (Select All), Esc (Close)">
                    <i class="fas fa-keyboard"></i>
                </div>
            </form>
        </div>
        
        <!-- Quick Actions Bar -->
        <div class="quick-actions-bar">
            <div class="left-actions">
                <button id="addCategoryBtn" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Category
                </button>
                <button id="selectAllBtn" class="btn btn-secondary">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button id="deselectAllBtn" class="btn btn-secondary" style="display: none;">
                    <i class="fas fa-square"></i> Deselect All
                </button>
            </div>
            
            <div class="right-actions">
                <div class="bulk-actions" id="bulkActions" style="display: none;">
                    <span class="bulk-info">
                        <span id="selectedCount">0</span> selected
                    </span>
                    <select id="bulkActionSelect" class="bulk-select">
                        <option value="">Choose action...</option>
                        <option value="activate">Activate</option>
                        <option value="deactivate">Deactivate</option>
                        <option value="enable_brands">Enable Brand Layout</option>
                        <option value="disable_brands">Disable Brand Layout</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button id="executeBulkAction" class="btn btn-warning">
                        <i class="fas fa-bolt"></i> Execute
                    </button>
                </div>
            </div>
        </div>
        
        <!-- ENHANCED CATEGORIES TABLE -->
        <div class="card" id="table-view">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Categories Management</h3>
                    <p class="card-subtitle">
                        <?php if ($search || $status_filter !== 'all' || $brand_filter !== 'all'): ?>
                            Showing <?php echo count($categories); ?> of <?php echo $total_categories; ?> categories
                        <?php else: ?>
                            Manage category visibility and brand layout features
                        <?php endif; ?>
                    </p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span id="table-info" class="text-xs text-muted" style="padding: 6px 12px; background: var(--admin-gray-100); border-radius: 6px;">
                        <?php echo count($categories); ?> total
                    </span>
                    <div class="save-controls">
                        <div class="save-indicator" id="saveIndicator">
                            <i class="fas fa-exclamation-triangle"></i>
                            Unsaved changes
                        </div>
                        <button id="resetChangesBtn" class="btn btn-secondary" style="display: none;">
                            <i class="fas fa-undo"></i>
                            Reset
                        </button>
                        <button id="saveAllBtn" class="btn btn-success" disabled>
                            <i class="fas fa-save"></i>
                            Save All Changes
                        </button>
                    </div>
                </div>
            </div>
            <div class="category-table" style="margin-top: 20px; overflow-x: auto;">
                <table>
                    <colgroup>
                        <col style="width:80px" />   <!-- Checkbox -->
                        <col style="width:280px" />  <!-- Category Details -->
                        <col style="width:120px" />  <!-- Status -->
                        <col style="width:100px" />  <!-- Raffles Count -->
                        <col style="width:80px" />   <!-- Sort Order -->
                        <col style="width:120px" />  <!-- Homepage Toggle -->
                        <col style="width:120px" />  <!-- Brand Display Toggle -->
                        <col style="width:120px" />  <!-- Actions -->
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="col-checkbox checkbox-header" onclick="toggleSelectAll(event)">
                                <input type="checkbox" id="masterCheckbox" class="checkbox">
                            </th>
                            <th class="col-details">Category Details</th>
                            <th class="col-status">Status</th>
                            <th class="col-raffles">Raffles</th>
                            <th class="col-sort">Sort Order</th>
                            <th class="col-homepage">Homepage</th>
                            <th class="col-brands">Brand Display</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: var(--ps-text-light);">
                                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                    <?php if ($search || $status_filter !== 'all' || $brand_filter !== 'all'): ?>
                                        No categories found matching your filters. <a href="categories.php">Clear filters</a>
                                    <?php else: ?>
                                        No categories found. <a href="#" onclick="document.getElementById('addCategoryBtn').click(); return false;">Add your first category</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                            <tr class="category-row <?php echo !$category['is_active'] ? 'inactive' : ''; ?>" onclick="toggleCategoryRow(this, event)" data-category-id="<?php echo $category['id']; ?>">
                                <td class="col-checkbox checkbox-column">
                                    <input type="checkbox" class="row-checkbox" data-category-id="<?php echo $category['id']; ?>">
                                </td>
                                <td class="col-details">
                                    <div class="category-client" style="display: flex; align-items: center; gap: 12px;">
                                        <div class="category-icon" style="width: 50px; height: 50px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #6b7280; border: 2px solid #e5e7eb; flex-shrink: 0;">
                                            <i class="fas <?php echo $category['icon'] ?: 'fa-tag'; ?>"></i>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div class="title" style="font-weight: 700; font-size: 14px; color: #374151; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </div>
                                            <div style="display: flex; gap: 8px; align-items: center; font-size: 11px; color: #6b7280;">
                                                <span style="background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-weight: 600;">
                                                    ID: <?php echo htmlspecialchars($category['id']); ?>
                                                </span>
                                                <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                    <?php echo $category['description'] ? htmlspecialchars($category['description']) : 'No description'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-status">
                                    <span class="status-badge <?php echo $category['is_active'] ? 'status-active' : 'status-inactive'; ?>" data-status="visibility" style="font-size: 11px; font-weight: 600; padding: 6px 12px; border-radius: 12px; display: inline-block;">
                                        <?php 
                                        $status_icons = [
                                            1 => '🟢',
                                            0 => '🔴'
                                        ];
                                        echo ($status_icons[$category['is_active']] ?? '⚪') . ' ' . ($category['is_active'] ? 'Active' : 'Inactive'); 
                                        ?>
                                    </span>
                                </td>
                                <td class="col-raffles">
                                    <div style="text-align: center;">
                                        <div style="font-weight: 700; font-size: 16px; color: #3b82f6;">
                                            <?php echo $category['raffle_count']; ?>
                                        </div>
                                        <div style="font-size: 10px; color: #6b7280; margin-top: 2px;">
                                            raffles
                                        </div>
                                    </div>
                                </td>
                                <td class="col-sort">
                                    <div style="text-align: center;">
                                        <input type="number" 
                                               class="sort-order-input" 
                                               data-category-id="<?php echo $category['id']; ?>"
                                               data-original="<?php echo $category['sort_order']; ?>"
                                               value="<?php echo $category['sort_order']; ?>"
                                               min="0" 
                                               max="999"
                                               style="width: 50px; text-align: center; padding: 4px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                                        <div style="font-size: 9px; color: #9ca3af; margin-top: 2px;">
                                            priority
                                        </div>
                                    </div>
                                </td>
                                <td class="col-homepage">
                                    <div class="toggle-group" style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                        <label class="toggle-switch">
                                            <input type="checkbox" 
                                                   data-setting="is_active" 
                                                   data-original="<?php echo $category['is_active']; ?>"
                                                   <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <div class="toggle-label" style="font-size: 10px; color: #6b7280; text-align: center;">Visibility</div>
                                    </div>
                                </td>
                                <td class="col-brands">
                                    <div class="toggle-group" style="display: flex; flex-direction: column; align-items: center; gap: 6px;">
                                        <label class="toggle-switch">
                                            <input type="checkbox" 
                                                   data-setting="show_brands" 
                                                   data-original="<?php echo $category['show_brands']; ?>"
                                                   <?php echo $category['show_brands'] ? 'checked' : ''; ?>>
                                            <span class="toggle-slider brands"></span>
                                        </label>
                                        <div class="toggle-label" style="font-size: 10px; color: #6b7280; text-align: center;">Brand Display</div>
                                        <div style="font-size: 9px; color: #9ca3af; text-align: center; margin-top: 2px;">
                                            <?php echo $category['show_brands'] ? 'Shows brands' : 'No brands'; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-actions">
                                    <div style="display: flex; gap: 4px; justify-content: center;">
                                        <button class="smart-row-edit-btn" onclick="editCategory(<?php echo $category['id']; ?>)" style="padding: 6px 12px; background: #6366f1; color: white; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 4px;">
                                            <i class="fas fa-edit"></i> <span class="btn-text">Edit</span>
                                        </button>
                                        <?php if ($category['raffle_count'] == 0): ?>
                                            <button class="smart-row-edit-btn" onclick="deleteCategory(<?php echo $category['id']; ?>)" style="padding: 6px 12px; background: #ef4444; color: white; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-trash"></i> <span class="btn-text">Delete</span>
                                            </button>
                                        <?php else: ?>
                                            <button class="smart-row-edit-btn" disabled title="Cannot delete category with existing raffles" style="padding: 6px 12px; background: #9ca3af; color: white; border: none; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: not-allowed; display: flex; align-items: center; gap: 4px;">
                                                <i class="fas fa-trash"></i> <span class="btn-text">Delete</span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Floating Save Bar -->
<div class="floating-save-bar" id="floatingSaveBar">
    <div class="save-bar-text">
        <span class="changes-count" id="changesCount">0</span>
        categories have unsaved changes
    </div>
    <button id="floatingSaveBtn" class="btn btn-success">
        <i class="fas fa-save"></i>
        Save Changes
    </button>
    <button id="floatingCancelBtn" class="btn btn-secondary">
        <i class="fas fa-times"></i>
        Cancel
    </button>
</div>

<!-- Hidden form for saving changes -->
<form id="saveChangesForm" method="POST" style="display: none;">
    <input type="hidden" name="save_all_changes" value="1">
    <input type="hidden" name="changes" id="changesInput">
</form>

<!-- Hidden form for bulk actions -->
<form id="bulkActionForm" method="POST" style="display: none;">
    <input type="hidden" name="bulk_action" id="bulkActionInput">
    <input type="hidden" name="selected_categories" id="selectedCategoriesInput">
</form>

<!-- Hidden form for sort order updates -->
<form id="sortOrderForm" method="POST" style="display: none;">
    <input type="hidden" name="update_sort_order" value="1">
    <input type="hidden" name="sort_orders" id="sortOrdersInput">
</form>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Add New Category</h3>
            <button type="button" class="modal-close" onclick="closeCategoryModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="categoryForm" method="POST">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="categoryName">Category Name *</label>
                        <input type="text" id="categoryName" name="name" class="form-input" required placeholder="Enter category name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="categoryIcon">Category Icon</label>
                        <input type="hidden" id="categoryIcon" name="icon" value="fa-tag">
                        <div class="icon-selector">
                            <?php foreach ($common_icons as $icon => $label): ?>
                            <div class="icon-option <?php echo $icon === 'fa-tag' ? 'selected' : ''; ?>" data-icon="<?php echo $icon; ?>">
                                <i class="fas <?php echo $icon; ?>"></i>
                                <span><?php echo $label; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label" for="categoryDescription">Description</label>
                        <textarea id="categoryDescription" name="description" class="form-textarea" placeholder="Enter category description (optional)"></textarea>
                    </div>
                </div>
                
                <div class="preview-card">
                    <div class="preview-title">Preview</div>
                    <div class="preview-category">
                        <div class="category-icon">
                            <i class="fas fa-tag" id="previewIcon"></i>
                        </div>
                        <div class="category-info">
                            <div class="category-name" id="previewName">New Category</div>
                            <div class="category-description" id="previewDescription">No description</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCategoryModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </div>
            
            <input type="hidden" name="category_id" id="categoryId">
            <input type="hidden" name="add_category" id="addCategoryAction" value="1">
            <input type="hidden" name="edit_category" id="editCategoryAction" value="">
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Delete Category</h3>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f59e0b; margin-bottom: 16px;"></i>
                <h4 style="margin: 0 0 8px 0; color: #1f2937;">Are you sure?</h4>
                <p style="margin: 0; color: #6b7280;">This action cannot be undone. The category will be permanently deleted.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>



<script>
// Global variables (declared at the top)
let categoryManager = null;
let categoryToDelete = null;

// Time update
function updateTime() {
    const timeEl = document.getElementById('time');
    const dateEl = document.getElementById('date');
    const now = new Date();
    
    timeEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    dateEl.innerText = now.toLocaleDateString([], { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
}
updateTime();
setInterval(updateTime, 1000);

// Enhanced Category Manager
class EnhancedCategoryManager {
    constructor() {
        this.changes = {};
        this.originalValues = {};
        this.selectedCategories = new Set();
        this.categoryData = {};
        this.init();
    }
    
    init() {
        this.storeOriginalValues();
        this.addEventListeners();
        this.updateUI();
        this.initializeData();
    }
    
    storeOriginalValues() {
        document.querySelectorAll('.category-row').forEach(row => {
            const categoryId = row.dataset.categoryId;
            this.originalValues[categoryId] = {};
            
            row.querySelectorAll('input[type="checkbox"]').forEach(input => {
                const setting = input.dataset.setting;
                if (setting) {
                    this.originalValues[categoryId][setting] = input.dataset.original === '1';
                }
            });
            
            // Store original sort order values
            const sortInput = row.querySelector('.sort-order-input');
            if (sortInput) {
                this.originalValues[categoryId]['sort_order'] = parseInt(sortInput.dataset.original) || 0;
            }
        });
    }
    
    initializeData() {
        // Store category data for easy access
        document.querySelectorAll('.category-row').forEach(row => {
            const categoryId = row.dataset.categoryId;
            const iconElement = row.querySelector('.category-icon i');
            const nameElement = row.querySelector('.title');
            const descriptionElement = row.querySelector('.col-details span:last-child');
            
            if (!categoryId || !iconElement || !nameElement) {
                console.warn('Missing required elements for category row:', row);
                return;
            }
            
            const icon = iconElement.className.match(/fa-[\w-]+/)?.[0] || 'fa-tag';
            const name = nameElement.textContent.trim();
            const description = descriptionElement ? descriptionElement.textContent.trim() : '';
            
            this.categoryData[categoryId] = {
                id: categoryId,
                name: name,
                icon: icon,
                description: description === 'No description' ? '' : description
            };
        });
    }
    
    addEventListeners() {
        // Toggle change handlers
        document.querySelectorAll('.category-row input[type="checkbox"]').forEach(input => {
            if (input.dataset.setting) {
                input.addEventListener('change', (e) => {
                    this.handleToggleChange(e.target);
                });
            }
        });
        
        // Sort order change handlers
        document.querySelectorAll('.sort-order-input').forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleSortOrderChange(e.target);
            });
            input.addEventListener('blur', (e) => {
                this.handleSortOrderChange(e.target);
            });
        });
        
        // Bulk selection handlers
        const masterCheckbox = document.getElementById('masterCheckbox');
        if (masterCheckbox) {
            masterCheckbox.addEventListener('change', (e) => {
                this.handleMasterCheckbox(e.target.checked);
            });
        }
        
        document.querySelectorAll('.row-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                this.handleRowCheckbox(e.target);
            });
        });
        
        // Quick action handlers
        const addBtn = document.getElementById('addCategoryBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                this.showAddCategoryModal();
            });
        }
        
        const selectAllBtn = document.getElementById('selectAllBtn');
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', () => {
                this.selectAllCategories();
            });
        }
        
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        if (deselectAllBtn) {
            deselectAllBtn.addEventListener('click', () => {
                this.deselectAllCategories();
            });
        }
        
        // Bulk action handlers
        const bulkBtn = document.getElementById('executeBulkAction');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', () => {
                this.executeBulkAction();
            });
        }
        
        // Save/reset handlers
        document.getElementById('saveAllBtn').addEventListener('click', () => {
            this.saveChanges();
        });
        
        document.getElementById('floatingSaveBtn').addEventListener('click', () => {
            this.saveChanges();
        });
        
        document.getElementById('resetChangesBtn').addEventListener('click', () => {
            this.resetChanges();
        });
        
        document.getElementById('floatingCancelBtn').addEventListener('click', () => {
            this.resetChanges();
        });
        
        // Modal handlers
        this.initializeModalHandlers();
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                if (Object.keys(this.changes).length > 0) {
                    this.saveChanges();
                }
            }
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                this.selectAllCategories();
            }
        });
    }
    
    initializeModalHandlers() {
        // Category form handlers
        const categoryForm = document.getElementById('categoryForm');
        if (categoryForm) {
            categoryForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitCategoryForm();
            });
        }
        
        // Icon selector
        document.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', () => {
                this.selectIcon(option);
            });
        });
        
        // Live preview
        const nameInput = document.getElementById('categoryName');
        if (nameInput) {
            nameInput.addEventListener('input', (e) => {
                this.updatePreview();
            });
        }
        
        const descInput = document.getElementById('categoryDescription');
        if (descInput) {
            descInput.addEventListener('input', (e) => {
                this.updatePreview();
            });
        }
        
        // Modal backdrop click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeAllModals();
                }
            });
        });
    }
    
    handleToggleChange(input) {
        const row = input.closest('.category-row');
        const categoryId = row.dataset.categoryId;
        const setting = input.dataset.setting;
        const newValue = input.checked;
        const originalValue = this.originalValues[categoryId][setting];
        

        
        if (!this.changes[categoryId]) {
            this.changes[categoryId] = {};
        }
        
        if (newValue !== originalValue) {
            this.changes[categoryId][setting] = newValue ? 1 : 0;
        } else {
            delete this.changes[categoryId][setting];
            if (Object.keys(this.changes[categoryId]).length === 0) {
                delete this.changes[categoryId];
            }
        }
        

        
        this.updateRowUI(row, categoryId);
        this.updateUI();
    }
    
    handleSortOrderChange(input) {
        const categoryId = input.dataset.categoryId;
        const newValue = parseInt(input.value) || 0;
        const originalValue = parseInt(input.dataset.original) || 0;
        

        
        if (!this.changes[categoryId]) {
            this.changes[categoryId] = {};
        }
        
        if (newValue !== originalValue) {
            this.changes[categoryId]['sort_order'] = newValue;
            input.style.backgroundColor = '#fff3cd';
            input.style.borderColor = '#ffc107';
        } else {
            delete this.changes[categoryId]['sort_order'];
            input.style.backgroundColor = '';
            input.style.borderColor = '';
            if (Object.keys(this.changes[categoryId]).length === 0) {
                delete this.changes[categoryId];
            }
        }
        

        
        // Only update the UI, don't call updateRowUI to avoid conflicts
        this.updateUI();
    }
    
    handleMasterCheckbox(checked) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            this.handleRowCheckbox(checkbox);
        });
    }
    
    handleRowCheckbox(checkbox) {
        const categoryId = checkbox.dataset.categoryId;
        
        if (checkbox.checked) {
            this.selectedCategories.add(categoryId);
        } else {
            this.selectedCategories.delete(categoryId);
        }
        
        this.updateBulkActions();
    }
    
    selectAllCategories() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
            this.selectedCategories.add(checkbox.dataset.categoryId);
        });
        const masterCheckbox = document.getElementById('masterCheckbox');
        if (masterCheckbox) {
            masterCheckbox.checked = true;
        }
        this.updateBulkActions();
    }
    
    deselectAllCategories() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        const masterCheckbox = document.getElementById('masterCheckbox');
        if (masterCheckbox) {
            masterCheckbox.checked = false;
        }
        this.selectedCategories.clear();
        this.updateBulkActions();
    }
    
    updateBulkActions() {
        const count = this.selectedCategories.size;
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        
        if (bulkActions) {
            bulkActions.style.display = count > 0 ? 'flex' : 'none';
        }
        if (selectedCount) {
            selectedCount.textContent = count;
        }
        
        if (selectAllBtn) {
            selectAllBtn.style.display = count > 0 ? 'none' : 'inline-flex';
        }
        if (deselectAllBtn) {
            deselectAllBtn.style.display = count > 0 ? 'inline-flex' : 'none';
        }
    }
    
    executeBulkAction() {
        const actionSelect = document.getElementById('bulkActionSelect');
        const action = actionSelect ? actionSelect.value : '';
        if (!action || this.selectedCategories.size === 0) return;
        
        const selectedArray = Array.from(this.selectedCategories);
        const actionName = actionSelect.selectedOptions[0].text;
        
        if (confirm(`Are you sure you want to ${actionName.toLowerCase()} ${selectedArray.length} categories?`)) {
            const actionInput = document.getElementById('bulkActionInput');
            const selectedInput = document.getElementById('selectedCategoriesInput');
            
            if (actionInput) actionInput.value = action;
            if (selectedInput) selectedInput.value = JSON.stringify(selectedArray);
            
            const form = document.getElementById('bulkActionForm');
            if (form) form.submit();
        }
    }
    
    updateRowUI(row, categoryId) {

        
        const hasChanges = this.changes[categoryId] && Object.keys(this.changes[categoryId]).length > 0;
        
        row.classList.toggle('changed', hasChanges);
        
        const visibilityBadge = row.querySelector('[data-status="visibility"]');
        const brandsBadge = row.querySelector('[data-status="brands"]');
        const visibilityInput = row.querySelector('[data-setting="is_active"]');
        const brandsInput = row.querySelector('[data-setting="show_brands"]');
        
        // Update visibility badge if it exists
        if (visibilityBadge && visibilityInput) {
            if (visibilityInput.checked) {
                visibilityBadge.textContent = '🟢 Active';
                visibilityBadge.className = 'status-badge status-active';
                row.classList.remove('inactive');
            } else {
                visibilityBadge.textContent = '🔴 Inactive';
                visibilityBadge.className = 'status-badge status-inactive';
                row.classList.add('inactive');
            }
        }
        
        // Update brands badge if it exists
        if (brandsBadge && brandsInput) {
            if (brandsInput.checked) {
                brandsBadge.textContent = 'Enabled';
                brandsBadge.className = 'status-badge brands-enabled';
            } else {
                brandsBadge.textContent = 'Disabled';
                brandsBadge.className = 'status-badge brands-disabled';
            }
        }
        
        // Update the brands display text in the toggle group
        const brandsDisplayText = row.querySelector('.col-brands .toggle-group div:last-child');
        if (brandsDisplayText && brandsInput) {
            brandsDisplayText.textContent = brandsInput.checked ? 'Shows brands' : 'No brands';
        }
    }
    
    updateUI() {
        const changeCount = Object.keys(this.changes).length;
        const hasChanges = changeCount > 0;
        
        const saveBtn = document.getElementById('saveAllBtn');
        const resetBtn = document.getElementById('resetChangesBtn');
        const saveIndicator = document.getElementById('saveIndicator');
        const floatingBar = document.getElementById('floatingSaveBar');
        const changesCount = document.getElementById('changesCount');
        
        saveBtn.disabled = !hasChanges;
        resetBtn.style.display = hasChanges ? 'inline-flex' : 'none';
        saveIndicator.classList.toggle('show', hasChanges);
        floatingBar.classList.toggle('show', hasChanges);
        
        if (hasChanges) {
            changesCount.textContent = changeCount;
        }
    }
    
    saveChanges() {
        if (Object.keys(this.changes).length === 0) return;
        
        document.getElementById('changesInput').value = JSON.stringify(this.changes);
        
        const saveButtons = document.querySelectorAll('#saveAllBtn, #floatingSaveBtn');
        saveButtons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        });
        
        document.getElementById('saveChangesForm').submit();
    }
    
    resetChanges() {
        document.querySelectorAll('.category-row').forEach(row => {
            const categoryId = row.dataset.categoryId;
            
            row.querySelectorAll('input[type="checkbox"]').forEach(input => {
                const setting = input.dataset.setting;
                if (setting) {
                    const originalValue = this.originalValues[categoryId][setting];
                    input.checked = originalValue;
                }
            });
            
            // Reset sort order inputs
            const sortInput = row.querySelector('.sort-order-input');
            if (sortInput) {
                const originalValue = this.originalValues[categoryId]['sort_order'] || 0;
                sortInput.value = originalValue;
                sortInput.style.backgroundColor = '';
                sortInput.style.borderColor = '';
            }
            
            this.updateRowUI(row, categoryId);
        });
        
        this.changes = {};
        this.updateUI();
    }
    
    showAddCategoryModal() {
        const modal = document.getElementById('categoryModal');
        if (!modal) return;
        
        document.getElementById('modalTitle').textContent = 'Add New Category';
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus"></i> Add Category';
        document.getElementById('categoryId').value = '';
        
        // Set form action for adding
        document.getElementById('addCategoryAction').value = '1';
        document.getElementById('editCategoryAction').value = '';
        
        // Reset form
        document.getElementById('categoryName').value = '';
        document.getElementById('categoryDescription').value = '';
        document.getElementById('categoryIcon').value = 'fa-tag';
        
        // Reset icon selection
        document.querySelectorAll('.icon-option').forEach(option => {
            option.classList.remove('selected');
        });
        const defaultIcon = document.querySelector('.icon-option[data-icon="fa-tag"]');
        if (defaultIcon) defaultIcon.classList.add('selected');
        
        this.updatePreview();
        this.showModal('categoryModal');
    }
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.remove('show');
        });
        document.body.style.overflow = '';
    }
    
    selectIcon(option) {
        document.querySelectorAll('.icon-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        option.classList.add('selected');
        
        const icon = option.dataset.icon;
        const iconInput = document.getElementById('categoryIcon');
        const previewIcon = document.getElementById('previewIcon');
        
        if (iconInput) iconInput.value = icon;
        if (previewIcon) previewIcon.className = `fas ${icon}`;
    }
    
    updatePreview() {
        const nameInput = document.getElementById('categoryName');
        const descInput = document.getElementById('categoryDescription');
        const previewName = document.getElementById('previewName');
        const previewDesc = document.getElementById('previewDescription');
        
        if (nameInput && previewName) {
            const name = nameInput.value || 'New Category';
            previewName.textContent = name;
        }
        
        if (descInput && previewDesc) {
            const description = descInput.value || 'No description';
            previewDesc.textContent = description;
        }
    }
    
    submitCategoryForm() {
        const form = document.getElementById('categoryForm');
        if (form) form.submit();
    }
}

// Modal functions
function closeCategoryModal() {
    const modal = document.getElementById('categoryModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

function closeDeleteModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}



// Global functions for button clicks (defined before class)
function editCategory(categoryId) {
    if (!categoryManager || !categoryManager.categoryData[categoryId]) return;
    
    const category = categoryManager.categoryData[categoryId];
    
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Category';
    document.getElementById('categoryId').value = categoryId;
    
    // Set form action for editing
    document.getElementById('addCategoryAction').value = '';
    document.getElementById('editCategoryAction').value = '1';
    
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description;
    document.getElementById('categoryIcon').value = category.icon;
    
    // Set icon selection
    document.querySelectorAll('.icon-option').forEach(option => {
        option.classList.remove('selected');
    });
    const selectedIcon = document.querySelector(`.icon-option[data-icon="${category.icon}"]`);
    if (selectedIcon) selectedIcon.classList.add('selected');
    
    categoryManager.updatePreview();
    categoryManager.showModal('categoryModal');
}

function deleteCategory(categoryId) {
    categoryToDelete = categoryId;
    if (categoryManager) {
        categoryManager.showModal('deleteModal');
    }
}

function confirmDelete() {
    if (categoryToDelete) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_category" value="1">
            <input type="hidden" name="category_id" value="${categoryToDelete}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}



// Initialize the enhanced category manager when DOM is ready
function initializeCategoryManager() {
    if (!categoryManager) {
        categoryManager = new EnhancedCategoryManager();
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeCategoryManager);
} else {
    // DOM is already loaded
    initializeCategoryManager();
}

// Global functions for row and checkbox interactions (matching raffles table)
function toggleSelectAll(event) {
    const masterCheckbox = document.getElementById('masterCheckbox');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = masterCheckbox.checked;
        if (categoryManager) {
            categoryManager.handleRowCheckbox(checkbox);
        }
    });
}

function toggleCategoryRow(row, event) {
    // Prevent toggle if clicking on any input elements, buttons, or interactive elements
    if (event && (
        event.target.closest('.smart-row-edit-btn') ||
        event.target.closest('input') ||
        event.target.closest('button') ||
        event.target.closest('label') ||
        event.target.closest('.toggle-switch')
    )) {
        return;
    }
    
    const checkbox = row.querySelector('.row-checkbox');
    if (checkbox) {
        checkbox.checked = !checkbox.checked;
        if (categoryManager) {
            categoryManager.handleRowCheckbox(checkbox);
        }
    }
}
</script>

<?php include __DIR__ . '/../inc/footer.php'; ?> 