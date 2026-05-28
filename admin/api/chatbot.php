<?php
require_once __DIR__ . '/../core/bootstrap.php';

Auth::requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$config = require __DIR__ . '/../config/config.php';
$tokenName = $config['security']['csrf_token_name'];
$token = $input[$tokenName] ?? $_POST[$tokenName] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

if (!CSRF::validate($token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please refresh the page.']);
    exit;
}

$message = trim($input['message'] ?? $_POST['message'] ?? '');

if ($message === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter a message.']);
    exit;
}

$reply = generateChatbotReply($message);

echo json_encode([
    'success' => true,
    'reply'   => $reply,
]);
