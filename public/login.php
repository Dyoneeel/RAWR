<?php
// login.php
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
        case 'invalid':
            $error = 'Invalid username or password';
            break;
        case 'banned':
            $error = 'Your account has been suspended';
            break;
        case 'inactive':
            $error = 'Your account is not activated';
            break;
        case 'session':
            $error = 'Your session has expired';
            break;
        case 'login_failed':
            $error = 'Invalid username or password';
            break;
        case 'csrf':
            $error = 'Session expired or invalid request. Please refresh and try again.';
            break;
        case 'empty':
            $error = 'Please fill in all fields.';
            break;
        default:
            $error = 'An error occurred. Please try again.';
    }
}

// Check for logout
$logout = isset($_GET['logout']) ? 'You have been successfully logged out' : '';

// Check for registration success
$registered = isset($_GET['registered']) ? 'Registration successful! Please log in' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAWR - Join the Pride | Login</title>
    <meta name="description" content="Join the wildest crypto casino on the blockchain. Login to start earning RAWR tokens.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            padding: 1rem;
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

        .jungle-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(45, 90, 45, 0.2) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(45, 90, 45, 0.2) 0%, transparent 20%),
                linear-gradient(135deg, var(--jungle-dark), var(--jungle-medium));
            z-index: -1;
        }

        .jungle-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><path d="M20,20 Q40,5 60,20 T100,20 Q85,40 100,60 T100,100 Q60,85 20,100 T0,100 Q5,60 0,20 T20,20 Z" fill="none" stroke="rgba(212,175,55,0.1)" stroke-width="0.5"/></svg>');
            background-size: 300px;
            opacity: 0.3;
            z-index: -1;
        }

        .lion-decoration {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, var(--gold-primary) 0%, transparent 70%);
            mask: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M50,15 C60,5 80,5 85,15 C95,25 95,40 90,50 C100,60 95,80 85,85 C75,95 60,95 50,90 C40,95 25,95 15,85 C5,75 5,60 10,50 C5,40 10,25 20,15 C30,5 40,5 50,15 Z" fill="white"/></svg>') center/contain no-repeat;
            z-index: -1;
            opacity: 0.1;
            animation: float 8s infinite ease-in-out;
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
            margin: 1rem;
        }

        .auth-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, transparent 20%, var(--jungle-dark) 70%);
            z-index: -1;
        }

        .logo-header {
            text-align: center;
            margin-bottom: 2rem;
            animation: slideDown 0.6s ease-out;
        }

        .logo-header h1 {
            font-size: 3.5rem; /* Make RAWR bigger */
            color: var(--gold-primary);
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.6);
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            cursor: pointer; /* Make it look clickable */
            transition: transform 0.2s;
        }
        .logo-header h1:hover {
            transform: scale(1.08) rotate(-2deg);
            filter: brightness(1.2);
        }

        .logo-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }

        /* --- Input fields: glassy, lighter, more like login.html --- */
        .form-group input {
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
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }
        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .form-group .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1.2rem;
            transition: color 0.3s;
            padding: 0;
            z-index: 2;
        }

        .form-group .password-toggle:hover {
            color: var(--gold-secondary);
        }

        /* Button style like login.html */
        .btn.btn-primary {
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
        .btn.btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 215, 0, 0.4);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -0.8rem 0 1.2rem 0;
            font-size: 0.95rem;
        }
        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.4em;
            color: var(--text-muted);
            font-size: 0.95em;
            user-select: none;
        }
        .remember-me input[type="checkbox"] {
            accent-color: var(--primary);
            width: 1em;
            height: 1em;
        }
        .forgot-password {
            text-align: right;
            margin: 0;
        }
        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.95em;
            transition: opacity 0.3s ease;
        }
        .forgot-password a:hover { opacity: 0.8; }

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

        .auth-links {
            display: none !important;
        }

        .message {
            padding: 1.2rem;
            margin-bottom: 1.8rem;
            border-radius: 10px;
            text-align: center;
            animation: fadeIn 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .message i {
            font-size: 1.5rem;
        }

        .message.error {
            background: rgba(244, 67, 54, 0.1);
            border: 1px solid var(--error);
            color: var(--error);
        }

        .message.success {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 0.5rem;
        }

        .terms-group input {
            margin-top: 5px;
        }

        .terms-group label {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .terms-group label a {
            color: var(--gold-secondary);
            text-decoration: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        /* Floating roar text effect */
        .floating-roar {
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
        }
        @keyframes fadeUpOut {
            0% { opacity: 1; transform: translate(-50%, -50%); }
            100% { opacity: 0; transform: translate(-50%, -100px); }
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: var(--gold-primary);
            opacity: 0.3;
            animation: float 8s infinite ease-in-out;
        }

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

        @media (max-width: 576px) {
            .auth-container {
                padding: 1.8rem;
            }
            
            .logo-header h1 {
                font-size: 2.2rem;
            }
            
            .auth-links {
                flex-direction: column;
                gap: 0.8rem;
                align-items: center;
            }
            
            .form-group input {
                padding: 1rem;
            }
            
            .btn {
                padding: 1rem;
                font-size: 1.1rem;
            }
        }

        @media (max-width: 768px) {
            .auth-container {
                padding: 2rem 1.5rem;
                max-width: 90%;
                margin: 1.5rem;
            }
            
            body {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                padding: 1.5rem 1rem;
                max-width: 92%;
                margin: 1rem;
                border-radius: 15px;
            }

            body {
                padding: 1rem;
            }
        }

        @media (max-width: 320px) {
            .auth-container {
                padding: 1rem 0.8rem;
                max-width: 95%;
                margin: 0.8rem;
                border-radius: 12px;
            }

            body {
                padding: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-bg"></div>
    <div class="floating-paws" id="floatingPaws"></div>

    <!-- Background elements -->
    <div class="jungle-bg"></div>
    <div class="jungle-overlay"></div>
    <div class="lion-decoration"></div>

    <a href="../index.php" class="back-home" onclick="goHome()">
        ← Back to RAWR
    </a>

    <!-- Login Container -->
    <div class="auth-container">
        <?php if ($error): ?>
            <div class="message error" id="alertMessage">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($logout): ?>
            <div class="message success" id="alertMessage">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($logout) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($registered): ?>
            <div class="message success" id="alertMessage">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($registered) ?>
            </div>
        <?php endif; ?>
        
        <div class="logo-header">
            <div class="lion-emoji" onclick="roarEffect()">🦁</div>
            <h1 onclick="roarEffect()">RAWR</h1>
            <p>The Lion's Game - Jungle Casino</p>
        </div>
        
        <form action="/RAWR/backend/auth/login_process.php" method="post" class="auth-form" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?= Security::generateToken() ?>">
            <div class="form-group">
                <label for="username">Email or Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your email or username" required autofocus>
            </div>
            
            <div class="form-group" style="margin-bottom: 0.5rem;">
                <label for="password">Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required style="padding-right:2.5rem;">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')" tabindex="-1">👁️</button>
                </div>
            </div>
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    Remember me
                </label>
                <span class="forgot-password">
                    <a href="#" onclick="forgotPassword()">Forgot Password?</a>
                </span>
            </div>
            
            <button type="submit" class="btn btn-primary">
                🦁 Enter the Jungle
            </button>
        </form>
        <div class="switch-page">
            <p>Don't have an account? <a href="register.php">Join the Pride</a></p>
        </div>
    </div>

    <div class="notification" id="notification">
        <span class="notification-icon">🦁</span>
        <span class="notification-text"></span>
    </div>

    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            createFloatingPaws();
            showNotification('Welcome back to RAWR! Enter your den.', 'info');
        });

        // Moving paws as background particles (like login.html's particles)
        function createFloatingPaws() {
            const pawContainer = document.getElementById('floatingPaws');
            pawContainer.innerHTML = '';
            const pawCount = 30;
            for (let i = 0; i < pawCount; i++) {
                const paw = document.createElement('div');
                paw.className = 'paw';
                paw.textContent = '🐾';
                paw.style.left = Math.random() * 100 + 'vw';
                paw.style.top = Math.random() * 100 + 'vh';
                paw.style.opacity = 0.08 + Math.random() * 0.15;
                paw.style.fontSize = (1.2 + Math.random() * 1.5) + 'rem';
                paw.style.animationDelay = Math.random() * 20 + 's';
                paw.style.animationDuration = 12 + Math.random() * 12 + 's';
                pawContainer.appendChild(paw);
            }
        }

        // Roar effect like login.html
        function roarEffect() {
            const lion = document.querySelector('.lion-emoji');
            const originalTransform = lion.style.transform;
            lion.style.transform = 'scale(1.3) rotate(10deg)';
            lion.style.filter = 'brightness(1.5)';
            setTimeout(() => {
                lion.style.transform = originalTransform;
                lion.style.filter = '';
            }, 300);
            showFloatingRoar('RAWR! 🦁');
        }

        function showFloatingRoar(text) {
            const floatingText = document.createElement('div');
            floatingText.className = 'floating-roar';
            floatingText.textContent = text;
            document.body.appendChild(floatingText);
            setTimeout(() => {
                floatingText.remove();
            }, 2000);
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            const icon = document.querySelector(`[onclick="togglePassword('${inputId}')"]`);
            icon.textContent = type === 'password' ? '👁️' : '🙈';
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

        function forgotPassword() {
            showNotification('Password reset instructions sent to your email', 'info');
        }

        // Form submissions
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-primary');
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entering Jungle...';
        });

        // Hide alert messages after 4 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alertMessages = document.querySelectorAll('#alertMessage');
            alertMessages.forEach(function(msg) {
                setTimeout(() => {
                    msg.style.opacity = '0';
                    msg.style.transition = 'opacity 0.5s';
                    setTimeout(() => msg.remove(), 600);
                }, 4000);
            });
        });
    </script>
</body>
</html>