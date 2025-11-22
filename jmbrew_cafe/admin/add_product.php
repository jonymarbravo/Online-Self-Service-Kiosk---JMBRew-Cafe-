<?php
// admin/add_product.php
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
    // Get form data
    $name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
    $category = isset($_POST['category']) ? sanitize($_POST['category']) : '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
    $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;

    // Validate inputs
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

    // CHECK FOR DUPLICATE PRODUCT NAME
    $check_stmt = $conn->prepare("SELECT id FROM products WHERE LOWER(name) = LOWER(?)");
    if (!$check_stmt) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $check_stmt->bind_param("s", $name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        $conn->close();
        jsonResponse(false, 'Product "' . $name . '" already exists! Please edit the existing product or use a different name.');
    }
    $check_stmt->close();

    // Handle file upload
    if (!isset($_FILES['image'])) {
        $conn->close();
        jsonResponse(false, 'No image uploaded');
    }

    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $conn->close();
        jsonResponse(false, 'Image upload error: ' . $file['error']);
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $allowed_types)) {
        $conn->close();
        jsonResponse(false, 'Invalid file type. Only JPG, PNG, and GIF are allowed');
    }

    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $conn->close();
        jsonResponse(false, 'File size too large. Maximum 5MB allowed');
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $conn->close();
            jsonResponse(false, 'Failed to create upload directory');
        }
    }

    // Generate unique filename
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '_' . time() . '.' . $ext;
    $upload_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $conn->close();
        jsonResponse(false, 'Failed to save image file');
    }

    $image_path = 'uploads/' . $new_filename;

    // Insert into database
    $is_available = $stock > 0 ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO products (name, price, category, image_path, stock_quantity, is_available) VALUES (?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        // Delete uploaded file if prepare fails
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $stmt->bind_param("sdssii", $name, $price, $category, $image_path, $stock, $is_available);

    if ($stmt->execute()) {
        $product_id = $conn->insert_id;
        $stmt->close();
        $conn->close();

        jsonResponse(true, 'Product added successfully', [
            'product_id' => $product_id,
            'name' => $name,
            'price' => $price,
            'category' => $category,
            'stock' => $stock
        ]);
    } else {
        // Delete uploaded image if database insert fails
        if (file_exists($upload_path)) {
            unlink($upload_path);
        }
        $stmt->close();
        $conn->close();
        jsonResponse(false, 'Error adding product to database: ' . $stmt->error);
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
