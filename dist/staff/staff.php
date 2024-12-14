<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../auth/auth.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

// Handle appeal status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appeal_status'])) {
    $appeal_id = $_POST['appeal_id'];
    $status = $_POST['status'];

    $update_stmt = $conn->prepare("UPDATE appeal_tb SET ap_status = ? WHERE ap_id = ?");
    $update_stmt->bind_param("si", $status, $appeal_id);

    if ($update_stmt->execute()) {
        $get_student_stmt = $conn->prepare("SELECT a_id FROM appeal_tb WHERE ap_id = ?");
        $get_student_stmt->bind_param("i", $appeal_id);
        $get_student_stmt->execute();
        $result = $get_student_stmt->get_result();
        $appeal_data = $result->fetch_assoc();
        $student_id = $appeal_data['a_id'];

        $description = ($status === 'Accepted') ? 'Your appeal has been approved' : 'Your appeal has been disapproved';

        $notify_stmt = $conn->prepare("INSERT INTO notification_tb (n_description, a_id, ap_id) VALUES (?, ?, ?)");
        $notify_stmt->bind_param("sii", $description, $student_id, $appeal_id);
        $notify_stmt->execute();

        $_SESSION['message'] = "Appeal status updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating appeal status!";
    }

    header("Location: staff.php");
    exit;
}

// Handle grade updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grades'])) {
    $student_id = $_POST['student_id'];
    $period = $_POST['period'];
    $science = $_POST['science'];
    $math = $_POST['math'];
    $programming = $_POST['programming'];
    $reed = $_POST['reed'];
    $period_average = ($science + $math + $programming + $reed) / 4;

    $check_stmt = $conn->prepare("SELECT * FROM grades_tb WHERE a_id = ?");
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
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
    } else {
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
    }

    // Update final grade
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
    }

    if ($success) {
        $_SESSION['message'] = "Grades updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating grades!";
    }

    header("Location: staff.php");
    exit;
}

// Handle account updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $user_id = $_SESSION['user_id'];
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
        $_SESSION['error'] = "Error updating account!";
    }

    header("Location: staff.php");
    exit;
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_image'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_error = $file['error'];

    if ($file_error === 0) {
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = array('jpg', 'jpeg', 'png', 'gif');

        if (in_array($file_ext, $allowed)) {
            $new_name = uniqid('profile_', true) . "." . $file_ext;
            $upload_path = "../uploads/profiles/" . $new_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $update_stmt = $conn->prepare("UPDATE acc_tb SET a_image = ? WHERE a_id = ?");
                $update_stmt->bind_param("si", $new_name, $user_id);

                if ($update_stmt->execute()) {
                    $_SESSION['message'] = "Profile image updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating profile image in database!";
                }
            } else {
                $_SESSION['error'] = "Error uploading file!";
            }
        } else {
            $_SESSION['error'] = "Invalid file type!";
        }
    } else {
        $_SESSION['error'] = "Error in file upload!";
    }

    header("Location: staff.php");
    exit;
}

// Fetch students with grades
$stmt = $conn->prepare("
    SELECT a.*, g.* 
    FROM acc_tb a 
    LEFT JOIN grades_tb g ON a.a_id = g.a_id 
    WHERE a.a_type = 'student' AND a.a_status = 'active'
");
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch pending appeals
$appeals_stmt = $conn->prepare("
    SELECT ap.*, a.a_fn, a.a_email 
    FROM appeal_tb ap 
    JOIN acc_tb a ON ap.a_id = a.a_id 
    WHERE ap.ap_status = 'Pending'
    ORDER BY ap.ap_id DESC
");
$appeals_stmt->execute();
$pending_appeals = $appeals_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch staff info
$staff_stmt = $conn->prepare("SELECT * FROM acc_tb WHERE a_id = ?");
$staff_stmt->bind_param("i", $_SESSION['user_id']);
$staff_stmt->execute();
$staff_info = $staff_stmt->get_result()->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard | Academic Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .modal {
            transition: opacity 0.25s ease;
        }

        .modal-active {
            overflow-x: hidden;
            overflow-y: visible !important;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <img src="https://images.unsplash.com/photo-1519452635265-7b1fbfd1e4e0?auto=format&fit=crop&q=80&w=50&h=50"
                            alt="School Logo"
                            class="h-8 w-auto">
                        <span class="ml-2 text-xl font-bold text-gray-900">Academic Portal</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="p-2 rounded-full hover:bg-gray-100">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center">
                        <img src="<?php echo !empty($staff_info['a_image']) ? '../uploads/profiles/' . htmlspecialchars($staff_info['a_image']) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=48&h=48&q=80'; ?>"
                            alt="Profile"
                            class="h-8 w-8 rounded-full object-cover">
                        <span class="ml-2 text-sm font-medium text-gray-700"><?php echo htmlspecialchars($staff_info['a_fn']); ?></span>
                    </div>
                    <button onclick="document.getElementById('accountModal').classList.remove('hidden')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        My Account
                    </button>
                    <button onclick="window.location.href='../auth/login.php'"
                        class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="mb-8 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['message']; ?></span>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-8 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; ?></span>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Header Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-50">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo count($students); ?></h2>
                        <p class="text-sm text-gray-500">Total Students</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-50">
                        <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo count($pending_appeals); ?></h2>
                        <p class="text-sm text-gray-500">Pending Appeals</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-50">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-900">95%</h2>
                        <p class="text-sm text-gray-500">Average Performance</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-50">
                        <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h2 class="text-2xl font-bold text-gray-900">4</h2>
                        <p class="text-sm text-gray-500">Active Subjects</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="showTab('students')" class="px-6 py-3 border-b-2 border-blue-500 text-blue-600 font-medium">
                        Students
                    </button>
                    <button onclick="showTab('appeals')" class="px-6 py-3 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium">
                        Appeals
                    </button>
                </nav>
            </div>

            <!-- Students Tab -->
            <div id="studentsTab" class="p-6">
                <div class="flex justify-between items-center mb-6">
                    <div class="relative">
                        <input type="text" placeholder="Search students..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                src="<?php echo !empty($student['a_image']) ? '../uploads/profiles/' . htmlspecialchars($student['a_image']) : 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=40&h=40&q=80'; ?>"
                                                alt="">
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['a_fn']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($student['a_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($student['a_grade']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick="showGradesModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            View Grades
                                        </button>
                                        <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                                            class="ml-2 inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                            Edit Grades
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Appeals Section -->
            <div id="appealsList" class="hidden"></div>

            <!-- Appeals Tab -->
            <div id="appealsTab" class="hidden p-6">
                <?php if (empty($pending_appeals)): ?>
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No pending appeals</h3>
                        <p class="mt-1 text-sm text-gray-500">There are currently no pending appeals to review.</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($pending_appeals as $appeal): ?>
                            <div class="bg-white rounded-lg border border-gray-200 p-6">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($appeal['a_fn']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appeal['a_email']); ?></p>
                                        <p class="mt-2 text-gray-700"><?php echo htmlspecialchars($appeal['ap_message']); ?></p>
                                    </div>
                                    <div class="flex space-x-3">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="appeal_id" value="<?php echo $appeal['ap_id']; ?>">
                                            <input type="hidden" name="status" value="Accepted">
                                            <button type="submit" name="update_appeal_status"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                Accept
                                            </button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="appeal_id" value="<?php echo $appeal['ap_id']; ?>">
                                            <input type="hidden" name="status" value="Declined">
                                            <button type="submit" name="update_appeal_status"
                                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                                Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>

    <!-- View Grades Modal -->
    <div id="viewGradesModal" class="fixed hidden inset-0 bg-black bg-opacity-25 backdrop-blur-sm overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-8 max-w-4xl bg-white rounded-2xl shadow-lg">
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-semibold text-[#1d1d1f]">Student Grades</h3>
                    <p class="text-[#1d1d1f]">Student: <span id="viewModalStudentName" class="font-semibold"></span></p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="px-6 py-4 text-left text-sm font-semibold text-[#86868b]">Subject</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-[#86868b]">Prelim</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-[#86868b]">Midterm</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-[#86868b]">Pre-Final</th>
                                <th class="px-6 py-4 text-center text-sm font-semibold text-[#86868b]">Final</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-[#1d1d1f]">Science</td>
                                <td class="px-6 py-4 text-center" id="viewScience1">-</td>
                                <td class="px-6 py-4 text-center" id="viewScience2">-</td>
                                <td class="px-6 py-4 text-center" id="viewScience3">-</td>
                                <td class="px-6 py-4 text-center" id="viewScience4">-</td>
                            </tr>
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-[#1d1d1f]">Mathematics</td>
                                <td class="px-6 py-4 text-center" id="viewMath1">-</td>
                                <td class="px-6 py-4 text-center" id="viewMath2">-</td>
                                <td class="px-6 py-4 text-center" id="viewMath3">-</td>
                                <td class="px-6 py-4 text-center" id="viewMath4">-</td>
                            </tr>
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-[#1d1d1f]">Programming</td>
                                <td class="px-6 py-4 text-center" id="viewProg1">-</td>
                                <td class="px-6 py-4 text-center" id="viewProg2">-</td>
                                <td class="px-6 py-4 text-center" id="viewProg3">-</td>
                                <td class="px-6 py-4 text-center" id="viewProg4">-</td>
                            </tr>
                            <tr class="border-b border-gray-100">
                                <td class="px-6 py-4 font-medium text-[#1d1d1f]">Reed</td>
                                <td class="px-6 py-4 text-center" id="viewReed1">-</td>
                                <td class="px-6 py-4 text-center" id="viewReed2">-</td>
                                <td class="px-6 py-4 text-center" id="viewReed3">-</td>
                                <td class="px-6 py-4 text-center" id="viewReed4">-</td>
                            </tr>
                            <tr class="bg-[#f5f5f7]">
                                <td class="px-6 py-4 font-semibold text-[#1d1d1f]">Period Average</td>
                                <td class="px-6 py-4 text-center font-semibold" id="viewPrelim">-</td>
                                <td class="px-6 py-4 text-center font-semibold" id="viewMidterm">-</td>
                                <td class="px-6 py-4 text-center font-semibold" id="viewPrefinal">-</td>
                                <td class="px-6 py-4 text-center font-semibold" id="viewFinal">-</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-6 p-6 bg-[#f5f5f7] rounded-xl">
                        <p class="text-lg">Final Grade: <span id="viewFinalGrade" class="font-semibold text-[#0071e3]">-</span></p>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8">
                    <button onclick="showEditModal(currentStudent)"
                        class="px-6 py-2 bg-[#0071e3] text-white font-medium rounded-lg hover:bg-[#0077ed] transition-colors">
                        Edit Grades
                    </button>
                    <button onclick="closeViewModal()"
                        class="px-6 py-2 border border-[#86868b] text-[#1d1d1f] font-medium rounded-lg hover:bg-[#f5f5f7] transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Grades Modal -->
    <div id="editGradeModal" class="fixed hidden inset-0 bg-black bg-opacity-25 backdrop-blur-sm overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-8 max-w-2xl bg-white rounded-2xl shadow-lg">
            <div class="space-y-6">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-semibold text-[#1d1d1f]">Edit Grades</h3>
                    <p class="text-[#1d1d1f]">Student: <span id="editModalStudentName" class="font-semibold"></span></p>
                </div>

                <form id="gradeForm" method="POST" class="space-y-6">
                    <input type="hidden" id="modalStudentId" name="student_id">
                    <input type="hidden" id="modalPeriod" name="period">
                    <input type="hidden" id="modalPeriodName" name="period_name">

                    <div>
                        <label class="block text-sm font-medium text-[#86868b] mb-2">Select Period</label>
                        <select id="gradePeriod" onchange="updatePeriodFields()"
                            class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0071e3] focus:ring focus:ring-[#0071e3] focus:ring-opacity-50">
                            <option value="1" data-name="prelim">Prelim</option>
                            <option value="2" data-name="midterm">Midterm</option>
                            <option value="3" data-name="prefinal">Pre-Final</option>
                            <option value="4" data-name="final">Final</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-[#86868b] mb-2">Science</label>
                            <input type="number" name="science" id="modalScience" min="0" max="100" step="0.01" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0071e3] focus:ring focus:ring-[#0071e3] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#86868b] mb-2">Mathematics</label>
                            <input type="number" name="math" id="modalMath" min="0" max="100" step="0.01" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0071e3] focus:ring focus:ring-[#0071e3] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#86868b] mb-2">Programming</label>
                            <input type="number" name="programming" id="modalProgramming" min="0" max="100" step="0.01" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0071e3] focus:ring focus:ring-[#0071e3] focus:ring-opacity-50">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-[#86868b] mb-2">Reed</label>
                            <input type="number" name="reed" id="modalReed" min="0" max="100" step="0.01" required
                                class="w-full px-4 py-2 rounded-lg border border-gray-200 focus:border-[#0071e3] focus:ring focus:ring-[#0071e3] focus:ring-opacity-50">
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <button type="submit" name="update_grades"
                            class="px-6 py-2 bg-[#0071e3] text-white font-medium rounded-lg hover:bg-[#0077ed] transition-colors">
                            Save Changes
                        </button>
                        <button type="button" onclick="closeEditModal()"
                            class="px-6 py-2 border border-[#86868b] text-[#1d1d1f] font-medium rounded-lg hover:bg-[#f5f5f7] transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
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
        let currentStudent = null;

        function showTab(tabName) {
            const studentTab = document.getElementById('studentsTab');
            const appealsTab = document.getElementById('appealsTab');

            if (tabName === 'students') {
                studentTab.classList.remove('hidden');
                appealsTab.classList.add('hidden');
            } else {
                studentTab.classList.add('hidden');
                appealsTab.classList.remove('hidden');
            }
        }

        function toggleAppeals() {
            const studentList = document.getElementById('studentList');
            const appealsList = document.getElementById('appealsList');
            const toggleBtn = document.getElementById('toggleAppealsBtn');

            if (appealsList.classList.contains('hidden')) {
                // Show appeals
                studentList.classList.add('hidden');
                appealsList.classList.remove('hidden');
                toggleBtn.textContent = 'Show Student List';

                // Fetch appeals data
                fetch('staff.php?fetch_appeals=1')
                    .then(response => response.text())
                    .then(html => {
                        appealsList.innerHTML = html;
                    });
            } else {
                // Show student list
                studentList.classList.remove('hidden');
                appealsList.classList.add('hidden');
                toggleBtn.textContent = 'See Student Appeals';
            }
        }

        function showGradesModal(student) {
            currentStudent = student;
            document.getElementById('viewModalStudentName').textContent = student.a_fn;

            // Update science grades
            document.getElementById('viewScience1').textContent = student.g_science1 || '-';
            document.getElementById('viewScience2').textContent = student.g_science2 || '-';
            document.getElementById('viewScience3').textContent = student.g_science3 || '-';
            document.getElementById('viewScience4').textContent = student.g_science4 || '-';

            // Update math grades
            document.getElementById('viewMath1').textContent = student.g_math1 || '-';
            document.getElementById('viewMath2').textContent = student.g_math2 || '-';
            document.getElementById('viewMath3').textContent = student.g_math3 || '-';
            document.getElementById('viewMath4').textContent = student.g_math4 || '-';

            // Update programming grades
            document.getElementById('viewProg1').textContent = student.g_programming1 || '-';
            document.getElementById('viewProg2').textContent = student.g_programming2 || '-';
            document.getElementById('viewProg3').textContent = student.g_programming3 || '-';
            document.getElementById('viewProg4').textContent = student.g_programming4 || '-';

            // Update reed grades
            document.getElementById('viewReed1').textContent = student.g_reed1 || '-';
            document.getElementById('viewReed2').textContent = student.g_reed2 || '-';
            document.getElementById('viewReed3').textContent = student.g_reed3 || '-';
            document.getElementById('viewReed4').textContent = student.g_reed4 || '-';

            // Update period averages
            document.getElementById('viewPrelim').textContent = student.g_prelim || '-';
            document.getElementById('viewMidterm').textContent = student.g_midterm || '-';
            document.getElementById('viewPrefinal').textContent = student.g_prefinal || '-';
            document.getElementById('viewFinal').textContent = student.g_final || '-';

            // Update final grade
            document.getElementById('viewFinalGrade').textContent = student.g_total || '-';

            document.getElementById('viewGradesModal').classList.remove('hidden');
        }

        function showEditModal(student) {
            currentStudent = student;
            document.getElementById('editModalStudentName').textContent = student.a_fn;
            document.getElementById('modalStudentId').value = student.a_id;
            updatePeriodFields();
            document.getElementById('editGradeModal').classList.remove('hidden');
            document.getElementById('viewGradesModal').classList.add('hidden');
        }

        function closeViewModal() {
            document.getElementById('viewGradesModal').classList.add('hidden');
        }

        function closeEditModal() {
            document.getElementById('editGradeModal').classList.add('hidden');
        }

        function updatePeriodFields() {
            const periodSelect = document.getElementById('gradePeriod');
            const period = periodSelect.value;
            const periodName = periodSelect.options[periodSelect.selectedIndex].dataset.name;

            document.getElementById('modalPeriod').value = period;
            document.getElementById('modalPeriodName').value = periodName;

            if (currentStudent) {
                switch (period) {
                    case '1':
                        document.getElementById('modalScience').value = currentStudent.g_science1 || '';
                        document.getElementById('modalMath').value = currentStudent.g_math1 || '';
                        document.getElementById('modalProgramming').value = currentStudent.g_programming1 || '';
                        document.getElementById('modalReed').value = currentStudent.g_reed1 || '';
                        break;
                    case '2':
                        document.getElementById('modalScience').value = currentStudent.g_science2 || '';
                        document.getElementById('modalMath').value = currentStudent.g_math2 || '';
                        document.getElementById('modalProgramming').value = currentStudent.g_programming2 || '';
                        document.getElementById('modalReed').value = currentStudent.g_reed2 || '';
                        break;
                    case '3':
                        document.getElementById('modalScience').value = currentStudent.g_science3 || '';
                        document.getElementById('modalMath').value = currentStudent.g_math3 || '';
                        document.getElementById('modalProgramming').value = currentStudent.g_programming3 || '';
                        document.getElementById('modalReed').value = currentStudent.g_reed3 || '';
                        break;
                    case '4':
                        document.getElementById('modalScience').value = currentStudent.g_science4 || '';
                        document.getElementById('modalMath').value = currentStudent.g_math4 || '';
                        document.getElementById('modalProgramming').value = currentStudent.g_programming4 || '';
                        document.getElementById('modalRee d').value = currentStudent.g_reed4 || '';
                        break;
                }
            }
        }

        window.onclick = function(event) {
            const viewModal = document.getElementById('viewGradesModal');
            const editModal = document.getElementById('editGradeModal');
            const modal = document.getElementById('accountModal');
            if (event.target == viewModal) {
                closeViewModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = [
                document.getElementById('viewGradesModal'),
                document.getElementById('editGradeModal'),
                document.getElementById('accountModal')
            ];

            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.classList.add('hidden');
                }
            });
        }


        // Initialize search functionality
        document.querySelector('input[placeholder="Search students..."]').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>

</html>