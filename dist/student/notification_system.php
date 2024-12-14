<?php
session_start();

// Check authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/auth.php");
    exit;
}

// Database connection
function connectDB() {
    $conn = new mysqli('localhost', 'root', '', 'grading_db');
    if ($conn->connect_error) {
        die('Connection Failed: ' . $conn->connect_error);
    }
    return $conn;
}

// Get notification status
function getNotificationStatus($user_id) {
    $conn = connectDB();
    $stmt = $conn->prepare("SELECT ap_status FROM appeal_tb WHERE student_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response = ['hasNotification' => false, 'message' => '', 'type' => ''];
    
    if ($result->num_rows > 0) {
        $appeal = $result->fetch_assoc();
        $response['hasNotification'] = true;
        
        switch ($appeal['ap_status']) {
            case 'accepted':
                $response['message'] = 'Your appeal has been approved!';
                $response['type'] = 'success';
                break;
            case 'rejected':
                $response['message'] = 'Your appeal has been disapproved.';
                $response['type'] = 'error';
                break;
            default:
                $response['message'] = 'Your appeal is still pending.';
                $response['type'] = 'pending';
        }
    }
    
    $stmt->close();
    $conn->close();
    return $response;
}

// Handle AJAX request
if (isset($_GET['check_notifications'])) {
    header('Content-Type: application/json');
    echo json_encode(getNotificationStatus($_SESSION['user_id']));
    exit;
}
?>

<!-- Notification Button -->
<button
    data-notification-button
    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"/>
    </svg>
    Notifications
</button>

<!-- Notification Modal -->
<div id="notificationModal" class="fixed hidden inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                <button
                    onclick="document.getElementById('notificationModal').classList.add('hidden')"
                    class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="notificationContent" class="p-4 rounded-lg mb-4">
                <!-- Notification content will be inserted here -->
            </div>
        </div>
    </div>
</div>

<script>
// Notification handling
const checkNotifications = async () => {
    try {
        const response = await fetch('notification_system.php?check_notifications=1');
        if (!response.ok) throw new Error('Network response was not ok');
        
        const data = await response.json();
        const notificationContent = document.getElementById('notificationContent');
        
        // Set notification styling based on type
        const styles = {
            success: ['bg-green-100', 'text-green-800'],
            error: ['bg-red-100', 'text-red-800'],
            pending: ['bg-yellow-100', 'text-yellow-800'],
            default: ['bg-gray-100', 'text-gray-800']
        };
        
        const [bgColor, textColor] = styles[data.hasNotification ? data.type : 'default'] || styles.default;
        
        // Update notification content
        notificationContent.className = `p-4 rounded-lg ${bgColor} ${textColor}`;
        notificationContent.textContent = data.hasNotification ? data.message : 'No notifications available.';
        
        // Show modal
        document.getElementById('notificationModal').classList.remove('hidden');
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while checking notifications');
    }
};

// Close modal when clicking outside
document.getElementById('notificationModal').addEventListener('click', (e) => {
    if (e.target === e.currentTarget) {
        e.target.classList.add('hidden');
    }
});

// Initialize notification button
document.addEventListener('DOMContentLoaded', () => {
    const notificationButton = document.querySelector('[data-notification-button]');
    if (notificationButton) {
        notificationButton.addEventListener('click', checkNotifications);
    }
});
</script>