<?php
if (!isset($pageTitle)) $pageTitle = 'Банкетам.Нет';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($pageTitle) ?> | Банкетам.Нет</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="/" class="logo">Банкетам.<span>Нет</span></a>
            <nav class="nav" id="nav">
                <a href="/">Главная</a>
                <?php if (isLoggedIn() && !isAdmin()): ?>
                    <a href="/cabinet.php">Личный кабинет</a>
                    <a href="/application.php">Новая заявка</a>
                    <a href="/logout.php">Выход</a>
                <?php elseif (isAdmin()): ?>
                    <a href="/admin.php">Панель администратора</a>
                    <a href="/logout.php">Выход</a>
                <?php else: ?>
                    <a href="/login.php">Вход</a>
                    <a href="/register.php">Регистрация</a>
                <?php endif; ?>
            </nav>
            <button class="burger" id="burger" aria-label="Меню">
                <span></span><span></span><span></span>
            </button>
        </div>
    </header>
    <main class="main">
        <div class="container">
