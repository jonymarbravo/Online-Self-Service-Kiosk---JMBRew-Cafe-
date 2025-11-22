<?php
// admin/get_product.php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

header('Content-Type: application/json');

try {
    $product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($product_id <= 0) {
        jsonResponse(false, 'Invalid product ID');
    }

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT id, name, price, category, image_path, stock_quantity, is_available FROM products WHERE id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $stmt->close();
        $conn->close();

        jsonResponse(true, 'Product retrieved successfully', [
            'id' => (int)$product['id'],
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'category' => $product['category'],
            'image_path' => $product['image_path'],
            'stock_quantity' => (int)$product['stock_quantity'],
            'is_available' => (bool)$product['is_available']
        ]);
    } else {
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Product not found');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
