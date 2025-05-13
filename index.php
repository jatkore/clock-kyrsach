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
$isAuthenticated = isset($_SESSION['user_id']);
// Получение 7 самых часто покупаемых товаров
$popularProductsStmt = $pdo->query("
    SELECT p.id, p.name, p.brand_name, p.color_name, p.type_name, p.view_name, p.gender, p.price, p.image_path 
    FROM Orders o 
    JOIN Product p ON o.product_id = p.id 
    GROUP BY p.id, p.name, p.brand_name, p.color_name, p.type_name, p.view_name, p.gender, p.price, p.image_path 
    ORDER BY SUM(o.quantity) DESC 
    LIMIT 7
");
$popularProducts = $popularProductsStmt->fetchAll(PDO::FETCH_ASSOC);
// Функция проверки наличия товара в корзине
function isProductInCart($pdo, $userId, $productId) {
    if (!$userId) return false;
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    return $stmt->fetchColumn() > 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Магазин Часов</title>
    <style>
        html {
            scroll-behavior: smooth; /* Плавная прокрутка */
        }
        /* Общие стили */
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
        nav {
            display: flex;
            gap: 15px;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .search-container {
            display: flex;
            align-items: center;
        }
        .search-input {
            padding: 5px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .login-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .login-button:hover {
            background-color: #45a049;
        }
        /* Стили для кнопки корзины */
        .cart-button {
            background-color: #ff6f61;
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .cart-button:hover {
            background-color: #e55039;
        }
        /* Стили для кнопки личного кабинета */
        .account-button {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .account-button:hover {
            background-color: #4caf50;
        }
        /* Секция главного баннера */
        .hero-section {
            text-align: center;
            background: url('https://via.placeholder.com/1920x400') no-repeat center center/cover;
            color: #000000;
            padding: 100px 20px;
        }
        .hero-section h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        .hero-section p {
            font-size: 18px;
        }
        /* Горизонтальная секция популярных товаров */
        .popular-products-section {
            padding: 20px;
            overflow-x: auto;
            white-space: nowrap;
        }
        .product-card {
            display: inline-block;
            width: 200px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-right: 10px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .product-card:last-child {
            margin-right: 0;
        }
        .product-card img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .product-card h3 {
            margin: 10px 0;
            font-size: 16px;
        }
        .product-card p {
            font-size: 14px;
            color: #666;
        }
        .product-card .price {
            font-size: 18px;
            color: #4CAF50;
            font-weight: bold;
        }
        .product-card:hover {
            transform: scale(1.05);
        }
        /* Информационные секции с ID для якорей */
        section[id] {
            scroll-margin-top: 80px; /* Отступ сверху при скролле */
        }
        /* Информационная секция */
        .info-section {
            padding: 20px;
            background-color: #f4f4f4;
            text-align: center;
        }
        .info-section h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        .info-section p {
            font-size: 16px;
            line-height: 1.6;
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
    <!-- Левая часть с меню -->
    <nav>
        <a href="catalog.php">Каталог</a>
        <a href="#about">О нас</a>
        <a href="#contacts">Контакты</a>
    </nav>
    <!-- Правая часть с кнопками корзины и входа -->
    <div>
        <?php if ($isAuthenticated): ?>
            <button class="cart-button" onclick="location.href='cart.php'">Корзина 🛒</button>
            <button class="account-button" onclick="location.href='lk.php'">Личный кабинет</button>
        <?php else: ?>
            <button id="loginButton">Войти</button>
        <?php endif; ?>
    </div>
</header>
<!-- Главный баннер -->
<section class="hero-section">
    <h1>Ищете идеальные часы?</h1>
    <p style="color: #000000;">У нас есть широкий выбор на любой вкус и бюджет!</p>
</section>
<!-- Секция популярных товаров -->
<section class="popular-products-section">
    <h2 style="margin-bottom: 10px; text-align: center;">Наши товары</h2>
    <div>
        <?php if (!empty($popularProducts)): ?>
            <?php foreach ($popularProducts as $product): ?>
                <div class="product-card">
                    <img src="uploads/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><strong>Бренд:</strong> <?= htmlspecialchars($product['brand_name']) ?></p>
                    <p><strong>Цена:</strong> <?= number_format($product['price'], 2, '.', ' ') ?> ₽</p>
                    <form method="POST" action="add_to_cart.php" onsubmit="return checkLogin(this)">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="login-button"
                            <?= isProductInCart($pdo, $_SESSION['user_id'] ?? null, $product['id']) ? 'disabled title="Товар уже в корзине"' : '' ?>>
                            <?= isProductInCart($pdo, $_SESSION['user_id'] ?? null, $product['id']) ? 'Добавлено' : 'Добавить в корзину' ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center;">Товары пока не добавлены.</p>
        <?php endif; ?>
    </div>
</section>
<!-- О нас -->
<section id="about" class="info-section">
    <h2>О нашем магазине</h2>
    <p>
        Мы специализируемся на продаже высококачественных часов от ведущих мировых производителей.
        В нашем ассортименте вы найдете как классические механические модели, так и современные умные часы.
        Каждый клиент получает индивидуальный подход и гарантию качества.
    </p>
</section>
<!-- Контакты -->
<section id="contacts" class="info-section">
    <h2>Свяжитесь с нами</h2>
    <p>
        По всем вопросам обращайтесь:<br>
        📞 Телефон: +7 (999) 123-45-67<br>
        📧 Email: info@watchstore.ru<br>
        📍 Адрес: г. Москва, ул. Часовая, д. 5
    </p>
</section>
<!-- Модальное окно для авторизации -->
<div id="loginModal" class="modal">
    <div class="modal-content">
        <h2>Авторизация</h2>
        <form method="POST" action="login.php?redirect=index.php" id="login-form">
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
    // Показать модальное окно
    document.getElementById('loginButton')?.addEventListener('click', function () {
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
    // После успешной авторизации перезагружаем страницу
    <?php if (isset($_SESSION['user_id'])): ?>
    sessionStorage.setItem('is_logged_in', 'true');
    <?php else: ?>
    sessionStorage.removeItem('is_logged_in');
    <?php endif; ?>
    function afterSuccessfulLogin() {
        sessionStorage.setItem('is_logged_in', 'true');
        closeLoginModal();
        location.reload();
    }
</script>
</body>
</html>