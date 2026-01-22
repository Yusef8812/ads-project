<?php include 'includes/header.php'; ?>

<div class="auth-container" style="max-width: 500px;">
    <h2>Create Account</h2>
    <p>Join the best job portal</p>
    <br>
    <form id="registerForm">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>I am a:</label>
            <select name="role" id="roleSelect" onchange="toggleCompanyFields()">
                <option value="Candidate">Job Seeker (Candidate)</option>
                <option value="Recruiter">Employer (Recruiter)</option>
            </select>
        </div>

        <!-- Recruiter Fields (Hidden by default) -->
        <div id="companyFields"
            style="display: none; border-left: 3px solid var(--primary); padding-left: 1rem; margin-top: 1rem;">
            <h3>Company Details</h3>
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" id="companyName">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="company_description" id="companyDesc" rows="3"></textarea>
            </div>
        </div>

        <script>
            function toggleCompanyFields() {
                const role = document.getElementById('roleSelect').value;
                const fields = document.getElementById('companyFields');
                const nameInput = document.getElementById('companyName');

                if (role === 'Recruiter') {
                    fields.style.display = 'block';
                    nameInput.required = true;
                } else {
                    fields.style.display = 'none';
                    nameInput.required = false;
                }
            }
        </script>
        <button type="submit" class="btn">Register</button>
    </form>
    <p id="msg" style="margin-top:10px;"></p>
</div>

<script>
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        const res = await fetch('api/auth.php?action=register', {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();

        const msg = document.getElementById('msg');
        if (result.success) {
            msg.style.color = 'var(--success)';
            msg.innerText = 'Registration successful! Redirecting to login...';
            setTimeout(() => window.location.href = 'login.php', 2000);
        } else {
            msg.style.color = 'var(--danger)';
            msg.innerText = result.message;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>