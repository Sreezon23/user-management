<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requireAuth();

$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$stmt = $pdo->prepare('SELECT id, name, email, status FROM users WHERE id = ?');
$stmt->execute([$currentUserId]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

if ($currentUser['status'] === 'blocked') {
    session_destroy();
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $selectedIds = $_POST['selected_ids'] ?? [];

    if (is_string($selectedIds)) {
        $selectedIds = [$selectedIds];
    }

    $selectedIds = array_map('intval', $selectedIds);
    $selectedIds = array_values($selectedIds);
    $affectsCurrentUser = in_array($currentUserId, $selectedIds, true);

    if ($action === '' || empty($selectedIds)) {
        $msg = 'Please select at least one user and an action';
        $type = 'warning';
    } else {
        try {
            $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));

            if ($action === 'block') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
            } elseif ($action === 'unblock') {
                $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                $stmt->execute($selectedIds);
            } elseif ($action === 'delete_unverified') {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders) AND is_verified = 0");
                $stmt->execute($selectedIds);
            }

            if ($affectsCurrentUser && in_array($action, ['block', 'delete', 'delete_unverified'], true)) {
                session_destroy();
                header('Location: ' . SITE_URL . 'login.php');
                exit;
            }

            $msg = ucfirst(str_replace('_', ' ', $action)) . ' action completed successfully';
            $type = 'success';
        } catch (Exception $e) {
            $msg = 'Action failed: ' . $e->getMessage();
            $type = 'danger';
        }
    }

    $params = http_build_query([
        'msg' => $msg,
        'type' => $type,
    ]);

    header('Location: ' . SITE_URL . 'admin.php?' . $params);
    exit;
}

$query = 'SELECT id, name, email, status, last_login FROM users ORDER BY created_at DESC';
$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$actionMessage = $_GET['msg'] ?? '';
$actionType = $_GET['type'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Admin Panel</h1>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <?php if (!empty($actionMessage)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($actionType); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($actionMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Registered Users (<?php echo count($users); ?>)</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="userForm">
                    <input type="hidden" name="action" id="actionField">

                    <div class="d-flex align-items-center mb-3">
                        <div class="btn-toolbar" role="toolbar" aria-label="User actions">
                            <div class="btn-group me-2" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-action="block">
                                    Block
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-action="unblock">
                                    Unblock
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete">
                                    Delete
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete_unverified">
                                    Delete unverified
                                </button>
                            </div>
                        </div>

                        <div class="ms-auto" style="max-width: 250px;">
                            <input type="text" id="filterInput" class="form-control form-control-sm" placeholder="Filter users">
                        </div>
                    </div>

                    <?php if (empty($users)): ?>
                        <p class="text-muted mt-3">No users registered yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="usersTable">
                                <thead>
                                    <tr>
                                        <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    name="selected_ids[]"
                                                    value="<?php echo (int)$user['id']; ?>"
                                                    class="user-checkbox"
                                                >
                                            </td>
                                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($user['status'] === 'active') ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($user['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['last_login'] ? htmlspecialchars($user['last_login']) : 'Never'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.user-checkbox');
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                });
            });
        }

        document.querySelectorAll('[data-action]').forEach(function(btn) {
            btn.addEventListener('click', function () {
                const action = this.getAttribute('data-action');
                const checked = document.querySelectorAll('.user-checkbox:checked');
                if (checked.length === 0) {
                    alert('Please select at least one user.');
                    return;
                }
                document.getElementById('actionField').value = action;
                document.getElementById('userForm').submit();
            });
        });

        const filterInput = document.getElementById('filterInput');
        if (filterInput) {
            filterInput.addEventListener('keyup', function () {
                const term = this.value.toLowerCase();
                const rows = document.querySelectorAll('#usersTable tbody tr');
                rows.forEach(function (row) {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.indexOf(term) !== -1 ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
