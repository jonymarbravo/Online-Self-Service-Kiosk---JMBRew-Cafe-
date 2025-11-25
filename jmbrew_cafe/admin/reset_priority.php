<?php
// admin/reset_priority.php (FIXED VERSION)
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonResponse(false, 'Unauthorized access');
}

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Start transaction
    $conn->begin_transaction();

    try {
        // This allows multiple orders to have the same priority number across different days
        $check_constraint = $conn->query("SHOW INDEXES FROM orders WHERE Key_name = 'priority_number'");

        if ($check_constraint && $check_constraint->num_rows > 0) {
            // Drop the unique constraint if it exists
            $conn->query("ALTER TABLE orders DROP INDEX priority_number");
        }

        // This ensures priority numbers are unique per day only
        $check_composite = $conn->query("SHOW INDEXES FROM orders WHERE Key_name = 'priority_per_day'");

        if (!$check_composite || $check_composite->num_rows == 0) {
            $conn->query("ALTER TABLE orders ADD UNIQUE KEY priority_per_day (priority_number, DATE(order_date))");
        }

        // Reset the priority counter
        $update = $conn->query("UPDATE priority_counter SET current_number = 0, last_reset = CURDATE() WHERE id = 1");

        if (!$update) {
            throw new Exception('Failed to reset priority counter: ' . $conn->error);
        }

        // Commit transaction
        $conn->commit();
        $conn->close();

        jsonResponse(true, 'Priority number reset successfully. Old orders are preserved and new orders will start from #1.');
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        jsonResponse(false, 'Transaction failed: ' . $e->getMessage());
    }
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}

