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
    <title>Login - RAWR Casino</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Update font import -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;900&display=swap" rel="stylesheet">
    <style>
        /* Update CSS variables */
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;  /* Update font family */
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-light);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow-x: hidden;
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
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            background: rgba(18, 18, 18, 0.95);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.7);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            margin: 1rem;
            animation: fadeIn 0.8s ease-out;
            z-index: 10;
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
            font-family: 'Poppins', sans-serif;
            font-size: 2.8rem;
            color: var(--gold-primary);
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.6);
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-header p {
            color: var(--text-muted);
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.8rem;
            animation: fadeIn 1s ease-out;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--gold-secondary);
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 1.2rem;
            background: rgba(30, 60, 30, 0.7);
            border: 1px solid var(--jungle-light);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 1.1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--gold-primary);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
        }

        .form-group .password-toggle {
            position: absolute;
            right: 15px;
            top: 45px;
            cursor: pointer;
            color: var(--text-muted);
            transition: color 0.3s;
            font-size: 1.2rem;
        }

        .form-group .password-toggle:hover {
            color: var(--gold-secondary);
        }

        .btn {
            padding: 1.2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-family: 'Orbitron', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: var(--bg-darker);
            box-shadow: 0 6px 0 rgba(180, 150, 40, 0.8);
        }

        .btn-primary:hover {
            background: var(--gold-secondary);
            transform: translateY(-3px);
            box-shadow: 0 9px 0 rgba(180, 150, 40, 0.8);
        }

        .btn-primary:active {
            transform: translateY(2px);
            box-shadow: 0 3px 0 rgba(180, 150, 40, 0.8);
        }

        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            font-size: 0.95rem;
        }

        .auth-links a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.3s;
            position: relative;
        }

        .auth-links a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--gold-secondary);
            transition: width 0.3s;
        }

        .auth-links a:hover::after {
            width: 100%;
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
    </style>
</head>
<body>
    <!-- Background elements -->
    <div class="jungle-bg"></div>
    <div class="jungle-overlay"></div>
    <div class="lion-decoration"></div>
    <div class="particles" id="particles"></div>

    <!-- Login Container -->
    <div class="auth-container">
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($logout): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($logout) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($registered): ?>
            <div class="message success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($registered) ?>
            </div>
        <?php endif; ?>
        
        <div class="logo-header">
            <h1>RAWR</h1>
            <p>The Lion's Game - Jungle Casino</p>
        </div>
        
        <form action="/RAWR/backend/auth/login_process.php" method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <span class="password-toggle" onclick="togglePassword('password')">👁️</span>
            </div>
            
            <button type="submit" class="btn btn-primary">Enter the Jungle</button>
            
            <div class="auth-links">
                <a href="register.php">Create an Account</a>
                <a href="#" onclick="showForgotPassword()">Forgot Password?</a>
            </div>
        </form>
    </div>

    <script>
        // Auth JS - Integrated directly into the page
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                const size = Math.random() * 10 + 5;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const animationDelay = Math.random() * 5;
                const hue = 40 + Math.random() * 20;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.animationDelay = `${animationDelay}s`;
                particle.style.backgroundColor = `hsl(${hue}, 70%, 60%)`;
                particle.style.opacity = 0.2 + Math.random() * 0.3;
                
                particlesContainer.appendChild(particle);
            }
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            const icon = document.querySelector(`[onclick="togglePassword('${inputId}')"]`);
            icon.textContent = type === 'password' ? '👁️' : '🔒';
        }

        function showMessage(text, type) {
            const messageArea = document.createElement('div');
            messageArea.className = `message ${type}`;
            
            const icon = document.createElement('i');
            icon.className = type === 'error' ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';
            
            messageArea.appendChild(icon);
            messageArea.appendChild(document.createTextNode(text));
            
            const container = document.querySelector('.auth-container');
            container.insertBefore(messageArea, container.firstChild);
            
            setTimeout(() => {
                messageArea.style.opacity = '0';
                setTimeout(() => {
                    messageArea.remove();
                }, 500);
            }, 5000);
        }

        function showForgotPassword() {
            showMessage("Please contact support to reset your password", "error");
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
        });
    </script>
</body>
</html>