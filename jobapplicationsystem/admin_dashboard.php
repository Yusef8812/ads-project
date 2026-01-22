<?php include 'includes/header.php'; ?>
<?php if (($role ?? '') !== 'Admin') {
    header("Location: login.php");
    exit;
} ?>

<h2>Admin Dashboard</h2>
<div class="dashboard-grid">
    <div class="card stat-card">
        <h3 id="statUsers">0</h3>
        <p>Total Users</p>
    </div>
    <div class="card stat-card">
        <h3 id="statJobs">0</h3>
        <p>Total Jobs</p>
    </div>
    <div class="card stat-card">
        <h3 id="statApps">0</h3>
        <p>Total Applications</p>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 2fr; gap:1.5rem;">
    <!-- Categories Manager -->
    <div class="card">
        <h3>Categories</h3>
        <br>
        <form id="addCatForm" style="display:flex; gap:0.5rem; margin-bottom:1rem;">
            <input type="text" name="name" placeholder="New Category" required>
            <button type="submit" class="btn btn-sm">Add</button>
        </form>
        <ul id="catList" style="max-height: 300px; overflow-y: auto;">
            <li>Loading...</li>
        </ul>
    </div>

    <!-- Users Manager -->
    <div class="card">
        <h3>User Management</h3>
        <br>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="userList">
                    <tr>
                        <td colspan="4">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Advanced Insights (Subquery Feature) -->
<div class="card" style="margin-top: 1.5rem;">
    <h3>Advanced Insights: High Interest Jobs</h3>
    <p>Jobs with more applications than the average.</p>
    <br>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Application Count</th>
                </tr>
            </thead>
            <tbody id="highInterestList">
                <tr>
                    <td colspan="3">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Stats
    async function loadStats() {
        const res = await fetch('api/admin.php?action=get_stats');
        const result = await res.json();
        if (result.success) {
            document.getElementById('statUsers').innerText = result.data.users;
            document.getElementById('statJobs').innerText = result.data.jobs;
            document.getElementById('statApps').innerText = result.data.applications;
        }
    }

    // Categories
    async function loadCategories() {
        const res = await fetch('api/admin.php?action=get_categories');
        const result = await res.json();
        const list = document.getElementById('catList');
        list.innerHTML = '';

        if (result.success && result.data) {
            result.data.forEach(cat => {
                const li = document.createElement('li');
                li.style.cssText = 'display:flex; justify-content:space-between; padding:0.5rem; border-bottom:1px solid rgba(255,255,255,0.05);';
                li.innerHTML = `<span>${cat.CategoryName}</span> <button class="btn btn-sm btn-danger" style="padding:0.2rem 0.5rem;" onclick="deleteCat(${cat.CategoryID})">x</button>`;
                list.appendChild(li);
            });
        }
    }

    document.getElementById('addCatForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        const res = await fetch('api/admin.php?action=add_category', { method: 'POST', body: JSON.stringify(data) });
        if ((await res.json()).success) { e.target.reset(); loadCategories(); }
    });

    async function deleteCat(id) {
        if (!confirm('Delete category?')) return;
        const res = await fetch('api/admin.php?action=delete_category', { method: 'POST', body: JSON.stringify({ id }) });
        if ((await res.json()).success) loadCategories();
    }

    // Users
    // Users
    async function loadUsers(page = 1) {
        const limit = 5;
        const res = await fetch(`api/admin.php?action=get_users&page=${page}&limit=${limit}`);
        const result = await res.json();
        const tbody = document.getElementById('userList');

        let paginationRow = document.getElementById('userPaginationRow');
        if (!paginationRow) {
            const tfoot = document.createElement('tfoot');
            paginationRow = document.createElement('tr');
            paginationRow.id = 'userPaginationRow';
            const td = document.createElement('td');
            td.colSpan = 4;
            td.id = 'userPaginationCell';
            paginationRow.appendChild(td);
            tfoot.appendChild(paginationRow);
            tbody.parentNode.appendChild(tfoot);
        }
        const paginationCell = document.getElementById('userPaginationCell');

        tbody.innerHTML = '';
        paginationCell.innerHTML = '';

        if (result.success && result.data.users && result.data.users.length > 0) {
            result.data.users.forEach(u => {
                tbody.innerHTML += `
                <tr>
                    <td>${u.Username}</td>
                    <td>${u.Email}</td>
                    <td>${u.Role}</td>
                    <td>
                        ${u.Role !== 'Admin' ? `<button class="btn btn-sm btn-danger" onclick="deleteUser(${u.UserID})">Delete</button>` : '-'}
                    </td>
                </tr>
            `;
            });

            if (result.data.pagination) {
                renderUserPagination(result.data.pagination, paginationCell);
            }
        }
    }

    function renderUserPagination(meta, container) {
        if (meta.total_pages <= 1) return;

        let html = '<div style="display: flex; gap: 0.5rem; justify-content: center; padding: 1rem;">';

        if (meta.current_page > 1) {
            html += `<button class="btn btn-sm" onclick="loadUsers(${meta.current_page - 1})">&laquo; Prev</button>`;
        }

        for (let i = 1; i <= meta.total_pages; i++) {
            if (i === 1 || i === meta.total_pages || (i >= meta.current_page - 2 && i <= meta.current_page + 2)) {
                const activeStyle = i === meta.current_page ? 'background: var(--primary); border-color: var(--primary);' : 'background: transparent; color: var(--text-muted);';
                html += `<button class="btn btn-sm" style="${activeStyle}" onclick="loadUsers(${i})">${i}</button>`;
            } else if (i === meta.current_page - 3 || i === meta.current_page + 3) {
                html += `<span style="color:white; align-self:center;">...</span>`;
            }
        }

        if (meta.current_page < meta.total_pages) {
            html += `<button class="btn btn-sm" onclick="loadUsers(${meta.current_page + 1})">Next &raquo;</button>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    async function deleteUser(id) {
        if (!confirm('Permanently delete user?')) return;
        const res = await fetch('api/admin.php?action=delete_user', { method: 'POST', body: JSON.stringify({ id }) });
        if ((await res.json()).success) { loadUsers(); loadStats(); }
    }

    async function loadHighInterestJobs() {
        const res = await fetch('api/admin.php?action=get_high_interest_jobs');
        const result = await res.json();
        const tbody = document.getElementById('highInterestList');
        tbody.innerHTML = '';

        if (result.success && result.data && result.data.length > 0) {
            result.data.forEach(job => {
                tbody.innerHTML += `
                <tr>
                    <td>${job.Title}</td>
                    <td>${job.CompanyName}</td>
                    <td>${job.AppCount}</td>
                </tr>
            `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="3">No high interest jobs found yet.</td></tr>';
        }
    }

    loadStats();
    loadCategories();
    loadUsers();
    loadHighInterestJobs();
</script>

<?php include 'includes/footer.php'; ?>