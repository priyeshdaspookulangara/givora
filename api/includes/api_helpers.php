<?php
// api/includes/api_helpers.php

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

function sendJsonResponse($success, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'success' => (bool)$success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

function getJsonInput() {
    $rawInput = file_get_contents('php://input');
    $decoded = json_decode($rawInput, true);
    return is_array($decoded) ? $decoded : $_POST;
}

function generateApiToken($pdo, $user_type, $user_id, $expiryDays = 30) {
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

    $stmt = $pdo->prepare("INSERT INTO api_tokens (user_type, user_id, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_type, $user_id, $token, $expires_at]);

    return [
        'token' => $token,
        'expires_at' => $expires_at
    ];
}

function authenticateApiRequest($pdo, $required_user_type = null) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!preg_match('/Bearer\s+(\S+)/i', $authHeader, $matches)) {
        sendJsonResponse(false, 'Unauthorized. Missing or invalid Authorization Bearer token.', null, 401);
    }

    $token = $matches[1];

    $stmt = $pdo->prepare("SELECT * FROM api_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenRecord = $stmt->fetch();

    if (!$tokenRecord) {
        sendJsonResponse(false, 'Invalid or expired token. Please login again.', null, 401);
    }

    if ($required_user_type && $tokenRecord['user_type'] !== $required_user_type) {
        sendJsonResponse(false, 'Forbidden. Insufficient permissions for this endpoint.', null, 403);
    }

    // Fetch user record
    if ($tokenRecord['user_type'] === 'member') {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $stmt->execute([$tokenRecord['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            sendJsonResponse(false, 'Member user account no longer exists.', null, 401);
        }
        return [
            'token' => $tokenRecord,
            'user_type' => 'member',
            'user' => $user
        ];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$tokenRecord['user_id']]);
        $admin = $stmt->fetch();
        if (!$admin) {
            sendJsonResponse(false, 'Admin account no longer exists.', null, 401);
        }
        return [
            'token' => $tokenRecord,
            'user_type' => 'admin',
            'user' => $admin
        ];
    }
}
