<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Подключение к БД
$host = 'mysql';
$dbname = 'watch_store';
$username = 'root';
$password = 'root';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}

// Получение товаров из корзины
$stmt = $pdo->prepare("SELECT p.*, c.quantity AS cart_quantity, c.id AS cart_id FROM cart c JOIN Product p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение количества товаров в корзине для отображения в шапке
$cartCount = 0;
$stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$cartCount = $stmt->fetchColumn() ?? 0;

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
    <title>Корзина | Minimal Horizon</title>
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
        .main-content {
            padding: 8rem 10% 4rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 3rem;
            text-align: center;
        }

        /* Стили для корзины */
        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 2rem 0;
            border-bottom: 1px solid var(--gray);
            gap: 2rem;
        }

        .select-checkbox {
            margin-right: 1rem;
        }

        .cart-item img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-right: 2rem;
        }

        .details {
            flex-grow: 1;
        }

        .details h3 {
            font-size: 1.2rem;
            font-weight: 400;
            margin-bottom: 0.5rem;
        }

        .details p {
            color: var(--text-light);
            margin-bottom: 1rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            margin-top: 1rem;
        }

        .quantity-control button {
            background: var(--black);
            color: var(--white);
            border: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .quantity-control button:hover {
            background: #333333;
        }

        .quantity-control button:disabled {
            background: var(--gray);
            cursor: not-allowed;
        }

        .quantity-value {
            width: 50px;
            text-align: center;
            margin: 0 0.5rem;
            padding: 0.5rem;
            border: 1px solid var(--gray);
        }

        .delete-btn {
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .delete-btn:hover {
            color: var(--error);
        }

        .checkout-section {
            margin-top: 3rem;
            display: flex;
            justify-content: flex-end;
        }

        .checkout-btn {
            background: var(--black);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 1px;
            cursor: pointer;
            transition: var(--transition);
        }

        .checkout-btn:hover {
            background: #333333;
            transform: translateY(-2px);
        }

        .checkout-btn:disabled {
            background: var(--gray);
            cursor: not-allowed;
            transform: none;
        }

        .empty-cart {
            text-align: center;
            padding: 5rem 0;
        }

        .empty-cart p {
            font-size: 1.2rem;
            color: var(--text-light);
            margin-bottom: 2rem;
        }

        .continue-shopping {
            background: var(--black);
            color: var(--white);
            border: none;
            padding: 1rem 2rem;
            font-size: 0.9rem;
            font-weight: 500;
            letter-spacing: 1px;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
        }

        .continue-shopping:hover {
            background: #333333;
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

        /* Адаптивность */
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .cart-item img {
                margin-right: 0;
                margin-bottom: 1rem;
            }

            .quantity-control {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="logo">Watch <span>Store</span></div>
    <nav>
        <a href="catalog.php">Каталог</a>

    </nav>
    <div class="header-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div style="position: relative;">

            </div>
            <button class="icon-btn" onclick="location.href='lk.php'"><i class="far fa-user"></i></button>
            <button class="icon-btn" onclick="location.href='?logout=1'"><i class="fas fa-sign-out-alt"></i></button>
        <?php else: ?>
            <button class="icon-btn" onclick="location.href='login.php'"><i class="far fa-user"></i></button>
        <?php endif; ?>
    </div>
</header>

<main class="main-content">
    <div class="cart-container">
        <h1 class="page-title">Ваша корзина</h1>

        <?php if (!empty($cartItems)): ?>
            <form id="cart-form">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <input type="checkbox" name="selected_items[]" value="<?= htmlspecialchars($item['cart_id']) ?>" class="select-checkbox" checked>
                        <img src="uploads/<?= htmlspecialchars($item['image_path'] ?? '') ?>" alt="<?= htmlspecialchars($item['name'] ?? '') ?>">
                        <div class="details">
                            <h3><?= htmlspecialchars($item['name'] ?? '') ?></h3>
                            <p>Цена: <?= number_format($item['price'] ?? 0, 0, '.', ' ') ?> ₽</p>
                            <div class="quantity-control">
                                <button class="dec-btn">-</button>
                                <input type="text"
                                       class="quantity-value"
                                       data-cart-id="<?= htmlspecialchars($item['cart_id']) ?>"
                                       value="<?= htmlspecialchars($item['cart_quantity']) ?>"
                                       readonly>
                                <button class="inc-btn">+</button>
                            </div>
                        </div>
                        <button class="delete-btn" data-cart-id="<?= htmlspecialchars($item['cart_id']) ?>">
                            <i class="fas fa-trash"></i> Удалить
                        </button>
                    </div>
                <?php endforeach; ?>
            </form>

            <div class="checkout-section">
                <button class="checkout-btn" onclick="processCheckout()">Перейти к оплате</button>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <p>Ваша корзина пуста</p>
                <a href="catalog.php" class="continue-shopping">Продолжить покупки</a>
            </div>
        <?php endif; ?>
    </div>
</main>



<script>
    // Инициализация чекбоксов
    const checkboxes = document.querySelectorAll('.select-checkbox');
    const checkoutBtn = document.querySelector('.checkout-btn');

    function updateCheckoutButton() {
        const selected = document.querySelectorAll('.select-checkbox:checked');
        checkoutBtn.disabled = selected.length === 0;
    }

    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateCheckoutButton);
    });

    // Обработка кнопок +/- для изменения количества
    document.querySelectorAll('.quantity-control').forEach(control => {
        const incBtn = control.querySelector('.inc-btn');
        const decBtn = control.querySelector('.dec-btn');
        const quantityInput = control.querySelector('.quantity-value');

        const updateQuantity = (delta) => {
            let newQuantity = parseInt(quantityInput.value) + delta;
            if (newQuantity < 1) newQuantity = 1;

            quantityInput.value = newQuantity;

            sendQuantityUpdate(quantityInput.dataset.cartId, newQuantity);

            decBtn.disabled = newQuantity <= 1;
        };

        incBtn.addEventListener('click', () => updateQuantity(1));
        decBtn.addEventListener('click', () => updateQuantity(-1));

        // Инициализация статуса кнопок
        decBtn.disabled = (parseInt(quantityInput.value) <= 1);
    });

    function sendQuantityUpdate(cartId, quantity) {
        fetch('update_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ cart_id: cartId, quantity: quantity })
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Ошибка обновления количества');
                    // Восстанавливаем предыдущее значение
                    const input = document.querySelector(`[data-cart-id="${cartId}"]`);
                    if (input) {
                        input.value = data.previousQuantity;
                    }
                }
            });
    }

    function processCheckout() {
        const selectedItems = Array.from(document.querySelectorAll('.select-checkbox:checked')).map(cb => cb.value);

        if (selectedItems.length > 0) {
            fetch('checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ selected_items: selectedItems })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'payment.php';
                    } else {
                        alert('Ошибка при обработке заказа');
                    }
                });
        }
    }

    // Обработчик для кнопки Удалить
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            const cartId = this.dataset.cartId;
            if (confirm('Вы уверены, что хотите удалить этот товар из корзины?')) {
                fetch('delete_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ cart_id: cartId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Удаляем элемент из DOM
                            const item = e.target.closest('.cart-item');
                            if (item) {
                                item.remove();
                            }
                            // Обновляем кнопку оформления заказа
                            updateCheckoutButton();

                            // Если корзина пуста, показываем сообщение
                            if (document.querySelectorAll('.cart-item').length === 0) {
                                window.location.reload();
                            }
                        } else {
                            alert('Ошибка удаления товара');
                        }
                    });
            }
            e.preventDefault();
        });
    });

    // Инициализация кнопки оформления заказа
    updateCheckoutButton();
</script>
</body>
</html>