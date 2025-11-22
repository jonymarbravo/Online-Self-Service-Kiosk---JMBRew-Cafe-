<?php
// admin/update_order_status.php
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

    $order_id = isset($input['order_id']) ? (int)$input['order_id'] : 0;
    $status = isset($input['status']) ? sanitize($input['status']) : '';

    // Validate inputs
    if ($order_id <= 0) {
        jsonResponse(false, 'Invalid order ID');
    }

    $allowed_statuses = ['pending', 'preparing', 'ready', 'completed'];
    if (!in_array($status, $allowed_statuses)) {
        jsonResponse(false, 'Invalid status');
    }

    $conn = getDBConnection();

    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(true, 'Order status updated successfully');
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Error updating order status: ' . $error);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
