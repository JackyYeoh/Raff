<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['flash_message'] = 'You must be logged in to add raffles.';
    header('Location: admin-login.php');
    exit;
}

// Database connection
$host = 'localhost';
$dbname = 'raffle_platform';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $_SESSION['flash_message'] = 'Database connection failed: ' . $e->getMessage();
    header('Location: admin/raffles.php');
    exit;
}

// Validate required fields
if (empty($_POST['title']) || empty($_POST['category_id']) || empty($_POST['ticket_price']) || empty($_POST['total_tickets'])) {
    $_SESSION['flash_message'] = 'Please fill all required fields (Title, Category, Ticket Price, Total Tickets).';
    header('Location: admin/raffles.php');
    exit;
}

// Prepare data for insertion
$title = trim($_POST['title']);
$description = trim($_POST['description'] ?? '');
$category_id = (int)$_POST['category_id'];
$brand_id = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
$ticket_price = (float)$_POST['ticket_price'];
$total_tickets = (int)$_POST['total_tickets'];
$tickets_per_entry = (int)($_POST['tickets_per_entry'] ?? 1);
$status = $_POST['status'] ?? 'draft';
$draw_date = !empty($_POST['draw_date']) ? $_POST['draw_date'] : null;
$quantity = (int)($_POST['quantity'] ?? 1);

// Validate quantity
if ($quantity < 1 || $quantity > 50) {
    $_SESSION['flash_message'] = 'Quantity must be between 1 and 50.';
    header('Location: admin/raffles.php');
    exit;
}

// Validate data
if ($ticket_price <= 0) {
    $_SESSION['flash_message'] = 'Ticket price must be greater than 0.';
    header('Location: admin/raffles.php');
    exit;
}

if ($total_tickets <= 0) {
    $_SESSION['flash_message'] = 'Total tickets must be greater than 0.';
    header('Location: admin/raffles.php');
    exit;
}

if ($tickets_per_entry <= 0) {
    $_SESSION['flash_message'] = 'Tickets per entry must be greater than 0.';
    header('Location: admin/raffles.php');
    exit;
}

// Handle image upload
$image_url = 'images/placeholder.png'; // Default image
$upload_dir = 'images/';

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_name = $_FILES['image']['name'];
    $file_size = $_FILES['image']['size'];
    $file_type = $_FILES['image']['type'];
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($file_type, $allowed_types)) {
        $_SESSION['flash_message'] = 'Invalid file type. Please upload JPG, PNG, or WEBP images only.';
        header('Location: admin/raffles.php');
        exit;
    }
    
    // Validate file size (5MB max)
    if ($file_size > 5 * 1024 * 1024) {
        $_SESSION['flash_message'] = 'File size too large. Maximum size is 5MB.';
        header('Location: admin/raffles.php');
        exit;
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
    $unique_filename = uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file_tmp, $upload_path)) {
        $image_url = $upload_path;
    } else {
        $_SESSION['flash_message'] = 'Failed to upload image. Using default image.';
    }
}

try {
    // Prepare statement for multiple insertions
    $stmt = $pdo->prepare(
        "INSERT INTO raffles (title, description, image_url, ticket_price, total_tickets, tickets_per_entry, sold_tickets, draw_date, status, category_id, brand_id, created_at) 
         VALUES (:title, :description, :image_url, :ticket_price, :total_tickets, :tickets_per_entry, 0, :draw_date, :status, :category_id, :brand_id, NOW())"
    );
    
    $created_count = 0;
    $created_raffle_ids = [];
    $pdo->beginTransaction();
    
    // Create multiple raffles
    for ($i = 1; $i <= $quantity; $i++) {
        // Use the same title for all raffles (no numbering)
        $raffle_title = $title;
        
        $result = $stmt->execute([
            ':title' => $raffle_title,
            ':description' => $description,
            ':image_url' => $image_url,
            ':ticket_price' => $ticket_price,
            ':total_tickets' => $total_tickets,
            ':tickets_per_entry' => $tickets_per_entry,
            ':draw_date' => $draw_date,
            ':status' => $status,
            ':category_id' => $category_id,
            ':brand_id' => $brand_id
        ]);
        
        if ($result) {
            $created_count++;
            $created_raffle_ids[] = $pdo->lastInsertId();
        }
    }
    
    // Handle tags if provided
    if (!empty($_POST['tags'])) {
        $tags = json_decode($_POST['tags'], true);
        if (is_array($tags) && !empty($tags)) {
            // Add tags to all created raffles
            $tag_stmt = $pdo->prepare("INSERT INTO raffle_tags (raffle_id, tag_name, tag_type, created_at) VALUES (:raffle_id, :tag_name, :tag_type, NOW())");
            
            foreach ($created_raffle_ids as $raffle_id) {
                foreach ($tags as $tag) {
                    try {
                        $tag_stmt->execute([
                            ':raffle_id' => $raffle_id,
                            ':tag_name' => $tag['tag_name'],
                            ':tag_type' => $tag['tag_type']
                        ]);
                    } catch (PDOException $e) {
                        // Tag might already exist, continue
                        continue;
                    }
                }
            }
        }
    }
    
    $pdo->commit();
    
    if ($created_count > 0) {
        $tag_message = '';
        if (!empty($_POST['tags'])) {
            $tags = json_decode($_POST['tags'], true);
            if (is_array($tags) && !empty($tags)) {
                $tag_count = count($tags);
                $tag_message = " with {$tag_count} tag" . ($tag_count > 1 ? 's' : '');
            }
        }
        
        if ($quantity > 1) {
            $_SESSION['flash_message'] = "Successfully created {$created_count} out of {$quantity} raffles based on \"" . htmlspecialchars($title) . "\"{$tag_message}!";
        } else {
            $_SESSION['flash_message'] = 'Raffle "' . htmlspecialchars($title) . '" created successfully' . $tag_message . '!';
        }
    } else {
        $_SESSION['flash_message'] = 'Failed to create raffles. Please try again.';
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['flash_message'] = 'Database error: ' . $e->getMessage();
}

// Redirect back to manage raffles page
header('Location: admin/raffles.php');
exit;
?> 