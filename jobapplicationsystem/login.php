<?php include 'includes/header.php'; ?>

<div class="auth-container">
    <h2>Welcome Back</h2>
    <p>Login to your account</p>
    <br>
    <form id="loginForm">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn">Login</button>
    </form>
    <br>
    <p style="font-size:0.9rem">Don't have an account? <a href="register.php" style="color:var(--primary)">Register</a>
    </p>
    <p id="msg" style="margin-top:10px; color:var(--danger)"></p>
</div>

<script>
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await fetch('api/auth.php?action=login', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.success) {
            window.location.href = result.data.redirect;
        } else {
            document.getElementById('msg').innerText = result.message;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>