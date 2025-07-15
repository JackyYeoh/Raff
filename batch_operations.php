<?php
session_start();

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
require_once 'inc/database.php';

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get and validate input
$raffle_ids = json_decode($_POST['raffle_ids'] ?? '[]', true);
$action = $_POST['action'] ?? '';

if (empty($raffle_ids) || !is_array($raffle_ids)) {
    echo json_encode(['success' => false, 'message' => 'No raffles selected']);
    exit;
}

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

// Sanitize raffle IDs
$raffle_ids = array_map('intval', $raffle_ids);
$raffle_ids = array_filter($raffle_ids, function($id) { return $id > 0; });

if (empty($raffle_ids)) {
    echo json_encode(['success' => false, 'message' => 'Invalid raffle IDs']);
    exit;
}

try {
    $pdo->beginTransaction();
    $processed_count = 0;
    
    switch ($action) {
        case 'update_status':
            $status = $_POST['status'] ?? '';
            $valid_statuses = ['active', 'draft', 'closed', 'cancelled'];
            
            if (!in_array($status, $valid_statuses)) {
                throw new Exception('Invalid status value');
            }
            
            $placeholders = str_repeat('?,', count($raffle_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE raffles SET status = ? WHERE id IN ($placeholders)");
            $params = array_merge([$status], $raffle_ids);
            
            if ($stmt->execute($params)) {
                $processed_count = $stmt->rowCount();
            }
            break;
            
        case 'delete':
            // Check if any raffles have sold tickets
            $placeholders = str_repeat('?,', count($raffle_ids) - 1) . '?';
            $check_stmt = $pdo->prepare("SELECT id, title, sold_tickets FROM raffles WHERE id IN ($placeholders)");
            $check_stmt->execute($raffle_ids);
            $raffles_to_check = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $raffles_with_sales = array_filter($raffles_to_check, function($raffle) {
                return $raffle['sold_tickets'] > 0;
            });
            
            if (!empty($raffles_with_sales)) {
                $titles = array_map(function($r) { return $r['title']; }, $raffles_with_sales);
                throw new Exception('Cannot delete raffles with sold tickets: ' . implode(', ', $titles));
            }
            
            // Delete raffles that have no sold tickets
            $deletable_ids = array_map(function($r) { return $r['id']; }, 
                array_filter($raffles_to_check, function($raffle) {
                    return $raffle['sold_tickets'] == 0;
                })
            );
            
            if (!empty($deletable_ids)) {
                $placeholders = str_repeat('?,', count($deletable_ids) - 1) . '?';
                $stmt = $pdo->prepare("DELETE FROM raffles WHERE id IN ($placeholders)");
                
                if ($stmt->execute($deletable_ids)) {
                    $processed_count = $stmt->rowCount();
                }
            }
            break;
            
        case 'duplicate':
            // Get original raffles data
            $placeholders = str_repeat('?,', count($raffle_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT * FROM raffles WHERE id IN ($placeholders)");
            $stmt->execute($raffle_ids);
            $original_raffles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Prepare insert statement
            $insert_stmt = $pdo->prepare("
                INSERT INTO raffles (title, description, image_url, ticket_price, total_tickets, 
                                   tickets_per_entry, sold_tickets, draw_date, status, category_id, brand_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, 'draft', ?, ?, NOW())
            ");
            
            foreach ($original_raffles as $raffle) {
                $new_title = $raffle['title'] . ' (Copy)';
                
                $insert_stmt->execute([
                    $new_title,
                    $raffle['description'],
                    $raffle['image_url'],
                    $raffle['ticket_price'],
                    $raffle['total_tickets'],
                    $raffle['tickets_per_entry'],
                    $raffle['draw_date'],
                    $raffle['category_id'],
                    $raffle['brand_id']
                ]);
                
                if ($insert_stmt->rowCount() > 0) {
                    $processed_count++;
                }
            }
            break;
            
        case 'batch_edit':
            // Build dynamic update query based on provided fields
            $update_fields = [];
            $update_params = [];
            
            // Status update
            if (isset($_POST['status']) && !empty($_POST['status'])) {
                $status = $_POST['status'];
                $valid_statuses = ['active', 'draft', 'closed', 'cancelled'];
                
                if (in_array($status, $valid_statuses)) {
                    $update_fields[] = 'status = ?';
                    $update_params[] = $status;
                }
            }
            
            // Category update
            if (isset($_POST['category_id']) && $_POST['category_id'] !== '') {
                $category_id = intval($_POST['category_id']);
                if ($category_id > 0) {
                    $update_fields[] = 'category_id = ?';
                    $update_params[] = $category_id;
                }
            }
            
            // Brand update
            if (isset($_POST['brand_id']) && $_POST['brand_id'] !== '') {
                $brand_id = intval($_POST['brand_id']);
                if ($brand_id > 0) {
                    $update_fields[] = 'brand_id = ?';
                    $update_params[] = $brand_id;
                }
            }
            
            // Price update
            if (isset($_POST['price_action']) && isset($_POST['price_value']) && $_POST['price_value'] !== '') {
                $price_action = $_POST['price_action'];
                $price_value = floatval($_POST['price_value']);
                
                switch ($price_action) {
                    case 'set':
                        if ($price_value >= 0) {
                            $update_fields[] = 'ticket_price = ?';
                            $update_params[] = $price_value;
                        }
                        break;
                        
                    case 'increase':
                        if ($price_value > 0) {
                            $update_fields[] = 'ticket_price = ticket_price + ?';
                            $update_params[] = $price_value;
                        }
                        break;
                        
                    case 'decrease':
                        if ($price_value > 0) {
                            $update_fields[] = 'ticket_price = GREATEST(0, ticket_price - ?)';
                            $update_params[] = $price_value;
                        }
                        break;
                        
                    case 'multiply':
                        if ($price_value > 0) {
                            $update_fields[] = 'ticket_price = ticket_price * ?';
                            $update_params[] = $price_value;
                        }
                        break;
                }
            }
            
            // Tickets per entry update
            if (isset($_POST['tickets_per_entry']) && $_POST['tickets_per_entry'] !== '') {
                $tickets_per_entry = intval($_POST['tickets_per_entry']);
                if ($tickets_per_entry > 0) {
                    $update_fields[] = 'tickets_per_entry = ?';
                    $update_params[] = $tickets_per_entry;
                }
            }
            
            // Draw date update
            if (isset($_POST['draw_date'])) {
                if (empty($_POST['draw_date'])) {
                    // Remove draw date
                    $update_fields[] = 'draw_date = NULL';
                } else {
                    // Set new draw date
                    $draw_date = $_POST['draw_date'];
                    if (DateTime::createFromFormat('Y-m-d\TH:i', $draw_date)) {
                        $update_fields[] = 'draw_date = ?';
                        $update_params[] = $draw_date;
                    }
                }
            }
            
            // Execute update if we have fields to update
            if (!empty($update_fields)) {
                $placeholders = str_repeat('?,', count($raffle_ids) - 1) . '?';
                $update_sql = "UPDATE raffles SET " . implode(', ', $update_fields) . 
                             " WHERE id IN ($placeholders)";
                
                $all_params = array_merge($update_params, $raffle_ids);
                $stmt = $pdo->prepare($update_sql);
                
                if ($stmt->execute($all_params)) {
                    $processed_count = $stmt->rowCount();
                }
            } else {
                throw new Exception('No valid fields provided for update');
            }
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
    $pdo->commit();
    
    // Log the batch operation
    error_log("Batch operation: $action performed on " . count($raffle_ids) . " raffles by admin. Processed: $processed_count");
    
    echo json_encode([
        'success' => true, 
        'message' => "Successfully processed $processed_count raffle(s)",
        'processed_count' => $processed_count,
        'action' => $action
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Batch operation error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 