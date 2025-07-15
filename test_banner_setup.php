<?php
echo "<h1>Banner System Database Test</h1>";

try {
    require_once 'inc/database.php';
    echo "<p>✅ Database connection successful!</p>";
    
    // Check if banner_slides table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'banner_slides'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "<p>✅ Banner slides table already exists!</p>";
        
        // Count existing banners
        $stmt = $pdo->query("SELECT COUNT(*) FROM banner_slides");
        $count = $stmt->fetchColumn();
        echo "<p>📊 Found {$count} banner slides in database.</p>";
        
        // Show sample banners
        $stmt = $pdo->query("SELECT title, is_active, sort_order FROM banner_slides ORDER BY sort_order LIMIT 5");
        $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($banners)) {
            echo "<h3>Sample Banners:</h3>";
            echo "<ul>";
            foreach ($banners as $banner) {
                $status = $banner['is_active'] ? 'Active' : 'Inactive';
                echo "<li><strong>{$banner['title']}</strong> (Order: {$banner['sort_order']}, Status: {$status})</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p>❌ Banner slides table does not exist. Creating it now...</p>";
        
        // Create the table
        $sql = "
        CREATE TABLE banner_slides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            subtitle VARCHAR(500),
            description TEXT,
            background_image VARCHAR(500) NOT NULL,
            button_text VARCHAR(100) DEFAULT 'Get Started',
            button_url VARCHAR(500),
            badge_text VARCHAR(100),
            badge_color ENUM('yellow', 'red', 'blue', 'green', 'purple') DEFAULT 'yellow',
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            start_date DATE NULL,
            end_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        echo "<p>✅ Banner slides table created successfully!</p>";
        
        // Insert sample data
        $sampleSlides = [
            [
                'title' => 'Win an iPhone 15 Pro Max',
                'subtitle' => 'Latest flagship smartphone',
                'description' => 'Get your chance to win the most advanced iPhone ever. Just RM1 per ticket!',
                'background_image' => 'images/iphone15.jpg',
                'button_text' => 'Buy Tickets Now',
                'button_url' => '#',
                'badge_text' => 'FLASH DEAL',
                'badge_color' => 'yellow',
                'sort_order' => 1
            ],
            [
                'title' => 'PlayStation 5 Bundle',
                'subtitle' => 'Gaming console + games',
                'description' => 'Experience next-gen gaming with PS5. Includes 3 popular games!',
                'background_image' => 'images/ps5.jpg',
                'button_text' => 'Try Your Luck',
                'button_url' => '#',
                'badge_text' => 'HOT DEAL',
                'badge_color' => 'red',
                'sort_order' => 2
            ],
            [
                'title' => 'MacBook Air M2',
                'subtitle' => 'Ultra-fast laptop',
                'description' => 'Powerful performance meets incredible battery life. Perfect for work and play!',
                'background_image' => 'images/monitor.jpg',
                'button_text' => 'Get Started',
                'button_url' => '#',
                'badge_text' => 'NEW ARRIVAL',
                'badge_color' => 'green',
                'sort_order' => 3
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO banner_slides (title, subtitle, description, background_image, button_text, button_url, badge_text, badge_color, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleSlides as $slide) {
            $stmt->execute([
                $slide['title'],
                $slide['subtitle'],
                $slide['description'],
                $slide['background_image'],
                $slide['button_text'],
                $slide['button_url'],
                $slide['badge_text'],
                $slide['badge_color'],
                $slide['sort_order']
            ]);
        }
        
        echo "<p>✅ Sample banner slides added successfully!</p>";
    }
    
    echo "<h2>🎉 Setup Complete!</h2>";
    echo "<p>You can now:</p>";
    echo "<ul>";
    echo "<li><a href='admin/banners.php'>Manage Banners</a> - Add, edit, and organize your banner slides</li>";
    echo "<li><a href='index.php'>View Homepage</a> - See your banner slider in action</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database connection settings in <code>inc/database.php</code></p>";
} catch (Exception $e) {
    echo "<p>❌ General Error: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}

h1, h2, h3 {
    color: #0070D1;
}

p {
    background: white;
    padding: 15px;
    border-radius: 8px;
    margin: 10px 0;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

ul {
    background: white;
    padding: 20px 40px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

li {
    margin: 10px 0;
}

a {
    color: #0070D1;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}

code {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
}
</style> 