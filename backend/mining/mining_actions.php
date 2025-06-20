<?php
require_once __DIR__ . '/../backend/inc/init.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => ''];

$userId = $_SESSION['user_id'];
$db = Database::getInstance();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'start':
                // Fetch mining data and upgrades
                $miningData = $db->fetchOne("SELECT * FROM mining_data WHERE user_id = ?", [$userId]);
                $upgrades = $db->fetchOne("SELECT * FROM mining_upgrades WHERE user_id = ?", [$userId]);

                if (!$miningData || !$upgrades) {
                    throw new Exception("Mining data or upgrades not found.");
                }

                // Cooldown check
                $lastMinedAt = strtotime($miningData['last_mined_at']);
                if ($lastMinedAt && time() - $lastMinedAt < 300) { // 5 minutes
                    $remaining = 300 - (time() - $lastMinedAt);
                    throw new Exception("Please wait {$remaining} seconds before mining again.");
                }

                // Basic formula to compute mined amount
                $boost = $miningData['boost_level'];
                $shovel = $upgrades['shovel_level'];
                $energy = $upgrades['energy_level'];
                $pickaxe = $upgrades['pickaxe_level'];

                $baseRate = 1.0; // base tokens per mine
                $totalBoost = 1 + ($boost + $shovel + $energy + $pickaxe - 4) * 0.1; // each level adds 10%
                $amountMined = round($baseRate * $totalBoost, 8);

                // Update user balance
                $db->query("UPDATE users SET rawr_balance = rawr_balance + ? WHERE id = ?", [$amountMined, $userId]);

                // Update mining_data
                $db->query("UPDATE mining_data SET total_mined = total_mined + ?, last_mined_at = NOW() WHERE user_id = ?", [$amountMined, $userId]);

                // Insert into mining_logs
                $db->insert("mining_logs", [
                    'user_id' => $userId,
                    'amount' => $amountMined,
                ]);

                $response['success'] = true;
                $response['message'] = "You mined {$amountMined} RAWR!";
                $response['amount'] = $amountMined;
                $response['newBalance'] = $db->fetchOne("SELECT rawr_balance FROM users WHERE id = ?", [$userId])['rawr_balance'];
                $response['totalMined'] = $db->fetchOne("SELECT total_mined FROM mining_data WHERE user_id = ?", [$userId])['total_mined'];
                break;

            case 'claim':
                $amount = (float) ($_POST['amount'] ?? 0);

                $db->query("UPDATE users SET rawr_balance = rawr_balance + ? WHERE id = ?", [$amount, $userId]);
                $db->query("UPDATE mining_data SET total_mined = total_mined + ? WHERE user_id = ?", [$amount, $userId]);

                $response['success'] = true;
                $response['message'] = 'Claimed successfully';
                $response['newBalance'] = $db->fetchOne("SELECT rawr_balance FROM users WHERE id = ?", [$userId])['rawr_balance'];
                $response['totalMined'] = $db->fetchOne("SELECT total_mined FROM mining_data WHERE user_id = ?", [$userId])['total_mined'];
                break;

            case 'upgrade':
                $type = $_POST['upgrade_type'] ?? '';
                $validTypes = ['shovel', 'energy', 'pickaxe'];
                if (!in_array($type, $validTypes)) {
                    throw new Exception("Invalid upgrade type.");
                }

                $upgradeField = "{$type}_level";
                $upgradeRow = $db->fetchOne("SELECT * FROM mining_upgrades WHERE user_id = ?", [$userId]);
                $user = $db->fetchOne("SELECT ticket_balance, rawr_balance FROM users WHERE id = ?", [$userId]);

                if (!$upgradeRow || !$user) {
                    throw new Exception("User data not found.");
                }

                $currentLevel = $upgradeRow[$upgradeField];
                
                // Max level check
                if ($currentLevel >= 5) {
                    throw new Exception("This upgrade is already at maximum level!");
                }

                $cost = $currentLevel * 100; // Example: level 2 costs 200 tickets

                if ($user['ticket_balance'] < $cost) {
                    throw new Exception("Not enough tickets to upgrade. You need {$cost} tickets.");
                }

                // Deduct tickets
                $db->query("UPDATE users SET ticket_balance = ticket_balance - ? WHERE id = ?", [$cost, $userId]);

                // Upgrade item
                $db->query("UPDATE mining_upgrades SET {$upgradeField} = {$upgradeField} + 1 WHERE user_id = ?", [$userId]);

                $response['success'] = true;
                $response['message'] = ucfirst($type) . " upgraded to level " . ($currentLevel + 1);
                $response['newLevel'] = $currentLevel + 1;
                $response['newCost'] = ($currentLevel + 1) * 100;
                $response['newTicketBalance'] = $user['ticket_balance'] - $cost;
                break;

            default:
                throw new Exception("Invalid action.");
        }
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);