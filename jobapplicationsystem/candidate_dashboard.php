<?php include 'includes/header.php'; ?>

<!-- Protected Route Check in JS or PHP? PHP is safer for initial load -->
<?php if (($role ?? '') !== 'Candidate') {
    header("Location: login.php");
    exit;
} ?>

<h2>My Applications</h2>
<p style="color:var(--text-muted); margin-bottom: 2rem;">Track the status of your job applications</p>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Job Title</th>
                <th>Company</th>
                <th>Applied Date</th>
                <th>Status</th>
                <th>Resume</th>
            </tr>
        </thead>
        <tbody id="appTable">
            <tr>
                <td colspan="5">Loading...</td>
            </tr>
        </tbody>
    </table>
</div>

<script>
    async function loadApplications() {
        const res = await fetch('api/candidate.php?action=my_applications');
        const result = await res.json();

        const tbody = document.getElementById('appTable');
        tbody.innerHTML = '';

        if (result.success && result.data.length > 0) {
            result.data.forEach(app => {
                let statusBadge = '';
                if (app.Status === 'Pending') statusBadge = '<span class="badge" style="background:#f59e0b; color:black">Pending</span>';
                else if (app.Status === 'Accepted') statusBadge = '<span class="badge badge-open">Hired/Accepted</span>';
                else if (app.Status === 'Rejected') statusBadge = '<span class="badge badge-closed">Rejected</span>';
                else statusBadge = `<span class="badge" style="background:var(--primary)">${app.Status}</span>`;

                tbody.innerHTML += `
                <tr>
                    <td><a href="job_details.php?id=${app.JobID}" style="color:var(--primary); font-weight:600;">${app.Title}</a></td>
                    <td>${app.CompanyName}</td>
                    <td>${new Date(app.AppliedAt).toLocaleDateString()}</td>
                    <td>${statusBadge}</td>
                    <td><a href="${app.ResumePath}" target="_blank" style="text-decoration:underline; font-size:0.9rem;">View Resume</a></td>
                </tr>
            `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">You haven\'t applied to any jobs yet.</td></tr>';
        }
    }
    loadApplications();
</script>

<?php include 'includes/footer.php'; ?>