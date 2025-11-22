<?php
// admin/update_stock.php
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

    $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
    $quantity = isset($input['quantity']) ? (int)$input['quantity'] : 0;

    // Validate inputs
    if ($product_id <= 0) {
        jsonResponse(false, 'Invalid product ID');
    }

    if ($quantity < 0) {
        jsonResponse(false, 'Stock quantity cannot be negative');
    }

    $conn = getDBConnection();

    // Update stock quantity and availability status
    $is_available = $quantity > 0 ? 1 : 0;

    $stmt = $conn->prepare("UPDATE products SET stock_quantity = ?, is_available = ?, updated_at = NOW() WHERE id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("iii", $quantity, $is_available, $product_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0 || $stmt->affected_rows === 0) {
            $stmt->close();
            $conn->close();
            jsonResponse(true, 'Stock updated successfully', [
                'new_quantity' => $quantity,
                'is_available' => (bool)$is_available
            ]);
        } else {
            $stmt->close();
            $conn->close();
            jsonResponse(false, 'Product not found');
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Error updating stock: ' . $error);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
