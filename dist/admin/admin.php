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

// Handle image upload
if (isset($_POST['upload_image']) && isset($_FILES['profile_image'])) {
    $user_id = $_POST['student_id'];
    $file = $_FILES['profile_image'];

    // Create uploads directory if it doesn't exist
    $upload_dir = "../uploads/profiles/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid() . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;

        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            // Update database with new image filename
            $update_stmt = $conn->prepare("UPDATE acc_tb SET a_image = ? WHERE a_id = ?");
            $update_stmt->bind_param("si", $new_filename, $user_id);

            if ($update_stmt->execute()) {
                $_SESSION['message'] = "Profile image uploaded successfully!";
            } else {
                $_SESSION['message'] = "Error updating profile image in database.";
            }
            $update_stmt->close();
        } else {
            $_SESSION['message'] = "Error uploading file.";
        }
    } else {
        $_SESSION['message'] = "Invalid file type or size. Please upload a JPG, PNG, or GIF file under 5MB.";
    }

    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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

// Get counts for dashboard metrics
$total_pending = $conn->query("SELECT COUNT(*) as count FROM acc_tb WHERE a_status = 'pending'")->fetch_assoc()['count'];
$total_students = $conn->query("SELECT COUNT(*) as count FROM acc_tb WHERE a_type = 'student'")->fetch_assoc()['count'];
$total_staff = $conn->query("SELECT COUNT(*) as count FROM acc_tb WHERE a_type = 'staff'")->fetch_assoc()['count'];
$total_accounts = $conn->query("SELECT COUNT(*) as count FROM acc_tb WHERE a_type IN ('student', 'staff')")->fetch_assoc()['count'];

// Fetch pending accounts
$pending_stmt = $conn->prepare("SELECT * FROM acc_tb WHERE a_status = 'pending'");
$pending_stmt->execute();
$pending_users = $pending_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pending_stmt->close();

// Fetch all users
$stmt = $conn->prepare("SELECT * FROM acc_tb WHERE a_type IN ('student', 'staff')");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Top Navigation Bar -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Admin Dashboard</h1>
                    </div>
                    <div class="flex items-center">
                        <button onclick="window.location.href='../auth/login.php'"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline"><?php echo $_SESSION['message']; ?></span>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Pending Accounts Card -->
                <div class="bg-orange-100 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-200 bg-opacity-75">
                            <i class="fas fa-clock text-orange-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-orange-600 font-medium">Pending Accounts</p>
                            <p class="text-2xl font-semibold text-orange-700"><?php echo $total_pending; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Students Card -->
                <div class="bg-blue-100 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-200 bg-opacity-75">
                            <i class="fas fa-user-graduate text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-blue-600 font-medium">Total Students</p>
                            <p class="text-2xl font-semibold text-blue-700"><?php echo $total_students; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Staff Card -->
                <div class="bg-green-100 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-200 bg-opacity-75">
                            <i class="fas fa-chalkboard-teacher text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-green-600 font-medium">Total Staff</p>
                            <p class="text-2xl font-semibold text-green-700"><?php echo $total_staff; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Accounts Card -->
                <div class="bg-purple-100 rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-200 bg-opacity-75">
                            <i class="fas fa-users text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-purple-600 font-medium">Total Accounts</p>
                            <p class="text-2xl font-semibold text-purple-700"><?php echo $total_accounts; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Account Management</h2>
                <button onclick="window.location.href='create_account.php'"
                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                    <i class="fas fa-plus mr-2"></i>Create New Account
                </button>
            </div>

            <!-- Pending Accounts Section -->
            <?php if (!empty($pending_users)): ?>
                <div class="bg-white rounded-lg shadow-md mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-800">Pending Accounts</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($pending_users as $user): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['a_fn']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['a_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($user['a_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" class="inline-flex space-x-2">
                                                <input type="hidden" name="user_id" value="<?php echo $user['a_id']; ?>">
                                                <button type="submit" name="update_status" value="active"
                                                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-md">
                                                    Accept
                                                </button>
                                                <button type="submit" name="update_status" value="declined"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md">
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

            <!-- All Accounts Section -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">All Accounts</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['a_fn']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['a_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['a_type'] === 'student' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo htmlspecialchars($user['a_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                            switch ($user['a_status']) {
                                                case 'active':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                case 'pending':
                                                    echo 'bg-yellow-100 text-yellow-800';
                                                    break;
                                                case 'declined':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo htmlspecialchars($user['a_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="showModal(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded-md">
                                            View Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Component -->
    <!-- Modal Component -->
    <div id="studentModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-2/3 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-lg font-medium text-gray-900">Account Details</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <!-- Profile Image Section -->
                    <div class="text-center">
                        <div id="profileImageContainer" class="mb-4">
                            <!-- Image will be inserted here via JavaScript -->
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-3">
                            <input type="hidden" name="student_id" id="modalStudentId">
                            <div class="flex items-center justify-center">
                                <input type="file"
                                    name="profile_image"
                                    accept="image/jpeg,image/png,image/gif"
                                    class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                    required>
                            </div>
                            <button type="submit"
                                name="upload_image"
                                class="w-full bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium py-2 px-4 rounded-md">
                                Upload Image
                            </button>
                        </form>
                    </div>

                    <!-- Account Information Form -->
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="student_id" id="modalStudentIdForm">

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Full Name</label>
                            <input type="text" name="fullname" id="modalFullname"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Grade</label>
                            <input type="text" name="grade" id="modalGrade"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Gender</label>
                            <input type="text" name="gender" id="modalGender"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Age</label>
                            <input type="number" name="age" id="modalAge"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Primary Contact</label>
                            <input type="text" name="primary_contact" id="modalPrimaryContact"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Primary Contact Number</label>
                            <input type="text" name="primary_contact_number" id="modalPrimaryContactNumber"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" onclick="closeModal()"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Cancel
                            </button>
                            <button type="submit" name="update_account"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showModal(user) {
            document.getElementById('modalStudentId').value = user.a_id;
            document.getElementById('modalStudentIdForm').value = user.a_id;
            document.getElementById('modalFullname').value = user.a_fn;
            document.getElementById('modalGrade').value = user.a_grade;
            document.getElementById('modalGender').value = user.a_gender;
            document.getElementById('modalAge').value = user.a_age;
            document.getElementById('modalPrimaryContact').value = user.a_pc;
            document.getElementById('modalPrimaryContactNumber').value = user.a_pcn;

            const imageContainer = document.getElementById('profileImageContainer');
            if (user.a_image) {
                imageContainer.innerHTML = `
                    <img src="../uploads/profiles/${user.a_image}" 
                         alt="Profile Picture" 
                         class="mx-auto w-32 h-32 rounded-full object-cover shadow-md">
                `;
            } else {
                imageContainer.innerHTML = `
                    <div class="mx-auto w-32 h-32 rounded-full bg-gray-200 flex items-center justify-center">
                        <i class="fas fa-user text-gray-400 text-4xl"></i>
                    </div>
                `;
            }

            document.getElementById('studentModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('studentModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('studentModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>