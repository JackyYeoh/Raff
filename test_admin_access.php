<?php
session_start();
require_once 'inc/database.php';

echo "<h1>Admin Access Test</h1>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM banner_slides");
    $bannerCount = $stmt->fetchColumn();
    echo "<p>✅ Database connection successful</p>";
    echo "<p>✅ Banner table exists with {$bannerCount} records</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test admin session
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    echo "<p>✅ Admin session is active</p>";
    echo "<p>✅ You can access <a href='admin/banners.php'>Banners Page</a></p>";
} else {
    echo "<p>❌ Admin session not active</p>";
    echo "<p>Please <a href='admin/admin-login.php'>login to admin</a> first</p>";
}

// Test banner table structure
try {
    $stmt = $pdo->query("DESCRIBE banner_slides");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Banner table structure:</p>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>{$column['Field']} - {$column['Type']}</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p>❌ Error checking table structure: " . $e->getMessage() . "</p>";
}

// Show sample data
try {
    $stmt = $pdo->query("SELECT * FROM banner_slides LIMIT 3");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($banners)) {
        echo "<p>✅ Sample banner data:</p>";
        echo "<ul>";
        foreach ($banners as $banner) {
            echo "<li>{$banner['title']} (ID: {$banner['id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No banner data found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error fetching banner data: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Quick Links:</h2>";
echo "<ul>";
echo "<li><a href='admin/admin-login.php'>Admin Login</a></li>";
echo "<li><a href='admin/dashboard.php'>Admin Dashboard</a></li>";
echo "<li><a href='admin/banners.php'>Banners Management</a></li>";
echo "<li><a href='index.php'>Homepage (to see banner slider)</a></li>";
echo "</ul>";

echo "<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
h1, h2 { color: #0070D1; }
p { background: white; padding: 15px; border-radius: 8px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
ul { background: white; padding: 20px 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
li { margin: 10px 0; }
a { color: #0070D1; text-decoration: none; font-weight: bold; }
a:hover { text-decoration: underline; }
</style>";
?> 