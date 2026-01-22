<?php
// api/recruiter.php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Recruiter') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
$recruiterId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Helpers
function sendJson($success, $msg, $data = [])
{
    echo json_encode(['success' => $success, 'message' => $msg, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'create_job') {
        // Recruiter needs a company first.
        // Check company
        $stmt = $pdo->prepare("SELECT CompanyID FROM Companies WHERE RecruiterID = ?");
        $stmt->execute([$recruiterId]);
        $company = $stmt->fetch();

        if (!$company) {
            sendJson(false, 'Recruiter profile incomplete. No company associated.');
        } else {
            $companyId = $company['CompanyID'];
        }

        $title = $data['title'];
        $desc = $data['description'];
        $salary = $data['salary'];
        $type = $data['job_type'];
        $catId = $data['category_id'] ?? 1;

        try {
            $stmt = $pdo->prepare("INSERT INTO Jobs (CompanyID, CategoryID, Title, Description, Salary, JobType, Status) VALUES (?, ?, ?, ?, ?, ?, 'Open')");
            $stmt->execute([$companyId, $catId, $title, $desc, $salary, $type]);
            sendJson(true, 'Job posted successfully');
        } catch (PDOException $e) {
            sendJson(false, 'Error posting job: ' . $e->getMessage());
        }

    } elseif ($action === 'update_job') {
        $jobId = $data['job_id'];

        // Ownership Check
        $stmt = $pdo->prepare("SELECT j.JobID FROM Jobs j JOIN Companies c ON j.CompanyID = c.CompanyID WHERE j.JobID = ? AND c.RecruiterID = ?");
        $stmt->execute([$jobId, $recruiterId]);
        if (!$stmt->fetch())
            sendJson(false, 'Job not found or unauthorized');

        $title = $data['title'];
        $desc = $data['description'];
        $salary = $data['salary'];
        $type = $data['job_type'];
        $catId = $data['category_id'] ?? 1;

        try {
            $stmt = $pdo->prepare("UPDATE Jobs SET Title=?, Description=?, Salary=?, JobType=?, CategoryID=? WHERE JobID=?");
            $stmt->execute([$title, $desc, $salary, $type, $catId, $jobId]);
            sendJson(true, 'Job updated successfully');
        } catch (PDOException $e) {
            sendJson(false, 'Error updating job: ' . $e->getMessage());
        }

    } elseif ($action === 'delete_job') {
        $jobId = $data['job_id'];

        // Ownership Check
        $stmt = $pdo->prepare("SELECT j.JobID FROM Jobs j JOIN Companies c ON j.CompanyID = c.CompanyID WHERE j.JobID = ? AND c.RecruiterID = ?");
        $stmt->execute([$jobId, $recruiterId]);
        if (!$stmt->fetch())
            sendJson(false, 'Job not found or unauthorized');

        try {
            $stmt = $pdo->prepare("DELETE FROM Jobs WHERE JobID = ?");
            $stmt->execute([$jobId]);
            sendJson(true, 'Job deleted successfully');
        } catch (PDOException $e) {
            sendJson(false, 'Error deleting job: ' . $e->getMessage());
        }

    } elseif ($action === 'toggle_status') {
        $jobId = $data['job_id'];

        // Verify ownership
        $stmt = $pdo->prepare("SELECT j.JobID FROM Jobs j JOIN Companies c ON j.CompanyID = c.CompanyID WHERE j.JobID = ? AND c.RecruiterID = ?");
        $stmt->execute([$jobId, $recruiterId]);
        if (!$stmt->fetch())
            sendJson(false, 'Job not found or unauthorized');

        try {
            // Use Stored Procedure
            $stmt = $pdo->prepare("CALL sp_ToggleJobStatus(?)");
            $stmt->execute([$jobId]);
            sendJson(true, 'Job status toggled');
        } catch (PDOException $e) {
            sendJson(false, 'Error toggling status: ' . $e->getMessage());
        }

    } elseif ($action === 'update_application') {
        $appId = $data['application_id'];
        $newStatus = $data['status']; // 'Accepted', 'Rejected'

        // Verify ownership (Complex join: App -> Job -> Company -> Recruiter)
        $sql = "SELECT a.ApplicationID FROM Applications a 
                JOIN Jobs j ON a.JobID = j.JobID 
                JOIN Companies c ON j.CompanyID = c.CompanyID 
                WHERE a.ApplicationID = ? AND c.RecruiterID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$appId, $recruiterId]);

        if (!$stmt->fetch())
            sendJson(false, 'Application not found or unauthorized');

        try {
            if ($newStatus === 'Accepted') {
                // Use Stored Procedure to hire
                $stmt = $pdo->prepare("CALL sp_HireApplicant(?)");
                $stmt->execute([$appId]);
                sendJson(true, 'Applicant hired (Status updated to Accepted)');
            } else {
                // Regular update
                $stmt = $pdo->prepare("UPDATE Applications SET Status = ? WHERE ApplicationID = ?");
                $stmt->execute([$newStatus, $appId]);
                sendJson(true, 'Application status updated');
            }
        } catch (PDOException $e) {
            sendJson(false, 'Error updating application: ' . $e->getMessage());
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'get_jobs') {
        $catId = $_GET['category_id'] ?? '';
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
        $offset = ($page - 1) * $limit;

        // 1. Get Total Count
        $countSql = "SELECT COUNT(*) FROM Jobs j JOIN Companies c ON j.CompanyID = c.CompanyID WHERE c.RecruiterID = ?";
        $countParams = [$recruiterId];

        if ($catId) {
            $countSql .= " AND j.CategoryID = ?";
            $countParams[] = $catId;
        }

        $stmtCount = $pdo->prepare($countSql);
        $stmtCount->execute($countParams);
        $totalJobs = $stmtCount->fetchColumn();
        $totalPages = ceil($totalJobs / $limit);

        // 2. Get Paged Data
        $sql = "SELECT j.*, c.CompanyName, cat.CategoryName,
                (SELECT COUNT(*) FROM Applications a WHERE a.JobID = j.JobID) as AppCount
                FROM Jobs j 
                JOIN Companies c ON j.CompanyID = c.CompanyID
                LEFT JOIN Categories cat ON j.CategoryID = cat.CategoryID
                WHERE c.RecruiterID = ?";

        $params = [$recruiterId];

        if ($catId) {
            $sql .= " AND j.CategoryID = ?";
            $params[] = $catId;
        }

        $sql .= " ORDER BY j.PostedDate DESC LIMIT $limit OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll();

        $response = [
            'jobs' => $jobs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_jobs' => $totalJobs,
                'limit' => $limit
            ]
        ];
        sendJson(true, 'Jobs retrieved', $response);

    } elseif ($action === 'get_applicants') {
        $jobId = $_GET['job_id'];

        // Ownership check
        $stmt = $pdo->prepare("SELECT JobID FROM Jobs j JOIN Companies c ON j.CompanyID = c.CompanyID WHERE j.JobID = ? AND c.RecruiterID = ?");
        $stmt->execute([$jobId, $recruiterId]);
        if (!$stmt->fetch())
            sendJson(false, 'Unauthorized');

        // Fetch applicants
        $sql = "SELECT a.*, u.Username, u.Email, u.Role /* Fetch user details */
                FROM Applications a
                JOIN Users u ON a.CandidateID = u.UserID
                WHERE a.JobID = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$jobId]);
        sendJson(true, 'Applicants retrieved', $stmt->fetchAll());

    } elseif ($action === 'get_categories') {
        $stmt = $pdo->query("SELECT * FROM Categories");
        sendJson(true, 'Categories retrieved', $stmt->fetchAll());
    }
}
?>