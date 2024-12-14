<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    http_response_code(500);
    exit('Connection Failed: ' . $conn->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = $data['notification_id'];
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();

$conn->close();
echo json_encode(['success' => true]);
?>