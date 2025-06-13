<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - RAWR Casino</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="jungle-bg"></div>
    <div class="container">
        <header class="dashboard-header">
            <div class="logo-container">
                <a href="/dashboard.php" class="logo">
                    <span class="logo-icon">🦁</span>
                    <span class="logo-text">RAWR</span>
                </a>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="user-info">
                <div class="balance-info">
                    <div class="balance-item">
                        <span class="balance-label">RAWR</span>
                        <span class="balance-value" id="headerRawrBalance">0.00000000</span>
                    </div>
                    <div class="balance-item">
                        <span class="balance-label">Tickets</span>
                        <span class="balance-value" id="headerTicketBalance">0</span>
                    </div>
                </div>
                <div class="user-profile">
                    <span class="username"><?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></span>
                    <div class="avatar">
                        <img src="/assets/images/avatars/lion-avatar.png" alt="User Avatar">
                        <span class="kyc-badge <?= isset($_SESSION['kyc_status']) ? $_SESSION['kyc_status'] : 'pending' ?>">
                            <?= isset($_SESSION['kyc_status']) ? ucfirst($_SESSION['kyc_status']) : 'Pending' ?>
                        </span>
                    </div>
                </div>
            </div>
        </header>
        
        <nav class="sidebar" id="sidebar">
            <ul class="nav-links">
                <li class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                    <a href="/dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= basename($_SERVER['PHP_SELF']) === 'mining.php' ? 'active' : '' ?>">
                    <a href="/mining.php">
                        <i class="fas fa-digging"></i>
                        <span>Mining</span>
                    </a>
                </li>
                <li>
                    <a href="/lobby.php">
                        <i class="fas fa-dice"></i>
                        <span>Games</span>
                    </a>
                </li>
                <li>
                    <a href="/shop.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Shop</span>
                    </a>
                </li>
                <li>
                    <a href="/leaderboard.php">
                        <i class="fas fa-trophy"></i>
                        <span>Leaderboard</span>
                    </a>
                </li>
                <li>
                    <a href="../daily.php">
                        <i class="fas fa-calendar-day"></i>
                        <span>Daily Rewards</span>
                    </a>
                </li>
                <li>
                    <a href="../profile.php">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
            
            <div class="referral-section">
                <h3>Invite Friends</h3>
                <div class="referral-code">
                    <span>Your Code:</span>
                    <strong id="referralCodeDisplay"><?= htmlspecialchars($_SESSION['referral_code'] ?? 'REF12345') ?></strong>
                    <button id="copyReferralBtn">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p>Earn 50 RAWR for each friend who joins and completes KYC</p>
            </div>
        </nav>
        
        <main class="main-content">