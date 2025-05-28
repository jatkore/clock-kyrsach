<?php
session_start();
// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// Проверяем, является ли пользователь администратором по email
if ($_SESSION['email'] !== 'kea@vt2b.ru') {
    header("Location: lk.php");
    exit;
}

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

$isAuthenticated = isset($_SESSION['user_id']);

// Обработка выхода из системы
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Инициализация переменных для сообщений
$successProduct = '';
$errorProduct = '';
$successBrand = '';
$errorBrand = '';
$successColor = '';
$errorColor = '';
$successType = '';
$errorType = '';
$successView = '';
$errorView = '';

// Получение списков брендов, цветов, типов и видов
$brands = $pdo->query("SELECT * FROM Brand")->fetchAll(PDO::FETCH_ASSOC);
$colors = $pdo->query("SELECT * FROM Color")->fetchAll(PDO::FETCH_ASSOC);
$types = $pdo->query("SELECT * FROM Type")->fetchAll(PDO::FETCH_ASSOC);
$views = $pdo->query("SELECT * FROM View")->fetchAll(PDO::FETCH_ASSOC);

// Статистика продаж
$totalRevenueStmt = $pdo->query("SELECT SUM(total_price) AS total_revenue FROM Orders");
$totalRevenue = $totalRevenueStmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

$orderCountStmt = $pdo->query("SELECT COUNT(*) AS order_count FROM Orders");
$orderCount = $orderCountStmt->fetch(PDO::FETCH_ASSOC)['order_count'] ?? 0;

$topProductsStmt = $pdo->query("
    SELECT 
        product_name AS name, 
        SUM(quantity) AS total_quantity
    FROM Orders
    GROUP BY product_name
    ORDER BY total_quantity DESC
    LIMIT 3
");
$topProducts = $topProductsStmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка формы добавления товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $productName = $_POST['name'] ?? '';
    $brandName = $_POST['brand'] ?? '';
    $colorName = $_POST['color'] ?? '';
    $typeName = $_POST['type'] ?? '';
    $viewName = $_POST['view'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $imagePath = '';

    // Обработка загрузки изображения
    $imagePath = '';
    if (isset($_FILES['image'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Проверки файла
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $fileType = $_FILES['image']['type'];
        $fileSize = $_FILES['image']['size'];
        $fileError = $_FILES['image']['error'];

        if ($fileError === UPLOAD_ERR_OK) {
            if (!in_array($fileType, $allowedTypes)) {
                $errorProduct = "Разрешены только файлы изображений (JPEG, PNG, GIF, WEBP).";
            } elseif ($fileSize > $maxFileSize) {
                $errorProduct = "Файл слишком большой. Максимальный размер: 5MB.";
            } else {
                // Генерируем уникальное имя файла
                $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('img_', true) . '.' . $fileExt;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                    $imagePath = $fileName;
                } else {
                    $errorProduct = "Ошибка при загрузке изображения на сервер.";
                }
            }
        } elseif ($fileError === UPLOAD_ERR_NO_FILE) {
            $errorProduct = "Пожалуйста, выберите изображение товара.";
        } else {
            $errorProduct = "Ошибка при загрузке файла. Код ошибки: " . $fileError;
        }
    }

    // Проверка остальных полей
    if (empty($errorProduct)) {
        if (empty($productName) || empty($brandName) || empty($colorName) ||
            empty($typeName) || empty($viewName) || empty($gender) ||
            empty($price) || empty($quantity) || empty($imagePath)) {
            $errorProduct = "Пожалуйста, заполните все поля.";
        } elseif (!is_numeric($price) || $price <= 0) {
            $errorProduct = "Цена должна быть положительным числом.";
        } elseif (!is_numeric($quantity) || $quantity <= 0) {
            $errorProduct = "Количество должно быть положительным числом.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO Product (name, brand_name, color_name, type_name, view_name, gender, price, quantity, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$productName, $brandName, $colorName, $typeName, $viewName, $gender, $price, $quantity, $imagePath]);
                $successProduct = "Товар успешно добавлен!";

                // Очищаем поля формы после успешного добавления
                $_POST = array();
            } catch (PDOException $e) {
                $errorProduct = "Ошибка при добавлении товара: " . $e->getMessage();
            }
        }
    }
}

// Обработка формы добавления бренда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $brandName = trim($_POST['brand_name'] ?? '');
    if (empty($brandName)) {
        $errorBrand = "Введите название бренда.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Brand (name) VALUES (?)");
            $stmt->execute([$brandName]);
            $successBrand = "Бренд успешно добавлен!";
            $_POST['brand_name'] = '';
        } catch (PDOException $e) {
            $errorBrand = "Ошибка при добавлении бренда: " . $e->getMessage();
        }
    }
}

// Обработка формы добавления цвета
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_color'])) {
    $colorName = trim($_POST['color_name'] ?? '');
    if (empty($colorName)) {
        $errorColor = "Введите название цвета.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Color (name) VALUES (?)");
            $stmt->execute([$colorName]);
            $successColor = "Цвет успешно добавлен!";
            $_POST['color_name'] = '';
        } catch (PDOException $e) {
            $errorColor = "Ошибка при добавлении цвета: " . $e->getMessage();
        }
    }
}

// Обработка формы добавления типа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_type'])) {
    $typeName = trim($_POST['type_name'] ?? '');
    if (empty($typeName)) {
        $errorType = "Введите название типа.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO Type (name) VALUES (?)");
            $stmt->execute([$typeName]);
            $successType = "Тип успешно добавлен!";
            $_POST['type_name'] = '';
        } catch (PDOException $e) {
            $errorType = "Ошибка при добавлении типа: " . $e->getMessage();
        }
    }
}

// Обработка формы добавления вида
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_view'])) {
    $viewName = trim($_POST['view_name'] ?? '');
    if (empty($viewName)) {
        $errorView = "Введите название вида.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO View (name) VALUES (?)");
            $stmt->execute([$viewName]);
            $successView = "Вид успешно добавлен!";
            $_POST['view_name'] = '';
        } catch (PDOException $e) {
            $errorView = "Ошибка при добавлении вида: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный Кабинет Администратора | Watch Store</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --black: #111111;
            --white: #ffffff;
            --gray: #e0e0e0;
            --light-gray: #f5f5f5;
            --accent: #000000;
            --text-dark: #333333;
            --text-light: #777777;
            --transition: all 0.3s cubic-bezier(0.25, 0.1, 0.25, 1);
            --error: #e74c3c;
            --success: #2ecc71;
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
            margin-right: 1420px;
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

        .logout-button {
            background: var(--black);
            color: var(--white);
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-button:hover {
            background: #333333;
        }

        /* Основной контейнер */
        .admin-container {
            display: flex;
            min-height: calc(100vh - 80px);
            margin-top: 80px;
        }

        /* Сайдбар */
        .sidebar {
            width: 250px;
            background-color: var(--light-gray);
            padding: 2rem 1rem;
            border-right: 1px solid var(--gray);
        }

        .sidebar h3 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            padding-left: 0.5rem;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            margin-bottom: 0.5rem;
        }

        .sidebar a {
            display: block;
            padding: 0.8rem 0.5rem;
            color: var(--text-dark);
            text-decoration: none;
            font-size: 0.9rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .sidebar a:hover, .sidebar a.active {
            background-color: var(--gray);
            color: var(--black);
        }

        /* Основное содержимое */
        .content {
            flex: 1;
            padding: 2rem 3rem;
        }

        .section {
            display: none;
            margin-bottom: 3rem;
        }

        .section.active {
            display: block;
        }

        .section h2 {
            font-size: 1.8rem;
            font-weight: 300;
            margin-bottom: 2rem;
        }

        /* Формы */
        .admin-form {
            max-width: 600px;
            margin: 0 auto;
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

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--black);
        }

        .form-group .file-input {
            padding: 0.5rem;
        }

        .submit-btn {
            background: var(--black);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .submit-btn:hover {
            background: #333333;
        }

        /* Сообщения */
        .message {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
        }

        .message.success {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .message.error {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--error);
            border: 1px solid var(--error);
        }

        /* Статистика */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--light-gray);
            padding: 1.5rem;
            border-radius: 4px;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-light);
        }

        .stat-card p {
            font-size: 1.5rem;
            font-weight: 300;
        }

        .top-products {
            margin-top: 2rem;
        }

        .top-products ul {
            list-style: none;
        }

        .top-products li {
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--gray);
            display: flex;
            justify-content: space-between;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--gray);
            }

            .content {
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Предпросмотр изображения */
        .image-preview {
            margin-top: 1rem;
            display: none;
        }

        .image-preview img {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid var(--gray);
            border-radius: 4px;
        }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">Watch <span>Store</span></a>
    <nav>
        <a href="catalog.php" class="nav">Каталог</a>
    </nav>
    <div class="header-actions">
        <?php if ($isAuthenticated): ?>
            <button class="icon-btn" onclick="location.href='?logout=1'"><i class="fas fa-sign-out-alt"></i></button>
        <?php endif; ?>
    </div>
</header>

<div class="admin-container">
    <!-- Сайдбар с навигацией -->
    <div class="sidebar">
        <h3>Меню администратора</h3>
        <ul>
            <li><a href="#sales" class="active">Статистика продаж</a></li>
            <li><a href="#add-product">Добавить товар</a></li>
            <li><a href="#add-brand">Добавить бренд</a></li>
            <li><a href="#add-color">Добавить цвет</a></li>
            <li><a href="#add-type">Добавить тип</a></li>
            <li><a href="#add-view">Добавить вид</a></li>
        </ul>
    </div>

    <!-- Основной контент -->
    <div class="content">
        <!-- Раздел "Статистика продаж" -->
        <section id="sales" class="section active">
            <h2>Статистика продаж</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Общая выручка</h3>
                    <p><?= number_format($totalRevenue, 0, '.', ' ') ?> ₽</p>
                </div>
                <div class="stat-card">
                    <h3>Количество заказов</h3>
                    <p><?= htmlspecialchars($orderCount) ?></p>
                </div>
            </div>

            <div class="top-products">
                <h3>Топ покупаемых товаров</h3>
                <ul>
                    <?php foreach ($topProducts as $product): ?>
                        <li>
                            <span><?= htmlspecialchars($product['name']) ?></span>
                            <span><?= htmlspecialchars($product['total_quantity']) ?> шт.</span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <!-- Раздел добавления товара -->
        <section id="add-product" class="section">
            <h2>Добавить товар</h2>
            <?php if (!empty($successProduct)): ?>
                <div class="message success"><?= htmlspecialchars($successProduct) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorProduct)): ?>
                <div class="message error"><?= htmlspecialchars($errorProduct) ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="admin-form" enctype="multipart/form-data">
                <input type="hidden" name="add_product">
                <div class="form-group">
                    <label for="product-name">Название товара:</label>
                    <input type="text" id="product-name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="product-brand">Бренд:</label>
                    <select id="product-brand" name="brand" required>
                        <option value="" disabled selected>Выберите бренд</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= htmlspecialchars($brand['name']) ?>" <?= isset($_POST['brand']) && $_POST['brand'] === $brand['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($brand['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="product-color">Цвет:</label>
                    <select id="product-color" name="color" required>
                        <option value="" disabled selected>Выберите цвет</option>
                        <?php foreach ($colors as $color): ?>
                            <option value="<?= htmlspecialchars($color['name']) ?>" <?= isset($_POST['color']) && $_POST['color'] === $color['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($color['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="product-type">Тип часов:</label>
                    <select id="product-type" name="type" required>
                        <option value="" disabled selected>Выберите тип</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= htmlspecialchars($type['name']) ?>" <?= isset($_POST['type']) && $_POST['type'] === $type['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="product-view">Вид:</label>
                    <select id="product-view" name="view" required>
                        <option value="" disabled selected>Выберите вид</option>
                        <?php foreach ($views as $view): ?>
                            <option value="<?= htmlspecialchars($view['name']) ?>" <?= isset($_POST['view']) && $_POST['view'] === $view['name'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($view['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="product-gender">Пол:</label>
                    <select id="product-gender" name="gender" required>
                        <option value="" disabled selected>Выберите пол</option>
                        <option value="Мужской" <?= isset($_POST['gender']) && $_POST['gender'] === 'Мужской' ? 'selected' : '' ?>>Мужской</option>
                        <option value="Женский" <?= isset($_POST['gender']) && $_POST['gender'] === 'Женский' ? 'selected' : '' ?>>Женский</option>
                        <option value="Унисекс" <?= isset($_POST['gender']) && $_POST['gender'] === 'Унисекс' ? 'selected' : '' ?>>Унисекс</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="product-price">Цена (₽):</label>
                    <input type="number" id="product-price" name="price" step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="product-quantity">Количество на складе:</label>
                    <input type="number" id="product-quantity" name="quantity" min="1" value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="product-image">Изображение товара:</label>
                    <input type="file" id="product-image" name="image" accept="image/*" required class="file-input">
                    <div class="image-preview" id="image-preview">
                        <img id="preview-image" src="#" alt="Предпросмотр изображения">
                    </div>
                </div>

                <button type="submit" class="submit-btn">Добавить товар</button>
            </form>
        </section>

        <!-- Раздел добавления бренда -->
        <section id="add-brand" class="section">
            <h2>Добавить бренд</h2>
            <?php if (!empty($successBrand)): ?>
                <div class="message success"><?= htmlspecialchars($successBrand) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorBrand)): ?>
                <div class="message error"><?= htmlspecialchars($errorBrand) ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="admin-form">
                <input type="hidden" name="add_brand">
                <div class="form-group">
                    <label for="brand-name">Название бренда:</label>
                    <input type="text" id="brand-name" name="brand_name" value="<?= htmlspecialchars($_POST['brand_name'] ?? '') ?>" required>
                </div>
                <button type="submit" class="submit-btn">Добавить бренд</button>
            </form>
        </section>

        <!-- Раздел добавления цвета -->
        <section id="add-color" class="section">
            <h2>Добавить цвет</h2>
            <?php if (!empty($successColor)): ?>
                <div class="message success"><?= htmlspecialchars($successColor) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorColor)): ?>
                <div class="message error"><?= htmlspecialchars($errorColor) ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="admin-form">
                <input type="hidden" name="add_color">
                <div class="form-group">
                    <label for="color-name">Название цвета:</label>
                    <input type="text" id="color-name" name="color_name" value="<?= htmlspecialchars($_POST['color_name'] ?? '') ?>" required>
                </div>
                <button type="submit" class="submit-btn">Добавить цвет</button>
            </form>
        </section>

        <!-- Раздел добавления типа -->
        <section id="add-type" class="section">
            <h2>Добавить тип</h2>
            <?php if (!empty($successType)): ?>
                <div class="message success"><?= htmlspecialchars($successType) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorType)): ?>
                <div class="message error"><?= htmlspecialchars($errorType) ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="admin-form">
                <input type="hidden" name="add_type">
                <div class="form-group">
                    <label for="type-name">Название типа:</label>
                    <input type="text" id="type-name" name="type_name" value="<?= htmlspecialchars($_POST['type_name'] ?? '') ?>" required>
                </div>
                <button type="submit" class="submit-btn">Добавить тип</button>
            </form>
        </section>

        <!-- Раздел добавления вида -->
        <section id="add-view" class="section">
            <h2>Добавить вид</h2>
            <?php if (!empty($successView)): ?>
                <div class="message success"><?= htmlspecialchars($successView) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorView)): ?>
                <div class="message error"><?= htmlspecialchars($errorView) ?></div>
            <?php endif; ?>
            <form method="POST" action="" class="admin-form">
                <input type="hidden" name="add_view">
                <div class="form-group">
                    <label for="view-name">Название вида:</label>
                    <input type="text" id="view-name" name="view_name" value="<?= htmlspecialchars($_POST['view_name'] ?? '') ?>" required>
                </div>
                <button type="submit" class="submit-btn">Добавить вид</button>
            </form>
        </section>
    </div>
</div>

<script>
    // Переключение между разделами
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    const sections = document.querySelectorAll('.section');

    sidebarLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Удалить активный класс у всех ссылок
            sidebarLinks.forEach(l => l.classList.remove('active'));
            // Добавить активный класс к выбранной ссылке
            this.classList.add('active');

            // Скрыть все разделы
            sections.forEach(section => section.classList.remove('active'));

            // Показать выбранный раздел
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Предпросмотр изображения перед загрузкой
    document.getElementById('product-image').addEventListener('change', function(event) {
        const preview = document.getElementById('preview-image');
        const previewContainer = document.getElementById('image-preview');

        if (this.files && this.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.style.display = 'block';
            }

            reader.readAsDataURL(this.files[0]);
        } else {
            preview.src = '#';
            previewContainer.style.display = 'none';
        }
    });
</script>

</body>
</html>