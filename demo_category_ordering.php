<?php
require_once 'inc/database.php';

echo "<h1>🎯 Category-Specific Brand Ordering Demo</h1>";

// Check if we have the necessary data
$stmt = $pdo->query("SELECT COUNT(*) FROM brands");
$brand_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM categories");
$category_count = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM brand_categories");
$brand_category_count = $stmt->fetchColumn();

echo "<h2>📊 Current Database Status</h2>";
echo "<ul>";
echo "<li>Brands: $brand_count</li>";
echo "<li>Categories: $category_count</li>";
echo "<li>Brand-Category Relationships: $brand_category_count</li>";
echo "</ul>";

// Show brands with their category-specific ordering
echo "<h2>🔍 Brand Ordering by Category</h2>";

$stmt = $pdo->query("
    SELECT c.name as category_name, b.name as brand_name, 
           b.is_featured, bc.category_sort_order, b.sort_order as global_sort_order
    FROM categories c
    INNER JOIN brand_categories bc ON c.id = bc.category_id
    INNER JOIN brands b ON bc.brand_id = b.id
    ORDER BY c.name, b.is_featured DESC, bc.category_sort_order ASC, b.name ASC
");

$current_category = '';
$brands_by_category = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $category = $row['category_name'];
    if (!isset($brands_by_category[$category])) {
        $brands_by_category[$category] = [];
    }
    $brands_by_category[$category][] = $row;
}

foreach ($brands_by_category as $category => $brands) {
    echo "<h3>📂 $category</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr style='background: #f3f4f6;'>";
    echo "<th style='padding: 8px; text-align: left;'>Position</th>";
    echo "<th style='padding: 8px; text-align: left;'>Brand Name</th>";
    echo "<th style='padding: 8px; text-align: center;'>Featured</th>";
    echo "<th style='padding: 8px; text-align: center;'>Category Sort Order</th>";
    echo "<th style='padding: 8px; text-align: center;'>Global Sort Order</th>";
    echo "</tr>";
    
    foreach ($brands as $index => $brand) {
        $position = $index + 1;
        $featured_icon = $brand['is_featured'] ? '⭐' : '';
        $featured_text = $brand['is_featured'] ? 'Yes' : 'No';
        
        echo "<tr>";
        echo "<td style='padding: 8px; font-weight: bold;'>$position</td>";
        echo "<td style='padding: 8px;'>$featured_icon {$brand['brand_name']}</td>";
        echo "<td style='padding: 8px; text-align: center;'>$featured_text</td>";
        echo "<td style='padding: 8px; text-align: center;'>{$brand['category_sort_order']}</td>";
        echo "<td style='padding: 8px; text-align: center;'>{$brand['global_sort_order']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>🎮 How to Use Category-Specific Ordering</h2>";
echo "<div style='background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 20px; margin: 20px 0;'>";
echo "<h3>Step-by-Step Guide:</h3>";
echo "<ol>";
echo "<li><strong>Go to Admin Panel:</strong> <a href='admin/admin-login.php' target='_blank'>Admin Login</a></li>";
echo "<li><strong>Navigate to Brands:</strong> <a href='admin/brands.php' target='_blank'>Brand Management</a></li>";
echo "<li><strong>Filter by Category:</strong> Use the category filter dropdown to select a specific category</li>";
echo "<li><strong>Enable Reorder Mode:</strong> Click the 'Reorder Brands' button</li>";
echo "<li><strong>Drag & Drop:</strong> Reorder brands as desired for that category</li>";
echo "<li><strong>Save Changes:</strong> Click 'Save Order' to apply the changes</li>";
echo "</ol>";

echo "<h3>💡 Example Scenario:</h3>";
echo "<p>Let's say you have Sony in both 'Gaming' and 'Electronics' categories:</p>";
echo "<ul>";
echo "<li><strong>Gaming Category:</strong> You want Sony to appear 1st</li>";
echo "<li><strong>Electronics Category:</strong> You want Sony to appear 3rd</li>";
echo "</ul>";
echo "<p>With category-specific ordering, you can achieve this! Sony will be 1st in Gaming and 3rd in Electronics.</p>";
echo "</div>";

echo "<h2>🔧 Technical Implementation</h2>";
echo "<div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 20px; margin: 20px 0;'>";
echo "<h3>Database Changes:</h3>";
echo "<ul>";
echo "<li><strong>New Column:</strong> <code>category_sort_order</code> in <code>brand_categories</code> table</li>";
echo "<li><strong>Fallback:</strong> If no category-specific order exists, falls back to global <code>sort_order</code></li>";
echo "<li><strong>Migration:</strong> Existing data automatically migrated with global sort order values</li>";
echo "</ul>";

echo "<h3>Frontend Changes:</h3>";
echo "<ul>";
echo "<li><strong>Admin Panel:</strong> Category-specific reordering in brands page</li>";
echo "<li><strong>API Endpoint:</strong> Updated to use category-specific ordering</li>";
echo "<li><strong>Frontend Display:</strong> Respects category-specific order with fallback</li>";
echo "</ul>";
echo "</div>";

echo "<div style='margin-top: 30px; text-align: center;'>";
echo "<a href='admin/brands.php' style='background: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 0 10px;'>";
echo "🚀 Try Category-Specific Reordering";
echo "</a>";
echo "<a href='index.php' style='background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin: 0 10px;'>";
echo "👀 View Frontend";
echo "</a>";
echo "</div>";
?> 