<?php
session_start();

// Simple admin authentication check
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
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
    
    // Check if this is a batch operation (from form data) or single operation (from JSON)
    if (isset($_POST['action'])) {
        // Batch operations
        $action = $_POST['action'];
        
        if ($action === 'batch_update_status') {
            // Batch status update
            if (!isset($_POST['raffle_ids']) || !isset($_POST['new_status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            $raffle_ids = json_decode($_POST['raffle_ids'], true);
            $new_status = $_POST['new_status'];
            
            // Validate status
            $valid_statuses = ['draft', 'active', 'closed', 'cancelled'];
            if (!in_array($new_status, $valid_statuses)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid status']);
                exit;
            }
            
            if (empty($raffle_ids) || !is_array($raffle_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No raffles selected']);
                exit;
            }
            
            // Update multiple raffles
            $placeholders = str_repeat('?,', count($raffle_ids) - 1) . '?';
            $stmt = $pdo->prepare("UPDATE raffles SET status = ? WHERE id IN ($placeholders)");
            $params = array_merge([$new_status], $raffle_ids);
            $result = $stmt->execute($params);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Raffles updated successfully',
                    'updated_count' => $stmt->rowCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No raffles were updated']);
            }
            
        } elseif ($action === 'batch_delete') {
            // Batch delete
            if (!isset($_POST['raffle_ids'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
                exit;
            }
            
            $raffle_ids = json_decode($_POST['raffle_ids'], true);
            
            if (empty($raffle_ids) || !is_array($raffle_ids)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'No raffles selected']);
                exit;
            }
            
            // Delete multiple raffles
            $placeholders = str_repeat('?,', count($raffle_ids) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM raffles WHERE id IN ($placeholders)");
            $result = $stmt->execute($raffle_ids);
            
            if ($result) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Raffles deleted successfully',
                    'deleted_count' => $stmt->rowCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No raffles were deleted']);
            }
            
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } else {
        // Single raffle operation (existing functionality)
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['raffle_id']) || !isset($input['status'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
            exit;
        }

        $raffle_id = $input['raffle_id'];
        $new_status = $input['status'];

        // Validate status
        $valid_statuses = ['draft', 'active', 'closed', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
            exit;
        }
        
        // Update the raffle status
        $stmt = $pdo->prepare("UPDATE raffles SET status = ? WHERE id = ?");
        $result = $stmt->execute([$new_status, $raffle_id]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Raffle status updated successfully',
                'raffle_id' => $raffle_id,
                'new_status' => $new_status
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Raffle not found or no changes made']);
        }
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 