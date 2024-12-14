<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get all subjects
$subjects = $conn->query("SELECT subject_code FROM subjects_tb")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'];
    $period = $_POST['period'];
    
    // Calculate period average
    $total = 0;
    $count = 0;
    
    try {
        $conn->begin_transaction();
        
        // Update grades for each subject
        foreach ($subjects as $subject) {
            $code = $subject['subject_code'];
            if (isset($_POST[$code])) {
                $grade = floatval($_POST[$code]);
                $total += $grade;
                $count++;
                
                $column = "g_{$code}{$period}";
                $update_stmt = $conn->prepare("UPDATE grades_tb SET $column = ? WHERE a_id = ?");
                $update_stmt->bind_param("di", $grade, $student_id);
                $update_stmt->execute();
            }
        }
        
        // Calculate and update period average
        if ($count > 0) {
            $period_average = $total / $count;
            $period_column = '';
            switch ($period) {
                case '1': $period_column = 'g_prelim'; break;
                case '2': $period_column = 'g_midterm'; break;
                case '3': $period_column = 'g_prefinal'; break;
                case '4': $period_column = 'g_final'; break;
            }
            
            if ($period_column) {
                $avg_stmt = $conn->prepare("UPDATE grades_tb SET $period_column = ? WHERE a_id = ?");
                $avg_stmt->bind_param("di", $period_average, $student_id);
                $avg_stmt->execute();
            }
        }
        
        // Update final grade if all period grades exist
        $final_grade_stmt = $conn->prepare("
            SELECT g_prelim, g_midterm, g_prefinal, g_final 
            FROM grades_tb WHERE a_id = ?
        ");
        $final_grade_stmt->bind_param("i", $student_id);
        $final_grade_stmt->execute();
        $grades = $final_grade_stmt->get_result()->fetch_assoc();
        
        if ($grades && !is_null($grades['g_prelim']) && !is_null($grades['g_midterm']) && 
            !is_null($grades['g_prefinal']) && !is_null($grades['g_final'])) {
            $final_grade = ($grades['g_prelim'] * 0.2) + ($grades['g_midterm'] * 0.2) +
                          ($grades['g_prefinal'] * 0.3) + ($grades['g_final'] * 0.3);
            
            $update_final_stmt = $conn->prepare("UPDATE grades_tb SET g_total = ? WHERE a_id = ?");
            $update_final_stmt->bind_param("di", $final_grade, $student_id);
            $update_final_stmt->execute();
        }
        
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();