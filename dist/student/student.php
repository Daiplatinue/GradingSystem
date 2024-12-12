<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/auth.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch student's grades
$stmt = $conn->prepare("
    SELECT a.*, g.* 
    FROM acc_tb a 
    LEFT JOIN grades_tb g ON a.a_id = g.a_id 
    WHERE a.a_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_account'])) {
        $fullname = $_POST['fullname'];
        $grade = $_POST['grade'];
        $gender = $_POST['gender'];
        $age = $_POST['age'];
        $primary_contact = $_POST['primary_contact'];
        $primary_contact_number = $_POST['primary_contact_number'];

        $update_stmt = $conn->prepare("UPDATE acc_tb SET a_fn = ?, a_grade = ?, a_gender = ?, a_age = ?, a_pc = ?, a_pcn = ? WHERE a_id = ?");
        $update_stmt->bind_param("ssssssi", $fullname, $grade, $gender, $age, $primary_contact, $primary_contact_number, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['message'] = "Account updated successfully!";
        } else {
            $_SESSION['message'] = "Error updating account!";
        }
        $update_stmt->close();
    }
    
    // Handle image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file = $_FILES['profile_image'];
        
        if (in_array($file['type'], $allowed_types)) {
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_stmt = $conn->prepare("UPDATE acc_tb SET a_image = ? WHERE a_id = ?");
                $image_stmt->bind_param("si", $new_filename, $user_id);
                
                if ($image_stmt->execute()) {
                    $_SESSION['message'] = "Profile image updated successfully!";
                } else {
                    $_SESSION['message'] = "Error updating profile image in database!";
                }
                $image_stmt->close();
            } else {
                $_SESSION['message'] = "Error uploading image file!";
            }
        } else {
            $_SESSION['message'] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gray-100 p-6">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>

    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">My Grades</h1>
            <div class="overflow-x-auto">
                <table class="table-auto border-collapse border border-gray-300 w-full text-center mb-6">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border border-gray-300 px-4 py-2">Subject</th>
                            <th class="border border-gray-300 px-4 py-2">Prelim</th>
                            <th class="border border-gray-300 px-4 py-2">Midterm</th>
                            <th class="border border-gray-300 px-4 py-2">Pre-Final</th>
                            <th class="border border-gray-300 px-4 py-2">Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2 font-semibold">Science</td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_science1'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_science2'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_science3'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_science4'] ?? '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2 font-semibold">Mathematics</td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_math1'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_math2'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_math3'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_math4'] ?? '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2 font-semibold">Programming</td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_programming1'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_programming2'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_programming3'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_programming4'] ?? '-'; ?></td>
                        </tr>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2 font-semibold">Reed</td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_reed1'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_reed2'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_reed3'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?php echo $student['g_reed4'] ?? '-'; ?></td>
                        </tr>
                        <tr class="bg-gray-100">
                            <td class="border border-gray-300 px-4 py-2 font-bold">Period Average</td>
                            <td class="border border-gray-300 px-4 py-2 font-bold"><?php echo $student['g_prelim'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2 font-bold"><?php echo $student['g_midterm'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2 font-bold"><?php echo $student['g_prefinal'] ?? '-'; ?></td>
                            <td class="border border-gray-300 px-4 py-2 font-bold"><?php echo $student['g_final'] ?? '-'; ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="bg-gray-100 p-4 rounded-lg">
                    <p class="text-lg font-bold">Final Grade: <span class="text-blue-600"><?php echo $student['g_total'] ?? '-'; ?></span></p>
                </div>
            </div>
        </div>

        <div class="flex gap-4">
            <button onclick="window.location.href='../auth/login.php'" 
                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                Logout
            </button>
            <button onclick="document.getElementById('accountModal').classList.remove('hidden')" 
                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                View Account
            </button>
        </div>
    </div>

    <!-- Account Modal -->
    <div id="accountModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Account Information</h3>
                
                <!-- Profile Image -->
                <div class="mb-4">
                    <?php if (!empty($student['a_image'])): ?>
                        <img src="../uploads/profiles/<?php echo htmlspecialchars($student['a_image']); ?>" 
                             alt="Profile Picture" 
                             class="mx-auto w-32 h-32 rounded-full object-cover mb-2">
                    <?php else: ?>
                        <div class="mx-auto w-32 h-32 rounded-full bg-gray-300 flex items-center justify-center mb-2">
                            <span class="text-gray-600">No Image</span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Image Upload Form -->
                    <form method="POST" enctype="multipart/form-data" class="mb-4">
                        <div class="flex items-center justify-center">
                            <input type="file" 
                                   name="profile_image" 
                                   accept="image/jpeg,image/png,image/gif" 
                                   class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <button type="submit" 
                                name="upload_image" 
                                class="mt-2 bg-blue-500 hover:bg-blue-700 text-white text-sm font-bold py-1 px-3 rounded">
                            Upload Image
                        </button>
                    </form>
                </div>

                <!-- Account Information Form -->
                <form method="POST" class="text-left">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($student['a_fn']); ?>" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Grade</label>
                        <input type="text" name="grade" value="<?php echo htmlspecialchars($student['a_grade']); ?>" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                        <input type="text" name="gender" value="<?php echo htmlspecialchars($student['a_gender']); ?>" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Age</label>
                        <input type="number" name="age" value="<?php echo htmlspecialchars($student['a_age']); ?>" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Primary Contact</label>
                        <input type="text" name="primary_contact" value="<?php echo htmlspecialchars($student['a_pc']); ?>" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Primary Contact Number</label>
                        <input type="text" name="primary_contact_number" value="<?php echo htmlspecialchars($student['a_pcn']); ?>" 
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    <div class="flex items-center justify-between mt-4">
                        <button type="submit" name="update_account" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update
                        </button>
                        <button type="button" onclick="document.getElementById('accountModal').classList.add('hidden')" 
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Close
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('accountModal');
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        }
    </script>
</body>
</html>