<?php
// admin/get_vip_customers.php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    $sql = "SELECT * FROM vip_customers ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if (!$result) {
        $conn->close();
        jsonResponse(false, 'Database query error: ' . $conn->error);
    }

    $vips = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $vips[] = [
                'id' => (int)$row['id'],
                'vip_id' => $row['vip_id'],
                'discount_percentage' => (float)$row['discount_percentage'],
                'created_at' => $row['created_at']
            ];
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'VIP customers retrieved',
        'vips' => $vips
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
