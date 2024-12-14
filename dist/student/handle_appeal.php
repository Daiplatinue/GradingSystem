<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $message = $_POST['description'] ?? '';
    $status = 'Pending'; // Default status for new appeals

    // Prepare and execute the insert statement
    $stmt = $conn->prepare("INSERT INTO appeal_tb (a_id, ap_message, ap_status) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $message, $status);

    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Appeal submitted successfully'
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error submitting appeal'
        ]);
    }

    $stmt->close();
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>