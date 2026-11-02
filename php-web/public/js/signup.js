const signupForm = document.getElementById('signupForm');
const signupMessage = document.getElementById('suMsg');

signupForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const name = document.getElementById('suName').value.trim();
    const email = document.getElementById('suEmail').value.trim().toLowerCase();
    const password = document.getElementById('suPassword').value;
    const confirm = document.getElementById('suConfirm').value;

    signupMessage.className = 'alert-box';

    if (name === '' || email === '' || password === '' || confirm === '') {
        signupMessage.textContent = 'Please fill in all fields.';
        return;
    }

    if (password.length < 6) {
        signupMessage.textContent = 'Password must be at least 6 characters.';
        return;
    }

    if (password !== confirm) {
        signupMessage.textContent = 'Passwords do not match.';
        return;
    }

    const users = JSON.parse(localStorage.getItem('users')) || [];

    if (email === 'admin@kimfresh.com' || users.some(u => u.email === email)) {
        signupMessage.textContent = 'That email is already registered.';
        return;
    }

    users.push({ name: name, email: email, password: password, role: 'admin' });
    localStorage.setItem('users', JSON.stringify(users));

    signupForm.reset();

    const loginMessage = document.getElementById('msg');
    loginMessage.className = 'alert-box success';
    loginMessage.textContent = 'Account created! You can log in now.';
    document.getElementById('email').value = email;
    window.location.hash = '#login';
    document.getElementById('password').focus();
});
