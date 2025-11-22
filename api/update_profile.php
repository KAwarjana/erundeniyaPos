
<?php
// api/update_profile.php
require_once '../config.php';
require_once '../auth.php';

Auth::requireAuth();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$fullName = trim($data['full_name'] ?? '');
$email = trim($data['email'] ?? '');
$userInfo = Auth::getUserInfo();

$conn = getDBConnection();
$stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ? WHERE user_id = ?");
$stmt->bind_param("ssi", $fullName, $email, $userInfo['user_id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}
?>