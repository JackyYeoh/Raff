<?php
// Define BASE_URL if it's not already defined (for direct access)
if (!defined('BASE_URL')) {
    define('BASE_URL', '/raffle-demo');
}

require_once 'inc/database.php';

// Simple admin check
session_start();
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: ' . BASE_URL . '/admin/admin-login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $edit_mode = $_POST['edit_mode'] ?? 'single';
    
    if ($edit_mode === 'single' && isset($_POST['raffle_id'])) {
        // Single edit mode - existing functionality
        $raffle_id = $_POST['raffle_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $draw_date = $_POST['draw_date'];
        $category_id = $_POST['category_id'];
        $brand_id = !empty($_POST['brand_id']) ? $_POST['brand_id'] : null;
        $ticket_price = $_POST['ticket_price'] ?? $_POST['price']; // Handle both field names
        $total_tickets = $_POST['total_tickets'];
        $tickets_per_entry = $_POST['tickets_per_entry'] ?: 1;
        $status = $_POST['status'] ?: 'draft';
        $image_url = null;
    } elseif ($edit_mode === 'batch' && isset($_POST['raffle_ids'])) {
        // Batch edit mode - handle via redirect to batch_operations.php
        $raffle_ids = json_decode($_POST['raffle_ids'], true);
        
        if (empty($raffle_ids) || !is_array($raffle_ids)) {
            $_SESSION['flash_message'] = 'Error: No raffles selected for batch edit.';
            header("Location: " . BASE_URL . "/admin/raffles.php");
            exit();
        }
        
        // Build batch data
        $batch_data = ['action' => 'batch_edit'];
        $changed_fields = [];
        
        // Collect changed fields
        $field_mapping = [
            'title' => 'title',
            'description' => 'description', 
            'status' => 'status',
            'category_id' => 'category_id',
            'brand_id' => 'brand_id',
            'ticket_price' => 'ticket_price',
            'total_tickets' => 'total_tickets',
            'tickets_per_entry' => 'tickets_per_entry',
            'draw_date' => 'draw_date'
        ];
        
        foreach ($field_mapping as $post_key => $db_field) {
            if (isset($_POST[$post_key]) && $_POST[$post_key] !== '') {
                $changed_fields[$db_field] = $_POST[$post_key];
            }
        }
        
        if (empty($changed_fields)) {
            $_SESSION['flash_message'] = 'Error: No changes detected in batch edit.';
            header("Location: " . BASE_URL . "/admin/raffles.php");
            exit();
        }
        
        // Process batch update directly here instead of redirecting
        try {
            $pdo->beginTransaction();
            $processed_count = 0;
            
            // Build dynamic update query
            $update_fields = [];
            $update_params = [];
            
            foreach ($changed_fields as $field => $value) {
                if ($field === 'brand_id' && empty($value)) {
                    $update_fields[] = 'brand_id = NULL';
                } else {
                    $update_fields[] = "$field = ?";
                    $update_params[] = $value;
                }
            }
            
            if (!empty($update_fields)) {
                $placeholders = str_repeat('?,', count($raffle_ids) - 1) . '?';
                $update_sql = "UPDATE raffles SET " . implode(', ', $update_fields) . 
                             " WHERE id IN ($placeholders)";
                
                $all_params = array_merge($update_params, $raffle_ids);
                $stmt = $pdo->prepare($update_sql);
                
                if ($stmt->execute($all_params)) {
                    $processed_count = $stmt->rowCount();
                }
            }
            
            $pdo->commit();
            
            $_SESSION['flash_message'] = "Successfully updated $processed_count raffle(s) via batch edit!";
            header("Location: " . BASE_URL . "/admin/raffles.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash_message'] = 'Error: ' . $e->getMessage();
            header("Location: " . BASE_URL . "/admin/raffles.php");
            exit();
        }
    } else {
        // Invalid request
        $_SESSION['flash_message'] = 'Error: Invalid edit request.';
        header("Location: " . BASE_URL . "/admin/raffles.php");
        exit();
    }
    
    // Continue with single edit mode logic below...

    // --- Image Upload Logic ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $upload_dir = 'images/';
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate file type
        if (!in_array($image['type'], $allowed_types)) {
            die("Error: Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.");
        }

        // Validate file size
        if ($image['size'] > $max_size) {
            die("Error: File size exceeds the maximum limit of 5MB.");
        }

        // Generate a unique filename
        $file_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $unique_filename = uniqid('raffle_', true) . '.' . $file_extension;
        $upload_path = $upload_dir . $unique_filename;

        // Move the uploaded file
        if (move_uploaded_file($image['tmp_name'], $upload_path)) {
            $image_url = 'images/' . $unique_filename;
        } else {
            // Handle file move error
            // For simplicity, we die here. In a real app, you might set an error message and redirect.
            die("Error: Failed to move uploaded file.");
        }
    }

    // --- Database Update ---
    try {
        // $pdo is already available from the included database.php file

        // If a new image was uploaded, update the image_url field.
        // Otherwise, keep the existing one.
        if ($image_url) {
            $stmt = $pdo->prepare("UPDATE raffles SET title = ?, description = ?, draw_date = ?, category_id = ?, brand_id = ?, ticket_price = ?, total_tickets = ?, tickets_per_entry = ?, status = ?, image_url = ? WHERE id = ?");
            $stmt->execute([$title, $description, $draw_date, $category_id, $brand_id, $ticket_price, $total_tickets, $tickets_per_entry, $status, $image_url, $raffle_id]);
        } else {
            // No new image, so don't update the image_url column
            $stmt = $pdo->prepare("UPDATE raffles SET title = ?, description = ?, draw_date = ?, category_id = ?, brand_id = ?, ticket_price = ?, total_tickets = ?, tickets_per_entry = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $description, $draw_date, $category_id, $brand_id, $ticket_price, $total_tickets, $tickets_per_entry, $status, $raffle_id]);
        }

        // Handle tags for single edit mode
        if ($edit_mode === 'single' && isset($_POST['tags'])) {
            $tags_input = $_POST['tags'];
            
            // Clear existing tags for this raffle
            $stmt = $pdo->prepare("DELETE FROM raffle_tags WHERE raffle_id = ?");
            $stmt->execute([$raffle_id]);
            
            // Process tags if provided
            if (!empty($tags_input)) {
                // Handle both comma-separated string and JSON array
                if (is_string($tags_input)) {
                    if (strpos($tags_input, '[') === 0) {
                        // JSON array
                        $tags = json_decode($tags_input, true);
                        if (is_array($tags)) {
                            $tags = array_map(function($tag) {
                                return is_array($tag) ? $tag['tag_name'] : $tag;
                            }, $tags);
                        } else {
                            $tags = [];
                        }
                    } else {
                        // Comma-separated string
                        $tags = array_map('trim', explode(',', $tags_input));
                    }
                } else {
                    $tags = [];
                }
                
                // Filter out empty tags and add them to database
                $tags = array_filter($tags, function($tag) {
                    return !empty(trim($tag));
                });
                
                if (!empty($tags)) {
                    $stmt = $pdo->prepare("INSERT INTO raffle_tags (raffle_id, tag_name, tag_type) VALUES (?, ?, 'custom')");
                    
                    foreach ($tags as $tag) {
                        $tag = trim($tag);
                        if (!empty($tag)) {
                            $stmt->execute([$raffle_id, $tag]);
                            
                            // Update popular tags
                            $popular_stmt = $pdo->prepare("
                                INSERT INTO popular_tags (tag_name, usage_count) 
                                VALUES (?, 1) 
                                ON DUPLICATE KEY UPDATE 
                                usage_count = usage_count + 1,
                                last_used = CURRENT_TIMESTAMP
                            ");
                            $popular_stmt->execute([$tag]);
                        }
                    }
                }
            }
        }

        // Set flash message and redirect back to the raffles page
        $_SESSION['flash_message'] = 'Raffle updated successfully!';
        header("Location: " . BASE_URL . "/admin/raffles.php");
        exit();

    } catch (PDOException $e) {
        // In a real app, log this error and show a user-friendly message.
        die("Database error: " . $e->getMessage());
    }

} else {
    // Redirect if not a POST request or raffle_id is not set
    header("Location: " . BASE_URL . "/admin/raffles.php");
    exit();
} 