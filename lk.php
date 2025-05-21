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


} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный Кабинет</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css ">
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

        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-dark);
            background-color: var(--white);
            line-height: 1.6;
        }

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


        .editable {
            display: none;
        }

        #editModeOn {
            display: block;
        }

        #editModeOff {
            display: block;
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

        .upload-button {
            background-color: #000000;
            color: white;
            border: none;
            padding: 10px 12px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            margin-left: 15px;
            transition: background-color 0.3s;
        }

        .upload-button:hover {
            background-color: #2980b9;
        }

        .header-actions {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .header-actions button {
            background: none;
            border: none;
            color: var(--text-dark);
            font-size: 1.1rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .container {
            display: flex;
            margin-top: 80px;
            padding: 2rem 10%;
        }

        .sidebar {
            width: 250px;
            margin-right: 2rem;
        }

        .sidebar h3 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            margin-bottom: 0.8rem;
        }

        .sidebar a {
            text-decoration: none;
            color: var(--text-dark);
            font-weight: 400;
            transition: var(--transition);
        }

        .sidebar a:hover {
            color: var(--black);
        }

        .content {
            flex-grow: 1;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        form.profile-form {
            background-color: var(--light-gray);
            padding: 2rem;
            border-radius: 4px;
            max-width: 500px;
        }

        form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        form input[type="text"] {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
        }

        form button {
            background-color: var(--black);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        form button:hover {
            background-color: #333333;
        }

        .profile-avatar {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .profile-avatar img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .profile-avatar input[type="file"] {
            margin-left: 1rem;
        }

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


    </style>
</head>
<body>
<header>
    <div class="logo">MINIMAL <span>HORIZON</span></div>
    <nav>
        <a href="index.php" class="active">Главная</a>

    </nav>


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
            <button class="icon-btn" onclick="location.href='?logout=1'"><i class="fas fa-sign-out-alt"></i></button>
            <button class="icon-btn" onclick="location.href='?logout=1'"><i class="fas fa-sign-out-alt"></i></button>
            <button class="icon-btn" onclick="location.href='?logout=1'"><i class="fas fa-sign-out-alt"></i></button>

        <?php else: ?>
            <button class="icon-btn" id="loginButton"><i class="far fa-user"></i></button>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    <div class="sidebar">
        <h3>Меню</h3>
        <ul>
            <li><a href="#profile" class="active">О себе</a></li>
            <li><a href="#orders">Заказы</a></li>
        </ul>
    </div>

    <div class="content">
        <!-- Раздел "О себе" -->
        <section id="profile" class="section active">
            <h2>О себе</h2>
            <?php if ($success): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php elseif ($error): ?>
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

        <!-- Раздел "Заказы" -->
        <section id="orders" class="section">
            <h2>Ваши заказы</h2>
            <p>На данный момент список ваших заказов пуст.</p>
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