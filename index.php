<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Магазин Часов</title>
    <style>
        /* Общие стили */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #333;
            color: white;
            padding: 10px 20px;
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
            margin-right: 10px; /* Отступ справа от кнопки корзины до кнопки "Войти" */
        }

        .cart-button:hover {
            background-color: #e55039;
        }

        /* Секция главного баннера */
        .hero-section {
            text-align: center;
            background: url('https://via.placeholder.com/1920x400') no-repeat center center/cover;
            color: white;
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
            overflow-x: auto; /* Добавляем горизонтальную прокрутку */
            white-space: nowrap; /* Отключаем перенос строк */
        }

        .product-card {
            display: inline-block; /* Карточки становятся в одну строку */
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
            margin-right: 0; /* Убираем отступ у последней карточки */
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

        .product-card:hover {
            transform: scale(1.05);
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

        /* Адаптивность */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
            }

            nav {
                flex-direction: column;
                gap: 10px;
            }

            .search-container {
                margin-top: 10px;
                width: 100%;
            }

            .search-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header>
        <!-- Левая часть с меню -->
        <nav>
            <a href="#">Каталог</a>
            <a href="#">О нас</a>
            <a href="#">Политика магазина</a>
            <a href="#">Тех. поддержка</a>
        </nav>

        <!-- Центральная часть с полем поиска -->
        <div class="search-container">
            <input type="text" class="search-input" placeholder="Поиск...">
        </div>

        <!-- Правая часть с кнопками корзины и входа -->
        <div>
            <button class="cart-button">Корзина 🛒</button>
            <button class="login-button">Войти</button>
        </div>
    </header>

    <!-- Главный баннер -->
    <section class="hero-section">
        <h1>Ищете идеальные часы?</h1>
        <p>У нас есть широкий выбор на любой вкус и бюджет!</p>
    </section>

    <!-- Секция популярных товаров -->
    <section class="popular-products-section">
        <h2 style="margin-bottom: 10px; text-align: center;">Популярные товары</h2>
        <div>
            <div class="product-card">
                <img src="https://via.placeholder.com/150x150" alt="Часы 1">
                <h3>Модель A123</h3>
                <p>Элегантные механические часы с кожаным ремешком.</p>
            </div>
            <div class="product-card">
                <img src="https://via.placeholder.com/150x150" alt="Часы 2">
                <h3>Модель B456</h3>
                <p>Спортивные кварцевые часы с функцией хронографа.</p>
            </div>
            <div class="product-card">
                <img src="https://via.placeholder.com/150x150" alt="Часы 3">
                <h3>Модель C789</h3>
                <p>Умные часы с GPS и мониторингом сердечного ритма.</p>
            </div>
            <div class="product-card">
                <img src="https://via.placeholder.com/150x150" alt="Часы 4">
                <h3>Модель D101</h3>
                <p>Классические женские часы с бриллиантовой отделкой.</p>
            </div>
        </div>
    </section>

    <!-- Информационная секция -->
    <section class="info-section">
        <h2>О нашем магазине</h2>
        <p>
            Мы специализируемся на продаже высококачественных часов от ведущих мировых производителей.
            В нашем ассортименте вы найдете как классические механические модели, так и современные умные часы.
            Каждый клиент получает индивидуальный подход и гарантию качества.
        </p>
    </section>
</body>
</html>
