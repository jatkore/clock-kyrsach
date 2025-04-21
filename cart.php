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
if (empty($cartItems)) {
    $cartItems = [];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Корзина</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }

        .cart-item {
            background-color: white;
            padding: 20px;
            margin: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .cart-item img {
            max-width: 150px;
            height: auto;
            margin-right: 20px;
        }

        .cart-item .details {
            flex: 1;
        }

        .quantity-control {
            display: inline-flex;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 5px;
        }

        .quantity-control button {
            width: 30px;
            padding: 6px 0;
            background: transparent;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 16px;
            transition: background 0.2s;
        }

        .quantity-control button:hover {
            background: #f0f0f0;
        }

        .quantity-control button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .quantity-value {
            padding: 6px 12px;
            min-width: 30px;
            text-align: center;
            border: none;
            outline: none;
            background: transparent;
            font-size: 14px;
            color: #333;
        }

        .checkout-btn {
            background-color: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
            opacity: 0.6;
            pointer-events: none;
        }

        .checkout-btn.active {
            opacity: 1;
            pointer-events: auto;
        }

        /* Стили для кнопки Удалить */
        .delete-btn {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
            transition: background-color 0.2s;
        }

        .delete-btn:hover {
            background-color: #cc0000;
        }
    </style>
</head>
<body>
<h1>Корзина</h1>

<form id="cart-form">
    <?php foreach ($cartItems as $item): ?>
        <div class="cart-item">
            <input type="checkbox" name="selected_items[]" value="<?= htmlspecialchars($item['cart_id']) ?>" class="select-checkbox">
            <div class="details">
                <img src="uploads/<?= htmlspecialchars($item['image_path'] ?? '') ?>" height="100">
                <h3><?= htmlspecialchars($item['name'] ?? '') ?></h3>
                <p>Цена: <?= htmlspecialchars($item['price'] ?? 0) ?> ₽</p>
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
            <!-- Кнопка Удалить -->
            <button class="delete-btn" data-cart-id="<?= htmlspecialchars($item['cart_id']) ?>">Удалить</button>
        </div>
    <?php endforeach; ?>
</form>

<button class="checkout-btn" onclick="processCheckout()">Перейти к оплате</button>

<script>
    // Инициализация чекбоксов
    const checkboxes = document.querySelectorAll('.select-checkbox');
    const checkoutBtn = document.querySelector('.checkout-btn');
    updateCheckoutButton();

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

    function updateCheckoutButton() {
        const selected = document.querySelectorAll('.select-checkbox:checked');
        checkoutBtn.classList.toggle('active', selected.length > 0);
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
                        } else {
                            alert('Ошибка удаления товара');
                        }
                    });
            }
            e.preventDefault();
        });
    });
</script>
</body>
</html>