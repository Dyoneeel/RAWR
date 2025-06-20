<?php
require_once __DIR__ . '/inc/init.php';
userOnly();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_mining') {
    $db = Database::getInstance();
    $userId = $_SESSION['user_id'];
    $reward = floatval($_POST['reward']);
    
    try {
        $db->beginTransaction();
        
        // Update user balance
        $user = $db->fetchOne("SELECT rawr_balance FROM users WHERE id = ?" [$userId]);
        $newBalance = $user['rawr_balance'] + $reward;
        
        $db->update('users', 
            ['rawr_balance' => $newBalance], 
            'id = ?', 
            [$userId]
        );
        
        // Update mining data
        $miningData = $db->fetchOne("SELECT * FROM mining_data WHERE user_id = ?", [$userId]);
        $newTotalMined = $miningData['total_mined'] + $reward;
        
        $db->update('mining_data', 
            [
                'total_mined' => $newTotalMined,
                'last_mined_at' => date('Y-m-d H:i:s')
            ], 
            'user_id = ?', 
            [$userId]
        );
        
        // Create mining log
        $db->insert('mining_logs', [
            'user_id' => $userId,
            'amount' => $reward,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        $db->commit();
        
        jsonResponse([
            'success' => true,
            'newBalance' => number_format($newBalance, 4),
            'totalMined' => number_format($newTotalMined, 4)
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Mining claim error: " . $e->getMessage());
        jsonResponse(['success' => false, 'message' => 'Failed to claim reward'], 500);
    }
}

jsonResponse(['success' => false, 'message' => 'Invalid request'], 400);