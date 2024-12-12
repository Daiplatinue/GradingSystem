<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u_id = $_SESSION['user_id'];
    $c_pd = $_POST['date'] ?? 'N/A';
    
    $time = $_POST['time'] ?? 'N/A';
    if ($time !== 'N/A') {
        $timestamp = strtotime($time);
        $c_pt = date('g:iA', $timestamp); 
    } else {
        $c_pt = 'N/A';
    }
    
    $c_rc = $_POST['reason'] ?? 'N/A';
    $c_urgent = isset($_POST['urgent']) ? 'urgent' : 'unurgent';
    $c_status = 'pending';
    $c_lc = date('Y-m-d H:i:s');
    $c_nc = 'N/A';  

    $conn = new mysqli('localhost', 'root', '', 'clinic_db');

    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    }

    $insert_query = "INSERT INTO check_up (u_id, c_pd, c_pt, c_rc, c_urgent, c_status, c_lc, c_nc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    
    if ($stmt) {
        $stmt->bind_param("isssssss", 
            $u_id,
            $c_pd,
            $c_pt,
            $c_rc,
            $c_urgent,
            $c_status,
            $c_lc,
            $c_nc
        );

        if ($stmt->execute()) {
            echo "<script>
                alert('Check-up request submitted successfully!');
                window.location.href = 'student.php';
            </script>";
        } else {
            echo "<script>
                alert('Error submitting check-up request: " . $stmt->error . "');
                window.location.href = 'student.php';
            </script>";
        }
        $stmt->close();
    } else {
        echo "<script>
            alert('Error preparing statement: " . $conn->error . "');
            window.location.href = 'student.php';
        </script>";
    }

    $conn->close();
}
?>