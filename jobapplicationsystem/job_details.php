<?php
require_once 'api/db.php';
include 'includes/header.php';

$jobId = $_GET['id'] ?? 0;
$pdo = getDBConnection();

// Fetch Job Details manually here for simplicity or add 'get_job_details' to API
$stmt = $pdo->prepare("
    SELECT j.*, c.CompanyName, c.Description as CompanyDesc, c.Logo, cat.CategoryName 
    FROM Jobs j 
    JOIN Companies c ON j.CompanyID = c.CompanyID 
    JOIN Categories cat ON j.CategoryID = cat.CategoryID
    WHERE j.JobID = ?
");
$stmt->execute([$jobId]);
$job = $stmt->fetch();

if (!$job) {
    echo "<div style='text-align:center; margin-top:50px;'><h2>Job not found</h2></div>";
    include 'includes/footer.php';
    exit;
}

$isClosed = $job['Status'] === 'Closed';
$isCandidate = isset($_SESSION['role']) && $_SESSION['role'] === 'Candidate';
$isLoggedIn = isset($_SESSION['user_id']);
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h1>
                <?= htmlspecialchars($job['Title']) ?>
            </h1>
            <?php if ($isClosed): ?>
                <span class="badge badge-closed">Position Filled</span>
            <?php else: ?>
                <span class="badge badge-open">Hiring Open</span>
            <?php endif; ?>
        </div>
        <p style="color:var(--primary); font-weight:600; margin-top:0.5rem;">
            <?= htmlspecialchars($job['CompanyName']) ?> &bull;
            <?= htmlspecialchars($job['CategoryName']) ?>
        </p>

        <hr style="margin: 1.5rem 0; border-color:rgba(255,255,255,0.1);">

        <div style="margin-bottom: 2rem;">
            <h3>Job Description</h3>
            <p>
                <?= nl2br(htmlspecialchars($job['Description'])) ?>
            </p>
        </div>

        <div style="margin-bottom: 2rem;">
            <h3>Details</h3>
            <ul style="list-style: disc; margin-left: 1.5rem; color: var(--text-muted);">
                <li><strong>Salary:</strong>
                    <?= htmlspecialchars($job['Salary']) ?>
                </li>
                <li><strong>Type:</strong>
                    <?= htmlspecialchars($job['JobType']) ?>
                </li>
                <li><strong>Posted:</strong>
                    <?= date('M d, Y', strtotime($job['PostedDate'])) ?>
                </li>
            </ul>
        </div>

        <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 8px;">
            <h3>About the Company</h3>
            <p>
                <?= nl2br(htmlspecialchars($job['CompanyDesc'])) ?>
            </p>
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <?php if ($isClosed): ?>
                <button class="btn" disabled style="background: var(--text-muted); cursor: not-allowed;">Applications
                    Closed</button>
            <?php elseif ($isCandidate): ?>
                <button class="btn" onclick="openModal()">Apply Now</button>
            <?php elseif ($isLoggedIn): ?>
                <p>Only Candidates can apply.</p>
            <?php else: ?>
                <a href="login.php" class="btn">Login to Apply</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Application Modal -->
<div id="applyModal" class="modal">
    <div class="modal-content">
        <h2>Apply for
            <?= htmlspecialchars($job['Title']) ?>
        </h2>
        <br>
        <form id="applyForm">
            <input type="hidden" name="job_id" value="<?= $job['JobID'] ?>">
            <div class="form-group">
                <label>Resume (PDF/DOC Only)</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx" required>
            </div>
            <div style="display:flex; gap:1rem;">
                <button type="button" class="btn" style="background:#475569" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn">Submit Application</button>
            </div>
        </form>
        <p id="applyMsg" style="margin-top:10px;"></p>
    </div>
</div>

<script>
    const modal = document.getElementById('applyModal');
    function openModal() { modal.classList.add('active'); }
    function closeModal() { modal.classList.remove('active'); }

    document.getElementById('applyForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);

        // Check file size client side
        const file = formData.get('resume');
        if (file.size > 5 * 1024 * 1024) {
            alert('File is too large (Max 5MB)');
            return;
        }

        const res = await fetch('api/candidate.php?action=apply', {
            method: 'POST',
            body: formData
        });
        const result = await res.json();

        const msg = document.getElementById('applyMsg');
        if (result.success) {
            msg.style.color = 'var(--success)';
            msg.innerText = result.message;
            setTimeout(() => {
                closeModal();
                window.location.reload(); // Reload to update status if we tracked it
            }, 1500);
        } else {
            msg.style.color = 'var(--danger)';
            msg.innerText = result.message;
        }
    });
</script>

<?php include 'includes/footer.php'; ?>