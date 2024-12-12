<?php
session_start();
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }

    $conn = new mysqli('localhost', 'root', '', 'clinic_db');
    if ($conn->connect_error) {
        throw new Exception('Connection Failed: ' . $conn->connect_error);
    }

    if (!isset($_POST['checkup_id']) || !isset($_POST['health_status'])) {
        throw new Exception('Missing required fields');
    }

    $checkup_id = filter_var($_POST['checkup_id'], FILTER_VALIDATE_INT);
    $health_status = trim(filter_var($_POST['health_status'], FILTER_SANITIZE_STRING));
    $allergies = trim(filter_var($_POST['allergies'] ?? '', FILTER_SANITIZE_STRING));

    if ($checkup_id === false) {
        throw new Exception('Invalid checkup ID');
    }

    if (empty($health_status)) {
        throw new Exception('Health status cannot be empty');
    }

    $conn->begin_transaction();

    try {
        // Get user ID from check_up table
        $user_query = "SELECT u_id, c_urgent FROM check_up WHERE c_id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $checkup_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $check_up_data = $result->fetch_assoc();

        if (!$check_up_data) {
            throw new Exception('Check-up record not found');
        }

        // Update user health status and allergies
        $update_user = "UPDATE user SET u_hs = ?, u_allergy = ? WHERE u_id = ?";
        $stmt = $conn->prepare($update_user);
        $stmt->bind_param("ssi", $health_status, $allergies, $check_up_data['u_id']);
        $stmt->execute();

        // Update check-up status
        $update_checkup = "UPDATE check_up SET c_status = 'completed' WHERE c_id = ?";
        $stmt = $conn->prepare($update_checkup);
        $stmt->bind_param("i", $checkup_id);
        $stmt->execute();

        // Create notification
        $notification_message = "Your check-up has been completed. Health status updated to: " . $health_status;
        $insert_notification = "INSERT INTO notification (u_id, n_schedName, c_rc, n_type, created_at) 
                              VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_notification);
        $n_type = $check_up_data['c_urgent'];
        $stmt->bind_param("isss", $check_up_data['u_id'], $notification_message, $checkup_id, $n_type);
        $stmt->execute();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Check-up updated successfully'
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>