<?php
// api/get_products.php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    $sql = "SELECT id, name, price, category, image_path, stock_quantity, is_available 
            FROM products 
            ORDER BY category, name";

    $result = $conn->query($sql);

    if (!$result) {
        $conn->close();
        jsonResponse(false, 'Database query error: ' . $conn->error);
    }

    $products = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'price' => (float)$row['price'],
                'category' => $row['category'],
                'image_path' => $row['image_path'],
                'stock_quantity' => (int)$row['stock_quantity'],
                'is_available' => (bool)$row['is_available']
            ];
        }
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
