<?php
// api/verify_vip.php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(false, 'Invalid JSON data');
    }

    $vip_id = isset($input['vip_id']) ? sanitize($input['vip_id']) : '';

    if (strlen($vip_id) !== 10) {
        jsonResponse(false, 'VIP ID must be 10 digits');
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT id, discount_percentage FROM vip_customers WHERE vip_id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("s", $vip_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        jsonResponse(true, 'VIP verified', [
            'discount_percentage' => (float)$row['discount_percentage']
        ]);
    } else {
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Invalid VIP ID');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
