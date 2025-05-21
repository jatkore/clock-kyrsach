<?php
session_start();

// Подключение к базе данных
$host = 'mysql';
$dbname = 'watch_store';
$username = 'root'; // Замените на ваше имя пользователя
$password = 'root'; // Замените на ваш пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Если пользователь уже авторизован — редиректим его
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin' || $_SESSION['email'] === 'kea@vt2b.ru') {
        header("Location: adminlk.php");
    } else {
        header("Location: lk.php");
    }
    exit;
}

$error = '';
// Обработка формы авторизации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Пожалуйста, заполните все поля.";
    } else {
        try {
            // Ищем пользователя по email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Успешная авторизация
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                $_SESSION['email'] = $user['email'];

                // Определяем, куда перенаправить
                if ($user['email'] === 'kea@vt2b.ru' || $user['role'] === 'admin') {
                    header("Location: adminlk.php");
                } else {
                    header("Location: lk.php");
                }
                exit;
            } else {
                $error = "Неверная почта или пароль.";
            }
        } catch (PDOException $e) {
            $error = "Ошибка при авторизации: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Авторизация</title>
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
            margin: 0;
            padding: 0;
            background-color: var(--light-gray);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-wrapper {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 4rem auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .login-wrapper h2 {
            font-size: 1.8rem;
            font-weight: 300;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--black);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        label {
            display: block;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        input[type="email"],
        input[type="password"] {
            width: 95%;
            padding: 0.8rem;
            border: 1px solid var(--gray);
            border-radius: 4px;
            font-size: 0.95rem;
            transition: var(--transition);
        }

        input:focus {
            outline: none;
            border-color: var(--black);
        }

        button {
            background-color: var(--black);
            color: var(--white);
            border: none;
            padding: 0.9rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: var(--transition);
        }

        button:hover {
            background-color: #333333;
        }

        a.back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--text-light);
            font-size: 0.9rem;
            text-decoration: none;
            transition: var(--transition);
        }

        a.back-link:hover {
            color: var(--black);
        }

        .error-message {
            color: var(--error);
            font-size: 0.85rem;
            text-align: center;
            margin-top: -1rem;
        }

        footer {
            margin-top: 3rem;
            text-align: center;
            color: var(--text-light);
            font-size: 0.8rem;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <h2>Авторизация</h2>

    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Пароль:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Войти</button>
    </form>

    <a href="register.php" class="back-link">Зарегистрироваться</a>
    <a href="index.php" class="back-link">← Вернуться на главную</a>

</div>



</body>
</html>