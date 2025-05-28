<?php
session_start();
// Проверяем, авторизован ли пользователь
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
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

    // Получаем данные пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: login.php");
        exit;
    }

    $success = '';
    $error = '';

    $isAuthenticated = isset($_SESSION['user_id']);

    // Обработка выхода из системы
    if (isset($_GET['logout'])) {
        session_unset();
        session_destroy();
        header("Location: index.php");
        exit();
    }

    // Обработка сохранения изменений
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
        $full_name = $_POST['full_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $email = $_POST['email'] ?? '';

        if (empty($full_name) || empty($phone) || empty($email)) {
            $error = "Пожалуйста, заполните все поля.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
                $stmt->execute([$full_name, $phone, $email, $_SESSION['user_id']]);
                $user['full_name'] = $full_name;
                $user['phone'] = $phone;
                $user['email'] = $email;
                $success = "Данные успешно обновлены!";
            } catch (PDOException $e) {
                $error = "Ошибка при обновлении данных: " . $e->getMessage();
            }
        }
    }

    // Обработка загрузки аватара
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
        $uploadDir = 'Avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $emailSafe = str_replace(['@', '.'], ['-', '_'], $user['email']);
        $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = $emailSafe . '.' . strtolower($fileExtension);
        $filePath = $uploadDir . $fileName;

        if (file_exists($filePath)) unlink($filePath);

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
            $stmt->execute([$fileName, $_SESSION['user_id']]);
            $user['avatar_path'] = $fileName;
            $success = "Аватар успешно загружен!";
        } else {
            $error = "Ошибка при загрузке аватара.";
        }
    }

    $cartCount = 0;
    if ($isAuthenticated) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cartCount = $stmt->fetchColumn() ?? 0;
    }

    $stmt = $pdo->prepare("
    SELECT 
        id AS order_id,
        order_date,
        quantity AS order_quantity,
        total_price,
        product_name,
        product_brand,
        product_color,
        product_image
    FROM Orders
    WHERE user_id = ?
    ORDER BY order_date DESC
");
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный Кабинет | Minimal Horizon</title>
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


        /* Заказы */
        .orders-list {
            margin-top: 2rem;
        }

        .order-card {
            border: 1px solid var(--gray);
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray);
        }

        .order-date {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
        }

        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            margin-right: 1rem;
            border-radius: 4px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .item-quantity {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .item-brand {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 0.2rem;
        }

        .item-price {
            font-weight: bold;
            margin: 0.3rem 0;
            color: var(--black);
        }

        .no-orders {
            text-align: center;
            padding: 2rem;
            color: var(--text-light);
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
            margin-right: 1400px;
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



        .icon-btn-cart {
            background: none;
            border: none;
            color: var(--text-dark);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
            width:50px;


        }

        .icon-btn-logout {
            background: none;
            border: none;
            color: var(--text-dark);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);


        }

        .icon-btn-cart:hover {
            color: var(--black);
            transform: translateY(-2px);

        }

        .icon-btn-logout:hover {
            color: var(--black);
            transform: translateY(-2px);

        }

        .cart-count {
            position: absolute;
            top: -6px;
            left: 20px;
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
        .user-container {
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
        .profile-form {
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

        /* Аватар */
        .avatar-container {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1.5rem;
            border: 2px solid var(--gray);
        }

        .avatar-upload {
            display: flex;
            flex-direction: column;
        }

        .avatar-upload label {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-dark);
        }

        .upload-button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 12px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 15px;
            transition: background-color 0.3s;
        }

        /* Редактируемые поля */
        .view-mode {
            display: block;
            padding: 0.8rem;
            background-color: var(--light-gray);
            border-radius: 4px;
            margin-bottom: 1rem;
        }

        .edit-mode {
            display: none;
        }

        .editing .view-mode {
            display: none;
        }

        .editing .edit-mode {
            display: block;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .user-container {
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
        }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="logo">Watch <span>Store</span></a>
    <nav>
        <a href="catalog.php">Каталог</a>
    </nav>





    <div class="header-actions">

            <div style="position: relative;">
                <button class="icon-btn-cart" onclick="location.href='cart.php'">
                    <i class="fas fa-shopping-bag"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count"><?= $cartCount ?></span>
                    <?php endif; ?>
                </button>

              <button class="icon-btn-logout" onclick="location.href='?logout=1'"><i class="fas fa-sign-out-alt"></i></button>


    </div>

</header>

<div class="user-container">
    <!-- Сайдбар с навигацией -->
    <div class="sidebar">
        <h3>Меню пользователя</h3>
        <ul>
            <li><a href="#profile" class="active">Профиль</a></li>
            <li><a href="#orders">Мои заказы</a></li>
        </ul>
    </div>

    <!-- Основной контент -->
    <div class="content">
        <!-- Раздел "Профиль" -->
        <section id="profile" class="section active">
            <h2>Профиль</h2>
            <?php if (!empty($success)): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="profile-form" enctype="multipart/form-data">
                <div class="avatar-container">
                    <img src="<?= !empty($user['avatar_path']) ? 'Avatars/' . htmlspecialchars($user['avatar_path']) : 'https://via.placeholder.com/100x100' ?>" alt="Аватар" class="avatar" id="avatar-preview">
                    <div class="avatar-upload">
                        <label for="avatar">Изменить аватар</label>
                        <input type="file" id="avatar" name="avatar" accept="image/*" class="file-input">
                    </div>
                </div>

                <div class="form-group">
                    <label for="full_name">ФИО:</label>
                    <div class="view-mode"><?= htmlspecialchars($user['full_name']) ?></div>
                    <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="edit-mode form-control">
                </div>

                <div class="form-group">
                    <label for="phone">Телефон:</label>
                    <div class="view-mode"><?= htmlspecialchars($user['phone']) ?></div>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="edit-mode form-control">
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <div class="view-mode"><?= htmlspecialchars($user['email']) ?></div>
                    <input type="text" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="edit-mode form-control">
                </div>

                <button type="button" id="edit-btn" class="submit-btn">Редактировать</button>
                <button type="submit" name="save_profile" id="save-btn" class="submit-btn" style="display: none;">Сохранить</button>
            </form>
        </section>

        <!-- Раздел "Мои заказы" -->
        <section id="orders" class="section">
            <h2>Мои заказы</h2>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <p>У вас пока нет заказов.</p>
                    <a href="catalog.php" class="submit-btn" style="display: inline-block; margin-top: 1rem;">Перейти в каталог</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                        <span class="order-date">
                            <?= date('d.m.Y', strtotime($order['order_date'])) ?>
                        </span>
                            </div>

                            <div class="order-item">
                                <?php
                                // Полный путь к изображению
                                $imagePath = !empty($order['product_image']) ? $order['product_image'] : 'https://via.placeholder.com/100x100';

                                // Проверяем, есть ли уже префикс 'uploads/'
                                if (!empty($order['product_image']) && strpos($order['product_image'], 'uploads/') === false) {
                                    $imagePath = 'uploads/' . $order['product_image'];
                                }
                                ?>
                                <img src="<?= htmlspecialchars($imagePath) ?>"
                                     alt="<?= htmlspecialchars($order['product_name']) ?>"
                                     class="item-image"
                                     onerror="this.src='https://via.placeholder.com/100x100'">
                                <div class="item-details">
                                    <div class="item-name"><?= htmlspecialchars($order['product_name']) ?></div>
                                    <div class="item-quantity">Количество: <?= htmlspecialchars($order['order_quantity']) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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

    // Редактирование профиля
    const editBtn = document.getElementById('edit-btn');
    const saveBtn = document.getElementById('save-btn');
    const form = document.querySelector('.profile-form');

    editBtn.addEventListener('click', function() {
        form.classList.add('editing');
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
    });

    // Предпросмотр аватара
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatar-preview');

    avatarInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarPreview.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });
</script>

</body>
</html>