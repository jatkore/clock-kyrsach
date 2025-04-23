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

// Проверка параметров
if (!isset($_GET['action'], $_GET['cart_id'])) {
    die(json_encode(['success' => false, 'message' => 'Неверные параметры']));
}

$action = $_GET['action'];
$cartId = (int)$_GET['cart_id'];

// Получаем текущее количество
$stmt = $pdo->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
$stmt->execute([$cartId, $_SESSION['user_id']]);
$quantity = $stmt->fetchColumn();

if (!$quantity) {
    die(json_encode(['success' => false, 'message' => 'Товар не найден']));
}

$newQuantity = $quantity;

if ($action === 'increase') {
    $newQuantity++;
} elseif ($action === 'decrease') {
    if ($quantity > 1) {
        $newQuantity--;
    } else {
        die(json_encode(['success' => false, 'message' => 'Количество не может быть меньше 1']));
    }
}

// Обновляем запись в БД
$stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
$stmt->execute([$newQuantity, $cartId, $_SESSION['user_id']]);

echo json_encode(['success' => true, 'new_quantity' => $newQuantity]);
?>