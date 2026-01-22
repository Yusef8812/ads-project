<?php
// api/admin.php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $pdo = getDBConnection();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}
$action = $_GET['action'] ?? '';

// Helpers
function sendJson($success, $msg, $data = [])
{
    echo json_encode(['success' => $success, 'message' => $msg, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add_category') {
        $name = $data['name'];
        try {
            $stmt = $pdo->prepare("INSERT INTO Categories (CategoryName) VALUES (?)");
            $stmt->execute([$name]);
            sendJson(true, 'Category added');
        } catch (PDOException $e) {
            sendJson(false, 'Error: ' . $e->getMessage());
        }

    } elseif ($action === 'delete_category') {
        $id = $data['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM Categories WHERE CategoryID = ?");
            $stmt->execute([$id]);
            sendJson(true, 'Category deleted');
        } catch (PDOException $e) {
            sendJson(false, 'Error: ' . $e->getMessage());
        }

    } elseif ($action === 'delete_user') {
        $id = $data['id'];
        // Prevent deleting self
        if ($id == $_SESSION['user_id'])
            sendJson(false, 'Cannot delete yourself');

        try {
            $stmt = $pdo->prepare("DELETE FROM Users WHERE UserID = ?");
            $stmt->execute([$id]);
            sendJson(true, 'User deleted');
        } catch (PDOException $e) {
            sendJson(false, 'Error: ' . $e->getMessage());
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if ($action === 'get_users') {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
        $offset = ($page - 1) * $limit;

        // 1. Get Total Count
        $stmtCount = $pdo->query("SELECT COUNT(*) FROM Users");
        $totalUsers = $stmtCount->fetchColumn();
        $totalPages = ceil($totalUsers / $limit);

        // 2. Get Paged Data
        $stmt = $pdo->prepare("SELECT UserID, Username, Email, Role, CreatedAt FROM Users ORDER BY CreatedAt DESC LIMIT $limit OFFSET $offset");
        $stmt->execute();
        $users = $stmt->fetchAll();

        $response = [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_users' => $totalUsers,
                'limit' => $limit
            ]
        ];
        sendJson(true, 'Users retrieved', $response);

    } elseif ($action === 'get_categories') {
        $stmt = $pdo->query("SELECT * FROM Categories");
        sendJson(true, 'Categories retrieved', $stmt->fetchAll());

    } elseif ($action === 'get_stats') {
        // Quick Dashboard stats
        // Could use VIEW or Counts
        $stats = [];
        $stats['users'] = $pdo->query("SELECT COUNT(*) FROM Users")->fetchColumn();
        $stats['jobs'] = $pdo->query("SELECT COUNT(*) FROM Jobs")->fetchColumn();
        $stats['applications'] = $pdo->query("SELECT COUNT(*) FROM Applications")->fetchColumn();
        sendJson(true, 'Stats retrieved', $stats);

    } elseif ($action === 'get_high_interest_jobs') {
        // Advanced SQL: Subquery Usage
        // Select jobs where application count > average application count
        $sql = "
            SELECT j.JobID, j.Title, c.CompanyName, COUNT(a.ApplicationID) as AppCount
            FROM Jobs j
            JOIN Companies c ON j.CompanyID = c.CompanyID
            LEFT JOIN Applications a ON j.JobID = a.JobID
            GROUP BY j.JobID, j.Title, c.CompanyName
            HAVING AppCount > (
                SELECT AVG(AppCount) 
                FROM (
                    SELECT COUNT(*) as AppCount 
                    FROM Applications 
                    GROUP BY JobID
                ) as Derived
            )
        ";
        $stmt = $pdo->query($sql);
        sendJson(true, 'High interest jobs retrieved', $stmt->fetchAll());
    }
}
?>