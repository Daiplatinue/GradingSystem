<?php
session_start();
require_once 'db_connection.php';

// Fetch pending appeals with student information
$appeals_stmt = $conn->prepare("
    SELECT ap.*, a.a_fn, a.a_email 
    FROM appeal_tb ap 
    JOIN acc_tb a ON ap.a_id = a.a_id 
    WHERE ap.ap_status = 'Pending'
    ORDER BY ap.ap_id DESC
");
$appeals_stmt->execute();
$appeals_result = $appeals_stmt->get_result();
$pending_appeals = $appeals_result->fetch_all(MYSQLI_ASSOC);
$appeals_stmt->close();

include 'components/appeals_list.php';
?>