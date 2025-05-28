<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minimal Horizon | Утончённые часы</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --black: #111111;
            --white: #ffffff;
            --gray: #e0e0e0;
            --light-gray: #f5f5f5;
            --accent: #000000; /* Чёрный как акцент */
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

        .header-icons {
            display: flex;
            gap: 1.5rem;
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

        /* Герой-секция */
        .hero {
            height: 100vh;
            display: flex;
            align-items: center;
            padding: 0 10%;
            background-color: var(--light-gray);
            position: relative;
            overflow: hidden;
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
            border-bottom: 2px solid var(--black);
        }

        .hero-text {
            color: var(--text-light);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }

        .hero-btn {
            background: var(--black);
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
            background: #333333;
            transform: translateY(-3px);
        }

        .hero-image {
            position: absolute;
            right: 10%;
            top: 50%;
            transform: translateY(-50%);
            width: 40%;
            max-width: 600px;
            filter: drop-shadow(0 20px 30px rgba(0, 0, 0, 0.1));
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
            color: var(--black);
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
        }

        .product-image {
            width: 100%;
            height: 350px;
            background-color: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            transition: var(--transition);
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
        }

        .product-price {
            font-size: 1rem;
            font-weight: 500;
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

        /* Адаптивность */
        @media (max-width: 1024px) {
            .hero-image {
                opacity: 0.5;
                right: 5%;
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
        }
    </style>
</head>
<body>
<header>
    <div class="logo">MINIMAL <span>HORIZON</span></div>
    <nav>
        <a href="#">Каталог</a>
        <a href="#">Коллекции</a>
        <a href="#">О бренде</a>
        <a href="#">Контакты</a>
    </nav>
    <div class="header-icons">
        <button class="icon-btn"><i class="fas fa-search"></i></button>
        <button class="icon-btn"><i class="far fa-user"></i></button>
        <button class="icon-btn"><i class="fas fa-shopping-bag"></i></button>
    </div>
</header>

<section class="hero">
    <div class="hero-content">
        <h1 class="hero-title">Утончённость <span>в каждой детали</span></h1>
        <p class="hero-text">
            Часы Minimal Horizon — это сочетание безупречного дизайна и высокого качества.
            Каждая модель создана для тех, кто ценит элегантность и функциональность.
        </p>
        <button class="hero-btn">
            <span>Исследовать коллекцию</span>
            <i class="fas fa-arrow-right"></i>
        </button>
    </div>
    <div class="hero-image">
        <img src="https://via.placeholder.com/800x800" alt="Minimal Watch">
    </div>
</section>

<section class="collection">
    <div class="section-header">
        <h2 class="section-title">Новая коллекция</h2>
        <a href="#" class="section-link">
            <span>Смотреть все</span>
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="products-grid">
        <div class="product-card">
            <div class="product-image">
                <img src="https://via.placeholder.com/500x500" alt="Watch Model 1">
            </div>
            <div class="product-info">
                <p class="product-brand">MINIMAL HORIZON</p>
                <h3 class="product-name">Classic Black</h3>
                <p class="product-price">24 900 ₽</p>
            </div>
        </div>
        <div class="product-card">
            <div class="product-image">
                <img src="https://via.placeholder.com/500x500" alt="Watch Model 2">
            </div>
            <div class="product-info">
                <p class="product-brand">MINIMAL HORIZON</p>
                <h3 class="product-name">Modern Silver</h3>
                <p class="product-price">27 500 ₽</p>
            </div>
        </div>
        <div class="product-card">
            <div class="product-image">
                <img src="https://via.placeholder.com/500x500" alt="Watch Model 3">
            </div>
            <div class="product-info">
                <p class="product-brand">MINIMAL HORIZON</p>
                <h3 class="product-name">Slim White</h3>
                <p class="product-price">22 300 ₽</p>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="footer-grid">
        <div>
            <div class="footer-logo">MINIMAL <span>HORIZON</span></div>
            <p class="footer-text">
                Элегантные часы для современного образа жизни. Безупречное качество и дизайн.
            </p>
            <div class="social-links">
                <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="social-link"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
        <div>
            <h3>Магазин</h3>
            <ul class="footer-links">
                <li><a href="#">Каталог</a></li>
                <li><a href="#">Коллекции</a></li>
                <li><a href="#">Новинки</a></li>
                <li><a href="#">Распродажа</a></li>
            </ul>
        </div>
        <div>
            <h3>Информация</h3>
            <ul class="footer-links">
                <li><a href="#">О бренде</a></li>
                <li><a href="#">Доставка и оплата</a></li>
                <li><a href="#">Гарантия</a></li>
                <li><a href="#">Контакты</a></li>
            </ul>
        </div>
        <div>
            <h3>Контакты</h3>
            <ul class="footer-links">
                <li><a href="#">Москва, ул. Тверская, 18</a></li>
                <li><a href="#">+7 (495) 123-45-67</a></li>
                <li><a href="#">info@minimalhorizon.ru</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        © 2023 Minimal Horizon. Все права защищены.
    </div>
</footer>
</body>
</html>