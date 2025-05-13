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

// Получение 3 новинок для отображения
$newProductsStmt = $pdo->query("
    SELECT id, name, brand_name, color_name, type_name, view_name, gender, price, image_path
    FROM Product
    ORDER BY id DESC 
    LIMIT 3
");
$newProducts = $newProductsStmt->fetchAll(PDO::FETCH_ASSOC);

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
        /* Общие стили */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
        }

        header {
            background-color: #333;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        nav {
            display: flex;
            gap: 20px;
        }
        nav a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: opacity 0.3s;
        }
        nav a:hover {
            opacity: 0.8;
        }

        .auth-buttons button {
            margin-left: 10px;
        }

        .cart-button, .login-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }
        .cart-button:hover, .login-button:hover {
            background-color: #45a049;
        }

        /* Главный баннер */
        .hero-section {
            text-align: center;
            background: url('https://via.placeholder.com/1920x400') no-repeat center center/cover;
            color: #000000;
            padding: 120px 20px 100px;
        }
        .hero-section h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        .hero-section p {
            font-size: 18px;
        }

        /* Преимущества */
        .features-section {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            padding: 40px 20px;
            background-color: #fff;
        }
        .feature {
            flex: 1 1 250px;
            max-width: 300px;
            text-align: center;
            margin: 15px;
        }
        .feature i {
            font-size: 48px;
            color: #ff6f61;
            margin-bottom: 10px;
        }
        .feature h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .feature p {
            font-size: 14px;
            color: #666;
        }

        /* О нас */
        .about-section {
            padding: 40px 20px;
            background-color: #f9f9f9;
            text-align: center;
        }
        .about-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        .about-section p {
            max-width: 800px;
            margin: 0 auto;
            font-size: 16px;
            line-height: 1.6;
        }

        /* Новинки */
        .new-products-section {
            padding: 40px 20px;
            background-color: #fff;
            text-align: center;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .product-card-new {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            transition: transform 0.3s ease;
        }
        .product-card-new:hover {
            transform: scale(1.03);
        }
        .product-card-new img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .product-card-new h3 {
            font-size: 16px;
            margin: 10px 0;
        }
        .product-card-new .price {
            color: #4CAF50;
            font-weight: bold;
        }

        /* Контакты */
        .contact-section {
            padding: 40px 20px;
            background-color: #f4f4f4;
            text-align: center;
        }
        .contact-section h2 {
            font-size: 28px;
            margin-bottom: 20px;
        }
        .contact-section p {
            font-size: 16px;
            color: #333;
        }

        /* Подписка */
        .newsletter-section {
            text-align: center;
            padding: 40px 20px;
            background-color: #eef7f5;
            color: #333;
        }
        .newsletter-section h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .newsletter-section p {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .subscribe-form input {
            padding: 10px;
            font-size: 14px;
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-right: 10px;
        }
        .subscribe-form button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .subscribe-form button:hover {
            background-color: #45a049;
        }

        /* Футер */
        footer {
            background-color: #333;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }

        /* Модальное окно */
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
        <a href="#">Главная</a>
        <a href="catalog.php">Каталог</a>
        <a href="#">О нас</a>
        <a href="#">Контакты</a>
    </nav>
    <!-- Правая часть с кнопками входа -->
    <div class="auth-buttons">
        <?php if ($isAuthenticated): ?>
            <button class="cart-button" onclick="location.href='cart.php'">Корзина 🛒</button>
            <button class="logout-button" onclick="location.href='logout.php'">Выйти</button>
        <?php else: ?>
            <button id="loginButton" class="login-button">Войти</button>
        <?php endif; ?>
    </div>
</header>

<!-- Главный баннер -->
<section class="hero-section">
    <h1>Ищете идеальные часы?</h1>
    <p>У нас есть широкий выбор на любой вкус и бюджет!</p>
</section>

<!-- Преимущества -->
<section class="features-section">
    <div class="feature">
        <i>⏰</i>
        <h3>Гарантия качества</h3>
        <p>Все товары сертифицированы и имеют официальную гарантию.</p>
    </div>
    <div class="feature">
        <i>🚚</i>
        <h3>Быстрая доставка</h3>
        <p>Доставляем заказы по всей России за 1–3 дня.</p>
    </div>
    <div class="feature">
        <i>💳</i>
        <h3>Удобная оплата</h3>
        <p>Оплата наличными, картой или онлайн через сайт.</p>
    </div>
    <div class="feature">
        <i>📞</i>
        <h3>Поддержка 24/7</h3>
        <p>Наши консультанты всегда готовы помочь вам.</p>
    </div>
</section>

<!-- О нас -->
<section class="about-section">
    <h2>О нашем магазине</h2>
    <p>
        Мы специализируемся на продаже высококачественных часов от ведущих мировых производителей.
        В нашем ассортименте вы найдете как классические механические модели, так и современные умные часы.
        Каждый клиент получает индивидуальный подход и гарантию качества.
    </p>
</section>

<!-- Новинки -->
<section class="new-products-section">
    <h2>Новинки</h2>
    <div class="products-grid">
        <?php if (!empty($newProducts)): ?>
            <?php foreach ($newProducts as $product): ?>
                <div class="product-card-new">
                    <img src="uploads/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <p><strong>Цена:</strong> <?= number_format($product['price'], 2, '.', ' ') ?> ₽</p>
                    <form method="POST" action="add_to_cart.php" onsubmit="return checkLogin(this)">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <button type="submit" class="login-button"
                            <?php if (isProductInCart($pdo, $_SESSION['user_id'] ?? null, $product['id'])): ?>
                                disabled title="Товар уже в корзине"
                            <?php endif; ?>>
                            <?php if (isProductInCart($pdo, $_SESSION['user_id'] ?? null, $product['id'])): ?>
                                Добавлено
                            <?php else: ?>
                                Добавить в корзину
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center;">Пока нет новинок.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Контакты -->
<section class="contact-section">
    <h2>Свяжитесь с нами</h2>
    <p>
        По всем вопросам обращайтесь:<br>
        📞 Телефон: +7 (999) 123-45-67<br>
        📧 Email: info@watchstore.ru<br>
        📍 Адрес: г. Москва, ул. Часовая, д. 5
    </p>
</section>

<!-- Подписка -->
<section class="newsletter-section">
    <h2>Подпишитесь на наши новости</h2>
    <p>Получайте уведомления о новых коллекциях и скидках!</p>
    <form class="subscribe-form">
        <input type="email" placeholder="Введите ваш email" required>
        <button type="submit">Подписаться</button>
    </form>
</section>

<!-- Футер -->
<footer>
    &copy; 2025 Магазин Часов. Все права защищены.<br>
    <small>г. Москва, ул. Часовая, д. 5 | Телефон: +7 (999) 123-45-67</small>
</footer>

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
    // Открытие модального окна
    document.getElementById('loginButton')?.addEventListener('click', function () {
        document.getElementById('loginModal').style.display = 'flex';
    });

    // Закрытие модального окна
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