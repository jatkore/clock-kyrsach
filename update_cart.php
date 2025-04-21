<?php
session_start();

// Подключение к БД
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

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неверный метод']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$cartId = filter_var($data['cart_id'] ?? '', FILTER_VALIDATE_INT);
$quantity = filter_var($data['quantity'] ?? '', FILTER_VALIDATE_INT);

if (!$cartId || !$quantity || $quantity < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Неверные параметры']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE id = :cart_id AND user_id = :user_id");
    $stmt->execute([
        ':quantity' => $quantity,
        ':cart_id' => $cartId,
        ':user_id' => $_SESSION['user_id']
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Товар не найден']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ошибка обновления']);
}
?>