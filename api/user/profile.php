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
    $password = trim($input['password'] ?? $member['password']);

    // Extended Address & Location Fields
    $address_line = trim($input['address_line'] ?? $member['address_line'] ?? '');
    $place = trim($input['place'] ?? $member['place'] ?? '');
    $city = trim($input['city'] ?? $member['city'] ?? '');
    $pincode = trim($input['pincode'] ?? $member['pincode'] ?? '');
    $state = trim($input['state'] ?? $member['state'] ?? 'Kerala');

    // KYC & Bank Fields
    $pan_number = strtoupper(trim($input['pan_number'] ?? $member['pan_number'] ?? ''));
    $aadhaar_number = trim($input['aadhaar_number'] ?? $member['aadhaar_number'] ?? '');
    $bank_name = trim($input['bank_name'] ?? $member['bank_name'] ?? '');
    $bank_account_number = trim($input['bank_account_number'] ?? $member['bank_account_number'] ?? '');
    $ifsc_code = strtoupper(trim($input['ifsc_code'] ?? $member['ifsc_code'] ?? ''));

    // Determine KYC status
    $kyc_status = $member['kyc_status'] ?? 'Pending';
    if (!empty($address_line) && !empty($city) && !empty($pincode) && !empty($pan_number) && !empty($aadhaar_number) && !empty($bank_name) && !empty($bank_account_number) && !empty($ifsc_code)) {
        if ($kyc_status === 'Pending' || $kyc_status === 'Rejected') {
            $kyc_status = 'Submitted';
        }
    }

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

    $stmt = $pdo->prepare("UPDATE members SET name = ?, email = ?, phone = ?, password = ?, profile_image = ?, address_line = ?, place = ?, city = ?, pincode = ?, state = ?, pan_number = ?, aadhaar_number = ?, bank_name = ?, bank_account_number = ?, ifsc_code = ?, kyc_status = ? WHERE member_id = ?");
    $stmt->execute([
        $name, $email, $phone, $password, $profile_image,
        $address_line, $place, $city, $pincode, $state,
        $pan_number, $aadhaar_number, $bank_name, $bank_account_number, $ifsc_code,
        $kyc_status, $member_id
    ]);

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
