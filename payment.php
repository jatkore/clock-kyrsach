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

// Получаем выбранные товары из сессии
$checkoutItems = $_SESSION['checkout_items'] ?? [];

if (empty($checkoutItems)) {
    die("Ошибка: Нет выбранных товаров для оформления.");
}

try {
    $pdo->beginTransaction();

    foreach ($checkoutItems as $item) {
        // Проверяем, существует ли товар в таблице cart
        $stmt = $pdo->prepare("SELECT id FROM cart WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'id' => $item['id'],
            'user_id' => $_SESSION['user_id']
        ]);

        if ($stmt->rowCount() === 0) {
            // Если товар отсутствует, игнорируем его и продолжаем обработку
            continue;
        }

        // Уменьшаем количество товара в таблице Product
        $stmt = $pdo->prepare("UPDATE Product SET quantity = quantity - :quantity WHERE id = :id");
        $stmt->execute([
            'id' => $item['product_id'],
            'quantity' => $item['cart_quantity']
        ]);

        // Проверяем, успешно ли обновилось количество
        if ($stmt->rowCount() === 0) {
            throw new Exception("Не удалось обновить количество товара с ID {$item['product_id']}");
        }

        // Если количество товара стало <= 0, удаляем товар из таблицы Product
        $stmt = $pdo->prepare("SELECT quantity FROM Product WHERE id = :id");
        $stmt->execute(['id' => $item['product_id']]);
        $remainingQuantity = $stmt->fetchColumn();

        if ($remainingQuantity <= 0) {
            $stmt = $pdo->prepare("DELETE FROM Product WHERE id = :id");
            $stmt->execute(['id' => $item['product_id']]);
        }



        // После уменьшения количества товара и удаления из корзины:
        foreach ($checkoutItems as $item) {
            // Получаем цену товара
            $stmt = $pdo->prepare("SELECT price FROM Product WHERE id = :id");
            $stmt->execute(['id' => $item['product_id']]);
            $price = $stmt->fetchColumn();

            // Добавляем запись в таблицу Orders
            $stmt = $pdo->prepare("INSERT INTO Orders (product_id, quantity, total_price) VALUES (:product_id, :quantity, :total_price)");
            $stmt->execute([
                'product_id' => $item['product_id'],
                'quantity' => $item['cart_quantity'],
                'total_price' => $price * $item['cart_quantity']
            ]);
        }


        // Удаляем товар из таблицы cart
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = :id");
        $stmt->execute(['id' => $item['id']]);

        // Если товар успешно удален, продолжаем
        if ($stmt->rowCount() === 0) {
            // Если товар не был удален, это нормально (он мог быть удален ранее)
            continue;
        }
    }

    $pdo->commit();

    // Очищаем выбранные товары из сессии
    unset($_SESSION['checkout_items']);

    // Перенаправляем пользователя на страницу благодарности
    header('Location: thankyou.php');
    exit;

} catch (Exception $e) {
    // Откатываем транзакцию при ошибке
    $pdo->rollBack();
    die("Ошибка при оформлении заказа: " . $e->getMessage());
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
    <?php foreach ($itemsToOrder as $item): ?>
        <div class="order-item">
            <img src="uploads/<?= htmlspecialchars($item['image_path']) ?>" height="100">
            <div class="details">
                <h3><?= htmlspecialchars($item['name']) ?></h3>
                <p>Цена: <?= htmlspecialchars($item['price']) ?> ₽</p>
                <p>Количество: <?= htmlspecialchars($item['cart_quantity']) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="total">
    <p>Итого: <?= array_sum(array_map(fn($item) => $item['price'] * $item['cart_quantity'], $itemsToOrder)) ?> ₽</p>
</div>

<form method="POST" action="">
    <button type="submit" class="checkout-btn">Оплатить выбранные товары</button>
</form>
</body>
</html>