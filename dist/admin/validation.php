<?php
function validateCheckupData($health_status, $allergies) {
    if (empty($health_status)) {
        throw new Exception('Health status is required');
    }

    if (strlen($health_status) > 1000) {
        throw new Exception('Health status is too long');
    }

    if (strlen($allergies) > 500) {
        throw new Exception('Allergies field is too long');
    }

    return true;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function formatTime($time) {
    return date('g:i A', strtotime($time));
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}
?>