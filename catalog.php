<?php
session_start();

// Подключение к базе данных
$host = 'mysql';
$dbname = 'watch_store';
$username = 'root';
$password = 'root';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Проверка авторизации
$isAuthenticated = isset($_SESSION['user_id']);

// Обработка выхода из системы
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: catalog.php");
    exit();
}

// Получение количества товаров в корзине
$cartCount = 0;
if ($isAuthenticated) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn() ?? 0;
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
    <title>Каталог | Watch Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --black: #111111;
            --white: #ffffff;
            --gray: #e0e0e0;
            --light-gray: #ffffff;
            --accent: #000000;
            --text-dark: #333333;
            --text-light: #777777;
            --transition: all 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
            --error: #e74c3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-dark);
            background-color: var(--white);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Шапка */
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--white);
            z-index: 1000;
            box-shadow: 0 1px 20px rgba(0, 0, 0, 0.03);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 300;
            letter-spacing: 2px;
            color: var(--black);
        }

        .logo span {
            font-weight: 600;
        }

        nav {
            display: flex;
            gap: 2rem;
            margin-right: 1320px;
        }

        nav a {
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 400;
            letter-spacing: 1px;
            transition: var(--transition);
            position: relative;
        }

        nav a:hover {
            color: var(--black);
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--black);
            transition: var(--transition);
        }

        nav a:hover::after {
            width: 100%;
        }

        .header-actions {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .icon-btn {
            background: none;
            border: none;
            color: var(--text-dark);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .icon-btn:hover {
            color: var(--black);
            transform: translateY(-2px);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--black);
            color: var(--white);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Основное содержимое */
        .main-content {
            padding-top: 80px;
            display: flex;
            min-height: calc(100vh - 80px);
        }

        /* Сайдбар с фильтрами */
        .sidebar {
            width: 280px;
            padding: 2rem;
            background-color: var(--light-gray);
            border-right: 1px solid var(--gray);
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
        }

        .filter-options {
            list-style: none;
        }

        .filter-option {
            margin-bottom: 0.5rem;
        }

        .filter-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .filter-option input[type="checkbox"] {
            margin-right: 0.5rem;
        }

        .price-range {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .price-range input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
        }

        .filter-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .filter-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .apply-button {
            background-color: var(--black);
            color: var(--white);
            flex: 1;
        }

        .apply-button:hover {
            background-color: #333;
        }

        .reset-button {
            background-color: var(--white);
            color: var(--text-dark);
            border: 1px solid var(--gray);
        }

        .reset-button:hover {
            background-color: var(--gray);
        }

        /* Контент с товарами */
        .content {
            flex: 1;
            padding: 2rem;
        }

        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .products-title {
            font-size: 1.5rem;
            font-weight: 300;
        }

        .products-count {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background-color: var(--white);
            border-radius: 4px;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 280px;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .product-brand {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .product-details {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .product-detail {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 1rem;
        }

        .add-to-cart {
            width: 100%;
            padding: 0.8rem;
            background-color: var(--black);
            color: var(--white);
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
        }

        .add-to-cart:hover {
            background-color: #333;
        }

        .add-to-cart:disabled {
            background-color: var(--gray);
            cursor: not-allowed;
        }

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
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
            z-index: 2000;
        }

        .modal-content {
            background-color: var(--white);
            width: 100%;
            max-width: 400px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--gray);
            position: relative;
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 500;
        }

        .modal-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-light);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--black);
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--gray);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .modal-btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-btn.primary {
            background: var(--black);
            color: var(--white);
        }

        .modal-btn.secondary {
            background: var(--white);
            color: var(--text-dark);
            border: 1px solid var(--gray);
        }

        .error-message {
            color: var(--error);
            font-size: 0.8rem;
            margin-top: 0.5rem;
            text-align: center;
        }

        /* Подвал */
        footer {
            background-color: var(--black);
            color: var(--white);
            padding: 3rem 5% 2rem;
        }

        footer {
            background-color: var(--black);
            color: var(--white);
            padding: 5rem 10% 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-logo {
            font-size: 1.2rem;
            font-weight: 300;
            letter-spacing: 2px;
            margin-bottom: 1rem;
        }

        .footer-logo span {
            font-weight: 600;
        }

        .footer-text {
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .social-links {
            display: flex;
            gap: 1rem;
        }

        .social-link {
            color: var(--gray);
            font-size: 1rem;
            transition: var(--transition);
        }

        .social-link:hover {
            color: var(--white);
        }

        .footer-column h3 {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            letter-spacing: 1px;
        }

        .footer-links {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 1rem;
        }

        .footer-links a {
            color: var(--gray);
            text-decoration: none;
            font-size: 0.9rem;
            transition: var(--transition);
        }

        .footer-links a:hover {
            color: var(--white);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray);
            font-size: 0.8rem;
        }

        /* Адаптивность */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
                padding: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--gray);
            }

            nav {
                display: none;
            }
        }
    </style>
</head>
<body>
<header>
    <a href="index.php" class="logo">Watch <span>Store</span></a>
    <nav>

        <a href="#contacts" class="nav">Контакты</a>
    </nav>
    <div class="header-actions">
        <?php if ($isAuthenticated): ?>
            <div style="position: relative;">
                <button class="icon-btn" onclick="location.href='cart.php'">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </button>
            </div>
            <button class="icon-btn" onclick="location.href='lk.php'"><i class="far fa-user"></i></button>
            <button class="icon-btn" onclick="location.href='?logout=1'"><i class="fas fa-sign-out-alt"></i></button>
        <?php else: ?>
            <button class="icon-btn" id="loginButton"><i class="far fa-user"></i></button>
        <?php endif; ?>
    </div>
</header>

<div class="main-content">
    <!-- Сайдбар с фильтрами -->
    <div class="sidebar">
        <form method="GET" action="" id="filter-form">
            <div class="filter-section">
                <h3 class="filter-title">Бренд</h3>
                <ul class="filter-options">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <li class="filter-option">
                                <label>
                                    <input
                                            type="checkbox"
                                            name="brand[]"
                                            value="<?= htmlspecialchars($category) ?>"
                                        <?= isset($_GET['brand']) && in_array($category, (array)$_GET['brand']) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($category) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Нет доступных брендов</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="filter-section">
                <h3 class="filter-title">Цвет</h3>
                <ul class="filter-options">
                    <?php if (!empty($colors)): ?>
                        <?php foreach ($colors as $color): ?>
                            <li class="filter-option">
                                <label>
                                    <input
                                            type="checkbox"
                                            name="color[]"
                                            value="<?= htmlspecialchars($color) ?>"
                                        <?= isset($_GET['color']) && in_array($color, (array)$_GET['color']) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($color) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Нет доступных цветов</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="filter-section">
                <h3 class="filter-title">Тип</h3>
                <ul class="filter-options">
                    <?php if (!empty($types)): ?>
                        <?php foreach ($types as $type): ?>
                            <li class="filter-option">
                                <label>
                                    <input
                                            type="checkbox"
                                            name="type[]"
                                            value="<?= htmlspecialchars($type) ?>"
                                        <?= isset($_GET['type']) && in_array($type, (array)$_GET['type']) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($type) ?>
                                </label>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>Нет доступных типов</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="filter-section">
                <h3 class="filter-title">Цена</h3>
                <div class="price-range">
                    <input type="number" name="min_price" placeholder="От"
                           value="<?= htmlspecialchars($_GET['min_price'] ?? $minPrice) ?>" min="0">
                    <input type="number" name="max_price" placeholder="До"
                           value="<?= htmlspecialchars($_GET['max_price'] ?? $maxPrice) ?>" min="0">
                </div>
            </div>

            <div class="filter-buttons">
                <button type="submit" name="apply" class="filter-button apply-button">Применить</button>
                <button type="button" onclick="clearFilters()" class="filter-button reset-button">Сбросить</button>
            </div>
        </form>
    </div>

    <!-- Основной контент с товарами -->
    <div class="content">
        <div class="products-header">
            <h1 class="products-title">Каталог часов</h1>
            <div class="products-count"><?= count($filteredProducts) ?> товаров</div>
        </div>

        <div class="products-grid">
            <?php if (empty($filteredProducts)): ?>
                <div class="no-products">
                    <p>Нет товаров, соответствующих выбранным фильтрам</p>
                    <button onclick="clearFilters()" class="filter-button apply-button">Сбросить фильтры</button>
                </div>
            <?php else: ?>
                <?php foreach ($filteredProducts as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="uploads/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="product-brand"><?= htmlspecialchars($product['brand_name']) ?></p>
                            <div class="product-details">
                                <span class="product-detail"><?= htmlspecialchars($product['color_name']) ?></span>
                                <span class="product-detail"><?= htmlspecialchars($product['type_name']) ?></span>
                                <span class="product-detail"><?= htmlspecialchars($product['gender']) ?></span>
                            </div>
                            <p class="product-price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</p>
                            <form method="POST" action="" onsubmit="return checkLogin(this)">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" name="add_to_cart" class="add-to-cart">
                                    Добавить в корзину
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    <div class="footer-grid">
        <div>
            <section id="contacts">
                <div class="footer-logo">Watch store <span></span></div>
                <p class="footer-text">
                    Элегантные часы для современного образа жизни. Безупречное качество и дизайн.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-pinterest"></i></a>
                </div>
            </section>
        </div>
        <div>
            <h3>Магазин</h3>
            <ul class="footer-links">
                <li><a href="catalog.php">Каталог</a></li>
                <li><a href="#about">О нас</a></li>
                <li><a href="#contacts">Контакты</a></li>
            </ul>
        </div>
        <div>
            <h3>Информация</h3>
            <ul  class="footer-links">
                <li><a href="#">Доставка и оплата</a></li>
                <li><a href="#garant">Гарантия</a></li>

            </ul>
        </div>
        <div>
            <h3>Контакты</h3>
            <ul class="footer-links">
                <li>Москва, ул. Часовая, д. 88</li>
                <li>+7 (999) 999-99-99</li>
                <li>info@watchstore.ru</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">

    </div>
</footer>

<!-- Модальное окно для авторизации -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Авторизация</h3>
            <button class="modal-close" onclick="closeLoginModal()">×</button>
        </div>
        <form method="POST" action="login.php" id="login-form">
            <input type="hidden" name="redirect" value="catalog.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <p class="error-message" id="error-message">
                    <?php
                    // Вывод ошибки авторизации, если она есть
                    if (isset($_SESSION['login_error'])) {
                        echo $_SESSION['login_error'];
                        unset($_SESSION['login_error']);
                    }
                    ?>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="modal-btn secondary" onclick="closeLoginModal()">Закрыть</button>
                <button type="submit" class="modal-btn primary">Войти</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Показать модальное окно
    document.getElementById('loginButton')?.addEventListener('click', function() {
        document.getElementById('loginModal').style.display = 'flex';
    });

    // Закрыть модальное окно
    function closeLoginModal() {
        document.getElementById('loginModal').style.display = 'none';
    }

    // Проверка авторизации перед добавлением товара в корзину
    function checkLogin(form) {
        <?php if (!$isAuthenticated): ?>
        alert('Для добавления товара в корзину необходимо авторизоваться.');
        document.getElementById('loginModal').style.display = 'flex';
        return false;
        <?php endif; ?>
        return true;
    }

    // Закрыть модальное окно при клике вне его
    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('loginModal')) {
            closeLoginModal();
        }
    });

    // Очистка фильтров
    function clearFilters() {
        document.getElementById('filter-form').reset();
        window.location.href = 'catalog.php';
    }

    // После успешной авторизации перезагружаем страницу
    <?php if (isset($_SESSION['user_id'])): ?>
    sessionStorage.setItem('is_logged_in', 'true');
    <?php else: ?>
    sessionStorage.removeItem('is_logged_in');
    <?php endif; ?>
</script>
</body>
</html>