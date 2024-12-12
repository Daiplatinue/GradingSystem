<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/auth.php");
    header("Location: ../student/check-up.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'clinic_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u_fn, u_email, u_bt, u_grade, u_hs, u_h, u_gender, u_allergy, u_age, u_image FROM user WHERE u_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();
$stmt->close();

$stmt2 = $conn->prepare("SELECT c_nc FROM check_up WHERE u_id = ? ORDER BY c_lc DESC LIMIT 1");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$check_up_data = $result2->fetch_assoc();
$stmt2->close();

$conn->close();

$next_checkup = $check_up_data['c_nc'] ?? 'N/A';
$last_checkup = $check_up_data['c_lc'] ?? 'N/A';
$user_name = $user_data['u_fn'] ?? 'User';
$email = $user_data['u_email'] ?? 'N/A';
$blood_type = $user_data['u_bt'] ?? 'N/A';
$grade = $user_data['u_grade'] ?? 'N/A';
$health_status = $user_data['u_hs'] ?? 'N/A';
$height = $user_data['u_h'] ?? 'N/A';
$gender = $user_data['u_gender'] ?? 'N/A';
$allergy = $user_data['u_allergy'] ?? 'N/A';
$age = $user_data['u_age'] ?? '0';
$primaryContact = $user_data['u_pc'] ?? 'N/A';
$primaryNumber = $user_data['u_pcn'] ?? 'N/A';
$secondaryContact = $user_data['u_sc'] ?? 'N/A';
$secondaryNumber = $user_data['u_scn'] ?? 'N/A';
$userImage = $user_data['u_image'] ?? '';


if (isset($_POST['submit'])) {
    $file = $_FILES['u_image'];
    $file_name = $file['name'];
    $tempname = $file['tmp_name'];
    $folder = '../uploads/profiles/' . $file_name;

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        echo "<h2>Invalid file type. Only JPG, PNG and GIF are allowed.</h2>";
        exit;
    }

    $query = $conn->prepare("UPDATE user SET u_image = ? WHERE u_id = ?");
    $query->bind_param("si", $file_name, $user_id);

    if ($query->execute() && move_uploaded_file($tempname, $folder)) {
        echo "<h2>Image Updated Successfully!</h2>";
    } else {
        echo "<h2>Upload Error!</h2>";
    }
    $query->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Health Portal - My Profile</title>
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
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="hidden lg:flex lg:flex-col w-64 border-r border-gray-700 bg-gray-800">
            <div class="flex-1 flex flex-col min-h-0">
                <div class="flex items-center h-16 flex-shrink-0 px-4 border-b border-gray-700">
                    <span class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-teal-400 text-transparent bg-clip-text">MediTrack</span>
                </div>
                <div class="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
                    <div class="flex-1 px-3 space-y-1">
                        <a href="doctor.php" class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-700">
                            <i class="fas fa-home w-6 h-6 mr-3"></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="checkup.php" class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-700">
                            <i class="fas fa-stethoscope w-6 h-6 mr-3"></i>
                            <span>Check-ups</span>
                        </a>
                        <a href="myaccount.php" class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg bg-blue-900/50 text-blue-100">
                            <i class="fas fa-user w-6 h-6 mr-3"></i>
                            <span>My Account</span>
                        </a>
                        <a href="../auth/login.php" class="group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg text-gray-300 hover:bg-gray-700">
                            <i class="fas fa-sign-out-alt w-6 h-6 mr-3"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
                <div class="flex-shrink-0 flex border-t justify-center border-gray-700 p-4">
                    <div class="flex items-center">
                        <div>
                            <img class="h-10 w-10 rounded-full object-cover ring-2 ring-green-400"
                                src="<?php echo !empty($user_data['u_image']) ? htmlspecialchars($user_data['u_image']) : '/uploads/profiles/'; ?>"
                                alt="Profile">
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white">
                                <?php echo htmlspecialchars($user_name); ?>
                            </p>
                            <p class="text-xs text-gray-400">Doctor</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Navigation -->
            <header class="bg-dark-100/95 backdrop-blur-md border-b border-gray-800 px-4 lg:px-6 py-4 ">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <h1 class="text-2xl font-bold">My Profile</h1>
                </div>
            </header>

            <div class="container mx-auto px-6 py-8">
                <div class="max-w-4xl mx-auto">
                    <!-- Profile Header -->
                    <div class="bg-dark-100 rounded-xl p-8 mb-6 animate-fade-in">
                        <div class="flex flex-col md:flex-row items-center">
                            <div class="relative mb-6 md:mb-0 md:mr-8">
                                <img
                                    src="<?php echo !empty($user_data['u_image']) ? htmlspecialchars($user_data['u_image']) : '/uploads/profiles/'; ?>"
                                    alt="Profile Picture" class="w-40 h-40 rounded-full border-4 border-blue-500">
                                <button class="absolute bottom-2 right-2 bg-blue-600 p-2 rounded-full hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <div class="text-center md:text-left flex-1">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-3xl font-bold mb-2">
                                            <?php echo htmlspecialchars($user_name); ?>
                                        </h2>
                                        <p class="text-gray-400 mb-4">
                                            <?php echo htmlspecialchars($user_id); ?>
                                        </p>
                                    </div>
                                    <button onclick="openEditModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center mt-[-8rem]">
                                        <i class="fas fa-edit mr-2"></i>
                                        Edit Profile
                                    </button>
                                </div>
                                <div class="flex flex-wrap justify-center md:justify-start gap-3">
                                    <span class="px-4 py-2 bg-dark-300 rounded-lg text-blue-400">
                                        <i class="fas fa-user-md mr-2"></i>Doctor
                                    </span>
                                    <span class="px-4 py-2 bg-dark-300 rounded-lg text-green-400">
                                        <i class="fas fa-circle mr-2"></i>Available
                                    </span>
                                    <span class="px-4 py-2 bg-dark-300 rounded-lg text-purple-400">
                                        <i class="fas fa-calendar mr-2"></i>Joined 2023
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-dark-100 rounded-xl p-6 animate-fade-in">
                            <h3 class="text-xl font-semibold mb-4">Personal Information</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Full Name</span>
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($user_name); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Email</span>
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($email); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Age</span>
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($age); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Gender</span>
                                    <span class="font-medium">
                                        <?php echo htmlspecialchars($gender); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-dark-100 rounded-xl p-6 animate-fade-in">
                            <h3 class="text-xl font-semibold mb-4">Work Information</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Status</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-500/20 text-green-400">
                                        <span class="w-2 h-2 mr-2 rounded-full bg-green-400"></span>
                                        Available
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Department</span>
                                    <span class="font-medium">BS - Information Technology</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Working Hours</span>
                                    <span class="font-medium">8:00 AM - 4:00 PM</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Experience</span>
                                    <span class="font-medium">10+ years</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="bg-dark-100 rounded-xl p-6 animate-fade-in">
                        <h3 class="text-xl font-semibold mb-4">Account Settings</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Email Notifications</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">SMS Alerts</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Two-Factor Auth</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400">Profile Visibility</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-700 peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL -->

                    <div id="editModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full p-4 z-50">
                        <div class="m-auto bg-dark-100 rounded-xl p-8 w-full max-w-2xl animate-fade-in">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="text-2xl font-bold">Edit Profile</h3>
                                <button onclick="closeEditModal()" class="text-gray-400 hover:text-white">
                                    <i class="fas fa-times text-xl"></i>
                                </button>
                            </div>
                            <form method="POST" id="editForm" onsubmit="handleSubmit(event)" enctype="multipart/form-data">
                                <!-- Profile Image Section -->
                                <div class="text-center mb-6">
                                    <div class="relative inline-block">
                                        <img id="imagePreview"
                                            src="<?php echo !empty($user_data['u_image']) ? htmlspecialchars($user_data['u_image']) : '/uploads/profiles/'; ?>"
                                            class="w-40 h-40 rounded-full object-cover border-4 border-blue-500">
                                        <button type="button"
                                            onclick="document.getElementById('profileImage').click()"
                                            class="absolute bottom-2 right-2 bg-blue-600 p-2 rounded-full hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                    <input type="file"
                                        id="profileImage"
                                        name="profile_image"
                                        accept="image/*"
                                        class="hidden"
                                        onchange="previewImage(event)">
                                </div>

                                <!-- Personal Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-gray-400 mb-2">Full Name</label>
                                        <input type="text" name="fullName" value="<?php echo htmlspecialchars($user_data['u_fn']); ?>"
                                            class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 mb-2">Age</label>
                                        <input type="number" name="age" value="<?php echo htmlspecialchars($age); ?>"
                                            class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 mb-2">Gender</label>
                                        <select name="gender" class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                                            <option value="male" <?php echo $gender === 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo $gender === 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo $gender === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 mb-2">Blood Type</label>
                                        <select name="bloodType" class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                                            <?php
                                            $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                            foreach ($bloodTypes as $type) {
                                                echo '<option value="' . $type . '"' . ($blood_type === $type ? ' selected' : '') . '>' . $type . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 mb-2">Height (cm)</label>
                                        <input type="number" name="height" value="<?php echo htmlspecialchars($height); ?>"
                                            class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                                    </div>

                                </div>
                                <div class="flex justify-end space-x-4 mt-8">
                                    <button type="button" onclick="closeEditModal()"
                                        class="px-6 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600">
                                        Cancel
                                    </button>
                                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>


                    <script src="profile.js"></script>
</body>

</html>