<?php
session_start();
require_once 'db_connect.php';

// Set header to return JSON
header('Content-Type: application/json');

// Disable error reporting to prevent HTML errors from breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get the raw input and decode it
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// For GET requests, action comes from URL parameter
$action = $_GET['action'] ?? ($data['action'] ?? '');

// ============================================
// Save a new pet
// ============================================
if ($action === 'save') {
    $petId = $data['petId'] ?? '';
    $name = $data['name'] ?? '';
    $type = $data['type'] ?? '';
    $color = $data['color'] ?? 'Unknown';
    
    if (empty($petId) || empty($name) || empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    try {
        // Check if pet already exists
        $checkStmt = $pdo->prepare("SELECT id, times_seen FROM saved_pets WHERE user_id = ? AND pet_unique_id = ?");
        $checkStmt->execute([$userId, $petId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing pet
            $updateStmt = $pdo->prepare("UPDATE saved_pets SET times_seen = times_seen + 1, last_seen = NOW(), pet_name = ?, pet_color = ? WHERE id = ?");
            $updateStmt->execute([$name, $color, $existing['id']]);
            echo json_encode(['success' => true, 'message' => 'Pet updated!']);
        } else {
            // Insert new pet
            $insertStmt = $pdo->prepare("INSERT INTO saved_pets (user_id, pet_unique_id, pet_name, pet_type, pet_color, first_seen, last_seen, times_seen) VALUES (?, ?, ?, ?, ?, NOW(), NOW(), 1)");
            $insertStmt->execute([$userId, $petId, $name, $type, $color]);
            echo json_encode(['success' => true, 'message' => 'Pet saved!']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => dbError($e)]);
    }
    exit;
}

// ============================================
// Get all saved pets for user
// ============================================
if ($action === 'get') {
    try {
        $stmt = $pdo->prepare("SELECT id, pet_unique_id as pet_id, pet_name as name, pet_type as type, pet_color as color, first_seen, last_seen, times_seen, created_at FROM saved_pets WHERE user_id = ? ORDER BY last_seen DESC");
        $stmt->execute([$userId]);
        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Make sure we're outputting only JSON
        echo json_encode(['success' => true, 'pets' => $pets]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => dbError($e)]);
    }
    exit;
}

// ============================================
// Delete a saved pet
// ============================================
if ($action === 'delete') {
    $petId = $data['petId'] ?? '';
    
    if (empty($petId)) {
        echo json_encode(['success' => false, 'message' => 'Pet ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM saved_pets WHERE user_id = ? AND pet_unique_id = ?");
        $stmt->execute([$userId, $petId]);
        echo json_encode(['success' => true, 'message' => 'Pet deleted']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => dbError($e)]);
    }
    exit;
}

// If we get here, the action was invalid
echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
?>