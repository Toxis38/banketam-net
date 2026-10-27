<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if (isAdmin()) {
    header('Location: /admin.php');
    exit;
}

$user = getCurrentUser($pdo);

// Handle review submission
$reviewError = '';
$reviewSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_text'])) {
    $appId = (int)($_POST['application_id'] ?? 0);
    $text = trim($_POST['review_text'] ?? '');

    if (empty($text)) {
        $reviewError = 'Введите текст отзыва';
    } else {
        // Check that application belongs to user and status allows review
        $stmt = $pdo->prepare("SELECT id, status FROM applications WHERE id = ? AND user_id = ?");
        $stmt->execute([$appId, $_SESSION['user_id']]);
        $app = $stmt->fetch();

        if (!$app) {
            $reviewError = 'Заявка не найдена';
        } elseif ($app['status'] === 'Новая') {
            $reviewError = 'Отзыв можно оставить только после изменения статуса администратором';
        } else {
            // Check if review already exists
            $stmt = $pdo->prepare("SELECT id FROM reviews WHERE application_id = ?");
            $stmt->execute([$appId]);
            if ($stmt->fetch()) {
                $reviewError = 'Отзыв уже оставлен';
            } else {
                $stmt = $pdo->prepare("INSERT INTO reviews (application_id, user_id, review_text) VALUES (?, ?, ?)");
                $stmt->execute([$appId, $_SESSION['user_id'], $text]);
                $reviewSuccess = true;
            }
        }
    }
}

// Get user applications with reviews
$stmt = $pdo->prepare("
    SELECT a.*, r.review_text, r.created_at as review_date
    FROM applications a
    LEFT JOIN reviews r ON r.application_id = a.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll();

$pageTitle = 'Личный кабинет';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Личный кабинет</h1>
<p>Добро пожаловать, <strong><?= htmlspecialchars($user['full_name']) ?></strong>!</p>

<div class="slider" style="margin-bottom: 24px;">
    <div class="slider-track">
        <div class="slide" style="background-image: url('/assets/images/image%20(1).jpeg')"></div>
        <div class="slide" style="background-image: url('/assets/images/image%20(2).jpeg')"></div>
        <div class="slide" style="background-image: url('/assets/images/image%20(3).jpeg')"></div>
        <div class="slide" style="background-image: url('/assets/images/image%20(4).jpeg')"></div>
    </div>
    <button class="slider-btn prev" aria-label="Назад">‹</button>
    <button class="slider-btn next" aria-label="Вперёд">›</button>
    <div class="slider-dots"></div>
</div>

<a href="/application.php" class="btn" style="margin-bottom: 24px;">+ Новая заявка</a>

<?php if ($reviewSuccess): ?>
    <div class="alert alert-success">Отзыв успешно добавлен!</div>
<?php endif; ?>
<?php if ($reviewError): ?>
    <div class="alert alert-error"><?= htmlspecialchars($reviewError) ?></div>
<?php endif; ?>

<h2>Мои заявки</h2>

<?php if (empty($applications)): ?>
    <div class="alert alert-info">У вас пока нет заявок. <a href="/application.php">Создать первую</a></div>
<?php else: ?>
    <?php foreach ($applications as $app): ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                <h3>Заявка #<?= $app['id'] ?></h3>
                <?php
                $statusClass = 'status-new';
                if ($app['status'] === 'Банкет назначен') $statusClass = 'status-assigned';
                if ($app['status'] === 'Банкет завершен') $statusClass = 'status-completed';
                ?>
                <span class="status <?= $statusClass ?>"><?= htmlspecialchars($app['status']) ?></span>
            </div>
            <p><strong>Помещение:</strong> <?= htmlspecialchars($app['room_type']) ?></p>
            <p><strong>Дата банкета:</strong> <?= htmlspecialchars($app['banquet_date']) ?></p>
            <p><strong>Оплата:</strong> <?= htmlspecialchars($app['payment_method']) ?></p>
            <p class="aux-text">Создана: <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></p>

            <?php if ($app['review_text']): ?>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--peach);">
                    <strong>Ваш отзыв:</strong>
                    <p><?= nl2br(htmlspecialchars($app['review_text'])) ?></p>
                </div>
            <?php elseif ($app['status'] !== 'Новая'): ?>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--peach);">
                    <form method="POST">
                        <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                        <div class="form-group">
                            <label>Оставить отзыв</label>
                            <textarea name="review_text" rows="3" required placeholder="Напишите ваш отзыв о услуге..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-secondary" style="width: auto; padding: 8px 16px;">Отправить отзыв</button>
                    </form>
                </div>
            <?php else: ?>
                <p class="aux-text" style="margin-top: 8px;">Отзыв можно оставить после изменения статуса заявки администратором.</p>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
