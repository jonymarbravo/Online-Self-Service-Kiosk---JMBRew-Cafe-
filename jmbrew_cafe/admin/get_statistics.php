<?php
// admin/get_statistics.php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

try {
    $conn = getDBConnection();
    $today = date('Y-m-d');

    // Today's sales - ONLY COMPLETED ORDERS (PAID)
    $sales_query = "SELECT COALESCE(SUM(final_price), 0) as total 
                    FROM orders 
                    WHERE DATE(created_at) = '$today' 
                    AND order_status = 'completed'";
    $sales_result = $conn->query($sales_query);
    $sales = $sales_result->fetch_assoc();
    $today_sales = floatval($sales['total']);

    // Total orders today
    $orders_query = "SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = '$today'";
    $orders_result = $conn->query($orders_query);
    $orders = $orders_result->fetch_assoc();
    $total_orders = intval($orders['total']);

    // Pending orders
    $pending_query = "SELECT COUNT(*) as total FROM orders WHERE order_status IN ('pending', 'preparing')";
    $pending_result = $conn->query($pending_query);
    $pending = $pending_result->fetch_assoc();
    $pending_orders = intval($pending['total']);

    // VIP count
    $vip_query = "SELECT COUNT(*) as total FROM vip_customers";
    $vip_result = $conn->query($vip_query);
    $vip = $vip_result->fetch_assoc();
    $vip_count = intval($vip['total']);

    // Recent orders
    $recent_sql = "SELECT o.*, 
                   GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.product_name) SEPARATOR ', ') as items_summary
                   FROM orders o
                   LEFT JOIN order_items oi ON o.id = oi.order_id
                   WHERE DATE(o.created_at) = '$today'
                   GROUP BY o.id
                   ORDER BY o.created_at DESC
                   LIMIT 10";

    $recent_result = $conn->query($recent_sql);
    $recent_orders = [];

    if ($recent_result && $recent_result->num_rows > 0) {
        while ($row = $recent_result->fetch_assoc()) {
            $recent_orders[] = [
                'id' => (int)$row['id'],
                'priority_number' => (int)$row['priority_number'],
                'items_summary' => $row['items_summary'] ?? '',
                'final_price' => $row['final_price'],
                'order_status' => $row['order_status'],
                'created_at' => $row['created_at']
            ];
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Statistics retrieved',
        'today_sales' => $today_sales,
        'total_orders' => $total_orders,
        'pending_orders' => $pending_orders,
        'vip_count' => $vip_count,
        'recent_orders' => $recent_orders
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
