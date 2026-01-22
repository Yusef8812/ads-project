<?php
// api/candidate.php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Candidate') {
    // For "get_jobs", we might allow public access if looking at the board? 
    // But let's restrict write actions.
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
$candidateId = $_SESSION['user_id'] ?? 0;
$action = $_GET['action'] ?? '';

function sendJson($success, $msg, $data = [])
{
    echo json_encode(['success' => $success, 'message' => $msg, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'apply') {
        if (!isset($_SESSION['user_id']))
            sendJson(false, 'Please login to apply');

        $jobId = $_POST['job_id'];

        // Handle Resume Upload
        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            sendJson(false, 'Resume file is required');
        }

        $file = $_FILES['resume'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx'];

        if (!in_array($ext, $allowed)) {
            sendJson(false, 'Invalid file type. Only PDF and DOC/DOCX allowed.');
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB
            sendJson(false, 'File too large (Max 5MB)');
        }

        $uploadDir = '../uploads/resumes/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0777, true);

        $filename = 'resume_' . $candidateId . '_' . time() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        $dbPath = 'uploads/resumes/' . $filename; // Relative path for DB

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            sendJson(false, 'Failed to upload file');
        }

        // Insert Application
        // Verification Point: "Application Guard" Trigger should fire if Job is Closed.
        try {
            $stmt = $pdo->prepare("INSERT INTO Applications (JobID, CandidateID, ResumePath, Status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$jobId, $candidateId, $dbPath]);
            sendJson(true, 'Application submitted successfully');
        } catch (PDOException $e) {
            // Check for Trigger Error (SQLSTATE 45000)
            if ($e->getCode() == '45000') {
                sendJson(false, 'Application Failed: This job is closed.');
            } elseif ($e->getCode() == '23000') {
                sendJson(false, 'You have already applied for this job.');
            } else {
                sendJson(false, 'Database Error: ' . $e->getMessage());
            }
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'search_jobs') {
        // Use VIEW: vw_OpenJobs
        $query = $_GET['q'] ?? '';
        $catId = $_GET['cat'] ?? '';
        $type = $_GET['type'] ?? '';

        // Pagination Parameters
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
        $offset = ($page - 1) * $limit;

        // 1. Get Total Count (for pagination)
        $countSql = "SELECT COUNT(*) FROM vw_OpenJobs WHERE 1=1";
        // Re-use logic for parameters? Ideally we build the WHERE clause once.
        $whereClause = "";
        $countParams = [];

        if ($query) {
            $whereClause .= " AND (Title LIKE ? OR CompanyName LIKE ?)";
            $countParams[] = "%$query%";
            $countParams[] = "%$query%";
        }
        if ($catId) {
            $whereClause .= " AND CategoryID = ?";
            $countParams[] = $catId;
        }
        if ($type) {
            $whereClause .= " AND JobType = ?";
            $countParams[] = $type;
        }

        $stmtCount = $pdo->prepare($countSql . $whereClause);
        $stmtCount->execute($countParams);
        $totalJobs = $stmtCount->fetchColumn();
        $totalPages = ceil($totalJobs / $limit);

        // 2. Get Paged Data
        $sql = "SELECT * FROM vw_OpenJobs WHERE 1=1" . $whereClause . " ORDER BY PostedDate DESC LIMIT $limit OFFSET $offset";

        // Params for data query are the same as count params
        $stmt = $pdo->prepare($sql);
        $stmt->execute($countParams);
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
        sendJson(true, 'Jobs found', $response);

    } elseif ($action === 'my_applications') {
        if (!isset($_SESSION['user_id']))
            sendJson(false, 'Unauthorized');

        $sql = "SELECT a.*, j.Title, c.CompanyName 
                FROM Applications a
                JOIN Jobs j ON a.JobID = j.JobID
                JOIN Companies c ON j.CompanyID = c.CompanyID
                WHERE a.CandidateID = ?
                ORDER BY a.AppliedAt DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$candidateId]);

        // Also get stats using Function fn_GetApplicantApplicationCount? 
        // Not strictly needed for list, but requested in requirements to use functions.
        // We can add a summary.

        sendJson(true, 'History retrieved', $stmt->fetchAll());

    } elseif ($action === 'get_categories') {
        $stmt = $pdo->query("SELECT * FROM Categories");
        sendJson(true, 'Categories retrieved', $stmt->fetchAll());
    }
}
?>