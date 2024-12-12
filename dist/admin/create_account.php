<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/auth.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $type = $_POST['type'];
    $grade = $_POST['grade'] ?? '';
    $gender = $_POST['gender'];
    $age = $_POST['age'];
    $primary_contact = $_POST['primary_contact'];
    $primary_contact_number = $_POST['primary_contact_number'];
    $status = 'active'; // Admin-created accounts are automatically active

    // Handle image upload
    $image_filename = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file = $_FILES['profile_image'];
        
        if (in_array($file['type'], $allowed_types)) {
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $image_filename = uniqid() . '_' . time() . '.' . $file_extension;
            move_uploaded_file($file['tmp_name'], $upload_dir . $image_filename);
        }
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into acc_tb
        $stmt = $conn->prepare("INSERT INTO acc_tb (a_fn, a_email, a_password, a_type, a_grade, a_gender, a_age, a_pc, a_pcn, a_image, a_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssissss", $fullname, $email, $password, $type, $grade, $gender, $age, $primary_contact, $primary_contact_number, $image_filename, $status);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating account");
        }
        
        $new_user_id = $conn->insert_id;
        $stmt->close();

        // If user is a student, create grades record with NULL values
        if ($type === 'student') {
            $grades_stmt = $conn->prepare("INSERT INTO grades_tb (a_id) VALUES (?)");
            $grades_stmt->bind_param("i", $new_user_id);
            
            if (!$grades_stmt->execute()) {
                throw new Exception("Error creating grades record");
            }
            $grades_stmt->close();
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Account created successfully!";
        header("Location: admin.php");
        exit;

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-6">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-2xl mx-4">
        <h1 class="text-2xl font-bold mb-6">Create New Account</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Basic Information -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Full Name *</label>
                    <input type="text" name="fullname" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Email *</label>
                    <input type="email" name="email" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Password *</label>
                    <input type="password" name="password" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>
                
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Type *</label>
                    <select name="type" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            onchange="toggleGradeField(this.value)">
                        <option value="staff">Staff</option>
                        <option value="student">Student</option>
                    </select>
                </div>

                <!-- Personal Information -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Gender *</label>
                    <select name="gender" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Age *</label>
                    <input type="number" name="age" required min="1" max="150"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div id="gradeField" class="hidden">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Grade</label>
                    <input type="text" name="grade"
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <!-- Contact Information -->
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Primary Contact Name *</label>
                    <input type="text" name="primary_contact" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Primary Contact Number *</label>
                    <input type="text" name="primary_contact_number" required
                           class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <!-- Profile Image -->
                <div class="md:col-span-2">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Profile Image</label>
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/gif"
                           class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-sm text-gray-500 mt-1">Accepted formats: JPEG, PNG, GIF</p>
                </div>
            </div>

            <div class="flex items-center justify-between mt-6">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Create Account
                </button>
                <a href="admin.php"
                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        function toggleGradeField(type) {
            const gradeField = document.getElementById('gradeField');
            gradeField.style.display = type === 'student' ? 'block' : 'none';
        }
    </script>
</body>
</html>