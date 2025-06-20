<?php
require_once __DIR__ . '/../inc/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action']) || !isset($input['amount'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['user_id'];
$amount = floatval($input['amount']);

try {
    $db = Database::getInstance();
    $db->beginTransaction();
    
    // Update user balance
    $db->executeQuery(
        "UPDATE users SET rawr_balance = rawr_balance + ? WHERE id = ?",
        [$amount, $userId]
    );
    
    // Log mining activity
    $db->executeQuery(
        "INSERT INTO mining_logs (user_id, amount, created_at) VALUES (?, ?, NOW())",
        [$userId, $amount]
    );
    
    // Get new balance
    $newBalance = $db->fetchOne(
        "SELECT rawr_balance FROM users WHERE id = ?", 
        [$userId]
    )['rawr_balance'];
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'newBalance' => $newBalance
    ]);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Mining claim failed: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to process mining reward'
    ]);
}