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




if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = $_POST['product_id'];

    // Получаем информацию о товаре
    $stmt = $pdo->prepare("SELECT id, name, price, image_path, quantity FROM Product WHERE id = :id");
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Ошибка: Товар не найден.");
    }

    // Проверяем, существует ли товар уже в корзине
    $existingItem = null;
    foreach ($_SESSION['order_items'] ?? [] as &$item) {
        if ($item['id'] == $productId) {
            $existingItem = $item;
            break;
        }
    }

    if ($existingItem) {
        // Если товар уже в корзине, ничего не делаем (так как добавляется только один экземпляр)
        die("Ошибка: Этот товар уже добавлен в корзину.");
    } else {
        // Если товара нет в корзине, добавляем с количеством 1

        // Добавляем товар в таблицу cart
        $stmt = $pdo->prepare("INSERT INTO cart (product_id, quantity) VALUES (:product_id, :quantity)");
        $stmt->execute([
            'product_id' => $productId,
            'quantity' => 1
        ]);

        // Добавляем товар в сессию
        $_SESSION['order_items'][] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image_path' => $product['image_path'],
            'cart_quantity' => 1,
            'max_quantity' => $product['quantity']
        ];
    }

    header('Location: cart.php');
    exit;
}
?>