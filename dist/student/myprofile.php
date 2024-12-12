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
                        <label class="block text-gray-400 mb-2">Grade/Year Level</label>
                        <input type="text" name="grade" value="<?php echo htmlspecialchars($grade); ?>"
                            class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                    </div>
                    <div>
                        <label class="block text-gray-400 mb-2">Height (cm)</label>
                        <input type="number" name="height" value="<?php echo htmlspecialchars($height); ?>"
                            class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-gray-400 mb-2">Allergies</label>
                        <textarea name="allergies" rows="2"
                            class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white"><?php echo htmlspecialchars($allergy); ?></textarea>
                    </div>
                </div>

                <!-- Emergency Contacts -->
                <div class="border-t border-gray-700 pt-6">
                    <h4 class="text-lg font-semibold mb-4">Emergency Contacts</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-400 mb-2">Primary Contact Name</label>
                            <input type="text" name="primaryContact" value="<?php echo htmlspecialchars($primaryContact); ?>"
                                class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Primary Contact Number</label>
                            <input type="tel" name="primaryNumber" value="<?php echo htmlspecialchars($primaryNumber); ?>"
                                class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Secondary Contact Name</label>
                            <input type="text" name="secondaryContact" value="<?php echo htmlspecialchars($secondaryContact); ?>"
                                class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                        </div>
                        <div>
                            <label class="block text-gray-400 mb-2">Secondary Contact Number</label>
                            <input type="tel" name="secondaryNumber" value="<?php echo htmlspecialchars($secondaryNumber); ?>"
                                class="w-full bg-dark-400 border border-gray-700 rounded-lg px-4 py-2 text-white">
                        </div>
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