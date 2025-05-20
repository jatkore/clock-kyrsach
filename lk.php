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
$username = 'root'; // Замените на ваше имя пользователя
$password = 'root';     // Замените на ваш пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получаем данные пользователя
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Если пользователь не найден, перенаправляем на страницу входа
        header("Location: login.php");
        exit;
    }

    // Обработка сохранения изменений
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];

        // Обновление профиля
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, email = ? WHERE id = ?");
        $stmt->execute([$full_name, $phone, $email, $_SESSION['user_id']]);
        $user['full_name'] = $full_name;
        $user['phone'] = $phone;
        $user['email'] = $email;
        $success = "Данные успешно обновлены!";
    }

    // Обработка загрузки аватара
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
        $uploadDir = 'Avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $emailSafe = str_replace(['@', '.'], ['-', '_'], $user['email']);
        $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $fileName = $emailSafe . '.' . strtolower($fileExtension);
        $filePath = $uploadDir . $fileName;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
            $stmt = $pdo->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
            $stmt->execute([$fileName, $_SESSION['user_id']]);
            $user['avatar_path'] = $fileName;
            $success = "Аватар успешно загружен!";
        } else {
            $error = "Ошибка при загрузке аватара.";
        }
    }
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный Кабинет</title>
    <style>
        /* Общие стили */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
            color: #333;
        }

        header {
            background-color: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            font-size: 16px;
            transition: opacity 0.3s;
        }

        nav a:hover {
            opacity: 0.8;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            display: flex;
            gap: 20px;
        }

        /* Сайдбар */
        .sidebar {
            flex: 0 0 220px;
            background-color: #ffffff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .sidebar h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            margin-bottom: 10px;
        }

        .sidebar a {
            color: #34495e;
            text-decoration: none;
            font-weight: 500;
            display: block;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background-color: #ecf0f1;
            color: #2c3e50;
        }

        /* Основной контент */
        .content {
            flex: 1;
            background-color: #fff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .profile-section h2 {
            margin-top: 0;
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .profile-avatar {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-avatar img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
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

        .upload-button:hover {
            background-color: #2980b9;
        }

        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .buttons {
            margin-top: 20px;
        }

        .btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 16px;
            margin-right: 10px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #27ae60;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-size: 14px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .editable {
            display: none;
        }

        #editModeOn {
            display: block;
        }

        #editModeOff {
            display: block;
        }

        input[type="text"] {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .profile-avatar img {
                width: 60px;
                height: 60px;
            }

            .editable-field {
                display: none;
                width: 100%;
                padding: 8px;
                margin-top: 5px;
                border: 1px solid #ccc;
                border-radius: 6px;
                font-size: 14px;
            }


        }
    </style>

    </style>
</head>
<body>
<header>
    <nav>
        <a href="#">Главная</a>
        <a href="#">Каталог</a>
        <a href="#">О нас</a>
        <a href="#">Контакты</a>
    </nav>
    <div>
        <button class="cart-button">Корзина 🛒</button>
        <button onclick="location.href='logout.php'" class="logout-button">Выйти</button>
    </div>
</header>

<div class="container">
    <!-- Сайдбар с навигацией -->
    <div class="sidebar">
        <h3>Меню</h3>
        <ul>
            <li><a href="#profile" class="active">О себе</a></li>
            <li><a href="#orders">Сделанные заказы</a></li>
        </ul>
    </div>

    <!-- Основной контент -->
    <div class="content">
        <!-- Блок "О себе" -->
        <section id="profile" class="profile-section">
            <h2>О себе</h2>

            <?php if (isset($success)): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php elseif (isset($error)): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" action="" enctype="multipart/form-data">
                <div class="profile-avatar">
                    <img id="avatar" src="<?= !empty($user['avatar_path']) ? 'Avatars/' . htmlspecialchars($user['avatar_path']) : 'https://via.placeholder.com/100x100 ' ?>" alt="Аватар">

                    <div id="avatar-upload" style="display: none;">
                        <label for="upload-avatar" class="upload-button">Выбрать фото</label>
                        <input type="file" id="upload-avatar" name="avatar" accept="image/*" style="display: none;">
                        <small>После выбора файла он сразу загрузится</small>
                    </div>
                </div>

                <p><strong>ФИО:</strong><br>
                    <span id="fullName"><?= htmlspecialchars($user['full_name']) ?></span>
                    <input type="text" id="edit-fullName" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" class="editable">
                </p>

                <p><strong>Номер телефона:</strong><br>
                    <span id="phone"><?= htmlspecialchars($user['phone']) ?></span>
                    <input type="text" id="edit-phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" class="editable">
                </p>

                <p><strong>Email:</strong><br>
                    <span id="email"><?= htmlspecialchars($user['email']) ?></span>
                    <input type="text" id="edit-email" name="email" value="<?= htmlspecialchars($user['email']) ?>" class="editable">
                </p>

                <div class="buttons">
                    <button type="button" id="editBtn" class="btn">Редактировать</button>
                    <button type="submit" name="save_profile" id="saveBtn" class="btn" style="display:none;">Сохранить</button>
                </div>
            </form>
        </section>

        <!-- Блок "Сделанные заказы" -->
        <section id="orders" class="orders-section">
            <h2>Сделанные заказы</h2>
            <div id="order-list">
                <!-- Заказы будут загружаться динамически -->
            </div>
        </section>
    </div>
</div>

<script>
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const fields = ['fullName', 'phone', 'email'];
    const avatarUpload = document.getElementById('avatar-upload');

    editBtn.addEventListener('click', () => {
        // Переключение видимости полей
        fields.forEach(id => {
            document.getElementById(id).style.display = 'none';
            document.getElementById('edit-' + id).style.display = 'block';
        });

        avatarUpload.style.display = 'block';
        saveBtn.style.display = 'inline-block';
        editBtn.style.display = 'none';
    });
</script>
</body>
</html>