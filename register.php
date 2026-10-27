<?php
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$success = false;
$old = ['login' => '', 'full_name' => '', 'phone' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $old = compact('login', 'full_name', 'phone', 'email');

    // Validation
    if (empty($login)) {
        $errors['login'] = 'Введите логин';
    } elseif (!preg_match('/^[a-zA-Z0-9]{6,}$/', $login)) {
        $errors['login'] = 'Логин: только латинские буквы и цифры, минимум 6 символов';
    }

    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Пароль должен быть не менее 8 символов';
    }

    if ($password !== $password2) {
        $errors['password2'] = 'Пароли не совпадают';
    }

    if (empty($full_name)) {
        $errors['full_name'] = 'Введите ФИО';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Введите телефон';
    } elseif (!preg_match('/^[\d\s\-\+\(\)]{10,}$/', $phone)) {
        $errors['phone'] = 'Некорректный номер телефона';
    }

    if (empty($email)) {
        $errors['email'] = 'Введите e-mail';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный e-mail';
    }

    // Check unique login
    if (empty($errors['login'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $errors['login'] = 'Такой логин уже занят';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password, full_name, phone, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$login, $hash, $full_name, $phone, $email]);
        $success = true;
    }
}

$pageTitle = 'Регистрация';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Регистрация</h1>

<?php if ($success): ?>
    <div class="alert alert-success">
        Регистрация успешна! Теперь вы можете <a href="/login.php">войти</a>.
    </div>
<?php else: ?>

<div class="form-card">
    <form method="POST" novalidate>
        <div class="form-group">
            <label for="login">Логин *</label>
            <input type="text" id="login" name="login" value="<?= htmlspecialchars($old['login']) ?>"
                   class="<?= isset($errors['login']) ? 'error-input' : '' ?>" required>
            <?php if (isset($errors['login'])): ?>
                <span class="error"><?= htmlspecialchars($errors['login']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password">Пароль *</label>
            <input type="password" id="password" name="password"
                   class="<?= isset($errors['password']) ? 'error-input' : '' ?>" required>
            <?php if (isset($errors['password'])): ?>
                <span class="error"><?= htmlspecialchars($errors['password']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="password2">Повторите пароль *</label>
            <input type="password" id="password2" name="password2"
                   class="<?= isset($errors['password2']) ? 'error-input' : '' ?>" required>
            <?php if (isset($errors['password2'])): ?>
                <span class="error"><?= htmlspecialchars($errors['password2']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="full_name">ФИО *</label>
            <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($old['full_name']) ?>"
                   class="<?= isset($errors['full_name']) ? 'error-input' : '' ?>" required>
            <?php if (isset($errors['full_name'])): ?>
                <span class="error"><?= htmlspecialchars($errors['full_name']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="phone">Телефон *</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($old['phone']) ?>"
                   class="<?= isset($errors['phone']) ? 'error-input' : '' ?>" placeholder="+7 (999) 123-45-67" required>
            <?php if (isset($errors['phone'])): ?>
                <span class="error"><?= htmlspecialchars($errors['phone']) ?></span>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">E-mail *</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>"
                   class="<?= isset($errors['email']) ? 'error-input' : '' ?>" required>
            <?php if (isset($errors['email'])): ?>
                <span class="error"><?= htmlspecialchars($errors['email']) ?></span>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn">Зарегистрироваться</button>
    </form>

    <div class="link-center">
        <a href="/login.php">Уже зарегистрированы? Войти</a>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
