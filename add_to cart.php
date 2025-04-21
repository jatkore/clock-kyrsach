<?php
session_start();

// Включаем логирование ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Авторизуйтесь']);
    exit;
}

// Получение данных
$productId = (int)($_POST['product_id'] ?? 0);
if ($productId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Некорректный товар']);
    exit;
}

// Подключение к БД
$host = 'mysql'; // Имя сервиса из docker-compose.yml
$dbname = 'watch_store';
$username = 'root';
$password = 'root'; // Пароль из docker-compose

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ошибка БД']);
    exit;
}

// Проверка товара
$stmt = $pdo->prepare("SELECT * FROM Product WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    exit;
}

// Обработка корзины
$cartStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
$cartStmt->execute([$_SESSION['user_id'], $productId]);
$cartItem = $cartStmt->fetch();

if ($cartItem) {
    $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?")
        ->execute([$cartItem['id']]);
} else {
    $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)")
        ->execute([$_SESSION['user_id'], $productId]);
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Товар добавлен']);
exit;
?>