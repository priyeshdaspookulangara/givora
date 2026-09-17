<?php
// api/user/profile.php
require_once __DIR__ . '/../includes/api_helpers.php';

$pdo = getDBConnection();
$auth = authenticateApiRequest($pdo, 'member');
$member = $auth['user'];
$member_id = $member['member_id'];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    unset($member['password']);
    sendJsonResponse(true, 'Profile details fetched successfully.', [
        'profile' => $member
    ]);
} elseif ($method === 'POST') {
    $input = getJsonInput();

    $name = trim($input['name'] ?? $member['name']);
    $email = trim($input['email'] ?? $member['email']);
    $phone = trim($input['phone'] ?? $member['phone']);
    $password = trim($input['password'] ?? '');

    // Profile image upload handling (multipart form)
    $profile_image = $member['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_image']['tmp_name'];
        $fileName = $_FILES['profile_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = __DIR__ . '/../../uploads/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $newFileName = $member_id . '_' . time() . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $profile_image = 'uploads/' . $newFileName;
            }
        }
    }

    if (!empty($password)) {
        $stmt = $pdo->prepare("UPDATE members SET name = ?, email = ?, phone = ?, password = ?, profile_image = ? WHERE member_id = ?");
        $stmt->execute([$name, $email, $phone, $password, $profile_image, $member_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE members SET name = ?, email = ?, phone = ?, profile_image = ? WHERE member_id = ?");
        $stmt->execute([$name, $email, $phone, $profile_image, $member_id]);
    }

    // Fetch updated member record
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $updated_member = $stmt->fetch();
    unset($updated_member['password']);

    sendJsonResponse(true, 'Profile updated successfully.', [
        'profile' => $updated_member
    ]);
} else {
    sendJsonResponse(false, 'Method not allowed.', null, 405);
}
