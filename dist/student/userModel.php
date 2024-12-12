<?php
require_once __DIR__ . '/../config/db.php';

class UserModel {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getUserProfile($userId) {
        $conn = $this->db->connect();
        $stmt = $conn->prepare("SELECT u_fn, u_bt, u_grade, u_hs, u_h, u_gender, u_allergy, u_age, u_pc, u_pcn, u_sc, u_scn, u_image FROM user WHERE u_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        $stmt->close();

        // Handle image path
        if ($userData && $userData['u_image']) {
            $userData['u_image'] = $this->getImageUrl($userData['u_image']);
        }

        return $userData;
    }
    
    public function updateProfile($userId, $data) {
        $conn = $this->db->connect();
        $updates = [];
        $types = "";
        $values = [];
        
        foreach ($data as $field => $value) {
            $updates[] = "$field = ?";
            $types .= "s";
            $values[] = $value;
        }
        
        if (empty($updates)) {
            return false;
        }
        
        $values[] = $userId;
        $types .= "i";
        
        $sql = "UPDATE user SET " . implode(", ", $updates) . " WHERE u_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }

    private function getImageUrl($imagePath) {
        if (!$imagePath) return '/uploads/profiles/default-avatar.png';
        
        // If path starts with http/https, it's already a full URL
        if (preg_match('/^https?:\/\//', $imagePath)) {
            return $imagePath;
        }
        
        // Remove any leading dots or slashes
        $imagePath = ltrim($imagePath, './');
        
        // If path starts with uploads, add leading slash
        if (strpos($imagePath, 'uploads/') === 0) {
            return '/' . $imagePath;
        }
        
        // If path contains uploads somewhere in the middle
        if (strpos($imagePath, 'uploads/') !== false) {
            return '/' . substr($imagePath, strpos($imagePath, 'uploads/'));
        }
        
        return '/' . $imagePath;
    }
}
?>