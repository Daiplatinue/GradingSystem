<?php
class ImageUploader {
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private $maxFileSize = 5242880; // 5MB
    
    public function __construct() {
        $this->uploadDir = dirname(__DIR__) . '/../uploads/profiles/';
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }
    
    public function upload($file, $userId) {
        // Validate file
        if (!$this->validateFile($file)) {
            return false;
        }
        
        // Generate safe filename
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
        $targetPath = $this->uploadDir . $fileName;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Return path relative to uploads directory
            return '/uploads/profiles/' . $fileName;
        }
        
        return false;
    }
    
    private function validateFile($file) {
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds limit of 5MB');
        }
        
        // Check file type
        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG and GIF are allowed');
        }
        
        return true;
    }
}
?>