<?php
// Подключение к базе данных
$host = 'mysql';
$dbname = 'watch_store';
$username = 'root'; // Замените на ваше имя пользователя
$password = 'root';     // Замените на ваш пароль

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Получение списков брендов, цветов, типов и видов
$brands = $pdo->query("SELECT * FROM Brand")->fetchAll(PDO::FETCH_ASSOC);
$colors = $pdo->query("SELECT * FROM Color")->fetchAll(PDO::FETCH_ASSOC);
$types = $pdo->query("SELECT * FROM Type")->fetchAll(PDO::FETCH_ASSOC);
$views = $pdo->query("SELECT * FROM View")->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправленной формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем existence каждого поля
    $productName = $_POST['name'] ?? '';
    $brandName = $_POST['brand'] ?? '';
    $colorName = $_POST['color'] ?? '';
    $typeName = $_POST['type'] ?? '';
    $viewName = $_POST['view'] ?? '';
    $gender = $_POST['gender'] ?? ''; // Поле "Пол"
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';

    // Обработка загрузки изображения
    $imagePath = ''; // Инициализация пути к изображению
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/'; // Директория для хранения изображений
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true); // Создаем директорию, если ее нет
        }

        $fileName = uniqid() . '-' . basename($_FILES['image']['name']); // Генерируем уникальное имя файла
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $imagePath = $fileName; // Сохраняем относительный путь к файлу
        } else {
            $error = "Ошибка при загрузке изображения.";
        }
    } else {
        $error = "Пожалуйста, выберите изображение.";
    }

    // Валидация данных
    if (
        empty($productName) ||
        empty($brandName) ||
        empty($colorName) ||
        empty($typeName) ||
        empty($viewName) ||
        empty($gender) || // Проверка поля "Пол"
        empty($price) ||
        empty($quantity) ||
        empty($imagePath)
    ) {
        $error = "Пожалуйста, заполните все поля.";
    } else {
        try {
            // Добавление товара с названием бренда, цвета, типа, вида, пола и изображения
            $stmt = $pdo->prepare("INSERT INTO Product (name, brand_name, color_name, type_name, view_name, gender, price, quantity, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$productName, $brandName, $colorName, $typeName, $viewName, $gender, $price, $quantity, $imagePath]);
            $success = "Товар успешно добавлен!";
        } catch (PDOException $e) {
            $error = "Ошибка при добавлении товара: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный Кабинет Администратора</title>
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
            padding: 5px 10px;
            display: block;
            border-radius: 5px;
        }

        .sidebar a.active {
            background-color: #4CAF50;
            color: white;
        }

        .sidebar a:hover {
            background-color: #ddd;
            color: #333;
        }

        /* Основной контент */
        .content {
            flex: 1;
        }

        .section {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .section h2 {
            margin-bottom: 15px;
        }

        .sales-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sales-info div {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-right: 1px solid #ddd;
        }

        .sales-info div:last-child {
            border-right: none;
        }

        .add-product-form {
            display: flex;
            flex-direction: column;
        }

        .add-product-form label {
            margin-bottom: 5px;
        }

        .add-product-form input, .add-product-form select, .add-product-form button {
            margin-bottom: 15px;
            padding: 5px;
            font-size: 14px;
        }

        .add-product-form button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .add-product-form button:hover {
            background-color: #45a049;
        }

        .message {
            text-align: center;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
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
    </style>
</head>
<body>
<header>
    <h1>Личный Кабинет Администратора</h1>
    <button class="logout-button">Выйти</button>
</header>

<div class="container">
    <!-- Сайдбар с навигацией -->
    <div class="sidebar">
        <h3>Меню</h3>
        <ul>
            <li><a href="#sales" class="active">Количество продаж</a></li>
            <li><a href="#add-product">Добавление товаров</a></li>
        </ul>
    </div>

    <!-- Основной контент -->
    <div class="content">
        <!-- Раздел "Количество продаж" -->
        <section id="sales" class="section">
            <h2>Количество продаж</h2>
            <div class="sales-info">
                <div>
                    <strong>Общая выручка:</strong><br>
                    <span>500,000 ₽</span>
                </div>
                <div>
                    <strong>Количество заказов:</strong><br>
                    <span>120</span>
                </div>
                <div>
                    <strong>Средний чек:</strong><br>
                    <span>4,167 ₽</span>
                </div>
            </div>
        </section>

        <!-- Раздел "Добавление товаров" -->
        <section id="add-product" class="section">
            <h2>Добавление товаров</h2>

            <?php if (isset($success)): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="add-product-form" enctype="multipart/form-data">
                <label for="product-name">Название:</label>
                <input type="text" id="product-name" name="name" required>

                <label for="product-brand">Бренд:</label>
                <select id="product-brand" name="brand" required>
                    <option value="" disabled selected>Выберите бренд</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= htmlspecialchars($brand['name']) ?>"><?= htmlspecialchars($brand['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="product-color">Цвет:</label>
                <select id="product-color" name="color" required>
                    <option value="" disabled selected>Выберите цвет</option>
                    <?php foreach ($colors as $color): ?>
                        <option value="<?= htmlspecialchars($color['name']) ?>"><?= htmlspecialchars($color['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="product-type">Тип часов:</label>
                <select id="product-type" name="type" required>
                    <option value="" disabled selected>Выберите тип</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= htmlspecialchars($type['name']) ?>"><?= htmlspecialchars($type['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="product-view">Вид:</label>
                <select id="product-view" name="view" required>
                    <option value="" disabled selected>Выберите вид</option>
                    <?php foreach ($views as $view): ?>
                        <option value="<?= htmlspecialchars($view['name']) ?>"><?= htmlspecialchars($view['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Новое поле "Пол" через <select> -->
                <label for="product-gender">Пол:</label>
                <select id="product-gender" name="gender" required>
                    <option value="" disabled selected>Выберите пол</option>
                    <option value="Мужской">Мужской</option>
                    <option value="Женский">Женский</option>
                    <option value="Унисекс">Унисекс</option>
                </select>

                <label for="product-price">Цена:</label>
                <input type="number" id="product-price" name="price" step="0.01" required>

                <label for="product-quantity">Количество:</label>
                <input type="number" id="product-quantity" name="quantity" min="1" required>

                <!-- Новое поле для загрузки изображения -->
                <label for="product-image">Изображение:</label>
                <input type="file" id="product-image" name="image" accept="image/*" required>

                <button type="submit">Добавить товар</button>
            </form>
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
            sections.forEach(section => section.style.display = 'none');

            // Добавить активный класс к выбранной ссылке
            this.classList.add('active');
            const targetId = this.getAttribute('href').substring(1);
            document.getElementById(targetId).style.display = 'block';
        });
    });
</script>
</body>
</html>