<?php
// Simple script to add image_url column to brands table
require_once 'inc/database.php';

echo "<h1>Brand Images Setup</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

try {
    // Check if brands table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'brands'");
    $brands_table_exists = $stmt->rowCount() > 0;
    
    if ($brands_table_exists) {
        echo "<p class='info'>✅ Brands table found</p>";
        
        // Check if image_url column exists in brands table
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'image_url'");
        $imageUrlExists = $stmt->rowCount() > 0;
        
        if (!$imageUrlExists) {
            // Add image_url column to brands table
            $pdo->exec("ALTER TABLE brands ADD COLUMN image_url VARCHAR(500) AFTER website_url");
            echo "<p class='success'>✅ Added image_url column to brands table</p>";
        } else {
            echo "<p class='info'>✅ image_url column already exists in brands table</p>";
        }
        
        // Create brands images directory
        $upload_dir = 'images/brands/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
            echo "<p class='success'>✅ Created brands images directory: $upload_dir</p>";
        } else {
            echo "<p class='info'>✅ Brands images directory already exists: $upload_dir</p>";
        }
        
        // Verify the column was added
        $stmt = $pdo->query("SHOW COLUMNS FROM brands LIKE 'image_url'");
        $columnExists = $stmt->rowCount() > 0;
        
        if ($columnExists) {
            echo "<h2 class='success'>🎉 Brand images setup completed successfully!</h2>";
            echo "<p><strong>Next steps:</strong></p>";
            echo "<ol>";
            echo "<li><a href='admin/admin-login.php' target='_blank'>Go to Admin Panel</a></li>";
            echo "<li>Login and navigate to Brands management</li>";
            echo "<li>Add or edit brands to upload logo images</li>";
            echo "</ol>";
        } else {
            echo "<p class='error'>❌ Failed to add image_url column. Please check database permissions.</p>";
        }
        
    } else {
        echo "<p class='error'>❌ Brands table doesn't exist. Please run setup-database.php first.</p>";
        echo "<p><a href='setup-database.php'>Run Database Setup</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection settings in inc/database.php</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Homepage</a></p>";
?> 