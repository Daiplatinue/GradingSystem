<?php

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

// Handle AJAX request for notifications
if (isset($_GET['check_notifications'])) {
    // Fetch appeal status
    $stmt = $conn->prepare("SELECT ap_status FROM appeal_tb WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = ['hasNotification' => false, 'message' => ''];

    if ($result->num_rows > 0) {
        $appeal = $result->fetch_assoc();
        $response['hasNotification'] = true;
        
        if ($appeal['ap_status'] === 'accepted') {
            $response['message'] = 'Your appeal has been approved!';
            $response['type'] = 'success';
        } else if ($appeal['ap_status'] === 'rejected') {
            $response['message'] = 'Your appeal has been disapproved.';
            $response['type'] = 'error';
        } else {
            $response['message'] = 'Your appeal is still pending.';
            $response['type'] = 'pending';
        }
    }

    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!-- Notification Modal -->
<div id="notificationModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Notifications</h3>
            <div id="notificationContent" class="p-4 rounded-lg mb-4">
                <!-- Notification content will be inserted here -->
            </div>
            <div class="flex justify-end">
                <button
                    onclick="document.getElementById('notificationModal').classList.add('hidden')"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function checkNotifications() {
    fetch('student_notification.php?check_notifications=1')
        .then(response => response.json())
        .then(data => {
            const notificationContent = document.getElementById('notificationContent');
            
            if (data.hasNotification) {
                let bgColor = 'bg-gray-100';
                let textColor = 'text-gray-800';
                
                if (data.type === 'success') {
                    bgColor = 'bg-green-100';
                    textColor = 'text-green-800';
                } else if (data.type === 'error') {
                    bgColor = 'bg-red-100';
                    textColor = 'text-red-800';
                } else if (data.type === 'pending') {
                    bgColor = 'bg-yellow-100';
                    textColor = 'text-yellow-800';
                }
                
                notificationContent.className = `p-4 rounded-lg ${bgColor} ${textColor}`;
                notificationContent.textContent = data.message;
            } else {
                notificationContent.className = 'p-4 rounded-lg bg-gray-100 text-gray-800';
                notificationContent.textContent = 'No notifications available.';
            }
            
            document.getElementById('notificationModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while checking notifications');
        });
}

// Initialize notification button when document is ready
document.addEventListener('DOMContentLoaded', function() {
    const notificationButton = document.querySelector('[data-notification-button]');
    if (notificationButton) {
        notificationButton.addEventListener('click', checkNotifications);
    }
});
</script>