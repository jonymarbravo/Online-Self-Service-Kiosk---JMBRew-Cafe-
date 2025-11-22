<?php
// admin/delete_vip.php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(false, 'Invalid JSON data');
    }

    $vip_id = isset($input['vip_id']) ? sanitize($input['vip_id']) : '';

    // Validate input
    if (empty($vip_id)) {
        jsonResponse(false, 'Invalid VIP ID');
    }

    $conn = getDBConnection();

    // Delete VIP customer
    $stmt = $conn->prepare("DELETE FROM vip_customers WHERE vip_id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("s", $vip_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            $conn->close();
            jsonResponse(true, 'VIP customer removed successfully');
        } else {
            $stmt->close();
            $conn->close();
            jsonResponse(false, 'VIP customer not found');
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Error removing VIP customer: ' . $error);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
