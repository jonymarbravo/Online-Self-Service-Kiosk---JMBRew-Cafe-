<?php
// api/get_priority_number.php - AUTO RESET EVERY 12 HOURS
require_once '../config.php';

header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Check if we need to reset the counter (every 12 hours)
    $checkReset = "SELECT current_number, last_reset FROM priority_counter WHERE id = 1";
    $result = $conn->query($checkReset);

    if (!$result) {
        $conn->close();
        jsonResponse(false, 'Database error: ' . $conn->error);
    }

    $row = $result->fetch_assoc();

    $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
    $currentTimestamp = $now->getTimestamp();

    // If no record exists, create one
    if (!$row) {
        $conn->query("INSERT INTO priority_counter (id, current_number, last_reset) VALUES (1, 0, NOW())");
        $result = $conn->query($checkReset);
        $row = $result->fetch_assoc();
    }

    // Get last reset timestamp
    $lastReset = new DateTime($row['last_reset'], new DateTimeZone('Asia/Manila'));
    $lastResetTimestamp = $lastReset->getTimestamp();

    // Calculate hours difference
    $hoursDifference = ($currentTimestamp - $lastResetTimestamp) / 3600;

    // Reset if 12 or more hours have passed
    if ($hoursDifference >= 12) {
        $conn->query("UPDATE priority_counter SET current_number = 0, last_reset = NOW() WHERE id = 1");
    }

    // Increment and get new priority number
    $update = $conn->query("UPDATE priority_counter SET current_number = current_number + 1 WHERE id = 1");

    if (!$update) {
        $conn->close();
        jsonResponse(false, 'Failed to generate priority number');
    }

    $result = $conn->query("SELECT current_number FROM priority_counter WHERE id = 1");
    $row = $result->fetch_assoc();

    $priorityNumber = (int)$row['current_number'];

    $conn->close();

    echo json_encode([
        'success' => true,
        'priority_number' => $priorityNumber
    ]);
} catch (Exception $e) {
    jsonResponse(false, 'Server error: ' . $e->getMessage());
}
