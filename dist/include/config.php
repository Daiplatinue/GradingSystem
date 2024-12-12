<?php
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads/profiles');
define('UPLOADS_URL', '/uploads/profiles');

// Ensure uploads directory exists
if (!file_exists(UPLOADS_PATH)) {
    mkdir(UPLOADS_PATH, 0777, true);
}
?>