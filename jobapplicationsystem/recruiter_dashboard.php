<?php include 'includes/header.php'; ?>
<?php if (($role ?? '') !== 'Recruiter') {
    header("Location: login.php");
    exit;
} ?>

<div class="dashboard-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <h2>My Job Postings</h2>
        <div style="display:flex; gap: 10px;">
            <select id="filterCategory"
                style="padding: 0.5rem; border-radius: 5px; background: #1e293b; color: white; border: 1px solid #334155;"
                onchange="loadJobs()">
                <option value="">All Categories</option>
            </select>
            <button class="btn" onclick="showPostJobModal()">+ Post Job</button>
        </div>
    </div>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Posted Date</th>
                <th>Status</th>
                <th>Applicants</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="jobsTable">
            <tr>
                <td colspan="6">Loading...</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Post Job Modal -->
<div id="jobModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Post a Job</h2>
        <form id="postJobForm">
            <input type="hidden" name="job_id" id="jobIdInput">
            <input type="hidden" name="action_type" id="actionTypeInput" value="create_job">

            <div class="form-group">
                <label>Job Title</label>
                <input type="text" name="title" id="titleInput" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="descInput" rows="4" required></textarea>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label>Salary</label>
                    <input type="text" name="salary" id="salaryInput" required>
                </div>
                <div class="form-group">
                    <label>Job Type</label>
                    <select name="job_type" id="typeInput">
                        <option>Full-time</option>
                        <option>Part-time</option>
                        <option>Contract</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="catInput">
                        <option value="">Loading...</option>
                    </select>
                </div>
            </div>
            <!-- Hack: Passing recruiter's company details since in API we check/create -->
            <input type="hidden" name="company_name" value="My Recruiter Company">

            <button type="submit" class="btn" id="modalSubmitBtn">Post Job</button>
            <button type="button" class="btn" style="background:#475569; margin-top:0.5rem;"
                onclick="closeJobModal()">Cancel</button>
        </form>
    </div>
</div>

<!-- View Applicants Modal -->
<div id="applicantsModal" class="modal">
    <div class="modal-content" style="max-width:800px; width:95%;">
        <h2>Applicants</h2>
        <br>
        <div style="max-height: 60vh; overflow-y: auto; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
            <table style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="position: sticky; top: 0; background: #1e293b; z-index: 10;">Name</th>
                        <th style="position: sticky; top: 0; background: #1e293b; z-index: 10;">Email</th>
                        <th style="position: sticky; top: 0; background: #1e293b; z-index: 10;">Date</th>
                        <th style="position: sticky; top: 0; background: #1e293b; z-index: 10;">Status</th>
                        <th style="position: sticky; top: 0; background: #1e293b; z-index: 10;">Action</th>
                    </tr>
                </thead>
                <tbody id="applicantsTable"></tbody>
            </table>
        </div>
        <br>
        <button class="btn"
            onclick="document.getElementById('applicantsModal').classList.remove('active')">Close</button>
    </div>
</div>

<script>
    let currentJobs = []; // Store fetched jobs to easily populate edit form

    async function loadCategories() {
        const res = await fetch('api/recruiter.php?action=get_categories');
        const result = await res.json();
        const select = document.getElementById('catInput');
        const filterSelect = document.getElementById('filterCategory');

        select.innerHTML = '';
        // Keep default "All Categories" in filter

        if (result.success && result.data) {
            result.data.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.CategoryID;
                opt.innerText = cat.CategoryName;
                select.appendChild(opt);

                // Add to filter too
                const opt2 = opt.cloneNode(true);
                filterSelect.appendChild(opt2);
            });
        }
    }

    async function loadJobs(page = 1) {
        const catId = document.getElementById('filterCategory') ? document.getElementById('filterCategory').value : '';
        const limit = 5;
        const res = await fetch(`api/recruiter.php?action=get_jobs&category_id=${catId}&page=${page}&limit=${limit}`);
        const result = await res.json();
        const tbody = document.getElementById('jobsTable');
        // We need a place for pagination controls. Let's find or create it.
        let paginationRow = document.getElementById('paginationRow');
        if (!paginationRow) {
            const tfoot = document.createElement('tfoot');
            paginationRow = document.createElement('tr');
            paginationRow.id = 'paginationRow';
            const td = document.createElement('td');
            td.colSpan = 6;
            td.id = 'paginationCell';
            paginationRow.appendChild(td);
            tfoot.appendChild(paginationRow);
            tbody.parentNode.appendChild(tfoot);
        }
        const paginationCell = document.getElementById('paginationCell');

        tbody.innerHTML = '';
        paginationCell.innerHTML = '';

        if (result.success && result.data.jobs && result.data.jobs.length > 0) {
            currentJobs = result.data.jobs; // Cache for editing
            result.data.jobs.forEach(job => {
                const statusClass = job.Status === 'Open' ? 'badge-open' : 'badge-closed';
                const toggleBtnText = job.Status === 'Open' ? 'Close Job' : 'Re-open';

                tbody.innerHTML += `
                <tr>
                    <td>${job.Title}</td>
                    <td>${job.CategoryName || 'N/A'}</td>
                    <td>${new Date(job.PostedDate).toLocaleDateString()}</td>
                    <td><span class="badge ${statusClass}">${job.Status}</span></td>
                    <td>${job.AppCount}</td>
                    <td>
                        <div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <button class="btn btn-sm" onclick="editJob(${job.JobID})" style="background:#3b82f6;">Edit</button>
                            <button class="btn btn-sm" onclick="deleteJob(${job.JobID})" style="background:#ef4444;">Delete</button>
                            <button class="btn btn-sm" onclick="viewApplicants(${job.JobID})">Applicants</button>
                            <button class="btn btn-sm" style="background:#475569;" onclick="toggleStatus(${job.JobID})">${toggleBtnText}</button>
                        </div>
                    </td>
                </tr>
            `;
            });

            if (result.data.pagination) {
                renderPagination(result.data.pagination, paginationCell);
            }

        } else {
            tbody.innerHTML = '<tr><td colspan="6">No jobs posted.</td></tr>';
        }
    }

    function renderPagination(meta, container) {
        if (meta.total_pages <= 1) return;

        let html = '<div style="display: flex; gap: 0.5rem; justify-content: center; padding: 1rem;">';

        if (meta.current_page > 1) {
            html += `<button class="btn btn-sm" onclick="loadJobs(${meta.current_page - 1})">&laquo; Prev</button>`;
        }

        for (let i = 1; i <= meta.total_pages; i++) {
            if (i === 1 || i === meta.total_pages || (i >= meta.current_page - 2 && i <= meta.current_page + 2)) {
                const activeStyle = i === meta.current_page ? 'background: var(--primary); border-color: var(--primary);' : 'background: transparent; color: var(--text-muted);';
                html += `<button class="btn btn-sm" style="${activeStyle}" onclick="loadJobs(${i})">${i}</button>`;
            } else if (i === meta.current_page - 3 || i === meta.current_page + 3) {
                html += `<span style="color:white; align-self:center;">...</span>`;
            }
        }

        if (meta.current_page < meta.total_pages) {
            html += `<button class="btn btn-sm" onclick="loadJobs(${meta.current_page + 1})">Next &raquo;</button>`;
        }

        html += '</div>';
        container.innerHTML = html;
    }

    async function deleteJob(id) {
        if (!confirm('Are you sure you want to delete this job? This cannot be undone.')) return;
        const res = await fetch('api/recruiter.php?action=delete_job', {
            method: 'POST', body: JSON.stringify({ job_id: id })
        });
        const result = await res.json();
        if (result.success) {
            loadJobs();
        } else {
            alert(result.message);
        }
    }

    function showPostJobModal() {
        // Reset form for create
        document.getElementById('postJobForm').reset();
        document.getElementById('modalTitle').innerText = 'Post a Job';
        document.getElementById('modalSubmitBtn').innerText = 'Post Job';
        document.getElementById('actionTypeInput').value = 'create_job';
        document.getElementById('jobIdInput').value = '';

        document.getElementById('jobModal').classList.add('active');
    }

    function editJob(id) {
        const job = currentJobs.find(j => j.JobID == id);
        if (!job) return;

        // Populate form
        document.getElementById('modalTitle').innerText = 'Edit Job';
        document.getElementById('modalSubmitBtn').innerText = 'Update Job';
        document.getElementById('actionTypeInput').value = 'update_job';
        document.getElementById('jobIdInput').value = job.JobID;

        document.getElementById('titleInput').value = job.Title;
        document.getElementById('descInput').value = job.Description;
        document.getElementById('salaryInput').value = job.Salary;
        document.getElementById('typeInput').value = job.JobType;
        document.getElementById('catInput').value = job.CategoryID;

        document.getElementById('jobModal').classList.add('active');
    }

    function closeJobModal() { document.getElementById('jobModal').classList.remove('active'); }

    loadCategories();

    document.getElementById('postJobForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(e.target).entries());
        const action = document.getElementById('actionTypeInput').value; // create_job or update_job

        const res = await fetch(`api/recruiter.php?action=${action}`, {
            method: 'POST',
            body: JSON.stringify(data)
        });
        const result = await res.json();
        if (result.success) {
            closeJobModal();
            loadJobs();
        } else {
            alert(result.message);
        }
    });

    async function toggleStatus(id) {
        if (!confirm('Change job status?')) return;
        const res = await fetch('api/recruiter.php?action=toggle_status', {
            method: 'POST', body: JSON.stringify({ job_id: id })
        });
        if ((await res.json()).success) loadJobs();
    }

    async function viewApplicants(jobId) {
        const res = await fetch(`api/recruiter.php?action=get_applicants&job_id=${jobId}`);
        const result = await res.json();
        const tbody = document.getElementById('applicantsTable');
        tbody.innerHTML = '';

        document.getElementById('applicantsModal').classList.add('active');

        if (result.success && result.data.length > 0) {
            result.data.forEach(app => {
                tbody.innerHTML += `
                <tr>
                    <td>${app.Username}</td>
                    <td>${app.Email}</td>
                    <td>${new Date(app.AppliedAt).toLocaleDateString()}</td>
                    <td>${app.Status}</td>
                    <td>
                        <a href="${app.ResumePath}" target="_blank" style="margin-right:10px;">Resume</a>
                        <button class="btn btn-sm btn-success" onclick="updateApp(${app.ApplicationID}, 'Accepted')">Hire</button>
                        <button class="btn btn-sm btn-danger" onclick="updateApp(${app.ApplicationID}, 'Rejected')">Reject</button>
                    </td>
                </tr>
            `;
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5">No applicants yet.</td></tr>';
        }
    }

    async function updateApp(appId, status) {
        if (!confirm(`Mark this applicant as ${status}?`)) return;
        const res = await fetch('api/recruiter.php?action=update_application', {
            method: 'POST', body: JSON.stringify({ application_id: appId, status: status })
        });
        if ((await res.json()).success) {
            alert('Updated!');
            // Ideally reload modal data, but lazy reload here:
            document.getElementById('applicantsModal').classList.remove('active');
            loadJobs(); // update counts
        }
    }

    loadJobs();
</script>

<?php include 'includes/footer.php'; ?>