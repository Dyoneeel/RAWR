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
    <title>Register - RAWR Casino</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* Auth CSS - Integrated directly into the page */
        :root {
            --jungle-dark: #0a1f0a;
            --jungle-medium: #1e3c1e;
            --jungle-light: #2d5a2d;
            --gold-primary: #d4af37;
            --gold-secondary: #f9e076;
            --accent-red: #d9534f;
            --accent-green: #5cb85c;
            --text-light: #f8f9fa;
            --text-muted: #adb5bd;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--jungle-dark), var(--jungle-medium));
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
            background: rgba(10, 31, 10, 0.85);
            border: 2px solid var(--gold-primary);
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
            font-family: 'Orbitron', sans-serif;
            font-size: 2.8rem;
            color: var(--gold-primary);
            text-transform: uppercase;
            letter-spacing: 3px;
            text-shadow: 0 0 15px rgba(212, 175, 55, 0.6);
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, var(--gold-primary), var(--gold-secondary));
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
            background: var(--gold-primary);
            color: var(--jungle-dark);
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
            color: var(--gold-secondary);
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
            background: rgba(217, 83, 79, 0.2);
            border: 1px solid var(--accent-red);
            color: var(--accent-red);
        }

        .message.success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
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

    <!-- Register Container -->
    <div class="auth-container">
        <?php if ($error): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="logo-header">
            <h1>Join the Pride</h1>
            <p>Create your RAWR Casino account</p>
        </div>
        
        <form action="/RAWR/backend/auth/register_process.php" method="post" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            
            <div class="form-group">
                <label for="reg-username">Username</label>
                <input type="text" id="reg-username" name="username" required>
                <small style="display: block; margin-top: 5px; color: var(--text-muted);">4-20 characters, letters and numbers only</small>
            </div>
            
            <div class="form-group">
                <label for="reg-email">Email Address</label>
                <input type="email" id="reg-email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="reg-password">Password</label>
                <input type="password" id="reg-password" name="password" required>
                <small style="display: block; margin-top: 5px; color: var(--text-muted);">Minimum 8 characters with uppercase, lowercase, and number</small>
            </div>
            
            <div class="form-group">
                <label for="reg-confirm">Confirm Password</label>
                <input type="password" id="reg-confirm" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label for="referral">Referral Code (Optional)</label>
                <input type="text" id="referral" name="referral_code">
            </div>
            
            <div class="terms-group">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I accept the <a href="terms.php" target="_blank">Terms & Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
            </div>
            
            <button type="submit" class="btn btn-primary">Create Account</button>
            
            <div class="auth-links">
                <a href="login.php">Already have an account? Login</a>
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', () => {
            createParticles();
            
            // Add password toggle functionality
            document.querySelectorAll('.password-toggle').forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const input = this.previousElementSibling;
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    this.textContent = type === 'password' ? '👁️' : '🔒';
                });
            });
        });
    </script>
</body>
</html>