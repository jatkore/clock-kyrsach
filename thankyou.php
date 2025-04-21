<?php
session_start();
unset($_SESSION['order_items']); // Очищаем сессию после оплаты
?>

<!DOCTYPE html>
<html>
<head>
    <title>Спасибо за заказ!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
<h1>Ваш заказ успешно оформлен!</h1>
<p>Спасибо за покупку. Мы свяжемся с вами в ближайшее время.</p>
</body>
</html>