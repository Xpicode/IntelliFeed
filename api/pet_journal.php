<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Unknown';

// Get the raw input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Get action from request
$action = $_GET['action'] ?? ($data['action'] ?? '');

// ============================================
// Add a new journal entry
// ============================================
if ($action === 'add') {
    $petName = $data['petName'] ?? '';
    $petType = $data['petType'] ?? '';
    $noteTitle = $data['noteTitle'] ?? '';
    $noteContent = $data['noteContent'] ?? '';
    
    // Validate required fields
    if (empty($petName) || empty($noteContent)) {
        echo json_encode(['success' => false, 'message' => 'Pet name and note content are required']);
        exit;
    }
    
    // Validate pet type
    if (!in_array($petType, ['cat', 'dog'])) {
        $petType = 'cat';
    }
    
    // Check character limit
    if (strlen($noteContent) > 500) {
        echo json_encode(['success' => false, 'message' => 'Note cannot exceed 500 characters']);
        exit;
    }
    
    try {
        // Insert journal entry (deleted_at is NULL by default for new entries)
        $insertStmt = $pdo->prepare("
            INSERT INTO pet_journal (user_id, pet_name, pet_type, note_title, note_content) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$userId, $petName, $petType, $noteTitle, $noteContent]);
        
        $journalId = $pdo->lastInsertId();
        
        // Log the activity
        if (function_exists('logUserActivity')) {
            logUserActivity($userId, $username, 'add_journal', 'success', "Added journal entry ID: $journalId");
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Journal entry added successfully',
            'journalId' => $journalId
        ]);
        
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 3819) {
            echo json_encode(['success' => false, 'message' => 'Note exceeds 500 characters']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    exit;
}

// ============================================
// Get all journal entries for the user (excluding deleted)
// ============================================
if ($action === 'get_all') {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pet_journal 
            WHERE user_id = ? AND deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'entries' => $entries]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// Get all journal entries including deleted (admin/recovery view)
// ============================================
if ($action === 'get_all_with_deleted') {
    try {
        $stmt = $pdo->prepare("
            SELECT *, 
                CASE 
                    WHEN deleted_at IS NOT NULL THEN 'deleted'
                    ELSE 'active'
                END as status
            FROM pet_journal 
            WHERE user_id = ?
            ORDER BY 
                CASE 
                    WHEN deleted_at IS NOT NULL THEN deleted_at
                    ELSE created_at
                END DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'entries' => $entries]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// Soft delete a journal entry
// ============================================
if ($action === 'delete') {
    $journalId = $data['journalId'] ?? '';
    
    if (empty($journalId)) {
        echo json_encode(['success' => false, 'message' => 'Journal ID required']);
        exit;
    }
    
    try {
        // Soft delete by setting deleted_at timestamp
        $stmt = $pdo->prepare("
            UPDATE pet_journal 
            SET deleted_at = NOW() 
            WHERE id = ? AND user_id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$journalId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            if (function_exists('logUserActivity')) {
                logUserActivity($userId, $username, 'delete_journal', 'success', "Soft deleted journal entry ID: $journalId");
            }
            
            echo json_encode(['success' => true, 'message' => 'Journal entry moved to trash']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Entry not found or already deleted']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// Permanently delete a journal entry
// ============================================
if ($action === 'permanent_delete') {
    $journalId = $data['journalId'] ?? '';
    
    if (empty($journalId)) {
        echo json_encode(['success' => false, 'message' => 'Journal ID required']);
        exit;
    }
    
    try {
        // Permanently delete
        $stmt = $pdo->prepare("DELETE FROM pet_journal WHERE id = ? AND user_id = ?");
        $stmt->execute([$journalId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            if (function_exists('logUserActivity')) {
                logUserActivity($userId, $username, 'permanent_delete_journal', 'success', "Permanently deleted journal entry ID: $journalId");
            }
            
            echo json_encode(['success' => true, 'message' => 'Journal entry permanently deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Entry not found']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// Restore a soft-deleted journal entry
// ============================================
if ($action === 'restore') {
    $journalId = $data['journalId'] ?? '';
    
    if (empty($journalId)) {
        echo json_encode(['success' => false, 'message' => 'Journal ID required']);
        exit;
    }
    
    try {
        // Restore by setting deleted_at back to NULL
        $stmt = $pdo->prepare("
            UPDATE pet_journal 
            SET deleted_at = NULL 
            WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL
        ");
        $stmt->execute([$journalId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            if (function_exists('logUserActivity')) {
                logUserActivity($userId, $username, 'restore_journal', 'success', "Restored journal entry ID: $journalId");
            }
            
            echo json_encode(['success' => true, 'message' => 'Journal entry restored']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Entry not found or not deleted']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// Update a journal entry (only if not deleted)
// ============================================
if ($action === 'update') {
    $journalId = $data['journalId'] ?? '';
    $noteTitle = $data['noteTitle'] ?? '';
    $noteContent = $data['noteContent'] ?? '';
    
    if (empty($journalId) || empty($noteContent)) {
        echo json_encode(['success' => false, 'message' => 'Journal ID and note content are required']);
        exit;
    }
    
    // Check character limit
    if (strlen($noteContent) > 500) {
        echo json_encode(['success' => false, 'message' => 'Note cannot exceed 500 characters']);
        exit;
    }
    
    try {
        // Only allow updates if entry is not deleted
        $stmt = $pdo->prepare("
            UPDATE pet_journal 
            SET note_title = ?, note_content = ?, updated_at = NOW() 
            WHERE id = ? AND user_id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$noteTitle, $noteContent, $journalId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            // Log the activity
            if (function_exists('logUserActivity')) {
                logUserActivity($userId, $username, 'update_journal', 'success', "Updated journal entry ID: $journalId");
            }
            
            echo json_encode(['success' => true, 'message' => 'Journal entry updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Entry not found or has been deleted']);
        }
        
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 3819) {
            echo json_encode(['success' => false, 'message' => 'Note exceeds 500 characters']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
    exit;
}

// ============================================
// Get trash/bin (soft deleted entries)
// ============================================
if ($action === 'get_trash') {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM pet_journal 
            WHERE user_id = ? AND deleted_at IS NOT NULL
            ORDER BY deleted_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'entries' => $entries]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// ============================================
// Empty trash (permanently delete all soft deleted entries)
// ============================================
if ($action === 'empty_trash') {
    try {
        // First, get count of entries to be deleted for logging
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pet_journal WHERE user_id = ? AND deleted_at IS NOT NULL");
        $countStmt->execute([$userId]);
        $count = $countStmt->fetchColumn();
        
        // Permanently delete all soft deleted entries
        $stmt = $pdo->prepare("DELETE FROM pet_journal WHERE user_id = ? AND deleted_at IS NOT NULL");
        $stmt->execute([$userId]);
        
        // Log the activity
        if (function_exists('logUserActivity')) {
            logUserActivity($userId, $username, 'empty_trash', 'success', "Emptied trash, deleted $count entries");
        }
        
        echo json_encode(['success' => true, 'message' => "Trash emptied, $count entries permanently deleted"]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>