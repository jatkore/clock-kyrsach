<?php
session_start();

// Подключение к базе данных
$host = 'mysql';
$dbname = 'watch_store';
$user = 'root';
$pass = 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Получаем товары из сессии
$items = $_SESSION['order_items'] ?? [];
if (empty($items)) {
    header('Location: cart.php');
    exit;
}

// Обработка POST-запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Начинаем транзакцию для атомарности операций
    $pdo->beginTransaction();
    try {
        foreach ($items as $item) {
            $productId = $item['id']; // Убедитесь, что items содержит поле 'id'

            // Удаляем из таблицы cart
            $stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = :id");
            $stmt->execute(['id' => $productId]);

            // Удаляем из таблицы Product (проверьте название таблицы и полей)
            $stmt = $pdo->prepare("DELETE FROM Product WHERE id = :id"); // Используйте правильное название таблицы
            $stmt->execute(['id' => $productId]);
        }

        // Завершаем транзакцию
        $pdo->commit();
    } catch (Exception $e) {
        // Откатываем изменения при ошибке
        $pdo->rollBack();
        die("Ошибка при удалении товаров: " . $e->getMessage());
    }

    // Очищаем корзину в сессии
    unset($_SESSION['order_items']);

    // Перенаправляем на страницу thankyou.php
    header('Location: thankyou.php');
    exit;
}

// Считаем общую сумму
$total = 0;
foreach ($items as $item) {
    $total += $item['price'] * $item['cart_quantity'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Подтверждение заказа</title>
    <style>
        /* ... Ваш существующий CSS ... */
    </style>
</head>
<body>
<h1>Подтверждение заказа</h1>

<div class="order-items">
    <?php foreach ($items as $item): ?>
        <div class="order-item">
            <img src="uploads/<?= htmlspecialchars($item['image_path'] ?? '') ?>" height="100">
            <div class="details">
                <h3><?= htmlspecialchars($item['name'] ?? '') ?></h3>
                <p>Цена: <?= htmlspecialchars($item['price'] ?? 0) ?> ₽</p>
                <p>Количество: <?= htmlspecialchars($item['cart_quantity'] ?? 0) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="total">
    <p>Итого: <?= $total ?> ₽</p>
</div>

<form method="POST" action="">
    <button type="submit" class="checkout-btn">Оплатить</button>
</form>
</body>
</html>