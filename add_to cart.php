<?php
session_start();

// Подключение к базе данных
$host = 'mysql';
$dbname = 'watch_store';
$username = 'root'; // Замените на ваше имя пользователя
$password = 'root'; // Замените на ваш пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); // Перенаправляем обратно на главную страницу
    exit;
}

// Получение ID товара из POST-запроса
$productId = (int)$_POST['product_id'] ?? 0;
if ($productId <= 0) {
    header('Location: index.php'); // Если ID некорректен, возвращаемся на главную страницу
    exit;
}

// Проверяем существование товара
$productStmt = $pdo->prepare("SELECT * FROM Product WHERE id = ?");
$productStmt->execute([$productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php'); // Если товар не найден, возвращаемся на главную страницу
    exit;
}

// Проверяем, есть ли уже этот товар в корзине
$cartStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
$cartStmt->execute([$_SESSION['user_id'], $productId]);
$cartItem = $cartStmt->fetch(PDO::FETCH_ASSOC);

if ($cartItem) {
    // Если товар уже в корзине, увеличиваем количество
    $updateStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
    $updateStmt->execute([$cartItem['id']]);
} else {
    // Иначе добавляем новый товар в корзину
    $insertStmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insertStmt->execute([$_SESSION['user_id'], $productId, 1]);
}

// Перенаправление обратно на главную страницу
header('Location: index.php');
exit;