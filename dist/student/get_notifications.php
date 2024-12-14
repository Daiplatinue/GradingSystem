<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the current user
$stmt = $conn->prepare("
    SELECT n.*, ap.ap_status 
    FROM notification_tb n 
    LEFT JOIN appeal_tb ap ON n.ap_id = ap.ap_id 
    WHERE n.a_id = ? 
    ORDER BY n.n_createdAt DESC
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'n_id' => $row['n_id'],
        'n_description' => htmlspecialchars($row['n_description']),
        'n_createdAt' => $row['n_createdAt'],
        'ap_status' => $row['ap_status']
    ];
}

$stmt->close();
$conn->close();

echo json_encode([
    'success' => true,
    'notifications' => $notifications
]);