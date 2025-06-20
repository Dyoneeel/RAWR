<?php
require_once __DIR__ . '/../backend/inc/init.php';
userOnly(); // Ensure only logged-in users can access this page

$db = Database::getInstance();
$userId = $_SESSION['user_id'];

// --- Fetch User Data and Related Information ---
$user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Handle if user is not found (shouldn't happen with userOnly(), but good practice)
if (!$user) {
    redirect('/login.php'); // Redirect to login if user data can't be fetched
}

// Fetch mining related data
$miningData = $db->fetchOne("SELECT * FROM mining_data WHERE user_id = ?", [$userId]);
$miningUpgrades = $db->fetchOne("SELECT * FROM mining_upgrades WHERE user_id = ?", [$userId]);

// Initialize mining_data and mining_upgrades if they don't exist
$db->beginTransaction();
try {
    if (!$miningData) {
        $db->insert("mining_data", ['user_id' => $userId, 'total_mined' => 0.0, 'boost_level' => 1]);
        $miningData = $db->fetchOne("SELECT * FROM mining_data WHERE user_id = ?", [$userId]); // Re-fetch after insert
    }
    if (!$miningUpgrades) {
        $db->insert("mining_upgrades", ['user_id' => $userId, 'shovel_level' => 1, 'energy_level' => 1, 'pickaxe_level' => 1]);
        $miningUpgrades = $db->fetchOne("SELECT * FROM mining_upgrades WHERE user_id = ?", [$userId]); // Re-fetch after insert
    }
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    error_log("Error initializing mining data/upgrades for user {$userId}: " . $e->getMessage());
    // Optionally, inform the user or redirect
}

// Fetch login streak data
$loginStreak = $db->fetchOne("SELECT * FROM login_streaks WHERE user_id = ?", [$userId]);
// If no streak data, provide defaults
$currentStreak = $loginStreak['current_streak'] ?? 0;
$longestStreak = $loginStreak['longest_streak'] ?? 0;

// Calculate mining boost/multiplier for display on profile
$shovelLevel = $miningUpgrades['shovel_level'] ?? 1;
$miningMultiplier = 1 + (($shovelLevel - 1) * 0.25); // Using 0.25 as per mining.php's assumed shovel boost per level

// --- Handle POST requests for profile updates and KYC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitizeInput($_POST['action'] ?? '');

    switch ($action) {
        case 'update_profile':
            $username = sanitizeInput($_POST['username'] ?? '');
            // For now, email is disabled, so not updated
            // $email = sanitizeInput($_POST['email'] ?? '');
            $bio = sanitizeInput($_POST['bio'] ?? '');

            // Basic validation
            if (empty($username)) {
                jsonResponse(['status' => 'error', 'message' => 'Username cannot be empty.'], 400);
            }

            // Check if username already exists for another user
            $existingUser = $db->fetchOne("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $userId]);
            if ($existingUser) {
                jsonResponse(['status' => 'error', 'message' => 'Username already taken.'], 409);
            }

            try {
                $db->update(
                    "users",
                    ['username' => $username], // Add 'bio' if a column exists for it in 'users' table
                    "id = ?",
                    [$userId]
                );
                jsonResponse(['status' => 'success', 'message' => 'Profile updated successfully!', 'new_username' => $username]);
            } catch (Exception $e) {
                error_log("Profile update failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to update profile. Database error.'], 500);
            }

        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmNewPassword = $_POST['confirm_new_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmNewPassword)) {
                jsonResponse(['status' => 'error', 'message' => 'All password fields are required.'], 400);
            }
            if ($newPassword !== $confirmNewPassword) {
                jsonResponse(['status' => 'error', 'message' => 'New password and confirmation do not match.'], 400);
            }
            if (strlen($newPassword) < 8) { // Example: minimum password length
                jsonResponse(['status' => 'error', 'message' => 'New password must be at least 8 characters long.'], 400);
            }

            // Verify current password
            if (!verifyPassword($currentPassword, $user['password_hash'])) {
                jsonResponse(['status' => 'error', 'message' => 'Incorrect current password.'], 401);
            }

            try {
                $newPasswordHash = passwordHash($newPassword);
                $db->update("users", ['password_hash' => $newPasswordHash], "id = ?", [$userId]);
                jsonResponse(['status' => 'success', 'message' => 'Password changed successfully!']);
            } catch (Exception $e) {
                error_log("Password change failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to change password. Database error.'], 500);
            }

        // --- KYC related actions (placeholders for now, requires actual file handling and admin review) ---
        case 'save_personal_info':
            $fullName = sanitizeInput($_POST['fullName'] ?? '');
            $dob = sanitizeInput($_POST['dob'] ?? '');
            $country = sanitizeInput($_POST['country'] ?? '');
            $contactNumber = sanitizeInput($_POST['contactNumber'] ?? '');
            $address = sanitizeInput($_POST['address'] ?? '');
            $city = sanitizeInput($_POST['city'] ?? '');
            $stateProvince = sanitizeInput($_POST['stateProvince'] ?? '');
            $postalCode = sanitizeInput($_POST['postalCode'] ?? '');

            // In a real application, you would update/insert into a kyc_requests table
            // For now, we'll just update the user's kyc_status to 'pending' if it's not already 'approved'
            try {
                if ($user['kyc_status'] !== 'approved') {
                    // This is a simplified update. A real KYC system would save all details.
                    $db->update("users", ['kyc_status' => 'pending'], "id = ?", [$userId]);
                    // You might also log this action or store these details in a kyc_requests table
                }
                jsonResponse(['status' => 'success', 'message' => 'Personal information saved.', 'kyc_status' => 'pending']);
            } catch (Exception $e) {
                error_log("Save personal info failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to save personal information.'], 500);
            }

        case 'send_email_code':
            // This would involve sending an actual email with a unique code
            // For now, simulate success
            // In a real app, you'd store the code in the DB with an expiry and associate with user
            jsonResponse(['status' => 'success', 'message' => 'Verification code sent to your email.']);

        case 'verify_email_code':
            // In a real app, you'd verify the code against a stored one
            // For now, simulate success
            // If verification is successful, update a 'email_verified' status for the user
            jsonResponse(['status' => 'success', 'message' => 'Email verified successfully.']);

        case 'connect_wallet':
            $walletAddress = sanitizeInput($_POST['walletAddress'] ?? '');
            if (empty($walletAddress)) {
                jsonResponse(['status' => 'error', 'message' => 'Wallet address cannot be empty.'], 400);
            }
            // In a real app, you'd store this wallet address in the users table
            try {
                $db->update("users", ['wallet_address' => $walletAddress], "id = ?", [$userId]); // Assuming 'wallet_address' column
                jsonResponse(['status' => 'success', 'message' => 'Wallet connected successfully.']);
            } catch (Exception $e) {
                error_log("Wallet connection failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to connect wallet.'], 500);
            }

        case 'submit_kyc_document':
            // This is a placeholder for file upload handling.
            // Actual file uploads would require $_FILES and careful security checks.
            // For now, just simulate success and update KYC status if not already approved.
            try {
                if ($user['kyc_status'] !== 'approved') {
                    $db->update("users", ['kyc_status' => 'pending'], "id = ?", [$userId]);
                }
                jsonResponse(['status' => 'success', 'message' => 'ID document submitted.']);
            } catch (Exception $e) {
                error_log("ID document submission failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to submit ID document.'], 500);
            }

        case 'submit_selfie':
            // Placeholder for selfie file upload
            try {
                if ($user['kyc_status'] !== 'approved') {
                    $db->update("users", ['kyc_status' => 'pending'], "id = ?", [$userId]);
                }
                jsonResponse(['status' => 'success', 'message' => 'Selfie submitted.']);
            } catch (Exception $e) {
                error_log("Selfie submission failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to submit selfie.'], 500);
            }

        case 'final_kyc_submit':
            // This is the final step of KYC submission, which changes status to 'pending'
            // and triggers backend review process (not implemented here)
            if ($user['kyc_status'] === 'approved') {
                jsonResponse(['status' => 'error', 'message' => 'KYC already approved.'], 400);
            }
            try {
                $db->update("users", ['kyc_status' => 'pending'], "id = ?", [$userId]);
                jsonResponse(['status' => 'success', 'message' => 'KYC application submitted for review.']);
            } catch (Exception $e) {
                error_log("Final KYC submission failed for user {$userId}: " . $e->getMessage());
                jsonResponse(['status' => 'error', 'message' => 'Failed to submit KYC application.'], 500);
            }
    }
    exit; // Exit after handling AJAX requests
}

// --- Fetch Game Statistics ---
// Define game type IDs based on your `game_types` table in rawr_casino (6).sql
// Example: 3 for Slot Machine, 4 for Russian Roulette, 2 for Card Flip, 5 for Jungle Jackpot
$gameStats = [
    'slots' => $db->fetchOne("SELECT COUNT(*) AS wins FROM game_results WHERE user_id = ? AND game_type_id = 3 AND outcome = 'win'", [$userId])['wins'] ?? 0,
    'roulette' => $db->fetchOne("SELECT COUNT(*) AS wins FROM game_results WHERE user_id = ? AND game_type_id = 4 AND outcome = 'win'", [$userId])['wins'] ?? 0,
    'cards' => $db->fetchOne("SELECT COUNT(*) AS wins FROM game_results WHERE user_id = ? AND game_type_id = 2 AND outcome = 'win'", [$userId])['wins'] ?? 0,
    'jackpot' => $db->fetchOne("SELECT COUNT(*) AS wins FROM game_results WHERE user_id = ? AND game_type_id = 5 AND outcome = 'win'", [$userId])['wins'] ?? 0
];
$totalGameWins = $gameStats['slots'] + $gameStats['roulette'] + $gameStats['cards'] + $gameStats['jackpot'];

// --- Leaderboard Rank Calculation ---
// This calculates rank based on rawr_balance.
$rank = $db->fetchOne("SELECT COUNT(*) + 1 AS rank FROM users WHERE rawr_balance > ?", [$user['rawr_balance']])['rank'] ?? 1;

// --- KYC Status for Frontend ---
// Prepare KYC data for JavaScript to determine progress and state
$kycData = $db->fetchOne("SELECT * FROM kyc_requests WHERE user_id = ?", [$userId]);
// Simplified `kycSteps` status for JS. In a real app, this would be more detailed.
$kycProgressStatus = [
    'personalInfo' => !empty($kycData['full_name']) && !empty($kycData['id_image_path']), // Assuming these imply personal info is submitted
    'emailVerified' => true, // Assuming email is verified upon registration for now, or add a column in users table
    'walletConnected' => !empty($user['wallet_address']), // Assuming a 'wallet_address' column in users table
    'idDocument' => !empty($kycData['id_image_path']) && ($kycData['status'] !== 'rejected'), // Assuming image path implies submission
    'selfie' => !empty($kycData['selfie_image_path']) && ($kycData['status'] !== 'rejected') // Assuming 'selfie_image_path' in kyc_requests
];

// Determine overall KYC progress for the bar
$completedKycStepsCount = 0;
foreach ($kycProgressStatus as $stepCompleted) {
    if ($stepCompleted) {
        $completedKycStepsCount++;
    }
}
$kycOverallProgress = ($completedKycStepsCount / 5) * 100; // 5 steps in the example

// Override progress if KYC is approved/rejected in the main `users` table
if ($user['kyc_status'] === 'approved') {
    $kycOverallProgress = 100;
} elseif ($user['kyc_status'] === 'rejected') {
    $kycOverallProgress = 0; // Or a specific value to indicate rejection
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAWR Casino - My Profile</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FFD700;
            --secondary: #FFA500;
            --accent: #FF6347;
            --dark: #1a1a1a;
            --darker: #121212;
            --text-light: #ffffff;
            --text-muted: #cccccc;
            --card-bg: rgba(30, 30, 30, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
            --border-radius: 12px;
            --transition: all 0.3s ease;
            --glow: 0 0 15px rgba(255, 215, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0c2d48, #1a1a1a);
            color: var(--text-light);
            min-height: 100vh;
            position: relative;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none" stroke="rgba(255,215,0,0.05)" stroke-width="0.5"/></svg>');
            z-index: -1;
            opacity: 0.3;
        }

        /* Top Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(20, 20, 20, 0.9);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .coin-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .wallet-balance {
            display: flex;
            gap: 1rem;
        }

        .balance-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            border: 1px solid var(--glass-border);
        }

        .balance-icon {
            color: var(--primary);
        }

        .balance-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .balance-value {
            font-weight: 600;
            color: var(--primary);
        }

        .menu-toggle {
            background: transparent;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: fixed;
            top: 70px;
            left: 0;
            width: 250px;
            height: calc(100vh - 70px);
            background: rgba(20, 20, 20, 0.9);
            backdrop-filter: blur(10px);
            border-right: 1px solid rgba(255, 215, 0, 0.2);
            padding: 1.5rem 0;
            z-index: 999;
            transform: translateX(-100%);
            transition: var(--transition);
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 1.5rem;
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
        }

        .sidebar-item:hover, .sidebar-item.active {
            background: rgba(255, 215, 0, 0.1);
            color: var(--primary);
            border-left: 3px solid var(--primary);
        }

        .sidebar-item i {
            width: 25px;
            text-align: center;
        }

        /* Profile Header - Updated */
        .profile-header {
            padding: 120px 1rem 60px;
            position: relative;
            text-align: center;
            background: linear-gradient(135deg, rgba(26, 26, 26, 0.8), rgba(45, 24, 16, 0.8));
            border-bottom: 1px solid rgba(255, 215, 0, 0.2);
            margin-bottom: 2rem;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
            margin: 0 auto 20px;
        }

        .profile-avatar {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            border: 3px solid var(--primary);
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            overflow: hidden;
        }

        .edit-avatar-btn {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 35px;
            height: 35px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .user-info {
            margin-top: 1rem;
        }

        .user-name {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .user-email {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }

        .member-since {
            color: var(--text-muted);
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(0, 128, 0, 0.2);
            color: #0f0;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            margin-top: 0.5rem;
            font-size: 0.8rem;
            font-weight: 500;
        }

        /* Profile Content */
        .profile-content {
            padding: 0 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 215, 0, 0.1);
            padding-bottom: 1rem;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: transparent;
            border: none;
            padding: 0.7rem 1.5rem;
            color: var(--text-muted);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            border-radius: 30px;
            font-size: 0.85rem;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #1a1a1a;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3), var(--glow);
        }

        .stat-icon {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .stat-title {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }

        /* Settings Form */
        .settings-form {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--glass-border);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 1rem;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            color: var(--text-light);
            font-size: 1rem;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #1a1a1a;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 215, 0, 0.4);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-outline:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        /* KYC Verification - Updated */
        .kyc-section {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--glass-border);
        }

        .kyc-status {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }

        .kyc-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .kyc-info {
            flex: 1;
        }

        .kyc-title {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }

        .kyc-description {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .kyc-steps {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .kyc-step {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .step-number {
            width: 35px;
            height: 35px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: var(--primary);
            flex-shrink: 0;
        }

        .step-content {
            flex: 1;
        }

        .step-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .step-description {
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        .upload-area {
            border: 2px dashed var(--glass-border);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            margin: 1rem 0;
            cursor: pointer;
            transition: var(--transition);
        }

        .upload-area:hover {
            border-color: var(--primary);
            background: rgba(255, 215, 0, 0.05);
        }

        .upload-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .upload-text {
            margin-bottom: 1rem;
        }

        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .file-item {
            width: 120px;
            height: 120px;
            border-radius: var(--border-radius);
            overflow: hidden;
            position: relative;
            border: 1px solid var(--glass-border);
        }

        .file-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 25px;
            height: 25px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .kyc-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .kyc-progress {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.3);
            border-radius: var(--border-radius);
            flex-wrap: wrap;
        }

        .progress-bar {
            flex: 1;
            height: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 5px;
            overflow: hidden;
            min-width: 150px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 5px;
            width: 0%;
            transition: width 0.5s ease;
        }

        /* Verification Code */
        .verification-code {
            display: flex;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .code-input {
            width: 45px;
            height: 45px;
            text-align: center;
            font-size: 1.2rem;
            border-radius: 8px;
            border: 1px solid var(--glass-border);
            background: rgba(0, 0, 0, 0.3);
            color: var(--text-light);
        }

        /* Wallet Connection */
        .wallet-connection {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius);
            border: 1px solid var(--glass-border);
            text-align: center;
            margin: 1rem 0;
        }

        .wallet-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            overflow-y: auto;
            padding: 2rem;
        }

        .modal-content {
            background: var(--card-bg);
            max-width: 800px;
            margin: 2rem auto;
            border-radius: var(--border-radius);
            border: 1px solid var(--primary);
            overflow: hidden;
            position: relative;
        }

        .modal-header {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid var(--glass-border);
        }

        .modal-title {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        .review-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--glass-border);
        }

        .review-title {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .review-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .review-item {
            margin-bottom: 0.5rem;
        }

        .review-label {
            font-weight: 500;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .review-value {
            font-weight: 600;
        }

        .review-images {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .review-image {
            width: 150px;
            height: 150px;
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }

        .review-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .agreement {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius);
        }

        .modal-footer {
            padding: 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            border-top: 1px solid var(--glass-border);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
        }

        /* Footer */
        footer {
            background: rgba(15, 15, 15, 0.9);
            padding: 2rem 1rem;
            text-align: center;
            border-top: 1px solid rgba(255, 215, 0, 0.1);
            margin-top: 3rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            text-align: left;
        }

        .footer-column h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 0.5rem;
        }

        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.8rem;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .social-links {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 50%;
            color: var(--primary);
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .social-links a:hover {
            background: var(--primary);
            color: #1a1a1a;
            transform: translateY(-3px);
        }

        .copyright {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Mobile Styles */
        @media (max-width: 576px) {
            .top-nav {
                flex-wrap: wrap;
                padding: 0.5rem;
            }

            .logo {
                font-size: 1.2rem;
            }

            .coin-logo {
                width: 30px;
                height: 30px;
            }

            .wallet-balance {
                order: 3;
                width: 100%;
                justify-content: center;
                margin-top: 0.5rem;
            }

            .profile-header {
                padding: 80px 1rem 30px;
            }

            .profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }

            .user-name {
                font-size: 1.5rem;
            }

            .tabs {
                gap: 0.3rem;
            }

            .tab-btn {
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .verification-code {
                flex-wrap: wrap;
                justify-content: center;
            }

            .kyc-step {
                padding: 1rem;
            }

            .kyc-actions {
                flex-direction: column;
            }

            .menu-toggle {
                display: block;
            }
        }

        /* Tablet Styles */
        @media (min-width: 576px) {
            .profile-header {
                padding: 100px 1rem 50px;
            }

            .profile-avatar {
                width: 140px;
                height: 140px;
            }
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            .top-nav {
                padding: 1rem 2rem;
            }

            .logo {
                font-size: 1.8rem;
            }

            .coin-logo {
                width: 45px;
                height: 45px;
            }

            .wallet-balance {
                gap: 1rem;
            }

            .balance-item {
                padding: 0.5rem 1rem;
            }

            .balance-label, .balance-value {
                font-size: 0.9rem;
            }

            .profile-header {
                padding: 120px 2rem 60px;
            }

            .profile-avatar {
                width: 160px;
                height: 160px;
            }

            .user-name {
                font-size: 2.2rem;
            }

            .kyc-steps {
                flex-direction: row;
                flex-wrap: wrap;
            }

            .kyc-step {
                flex: 1;
                min-width: 300px;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="logo">
            <div class="coin-logo">🦁</div>
            <span>RAWR</span>
        </div>

        <div class="nav-actions">
            <div class="wallet-balance">
                <div class="balance-item">
                    <i class="fas fa-coins balance-icon"></i>
                    <span class="balance-label">RAWR:</span>
                    <span class="balance-value" id="rawrBalance"><?= number_format((float)$user['rawr_balance'], 2) ?></span>
                </div>
                <div class="balance-item">
                    <i class="fas fa-ticket-alt balance-icon"></i>
                    <span class="balance-label">Tickets:</span>
                    <span class="balance-value" id="ticketsBalance"><?= (int)$user['ticket_balance'] ?></span>
                </div>
            </div>
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <a href="#" class="sidebar-item">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-digging"></i>
            <span>Mining ⛏️</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-dice"></i>
            <span>Lobby 🎰</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-shopping-cart"></i>
            <span>Shop 🛍️</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-trophy"></i>
            <span>Leaderboard 🏆</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-gift"></i>
            <span>Daily Rewards 🎁</span>
        </a>
        <a href="#" class="sidebar-item active">
            <i class="fas fa-user"></i>
            <span>Profile 👤</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-cog"></i>
            <span>Settings ⚙️</span>
        </a>
        <a href="#" class="sidebar-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </aside>

    <!-- Profile Header -->
    <section class="profile-header">
        <div class="avatar-container">
            <div class="profile-avatar" id="profileAvatar">
                <span>🦁</span>
            </div>
            <div class="edit-avatar-btn" id="editAvatarBtn">
                <i class="fas fa-pencil-alt"></i>
            </div>
        </div>

        <div class="user-info">
            <h1 class="user-name" id="profileUsername"><?= htmlspecialchars($user['username']) ?></h1>
            <div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
            <div class="member-since">Member since: <?= date('M Y', strtotime($user['created_at'])) ?></div>
            <?php if($user['kyc_status'] === 'approved'): ?>
            <div class="verification-badge">
                <i class="fas fa-shield-alt"></i> Verified Account
            </div>
            <?php elseif($user['kyc_status'] === 'pending'): ?>
            <div class="verification-badge" style="background: rgba(255, 165, 0, 0.2); color: #FFA500;">
                <i class="fas fa-hourglass-half"></i> KYC Pending
            </div>
            <?php else: // 'rejected' or 'not_submitted' ?>
            <div class="verification-badge" style="background: rgba(255, 0, 0, 0.2); color: #FF6347;">
                <i class="fas fa-times-circle"></i> KYC Not Verified
            </div>
            <?php endif; ?>
             <div class="referral-code-display" style="margin-top: 1rem; font-size: 0.9rem; color: var(--text-muted);">
                Your Referral Code: <span style="font-weight: 600; color: var(--primary);"><?= htmlspecialchars($user['referral_code']) ?></span>
            </div>
        </div>
    </section>

    <!-- Profile Content -->
    <section class="profile-content">
        <div class="tabs">
            <button class="tab-btn active" data-tab="overview">Overview</button>
            <button class="tab-btn" data-tab="settings">Settings</button>
            <button class="tab-btn" data-tab="kyc">KYC Verification</button>
        </div>

        <!-- Overview Tab -->
        <div class="tab-content active" id="overviewTab">
            <h2 class="section-title">
                <i class="fas fa-chart-line"></i>
                My Stats
            </h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="stat-title">Total RAWR Balance</div>
                    <div class="stat-value" id="overviewRawrBalance"><?= number_format((float)$user['rawr_balance'], 2) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-title">Total Tickets</div>
                    <div class="stat-value" id="overviewTicketsBalance"><?= (int)$user['ticket_balance'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-title">Leaderboard Rank</div>
                    <div class="stat-value">#<span id="overviewRank"><?= (int)$rank ?></span></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <div class="stat-title">Total Wins</div>
                    <div class="stat-value" id="overviewTotalWins"><?= (int)$totalGameWins ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-title">Current Login Streak</div>
                    <div class="stat-value" id="currentLoginStreak"><?= (int)$currentStreak ?> Days</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="stat-title">Longest Login Streak</div>
                    <div class="stat-value" id="longestLoginStreak"><?= (int)$longestStreak ?> Days</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-mining"></i>
                    </div>
                    <div class="stat-title">Total RAWR Mined</div>
                    <div class="stat-value" id="totalRawrMined"><?= number_format((float)$miningData['total_mined'] ?? 0, 4) ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="stat-title">Mining Boost Level</div>
                    <div class="stat-value" id="miningBoostLevel">x<?= number_format($miningMultiplier, 2) ?></div>
                </div>
            </div>

            <h2 class="section-title">
                <i class="fas fa-gamepad"></i>
                Game Statistics
            </h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dice"></i>
                    </div>
                    <div class="stat-title">Slot Wins</div>
                    <div class="stat-value"><?= (int)$gameStats['slots'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-circle"></i>
                    </div>
                    <div class="stat-title">Roulette Wins</div>
                    <div class="stat-value"><?= (int)$gameStats['roulette'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-cards"></i>
                    </div>
                    <div class="stat-title">Card Wins</div>
                    <div class="stat-value"><?= (int)$gameStats['cards'] ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-paw"></i>
                    </div>
                    <div class="stat-title">Jungle Jackpot Wins</div>
                    <div class="stat-value"><?= (int)$gameStats['jackpot'] ?></div>
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div class="tab-content" id="settingsTab">
            <h2 class="section-title">
                <i class="fas fa-user-cog"></i>
                Profile Settings
            </h2>

            <div class="settings-form">
                <div class="form-group">
                    <label class="form-label">Profile Picture</label>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div class="profile-avatar" style="width: 80px; height: 80px; font-size: 2rem;">🦁</div>
                        <button class="btn btn-outline" id="uploadAvatarBtn">
                            <i class="fas fa-upload"></i> Upload New Avatar
                        </button>
                         <input type="file" id="avatarFileInput" accept="image/*" style="display: none;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="settingsUsername">Username</label>
                        <input type="text" class="form-input" id="settingsUsername" value="<?= htmlspecialchars($user['username']) ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="settingsEmail">Email Address</label>
                        <input type="email" class="form-input" id="settingsEmail" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="settingsBio">Bio</label>
                    <textarea class="form-input" id="settingsBio" rows="3"><?= htmlspecialchars($user['bio'] ?? 'RAWR Casino enthusiast!') ?></textarea>
                </div>

                <button class="btn" id="saveProfileBtn">
                    <i class="fas fa-save"></i> Save Changes
                </button>


                <h3 class="section-title" style="font-size: 1.2rem; margin: 2rem 0 1rem;">
                    <i class="fas fa-shield-alt"></i>
                    Security Settings
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="currentPassword">Current Password</label>
                        <input type="password" class="form-input" id="currentPassword" placeholder="Enter current password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="newPassword">New Password</label>
                        <input type="password" class="form-input" id="newPassword" placeholder="Enter new password">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirmNewPassword">Confirm New Password</label>
                    <input type="password" class="form-input" id="confirmNewPassword" placeholder="Confirm new password">
                </div>

                <button class="btn" id="changePasswordBtn">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
        </div>

        <!-- KYC Verification Tab - Updated -->
        <div class="tab-content" id="kycTab">
            <div class="kyc-section">
                <div class="kyc-status">
                    <div class="kyc-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="kyc-info">
                        <div class="kyc-title">Identity Verification (KYC)</div>
                        <div class="kyc-description">Complete verification to unlock all features and higher withdrawal limits</div>
                    </div>
                </div>

                <div class="kyc-progress">
                    <div>Verification Progress:</div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="kycProgress" style="width: <?= $kycOverallProgress ?>%"></div>
                    </div>
                    <div id="progressPercentage"><?= round($kycOverallProgress) ?>%</div>
                </div>

                <div class="kyc-steps">
                    <!-- Step 1: Personal Information -->
                    <div class="kyc-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <div class="step-title">Personal Information</div>
                            <div class="step-description">Provide your full name, date of birth, and residential address</div>

                            <div class="form-group">
                                <label class="form-label" for="fullName">Full Name</label>
                                <input type="text" class="form-input" id="fullName" placeholder="As shown on your ID" value="<?= htmlspecialchars($kycData['full_name'] ?? '') ?>">
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="dob">Date of Birth</label>
                                    <input type="date" class="form-input" id="dob" value="<?= htmlspecialchars($kycData['date_of_birth'] ?? '') ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="country">Country</label>
                                    <select class="form-input" id="country">
                                        <option value="">Select Country</option>
                                        <option value="us" <?= (isset($kycData['country']) && $kycData['country'] === 'us') ? 'selected' : '' ?>>United States</option>
                                        <option value="uk" <?= (isset($kycData['country']) && $kycData['country'] === 'uk') ? 'selected' : '' ?>>United Kingdom</option>
                                        <option value="ca" <?= (isset($kycData['country']) && $kycData['country'] === 'ca') ? 'selected' : '' ?>>Canada</option>
                                        <option value="au" <?= (isset($kycData['country']) && $kycData['country'] === 'au') ? 'selected' : '' ?>>Australia</option>
                                        <!-- Add more countries as needed -->
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="contactNumber">Contact Number</label>
                                <input type="tel" class="form-input" id="contactNumber" placeholder="+1 (123) 456-7890" value="<?= htmlspecialchars($kycData['contact_number'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="address">Residential Address</label>
                                <input type="text" class="form-input" id="address" placeholder="Street Address" value="<?= htmlspecialchars($kycData['address'] ?? '') ?>">
                                <input type="text" class="form-input" id="city" style="margin-top: 0.5rem;" placeholder="City" value="<?= htmlspecialchars($kycData['city'] ?? '') ?>">
                                <div class="form-row">
                                    <input type="text" class="form-input" id="stateProvince" placeholder="State/Province" value="<?= htmlspecialchars($kycData['state_province'] ?? '') ?>">
                                    <input type="text" class="form-input" id="postalCode" placeholder="Postal Code" value="<?= htmlspecialchars($kycData['postal_code'] ?? '') ?>">
                                </div>
                            </div>

                            <button class="btn" id="savePersonalInfoBtn">
                                <i class="fas fa-save"></i> Save Information
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: Email Verification -->
                    <div class="kyc-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <div class="step-title">Email Verification</div>
                            <div class="step-description">Verify your email address to secure your account</div>

                            <div class="form-group">
                                <label class="form-label" for="verifyEmail">Email Address</label>
                                <input type="email" class="form-input" id="verifyEmail" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            </div>
                            <?php if (!($kycData['email_verified'] ?? false)): // Check if email is not yet verified in KYC data ?>
                            <button class="btn" id="sendCodeBtn">
                                <i class="fas fa-paper-plane"></i> Send Verification Code
                            </button>

                            <div id="codeSection" style="display: none; margin-top: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label">Enter Verification Code</label>
                                    <div class="verification-code">
                                        <input type="text" class="code-input" maxlength="1" data-index="0">
                                        <input type="text" class="code-input" maxlength="1" data-index="1">
                                        <input type="text" class="code-input" maxlength="1" data-index="2">
                                        <input type="text" class="code-input" maxlength="1" data-index="3">
                                        <input type="text" class="code-input" maxlength="1" data-index="4">
                                        <input type="text" class="code-input" maxlength="1" data-index="5">
                                    </div>
                                </div>

                                <button class="btn" id="verifyEmailCodeBtn">
                                    <i class="fas fa-check"></i> Verify Code
                                </button>
                            </div>
                            <?php else: ?>
                            <div class="verification-badge" style="background: rgba(0, 128, 0, 0.2); color: #0f0; padding: 0.5rem 1rem; border-radius: 5px;">
                                <i class="fas fa-check-circle"></i> Email Verified
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Step 3: Connect Wallet -->
                    <div class="kyc-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <div class="step-title">Connect Wallet</div>
                            <div class="step-description">Connect your MetaMask wallet to your account</div>
                            <?php if (empty($user['wallet_address'])): ?>
                            <div class="wallet-connection">
                                <div class="wallet-icon">
                                    <i class="fab fa-ethereum"></i>
                                </div>
                                <p>Connect your MetaMask wallet to enable crypto transactions and withdrawals</p>
                                <button class="btn" id="connectWalletBtn">
                                    <i class="fab fa-metamask"></i> Connect MetaMask
                                </button>
                            </div>

                            <div id="walletInfo" style="display: none; margin-top: 1.5rem;">
                                <div class="form-group">
                                    <label class="form-label" for="walletAddress">Connected Wallet Address</label>
                                    <input type="text" class="form-input" id="walletAddress" value="" readonly>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="verification-badge" style="background: rgba(0, 128, 0, 0.2); color: #0f0; padding: 0.5rem 1rem; border-radius: 5px;">
                                <i class="fas fa-check-circle"></i> Wallet Connected: <span style="font-weight: bold;"><?= substr(htmlspecialchars($user['wallet_address']), 0, 6) . '...' . substr(htmlspecialchars($user['wallet_address']), -4) ?></span>
                            </div>
                            <div class="form-group" style="margin-top: 1rem;">
                                <label class="form-label" for="walletAddress">Connected Wallet Address</label>
                                <input type="text" class="form-input" id="walletAddress" value="<?= htmlspecialchars($user['wallet_address']) ?>" readonly>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Step 4: ID Document -->
                    <div class="kyc-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <div class="step-title">ID Document</div>
                            <div class="step-description">Upload a government-issued ID (Passport, Driver's License, or National ID)</div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="idType">ID Type</label>
                                    <select class="form-input" id="idType">
                                        <option value="">Select ID Type</option>
                                        <option value="passport" <?= (isset($kycData['id_type']) && $kycData['id_type'] === 'passport') ? 'selected' : '' ?>>Passport</option>
                                        <option value="driver" <?= (isset($kycData['id_type']) && $kycData['id_type'] === 'driver') ? 'selected' : '' ?>>Driver's License</option>
                                        <option value="national" <?= (isset($kycData['id_type']) && $kycData['id_type'] === 'national') ? 'selected' : '' ?>>National ID</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label" for="idNumber">ID Number</label>
                                    <input type="text" class="form-input" id="idNumber" placeholder="Enter ID number" value="<?= htmlspecialchars($kycData['id_number'] ?? '') ?>">
                                </div>
                            </div>

                            <h4 style="margin: 1.5rem 0 1rem; color: var(--primary);">Front of ID</h4>
                            <div class="upload-area" id="idFrontUploadArea">
                                <div class="upload-icon">
                                    <i class="fas fa-id-card"></i>
                                </div>
                                <div class="upload-text">Drag & drop front of ID here or click to browse</div>
                                <div class="btn btn-outline">Select File</div>
                                <input type="file" id="idFrontDocument" accept="image/*" style="display: none;">
                            </div>

                            <div class="file-preview" id="idFrontPreview">
                                <?php if (!empty($kycData['id_image_path'])): ?>
                                <div class="file-item">
                                    <img src="<?= htmlspecialchars(BASE_URL . 'uploads/kyc_docs/' . $kycData['id_image_path']) ?>" alt="ID Front">
                                    <div class="file-remove" onclick="this.parentElement.remove();"><i class="fas fa-times"></i></div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <h4 style="margin: 1.5rem 0 1rem; color: var(--primary);">Back of ID (Optional)</h4>
                            <div class="upload-area" id="idBackUploadArea">
                                <div class="upload-icon">
                                    <i class="fas fa-id-card-alt"></i>
                                </div>
                                <div class="upload-text">Drag & drop back of ID here or click to browse</div>
                                <div class="btn btn-outline">Select File</div>
                                <input type="file" id="idBackDocument" accept="image/*" style="display: none;">
                            </div>

                            <div class="file-preview" id="idBackPreview">
                                <?php if (!empty($kycData['id_image_back_path'] ?? '')): // Assuming a column for back of ID ?>
                                <div class="file-item">
                                    <img src="<?= htmlspecialchars(BASE_URL . 'uploads/kyc_docs/' . $kycData['id_image_back_path']) ?>" alt="ID Back">
                                    <div class="file-remove" onclick="this.parentElement.remove();"><i class="fas fa-times"></i></div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <button class="btn" id="saveIdDocumentBtn">
                                <i class="fas fa-upload"></i> Submit ID Document
                            </button>
                        </div>
                    </div>

                    <!-- Step 5: Selfie Verification -->
                    <div class="kyc-step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <div class="step-title">Selfie Verification</div>
                            <div class="step-description">Take a selfie holding your ID document</div>

                            <div class="upload-area" id="selfieUploadArea">
                                <div class="upload-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <div class="upload-text">Upload a selfie with your ID document</div>
                                <div class="btn btn-outline">Take Photo</div>
                                <input type="file" id="selfieDocument" accept="image/*" style="display: none;">
                            </div>

                            <div class="file-preview" id="selfiePreview">
                                <?php if (!empty($kycData['selfie_image_path'] ?? '')): // Assuming a column for selfie path ?>
                                <div class="file-item">
                                    <img src="<?= htmlspecialchars(BASE_URL . 'uploads/kyc_docs/' . $kycData['selfie_image_path']) ?>" alt="Selfie with ID">
                                    <div class="file-remove" onclick="this.parentElement.remove();"><i class="fas fa-times"></i></div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <button class="btn" id="saveSelfieBtn">
                                <i class="fas fa-upload"></i> Submit Selfie
                            </button>
                        </div>
                    </div>

                    <!-- Step 6: Review and Submit -->
                    <div class="kyc-step">
                        <div class="step-number">6</div>
                        <div class="step-content">
                            <div class="step-title">Review and Submit</div>
                            <div class="step-description">Review your information and submit for verification</div>

                            <p style="margin: 1.5rem 0;">Please review all information before submitting for verification.</p>

                            <div class="kyc-actions">
                                <button class="btn" id="reviewKycBtn">
                                    <i class="fas fa-eye"></i> Review Information
                                </button>

                                <button class="btn" id="submitKycBtn" <?= ($user['kyc_status'] === 'approved' || $user['kyc_status'] === 'pending') ? 'disabled' : '' ?>>
                                    <i class="fas fa-paper-plane"></i> Submit for Verification
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- KYC Review Modal -->
    <div class="modal" id="kycReviewModal">
        <div class="modal-content">
            <button class="close-modal" id="closeModal">&times;</button>
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-file-alt"></i> KYC Verification Review</h2>
            </div>
            <div class="modal-body">
                <div class="review-section">
                    <h3 class="review-title"><i class="fas fa-user"></i> Personal Information</h3>
                    <div class="review-grid">
                        <div class="review-item">
                            <div class="review-label">Full Name</div>
                            <div class="review-value" id="reviewFullName"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">Date of Birth</div>
                            <div class="review-value" id="reviewDob"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">Country</div>
                            <div class="review-value" id="reviewCountry"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">Contact Number</div>
                            <div class="review-value" id="reviewContact"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">Address</div>
                            <div class="review-value" id="reviewAddress"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">City</div>
                            <div class="review-value" id="reviewCity"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">State/Province</div>
                            <div class="review-value" id="reviewStateProvince"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">Postal Code</div>
                            <div class="review-value" id="reviewPostalCode"></div>
                        </div>
                    </div>
                </div>

                <div class="review-section">
                    <h3 class="review-title"><i class="fas fa-id-card"></i> ID Document</h3>
                    <div class="review-grid">
                        <div class="review-item">
                            <div class="review-label">ID Type</div>
                            <div class="review-value" id="reviewIdType"></div>
                        </div>
                        <div class="review-item">
                            <div class="review-label">ID Number</div>
                            <div class="review-value" id="reviewIdNumber"></div>
                        </div>
                    </div>
                    <div class="review-images">
                        <div class="review-image">
                            <img src="" id="reviewIdFront" alt="ID Front">
                        </div>
                        <div class="review-image">
                            <img src="" id="reviewIdBack" alt="ID Back">
                        </div>
                    </div>
                </div>

                <div class="review-section">
                    <h3 class="review-title"><i class="fas fa-camera"></i> Selfie Verification</h3>
                    <div class="review-images">
                        <div class="review-image">
                            <img src="" id="reviewSelfie" alt="Selfie with ID">
                        </div>
                    </div>
                </div>

                <div class="review-section">
                    <h3 class="review-title"><i class="fab fa-ethereum"></i> Wallet Information</h3>
                    <div class="review-item">
                        <div class="review-label">Wallet Address</div>
                        <div class="review-value" id="reviewWallet"></div>
                    </div>
                </div>

                <div class="agreement">
                    <input type="checkbox" id="agreeTerms">
                    <label for="agreeTerms">
                        I confirm that all information provided is accurate and complete. I agree to the
                        <a href="#" style="color: var(--primary);">Terms of Service</a> and
                        <a href="#" style="color: var(--primary);">Privacy Policy</a>. I understand that
                        my KYC application will be reviewed by the RAWR Casino team, and this process may take
                        1-3 business days.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelReviewBtn">Cancel</button>
                <button class="btn" id="confirmSubmitBtn" disabled>Submit for Verification</button>
            </div>
        </div>
    </div>

    <!-- KYC Submitted Modal -->
    <div class="modal" id="kycSubmittedModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title"><i class="fas fa-check-circle"></i> KYC Submitted Successfully!</h2>
            </div>
            <div class="modal-body">
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 5rem; color: var(--primary); margin-bottom: 1.5rem;">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <h3 style="font-size: 1.8rem; margin-bottom: 1rem; color: var(--primary);">Verification in Progress</h3>
                    <p>Your KYC application has been submitted successfully. Our team will review your information within 1-3 business days.</p>
                    <p>You'll receive an email notification once your verification is complete.</p>
                    <p style="margin-top: 2rem;">
                        <button class="btn" id="closeSubmittedModal">
                            <i class="fas fa-check"></i> Continue to Casino
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>

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
        // PHP data injected into JavaScript
        const phpData = {
            userId: <?= (int)$userId ?>,
            username: '<?= htmlspecialchars($user['username']) ?>',
            email: '<?= htmlspecialchars($user['email']) ?>',
            rawrBalance: <?= (float)$user['rawr_balance'] ?>,
            ticketBalance: <?= (int)$user['ticket_balance'] ?>,
            memberSince: '<?= date('M Y', strtotime($user['created_at'])) ?>',
            kycStatus: '<?= htmlspecialchars($user['kyc_status']) ?>',
            referralCode: '<?= htmlspecialchars($user['referral_code']) ?>',
            currentLoginStreak: <?= (int)$currentStreak ?>,
            longestLoginStreak: <?= (int)$longestStreak ?>,
            totalRawrMined: <?= number_format((float)$miningData['total_mined'] ?? 0, 4, '.', '') ?>,
            miningBoostLevel: <?= number_format((float)$miningMultiplier, 2, '.', '') ?>,
            totalGameWins: <?= (int)$totalGameWins ?>,
            leaderboardRank: <?= (int)$rank ?>,
            kycProgressStatus: JSON.parse('<?= json_encode($kycProgressStatus) ?>'), // Detailed KYC step status
            kycOverallProgress: <?= (float)$kycOverallProgress ?>,

            // KYC request data (if any exists) for pre-filling forms and review
            kycRequestData: {
                fullName: '<?= htmlspecialchars($kycData['full_name'] ?? '') ?>',
                dob: '<?= htmlspecialchars($kycData['date_of_birth'] ?? '') ?>',
                country: '<?= htmlspecialchars($kycData['country'] ?? '') ?>',
                contactNumber: '<?= htmlspecialchars($kycData['contact_number'] ?? '') ?>',
                address: '<?= htmlspecialchars($kycData['address'] ?? '') ?>',
                city: '<?= htmlspecialchars($kycData['city'] ?? '') ?>',
                stateProvince: '<?= htmlspecialchars($kycData['state_province'] ?? '') ?>',
                postalCode: '<?= htmlspecialchars($kycData['postal_code'] ?? '') ?>',
                idType: '<?= htmlspecialchars($kycData['id_type'] ?? '') ?>',
                idNumber: '<?= htmlspecialchars($kycData['id_number'] ?? '') ?>',
                idFrontPath: '<?= htmlspecialchars(isset($kycData['id_image_path']) ? BASE_URL . 'uploads/kyc_docs/' . $kycData['id_image_path'] : '') ?>',
                idBackPath: '<?= htmlspecialchars(isset($kycData['id_image_back_path']) ? BASE_URL . 'uploads/kyc_docs/' . $kycData['id_image_back_path'] : '') ?>',
                selfiePath: '<?= htmlspecialchars(isset($kycData['selfie_image_path']) ? BASE_URL . 'uploads/kyc_docs/' . $kycData['selfie_image_path'] : '') ?>',
                walletAddress: '<?= htmlspecialchars($user['wallet_address'] ?? '') ?>'
            }
        };


        // --- DOM Elements ---
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        // Overview Tab Elements
        const overviewRawrBalance = document.getElementById('overviewRawrBalance');
        const overviewTicketsBalance = document.getElementById('overviewTicketsBalance');
        const overviewRank = document.getElementById('overviewRank');
        const overviewTotalWins = document.getElementById('overviewTotalWins');
        const currentLoginStreak = document.getElementById('currentLoginStreak');
        const longestLoginStreak = document.getElementById('longestLoginStreak');
        const totalRawrMined = document.getElementById('totalRawrMined');
        const miningBoostLevel = document.getElementById('miningBoostLevel');

        // Settings Tab Elements
        const profileUsername = document.getElementById('profileUsername');
        const settingsUsername = document.getElementById('settingsUsername');
        const settingsBio = document.getElementById('settingsBio');
        const saveProfileBtn = document.getElementById('saveProfileBtn');
        const currentPasswordInput = document.getElementById('currentPassword');
        const newPasswordInput = document.getElementById('newPassword');
        const confirmNewPasswordInput = document.getElementById('confirmNewPassword');
        const changePasswordBtn = document.getElementById('changePasswordBtn');
        const uploadAvatarBtn = document.getElementById('uploadAvatarBtn');
        const avatarFileInput = document.getElementById('avatarFileInput');

        // KYC Tab Elements
        const kycProgressFill = document.getElementById('kycProgress');
        const progressPercentage = document.getElementById('progressPercentage');
        const fullNameInput = document.getElementById('fullName');
        const dobInput = document.getElementById('dob');
        const countrySelect = document.getElementById('country');
        const contactNumberInput = document.getElementById('contactNumber');
        const addressInput = document.getElementById('address');
        const cityInput = document.getElementById('city');
        const stateProvinceInput = document.getElementById('stateProvince');
        const postalCodeInput = document.getElementById('postalCode');
        const savePersonalInfoBtn = document.getElementById('savePersonalInfoBtn');

        const sendCodeBtn = document.getElementById('sendCodeBtn');
        const codeSection = document.getElementById('codeSection');
        const codeInputs = document.querySelectorAll('.code-input');
        const verifyEmailCodeBtn = document.getElementById('verifyEmailCodeBtn');

        const connectWalletBtn = document.getElementById('connectWalletBtn');
        const walletInfo = document.getElementById('walletInfo');
        const walletAddressInput = document.getElementById('walletAddress');

        const idTypeSelect = document.getElementById('idType');
        const idNumberInput = document.getElementById('idNumber');
        const idFrontUploadArea = document.getElementById('idFrontUploadArea');
        const idFrontDocumentInput = document.getElementById('idFrontDocument');
        const idFrontPreview = document.getElementById('idFrontPreview');
        const idBackUploadArea = document.getElementById('idBackUploadArea');
        const idBackDocumentInput = document.getElementById('idBackDocument');
        const idBackPreview = document.getElementById('idBackPreview');
        const saveIdDocumentBtn = document.getElementById('saveIdDocumentBtn');

        const selfieUploadArea = document.getElementById('selfieUploadArea');
        const selfieDocumentInput = document.getElementById('selfieDocument');
        const selfiePreview = document.getElementById('selfiePreview');
        const saveSelfieBtn = document.getElementById('saveSelfieBtn');

        const reviewKycBtn = document.getElementById('reviewKycBtn');
        const submitKycBtn = document.getElementById('submitKycBtn');

        // Modals
        const kycReviewModal = document.getElementById('kycReviewModal');
        const closeModalBtn = document.getElementById('closeModal');
        const cancelReviewBtn = document.getElementById('cancelReviewBtn');
        const agreeTermsCheckbox = document.getElementById('agreeTerms');
        const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
        const kycSubmittedModal = document.getElementById('kycSubmittedModal');
        const closeSubmittedModalBtn = document.getElementById('closeSubmittedModal');

        // Review Modal Elements
        const reviewFullName = document.getElementById('reviewFullName');
        const reviewDob = document.getElementById('reviewDob');
        const reviewCountry = document.getElementById('reviewCountry');
        const reviewContact = document.getElementById('reviewContact');
        const reviewAddress = document.getElementById('reviewAddress');
        const reviewCity = document.getElementById('reviewCity');
        const reviewStateProvince = document.getElementById('reviewStateProvince');
        const reviewPostalCode = document.getElementById('reviewPostalCode');
        const reviewIdType = document.getElementById('reviewIdType');
        const reviewIdNumber = document.getElementById('reviewIdNumber');
        const reviewIdFront = document.getElementById('reviewIdFront');
        const reviewIdBack = document.getElementById('reviewIdBack');
        const reviewSelfie = document.getElementById('reviewSelfie');
        const reviewWallet = document.getElementById('reviewWallet');


        // --- Global State / Flags ---
        let currentKycProgressStatus = phpData.kycProgressStatus;
        let currentKycOverallProgress = phpData.kycOverallProgress;
        let verificationCode = null; // Store the generated code

        // --- Functions ---

        // Helper to display messages (replaces alert)
        function showNotification(message, type = "info") {
            const notification = document.createElement('div');
            notification.style.position = 'fixed';
            notification.style.bottom = '20px';
            notification.style.right = '20px';
            notification.style.backgroundColor = type === "success" ? 'rgba(0, 128, 0, 0.2)' :
                                                type === "error" ? 'rgba(255, 0, 0, 0.2)' : 'rgba(30, 30, 30, 0.9)';
            notification.style.color = type === "success" ? '#0f0' :
                                      type === "error" ? '#f55' : 'var(--primary)';
            notification.style.padding = '15px 25px';
            notification.style.borderRadius = '8px';
            notification.style.border = type === "success" ? '1px solid #0f0' :
                                      type === "error" ? '1px solid #f55' : '1px solid var(--primary)';
            notification.style.boxShadow = '0 5px 15px rgba(0, 0, 0, 0.3)';
            notification.style.zIndex = '1000';
            notification.style.transition = 'transform 0.3s ease';
            notification.style.transform = 'translateX(120%)';
            notification.innerHTML = `<i class="fas fa-${type === "success" ? "check-circle" :
                                                       type === "error" ? "exclamation-circle" : "info-circle"}"></i> ${message}`;

            document.body.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);

            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(120%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }

        // Update overall UI with latest PHP data and calculated states
        function updateUI() {
            overviewRawrBalance.textContent = phpData.rawrBalance.toFixed(2);
            overviewTicketsBalance.textContent = phpData.ticketBalance;
            overviewRank.textContent = phpData.leaderboardRank;
            overviewTotalWins.textContent = phpData.totalGameWins;
            currentLoginStreak.textContent = `${phpData.currentLoginStreak} Days`;
            longestLoginStreak.textContent = `${phpData.longestLoginStreak} Days`;
            totalRawrMined.textContent = `${phpData.totalRawrMined} RAWR`;
            miningBoostLevel.textContent = `x${phpData.miningBoostLevel}`;

            profileUsername.textContent = phpData.username;
            settingsUsername.value = phpData.username;

            // Update KYC progress bar and percentage
            kycProgressFill.style.width = `${phpData.kycOverallProgress}%`;
            progressPercentage.textContent = `${Math.round(phpData.kycOverallProgress)}%`;

            // Update KYC status badge
            const kycBadge = document.querySelector('.profile-header .verification-badge');
            if (kycBadge) {
                kycBadge.remove(); // Remove existing badge to update
            }
            const userInfoDiv = document.querySelector('.user-info');
            const newBadge = document.createElement('div');
            newBadge.classList.add('verification-badge');

            if (phpData.kycStatus === 'approved') {
                newBadge.style.background = 'rgba(0, 128, 0, 0.2)';
                newBadge.style.color = '#0f0';
                newBadge.innerHTML = '<i class="fas fa-shield-alt"></i> Verified Account';
                submitKycBtn.disabled = true; // If already approved, disable final submit
            } else if (phpData.kycStatus === 'pending') {
                newBadge.style.background = 'rgba(255, 165, 0, 0.2)';
                newBadge.style.color = '#FFA500';
                newBadge.innerHTML = '<i class="fas fa-hourglass-half"></i> KYC Pending';
                submitKycBtn.disabled = true; // If pending, disable final submit
            } else { // rejected or not_submitted
                newBadge.style.background = 'rgba(255, 0, 0, 0.2)';
                newBadge.style.color = '#FF6347';
                newBadge.innerHTML = '<i class="fas fa-times-circle"></i> KYC Not Verified';
                submitKycBtn.disabled = false; // Enable if not verified/rejected
            }
            userInfoDiv.appendChild(newBadge);

            // Pre-fill KYC form fields if data exists
            fullNameInput.value = phpData.kycRequestData.fullName;
            dobInput.value = phpData.kycRequestData.dob;
            countrySelect.value = phpData.kycRequestData.country;
            contactNumberInput.value = phpData.kycRequestData.contactNumber;
            addressInput.value = phpData.kycRequestData.address;
            cityInput.value = phpData.kycRequestData.city;
            stateProvinceInput.value = phpData.kycRequestData.stateProvince;
            postalCodeInput.value = phpData.kycRequestData.postalCode;
            idTypeSelect.value = phpData.kycRequestData.idType;
            idNumberInput.value = phpData.kycRequestData.idNumber;
            walletAddressInput.value = phpData.kycRequestData.walletAddress;

            // Display uploaded images if paths exist
            if (phpData.kycRequestData.idFrontPath) {
                idFrontPreview.innerHTML = `<div class="file-item"><img src="${phpData.kycRequestData.idFrontPath}" alt="ID Front"><div class="file-remove" onclick="this.parentElement.remove(); phpData.kycRequestData.idFrontPath = ''; updateKycProgress();"><i class="fas fa-times"></i></div></div>`;
            }
            if (phpData.kycRequestData.idBackPath) {
                idBackPreview.innerHTML = `<div class="file-item"><img src="${phpData.kycRequestData.idBackPath}" alt="ID Back"><div class="file-remove" onclick="this.parentElement.remove(); phpData.kycRequestData.idBackPath = ''; updateKycProgress();"><i class="fas fa-times"></i></div></div>`;
            }
            if (phpData.kycRequestData.selfiePath) {
                selfiePreview.innerHTML = `<div class="file-item"><img src="${phpData.kycRequestData.selfiePath}" alt="Selfie with ID"><div class="file-remove" onclick="this.parentElement.remove(); phpData.kycRequestData.selfiePath = ''; updateKycProgress();"><i class="fas fa-times"></i></div></div>`;
            }

            // Update status of KYC step buttons/sections
            // This is largely driven by `currentKycProgressStatus` but also `phpData.kycStatus` (overall from DB)
            // For example, if wallet is connected:
            if (phpData.kycRequestData.walletAddress) {
                connectWalletBtn.innerHTML = '<i class="fas fa-check"></i> Connected';
                connectWalletBtn.disabled = true;
                walletInfo.style.display = 'block';
            } else {
                 connectWalletBtn.innerHTML = '<i class="fab fa-metamask"></i> Connect MetaMask';
                 connectWalletBtn.disabled = false;
                 walletInfo.style.display = 'none';
            }

            // If email is verified, disable send code btn and show badge
            const emailVerifiedBadge = document.querySelector('.kyc-step:nth-child(2) .verification-badge');
            if (phpData.kycProgressStatus.emailVerified) {
                if (emailVerifiedBadge) emailVerifiedBadge.remove(); // Remove old one if it exists
                 const emailSection = document.querySelector('.kyc-step:nth-child(2) .step-content');
                 const newEmailBadge = document.createElement('div');
                 newEmailBadge.classList.add('verification-badge');
                 newEmailBadge.style.cssText = 'background: rgba(0, 128, 0, 0.2); color: #0f0; padding: 0.5rem 1rem; border-radius: 5px;';
                 newEmailBadge.innerHTML = '<i class="fas fa-check-circle"></i> Email Verified';
                 emailSection.insertBefore(newEmailBadge, codeSection); // Insert before codeSection
                 sendCodeBtn.style.display = 'none';
                 codeSection.style.display = 'none';
            } else {
                 if (emailVerifiedBadge) emailVerifiedBadge.remove();
                 sendCodeBtn.style.display = 'inline-flex'; // Show if not verified
            }

            // Update submit KYC button state based on overall KYC status
            if (phpData.kycStatus === 'approved' || phpData.kycStatus === 'pending') {
                submitKycBtn.disabled = true;
                reviewKycBtn.classList.remove('pulse');
            } else if (currentKycOverallProgress === 100) {
                 submitKycBtn.disabled = false;
                 reviewKycBtn.classList.add('pulse');
            } else {
                 submitKycBtn.disabled = true;
                 reviewKycBtn.classList.remove('pulse');
            }
        }


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

        // Tab functionality
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                btn.classList.add('active');
                document.getElementById(btn.dataset.tab + 'Tab').classList.add('active');
            });
        });

        // Avatar upload simulation (for UI, not actual file upload to server)
        document.getElementById('editAvatarBtn').addEventListener('click', function() {
            const avatars = ["🦁", "🐯", "🐆", "🐘", "🦏", "🦒", "🦧", "🐊", "🦜"];
            const randomAvatar = avatars[Math.floor(Math.random() * avatars.length)];
            document.getElementById('profileAvatar').textContent = randomAvatar;
            showNotification("Avatar updated successfully!");
        });

        uploadAvatarBtn.addEventListener('click', () => {
             avatarFileInput.click();
        });

        avatarFileInput.addEventListener('change', (e) => {
             if (e.target.files.length > 0) {
                 const file = e.target.files[0];
                 const reader = new FileReader();
                 reader.onload = function(event) {
                     document.getElementById('profileAvatar').innerHTML = `<img src="${event.target.result}" alt="Profile Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
                     showNotification("Avatar uploaded (client-side only).");
                 };
                 reader.readAsDataURL(file);
             }
        });


        // File upload handlers for KYC documents (client-side preview only)
        function setupUploadArea(uploadAreaElement, fileInputElement, previewElement) {
            uploadAreaElement.addEventListener('click', () => {
                fileInputElement.click();
            });

            fileInputElement.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    const file = e.target.files[0];
                    const reader = new FileReader();

                    reader.onload = function(event) {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        fileItem.innerHTML = `
                            <img src="${event.target.result}" alt="Uploaded file">
                            <div class="file-remove" onclick="this.parentElement.remove(); updateKycProgress();">
                                <i class="fas fa-times"></i>
                            </div>
                        `;
                        previewElement.innerHTML = ''; // Clear previous preview
                        previewElement.appendChild(fileItem);
                        updateKycProgress(); // Update progress after file selected (client-side)
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Initialize file upload areas
        setupUploadArea(idFrontUploadArea, idFrontDocumentInput, idFrontPreview);
        setupUploadArea(idBackUploadArea, idBackDocumentInput, idBackPreview);
        setupUploadArea(selfieUploadArea, selfieDocumentInput, selfiePreview);


        // Update KYC progress logic (client-side)
        function updateKycProgress() {
            let completedSteps = 0;
            // Check if personal info fields are filled
            if (fullNameInput.value && dobInput.value && countrySelect.value && contactNumberInput.value &&
                addressInput.value && cityInput.value && stateProvinceInput.value && postalCodeInput.value) {
                currentKycProgressStatus.personalInfo = true;
            } else {
                currentKycProgressStatus.personalInfo = false;
            }

            // Email status is handled by backend response in real app, simulated here
            // For now, if emailVerified is true from PHP, it remains true.
            // Otherwise, it's set by verifyEmailCode success.

            // Check if wallet is connected
            if (walletAddressInput.value && walletAddressInput.value !== '') {
                currentKycProgressStatus.walletConnected = true;
            } else {
                currentKycProgressStatus.walletConnected = false;
            }

            // Check if ID documents are previewed (implies selected by user)
            if (idFrontPreview.children.length > 0) {
                currentKycProgressStatus.idDocument = true;
            } else {
                currentKycProgressStatus.idDocument = false;
            }

            // Check if selfie is previewed
            if (selfiePreview.children.length > 0) {
                currentKycProgressStatus.selfie = true;
            } else {
                currentKycProgressStatus.selfie = false;
            }

            // Count truly completed steps
            Object.values(currentKycProgressStatus).forEach(isComplete => {
                if (isComplete) {
                    completedSteps++;
                }
            });

            currentKycOverallProgress = (completedSteps / 5) * 100; // Total 5 steps for progress calculation
            kycProgressFill.style.width = currentKycOverallProgress + '%';
            progressPercentage.textContent = Math.round(currentKycOverallProgress) + '%';

            // Enable/disable final submit button based on all steps completion
            if (currentKycOverallProgress === 100 && phpData.kycStatus !== 'approved' && phpData.kycStatus !== 'pending') {
                submitKycBtn.disabled = false;
                reviewKycBtn.classList.add('pulse');
            } else {
                submitKycBtn.disabled = true;
                reviewKycBtn.classList.remove('pulse');
            }
        }


        // --- KYC Step Functions (AJAX calls to backend) ---

        savePersonalInfoBtn.addEventListener('click', () => {
            const formData = new FormData();
            formData.append('action', 'save_personal_info');
            formData.append('fullName', fullNameInput.value);
            formData.append('dob', dobInput.value);
            formData.append('country', countrySelect.value);
            formData.append('contactNumber', contactNumberInput.value);
            formData.append('address', addressInput.value);
            formData.append('city', cityInput.value);
            formData.append('stateProvince', stateProvinceInput.value);
            formData.append('postalCode', postalCodeInput.value);


            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message, 'success');
                    phpData.kycRequestData.fullName = fullNameInput.value;
                    phpData.kycRequestData.dob = dobInput.value;
                    phpData.kycRequestData.country = countrySelect.value;
                    phpData.kycRequestData.contactNumber = contactNumberInput.value;
                    phpData.kycRequestData.address = addressInput.value;
                    phpData.kycRequestData.city = cityInput.value;
                    phpData.kycRequestData.stateProvince = stateProvinceInput.value;
                    phpData.kycRequestData.postalCode = postalCodeInput.value;

                    phpData.kycStatus = data.kyc_status; // Update overall status
                    updateKycProgress(); // Re-evaluate progress
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error saving personal info:', error);
                showNotification('An error occurred saving personal info.', 'error');
            });
        });

        sendCodeBtn.addEventListener('click', () => {
            const email = document.getElementById('verifyEmail').value;
            if (!email) {
                showNotification("Email address is required", "error");
                return;
            }

            fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=send_email_code&email=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message, 'info');
                    codeSection.style.display = 'block';
                    // In a real app, the backend would generate and store the code securely.
                    // For demo, we simulate a code here:
                    verificationCode = Math.floor(100000 + Math.random() * 900000).toString();
                    console.log("Simulated verification code: " + verificationCode); // For testing
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error sending code:', error);
                showNotification('An error occurred sending verification code.', 'error');
            });
        });

        verifyEmailCodeBtn.addEventListener('click', () => {
            let enteredCode = '';
            codeInputs.forEach(input => {
                enteredCode += input.value;
            });

            if (enteredCode === verificationCode) { // Compare with simulated code
                 fetch('profile.php', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                     body: `action=verify_email_code&code=${encodeURIComponent(enteredCode)}`
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.status === 'success') {
                         currentKycProgressStatus.emailVerified = true; // Update client-side status
                         showNotification("Email verified successfully!", "success");
                         updateKycProgress();
                         sendCodeBtn.style.display = 'none'; // Hide send code button
                         codeSection.style.display = 'none'; // Hide code input
                         // Optionally reload or update PHP data to reflect verified state
                         phpData.kycProgressStatus.emailVerified = true;
                         updateUI(); // Re-render UI based on new status
                     } else {
                         showNotification(data.message, 'error');
                     }
                 })
                 .catch(error => {
                     console.error('Error verifying code:', error);
                     showNotification('An error occurred verifying code.', 'error');
                 });
            } else {
                showNotification("Invalid verification code. Please try again.", "error");
            }
        });


        connectWalletBtn.addEventListener('click', async () => {
             if (typeof window.ethereum === 'undefined') {
                 showNotification("MetaMask is not installed. Please install it to connect your wallet.", "error");
                 return;
             }

             try {
                 const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                 const walletAddress = accounts[0]; // Get the first connected account

                 fetch('profile.php', {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                     body: `action=connect_wallet&walletAddress=${encodeURIComponent(walletAddress)}`
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.status === 'success') {
                         phpData.kycRequestData.walletAddress = walletAddress; // Update local data
                         showNotification("MetaMask wallet connected successfully!", "success");
                         updateKycProgress();
                         updateUI(); // Re-render UI to show connected state
                     } else {
                         showNotification(data.message, 'error');
                     }
                 })
                 .catch(error => {
                     console.error('Error connecting wallet to backend:', error);
                     showNotification('An error occurred connecting your wallet.', 'error');
                 });

             } catch (error) {
                 console.error('MetaMask connection failed:', error);
                 showNotification('MetaMask connection failed. Please try again or check your MetaMask extension.', 'error');
             }
         });


        saveIdDocumentBtn.addEventListener('click', () => {
            const idType = idTypeSelect.value;
            const idNumber = idNumberInput.value;
            const idFrontFile = idFrontDocumentInput.files[0];
            const idBackFile = idBackDocumentInput.files[0]; // Optional

            if (!idType || !idNumber || !idFrontFile) {
                showNotification("Please select ID type, enter ID number, and upload ID front.", "error");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'submit_kyc_document');
            formData.append('idType', idType);
            formData.append('idNumber', idNumber);
            formData.append('idFront', idFrontFile);
            if (idBackFile) {
                formData.append('idBack', idBackFile);
            }

            fetch('profile.php', {
                method: 'POST',
                body: formData // FormData handles file uploads automatically
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification("ID document submitted successfully!", "success");
                    // Update phpData paths for persistent display (client-side only for this demo)
                    phpData.kycRequestData.idType = idType;
                    phpData.kycRequestData.idNumber = idNumber;
                    // In a real app, the server would return the actual file paths
                    phpData.kycRequestData.idFrontPath = idFrontPreview.querySelector('img')?.src || '';
                    phpData.kycRequestData.idBackPath = idBackPreview.querySelector('img')?.src || '';
                    updateKycProgress();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error submitting ID document:', error);
                showNotification('An error occurred submitting ID document.', 'error');
            });
        });

        saveSelfieBtn.addEventListener('click', () => {
            const selfieFile = selfieDocumentInput.files[0];

            if (!selfieFile) {
                showNotification("Please upload your selfie with ID.", "error");
                return;
            }

            const formData = new FormData();
            formData.append('action', 'submit_selfie');
            formData.append('selfie', selfieFile);

            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification("Selfie submitted successfully!", "success");
                    phpData.kycRequestData.selfiePath = selfiePreview.querySelector('img')?.src || '';
                    updateKycProgress();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error submitting selfie:', error);
                showNotification('An error occurred submitting selfie.', 'error');
            });
        });


        // Profile settings save
        saveProfileBtn.addEventListener('click', () => {
            const newUsername = settingsUsername.value;
            const newBio = settingsBio.value; // Assuming 'bio' field exists in 'users' table

            const formData = new FormData();
            formData.append('action', 'update_profile');
            formData.append('username', newUsername);
            formData.append('bio', newBio); // Add bio to form data

            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message, 'success');
                    phpData.username = data.new_username; // Update local data
                    profileUsername.textContent = data.new_username; // Update header
                    updateUI();
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error updating profile:', error);
                showNotification('An error occurred while updating profile.', 'error');
            });
        });

        // Password change
        changePasswordBtn.addEventListener('click', () => {
            const currentPassword = currentPasswordInput.value;
            const newPassword = newPasswordInput.value;
            const confirmNewPassword = confirmNewPasswordInput.value;

            const formData = new FormData();
            formData.append('action', 'change_password');
            formData.append('current_password', currentPassword);
            formData.append('new_password', newPassword);
            formData.append('confirm_new_password', confirmNewPassword);

            fetch('profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message, 'success');
                    // Clear password fields
                    currentPasswordInput.value = '';
                    newPasswordInput.value = '';
                    confirmNewPasswordInput.value = '';
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error changing password:', error);
                showNotification('An error occurred while changing password.', 'error');
            });
        });


        // --- KYC Review Modal Logic ---
        reviewKycBtn.addEventListener('click', () => {
            if (phpData.kycStatus === 'approved' || phpData.kycStatus === 'pending') {
                showNotification(`KYC is already ${phpData.kycStatus}.`, 'info');
                return;
            }
            if (currentKycOverallProgress < 100) {
                showNotification("Please complete all KYC steps before reviewing.", "error");
                return;
            }

            // Populate review modal with current form data and file previews
            reviewFullName.textContent = fullNameInput.value;
            reviewDob.textContent = dobInput.value;
            reviewCountry.textContent = countrySelect.options[countrySelect.selectedIndex].text;
            reviewContact.textContent = contactNumberInput.value;
            reviewAddress.textContent = `${addressInput.value}, ${cityInput.value}, ${stateProvinceInput.value}, ${postalCodeInput.value}`;
            reviewCity.textContent = cityInput.value;
            reviewStateProvince.textContent = stateProvinceInput.value;
            reviewPostalCode.textContent = postalCodeInput.value;

            reviewIdType.textContent = idTypeSelect.options[idTypeSelect.selectedIndex].text;
            reviewIdNumber.textContent = idNumberInput.value;

            reviewWallet.textContent = walletAddressInput.value;

            // Image previews
            reviewIdFront.src = idFrontPreview.querySelector('img')?.src || 'https://placehold.co/150x150/000000/FFFFFF?text=No+Image';
            reviewIdBack.src = idBackPreview.querySelector('img')?.src || 'https://placehold.co/150x150/000000/FFFFFF?text=No+Image';
            reviewSelfie.src = selfiePreview.querySelector('img')?.src || 'https://placehold.co/150x150/000000/FFFFFF?text=No+Image';


            kycReviewModal.style.display = 'block';
        });

        closeModalBtn.addEventListener('click', () => {
            kycReviewModal.style.display = 'none';
        });

        cancelReviewBtn.addEventListener('click', () => {
            kycReviewModal.style.display = 'none';
        });

        agreeTermsCheckbox.addEventListener('change', function() {
            confirmSubmitBtn.disabled = !this.checked;
        });

        confirmSubmitBtn.addEventListener('click', () => {
            // Final submission to backend
            fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=final_kyc_submit`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(data.message, 'success');
                    kycReviewModal.style.display = 'none';
                    kycSubmittedModal.style.display = 'block';
                    phpData.kycStatus = 'pending'; // Update local status to pending
                    updateUI(); // Refresh UI
                } else {
                    showNotification(data.message, 'error');
                    kycReviewModal.style.display = 'none'; // Close review modal even on error
                }
            })
            .catch(error => {
                console.error('Error submitting KYC:', error);
                showNotification('An error occurred during KYC submission.', 'error');
                kycReviewModal.style.display = 'none';
            });
        });

        closeSubmittedModalBtn.addEventListener('click', () => {
            kycSubmittedModal.style.display = 'none';
        });

        // Verification code input navigation
        codeInputs.forEach((input, index, inputs) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });

        // Initial UI update on page load
        updateUI();
        updateKycProgress(); // Also call this to calculate initial client-side progress based on PHP data
    </script>
</body>
</html>
