<?php
session_start();

// Настройка логгирования
$debugLog = '/tmp/payment_debug.log';
file_put_contents($debugLog, "=== Начало обработки заказа ===\n", FILE_APPEND);

try {
    // Проверка авторизации
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Пользователь не авторизован");
    }

    // Подключение к БД
    $pdo = new PDO('mysql:host=mysql;dbname=watch_store', 'root', 'root', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Получаем товары из сессии
    $checkoutItems = $_SESSION['checkout_items'] ?? [];
    if (empty($checkoutItems)) {
        throw new Exception("Корзина пуста");
    }

    file_put_contents($debugLog, "Товары для оформления: " . print_r($checkoutItems, true) . "\n", FILE_APPEND);

    $pdo->beginTransaction();

    foreach ($checkoutItems as $item) {
        // 1. Получаем полные данные товара
        $stmt = $pdo->prepare("
            SELECT name, brand_name, color_name, image_path, price, quantity 
            FROM Product 
            WHERE id = :product_id
        ");
        $stmt->execute(['product_id' => $item['product_id']]);
        $product = $stmt->fetch();

        file_put_contents($debugLog, "Данные товара: " . print_r($product, true) . "\n", FILE_APPEND);

        if (!$product) {
            file_put_contents($debugLog, "Товар не найден, пропускаем\n", FILE_APPEND);
            continue;
        }

        // 2. Проверяем количество
        if ($product['quantity'] < $item['cart_quantity']) {
            throw new Exception("Недостаточно товара '{$product['name']}' в наличии");
        }

        // 3. Формируем данные для заказа
        $orderData = [
            'user_id' => $_SESSION['user_id'],
            'product_name' => $product['name'],
            'product_brand' => $product['brand_name'],
            'product_color' => $product['color_name'],
            'product_image' => $product['image_path'],
            'quantity' => $item['cart_quantity'],
            'total_price' => $product['price'] * $item['cart_quantity']
        ];

        file_put_contents($debugLog, "Данные заказа: " . print_r($orderData, true) . "\n", FILE_APPEND);

        // 4. Добавляем заказ
        $stmt = $pdo->prepare("
            INSERT INTO Orders 
            (user_id, product_name, product_brand, product_color, product_image, quantity, total_price) 
            VALUES 
            (:user_id, :product_name, :product_brand, :product_color, :product_image, :quantity, :total_price)
        ");

        if (!$stmt->execute($orderData)) {
            $error = $stmt->errorInfo();
            throw new Exception("Ошибка записи заказа: " . $error[2]);
        }

        $orderId = $pdo->lastInsertId();
        file_put_contents($debugLog, "Заказ создан, ID: $orderId\n", FILE_APPEND);

        // 5. Обновляем количество товара
        $newQuantity = $product['quantity'] - $item['cart_quantity'];
        $stmt = $pdo->prepare("UPDATE Product SET quantity = :quantity WHERE id = :product_id");
        $stmt->execute([
            'quantity' => $newQuantity,
            'product_id' => $item['product_id']
        ]);

        // 6. Удаляем товар если количество = 0
        if ($newQuantity <= 0) {
            $stmt = $pdo->prepare("DELETE FROM Product WHERE id = :product_id");
            $stmt->execute(['product_id' => $item['product_id']]);
            file_put_contents($debugLog, "Товар {$item['product_id']} удален\n", FILE_APPEND);
        }

        // 7. Удаляем из корзины
        $stmt = $pdo->prepare("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            'id' => $item['id'],
            'user_id' => $_SESSION['user_id']
        ]);
    }

    $pdo->commit();
    file_put_contents($debugLog, "Транзакция успешно завершена\n", FILE_APPEND);

    unset($_SESSION['checkout_items']);
    header('Location: thankyou.php');
    exit;

} catch (Exception $e) {
    file_put_contents($debugLog, "ОШИБКА: " . $e->getMessage() . "\n", FILE_APPEND);

    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Очищаем вывод перед отображением ошибки
    while (ob_get_level()) ob_end_clean();
    die("Ошибка при оформлении заказа: " . $e->getMessage());
}