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

    if (!isset($_POST['checkup_id'])) {
        throw new Exception('Missing checkup ID');
    }

    $checkup_id = filter_var($_POST['checkup_id'], FILTER_VALIDATE_INT);
    
    if ($checkup_id === false) {
        throw new Exception('Invalid checkup ID');
    }

    $conn->begin_transaction();

    try {
        // Get checkup details
        $query = "SELECT u_id, c_pd, c_pt, c_urgent FROM check_up WHERE c_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $checkup_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $checkup_data = $result->fetch_assoc();

        if (!$checkup_data) {
            throw new Exception('Check-up record not found');
        }

        // Update check-up status to 'in_progress'
        $update_status = "UPDATE check_up SET c_status = 'in_progress' WHERE c_id = ?";
        $stmt = $conn->prepare($update_status);
        $stmt->bind_param("i", $checkup_id);
        $stmt->execute();

        // Create notification
        $notification_message = "Your check-up application has been accepted. Please proceed to the clinic on " . 
                              date('F j, Y', strtotime($checkup_data['c_pd'])) . " at " . 
                              date('g:i A', strtotime($checkup_data['c_pt']));
        
        $insert_notification = "INSERT INTO notification (u_id, n_schedName, c_rc, n_type, created_at) 
                              VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_notification);
        $n_type = $checkup_data['c_urgent'];
        $stmt->bind_param("isss", $checkup_data['u_id'], $notification_message, $checkup_id, $n_type);
        $stmt->execute();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Check-up started successfully'
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