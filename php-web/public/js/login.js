const loginForm = document.getElementById('loginForm');
const loginButton = document.getElementById('loginButton');
const message = document.getElementById('msg');

loginForm.addEventListener('submit', async function (event) {
    event.preventDefault();
    message.className = 'alert-box';
    message.textContent = '';

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (email === '' || password === '') {
        message.textContent = 'Please enter your email and password.';
        return;
    }

    loginButton.disabled = true;
    loginButton.textContent = 'Logging in...';

    try {
        const res = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const data = await res.json();

        if (res.ok && data.success) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('admin', JSON.stringify({ name: data.name, email: data.email }));
            window.location.href = 'dashboard.html';
        } else {
            message.textContent = data.message || 'Invalid credentials.';
        }
    } catch (err) {
        message.textContent = 'Connection error. Is the backend running?';
    } finally {
        loginButton.disabled = false;
        loginButton.textContent = 'Login';
    }
});

function showTab() {
    const isSignup = window.location.hash === '#signup';
    document.getElementById('loginPanel').hidden = isSignup;
    document.getElementById('signupPanel').hidden = !isSignup;
    document.getElementById('tabLogin').classList.toggle('active', !isSignup);
    document.getElementById('tabSignup').classList.toggle('active', isSignup);
    document.getElementById('msg').textContent = '';
    document.getElementById('suMsg').textContent = '';
}

window.addEventListener('hashchange', showTab);
showTab();
