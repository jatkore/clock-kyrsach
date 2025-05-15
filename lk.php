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

    // Обработка загрузки аватара
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
        $uploadDir = 'Avatars/'; // Директория для хранения аватаров
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Создаем директорию, если ее нет
        }

        // Генерируем уникальное имя файла на основе почты пользователя
        $emailSafe = str_replace(['@', '.'], ['-', '_'], $user['email']); // Очищаем почту от недопустимых символов
        $fileExtension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION); // Получаем расширение файла
        $fileName = $emailSafe . '.' . strtolower($fileExtension); // Имя файла = почта + расширение
        $filePath = $uploadDir . $fileName;

        // Проверяем, существует ли уже файл с таким именем
        if (file_exists($filePath)) {
            unlink($filePath); // Удаляем старый файл, если он существует
        }

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
            // Сохраняем путь к файлу в базе данных
            $stmt = $pdo->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
            $stmt->execute([$fileName, $_SESSION['user_id']]);
            $user['avatar_path'] = $fileName; // Обновляем данные пользователя
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

        nav a {
            color: white;
            text-decoration: none;
            margin-right: 15px;
            font-size: 16px;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            display: flex;
        }

        /* Сайдбар с навигацией */
        .sidebar {
            flex: 0 0 200px;
            margin-right: 20px;
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
        }

        .sidebar h3 {
            margin-bottom: 10px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            margin-bottom: 5px;
        }

        .sidebar a {
            color: #333;
            text-decoration: none;
            font-size: 16px;
        }

        .sidebar a:hover {
            text-decoration: underline;
        }

        /* Основной контент */
        .content {
            flex: 1;
        }

        .profile-section {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .profile-section h2 {
            margin-bottom: 15px;
        }

        .profile-avatar {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .profile-avatar img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .upload-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }

        .upload-button:hover {
            background-color: #45a049;
        }

        .orders-section {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }

        .order-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-details {
            display: flex;
            justify-content: space-between;
        }

        /* Адаптивность */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                flex: 1;
                margin-bottom: 20px;
            }
        }

        /* Редактируемые поля */
        .editable {
            display: inline-block;
        }

        input[type="text"], input[type="tel"], input[type="email"] {
            display: none;
            width: 100%;
            margin-top: 5px;
            padding: 5px;
        }

        button#editButton {
            margin-right: 10px;
        }
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
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="profile-avatar">
                <img id="avatar" src="<?= !empty($user['avatar_path']) ? 'Avatars/' . htmlspecialchars($user['avatar_path']) : 'https://via.placeholder.com/100x100 ' ?>" alt="Аватар">

                <!-- Форма для загрузки аватара -->
                <form method="POST" action="" enctype="multipart/form-data">
                    <label for="upload-avatar" class="upload-button">Выбрать фото</label>
                    <input type="file" id="upload-avatar" name="avatar" accept="image/*" required style="display: none;">
                    <button type="submit">Загрузить аватар</button>
                </form>
            </div>

            <p><strong>ФИО:</strong>
                <span class="editable" id="fullNameDisplay"><?= htmlspecialchars($user['full_name']) ?></span>
                <input type="text" id="fullNameInput" value="<?= htmlspecialchars($user['full_name']) ?>">
            </p>
            <p><strong>Номер телефона:</strong>
                <span class="editable" id="phoneDisplay"><?= htmlspecialchars($user['phone']) ?></span>
                <input type="tel" id="phoneInput" value="<?= htmlspecialchars($user['phone']) ?>">
            </p>
            <p><strong>Email:</strong>
                <span class="editable" id="emailDisplay"><?= htmlspecialchars($user['email']) ?></span>
                <input type="email" id="emailInput" value="<?= htmlspecialchars($user['email']) ?>">
            </p>

            <button id="editButton">Редактировать</button>
            <button id="saveButton" style="display: none;">Сохранить</button>
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
    // При клике на кнопку "Выбрать фото" открывается диалог выбора файла
    document.querySelector('.upload-button').addEventListener('click', function (event) {
        event.preventDefault(); // Предотвращаем отправку формы
        document.getElementById('upload-avatar').click(); // Открываем диалог выбора файла
    });

    // При выборе файла показываем его превью (необязательно)
    document.getElementById('upload-avatar').addEventListener('change', function (event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('avatar').src = e.target.result; // Показываем превью выбранного файла
            };
            reader.readAsDataURL(file);
        }
    });

    // Логика редактирования профиля
    document.getElementById('editButton').addEventListener('click', function () {
        document.getElementById('fullNameDisplay').style.display = 'none';
        document.getElementById('phoneDisplay').style.display = 'none';
        document.getElementById('emailDisplay').style.display = 'none';

        document.getElementById('fullNameInput').style.display = 'block';
        document.getElementById('phoneInput').style.display = 'block';
        document.getElementById('emailInput').style.display = 'block';

        this.style.display = 'none';
        document.getElementById('saveButton').style.display = 'inline-block';
    });

    document.getElementById('saveButton').addEventListener('click', function (e) {
        e.preventDefault();

        const full_name = document.getElementById('fullNameInput').value.trim();
        const phone = document.getElementById('phoneInput').value.trim();
        const email = document.getElementById('emailInput').value.trim();

        fetch('update_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ full_name, phone, email })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Данные успешно обновлены');

                    document.getElementById('fullNameDisplay').textContent = full_name;
                    document.getElementById('phoneDisplay').textContent = phone;
                    document.getElementById('emailDisplay').textContent = email;

                    document.getElementById('fullNameDisplay').style.display = 'inline';
                    document.getElementById('phoneDisplay').style.display = 'inline';
                    document.getElementById('emailDisplay').style.display = 'inline';

                    document.getElementById('fullNameInput').style.display = 'none';
                    document.getElementById('phoneInput').style.display = 'none';
                    document.getElementById('emailInput').style.display = 'none';

                    document.getElementById('saveButton').style.display = 'none';
                    document.getElementById('editButton').style.display = 'inline-block';
                } else {
                    alert('Ошибка при сохранении: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при отправке запроса.');
            });
    });
</script>
</body>
</html>