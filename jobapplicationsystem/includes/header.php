<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application System</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>

<body>

    <header>
        <nav>
            <div class="logo">JobPortal</div>
            <ul>
                <li><a href="index.php">Home</a></li>
                <?php if ($role === 'Guest'): ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php else: ?>
                    <?php if ($role === 'Admin'): ?>
                        <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <?php elseif ($role === 'Recruiter'): ?>
                        <li><a href="recruiter_dashboard.php">Dashboard</a></li>
                    <?php elseif ($role === 'Candidate'): ?>
                        <li><a href="candidate_dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="#" id="logoutBtn">Logout</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>