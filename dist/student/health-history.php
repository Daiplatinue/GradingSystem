<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/auth.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'clinic_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the current user
$notifications_query = "SELECT n.*, c.c_pd, c.c_pt, c.c_rc, c.c_urgent as n_type, c.c_status
                       FROM notification n 
                       JOIN check_up c ON n.c_rc = c.c_id 
                       WHERE n.u_id = ? 
                       ORDER BY n.created_at DESC";

$stmt = $conn->prepare($notifications_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications_result = $stmt->get_result();

// Fetch user data
$user_stmt = $conn->prepare("SELECT u_fn, u_grade, u_hs, u_image FROM user WHERE u_id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

function getTimeAgo($timestamp)
{
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;

    if ($time_difference < 60) {
        return "Just now";
    } elseif ($time_difference < 3600) {
        $minutes = round($time_difference / 60);
        return $minutes . ($minutes == 1 ? " minute ago" : " minutes ago");
    } elseif ($time_difference < 86400) {
        $hours = round($time_difference / 3600);
        return $hours . ($hours == 1 ? " hour ago" : " hours ago");
    } else {
        $days = round($time_difference / 86400);
        return $days . ($days == 1 ? " day ago" : " days ago");
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Health Portal - Notifications</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        dark: {
                            100: '#1E293B',
                            200: '#0F172A',
                            300: '#0F1629',
                            400: '#1E2A4A'
                        }
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-dark-200 text-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-72 bg-dark-100 border-r border-gray-800 fixed h-full">
            <div class="p-6">
                <div class="text-center mb-8">
                    <div class="relative inline-block">
                        <img src="<?php echo !empty($user_data['u_image']) ? htmlspecialchars($user_data['u_image']) : '/uploads/profiles/default.jpg'; ?>"
                            class="w-40 h-40 rounded-full object-cover border-4 border-blue-500">
                    </div>
                    <h4 class="text-xl font-bold mt-3">
                        <?php echo htmlspecialchars($user_data['u_fn']); ?>
                    </h4>
                    <p class="text-gray-400">
                        <?php echo htmlspecialchars($user_data['u_grade']); ?>
                    </p>
                    <div class="mt-3 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-400 border border-green-500/30">
                        <span class="w-2 h-2 mr-2 rounded-full bg-green-400"></span>
                        <?php echo htmlspecialchars($user_data['u_hs']); ?>
                    </div>
                </div>

                <nav class="space-y-2">
                    <a href="student.php"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-dark-400 text-gray-400 hover:text-white transition-all duration-300 transform hover:translate-x-1">
                        <i class="fas fa-newspaper"></i>
                        <span>News Feed</span>
                    </a>
                    <a href="myprofile.php"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-dark-400 text-gray-400 hover:text-white transition-all duration-300 transform hover:translate-x-1">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="health-history.php"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 text-white hover:from-blue-700 hover:to-blue-800 transition-all duration-300 transform hover:translate-x-1">
                        <i class="fas fa-history"></i>
                        <span>Notifications</span>
                    </a>
                    <a href="../auth/login.php"
                        class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-dark-400 text-gray-400 hover:text-white transition-all duration-300 transform hover:translate-x-1">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-72">
            <div class="p-8">
                <h1 class="text-2xl font-bold mb-6">Notifications</h1>

                <div class="space-y-4">
                    <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                        <div class="bg-dark-100 rounded-xl p-6 hover:bg-dark-400 transition-all duration-300">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <?php if ($notification['n_type'] == 'urgent'): ?>
                                        <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                        <h3 class="text-lg font-semibold text-red-400">Urgent: Check-up Update</h3>
                                    <?php else: ?>
                                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                        <h3 class="text-lg font-semibold">Check-up Update</h3>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ml-5">
                                <p class="text-gray-300 mb-3"><?php echo htmlspecialchars($notification['n_schedName']); ?></p>

                                <div class="flex flex-wrap gap-4 text-sm text-gray-400">
                                    <span>
                                        <i class="fas fa-calendar-alt mr-2"></i>
                                        <?php echo date('F j, Y', strtotime($notification['c_pd'])); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clock mr-2"></i>
                                        <?php echo date('g:i A', strtotime($notification['c_pt'])); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-clipboard-list mr-2"></i>
                                        <?php echo htmlspecialchars($notification['c_rc']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <?php if ($notifications_result->num_rows === 0): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-bell-slash text-4xl text-gray-600 mb-4"></i>
                            <p class="text-gray-400">No notifications yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>

</html>