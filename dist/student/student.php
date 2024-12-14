<?php
session_start();

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/auth.php");
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch student data
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

// Handle form submissions
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
            }
        }
    }
}

// Get notifications
function getNotifications($user_id)
{
    global $conn;
    $stmt = $conn->prepare("
        SELECT n.*, ap.ap_status, ap.ap_description 
        FROM notifications_tb n
        LEFT JOIN appeals_tb ap ON n.ap_id = ap.ap_id
        WHERE n.user_id = ?
        ORDER BY n.n_createdAt DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];

    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['n_id'],
            'status' => $row['ap_status'],
            'description' => $row['n_description'],
            'created_at' => $row['n_createdAt']
        ];
    }

    $stmt->close();
    return $notifications;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_notifications') {
    $notifications = getNotifications($_SESSION['user_id']);
    header('Content-Type: application/json');
    echo json_encode($notifications);
    exit;
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .card {
            transition: transform 0.3s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .grade-card {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-link {
            position: relative;
            overflow: hidden;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #3b82f6;
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }
    </style>
</head>

<body class="min-h-screen">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <img src="https://via.placeholder.com/40" alt="Logo" class="h-8 w-8 rounded-full">
                    <span class="ml-2 text-xl font-semibold text-gray-800">Student Portal</span>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="loadNotifications()" class="nav-link px-3 py-2 text-gray-700 hover:text-blue-600">
                        <i class="fas fa-bell"></i>
                    </button>
                    <button onclick="document.getElementById('accountModal').classList.remove('hidden')" class="nav-link px-3 py-2 text-gray-700 hover:text-blue-600">
                        <i class="fas fa-user"></i>
                    </button>
                    <button onclick="window.location.href='../auth/login.php'" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8 animate__animated animate__fadeIn">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <?php if (!empty($student['a_image'])): ?>
                        <img src="../uploads/profiles/<?php echo htmlspecialchars($student['a_image']); ?>" alt="Profile" class="h-20 w-20 rounded-full object-cover">
                    <?php else: ?>
                        <div class="h-20 w-20 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user text-blue-500 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ml-6">
                    <h1 class="text-2xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($student['a_fn']); ?>!</h1>
                    <p class="text-gray-600">Grade <?php echo htmlspecialchars($student['a_grade']); ?> Student</p>
                </div>
            </div>
        </div>

        <!-- Grades Section -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-800 mb-6">Academic Performance</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php
                $subjects = [
                    ['name' => 'Science', 'grades' => [$student['g_science1'], $student['g_science2'], $student['g_science3'], $student['g_science4']]],
                    ['name' => 'Mathematics', 'grades' => [$student['g_math1'], $student['g_math2'], $student['g_math3'], $student['g_math4']]],
                    ['name' => 'Programming', 'grades' => [$student['g_programming1'], $student['g_programming2'], $student['g_programming3'], $student['g_programming4']]],
                    ['name' => 'Reed', 'grades' => [$student['g_reed1'], $student['g_reed2'], $student['g_reed3'], $student['g_reed4']]]
                ];

                foreach ($subjects as $index => $subject):
                    $avgGrade = array_filter($subject['grades'], function ($grade) {
                        return $grade !== null;
                    });
                    $avgGrade = !empty($avgGrade) ? array_sum($avgGrade) / count($avgGrade) : 0;
                ?>
                    <div class="grade-card bg-gradient-to-br <?php echo $avgGrade >= 75 ? 'from-green-50 to-green-100' : 'from-red-50 to-red-100'; ?> rounded-xl p-6 shadow-sm" style="animation-delay: <?php echo $index * 0.1; ?>s">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-semibold"><?php echo $subject['name']; ?></h3>
                            <span class="text-sm font-medium <?php echo $avgGrade >= 75 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo number_format($avgGrade, 1); ?>%
                            </span>
                        </div>
                        <div class="space-y-2">
                            <?php foreach (['Prelim', 'Midterm', 'Pre-Final', 'Final'] as $i => $period): ?>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600"><?php echo $period; ?></span>
                                    <span class="font-medium"><?php echo $subject['grades'][$i] ?? '-'; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Quick Actions</h3>
                <div class="space-y-4">
                    <button onclick="document.getElementById('appealModal').classList.remove('hidden')"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-paper-plane mr-2"></i>Submit an Appeal
                    </button>
                    <button onclick="document.getElementById('accountModal').classList.remove('hidden')"
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-cog mr-2"></i>Update Profile
                    </button>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Final Grade</h3>
                <div class="relative pt-1">
                    <?php
                    $totalGrade = $student['g_total'] ?? 0;
                    $progressColor = $totalGrade >= 75 ? 'bg-green-500' : 'bg-red-500';
                    ?>
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                        <div style="width:<?php echo $totalGrade; ?>%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center <?php echo $progressColor; ?> transition-all duration-500"></div>
                    </div>
                    <div class="flex justify-between text-sm text-gray-600">
                        <span>Overall Grade</span>
                        <span class="font-semibold"><?php echo $totalGrade; ?>%</span>
                    </div>
                </div>
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

    <!-- Appeal Modal -->
    <div id="appealModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Submit an Appeal</h3>
                <form id="appealForm" onsubmit="handleAppealSubmit(event)">
                    <textarea
                        name="description"
                        class="w-full px-3 py-2 text-gray-700 border rounded-lg focus:outline-none"
                        rows="4"
                        placeholder="Enter your appeal description..."
                        required></textarea>
                    <div class="flex justify-end gap-2 mt-4">
                        <button
                            type="button"
                            onclick="document.getElementById('appealModal').classList.add('hidden')"
                            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg">Cancel</button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-lg">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div id="notificationsModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-[600px] shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                    <button onclick="document.getElementById('notificationsModal').classList.add('hidden')"
                        class="text-gray-500 hover:text-gray-700">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div id="notificationsList" class="space-y-3 max-h-[400px] overflow-y-auto">
                    <!-- Notifications will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadNotifications() {
            const notificationsModal = document.getElementById('notificationsModal');
            const notificationsList = document.getElementById('notificationsList');

            // Show modal and loading state
            notificationsModal.classList.remove('hidden');
            notificationsList.innerHTML = '<div class="text-center py-4">Loading notifications...</div>';

            // Fetch notifications
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (data.notifications.length === 0) {
                            notificationsList.innerHTML = '<div class="text-center py-4 text-gray-500">No notifications</div>';
                            return;
                        }

                        notificationsList.innerHTML = data.notifications.map(notification => `
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-gray-800">${notification.n_description}</p>
                                <p class="text-sm text-gray-500 mt-1">${notification.n_createdAt}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                    } else {
                        notificationsList.innerHTML = '<div class="text-center py-4 text-red-500">Error loading notifications</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    notificationsList.innerHTML = '<div class="text-center py-4 text-red-500">Error loading notifications</div>';
                });
        }

        window.onclick = function(event) {
            const modal = document.getElementById('accountModal');
            const appealModal = document.getElementById('appealModal');
            const notificationsModal = document.getElementById('notificationsModal');
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
            if (event.target == appealModal) {
                appealModal.classList.add('hidden');
            }
            if (event.target == notificationsModal) {
                notificationsModal.classList.add('hidden');
            }
        }

        function handleAppealSubmit(event) {
            event.preventDefault();

            const form = event.target;
            const formData = new FormData(form);

            fetch('handle_appeal.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Appeal submitted successfully!');
                        document.getElementById('appealModal').classList.add('hidden');
                        form.reset();
                    } else {
                        alert('Error submitting appeal: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting the appeal');
                });
        }
    </script>
</body>

</html>