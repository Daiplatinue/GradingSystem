<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? null;
    $email = $_POST['email'] ?? null;
    $bloodtype = $_POST['bloodtype'] ?? null;
    $password = $_POST['password'] ?? null;
    $type = $_POST['type'] ?? 'student';

    $grade = $_POST['grade'] ?? 'N/A';
    $healthStatus = $_POST['healthStatus'] ?? 'Healthy';
    $height = $_POST['height'] ?? 'N/A';
    $gender = $_POST['gender'] ?? 'others';
    $allergy = $_POST['allergy'] ?? 'None';
    $age = $_POST['age'] ?? '0';
    $primaryContact = $_POST['pc'] ?? 'N/A';
    $primaryNumber = $_POST['pn'] ?? 'N/A';
    $secondaryContact = $_POST['sc'] ?? 'N/A';
    $secondaryNumber = $_POST['sn'] ?? 'N/A';
    $userImage = $_POST['u_image'] ?? './src/';

    if (!$fullname || !$email || !$bloodtype || !$password || !$type) {
        die("Please fill in all the required fields.");
    }

    $conn = new mysqli('localhost', 'root', '', 'clinic_db');

    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    }

    $check_query = "SELECT * FROM user WHERE u_email = ?";
    $stmt = $conn->prepare($check_query);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<script>alert('Error: Email already exists. Please use a different email address.');</script>";
            $stmt->close();
        } else {
            $insert_query = "INSERT INTO user (u_fn, u_email, u_bt, u_password, u_type, u_grade, u_hs, u_h, u_gender, u_allergy, u_age, u_pc, u_sc, u_pcn, u_scn, u_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);

            if ($insert_stmt) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $insert_stmt->bind_param(
                    "ssssssssssisssss",
                    $fullname,
                    $email,
                    $bloodtype,
                    $hashed_password,
                    $type,
                    $grade,
                    $healthStatus,
                    $height,
                    $gender,
                    $allergy,
                    $age,
                    $primaryContact,
                    $primaryNumber,
                    $secondaryContact,
                    $secondaryNumber,
                    $userImage
                );

                if ($insert_stmt->execute()) {
                    session_start();
                    $_SESSION['user_id'] = $insert_stmt->insert_id;
                    $_SESSION['user_name'] = $fullname;
                    $_SESSION['blood_type'] = $bloodtype;
                    $_SESSION['grade'] = $grade;
                    $_SESSION['health_status'] = $healthStatus;
                    $_SESSION['height'] = $height;
                    $_SESSION['gender'] = $gender;
                    $_SESSION['allergy'] = $allergy;
                    $_SESSION['age'] = $age;
                    $_SESSION['pc'] = $primaryContact;
                    $_SESSION['pn'] = $primaryNumber;
                    $_SESSION['sn'] = $secondaryContact;
                    $_SESSION['sc'] = $secondaryNumber;
                    $_SESSION['image'] = $userImage;

                    if ($type === 'doctor') {
                        header("Location: ../doctor/doctor.php");
                    } elseif ($type === 'student') {
                        header("Location: ../student/student.php");
                    } else {
                        echo "User type not recognized.";
                    }
                    exit;
                } else {
                    echo "Error: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            } else {
                echo "Error: " . $conn->error;
            }
        }
    } else {
        echo "Error: " . $conn->error;
    }

    $conn->close();
}
