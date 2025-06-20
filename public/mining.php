<?php
require_once __DIR__ . '/../backend/inc/init.php';

// Ensure user is logged in
userOnly();

$userId = $_SESSION['user_id'];
$db = Database::getInstance(); // Already initialized in init.php, but good practice to ensure

// Handle mining actions via POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize action input
    $action = sanitizeInput($_POST['action'] ?? '');

    switch ($action) {
        case 'start_mining':
            // Check if mining is already in progress for this session
            if (isset($_SESSION['mining_start_time'])) {
                jsonResponse(['status' => 'error', 'message' => 'Mining already in progress'], 409);
            }

            // Fetch current mining upgrades for the user
            $upgrades = $db->fetchOne("SELECT * FROM mining_upgrades WHERE user_id = ?", [$userId]);

            // If no upgrades exist, initialize them for the user
            if (!$upgrades) {
                // Default levels for new users
                $defaultShovelLevel = 1;
                $defaultEnergyLevel = 1;
                $defaultPickaxeLevel = 1;

                // Insert initial mining upgrades record
                $db->insert("mining_upgrades", [
                    'user_id' => $userId,
                    'shovel_level' => $defaultShovelLevel,
                    'energy_level' => $defaultEnergyLevel,
                    'pickaxe_level' => $defaultPickaxeLevel
                ]);
                $upgrades = ['energy_level' => $defaultEnergyLevel]; // Set energy level for duration calculation
            }

            // Calculate mining duration based on energy level
            // Base duration is 60 minutes (3600 seconds)
            $energyLevel = $upgrades['energy_level'];
            // Duration reduction: 10% per energy level, e.g., level 1 = 0% reduction, level 2 = 10% reduction
            // Ensure minimum duration is 10 seconds to avoid division by zero or too short cycles
            $miningDuration = max(10, 3600 * (1 - (($energyLevel - 1) * 0.1)));

            // Store mining session details in session variables
            $_SESSION['mining_start_time'] = time();
            $_SESSION['mining_duration'] = $miningDuration;

            jsonResponse(['status' => 'success', 'duration' => $miningDuration]);

        case 'claim_reward':
            // Validate if there's an active mining session
            if (!isset($_SESSION['mining_start_time']) || !isset($_SESSION['mining_duration'])) {
                jsonResponse(['status' => 'error', 'message' => 'No active mining session to claim reward from'], 400);
            }

            $startTime = $_SESSION['mining_start_time'];
            $duration = $_SESSION['mining_duration'];

            // Check if the mining session is actually complete
            if (time() < $startTime + $duration) {
                jsonResponse(['status' => 'error', 'message' => 'Mining not yet complete. Please wait.'], 400);
            }

            // Fetch current mining upgrades
            $upgrades = $db->fetchOne("SELECT * FROM mining_upgrades WHERE user_id = ?", [$userId]);
            if (!$upgrades) {
                jsonResponse(['status' => 'error', 'message' => 'Mining upgrades not found. Cannot calculate reward.'], 500);
            }

            $shovelLevel = $upgrades['shovel_level'];
            $pickaxeLevel = $upgrades['pickaxe_level'];

            // Calculate base reward using the function from functions.php
            $baseReward = getMiningReward($shovelLevel); // This now directly gets the reward based on shovel level

            $bonus = 0;
            // Apply bonus chance based on pickaxe level
            // Example: 10% chance per pickaxe level (Level 1 = 10%, Level 2 = 20%, etc.)
            if (mt_rand(1, 100) <= ($pickaxeLevel * 10)) {
                // Bonus amount is 100% to 300% of the base reward
                $bonus = $baseReward * (mt_rand(100, 300) / 100);
            }

            $reward = $baseReward + $bonus;

            // Start a database transaction for atomic updates
            $db->beginTransaction();
            try {
                // Update user's RAWR balance
                $db->update("users", ['rawr_balance' => 'rawr_balance + ?'], "id = ?", [$reward, $userId]);

                // Update total mined in mining_data table
                // Check if mining_data exists, insert if not
                $miningDataExists = $db->fetchOne("SELECT user_id FROM mining_data WHERE user_id = ?", [$userId]);
                if (!$miningDataExists) {
                    $db->insert("mining_data", ['user_id' => $userId, 'total_mined' => 0.0]);
                }
                $db->update("mining_data", ['total_mined' => 'total_mined + ?', 'last_mined_at' => 'NOW()'], "user_id = ?", [$reward, $userId]);

                // Log the mining reward
                $db->insert("mining_logs", ['user_id' => $userId, 'amount' => $reward, 'created_at' => 'NOW()']);

                $db->commit();

                // Clear the session mining details after successful claim
                unset($_SESSION['mining_start_time']);
                unset($_SESSION['mining_duration']);

                // Note: Client-side streak logic remains, not persisted here.
                jsonResponse(['status' => 'success', 'reward' => round($reward, 4), 'bonus' => round($bonus, 4)]);

            } catch (Exception $e) {
                $db->rollBack();
                error_log("Mining reward claim failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to claim reward. Database error.'], 500);
            }

        case 'upgrade_equipment':
            $type = sanitizeInput($_POST['type'] ?? '');
            if (!in_array($type, ['shovel', 'energy', 'pickaxe'])) {
                jsonResponse(['status' => 'error', 'message' => 'Invalid upgrade type'], 400);
            }

            $db->beginTransaction();
            try {
                // Fetch user's current RAWR balance (for locking during transaction)
                $user = $db->fetchOne("SELECT rawr_balance FROM users WHERE id = ? FOR UPDATE", [$userId]);
                $currentBalance = (float)($user['rawr_balance'] ?? 0);

                // Fetch current upgrade levels (for locking during transaction)
                $upgrades = $db->fetchOne("SELECT * FROM mining_upgrades WHERE user_id = ? FOR UPDATE", [$userId]);

                // If no upgrade record exists, create a default one
                if (!$upgrades) {
                    $db->insert("mining_upgrades", [
                        'user_id' => $userId,
                        'shovel_level' => 1,
                        'energy_level' => 1,
                        'pickaxe_level' => 1
                    ]);
                    $upgrades = ['shovel_level' => 1, 'energy_level' => 1, 'pickaxe_level' => 1]; // Use default values
                }

                $currentLevel = (int)$upgrades["{$type}_level"];

                // Define base costs for each type and calculate dynamic cost
                $baseCosts = [
                    'shovel' => 15,
                    'energy' => 25,
                    'pickaxe' => 50
                ];
                $cost = ceil($baseCosts[$type] * pow(1.5, $currentLevel - 1)); // Cost increases exponentially

                if ($currentBalance < $cost) {
                    $db->rollBack();
                    jsonResponse(['status' => 'error', 'message' => 'Insufficient RAWR balance for upgrade'], 402);
                }

                // Deduct cost from user's balance
                $db->update("users", ['rawr_balance' => 'rawr_balance - ?'], "id = ?", [$cost, $userId]);

                // Increment upgrade level
                $newLevel = $currentLevel + 1;
                $db->update("mining_upgrades", ["{$type}_level" => $newLevel], "user_id = ?", [$userId]);

                $db->commit();
                jsonResponse([
                    'status' => 'success',
                    'new_level' => $newLevel,
                    'new_balance' => round($currentBalance - $cost, 4) // Send back updated balance
                ]);

            } catch (Exception $e) {
                $db->rollBack();
                error_log("Equipment upgrade failed for user {$userId}, type {$type}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to upgrade equipment. Database error.'], 500);
            }
    }
    // Exit after handling POST request to prevent rendering HTML
    exit;
}

// Initial fetch of user data and mining stats for rendering the page
$userData = $db->fetchOne("
    SELECT u.rawr_balance, u.ticket_balance, m.total_mined,
           mu.shovel_level, mu.energy_level, mu.pickaxe_level
    FROM users u
    LEFT JOIN mining_data m ON u.id = m.user_id
    LEFT JOIN mining_upgrades mu ON u.id = mu.user_id
    WHERE u.id = ?
", [$userId]);

// Initialize mining_data and mining_upgrades if they don't exist for the user
if (!$userData || $userData['total_mined'] === null || $userData['shovel_level'] === null) {
    $db->beginTransaction();
    try {
        // Initialize mining_data
        $miningDataExists = $db->fetchOne("SELECT user_id FROM mining_data WHERE user_id = ?", [$userId]);
        if (!$miningDataExists) {
            $db->insert("mining_data", ['user_id' => $userId, 'total_mined' => 0.0]);
        }

        // Initialize mining_upgrades
        $miningUpgradesExists = $db->fetchOne("SELECT user_id FROM mining_upgrades WHERE user_id = ?", [$userId]);
        if (!$miningUpgradesExists) {
            $db->insert("mining_upgrades", [
                'user_id' => $userId,
                'shovel_level' => 1,
                'energy_level' => 1,
                'pickaxe_level' => 1
            ]);
        }
        $db->commit();

        // Re-fetch user data after initialization
        $userData = $db->fetchOne("
            SELECT u.rawr_balance, u.ticket_balance, m.total_mined,
                   mu.shovel_level, mu.energy_level, mu.pickaxe_level
            FROM users u
            LEFT JOIN mining_data m ON u.id = m.user_id
            LEFT JOIN mining_upgrades mu ON u.id = mu.user_id
            WHERE u.id = ?
        ", [$userId]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error initializing mining data for user {$userId}: " . $e->getMessage());
        die("System maintenance in progress. Please try again later.");
    }
}

// Ensure levels are set, even if initially null from LEFT JOIN before initialization
$shovelLevel = (int)($userData['shovel_level'] ?? 1);
$energyLevel = (int)($userData['energy_level'] ?? 1);
$pickaxeLevel = (int)($userData['pickaxe_level'] ?? 1);
$totalMined = (float)($userData['total_mined'] ?? 0);


// Calculate mining stats for display
$baseMiningRate = MINING_BASE_REWARD; // From config.php
$miningRate = getMiningReward($shovelLevel); // Use function to get rate based on shovel level
$nextReward = $miningRate; // Assuming the next reward is based on the current rate for a full cycle

// Active boosts multiplier for display
$activeBoostsMultiplier = 1 + (($shovelLevel - 1) * 0.25); // Example: 0% at level 1, 25% at level 2

// Fetch mining history
$history = $db->fetchAll("
    SELECT amount, created_at
    FROM mining_logs
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
", [$userId]);

// Calculate active mining session details for the frontend
$isMining = false;
$remainingTime = 0;
$miningDurationForJS = MINING_COOLDOWN; // Default to MINING_COOLDOWN if not set in session

if (isset($_SESSION['mining_start_time'])) {
    $elapsed = time() - $_SESSION['mining_start_time'];
    $sessionDuration = $_SESSION['mining_duration'] ?? MINING_COOLDOWN;
    $remainingTime = max(0, $sessionDuration - $elapsed);
    if ($remainingTime > 0) {
        $isMining = true;
        $miningDurationForJS = $sessionDuration;
    } else {
        // Mining session completed but not claimed, reset for next session start
        unset($_SESSION['mining_start_time']);
        unset($_SESSION['mining_duration']);
    }
}

// Streak data (assuming best streak is a persistent value, currently not in DB as 'mining_streak')
// For now, it will default to 0. If there's a need to persist this, a new column in mining_data or users table would be needed.
$bestStreak = 0; // Currently not persisted in DB for mining.


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAWR Crypto Mining</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/RAWR/public/css/style.css">
    <style>
        :root {
            --primary: #FFD700;
            --primary-light: #FFDF40;
            --secondary: #FFA500;
            --accent: #FF6B35;
            --dark-bg: #0d0d0d;
            --dark-bg-gradient: linear-gradient(135deg, #1a1a1a 0%, #2d1810 100%);
            --card-bg: rgba(30, 30, 30, 0.7);
            --text-light: #f0f0f0;
            --text-muted: #aaa;
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --glass-bg: rgba(40, 40, 40, 0.35);
            --glass-border: rgba(255, 215, 0, 0.15);
            --glow: 0 0 15px rgba(255, 215, 0, 0.3);
            --section-spacing: 3rem;
        }

        /* Full-screen hero section */
        .mining-hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 80px 1rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .mining-hero::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background:
                radial-gradient(circle at 20% 30%, rgba(255, 215, 0, 0.05) 0%, transparent 30%),
                radial-gradient(circle at 80% 70%, rgba(255, 107, 53, 0.05) 0%, transparent 30%);
            z-index: -1;
        }

        .hero-content {
            max-width: 1200px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
        }

        .mining-hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.2);
            line-height: 1.1;
        }

        .mining-hero p {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 2.5rem;
            color: var(--text-muted);
            padding: 0 1rem;
            line-height: 1.7;
        }

        .mining-character {
            position: relative;
            width: 280px;
            height: 280px;
            margin: 1.5rem auto 2.5rem;
            perspective: 1000px;
        }

        .character-container {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 0.5s ease;
        }

        .mining-lion {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14rem;
            transform: translateZ(50px);
            filter: drop-shadow(0 0 30px rgba(255, 215, 0, 0.5));
            transition: all 0.3s ease;
            cursor: pointer;
            animation: idle-bounce 3s infinite ease-in-out;
        }

        .mining-progress-container {
            max-width: 600px;
            width: 100%;
            margin: 1.5rem auto 0;
            padding: 1.8rem;
            background: var(--card-bg);
            border-radius: var(--border-radius);
            border: 1px solid var(--glass-border);
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(5px);
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.2rem;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .progress-title {
            font-size: 1.2rem;
            color: var(--primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .progress-time {
            color: var(--text-muted);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .progress-bar-container {
            height: 20px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            margin-bottom: 0.8rem;
            border: 1px solid rgba(0, 0, 0, 0.2);
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 10px;
            width: 45%;
            position: relative;
            overflow: hidden;
        }

        .progress-bar::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent);
            animation: progress-shine 1.5s infinite;
        }

        .progress-stats {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
            font-size: 0.95rem;
            color: var(--text-muted);
            margin-top: 1.2rem;
        }

        .progress-stats span {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-value {
            font-weight: 600;
            color: var(--primary);
        }

        .mining-controls {
            display: flex;
            gap: 1.5rem;
            margin-top: 2.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .mining-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #1a1a1a;
            border: none;
            border-radius: 50px;
            padding: 1.1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            min-width: 220px;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .mining-btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg,
                rgba(255, 255, 255, 0.3),
                rgba(255, 255, 255, 0.1));
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .mining-btn:hover::after {
            opacity: 1;
        }

        .mining-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(255, 215, 0, 0.4);
        }

        .mining-btn.secondary {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .mining-btn.secondary:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .mining-btn.pulse {
            animation: static-pulse 1.5s infinite ease-in-out;
        }

        /* Stats Section */
        .mining-stats {
            padding: var(--section-spacing) 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 2.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 3px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.8rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.8rem;
            text-align: center;
            transition: var(--transition);
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3), var(--glow);
            border-color: rgba(255, 215, 0, 0.3);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1.2rem;
            color: var(--primary);
        }

        .stat-title {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            flex-grow: 1;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.5rem;
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.2);
        }

        .stat-subtext {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Upgrade Section */
        .upgrade-section {
            padding: var(--section-spacing) 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .upgrade-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.8rem;
        }

        .upgrade-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.8rem;
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .upgrade-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3), var(--glow);
        }

        .upgrade-header {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            margin-bottom: 1.2rem;
        }

        .upgrade-icon {
            font-size: 2rem;
            color: var(--primary);
            width: 50px;
            height: 50px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upgrade-title {
            font-weight: 600;
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 0.2rem;
        }

        .upgrade-level {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .upgrade-description {
            font-size: 0.95rem;
            margin-bottom: 1.8rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .upgrade-cost {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cost-value {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .upgrade-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #1a1a1a;
            border: none;
            border-radius: 30px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .upgrade-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        /* History Section */
        .mining-history {
            padding: var(--section-spacing) 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .history-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.8rem;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.2);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1.2rem;
            border-bottom: 1px solid rgba(255, 215, 0, 0.15);
        }

        .history-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-height: 350px;
            overflow-y: auto;
            padding-right: 0.8rem;
        }

        .history-list::-webkit-scrollbar {
            width: 8px;
        }

        .history-list::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .history-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .history-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border-radius: var(--border-radius);
            background: rgba(0, 0, 0, 0.3);
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .history-item:hover {
            background: rgba(255, 215, 0, 0.08);
            transform: translateX(5px);
        }

        .history-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 215, 0, 0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .history-details {
            flex: 1;
        }

        .history-description {
            font-weight: 500;
            margin-bottom: 0.3rem;
            color: var(--text-light);
        }

        .history-time {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .history-amount {
            font-weight: 700;
            color: var(--primary);
            font-size: 1rem;
            white-space: nowrap;
        }

        /* Animations */
        @keyframes idle-bounce {
            0%, 100% { transform: translateY(0) translateZ(50px); }
            50% { transform: translateY(-20px) translateZ(50px); }
        }

        @keyframes static-pulse {
            0%, 100% {
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3),
                            0 0 0 0 rgba(255, 215, 0, 0.6);
            }
            50% {
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3),
                            0 0 0 15px rgba(255, 215, 0, 0);
            }
        }

        @keyframes progress-shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes mining-shake {
            0% { transform: rotate(0deg) translateZ(50px); }
            25% { transform: rotate(5deg) translateZ(50px); }
            50% { transform: rotate(0deg) translateZ(50px); }
            75% { transform: rotate(-5deg) translateZ(50px); }
            100% { transform: rotate(0deg) translateZ(50px); }
        }

        @keyframes lion-roar {
            0% { transform: scale(1) translateZ(50px); }
            20% { transform: scale(1.3) translateZ(50px); }
            40% { transform: scale(0.9) translateZ(50px); }
            60% { transform: scale(1.2) translateZ(50px); }
            80% { transform: scale(0.95) translateZ(50px); }
            100% { transform: scale(1) translateZ(50px); }
        }

        @keyframes coin-fly {
            0% {
                transform: translate(0, 0) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(var(--tx, 0), var(--ty, -100px)) scale(0.3);
                opacity: 0;
            }
        }

        .roar-text {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent);
            text-shadow: 0 0 15px rgba(255, 107, 53, 0.8);
            opacity: 0;
            z-index: 10;
            animation: roar-fade 1.5s forwards;
        }

        @keyframes roar-fade {
            0% { opacity: 0; transform: translateX(-50%) scale(0.5); }
            30% { opacity: 1; transform: translateX(-50%) scale(1.2); }
            70% { opacity: 1; transform: translateX(-50%) scale(1); }
            100% { opacity: 0; transform: translateX(-50%) translateY(-50px) scale(0.8); }
        }

        /* Mobile responsiveness */
        @media (max-width: 992px) {
            .mining-hero h1 {
                font-size: 2.8rem;
            }

            .mining-character {
                width: 240px;
                height: 240px;
            }

            .mining-lion {
                font-size: 12rem;
            }
        }

        @media (max-width: 768px) {
            .top-nav {
                padding: 1rem;
            }

            .mining-hero {
                padding: 80px 1rem 2rem;
            }

            .mining-hero h1 {
                font-size: 2.3rem;
            }

            .mining-hero p {
                font-size: 1.1rem;
            }

            .mining-character {
                width: 200px;
                height: 200px;
                margin: 1rem auto 1.8rem;
            }

            .mining-lion {
                font-size: 10rem;
            }

            .mining-progress-container {
                padding: 1.5rem;
            }

            .mining-controls {
                flex-direction: column;
                gap: 1rem;
                width: 100%;
                max-width: 400px;
            }

            .mining-btn {
                width: 100%;
                padding: 1rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .progress-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .progress-time {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 480px) {
            .mining-hero h1 {
                font-size: 2rem;
            }

            .mining-hero p {
                font-size: 1rem;
            }

            .mining-character {
                width: 180px;
                height: 180px;
            }

            .mining-lion {
                font-size: 8rem;
            }

            .section-title {
                font-size: 1.7rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="logo">
            <div class="coin-logo"></div>
            <span>RAWR</span>
        </div>

        <div class="wallet-balance">
            <div class="balance-item">
                <i class="fas fa-coins balance-icon"></i>
                <span class="balance-label">RAWR:</span>
                <span class="balance-value" id="rawrBalance"><?= number_format((float)$userData['rawr_balance'], 4) ?></span>
            </div>
            <div class="balance-item">
                <i class="fas fa-ticket-alt balance-icon"></i>
                <span class="balance-label">Tickets:</span>
                <span class="balance-value" id="ticketsBalance"><?= (int)$userData['ticket_balance'] ?></span>
            </div>
        </div>
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
    </nav>

  <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <a href="#" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-digging"></i>
            <span>Mining</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-dice"></i>
            <span>Casino</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Wallet</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-trophy"></i>
            <span>Leaderboard</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-gift"></i>
            <span>Daily Rewards</span>
        </a>
        <a href="#" class="sidebar-item active">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="logout.php" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </aside>

    <!-- Mining Hero Section -->
    <section class="mining-hero">
        <div class="hero-content">
            <h1>Mine RAWR Tokens</h1>
            <p>Your lion is hard at work digging for treasures! Collect rewards every hour and maximize your earnings with upgrades.</p>

            <div class="mining-character">
                <div class="character-container">
                    <div class="mining-lion" id="miningLion">🦁</div>
                </div>
            </div>

            <div class="mining-progress-container">
                <div class="progress-header">
                    <div class="progress-title"><i class="fas fa-hourglass-half"></i> Mining Progress</div>
                    <div class="progress-time"><i class="fas fa-clock"></i> Next reward in: <span id="timeLeft"><?= $isMining ? gmdate("i:s", $remainingTime) : gmdate("i:s", $miningDurationForJS) ?></span></div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="miningProgress" style="width: <?= $isMining ? (100 - ($remainingTime / $miningDurationForJS * 100)) : 0 ?>%"></div>
                </div>
                <div class="progress-stats">
                    <span>
                        <div><i class="fas fa-tachometer-alt"></i> Current rate:</div>
                        <div class="stat-value" id="miningRate"><?= number_format($miningRate, 2) ?> RAWR/hr</div>
                    </span>
                    <span>
                        <div><i class="fas fa-gem"></i> Next reward:</div>
                        <div class="stat-value" id="nextReward"><?= number_format($nextReward, 2) ?> RAWR</div>
                    </span>
                </div>
            </div>

            <div class="mining-controls">
                <button class="mining-btn <?= $isMining ? 'pulse' : '' ?>" id="startMining">
                    <i class="fas <?= $isMining ? 'fa-sync-alt fa-spin' : 'fa-play' ?>"></i>
                    <?= $isMining ? 'Mining...' : 'Start Mining' ?>
                </button>
                <button class="mining-btn secondary" id="claimReward" <?= (!$isMining || $remainingTime > 0) ? 'disabled' : '' ?>>
                    <i class="fas fa-gem"></i>
                    Claim Reward (<span id="rewardAmount"><?= number_format($nextReward, 2) ?></span> RAWR)
                </button>
            </div>
        </div>
    </section>

    <!-- Mining Stats Section -->
    <section class="mining-stats">
        <h2 class="section-title">
            <i class="fas fa-chart-line"></i>
            Mining Statistics
        </h2>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-coins"></i>
                </div>
                <div class="stat-title">Total Mined</div>
                <div class="stat-value" id="totalMined"><?= number_format($totalMined, 4) ?> RAWR</div>
                <div class="stat-subtext">Lifetime earnings</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="stat-title">Mining Rate</div>
                <div class="stat-value" id="currentRate"><?= number_format($miningRate, 2) ?> RAWR/hr</div>
                <div class="stat-subtext">With current equipment</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <div class="stat-title">Best Streak</div>
                <div class="stat-value" id="bestStreak"><?= number_format($bestStreak, 1) ?> RAWR</div>
                <div class="stat-subtext">In a single session</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="stat-title">Active Boosts</div>
                <div class="stat-value" id="activeBoosts">x<?= number_format($activeBoostsMultiplier, 1) ?></div>
                <div class="stat-subtext">Total multiplier</div>
            </div>
        </div>
    </section>

    <!-- Upgrade Section -->
    <section class="upgrade-section">
        <h2 class="section-title">
            <i class="fas fa-tools"></i>
            Mining Upgrades
        </h2>

        <div class="upgrade-grid">
            <div class="upgrade-card">
                <div class="upgrade-header">
                    <div class="upgrade-icon">
                        <i class="fas fa-shovel"></i>
                    </div>
                    <div>
                        <div class="upgrade-title">Stronger Shovel</div>
                        <div class="upgrade-level">Level <span id="shovelLevel"><?= $shovelLevel ?></span></div>
                    </div>
                </div>
                <div class="upgrade-description">
                    Increases your mining efficiency by 25% per level. Dig deeper and find more RAWR!
                </div>
                <div class="upgrade-cost">
                    <div class="cost-value">
                        <i class="fas fa-coins"></i>
                        <span id="shovelCost"><?= ceil(15 * pow(1.5, $shovelLevel - 1)) ?></span>
                    </div>
                    <button class="upgrade-btn" id="upgradeShovel">Upgrade</button>
                </div>
            </div>

            <div class="upgrade-card">
                <div class="upgrade-header">
                    <div class="upgrade-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <div class="upgrade-title">Energy Boost</div>
                        <div class="upgrade-level">Level <span id="energyLevel"><?= $energyLevel ?></span></div>
                    </div>
                </div>
                <div class="upgrade-description">
                    Reduces mining time by 10% per level. Mine faster and collect rewards more frequently!
                </div>
                <div class="upgrade-cost">
                    <div class="cost-value">
                        <i class="fas fa-coins"></i>
                        <span id="energyCost"><?= ceil(25 * pow(1.5, $energyLevel - 1)) ?></span>
                    </div>
                    <button class="upgrade-btn" id="upgradeEnergy">Upgrade</button>
                </div>
            </div>

            <div class="upgrade-card">
                <div class="upgrade-header">
                    <div class="upgrade-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <div class="upgrade-title">Royal Pickaxe</div>
                        <div class="upgrade-level">Level <span id="pickaxeLevel"><?= $pickaxeLevel ?></span></div>
                    </div>
                </div>
                <div class="upgrade-description">
                    Chance to find bonus RAWR with each mining session. Higher levels increase bonus chance.
                </div>
                <div class="upgrade-cost">
                    <div class="cost-value">
                        <i class="fas fa-coins"></i>
                        <span id="pickaxeCost"><?= ceil(50 * pow(1.5, $pickaxeLevel - 1)) ?></span>
                    </div>
                    <button class="upgrade-btn" id="upgradePickaxe">Upgrade</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Mining History Section -->
    <section class="mining-history">
        <div class="history-card">
            <div class="history-header">
                <h2 class="history-title">
                    <i class="fas fa-history"></i>
                    Mining History
                </h2>
                <button class="mining-btn secondary" id="clearHistory">
                    <i class="fas fa-trash"></i>
                    Clear
                </button>
            </div>
            <div class="history-list" id="historyList">
                <?php foreach ($history as $entry): ?>
                <div class="history-item">
                    <div class="history-icon">⛏️</div>
                    <div class="history-details">
                        <div class="history-description">Mined RAWR Tokens</div>
                        <div class="history-time"><?= date('M j, H:i', strtotime($entry['created_at'])) ?></div>
                    </div>
                    <div class="history-amount">+<?= number_format((float)$entry['amount'], 4) ?> RAWR</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-column">
                <h3>RAWR Casino</h3>
                <p>The ultimate play-to-earn experience in the jungle. Play, win, and earn your way to the top!</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-telegram"></i></a>
                    <a href="#"><i class="fab fa-reddit"></i></a>
                </div>
            </div>

            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Mining</a></li>
                    <li><a href="#">Casino</a></li>
                    <li><a href="#">Leaderboard</a></li>
                    <li><a href="#">Shop</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Resources</h3>
                <ul class="footer-links">
                    <li><a href="#">FAQs</a></li>
                    <li><a href="#">Tutorials</a></li>
                    <li><a href="#">Whitepaper</a></li>
                    <li><a href="#">Tokenomics</a></li>
                    <li><a href="#">Support</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Legal</h3>
                <ul class="footer-links">
                    <li><a href="#">Terms of Service</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Disclaimer</a></li>
                    <li><a href="#">AML Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="copyright">
            &copy; 2023 RAWR Casino. All rights reserved. The jungle is yours to conquer!
        </div>
    </footer>

    <script>
        // PHP data to JS
        const phpData = {
            rawrBalance: <?= (float)$userData['rawr_balance'] ?>,
            ticketsBalance: <?= (int)$userData['ticket_balance'] ?>,
            totalMined: <?= (float)$totalMined ?>,
            miningRate: <?= (float)$miningRate ?>,
            bestStreak: <?= (float)$bestStreak ?>, // This is not persistent currently
            activeBoosts: <?= (float)$activeBoostsMultiplier ?>,
            shovelLevel: <?= (int)$shovelLevel ?>,
            energyLevel: <?= (int)$energyLevel ?>,
            pickaxeLevel: <?= (int)$pickaxeLevel ?>,
            isMining: <?= $isMining ? 'true' : 'false' ?>,
            remainingTime: <?= (int)$remainingTime ?>,
            miningDuration: <?= (int)$miningDurationForJS ?>
        };

        // DOM Elements
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const miningLion = document.getElementById('miningLion');
        const startMiningBtn = document.getElementById('startMining');
        const claimRewardBtn = document.getElementById('claimReward');
        const miningProgress = document.getElementById('miningProgress');
        const timeLeft = document.getElementById('timeLeft');
        const historyList = document.getElementById('historyList');
        const clearHistoryBtn = document.getElementById('clearHistory');
        const rawrBalance = document.getElementById('rawrBalance');
        const ticketsBalance = document.getElementById('ticketsBalance');
        const totalMinedElement = document.getElementById('totalMined'); // Renamed to avoid conflict with phpData.totalMined
        const currentRateElement = document.getElementById('currentRate'); // Renamed
        const bestStreakElement = document.getElementById('bestStreak'); // Renamed
        const activeBoostsElement = document.getElementById('activeBoosts'); // Renamed
        const miningRateElement = document.getElementById('miningRate'); // Renamed
        const nextRewardElement = document.getElementById('nextReward'); // Renamed
        const rewardAmountElement = document.getElementById('rewardAmount'); // Renamed
        const upgradeShovelBtn = document.getElementById('upgradeShovel'); // Renamed
        const upgradeEnergyBtn = document.getElementById('upgradeEnergy'); // Renamed
        const upgradePickaxeBtn = document.getElementById('upgradePickaxe'); // Renamed
        const shovelCostElement = document.getElementById('shovelCost'); // Renamed
        const energyCostElement = document.getElementById('energyCost'); // Renamed
        const pickaxeCostElement = document.getElementById('pickaxeCost'); // Renamed
        const shovelLevelElement = document.getElementById('shovelLevel'); // Renamed
        const energyLevelElement = document.getElementById('energyLevel'); // Renamed
        const pickaxeLevelElement = document.getElementById('pickaxeLevel'); // Renamed

        // Mining state
        let isMining = phpData.isMining;
        let miningTimer;
        let secondsLeft = phpData.remainingTime;
        let miningDuration = phpData.miningDuration;
        let currentSessionStreak = 0; // Tracks streak within current client session
        let bestOverallStreak = phpData.bestStreak; // From PHP data, currently non-persistent
        let totalRawrMined = phpData.totalMined;
        let currentShovelLevel = phpData.shovelLevel;
        let currentEnergyLevel = phpData.energyLevel;
        let currentPickaxeLevel = phpData.pickaxeLevel;

        // Base mining rate from config, adjust if different in JS or calculate dynamically
        const BASE_MINING_REWARD_PER_HOUR = <?= MINING_BASE_REWARD * 12 ?>; // Assuming MINING_BASE_REWARD is per 5 mins, so *12 for hour

        // Menu Toggle for Mobile
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('active') &&
                !sidebar.contains(e.target) &&
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });

        // Format time for display
        function formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        }

        // Calculate current mining rate based on shovel level
        function calculateMiningRate(shovelLvl) {
            // This mirrors getMiningReward function from functions.php but scaled to hourly
            const base = <?= MINING_BASE_REWARD ?>; // Base per MINING_COOLDOWN (5 minutes)
            const multiplier = 1 + ((shovelLvl - 1) * 0.2); // Original was 0.2, check functions.php
            const rewardPerInterval = base * multiplier;
            // Convert to hourly rate: 3600 seconds / MINING_COOLDOWN seconds per interval
            return rewardPerInterval * (3600 / <?= MINING_COOLDOWN ?>);
        }

        // Calculate next reward amount
        function calculateNextReward() {
            const ratePerHour = calculateMiningRate(currentShovelLevel);
            // The next reward is based on the full mining duration, which might be reduced by energy level
            const actualMiningDurationHours = miningDuration / 3600;
            return ratePerHour * actualMiningDurationHours;
        }

        // Update UI elements with latest data
        function updateUI() {
            rawrBalance.textContent = phpData.rawrBalance.toFixed(4);
            ticketsBalance.textContent = phpData.ticketsBalance;
            totalMinedElement.textContent = `${totalRawrMined.toFixed(4)} RAWR`;

            const currentHourlyRate = calculateMiningRate(currentShovelLevel);
            miningRateElement.textContent = `${currentHourlyRate.toFixed(2)} RAWR/hr`;
            currentRateElement.textContent = `${currentHourlyRate.toFixed(2)} RAWR/hr`;

            const nextRewardVal = calculateNextReward();
            nextRewardElement.textContent = `${nextRewardVal.toFixed(2)} RAWR`;
            rewardAmountElement.textContent = nextRewardVal.toFixed(2);

            bestStreakElement.textContent = `${bestOverallStreak.toFixed(1)} RAWR`; // Still client-side based
            activeBoostsElement.textContent = `x${(1 + ((currentShovelLevel - 1) * 0.25)).toFixed(1)}`;

            shovelLevelElement.textContent = currentShovelLevel;
            energyLevelElement.textContent = currentEnergyLevel;
            pickaxeLevelElement.textContent = currentPickaxeLevel;

            // Update costs
            shovelCostElement.textContent = Math.ceil(15 * Math.pow(1.5, currentShovelLevel - 1));
            energyCostElement.textContent = Math.ceil(25 * Math.pow(1.5, currentEnergyLevel - 1));
            pickaxeCostElement.textContent = Math.ceil(50 * Math.pow(1.5, currentPickaxeLevel - 1));

            // Update button states
            startMiningBtn.innerHTML = isMining ? '<i class="fas fa-sync-alt fa-spin"></i> Mining...' : '<i class="fas fa-play"></i> Start Mining';
            startMiningBtn.classList.toggle('pulse', isMining);
            claimRewardBtn.disabled = !isMining || secondsLeft > 0;
            miningLion.classList.toggle('mining', isMining && secondsLeft > 0);
            miningLion.classList.toggle('idle', !isMining || secondsLeft === 0);
        }

        // Start mining function
        function startMining() {
            if (isMining) return; // Prevent starting if already mining

            fetch('mining.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=start_mining'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    isMining = true;
                    miningDuration = data.duration;
                    secondsLeft = data.duration; // Initialize secondsLeft with the new duration
                    timeLeft.textContent = formatTime(secondsLeft);

                    updateUI(); // Update UI immediately to reflect mining start

                    // Start the timer
                    miningTimer = setInterval(updateMiningProgress, 1000);
                } else {
                    // Use a custom modal or alert simulation for user feedback
                    displayMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error starting mining:', error);
                displayMessage('An unexpected error occurred while starting mining.', 'error');
            });
        }

        // Update mining progress
        function updateMiningProgress() {
            secondsLeft--;

            // Update time display and progress bar
            timeLeft.textContent = formatTime(secondsLeft);
            const progress = 100 - (secondsLeft / miningDuration) * 100;
            miningProgress.style.width = `${progress}%`;

            // When the mining session is complete
            if (secondsLeft <= 0) {
                clearInterval(miningTimer);
                miningProgress.classList.add('progress-glow'); // Add visual cue for completion
                updateUI(); // Update UI to enable claim button
            }
        }

        // Claim reward function
        function claimReward() {
            // Check if mining is complete before allowing claim
            if (!isMining || secondsLeft > 0) {
                displayMessage('Mining not complete or no active session.', 'error');
                return;
            }

            fetch('mining.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=claim_reward'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update frontend balances and stats
                    phpData.rawrBalance += data.reward;
                    totalRawrMined += data.reward;
                    currentSessionStreak++; // Increment client-side streak
                    // bestOverallStreak is not updated from backend, so it remains client-side for this demo.
                    // If best streak needs to be persisted, backend should return it or manage it.

                    createCoinAnimation(data.reward); // Visual feedback

                    // Add reward to history list
                    addHistoryEntry(data.reward);

                    // Reset mining state on frontend
                    resetMiningState();

                    // Show bonus if applicable
                    if (data.bonus > 0) {
                        displayMessage(`BONUS! +${data.bonus.toFixed(4)} RAWR`, 'success');
                    }
                    updateUI(); // Refresh all UI elements
                } else {
                    displayMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error claiming reward:', error);
                displayMessage('An unexpected error occurred while claiming reward.', 'error');
            });
        }

        // Helper to display messages (replaces alert)
        function displayMessage(message, type = 'info') {
            const messageElement = document.createElement('div');
            messageElement.classList.add('roar-text'); // Re-use existing styling
            messageElement.textContent = message;
            if (type === 'error') {
                messageElement.style.color = '#ff5555';
            } else if (type === 'success') {
                messageElement.style.color = '#00ff9d';
            }
            document.querySelector('.mining-character').appendChild(messageElement);

            setTimeout(() => {
                messageElement.remove();
            }, 2000);
        }

        // Create coin animation for reward claim
        function createCoinAnimation(reward) {
            const lionRect = miningLion.getBoundingClientRect();
            const balanceRect = document.querySelector('.balance-item').getBoundingClientRect();

            // Create coins based on reward amount, cap at 10 for performance
            const coinCount = Math.min(10, Math.floor(reward * 2)); // Adjust multiplier as needed

            for (let i = 0; i < coinCount; i++) {
                const coin = document.createElement('div');
                coin.classList.add('coin-animation');
                coin.innerHTML = '<i class="fas fa-coins"></i>';
                coin.style.position = 'fixed';
                coin.style.color = '#FFD700';
                coin.style.fontSize = '1.5rem';
                coin.style.zIndex = '1000';
                // Start position near the lion
                coin.style.left = `${lionRect.left + lionRect.width / 2}px`;
                coin.style.top = `${lionRect.top + lionRect.height / 2}px`;

                document.body.appendChild(coin);

                // Random offset for end position near the balance
                const offsetX = (Math.random() - 0.5) * 40;
                const offsetY = (Math.random() - 0.5) * 40;

                // Animate coin
                const animation = coin.animate([
                    {
                        transform: 'translate(0, 0) scale(1)',
                        opacity: 1
                    },
                    {
                        // Target balance position relative to coin's initial position
                        transform: `translate(${balanceRect.left - lionRect.left + offsetX}px, ${balanceRect.top - lionRect.top + offsetY}px) scale(0.3)`,
                        opacity: 0
                    }
                ], {
                    duration: 800 + Math.random() * 400, // Vary duration slightly
                    easing: 'ease-out',
                    fill: 'forwards' // Keep the final state
                });

                // Remove coin after animation
                animation.onfinish = () => coin.remove();
            }
        }

        // Add an entry to the history list
        function addHistoryEntry(amount) {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const dateString = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

            const historyItem = document.createElement('div');
            historyItem.classList.add('history-item');
            historyItem.innerHTML = `
                <div class="history-icon">⛏️</div>
                <div class="history-details">
                    <div class="history-description">Mined RAWR Tokens</div>
                    <div class="history-time">${dateString}, ${timeString}</div>
                </div>
                <div class="history-amount">+${amount.toFixed(4)} RAWR</div>
            `;
            // Add to the top of the list
            historyList.insertBefore(historyItem, historyList.firstChild);
        }


        // Reset mining state
        function resetMiningState() {
            clearInterval(miningTimer);
            isMining = false;
            secondsLeft = miningDuration; // Reset to full duration for next session
            miningProgress.style.width = '0%';
            miningProgress.classList.remove('progress-glow');
            updateUI(); // Update UI to reflect reset
        }

        // Lion roar animation
        function triggerRoar() {
            // Add roar animation class
            miningLion.classList.remove('idle', 'mining');
            miningLion.classList.add('roar');

            // Create roar text element
            const roarText = document.createElement('div');
            roarText.classList.add('roar-text');
            roarText.textContent = 'ROAR!';
            document.querySelector('.mining-character').appendChild(roarText);

            // Remove roar animation class after animation completes
            setTimeout(() => {
                miningLion.classList.remove('roar');
                // Restore previous state based on mining status
                miningLion.classList.toggle('mining', isMining && secondsLeft > 0);
                miningLion.classList.toggle('idle', !isMining || secondsLeft === 0);

                // Remove roar text after animation
                setTimeout(() => {
                    roarText.remove();
                }, 1500);
            }, 800);
        }

        // Upgrade equipment
        function upgradeEquipment(type) {
            fetch('mining.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=upgrade_equipment&type=${type}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update frontend levels and balance
                    phpData.rawrBalance = data.new_balance;

                    switch(type) {
                        case 'shovel':
                            currentShovelLevel = data.new_level;
                            break;
                        case 'energy':
                            currentEnergyLevel = data.new_level;
                            // Recalculate miningDuration based on new energy level
                            // This mirrors PHP's calculation for energy reduction
                            miningDuration = Math.max(10, 3600 * (1 - ((currentEnergyLevel - 1) * 0.1)));
                            // If mining is not active, set timeLeft to new full duration
                            if (!isMining) {
                                secondsLeft = miningDuration;
                            }
                            break;
                        case 'pickaxe':
                            currentPickaxeLevel = data.new_level;
                            break;
                    }

                    updateUI(); // Refresh all UI elements

                    // Show success message
                    displayMessage('UPGRADED!', 'success');
                } else {
                    // Show error message
                    displayMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error upgrading equipment:', error);
                displayMessage('An unexpected error occurred during upgrade.', 'error');
            });
        }

        // Event listeners
        startMiningBtn.addEventListener('click', startMining);
        claimRewardBtn.addEventListener('click', claimReward);
        miningLion.addEventListener('click', triggerRoar);
        upgradeShovelBtn.addEventListener('click', () => upgradeEquipment('shovel'));
        upgradeEnergyBtn.addEventListener('click', () => upgradeEquipment('energy'));
        upgradePickaxeBtn.addEventListener('click', () => upgradeEquipment('pickaxe'));

        clearHistoryBtn.addEventListener('click', () => {
            historyList.innerHTML = '';
            displayMessage('Mining history cleared!', 'info');
        });

        // Initialize UI on page load
        updateUI();

        // Start mining timer if session is active from PHP
        if (isMining) {
            miningTimer = setInterval(updateMiningProgress, 1000);
        }
    </script>
</body>
</html>
