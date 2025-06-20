document.addEventListener('DOMContentLoaded', function() {
    // Password visibility toggle
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.textContent = type === 'password' ? '👁️' : '🔒';
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const inputs = this.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.classList.add('error');
                    
                    setTimeout(() => {
                        input.classList.remove('error');
                    }, 2000);
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showMessage('Please fill in all required fields', 'error');
            }
        });
    });
    
    // Display flash messages
    const message = document.querySelector('.message');
    if (message) {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    }
});

function showMessage(text, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = text;
    
    const container = document.querySelector('.auth-container');
    container.insertBefore(messageDiv, container.firstChild);
    
    setTimeout(() => {
        messageDiv.style.opacity = '0';
        setTimeout(() => {
            messageDiv.remove();
        }, 500);
    }, 5000);
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
    
    const icon = document.querySelector(`[data-toggle="${inputId}"]`);
    icon.textContent = type === 'password' ? '👁️' : '🔒';
}