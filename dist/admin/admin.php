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

// Handle account status updates
if (isset($_POST['update_status'])) {
    $user_id = $_POST['user_id'];
    $status = $_POST['update_status'];

    $update_stmt = $conn->prepare("UPDATE acc_tb SET a_status = ? WHERE a_id = ?");
    $update_stmt->bind_param("si", $status, $user_id);

    if ($update_stmt->execute()) {
        $_SESSION['message'] = "Account status updated successfully!";
    } else {
        $_SESSION['message'] = "Error updating account status!";
    }
    $update_stmt->close();
}

// Fetch all users (students and staff)
$stmt = $conn->prepare("
    SELECT a.*, 
           g.g_science1, g.g_science2, g.g_science3, g.g_science4,
           g.g_math1, g.g_math2, g.g_math3, g.g_math4,
           g.g_programming1, g.g_programming2, g.g_programming3, g.g_programming4,
           g.g_reed1, g.g_reed2, g.g_reed3, g.g_reed4,
           g.g_prelim, g.g_midterm, g.g_prefinal, g.g_final, g.g_total 
    FROM acc_tb a 
    LEFT JOIN grades_tb g ON a.a_id = g.a_id 
    WHERE a.a_type IN ('student', 'staff')
");
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch pending accounts
$pending_stmt = $conn->prepare("SELECT * FROM acc_tb WHERE a_status = 'pending'");
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_users = $pending_result->fetch_all(MYSQLI_ASSOC);
$pending_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../style.css">
</head>

<body class="bg-gray-100 p-6">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $_SESSION['message']; ?></span>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Create Account Button -->
    <div class="mb-4">
        <button onclick="window.location.href='create_account.php'"
            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            Create New Account
        </button>
    </div>

    <!-- Pending Accounts Section -->
    <?php if (!empty($pending_users)): ?>
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Pending Accounts</h2>
            <div class="overflow-x-auto">
                <table class="table-auto border-collapse border border-gray-300 w-full text-center">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="border border-gray-300 px-4 py-2">Full Name</th>
                            <th class="border border-gray-300 px-4 py-2">Email</th>
                            <th class="border border-gray-300 px-4 py-2">Type</th>
                            <th class="border border-gray-300 px-4 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['a_fn']); ?></td>
                                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['a_email']); ?></td>
                                <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['a_type']); ?></td>
                                <td class="border border-gray-300 px-4 py-2">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="user_id" value="<?php echo $user['a_id']; ?>">
                                        <button type="submit" name="update_status" value="active"
                                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm mr-2">
                                            Accept
                                        </button>
                                        <button type="submit" name="update_status" value="declined"
                                            class="bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-sm">
                                            Decline
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- All Users Section -->
    <div class="overflow-x-auto">
        <h2 class="text-xl font-bold mb-4">All Users</h2>
        <table class="table-auto border-collapse border border-gray-300 w-full text-center">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-300 px-4 py-2">Full Name</th>
                    <th class="border border-gray-300 px-4 py-2">Email</th>
                    <th class="border border-gray-300 px-4 py-2">Type</th>
                    <th class="border border-gray-300 px-4 py-2">Status</th>
                    <th class="border border-gray-300 px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['a_fn']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['a_email']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['a_type']); ?></td>
                        <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($user['a_status']); ?></td>
                        <td class="border border-gray-300 px-4 py-2">
                            <button onclick="showModal(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">
                                View Details
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        <button onclick="window.location.href='../auth/login.php'" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
            Logout
        </button>
    </div>

    <!-- Student Modal -->
    <div id="studentModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Student Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Profile Image Section -->
                    <div class="text-center">
                        <div id="profileImageContainer" class="mb-4">
                            <!-- Image will be inserted here via JavaScript -->
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="mb-4">
                            <input type="hidden" name="student_id" id="modalStudentId">
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
                        <input type="hidden" name="student_id" id="modalStudentIdForm">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Full Name</label>
                            <input type="text" name="fullname" id="modalFullname" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Grade</label>
                            <input type="text" name="grade" id="modalGrade" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                            <input type="text" name="gender" id="modalGender" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Age</label>
                            <input type="number" name="age" id="modalAge" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Primary Contact</label>
                            <input type="text" name="primary_contact" id="modalPrimaryContact" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Primary Contact Number</label>
                            <input type="text" name="primary_contact_number" id="modalPrimaryContactNumber" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="flex items-center justify-between mt-4">
                            <button type="submit" name="update_account" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
                <div class="flex items-center justify-end mt-4">
                    <button type="button" onclick="closeModal()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showModal(student) {
            document.getElementById('modalStudentId').value = student.a_id;
            document.getElementById('modalStudentIdForm').value = student.a_id;
            document.getElementById('modalFullname').value = student.a_fn;
            document.getElementById('modalGrade').value = student.a_grade;
            document.getElementById('modalGender').value = student.a_gender;
            document.getElementById('modalAge').value = student.a_age;
            document.getElementById('modalPrimaryContact').value = student.a_pc;
            document.getElementById('modalPrimaryContactNumber').value = student.a_pcn;

            const imageContainer = document.getElementById('profileImageContainer');
            if (student.a_image) {
                imageContainer.innerHTML = `
                <img src="../uploads/profiles/${student.a_image}" 
                     alt="Profile Picture" 
                     class="mx-auto w-32 h-32 rounded-full object-cover mb-2">
            `;
            } else {
                imageContainer.innerHTML = `
                <div class="mx-auto w-32 h-32 rounded-full bg-gray-300 flex items-center justify-center mb-2">
                    <span class="text-gray-600">No Image</span>
                </div>
            `;
            }

            document.getElementById('studentModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('studentModal').classList.add('hidden');
        }

        window.onclick = function(event) {
            const modal = document.getElementById('studentModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>