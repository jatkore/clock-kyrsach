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

// Получение количества товаров в корзине
$cartCount = 0;
if ($isAuthenticated) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn() ?? 0;
}

// Обработка выхода из системы
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Спасибо за заказ | Minimal Horizon</title>
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

        /* Основной контент */
        .thank-you-section {
            padding: 10rem 10% 6rem;
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .thank-you-icon {
            font-size: 5rem;
            color: #4CAF50;
            margin-bottom: 2rem;
            animation: fadeIn 1s ease-in-out;
        }

        .thank-you-title {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 1.5rem;
        }

        .thank-you-text {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-bottom: 3rem;
            line-height: 1.8;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 3rem;
        }

        .btn {
            padding: 1rem 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 1px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--black);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background: #333333;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--white);
            color: var(--black);
            border: 1px solid var(--black);
        }

        .btn-secondary:hover {
            background: var(--light-gray);
            transform: translateY(-2px);
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
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--gray);
            font-size: 0.8rem;
        }

        /* Анимации */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .thank-you-section {
                padding: 8rem 5% 4rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="logo">MINIMAL <span>HORIZON</span></div>

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
            <button class="icon-btn" onclick="location.href='login.php'"><i class="far fa-user"></i></button>
        <?php endif; ?>
    </div>
</header>

<main class="thank-you-section">
    <div class="thank-you-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    <h1 class="thank-you-title">Спасибо за ваш заказ!</h1>
    <p class="thank-you-text">
        Ваш заказ успешно оформлен и передан в обработку. В ближайшее время с вами свяжется наш менеджер для подтверждения заказа.<br>
        Номер вашего заказа: #<?= rand(100000, 999999) ?>
    </p>

    <div class="action-buttons">
        <a href="catalog.php" class="btn btn-primary">Продолжить покупки</a>
        <a href="index.php" class="btn btn-secondary">На главную</a>
    </div>
</main>


</body>
</html>