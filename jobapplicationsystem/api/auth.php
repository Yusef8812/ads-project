<?php
// api/auth.php
require_once 'db.php';

header('Content-Type: application/json');
session_start();

$action = $_GET['action'] ?? '';

// Helper to send JSON response
function sendResponse($success, $message, $data = [])
{
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'register') {
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'Candidate'; // Default to Candidate

        if (!$username || !$email || !$password) {
            sendResponse(false, 'Missing fields');
        }

        // Validate Role
        if (!in_array($role, ['Recruiter', 'Candidate'])) {
            // Admin cannot register via API publicly usually, but for demo we allow it or restrict.
            // Let's restrict Admin registration here.
            sendResponse(false, 'Invalid role selected');
        }

        if ($role === 'Recruiter') {
            $companyName = $data['company_name'] ?? '';
            $companyDesc = $data['company_description'] ?? 'No description provided';
            if (empty($companyName)) {
                sendResponse(false, 'Company Name is required for Recruiters');
            }
        }

        try {
            $pdo = getDBConnection();
        } catch (PDOException $e) {
            sendResponse(false, 'Database connection failed: ' . $e->getMessage());
        }
        // Check if user exists
        $stmt = $pdo->prepare("SELECT UserID FROM Users WHERE Email = ? OR Username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            sendResponse(false, 'User already exists');
        }

        // Hash password
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO Users (Username, Email, PasswordHash, Role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $role]);
            $userId = $pdo->lastInsertId();

            if ($role === 'Recruiter') {
                $stmt = $pdo->prepare("INSERT INTO Companies (RecruiterID, CompanyName, Description) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $companyName, $companyDesc]);
            }

            $pdo->commit();
            sendResponse(true, 'Registration successful', ['user_id' => $userId]);

        } catch (PDOException $e) {
            $pdo->rollBack();
            sendResponse(false, 'Database error: ' . $e->getMessage());
        }

    } elseif ($action === 'login') {
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        if (!$email || !$password) {
            sendResponse(false, 'Missing email or password');
        }

        try {
            $pdo = getDBConnection();
        } catch (PDOException $e) {
            sendResponse(false, 'Database connection failed: ' . $e->getMessage());
        }
        $stmt = $pdo->prepare("SELECT UserID, Username, PasswordHash, Role FROM Users WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['PasswordHash'])) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['Role'];

            sendResponse(true, 'Login successful', [
                'redirect' => strtolower($user['Role']) . '_dashboard.php', // Simple redirection logic
                'role' => $user['Role']
            ]);
        } else {
            sendResponse(false, 'Invalid credentials');
        }
    } elseif ($action === 'logout') {
        session_destroy();
        sendResponse(true, 'Logged out');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check Status
    if ($action === 'check') {
        if (isset($_SESSION['user_id'])) {
            sendResponse(true, 'Authenticated', [
                'user_id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'username' => $_SESSION['username']
            ]);
        } else {
            sendResponse(false, 'Not authenticated');
        }
    }
}
?>