<?php
// api/get_order_status.php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

    if ($order_id <= 0) {
        jsonResponse(false, 'Invalid order ID');
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT order_status, priority_number, is_vip, vip_id FROM orders WHERE id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        jsonResponse(true, 'Status retrieved', [
            'status' => $row['order_status'],
            'priority_number' => (int)$row['priority_number'],
            'is_vip' => (bool)$row['is_vip'],
            'vip_id' => $row['vip_id']
        ]);
    } else {
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Order not found');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
