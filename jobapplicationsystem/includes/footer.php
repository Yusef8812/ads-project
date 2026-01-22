</main>

<script>
    // Common JS
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            const res = await fetch('api/auth.php?action=logout', { method: 'POST' });
            const data = await res.json();
            if (data.success) {
                window.location.href = 'login.php';
            }
        });
    }

    // Dynamic Active Link
    const currentPath = window.location.pathname.split("/").pop();
    document.querySelectorAll('nav a').forEach(a => {
        if (a.getAttribute('href') === currentPath) {
            a.style.color = 'var(--primary)';
        }
    });
</script>
</body>

</html>