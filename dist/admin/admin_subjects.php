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

// Get current admin ID
$current_admin_id = $_SESSION['user_id'];

// Handle adding new subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_name = $_POST['subject_name'];
    // Generate a unique code by removing spaces and special characters
    $subject_code = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $subject_name));
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if subject code already exists
        $check_stmt = $conn->prepare("SELECT sub_id FROM subjects_tb WHERE sub_code = ?");
        $check_stmt->bind_param("s", $subject_code);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("A subject with this name already exists.");
        }

        // First, create a new entry in grades_tb to get g_id
        $create_grade_stmt = $conn->prepare("INSERT INTO grades_tb (a_id) VALUES (?)");
        $create_grade_stmt->bind_param("i", $current_admin_id);
        $create_grade_stmt->execute();
        $grade_id = $conn->insert_id;
        
        // Add columns to grades_tb for each period
        $alter_queries = [];
        for ($i = 1; $i <= 4; $i++) {
            $column_name = "g_{$subject_code}{$i}";
            
            // Check if column exists
            $column_check = $conn->query("SHOW COLUMNS FROM grades_tb LIKE '$column_name'");
            if ($column_check->num_rows === 0) {
                $alter_queries[] = "ADD COLUMN $column_name double DEFAULT NULL";
            }
        }
        
        // Execute alter table query if there are columns to add
        if (!empty($alter_queries)) {
            $alter_query = "ALTER TABLE grades_tb " . implode(", ", $alter_queries);
            $conn->query($alter_query);
        }
        
        // Add subject to subjects_tb with g_id
        $current_date = date('Y-m-d H:i:s');
        $insert_stmt = $conn->prepare("INSERT INTO subjects_tb (sub_name, sub_code, sub_createdAt, a_id, g_id) VALUES (?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("sssii", $subject_name, $subject_code, $current_date, $current_admin_id, $grade_id);
        $insert_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        $_SESSION['message'] = "Subject added successfully!";
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['error'] = "Error adding subject: " . $e->getMessage();
    }
    
    header("Location: admin_subjects.php");
    exit;
}

// Fetch all subjects with creator information and grades
$subjects = $conn->query("
    SELECT s.*, a.a_fn as created_by_name, g.*
    FROM subjects_tb s
    LEFT JOIN acc_tb a ON s.a_id = a.a_id
    LEFT JOIN grades_tb g ON s.g_id = g.g_id
    ORDER BY s.sub_name
")->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subject Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Add New Subject</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject Name</label>
                        <input type="text" name="subject_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <button type="submit" name="add_subject"
                        class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Add Subject
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Current Subjects</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Subject Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Subject Code
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created At
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created By
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Grade ID
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($subjects as $subject): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($subject['sub_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($subject['sub_code']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($subject['sub_createdAt']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($subject['created_by_name'] ?? 'System'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($subject['g_id']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                <a href="admin.php" class="inline-block bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</body>
</html>