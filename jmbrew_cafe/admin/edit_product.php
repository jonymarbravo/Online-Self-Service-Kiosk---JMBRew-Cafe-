<?php
// admin/edit_product.php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

try {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $category = isset($_POST['category']) ? sanitize($_POST['category']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;

    // Validate inputs
    if ($product_id <= 0) {
        jsonResponse(false, 'Invalid product ID');
    }

    if (empty($name)) {
        jsonResponse(false, 'Product name is required');
    }

    if (empty($category)) {
        jsonResponse(false, 'Category is required');
    }

    if ($price <= 0) {
        jsonResponse(false, 'Price must be greater than 0');
    }

    $allowed_categories = ['Coffee', 'Juices', 'Sandwiches'];
    if (!in_array($category, $allowed_categories)) {
        jsonResponse(false, 'Invalid category');
    }

    $conn = getDBConnection();

    // Check for duplicate name (excluding current product)
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?) AND id != ?");
    if (!$check_stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $check_stmt->bind_param("si", $name, $product_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        $conn->close();
        jsonResponse(false, 'Another product with name "' . $name . '" already exists!');
    }
    $check_stmt->close();

    // Get current product data
    $get_stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $get_stmt->bind_param("i", $product_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result();

    if ($get_result->num_rows === 0) {
        $get_stmt->close();
        $conn->close();
        jsonResponse(false, 'Product not found');
    }

    $current_data = $get_result->fetch_assoc();
    $image_path = $current_data['image_path'];
    $get_stmt->close();

    // Handle new image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed_types)) {
            $conn->close();
            jsonResponse(false, 'Invalid file type. Only JPG, PNG, and GIF are allowed');
        }

        // Validate file size
        if ($file['size'] > 5 * 1024 * 1024) {
            $conn->close();
            jsonResponse(false, 'File size too large. Maximum 5MB allowed');
        }

        // Create uploads directory
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $new_filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Delete old image
            $old_image_path = '../' . $image_path;
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }

            $image_path = 'uploads/' . $new_filename;
        } else {
            $conn->close();
            jsonResponse(false, 'Failed to save new image');
        }
    }

    // Update product
    $is_available = $stock > 0 ? 1 : 0;

    $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, category = ?, image_path = ?, stock_quantity = ?, is_available = ?, updated_at = NOW() WHERE id = ?");

    if (!$stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("sdsssii", $name, $price, $category, $image_path, $stock, $is_available, $product_id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        jsonResponse(true, 'Product updated successfully', [
            'product_id' => $product_id,
            'name' => $name,
            'price' => $price,
            'category' => $category,
            'stock' => $stock
        ]);
    } else {
        $error = $stmt->error;
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Error updating product: ' . $error);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
