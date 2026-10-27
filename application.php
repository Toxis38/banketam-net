<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();
if (isAdmin()) {
    header('Location: /admin.php');
    exit;
}

$errors = [];
$success = false;
$old = ['room_type' => '', 'banquet_date' => '', 'payment_method' => ''];

$rooms = ['зал', 'ресторан', 'летняя веранда', 'закрытая веранда'];
$payments = [
    'Предоплата по QR-коду',
    'Оплата картой МИР',
    'Постоплата в офисе организации'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_type = $_POST['room_type'] ?? '';
    $banquet_date = trim($_POST['banquet_date'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';

    $old = compact('room_type', 'banquet_date', 'payment_method');

    if (empty($room_type) || !in_array($room_type, $rooms)) {
        $errors['room_type'] = 'Выберите помещение';
    }

    if (empty($banquet_date)) {
        $errors['banquet_date'] = 'Укажите дату';
    } elseif (!preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $banquet_date)) {
        $errors['banquet_date'] = 'Дата в формате ДД.ММ.ГГГГ';
    } else {
        $parts = explode('.', $banquet_date);
        if (!checkdate((int)$parts[1], (int)$parts[0], (int)$parts[2])) {
            $errors['banquet_date'] = 'Некорректная дата';
        }
    }

    if (empty($payment_method) || !in_array($payment_method, $payments)) {
        $errors['payment_method'] = 'Выберите способ оплаты';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO applications (user_id, room_type, banquet_date, payment_method, status) VALUES (?, ?, ?, ?, 'Новая')");
        $stmt->execute([$_SESSION['user_id'], $room_type, $banquet_date, $payment_method]);
        $success = true;
        $old = ['room_type' => '', 'banquet_date' => '', 'payment_method' => ''];
    }
}

$pageTitle = 'Новая заявка';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Оформление заявки</h1>

<?php if ($success): ?>
    <div class="alert alert-success">
        Заявка успешно создана и отправлена администратору на согласование!
        <br><a href="/cabinet.php">Перейти в личный кабинет</a>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST">
        <div class="form-group">
            <label for="room_type">Помещение *</label>
            <select id="room_type" name="room_type" class="<?= isset($errors['room_type']) ? 'error-input' : '' ?>" required>
                <option value="">— Выберите —</option>
                <?php foreach ($rooms as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>" <?= $old['room_type'] === $r ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['room_type'])): ?>
                <span class="error"><?= htmlspecialchars($errors['room_type']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="banquet_date">Дата начала банкета (ДД.ММ.ГГГГ) *</label>
            <input type="text" id="banquet_date" name="banquet_date" 
                   value="<?= htmlspecialchars($old['banquet_date']) ?>"
                   placeholder="15.06.2026"
                   class="<?= isset($errors['banquet_date']) ? 'error-input' : '' ?>"
                   maxlength="10" required>
            <?php if (isset($errors['banquet_date'])): ?>
                <span class="error"><?= htmlspecialchars($errors['banquet_date']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="payment_method">Способ оплаты *</label>
            <select id="payment_method" name="payment_method" class="<?= isset($errors['payment_method']) ? 'error-input' : '' ?>" required>
                <option value="">— Выберите —</option>
                <?php foreach ($payments as $p): ?>
                    <option value="<?= htmlspecialchars($p) ?>" <?= $old['payment_method'] === $p ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['payment_method'])): ?>
                <span class="error"><?= htmlspecialchars($errors['payment_method']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn">Отправить заявку</button>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
