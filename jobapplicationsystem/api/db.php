<?php
// api/db.php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'JobSystemDB');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection()
{
    static $pdo = null;

    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // In a real app, log error. For now, output for debugging.
            // If database doesn't exist, we might catch it here.
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }
    }
    return $pdo;
}
?>