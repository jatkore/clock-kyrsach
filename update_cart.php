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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $cartId = intval($data['cart_id']);
    $newQuantity = intval($data['quantity']);

    if ($cartId <= 0 || $newQuantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        exit;
    }

    try {
        // Получаем текущее количество товара из таблицы Product
        $stmt = $pdo->prepare("SELECT p.quantity AS product_quantity FROM cart c JOIN Product p ON c.product_id = p.id WHERE c.id = :cart_id");
        $stmt->execute(['cart_id' => $cartId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Товар не найден']);
            exit;
        }

        if ($newQuantity > $product['product_quantity']) {
            echo json_encode(['success' => false, 'message' => 'Превышено максимальное количество товара']);
            exit;
        }

        // Обновляем количество товара в корзине
        $stmt = $pdo->prepare("UPDATE cart SET quantity = :quantity WHERE id = :id");
        $stmt->execute([
            'id' => $cartId,
            'quantity' => $newQuantity
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при обновлении количества: ' . $e->getMessage()]);
    }
}