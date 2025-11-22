<?php
// admin/get_cashier_orders.php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

try {
    $conn = getDBConnection();

    $sql = "SELECT o.*, 
            GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.product_name) SEPARATOR '<br>') as items_summary
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.order_status = 'ready'
            GROUP BY o.id
            ORDER BY o.updated_at DESC";

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
                'total_price' => $row['total_price'],
                'discount_amount' => $row['discount_amount'],
                'final_price' => $row['final_price'],
                'payment_method' => $row['payment_method'],
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
