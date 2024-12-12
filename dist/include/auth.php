<?php
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized access');
    }
    
    return $_SESSION['user_id'];
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>