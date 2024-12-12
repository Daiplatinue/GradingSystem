<?php
header('Content-Type: text/html; charset=UTF-8');

require_once '../config/db.php';
require_once '../student/userModel.php';
require_once '../include/image-upload.php';
require_once '../include/auth.php';

function sendErrorResponse($message) {
    echo '<div class="error-message">' . htmlspecialchars($message) . '</div>';
    exit;
}

function sendSuccessResponse($data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

try {
    $userId = checkAuth();
    
    $userModel = new UserModel();

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $imageUploader = new ImageUploader();
        $imagePath = $imageUploader->upload($_FILES['profile_image'], $userId);
        
        if ($imagePath) {
            $updateData = ['u_image' => $imagePath];
            if ($userModel->updateProfile($userId, $updateData)) {
                sendSuccessResponse(['image_path' => $imagePath]);
            } else {
                sendErrorResponse('Failed to update image in database');
            }
        } else {
            sendErrorResponse('Failed to upload image');
        }
    }

    $fieldMappings = [
        'fullName' => 'u_fn',
        'age' => 'u_age',
        'gender' => 'u_gender',
        'bloodType' => 'u_bt',
        'grade' => 'u_grade',
        'height' => 'u_h',
        'allergies' => 'u_allergy',
        'primaryContact' => 'u_pc',
        'primaryNumber' => 'u_pcn',
        'secondaryContact' => 'u_sc',
        'secondaryNumber' => 'u_scn'
    ];

    $updateData = [];
    foreach ($fieldMappings as $formField => $dbField) {
        if (isset($_POST[$formField])) {
            $value = trim($_POST[$formField]);
            
            if (in_array($dbField, ['u_age', 'u_h']) && $value !== '') {
                if (!is_numeric($value)) {
                    sendErrorResponse("Invalid value for " . $formField);
                }
            }
            
            $updateData[$dbField] = $value;
        }
    }

    if (!empty($updateData)) {
        if ($userModel->updateProfile($userId, $updateData)) {
            sendSuccessResponse(['message' => 'Profile updated successfully']);
        } else {
            sendErrorResponse('Failed to update profile data');
        }
    }

    sendSuccessResponse(['message' => 'No changes were made']);

} catch (Exception $e) {
    sendErrorResponse($e->getMessage());
}
?>