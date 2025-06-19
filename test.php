<?php
// Старт сессии должен быть в самом начале файла, до любого вывода
session_start();

// Подключение к базе данных
$host = 'mysql';
$dbname = 'appliance_store';
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
    header("Location: index.php");
    exit();
}

// Получение 7 самых часто покупаемых товаров
$popularProductsStmt = $pdo->query("
    SELECT p.id, p.name, p.brand_name, p.category_name, p.type_name, p.features, p.price, p.image_path 
    FROM Product p
    JOIN (
        SELECT product_name, SUM(quantity) as total_quantity
        FROM Orders
        GROUP BY product_name
    ) o ON p.name = o.product_name
    ORDER BY o.total_quantity DESC 
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

// Получение количества товаров в корзине
$cartCount = 0;
if ($isAuthenticated) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn() ?? 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechHome | Магазин бытовой техники</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --black: #111111;
            --white: #ffffff;
            --gray: #e0e0e0;
            --light-gray: #f5f5f5;
            --accent: #0066cc;
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
            color: var(--accent);
        }

        .logo span {
            font-weight: 600;
        }

        nav {
            display: flex;
            gap: 2rem;
            margin-right: 1200px;
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
            color: var(--accent);
        }

        nav a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 1px;
            background: var(--accent);
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
            color: var(--accent);
            transform: translateY(-2px);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--accent);
            color: var(--white);
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Герой-секция */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 10%;
            background-color: var(--light-gray);
            position: relative;
            overflow: hidden;
            margin-top: 80px;
        }

        .hero-content {
            max-width: 500px;
            z-index: 2;
        }

        .hero-title {
            font-size: 3rem;
            font-weight: 300;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-title span {
            font-weight: 400;
            border-bottom: 2px solid var(--accent);
        }

        .hero-text {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .hero-btn {
            background: var(--accent);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 1px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .hero-btn:hover {
            background: #0055aa;
            transform: translateY(-3px);
        }

        .hero-image {
            position: absolute;
            right: 10%;
            top: 50%;
            transform: translateY(-50%);
            width: 50%;
            max-width: 700px;
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.1));
        }

        a.back-link {
            display: block;
            text-align: left;
            margin-top: 1rem;
            color: var(--text-light);
            font-size: 0.9rem;
            text-decoration: none;
            transition: var(--transition);
        }

        a.back-link:hover {
            color: var(--accent);
        }

        /* Коллекция */
        .collection {
            padding: 6rem 10%;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 300;
        }

        .section-link {
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .section-link:hover {
            color: var(--accent);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid var(--gray);
            border-radius: 8px;
            padding: 15px;
        }

        .product-image {
            width: 100%;
            height: 250px;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transition: var(--transition);
            border-radius: 4px;
        }

        .product-image img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
            transition: var(--transition);
        }

        .product-card:hover .product-image {
            transform: translateY(-10px);
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            text-align: center;
        }

        .product-brand {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 400;
            margin-bottom: 0.5rem;
            min-height: 50px;
        }

        .product-features {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 1rem;
            text-align: left;
        }

        .product-price {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 1rem;
            color: var(--accent);
        }

        .add-to-cart {
            background: var(--accent);
            color: var(--white);
            border: none;
            padding: 0.8rem;
            width: 100%;
            font-size: 0.9rem;
            cursor: pointer;
            transition: var(--transition);
            border-radius: 4px;
        }

        .add-to-cart:hover {
            background: #0055aa;
        }

        .add-to-cart:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }

        /* Категории */
        .categories {
            padding: 4rem 10%;
            background-color: var(--light-gray);
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .category-card {
            background: var(--white);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .category-image {
            height: 150px;
            background-color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .category-image img {
            max-width: 80%;
            max-height: 80%;
            object-fit: contain;
        }

        .category-name {
            padding: 1rem;
            text-align: center;
            font-weight: 500;
        }

        /* Информационные секции */
        .info-section {
            padding: 5rem 10%;
            background-color: var(--white);
            text-align: center;
        }

        .info-section h2 {
            font-size: 1.8rem;
            font-weight: 300;
            margin-bottom: 2rem;
        }

        .info-section p {
            max-width: 800px;
            margin: 0 auto 1.5rem;
            color: var(--text-light);
        }

        /* Подвал */
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
            color: var(--white);
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
            color: var(--white);
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
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray);
            font-size: 0.8rem;
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
            border-color: var(--accent);
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
            background: var(--accent);
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

        /* Адаптивность */
        @media (max-width: 1024px) {
            .hero-image {
                opacity: 0.5;
                right: 5%;
            }

            .categories-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            nav {
                display: none;
            }

            .hero {
                flex-direction: column;
                justify-content: center;
                text-align: center;
                padding-top: 6rem;
            }

            .hero-content {
                max-width: 100%;
                margin-bottom: 3rem;
            }

            .hero-image {
                position: relative;
                right: auto;
                top: auto;
                transform: none;
                width: 100%;
                margin-top: 2rem;
                opacity: 1;
            }

            .categories-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<header>
    <a href="index.php" class="logo">Tech<span>Home</span></a>
    <nav>
        <a href="catalog.php">Каталог</a>
        <a href="#categories">Категории</a>
        <a href="#contacts">Контакты</a>
    </nav>
    <div class="header-actions">
        <?php if ($isAuthenticated): ?>
            <div style="position: relative;">
                <button class="icon-btn" onclick="location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i>
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

<section id="about" class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Современная техника <span>для вашего дома</span></h1>
        <p class="hero-text">
            TechHome предлагает широкий ассортимент бытовой техники от ведущих мировых производителей.
            Качество, надежность и инновационные технологии для вашего комфорта.
        </p>
        <button class="hero-btn" onclick="location.href='catalog.php'">
            <span>Полный ассортимент</span>
            <i class="fas fa-arrow-right"></i>
        </button>
    </div>
    <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1556740738-b6a63e27c4df?ixlib=rb-1.2.1&auto=format&fit=crop&w=1000&q=80" alt="Бытовая техника">
    </div>
</section>

<section class="collection">
    <div class="section-header">
        <h2 class="section-title">Популярные товары</h2>
        <a href="catalog.php" class="section-link">
            <span>Смотреть все</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="products-grid">
        <?php if (!empty($popularProducts)): ?>
            <?php foreach ($popularProducts as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="uploads/<?= htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-info">
                        <p class="product-brand"><?= htmlspecialchars($product['brand_name']) ?></p>
                        <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                        <p class="product-features"><?= htmlspecialchars($product['features']) ?></p>
                        <p class="product-price"><?= number_format($product['price'], 0, '.', ' ') ?> ₽</p>
                        <form method="POST" action="add_to_cart.php" onsubmit="return checkLogin(this)">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <button type="submit" class="add-to-cart"
                                <?= isProductInCart($pdo, $_SESSION['user_id'] ?? null, $product['id']) ? 'disabled' : '' ?>>
                                <?= isProductInCart($pdo, $_SESSION['user_id'] ?? null, $product['id']) ? 'В корзине' : 'Добавить в корзину' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="grid-column: 1 / -1; text-align: center;">Товары пока не добавлены</p>
        <?php endif; ?>
    </div>
</section>

<section id="categories" class="categories">
    <h2 class="section-title" style="text-align: center;">Категории</h2>
    <div class="categories-grid">
        <div class="category-card">
            <div class="category-image">
                <img src="https://cdn-icons-png.flaticon.com/512/3659/3659898.png" alt="Крупная техника">
            </div>
            <div class="category-name">Крупная техника</div>
        </div>
        <div class="category-card">
            <div class="category-image">
                <img src="https://cdn-icons-png.flaticon.com/512/3097/3097006.png" alt="Кухонная техника">
            </div>
            <div class="category-name">Кухонная техника</div>
        </div>
        <div class="category-card">
            <div class="category-image">
                <img src="https://cdn-icons-png.flaticon.com/512/2933/2933245.png" alt="Климатическая техника">
            </div>
            <div class="category-name">Климатическая техника</div>
        </div>
        <div class="category-card">
            <div class="category-image">
                <img src="https://cdn-icons-png.flaticon.com/512/3194/3194834.png" alt="Техника для дома">
            </div>
            <div class="category-name">Техника для дома</div>
        </div>
    </div>
</section>

<section class="info-section">
    <h2>Почему выбирают нас?</h2>
    <p>Мы предлагаем только качественную технику от проверенных производителей с официальной гарантией.</p>
    <p>Быстрая доставка по всей России и удобные способы оплаты.</p>
    <p>Профессиональные консультации и сервисное обслуживание.</p>
</section>

<footer>
    <div class="footer-grid">
        <div>
            <section id="contacts">
                <div class="footer-logo">Tech<span>Home</span></div>
                <p class="footer-text">
                    Магазин современной бытовой техники для вашего дома. Широкий ассортимент, гарантия качества и лучшие цены.
                </p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-vk"></i></a>
                </div>
            </section>
        </div>
        <div>
            <h3>Магазин</h3>
            <ul class="footer-links">
                <li><a href="catalog.php">Каталог</a></li>
                <li><a href="#categories">Категории</a></li>
                <li><a href="#about">О нас</a></li>
                <li><a href="#contacts">Контакты</a></li>
            </ul>
        </div>
        <div>
            <h3>Информация</h3>
            <ul class="footer-links">
                <li><a href="#">Доставка и оплата</a></li>
                <li><a href="#">Гарантия</a></li>
                <li><a href="#">Кредит</a></li>
                <li><a href="#">Сервисные центры</a></li>
            </ul>
        </div>
        <div>
            <h3>Контакты</h3>
            <ul class="footer-links">
                <li>Москва, ул. Техническая, д. 15</li>
                <li>+7 (800) 555-35-35</li>
                <li>info@techhome.ru</li>
                <li>Ежедневно с 9:00 до 21:00</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        © 2023 TechHome. Все права защищены.
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
            <input type="hidden" name="redirect" value="index.php">
            <div class="modal-body">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                    <a href="register.php" class="back-link">Зарегистрироваться</a>
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
</script>
</body>
</html>