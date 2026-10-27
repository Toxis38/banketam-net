<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$loginVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $loginVal = $login;

    if (empty($login) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        // Check admin first
        if ($login === ADMIN_LOGIN && $password === ADMIN_PASSWORD) {
            $_SESSION['user_id'] = 0;
            $_SESSION['is_admin'] = true;
            $_SESSION['login'] = ADMIN_LOGIN;
            header('Location: /admin.php');
            exit;
        }

        // Regular user
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = false;
            $_SESSION['login'] = $login;
            header('Location: /cabinet.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}

$pageTitle = 'Вход';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Вход</h1>

<div class="form-card">
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="login">Логин</label>
            <input type="text" id="login" name="login" value="<?= htmlspecialchars($loginVal) ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Пароль</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn">Войти</button>
    </form>

    <div class="link-center">
        <a href="/register.php">Ещё не зарегистрированы? Регистрация</a>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
