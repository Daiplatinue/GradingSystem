<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        echo "Please fill in all the fields.";
        exit;
    }

    $conn = new mysqli('localhost', 'root', '', 'grading_db');
    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT a_id, a_password, a_type, a_fn, a_grade, a_gender, a_age, a_pc, a_pcn, a_image, a_status FROM acc_tb WHERE a_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $user_type, $fullname, $grade, $gender, $age, $primary_contact, $primary_contact_number, $image, $status);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            // Check account status
            if ($status === 'declined') {
                echo '<script>
                    alert("Your account has been declined. Please contact the support manager.");
                    window.location.href = "login.php";
                </script>';
                exit;
            }

            // Store user data in session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['grade'] = $grade;
            $_SESSION['gender'] = $gender;
            $_SESSION['age'] = $age;
            $_SESSION['primary_contact'] = $primary_contact;
            $_SESSION['primary_contact_number'] = $primary_contact_number;
            $_SESSION['image'] = $image;
            $_SESSION['user_type'] = $user_type;

            // Redirect based on user type
            switch ($user_type) {
                case 'staff':
                    header("Location: ../staff/staff.php");
                    break;
                case 'student':
                    header("Location: ../student/student.php");
                    break;
                case 'admin':
                    header("Location: ../admin/admin.php");
                    break;
                default:
                    echo "Invalid user type!";
                    break;
            }
            exit;
        } else {
            echo "Invalid password!";
        }
    } else {
        echo "No user found with that email!";
    }

    $stmt->close();
    $conn->close();
}
?>