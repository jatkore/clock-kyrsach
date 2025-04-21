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

// Получаем товары по выбранным cart_id
    $stmt = $pdo->prepare("SELECT p.*, c.quantity AS cart_quantity, c.id AS cart_id FROM cart c JOIN Product p ON c.product_id = p.id WHERE c.user_id = ? AND c.id IN (" . implode(',', array_fill(0, count($selectedCartIds), '?')) . ")");
    $params = array_merge([$_SESSION['user_id']], $selectedCartIds);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Сохраняем в сессию
    $_SESSION['order_items'] = $items;

// Очищаем корзину (если нужно)
// $pdo->exec("DELETE FROM cart WHERE id IN (" . implode(',', $selectedCartIds) . ") AND user_id = " . $_SESSION['user_id']);

    echo json_encode(['success' => true]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Неверный метод']);
}
?>