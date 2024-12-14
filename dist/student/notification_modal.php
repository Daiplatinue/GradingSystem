<?php
// This file will be included in your main dashboard file
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
    fetch('check_notification.php')
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

// Add click event to notification button
document.querySelector('[data-notification-button]').addEventListener('click', checkNotifications);
</script>