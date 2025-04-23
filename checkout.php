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
    die(json_encode(['success' => false, 'message' => 'Ошибка БД']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $selectedCartIds = $data['selected_items'] ?? [];

    if (empty($selectedCartIds)) {
        echo json_encode(['success' => false, 'message' => 'Не выбрано ни одного товара']);
        exit;
    }

    try {
        // Получаем информацию о выбранных товарах
        $placeholders = implode(',', array_fill(0, count($selectedCartIds), '?'));
        $stmt = $pdo->prepare("SELECT c.id, c.product_id, c.quantity AS cart_quantity, p.quantity AS product_quantity 
                               FROM cart c 
                               JOIN Product p ON c.product_id = p.id 
                               WHERE c.id IN ($placeholders)");
        $stmt->execute($selectedCartIds);
        $selectedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Проверяем доступность товаров
        foreach ($selectedItems as $item) {
            if ($item['cart_quantity'] > $item['product_quantity']) {
                echo json_encode(['success' => false, 'message' => 'Недостаточно товара на складе']);
                exit;
            }
        }

        // Сохраняем выбранные товары в сессии для дальнейшей обработки
        $_SESSION['checkout_items'] = $selectedItems;

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка при оформлении заказа: ' . $e->getMessage()]);
    }
}