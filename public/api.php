<?php
// public/api.php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/functions.php';
require_once __DIR__ . '/../src/Controllers/ChatController.php';
require_once __DIR__ . '/../src/Controllers/ConnectionController.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Oturum gerekli']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

switch ($action) {
    case 'chat':
        $controller = new ChatController();
        $response = $controller->handle($input);
        echo json_encode($response);
        break;

    case 'test_ai':
        $controller = new ConnectionController();
        $response = $controller->testAI();
        echo json_encode($response);
        break;

    case 'test_db':
        $controller = new ConnectionController();
        $response = $controller->testDB($input);
        echo json_encode($response);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Geçersiz işlem']);
        break;
}
?>