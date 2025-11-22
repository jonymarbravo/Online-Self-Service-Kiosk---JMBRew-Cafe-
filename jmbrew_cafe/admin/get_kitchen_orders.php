<?php
// admin/get_kitchen_orders.php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

try {
    $conn = getDBConnection();

    $sql = "SELECT o.*, 
            GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.product_name) SEPARATOR ', ') as items_summary,
            GROUP_CONCAT(DISTINCT oi.order_type SEPARATOR ', ') as order_types
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.order_status IN ('pending', 'preparing', 'ready')
            GROUP BY o.id
            ORDER BY o.priority_number ASC";

    $result = $conn->query($sql);

    if (!$result) {
        $conn->close();
        jsonResponse(false, 'Database query error: ' . $conn->error);
    }

    $orders = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = [
                'id' => (int)$row['id'],
                'priority_number' => (int)$row['priority_number'],
                'items_summary' => $row['items_summary'] ?? '',
                'order_types' => $row['order_types'] ?? '',
                'status' => $row['order_status'],
                'is_vip' => (bool)$row['is_vip'],
                'vip_id' => $row['vip_id'],
                'created_at' => $row['created_at']
            ];
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Orders retrieved',
        'orders' => $orders
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
