<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'staff') {
    header("Location: ../auth/auth.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'grading_db');
if ($conn->connect_error) {
    die('Connection Failed: ' . $conn->connect_error);
}

// Handle grade updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_grades'])) {
    $student_id = $_POST['student_id'];
    $period = $_POST['period'];
    $science = $_POST['science'];
    $math = $_POST['math'];
    $programming = $_POST['programming'];
    $reed = $_POST['reed'];
    
    // Calculate period average
    $period_average = ($science + $math + $programming + $reed) / 4;
    
    // Check if student already has grades
    $check_stmt = $conn->prepare("SELECT * FROM grades_tb WHERE a_id = ?");
    $check_stmt->bind_param("i", $student_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing grades
        $sql = "";
        switch($period) {
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
        $update_stmt->close();
    } else {
        // Insert new grades
        $sql = "";
        switch($period) {
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
        $insert_stmt->close();
    }
    
    // Calculate and update final grade if all period grades exist
    $final_grade_stmt = $conn->prepare("SELECT g_prelim, g_midterm, g_prefinal, g_final FROM grades_tb WHERE a_id = ?");
    $final_grade_stmt->bind_param("i", $student_id);
    $final_grade_stmt->execute();
    $grades_result = $final_grade_stmt->get_result();
    $grades = $grades_result->fetch_assoc();
    
    if ($grades && !is_null($grades['g_prelim']) && !is_null($grades['g_midterm']) && 
        !is_null($grades['g_prefinal']) && !is_null($grades['g_final'])) {
        $final_grade = ($grades['g_prelim'] * 0.2) + ($grades['g_midterm'] * 0.2) + 
                      ($grades['g_prefinal'] * 0.3) + ($grades['g_final'] * 0.3);
        
        $update_final_stmt = $conn->prepare("UPDATE grades_tb SET g_total = ? WHERE a_id = ?");
        $update_final_stmt->bind_param("di", $final_grade, $student_id);
        $update_final_stmt->execute();
        $update_final_stmt->close();
    }
    
    if ($success) {
        $_SESSION['message'] = "Grades updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating grades!";
    }
    
    header("Location: staff.php");
    exit;
}

// Fetch all students and their grades
$stmt = $conn->prepare("
    SELECT a.*, g.* 
    FROM acc_tb a 
    LEFT JOIN grades_tb g ON a.a_id = g.a_id 
    WHERE a.a_type = 'student' AND a.a_status = 'active'
");
$stmt->execute();
$result = $stmt->get_result();
$students = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="bg-gray-100 p-6">
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

    <div class="overflow-x-auto">
        <h2 class="text-2xl font-bold mb-4">Student List</h2>
        <table class="table-auto border-collapse border border-gray-300 w-full text-center">
            <thead class="bg-gray-200">
                <tr>
                    <th class="border border-gray-300 px-4 py-2">Full Name</th>
                    <th class="border border-gray-300 px-4 py-2">Email</th>
                    <th class="border border-gray-300 px-4 py-2">Grade</th>
                    <th class="border border-gray-300 px-4 py-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                <tr>
                    <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($student['a_fn']); ?></td>
                    <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($student['a_email']); ?></td>
                    <td class="border border-gray-300 px-4 py-2"><?php echo htmlspecialchars($student['a_grade']); ?></td>
                    <td class="border border-gray-300 px-4 py-2">
                        <button onclick="showGradesModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm mr-2">
                            View Grades
                        </button>
                        <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($student)); ?>)"
                                class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">
                            Edit Grades
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- View Grades Modal -->
    <div id="viewGradesModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Student Grades</h3>
                    <p class="text-lg"><strong>Student:</strong> <span id="viewModalStudentName"></span></p>
                </div>

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
                                <td class="border border-gray-300 px-4 py-2" id="viewScience1">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewScience2">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewScience3">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewScience4">-</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2 font-semibold">Mathematics</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewMath1">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewMath2">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewMath3">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewMath4">-</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2 font-semibold">Programming</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewProg1">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewProg2">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewProg3">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewProg4">-</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 px-4 py-2 font-semibold">Reed</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewReed1">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewReed2">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewReed3">-</td>
                                <td class="border border-gray-300 px-4 py-2" id="viewReed4">-</td>
                            </tr>
                            <tr class="bg-gray-100">
                                <td class="border border-gray-300 px-4 py-2 font-bold">Period Average</td>
                                <td class="border border-gray-300 px-4 py-2 font-bold" id="viewPrelim">-</td>
                                <td class="border border-gray-300 px-4 py-2 font-bold" id="viewMidterm">-</td>
                                <td class="border border-gray-300 px-4 py-2 font-bold" id="viewPrefinal">-</td>
                                <td class="border border-gray-300 px-4 py-2 font-bold" id="viewFinal">-</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="bg-gray-100 p-4 rounded-lg mb-4">
                        <p class="text-lg font-bold">Final Grade: <span id="viewFinalGrade" class="text-blue-600">-</span></p>
                    </div>
                </div>

                <div class="flex justify-end gap-4">
                    <button onclick="showEditModal(currentStudent)" 
                            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                        Edit Grades
                    </button>
                    <button onclick="closeViewModal()"
                            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Grades Modal -->
    <div id="editGradeModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-3/4 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Edit Grades</h3>
                    <p class="text-lg"><strong>Student:</strong> <span id="editModalStudentName"></span></p>
                </div>

                <form id="gradeForm" method="POST" class="space-y-4">
                    <input type="hidden" id="modalStudentId" name="student_id">
                    <input type="hidden" id="modalPeriod" name="period">
                    <input type="hidden" id="modalPeriodName" name="period_name">

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Select Period:</label>
                        <select id="gradePeriod" onchange="updatePeriodFields()" 
                                class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            <option value="1" data-name="prelim">Prelim</option>
                            <option value="2" data-name="midterm">Midterm</option>
                            <option value="3" data-name="prefinal">Pre-Final</option>
                            <option value="4" data-name="final">Final</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Science</label>
                            <input type="number" name="science" id="modalScience" min="0" max="100" step="0.01" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Mathematics</label>
                            <input type="number" name="math" id="modalMath" min="0" max="100" step="0.01" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Programming</label>
                            <input type="number" name="programming" id="modalProgramming" min="0" max="100" step="0.01" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Reed</label>
                            <input type="number" name="reed" id="modalReed" min="0" max="100" step="0.01" required
                                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4">
                        <button type="submit" name="update_grades"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Save Changes
                        </button>
                        <button type="button" onclick="closeEditModal()"
                                class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    let currentStudent = null;

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
            switch(period) {
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
                    document.getElementById('modalReed').value = currentStudent.g_reed4 || '';
                    break;
            }
        }
    }

    // Close modals when clicking outside
    window.onclick = function(event) {
        const viewModal = document.getElementById('viewGradesModal');
        const editModal = document.getElementById('editGradeModal');
        if (event.target == viewModal) {
            closeViewModal();
        }
        if (event.target == editModal) {
            closeEditModal();
        }
    }
    </script>
</body>
</html>