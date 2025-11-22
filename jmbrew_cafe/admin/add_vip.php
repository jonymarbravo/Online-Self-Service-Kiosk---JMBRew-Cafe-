<?php
// admin/add_vip.php
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

    // Validate VIP ID - must be exactly 10 digits
    if (strlen($vip_id) !== 10) {
        jsonResponse(false, 'VIP ID must be exactly 10 digits');
    }

    if (!ctype_digit($vip_id)) {
        jsonResponse(false, 'VIP ID must contain only numbers');
    }

    $conn = getDBConnection();

    // Check if VIP ID already exists
    $check_stmt = $conn->prepare("SELECT id FROM vip_customers WHERE vip_id = ?");

    if (!$check_stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $check_stmt->bind_param("s", $vip_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        $conn->close();
        jsonResponse(false, 'This VIP ID already exists');
    }

    $check_stmt->close();

    // Insert new VIP customer with default 20% discount
    $discount = 20.00;
    $stmt = $conn->prepare("INSERT INTO vip_customers (vip_id, discount_percentage) VALUES (?, ?)");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("sd", $vip_id, $discount);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        jsonResponse(true, 'VIP customer added successfully', [
            'vip_id' => $vip_id,
            'discount_percentage' => $discount
        ]);
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Error adding VIP customer: ' . $error);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
