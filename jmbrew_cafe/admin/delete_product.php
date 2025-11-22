<?php
// admin/delete_product.php
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

    if ($product_id <= 0) {
        jsonResponse(false, 'Invalid product ID');
    }

    $conn = getDBConnection();

    // Get image path before deleting
    $stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $image_path = '../' . $row['image_path'];

        $stmt->close();

        // Delete the product from database
        $delete_stmt = $conn->prepare("DELETE FROM products WHERE id = ?");

        if (!$delete_stmt) {
            $conn->close();
            jsonResponse(false, 'Database error: ' . $conn->error);
        }

        $delete_stmt->bind_param("i", $product_id);

        if ($delete_stmt->execute()) {
            // Delete image file if it exists
            if (file_exists($image_path)) {
                unlink($image_path);
            }

            $delete_stmt->close();
            $conn->close();
            jsonResponse(true, 'Product deleted successfully');
        } else {
            $error = $delete_stmt->error;
            $delete_stmt->close();
            $conn->close();
            jsonResponse(false, 'Error deleting product: ' . $error);
        }
    } else {
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Product not found');
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
