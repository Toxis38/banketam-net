<?php
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Главная';
require_once __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1>Банкетам.Нет</h1>
    <p>Бронирование помещений для проведения банкетов: зал, ресторан, летняя веранда, закрытая веранда</p>
    
    <div class="slider">
        <div class="slider-track">
            <div class="slide" style="background-image: url('/assets/images/image.jpeg')"></div>
            <div class="slide" style="background-image: url('/assets/images/image%20(1).jpeg')"></div>
            <div class="slide" style="background-image: url('/assets/images/image%20(2).jpeg')"></div>
            <div class="slide" style="background-image: url('/assets/images/image%20(3).jpeg')"></div>
        </div>
        <button class="slider-btn prev" aria-label="Назад">‹</button>
        <button class="slider-btn next" aria-label="Вперёд">›</button>
        <div class="slider-dots"></div>
    </div>

    <div class="features">
        <div class="feature">
            <h3>Зал</h3>
            <p class="aux-text">Просторный зал для больших компаний</p>
        </div>
        <div class="feature">
            <h3>Ресторан</h3>
            <p class="aux-text">Уютный ресторан с изысканной кухней</p>
        </div>
        <div class="feature">
            <h3>Летняя веранда</h3>
            <p class="aux-text">Открытое пространство на свежем воздухе</p>
        </div>
        <div class="feature">
            <h3>Закрытая веранда</h3>
            <p class="aux-text">Защищённая веранда в любую погоду</p>
        </div>
    </div>

    <?php if (!isLoggedIn()): ?>
        <a href="/register.php" class="btn">Зарегистрироваться</a>
        <div class="link-center">
            <a href="/login.php">Уже есть аккаунт? Войти</a>
        </div>
    <?php else: ?>
        <a href="/application.php" class="btn">Оформить заявку</a>
    <?php endif; ?>
</div>

<div class="form-card" style="margin-top: 24px;">
    <h2>Варианты оплаты</h2>
    <ul style="list-style: none; padding-left: 0;">
        <li style="margin-bottom: 8px;">✓ Предоплата по QR-коду</li>
        <li style="margin-bottom: 8px;">✓ Оплата картой МИР</li>
        <li>✓ Постоплата в офисе организации</li>
    </ul>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
