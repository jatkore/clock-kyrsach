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

// Получение списка брендов, цветов и типов
$brands = $pdo->query("SELECT * FROM Brand")->fetchAll(PDO::FETCH_ASSOC);
$colors = $pdo->query("SELECT * FROM Color")->fetchAll(PDO::FETCH_ASSOC);
$types = $pdo->query("SELECT * FROM Type")->fetchAll(PDO::FETCH_ASSOC);

// Обработка отправленной формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверяем existence каждого поля
    $productName = $_POST['name'] ?? '';
    $brandName = $_POST['brand'] ?? '';
    $colorName = $_POST['color'] ?? ''; // Добавляем проверку для color
    $typeName = $_POST['type'] ?? '';   // Добавляем проверку для type
    $price = $_POST['price'] ?? '';
    $quantity = $_POST['quantity'] ?? '';

    // Валидация данных
    if (empty($productName) || empty($brandName) || empty($colorName) || empty($typeName) || empty($price) || empty($quantity)) {
        $error = "Пожалуйста, заполните все поля.";
    } else {
        try {
            // Добавление товара с названием бренда, цвета и типа
            $stmt = $pdo->prepare("INSERT INTO Product (name, brand_name, color_name, type_name, price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$productName, $brandName, $colorName, $typeName, $price, $quantity]);
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
    <title>Добавление товара</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        form {
            max-width: 400px;
            margin: 0 auto;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
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
    </style>
</head>
<body>
<h1>Добавление товара</h1>

<?php if (isset($success)): ?>
    <div class="message success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="message error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="">
    <label for="name">Название:</label>
    <input type="text" id="name" name="name" required>

    <label for="brand">Бренд:</label>
    <select id="brand" name="brand" required>
        <option value="" disabled selected>Выберите бренд</option>
        <?php foreach ($brands as $brand): ?>
            <option value="<?= htmlspecialchars($brand['name']) ?>"><?= htmlspecialchars($brand['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="color">Цвет:</label>
    <select id="color" name="color" required>
        <option value="" disabled selected>Выберите цвет</option>
        <?php foreach ($colors as $color): ?>
            <option value="<?= htmlspecialchars($color['name']) ?>"><?= htmlspecialchars($color['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="type">Тип часов:</label>
    <select id="type" name="type" required>
        <option value="" disabled selected>Выберите тип</option>
        <?php foreach ($types as $type): ?>
            <option value="<?= htmlspecialchars($type['name']) ?>"><?= htmlspecialchars($type['name']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="price">Цена:</label>
    <input type="number" id="price" name="price" step="0.01" required>

    <label for="quantity">Количество:</label>
    <input type="number" id="quantity" name="quantity" min="1" required>

    <button type="submit">Добавить товар</button>
</form>
</body>
</html>