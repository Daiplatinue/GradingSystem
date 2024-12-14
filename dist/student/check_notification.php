<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/auth.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch appeal status
$stmt = $conn->prepare("SELECT ap_status FROM appeal_tb WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$response = ['hasNotification' => false, 'message' => ''];

if ($result->num_rows > 0) {
    $appeal = $result->fetch_assoc();
    $response['hasNotification'] = true;
    
    if ($appeal['ap_status'] === 'accepted') {
        $response['message'] = 'Your appeal has been approved!';
        $response['type'] = 'success';
    } else if ($appeal['ap_status'] === 'rejected') {
        $response['message'] = 'Your appeal has been disapproved.';
        $response['type'] = 'error';
    } else {
        $response['message'] = 'Your appeal is still pending.';
        $response['type'] = 'pending';
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>