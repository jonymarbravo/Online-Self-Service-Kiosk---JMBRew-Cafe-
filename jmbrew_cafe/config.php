<?php
// config.php - Database Configuration

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'jmbrew_cafe');

// Create database connection
function getDBConnection()
{
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die(json_encode(['success' => false, 'message' => 'Database connection error: ' . $e->getMessage()]));
    }
}

// VIP discount percentage
define('VIP_DISCOUNT_PERCENT', 20);

// Max items per product
define('MAX_PRODUCT_LIMIT', 10);

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Helper function to sanitize input
function sanitize($data)
{
    if ($data === null) return '';
    return htmlspecialchars(strip_tags(trim($data)));
}

// Helper function to generate JSON response
function jsonResponse($success, $message, $data = null)
{
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

// Error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
