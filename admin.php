<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();

$message = '';
$messageType = '';

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['app_id'], $_POST['status'])) {
    $appId = (int)$_POST['app_id'];
    $status = $_POST['status'];
    $allowed = ['Новая', 'Банкет назначен', 'Банкет завершен'];

    if (in_array($status, $allowed)) {
        $stmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $appId]);
        $message = 'Статус заявки #' . $appId . ' изменён на «' . $status . '»';
        $messageType = 'success';
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 5;
$offset = ($page - 1) * $perPage;

$allowedSort = ['id', 'created_at', 'banquet_date', 'status', 'room_type'];
if (!in_array($sort, $allowedSort)) $sort = 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

$where = '';
$params = [];
if ($statusFilter && in_array($statusFilter, ['Новая', 'Банкет назначен', 'Банкет завершен'])) {
    $where = 'WHERE a.status = ?';
    $params[] = $statusFilter;
}

// Count total
$countSql = "SELECT COUNT(*) FROM applications a $where";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

// Fetch
$sql = "
    SELECT a.*, u.full_name, u.login, u.phone, u.email
    FROM applications a
    JOIN users u ON u.id = a.user_id
    $where
    ORDER BY a.$sort $order
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$pageTitle = 'Панель администратора';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Панель администратора</h1>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="filters">
    <form method="GET" style="display: flex; flex-wrap: wrap; gap: 8px; width: 100%;">
        <select name="status" onchange="this.form.submit()">
            <option value="">Все статусы</option>
            <option value="Новая" <?= $statusFilter === 'Новая' ? 'selected' : '' ?>>Новая</option>
            <option value="Банкет назначен" <?= $statusFilter === 'Банкет назначен' ? 'selected' : '' ?>>Банкет назначен</option>
            <option value="Банкет завершен" <?= $statusFilter === 'Банкет завершен' ? 'selected' : '' ?>>Банкет завершен</option>
        </select>
        <select name="sort" onchange="this.form.submit()">
            <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>По дате создания</option>
            <option value="banquet_date" <?= $sort === 'banquet_date' ? 'selected' : '' ?>>По дате банкета</option>
            <option value="status" <?= $sort === 'status' ? 'selected' : '' ?>>По статусу</option>
            <option value="id" <?= $sort === 'id' ? 'selected' : '' ?>>По ID</option>
        </select>
        <select name="order" onchange="this.form.submit()">
            <option value="DESC" <?= $order === 'DESC' ? 'selected' : '' ?>>Сначала новые</option>
            <option value="ASC" <?= $order === 'ASC' ? 'selected' : '' ?>>Сначала старые</option>
        </select>
    </form>
</div>

<p class="aux-text">Всего заявок: <?= $total ?></p>

<?php if (empty($applications)): ?>
    <div class="alert alert-info">Заявок не найдено</div>
<?php else: ?>
    <?php foreach ($applications as $app): ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <h3>Заявка #<?= $app['id'] ?></h3>
                <?php
                $statusClass = 'status-new';
                if ($app['status'] === 'Банкет назначен') $statusClass = 'status-assigned';
                if ($app['status'] === 'Банкет завершен') $statusClass = 'status-completed';
                ?>
                <span class="status <?= $statusClass ?>"><?= htmlspecialchars($app['status']) ?></span>
            </div>
            <p><strong>Клиент:</strong> <?= htmlspecialchars($app['full_name']) ?> (<?= htmlspecialchars($app['login']) ?>)</p>
            <p><strong>Телефон:</strong> <?= htmlspecialchars($app['phone']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($app['email']) ?></p>
            <p><strong>Помещение:</strong> <?= htmlspecialchars($app['room_type']) ?></p>
            <p><strong>Дата банкета:</strong> <?= htmlspecialchars($app['banquet_date']) ?></p>
            <p><strong>Оплата:</strong> <?= htmlspecialchars($app['payment_method']) ?></p>
            <p class="aux-text">Создана: <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></p>

            <form method="POST" style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                <select name="status" style="flex: 1; min-width: 140px; padding: 8px; border-radius: 8px; border: 2px solid var(--peach); font-family: Oswald, sans-serif;">
                    <option value="Новая" <?= $app['status'] === 'Новая' ? 'selected' : '' ?>>Новая</option>
                    <option value="Банкет назначен" <?= $app['status'] === 'Банкет назначен' ? 'selected' : '' ?>>Банкет назначен</option>
                    <option value="Банкет завершен" <?= $app['status'] === 'Банкет завершен' ? 'selected' : '' ?>>Банкет завершен</option>
                </select>
                <button type="submit" class="btn btn-secondary" style="width: auto; padding: 8px 16px;">Изменить статус</button>
            </form>
        </div>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&status=<?= urlencode($statusFilter) ?>&sort=<?= $sort ?>&order=<?= $order ?>"
                   class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
