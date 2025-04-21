<?php
session_start();

// Подключение к базе данных
$host = 'mysql';
$dbname = 'watch_store';
$username = 'root'; // Замените на ваше имя пользователя
$password = 'root';     // Замените на ваш пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение минимальной и максимальной цены
$priceRangeStmt = $pdo->query("SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM Product");
$priceRange = $priceRangeStmt->fetch(PDO::FETCH_ASSOC);
$minPrice = $priceRange['min_price'] ?? 0;
$maxPrice = $priceRange['max_price'] ?? 0;

// Получение уникальных значений для фильтров
$categories = $pdo->query("SELECT DISTINCT brand_name FROM Product")->fetchAll(PDO::FETCH_COLUMN);
$colors = $pdo->query("SELECT DISTINCT color_name FROM Product")->fetchAll(PDO::FETCH_COLUMN);
$types = $pdo->query("SELECT DISTINCT type_name FROM Product")->fetchAll(PDO::FETCH_COLUMN);
$views = $pdo->query("SELECT DISTINCT view_name FROM Product")->fetchAll(PDO::FETCH_COLUMN);
$genders = $pdo->query("SELECT DISTINCT gender FROM Product")->fetchAll(PDO::FETCH_COLUMN);

// Фильтрация товаров
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['apply'])) {
    $filters = [
        'brand' => $_GET['brand'] ?? [],
        'color' => $_GET['color'] ?? [],
        'type' => $_GET['type'] ?? [],
        'view' => $_GET['view'] ?? [],
        'gender' => $_GET['gender'] ?? [],
        'min_price' => (int)($_GET['min_price'] ?? $minPrice),
        'max_price' => (int)($_GET['max_price'] ?? $maxPrice),
    ];

    // Преобразуем параметры GET в массивы
    $filters['brand'] = is_array($filters['brand']) ? $filters['brand'] : ($filters['brand'] !== '' ? explode(',', $filters['brand']) : []);
    $filters['color'] = is_array($filters['color']) ? $filters['color'] : ($filters['color'] !== '' ? explode(',', $filters['color']) : []);
    $filters['type'] = is_array($filters['type']) ? $filters['type'] : ($filters['type'] !== '' ? explode(',', $filters['type']) : []);
    $filters['view'] = is_array($filters['view']) ? $filters['view'] : ($filters['view'] !== '' ? explode(',', $filters['view']) : []);
    $filters['gender'] = is_array($filters['gender']) ? $filters['gender'] : ($filters['gender'] !== '' ? explode(',', $filters['gender']) : []);

    // SQL-запрос с учетом фильтров
    $sql = "SELECT * FROM Product WHERE 1=1";

    if (!empty($filters['brand'])) {
        $placeholders = array_fill(0, count($filters['brand']), '?');
        $sql .= " AND brand_name IN (" . implode(', ', $placeholders) . ")";
    }

    if (!empty($filters['color'])) {
        $placeholders = array_fill(0, count($filters['color']), '?');
        $sql .= " AND color_name IN (" . implode(', ', $placeholders) . ")";
    }

    if (!empty($filters['type'])) {
        $placeholders = array_fill(0, count($filters['type']), '?');
        $sql .= " AND type_name IN (" . implode(', ', $placeholders) . ")";
    }

    if (!empty($filters['view'])) {
        $placeholders = array_fill(0, count($filters['view']), '?');
        $sql .= " AND view_name IN (" . implode(', ', $placeholders) . ")";
    }

    if (!empty($filters['gender'])) {
        $placeholders = array_fill(0, count($filters['gender']), '?');
        $sql .= " AND gender IN (" . implode(', ', $placeholders) . ")";
    }

    // Добавляем условие для цены
    $sql .= " AND price BETWEEN ? AND ?";

    // Подготовка запроса
    $stmt = $pdo->prepare($sql);

    // Массив для привязки параметров
    $bindValues = [];

    // Привязка значений для брендов
    if (!empty($filters['brand'])) {
        $bindValues = array_merge($bindValues, $filters['brand']);
    }

    // Привязка значений для цветов
    if (!empty($filters['color'])) {
        $bindValues = array_merge($bindValues, $filters['color']);
    }

    // Привязка значений для типов
    if (!empty($filters['type'])) {
        $bindValues = array_merge($bindValues, $filters['type']);
    }

    // Привязка значений для видов
    if (!empty($filters['view'])) {
        $bindValues = array_merge($bindValues, $filters['view']);
    }

    // Привязка значений для пола
    if (!empty($filters['gender'])) {
        $bindValues = array_merge($bindValues, $filters['gender']);
    }

    // Привязка значений для цены
    $bindValues[] = $filters['min_price'];
    $bindValues[] = $filters['max_price'];

    // Привязка параметров к запросу
    foreach ($bindValues as $index => $value) {
        $stmt->bindValue($index + 1, $value);
    }

    $stmt->execute();
    $filteredProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $filteredProducts = $pdo->query("SELECT * FROM Product")->fetchAll(PDO::FETCH_ASSOC);
}

// Добавление товара в корзину
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && isset($_SESSION['user_id'])) {
    $productId = (int)$_POST['product_id'];

    // Проверяем существование товара
    $productStmt = $pdo->prepare("SELECT * FROM Product WHERE id = ?");
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Товар не найден.");
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

    // Редирект обратно на каталог
    header("Location: catalog.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Каталог Часов</title>
    <style>
        /* Стили оставляем без изменений */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        header {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .sidebar {
            flex: 0 0 200px;
            margin-right: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .content {
            flex: 1;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .product-card {
            width: calc(33.33% - 20px);
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .product-card img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .product-card h3 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: #333;
        }

        .product-card p {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .product-card .price {
            font-size: 18px;
            color: #4CAF50;
            font-weight: bold;
        }

        button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        button.add-to-cart-button {
            background-color: #4CAF50;
            color: white;
        }

        button.add-to-cart-button:hover {
            background-color: #45a049;
        }

        /* Модальное окно для авторизации */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            width: 300px;
            text-align: center;
        }

        .modal-content h2 {
            margin-bottom: 15px;
            font-size: 20px;
        }

        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .modal-content input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .modal-content button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .modal-content button.login-button {
            background-color: #4CAF50;
            color: white;
        }

        .modal-content button.login-button:hover {
            background-color: #45a049;
        }

        .modal-content button.close-button {
            background-color: #ff6f61;
            color: white;
            margin-top: 10px;
        }

        .modal-content button.close-button:hover {
            background-color: #e55039;
        }

        .error-message {
            color: red;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
<header>
    <nav>
        <a href="#">Главная</a>
        <a href="#">Каталог</a>
        <a href="#">О нас</a>
        <a href="#">Контакты</a>
    </nav>
    <div>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <!-- Если пользователь не авторизован, показываем модальное окно -->
            <button class="cart-button" onclick="showLoginModal()">Корзина 🛒</button>
            <button class="login-button" onclick="showLoginModal()">Войти</button>
        <?php else: ?>
            <!-- Если пользователь авторизован, переходим на страницу корзины -->
            <button class="cart-button" onclick="location.href='cart.php'">Корзина 🛒</button>
            <button class="logout-button" onclick="location.href='logout.php'">Выйти</button>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    <!-- Сайдбар с категориями и фильтрами -->
    <div class="sidebar">
        <form method="GET" action="" id="filter-form">
            <h3>Бренд</h3>
            <ul>
                <?php if (!empty($categories)): ?>
                    <?php foreach ($categories as $category): ?>
                        <li>
                            <label>
                                <input
                                        type="checkbox"
                                        name="brand[]"
                                        value="<?= htmlspecialchars($category) ?>"
                                    <?= in_array($category, $filters['brand'] ?? []) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($category) ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет доступных брендов.</p>
                <?php endif; ?>
            </ul>

            <h3>Цвет</h3>
            <ul>
                <?php if (!empty($colors)): ?>
                    <?php foreach ($colors as $color): ?>
                        <li>
                            <label>
                                <input
                                        type="checkbox"
                                        name="color[]"
                                        value="<?= htmlspecialchars($color) ?>"
                                    <?= in_array($color, $filters['color'] ?? []) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($color) ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет доступных цветов.</p>
                <?php endif; ?>
            </ul>

            <h3>Тип</h3>
            <ul>
                <?php if (!empty($types)): ?>
                    <?php foreach ($types as $type): ?>
                        <li>
                            <label>
                                <input
                                        type="checkbox"
                                        name="type[]"
                                        value="<?= htmlspecialchars($type) ?>"
                                    <?= in_array($type, $filters['type'] ?? []) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет доступных типов.</p>
                <?php endif; ?>
            </ul>

            <h3>Вид</h3>
            <ul>
                <?php if (!empty($views)): ?>
                    <?php foreach ($views as $view): ?>
                        <li>
                            <label>
                                <input
                                        type="checkbox"
                                        name="view[]"
                                        value="<?= htmlspecialchars($view) ?>"
                                    <?= in_array($view, $filters['view'] ?? []) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($view) ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет доступных видов.</p>
                <?php endif; ?>
            </ul>

            <h3>Пол</h3>
            <ul>
                <?php if (!empty($genders)): ?>
                    <?php foreach ($genders as $gender): ?>
                        <li>
                            <label>
                                <input
                                        type="checkbox"
                                        name="gender[]"
                                        value="<?= htmlspecialchars($gender) ?>"
                                    <?= in_array($gender, $filters['gender'] ?? []) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($gender) ?>
                            </label>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Нет доступных полов.</p>
                <?php endif; ?>
            </ul>

            <h3>Цена</h3>
            <label>От: <input type="number" id="price-min" name="min_price" value="<?= htmlspecialchars($_GET['min_price'] ?? $minPrice) ?>"></label><br>
            <label>До: <input type="number" id="price-max" name="max_price" value="<?= htmlspecialchars($_GET['max_price'] ?? $maxPrice) ?>"></label><br>

            <!-- Кнопка "Применить" -->
            <button type="submit" name="apply" class="apply-button">Применить</button>

            <!-- Кнопка "Очистить фильтры" -->
            <button type="button" onclick="clearFilters()" class="reset-button">Очистить фильтры</button>
        </form>
    </div>

    <!-- Основной контент с товарами -->
    <div class="content" id="product-list">
        <?php if (empty($filteredProducts)): ?>
            <p>Нет товаров, соответствующих выбранным фильтрам.</p>
        <?php else: ?>
            <?php foreach ($filteredProducts as $product): ?>
                <div class="product-card">
                    <img src="uploads/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p>Бренд: <?= htmlspecialchars($product['brand_name']) ?></p>
                    <p>Цвет: <?= htmlspecialchars($product['color_name']) ?></p>
                    <p>Тип: <?= htmlspecialchars($product['type_name']) ?></p>
                    <p>Вид: <?= htmlspecialchars($product['view_name']) ?></p>
                    <p>Пол: <?= htmlspecialchars($product['gender']) ?></p>
                    <p class="price"><?= htmlspecialchars($product['price']) ?> ₽</p>

                    <!-- Кнопка "Добавить в корзину" -->
                    <form method="POST" action="" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" name="add_to_cart" class="add-to-cart-button">Добавить в корзину</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Модальное окно для авторизации -->
<div id="login-modal" class="modal">
    <div class="modal-content">
        <h2>Авторизация</h2>
        <form method="POST" action="login.php" id="login-form">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="login-button">Войти</button>
            <p class="error-message" id="error-message"></p>
        </form>

        <button type="button" onclick="closeLoginModal()" class="close-button">Закрыть</button>
    </div>
</div>

<script>
    // Функция для показа модального окна авторизации
    function showLoginModal() {
        document.getElementById('login-modal').style.display = 'flex';
    }

    // Функция для закрытия модального окна авторизации
    function closeLoginModal() {
        document.getElementById('login-modal').style.display = 'none';
    }

    // Функция для очистки фильтров
    function clearFilters() {
        document.getElementById('filter-form').reset(); // Сброс формы
        window.location.href = '?'; // Перезагрузка страницы без параметров
    }

    // Проверка авторизации перед добавлением товара в корзину
    document.addEventListener('DOMContentLoaded', () => {
        const addToCartForms = document.querySelectorAll('.add-to-cart-form');

        addToCartForms.forEach(form => {
            form.addEventListener('submit', function (event) {
                // Если пользователь не авторизован, показываем модальное окно
                if (!sessionStorage.getItem('is_logged_in')) {
                    event.preventDefault(); // Предотвращаем отправку формы
                    showLoginModal(); // Показываем модальное окно
                }
            });
        });

        // Обработка перехода в корзину
        const cartButton = document.querySelector('.cart-button');
        if (cartButton) {
            cartButton.addEventListener('click', function (event) {
                // Если пользователь не авторизован, показываем модальное окно
                if (!sessionStorage.getItem('is_logged_in')) {
                    event.preventDefault(); // Предотвращаем переход
                    showLoginModal(); // Показываем модальное окно
                }
            });
        }
    });

    // После успешной авторизации разрешаем действия
    <?php if (isset($_SESSION['user_id'])): ?>
    sessionStorage.setItem('is_logged_in', 'true'); // Устанавливаем флаг авторизации
    <?php else: ?>
    sessionStorage.removeItem('is_logged_in'); // Удаляем флаг авторизации
    <?php endif; ?>

    // Функция для обновления состояния после входа
    function afterSuccessfulLogin() {
        sessionStorage.setItem('is_logged_in', 'true'); // Устанавливаем флаг авторизации
        closeLoginModal(); // Закрываем модальное окно
        location.reload(); // Перезагружаем страницу, чтобы обновить состояние
    }
</script>
</body>
</html>
