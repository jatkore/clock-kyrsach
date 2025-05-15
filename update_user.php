<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'message' => 'Неавторизованный доступ']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['full_name']) || !isset($data['phone']) || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Недостающие данные']);
    exit;
}

$host = 'mysql';
$dbname = 'watch_store';
$username = 'root';
$password = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
    $stmt->execute([
        $data['full_name'],
        $data['phone'],
        $data['email'],
        $_SESSION['user_id']
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}