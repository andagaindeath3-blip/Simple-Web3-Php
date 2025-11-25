<?php
header('Content-Type: application/json');
require 'JwtToken.php'; // Твой класс из предыдущего ответа

JwtToken::setSecret('my-very-strong-secret-key-2025-andagaindeath3'); // Твой секрет

$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => true];

try {
    if ($input['action'] === 'create') {
        $payload = [
            'user_id' => (int)$input['user_id'],
            'username' => $input['username'],
            'role' => $input['role']
        ];
        $token = JwtToken::create($payload, (int)$input['expires']);
        $response['token'] = $token;
        $response['message'] = 'Токен создан успешно!';
    } elseif ($input['action'] === 'verify') {
        $payload = JwtToken::verify($input['token']);
        $response['payload'] = $payload;
        $response['message'] = 'Токен валиден!';
    } else {
        throw new Exception('Неизвестное действие');
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>