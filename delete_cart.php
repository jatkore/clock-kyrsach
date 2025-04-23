<?php
session_start();

$host = 'mysql';
$dbname = 'watch_store';
$username = 'root';
$password = 'root';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка подключения к БД']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $cartId = intval($data['cart_id']);

    if ($cartId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        exit;
    }

    try {
        // Удаляем товар из корзины
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = :id");
        $stmt->execute(['id' => $cartId]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при удалении товара: ' . $e->getMessage()]);
    }
}