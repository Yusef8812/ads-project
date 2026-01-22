<?php include 'includes/header.php'; ?>

<div style="text-align: center; margin-bottom: 2rem;">
    <h1 style="font-size: 3rem; margin-bottom: 1rem;">Find Your Dream Job</h1>
    <div class="filter-container">
        <div class="filter-row">
            <input type="text" id="searchInput" class="filter-input" placeholder="Search title or company...">
            <select id="catFilter" class="filter-input">
                <option value="">Category</option>
            </select>
            <select id="typeFilter" class="filter-input">
                <option value="">Type</option>
                <option>Full-time</option>
                <option>Part-time</option>
                <option>Contract</option>
                <option>Internship</option>
            </select>
        </div>
        <button class="btn filter-btn" onclick="loadJobs()">Search Jobs</button>
    </div>
</div>

<div id="jobList">
    <!-- Jobs will be loaded here -->
    <p style="text-align:center; color: var(--text-muted)">Loading jobs...</p>
</div>

<script>
    async function loadCategories() {
        // Reuse recruiter API or create public one? Recruiter API checks auth?
        // Wait, recruiter API checks for Recruiter role. Candidate API might be better or public.
        // Actually, let's just make a quick public action in candidate or admin.
        // candidate.php checks role? Yes: if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Candidate')
        // BUT it allows GET if not logged in? "if ($_SERVER['REQUEST_METHOD'] !== 'GET')" -> this implies GET is allowed for public?
        // Let's check candidate.php auth block again.
        // "if (!isset... || role !== Candidate) { if (METHOD !== GET) { error; exit; } }" 
        // This means GET is allowed for anyone! Great.
        // But candidate.php doesn't have get_categories action. admin.php has.
        // Let's add get_categories to candidate.php for public use.

        // Temporarily, let's try to hit admin.php? No that's secured.
        // I need to add 'get_categories' to candidate.php first.
        const res = await fetch('api/candidate.php?action=get_categories');
        const result = await res.json();
        const select = document.getElementById('catFilter');
        select.innerHTML = '<option value="">Category</option>'; // Reset with default
        if (result.success && result.data) {
            result.data.forEach(cat => {
                const opt = document.createElement('option');
                opt.value = cat.CategoryID;
                opt.innerText = cat.CategoryName;
                select.appendChild(opt);
            });
        }
    }

    let currentPage = 1;

    async function loadJobs(page = 1) {
        currentPage = page;
        const query = document.getElementById('searchInput').value;
        const cat = document.getElementById('catFilter').value;
        const type = document.getElementById('typeFilter').value;
        const limit = 5; 

        try {
            const res = await fetch(`api/candidate.php?action=search_jobs&q=${query}&cat=${cat}&type=${type}&page=${page}&limit=${limit}`);
            const result = await res.json();

            const list = document.getElementById('jobList');
            // Check if pagination container exists, if not create it dynamically or assume user deleted it from HTML and we inject?
            // Better to rely on HTML existence. I will ensure HTML is updated too in next step if this fails, but usually safe to assume element ID exists if I add it back.
            // Wait, user deleted the HTML div too in step 533. I need to re-add that too.
            // Let's assume I will re-add the DIV in index.php via a separate call or handle it here if I find the insertion point.
            // For now, let's just fix the JS logic.
            
            let paginationContainer = document.getElementById('pagination-controls');
            if(!paginationContainer) {
                 // Auto-create if missing (User might have deleted it)
                 paginationContainer = document.createElement('div');
                 paginationContainer.id = 'pagination-controls';
                 list.parentNode.insertBefore(paginationContainer, list.nextSibling);
            }

            list.innerHTML = '';
            paginationContainer.innerHTML = '';

            if (result.success && result.data.jobs && result.data.jobs.length > 0) {
                result.data.jobs.forEach(job => {
                    const div = document.createElement('div');
                    div.className = 'job-item';
                    div.innerHTML = `
                    <div class="job-main">
                        <h3>${job.Title}</h3>
                        <p class="job-sub">${job.CompanyName} &bull; ${job.CategoryName} &bull; ${job.JobType}</p>
                        <p class="job-sub">Salary: ${job.Salary}</p>
                    </div>
                    <div>
                         <a href="job_details.php?id=${job.JobID}" class="btn btn-sm">View Details</a>
                    </div>
                `;
                    list.appendChild(div);
                });
                
                if (result.data.pagination) {
                    renderPagination(result.data.pagination);
                }
                
            } else {
                list.innerHTML = '<p style="text-align:center;">No jobs found matching your criteria.</p>';
            }
        } catch (error) {
            console.error('Error loading jobs:', error);
        }
    }

    function renderPagination(meta) {
        const container = document.getElementById('pagination-controls');
        if (!container || meta.total_pages <= 1) return;

        let html = '<div style="display: flex; gap: 0.5rem; justify-content: center; margin-top: 1rem;">';
        
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

    // Event Listeners for Real-time Search
    document.getElementById('searchInput').addEventListener('input', () => loadJobs(1));
    document.getElementById('catFilter').addEventListener('change', () => loadJobs(1));
    document.getElementById('typeFilter').addEventListener('change', () => loadJobs(1));

    // Load initially
    loadCategories();
    loadJobs(1);
</script>

<?php include 'includes/footer.php'; ?>