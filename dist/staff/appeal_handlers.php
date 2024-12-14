<?php
// Handle appeal status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appeal_status'])) {
    $appeal_id = $_POST['appeal_id'];
    $status = $_POST['status'];

    $update_stmt = $conn->prepare("UPDATE appeal_tb SET ap_status = ? WHERE ap_id = ?");
    $update_stmt->bind_param("si", $status, $appeal_id);

    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Appeal status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating appeal status!";
    }

    $update_stmt->close();
    header("Location: staff.php");
    exit;
}
?>