<?php
// Handle grade updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grades'])) {
    $student_id = $_POST['student_id'];
    $period = $_POST['period'];
    $science = $_POST['science'];
    $math = $_POST['math'];
    $programming = $_POST['programming'];
    $reed = $_POST['reed'];

    // Calculate period average
    $period_average = ($science + $math + $programming + $reed) / 4;

    // Check if student already has grades
    $check_stmt = $conn->prepare("SELECT * FROM grades_tb WHERE a_id = ?");
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing grades
        $sql = "";
        switch ($period) {
            case '1':
                $sql = "UPDATE grades_tb SET g_science1 = ?, g_math1 = ?, g_programming1 = ?, g_reed1 = ?, g_prelim = ? WHERE a_id = ?";
                break;
            case '2':
                $sql = "UPDATE grades_tb SET g_science2 = ?, g_math2 = ?, g_programming2 = ?, g_reed2 = ?, g_midterm = ? WHERE a_id = ?";
                break;
            case '3':
                $sql = "UPDATE grades_tb SET g_science3 = ?, g_math3 = ?, g_programming3 = ?, g_reed3 = ?, g_prefinal = ? WHERE a_id = ?";
                break;
            case '4':
                $sql = "UPDATE grades_tb SET g_science4 = ?, g_math4 = ?, g_programming4 = ?, g_reed4 = ?, g_final = ? WHERE a_id = ?";
                break;
        }

        $update_stmt = $conn->prepare($sql);
        $update_stmt->bind_param("dddddi", $science, $math, $programming, $reed, $period_average, $student_id);
        $success = $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new grades
        $sql = "";
        switch ($period) {
            case '1':
                $sql = "INSERT INTO grades_tb (a_id, g_science1, g_math1, g_programming1, g_reed1, g_prelim) VALUES (?, ?, ?, ?, ?, ?)";
                break;
            case '2':
                $sql = "INSERT INTO grades_tb (a_id, g_science2, g_math2, g_programming2, g_reed2, g_midterm) VALUES (?, ?, ?, ?, ?, ?)";
                break;
            case '3':
                $sql = "INSERT INTO grades_tb (a_id, g_science3, g_math3, g_programming3, g_reed3, g_prefinal) VALUES (?, ?, ?, ?, ?, ?)";
                break;
            case '4':
                $sql = "INSERT INTO grades_tb (a_id, g_science4, g_math4, g_programming4, g_reed4, g_final) VALUES (?, ?, ?, ?, ?, ?)";
                break;
        }

        $insert_stmt = $conn->prepare($sql);
        $insert_stmt->bind_param("iddddd", $student_id, $science, $math, $programming, $reed, $period_average);
        $success = $insert_stmt->execute();
        $insert_stmt->close();
    }

    updateFinalGrade($conn, $student_id);

    if ($success) {
        $_SESSION['message'] = "Grades updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating grades!";
    }

    header("Location: staff.php");
    exit;
}

function updateFinalGrade($conn, $student_id) {
    $final_grade_stmt = $conn->prepare("SELECT g_prelim, g_midterm, g_prefinal, g_final FROM grades_tb WHERE a_id = ?");
    $final_grade_stmt->bind_param("i", $student_id);
    $final_grade_stmt->execute();
    $grades_result = $final_grade_stmt->get_result();
    $grades = $grades_result->fetch_assoc();

    if (
        $grades && !is_null($grades['g_prelim']) && !is_null($grades['g_midterm']) &&
        !is_null($grades['g_prefinal']) && !is_null($grades['g_final'])
    ) {
        $final_grade = ($grades['g_prelim'] * 0.2) + ($grades['g_midterm'] * 0.2) +
            ($grades['g_prefinal'] * 0.3) + ($grades['g_final'] * 0.3);

        $update_final_stmt = $conn->prepare("UPDATE grades_tb SET g_total = ? WHERE a_id = ?");
        $update_final_stmt->bind_param("di", $final_grade, $student_id);
        $update_final_stmt->execute();
        $update_final_stmt->close();
    }
}
?>