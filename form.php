<!DOCTYPE html>
<html lang="en">
<head>
    <met a charset="UTF-8">
    <met a name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить продукт</title>
</head>
<body>
    <h1>Добавить новый продукт</h1>
    <form action="" method="post">
        <label for="brand_name">Бренд:</label>
        <select id="brand_name" name="brand_name" required>
            <option value="Rolex">Rolex</option>
            <option value="Omega">Omega</option>
            <option value="Casio">Casio</option>
        </select><br>

        <label for="name">Название товара:</label>
        <input type="text" id="name" name="name" required><br>

        <label for="type_name">Тип товара:</label>
        <select id="type_name" name="type_name" required>
            <option value="Механические">Механические</option>
            <option value="Кварцевые">Кварцевые</option>
            <option value="Умные часы">Умные часы</option>
        </select><br>

        <label for="color_name">Цвет:</label>
        <select id="color_name" name="color_name" required>
            <option value="Черный">Черный</option>
            <option value="Белый">Белый</option>
            <option value="Серебристый">Серебристый</option>
        </select><br>

        <label for="price">Цена:</label>
        <input type="number" id="price" name="price" step="0.01" required><br>

        <label for="stock_quantity">Количество в наличии:</label>
        <input type="number" id="stock_quantity" name="stock_quantity" required><br>

        <input type="submit" value="Добавить продукт">
    </form>
</body>
</html>


<?php
$servername = "mysql"; // Имя сервера
$username = "root"; // Имя пользователя базы данных
$password = "root"; // Пароль базы данных
$dbname = "watch_store"; // Имя базы данных

// Создаем соединение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверяем соединение
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Получаем данные из формы
$brand_name = $_POST['brand_name'];
$name = $_POST['name'];
$type_name = $_POST['type_name'];
$color_name = $_POST['color_name'];
$price = $_POST['price'];
$stock_quantity = $_POST['stock_quantity'];

// Подготавливаем и выполняем SQL-запрос
$sql = "INSERT INTO products (brand_name, name, type_name, color_name, price, stock_quantity)
        VALUES ('$brand_name', '$name', '$type_name', '$color_name', $price, $stock_quantity)";

if ($conn->query($sql) === TRUE) {
    echo "Новый продукт успешно добавлен!";
} else {
    echo "Ошибка: " . $sql . "<br>" . $conn->error;
}

// Закрываем соединение
$conn->close();
?>
