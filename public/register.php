<?php
// register.php
require_once __DIR__ . '/../backend/inc/init.php';

// Redirect logged in users
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Check for error messages
$error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'csrf':
            $error = 'Invalid security token. Please try again.';
            break;
        case 'username':
            $error = 'Username is already taken';
            break;
        case 'email':
            $error = 'Email is already registered';
            break;
        case 'password':
            $error = 'Passwords do not match';
            break;
        case 'referral':
            $error = 'Invalid referral code';
            break;
        case 'terms':
            $error = 'You must accept the terms and conditions';
            break;
        default:
            $error = 'An error occurred. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAWR - Join the Pride | Register</title>
    <meta name="description" content="Create your RAWR Casino account and join the pride!">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FFD700;
            --primary-dark: #FFA500;
            --secondary: #FF6B35;
            --bg-dark: #1a1a1a;
            --bg-darker: #121212;
            --bg-gradient: linear-gradient(135deg, #1a1a1a 0%, #2d1810 50%, #1a1a1a 100%);
            --text-light: #f0f0f0;
            --text-muted: #ccc;
            --success: #4CAF50;
            --error: #f44336;
            --gold-primary: #D4AF37;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-light);
            overflow-x: hidden;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem; /* Increased base padding */
        }
        .auth-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"><defs><radialGradient id="stars"><stop offset="0%" stop-color="%23FFD700" stop-opacity="1"/><stop offset="100%" stop-color="%23FFD700" stop-opacity="0"/></radialGradient></defs><circle cx="100" cy="100" r="2" fill="url(%23stars)"/><circle cx="300" cy="200" r="1.5" fill="url(%23stars)"/><circle cx="500" cy="150" r="1" fill="url(%23stars)"/><circle cx="700" cy="300" r="2" fill="url(%23stars)"/><circle cx="900" cy="250" r="1.5" fill="url(%23stars)"/><circle cx="1100" cy="400" r="1" fill="url(%23stars)"/></svg>') repeat;
            opacity: 0.3;
            animation: twinkle 4s ease-in-out infinite alternate;
            z-index: 0;
        }
        @keyframes twinkle { 0% { opacity: 0.3; } 100% { opacity: 0.6; } }
        .floating-paws {
            position: fixed;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 1;
        }
        .paw {
            position: absolute;
            font-size: 1.5rem;
            opacity: 0.1;
            animation: pawFloat 20s linear infinite;
        }
        @keyframes pawFloat {
            0% { transform: translateY(110vh) translateX(0) rotate(0deg); opacity: 0; }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% { transform: translateY(-10vh) translateX(50vw) rotate(360deg); opacity: 0; }
        }
        .auth-container {
            background: rgba(18, 18, 18, 0.95);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 25px;
            backdrop-filter: blur(20px);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 550px;
            position: relative;
            z-index: 10;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5), 
                        0 0 0 1px rgba(255, 215, 0, 0.1),
                        inset 0 1px 0 rgba(255, 215, 0, 0.1);
            overflow: hidden;
        }
        .auth-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 50% 0%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        .back-home {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            z-index: 20;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            backdrop-filter: blur(10px);
            font-size: 0.9rem;
        }
        .back-home:hover {
            transform: translateX(-5px);
            background: rgba(255, 215, 0, 0.2);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.2);
        }
        .logo-header {
            text-align: center;
            margin-bottom: 2rem;
            animation: slideDown 0.6s ease-out;
        }
        .logo-header h1 {
            font-size: 3.5rem;
            color: var(--gold-primary);
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.6);
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .logo-header h1:hover {
            transform: scale(1.08) rotate(-2deg);
            filter: brightness(1.2);
        }
        .logo {
            font-size: 2.8rem;
            font-weight: 900;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
            margin-bottom: 0.5rem;
            animation: glow 2s ease-in-out infinite alternate;
        }
        @keyframes glow { 0% { filter: brightness(1); } 100% { filter: brightness(1.2); } }
        .lion-emoji {
            font-size: 3.2rem;
            margin-bottom: 0.8rem;
            animation: bounce 3s ease-in-out infinite;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .lion-emoji:hover { transform: scale(1.1) rotate(5deg); }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        .tagline {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        .input-group { margin-bottom: 1.2rem; position: relative; }
        .input-label {
            display: block;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }
        .input-field {
            width: 100%;
            padding: 0.9rem 1.1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            color: var(--text-light);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }
        .input-field:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }
        .input-field::placeholder { color: var(--text-muted); }
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        .password-toggle:hover { color: var(--primary); }
        .forgot-password {
            text-align: right;
            margin: -0.8rem 0 1.2rem;
        }
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.85rem;
            transition: opacity 0.3s ease;
        }
        .forgot-password a:hover { opacity: 0.8; }
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 15px;
            color: var(--bg-darker);
            font-family: inherit;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-bottom: 1.2rem;
        }
        .submit-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.2) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            transition: all 0.5s ease;
            opacity: 0;
        }
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
        }
        .submit-btn:hover::after { animation: shine 1s ease; }
        @keyframes shine {
            0% { transform: rotate(30deg) translate(-100%, -100%); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: rotate(30deg) translate(100%, 100%); opacity: 0; }
        }
        .submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        .divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
            color: var(--text-muted);
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.3), transparent);
        }
        .divider span {
            background: var(--bg-darker);
            padding: 0 1rem;
            font-size: 0.85rem;
        }
        .switch-page {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        .switch-page a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .switch-page a:hover { text-decoration: underline; }
        .notification {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.9rem 1.3rem;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
            max-width: 280px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        .notification.success {
            background: var(--success);
            box-shadow: 0 5px 20px rgba(76, 175, 80, 0.3);
        }
        .notification.error {
            background: var(--error);
            box-shadow: 0 5px 20px rgba(244, 67, 54, 0.3);
        }
        .notification.info {
            background: var(--primary);
            color: var(--bg-darker);
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.3);
        }
        .loading-spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2px solid transparent;
            border-top: 2px solid var(--bg-darker);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes fadeUpOut {
            0% { opacity: 1; transform: translate(-50%, -50%); }
            100% { opacity: 0; transform: translate(-50%, -100px); }
        }
        .input-group label a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }
        .input-group label a:hover {
            color: var(--primary-dark);
            text-decoration: underline wavy var(--primary-dark);
        }
        @media (max-width: 768px) {
            .auth-container {
                padding: 2rem 1.5rem;
                max-width: 90%; /* Reduced from 95% */
                margin: 1.5rem; /* Added margin */
            }
            
            body {
                padding: 1.5rem; /* Increased padding */
            }

            .logo-header h1 {
                font-size: 3rem;
            }
            
            .lion-emoji { 
                font-size: 3rem; 
            }
            
            .back-home { 
                top: 1rem; 
                left: 1rem; 
            }

            .input-field {
                font-size: 16px; /* Prevents zoom on iOS */
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 1.5rem 1rem;
                max-width: 92%; /* Reduced from 98% */
                margin: 1rem; /* Added margin */
                border-radius: 15px;
            }

            .logo-header h1 {
                font-size: 2.5rem;
            }

            .input-group {
                margin-bottom: 1rem;
            }

            .submit-btn {
                padding: 0.9rem;
            }

            body {
                padding: 1rem; /* Added padding */
            }
        }

        /* Small phones */
        @media (max-width: 320px) {
            .auth-container {
                padding: 1rem 0.8rem;
                max-width: 95%; /* Reduced from 100% */
                margin: 0.8rem; /* Added margin */
                border-radius: 12px;
            }

            body {
                padding: 0.8rem; /* Added padding */
            }

            .logo-header h1 {
                font-size: 2.2rem;
            }

            .tagline {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-bg"></div>
    <div class="floating-paws" id="floatingPaws"></div>

    <a href="#" class="back-home" onclick="goHome()">
        ← Back to RAWR
    </a>

    <div class="auth-container">
        <div class="logo-header">
            <div class="lion-emoji" onclick="roarEffect()">🦁</div>
            <h1 onclick="roarEffect()">RAWR</h1>
            <p class="tagline">Create your RAWR Casino account</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="color: var(--error); text-align:center; margin-bottom:1rem; font-weight:bold;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form class="form" id="registerForm" method="post" action="/RAWR/backend/auth/register_process.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
            <div class="input-group">
                <label class="input-label">Username</label>
                <input type="text" name="username" class="input-field" placeholder="Enter your username" required>
            </div>
            <div class="input-group">
                <label class="input-label">Email Address</label>
                <input type="email" name="email" class="input-field" placeholder="Enter your email" required>
            </div>
            <div class="input-group">
                <label class="input-label">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" class="input-field" id="regPassword" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('regPassword')">👁️</button>
                </div>
            </div>
            <div class="input-group">
                <label class="input-label">Confirm Password</label>
                <div style="position: relative;">
                    <input type="password" name="confirm_password" class="input-field" id="regConfirm" placeholder="Confirm your password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('regConfirm')">👁️</button>
                </div>
            </div>
            <div class="input-group">
                <label class="input-label">Referral Code (Optional)</label>
                <input type="text" name="referral_code" class="input-field" placeholder="Enter referral code">
            </div>
            <div class="input-group" style="display: flex; align-items: flex-start; gap: 10px; margin-top: 0.5rem;">
                <input type="checkbox" id="terms" name="terms" required style="margin-top: 5px;">
                <label for="terms" style="font-size: 0.9rem; color: var(--text-muted); line-height: 1.5;">I accept the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
            </div>
            <button type="submit" class="submit-btn">
                <span class="loading-spinner" id="registerSpinner"></span>
                🦁 Create Account
            </button>
        </form>
        <div class="switch-page">
            <p>Already a member? <a href="login.php">Login to your Den</a></p>
        </div>
    </div>

    <div class="notification" id="notification">
        <span class="notification-icon">🦁</span>
        <span class="notification-text"></span>
    </div>

    <script>
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            createFloatingPaws();
            showNotification('Create your RAWR account and join the pride!', 'info');
        });

        function createFloatingPaws() {
            const pawContainer = document.getElementById('floatingPaws');
            const pawCount = 12;
            for (let i = 0; i < pawCount; i++) {
                const paw = document.createElement('div');
                paw.className = 'paw';
                paw.textContent = '🐾';
                paw.style.left = Math.random() * 100 + 'vw';
                paw.style.animationDelay = Math.random() * 20 + 's';
                paw.style.animationDuration = 15 + Math.random() * 10 + 's';
                pawContainer.appendChild(paw);
            }
        }

        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleBtn = passwordField.nextElementSibling;
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordField.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        function roarEffect() {
            const lion = document.querySelector('.lion-emoji');
            const header = document.querySelector('.logo-header h1');
            const originalTransform = lion.style.transform;
            
            // Animate lion
            lion.style.transform = 'scale(1.3) rotate(10deg)';
            lion.style.filter = 'brightness(1.5)';
            
            // Animate header
            header.style.transform = 'scale(1.08) rotate(-2deg)';
            header.style.filter = 'brightness(1.2)';
            
            // Create roar effect
            showFloatingText('RAWR! 🦁');
            
            // Reset animations
            setTimeout(() => {
                lion.style.transform = originalTransform;
                lion.style.filter = '';
                header.style.transform = '';
                header.style.filter = '';
            }, 300);
        }

        function showFloatingText(text) {
            const floatingText = document.createElement('div');
            floatingText.textContent = text;
            floatingText.style.cssText = `
                position: fixed;
                top: 30%;
                left: 50%;
                transform: translate(-50%, -50%);
                font-size: 2rem;
                font-weight: bold;
                color: var(--primary);
                pointer-events: none;
                z-index: 1000;
                animation: fadeUpOut 2s ease-out forwards;
                text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
            `;
            document.body.appendChild(floatingText);
            setTimeout(() => {
                floatingText.remove();
            }, 2000);
        }

        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            const textElement = notification.querySelector('.notification-text');
            notification.classList.remove('success', 'error', 'info', 'show');
            textElement.textContent = message;
            notification.classList.add(type);
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            setTimeout(() => {
                notification.classList.remove('show');
            }, 4000);
        }

        function goHome() {
            showNotification('Returning to the pride...', 'info');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        }

        // Form submissions
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.submit-btn');
            const spinner = document.getElementById('registerSpinner');
            // Show loading state
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            // Let PHP handle the actual registration, but show spinner for effect
            setTimeout(() => {
                submitBtn.disabled = false;
                spinner.style.display = 'none';
            }, 2000);
        });
    </script>
</body>
</html>