<?php
// api/place_order.php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(false, 'Invalid JSON data');
    }

    $priority_number = isset($input['priority_number']) ? (int)$input['priority_number'] : 0;
    $items = isset($input['items']) ? $input['items'] : [];
    $payment_method = isset($input['payment_method']) ? sanitize($input['payment_method']) : 'Cashier';
    $is_vip = isset($input['is_vip']) ? (bool)$input['is_vip'] : false;
    $vip_id = $is_vip && isset($input['vip_id']) ? sanitize($input['vip_id']) : null;

    // Validate data
    if (empty($items)) {
        jsonResponse(false, 'No items in order');
    }

    if ($priority_number <= 0) {
        jsonResponse(false, 'Invalid priority number');
    }

    $conn = getDBConnection();
    $conn->begin_transaction();

    try {
        // Check if priority number already exists TODAY
        $today = date('Y-m-d');
        $check_priority = $conn->prepare("SELECT id FROM orders WHERE priority_number = ? AND DATE(order_date) = ? LIMIT 1");
        $check_priority->bind_param("is", $priority_number, $today);
        $check_priority->execute();
        $priority_result = $check_priority->get_result();

        if ($priority_result->num_rows > 0) {
            // Priority number exists today, get a new one
            $check_priority->close();

            // Get the next available priority number for today
            $get_max = $conn->query("SELECT IFNULL(MAX(priority_number), 0) + 1 as next_priority FROM orders WHERE DATE(order_date) = '$today'");
            $max_row = $get_max->fetch_assoc();
            $priority_number = (int)$max_row['next_priority'];
        } else {
            $check_priority->close();
        }

        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
            $subtotal += $price * $quantity;
        }

        $discount = $is_vip ? $subtotal * 0.20 : 0;
        $total = $subtotal - $discount;

        // Insert order
        $stmt = $conn->prepare("INSERT INTO orders (priority_number, total_price, discount_amount, final_price, payment_method, order_status, vip_id, is_vip) VALUES (?, ?, ?, ?, ?, 'preparing', ?, ?)");

        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param("idddssi", $priority_number, $subtotal, $discount, $total, $payment_method, $vip_id, $is_vip);

        if (!$stmt->execute()) {
            throw new Exception('Order insert failed: ' . $stmt->error);
        }

        $order_id = $conn->insert_id;
        $stmt->close();

        // Insert order items and update stock
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, order_type) VALUES (?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            throw new Exception('Prepare order items failed: ' . $conn->error);
        }

        foreach ($items as $item) {
            $product_id = isset($item['productId']) ? (int)$item['productId'] : 0;
            $name = isset($item['name']) ? sanitize($item['name']) : '';
            $price = isset($item['price']) ? floatval($item['price']) : 0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
            $order_type = isset($item['orderType']) ? sanitize($item['orderType']) : 'Dine In';

            if ($product_id <= 0 || $quantity <= 0) {
                throw new Exception('Invalid product data');
            }

            // Check stock availability
            $stock_check = $conn->query("SELECT stock_quantity FROM products WHERE id = $product_id");
            if (!$stock_check || $stock_check->num_rows === 0) {
                throw new Exception('Product not found: ' . $name);
            }

            $stock_row = $stock_check->fetch_assoc();
            if ($stock_row['stock_quantity'] < $quantity) {
                throw new Exception('Insufficient stock for: ' . $name);
            }

            // Update stock
            $update_stock = $conn->query("UPDATE products SET stock_quantity = stock_quantity - $quantity WHERE id = $product_id");
            if (!$update_stock) {
                throw new Exception('Failed to update stock for: ' . $name);
            }

            // Check if out of stock after update
            $stock_check = $conn->query("SELECT stock_quantity FROM products WHERE id = $product_id");
            $stock_row = $stock_check->fetch_assoc();

            if ($stock_row['stock_quantity'] <= 0) {
                $conn->query("UPDATE products SET is_available = 0 WHERE id = $product_id");
            }

            // Insert order item
            $stmt->bind_param("iisdis", $order_id, $product_id, $name, $price, $quantity, $order_type);

            if (!$stmt->execute()) {
                throw new Exception('Failed to insert order item: ' . $name);
            }
        }

        $stmt->close();
        $conn->commit();
        $conn->close();

        jsonResponse(true, 'Order placed successfully', ['order_id' => $order_id, 'priority_number' => $priority_number]);
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        jsonResponse(false, 'Transaction failed: ' . $e->getMessage());
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}

