<?php
require_once 'config.php';

if (!isAuthenticated()) {
    header('Location: ' . SITE_URL . 'login.php');
    exit;
}

requireAuth();

$query = 'SELECT id, name, email, status, last_login FROM users ORDER BY created_at DESC';
$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$actionMessage = '';
$actionType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $selectedIds = $_POST['selected_ids'] ?? [];
    
    if (is_string($selectedIds)) {
        $selectedIds = [$selectedIds];
    }
    
    if (!empty($selectedIds)) {
        try {
            foreach ($selectedIds as $userId) {
                $userId = (int)$userId;
                
                if ($action === 'block') {
                    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')
                        ->execute(['blocked', $userId]);
                } elseif ($action === 'unblock') {
                    $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')
                        ->execute(['active', $userId]);
                } elseif ($action === 'delete') {
                    $pdo->prepare('DELETE FROM users WHERE id = ?')
                        ->execute([$userId]);
                } elseif ($action === 'delete_unverified') {
                    $pdo->prepare('DELETE FROM users WHERE id = ? AND status = ?')
                        ->execute([$userId, 'unverified']);
                }
            }
            
            $actionMessage = ucfirst($action) . ' action completed successfully';
            $actionType = 'success';
            
            $stmt = $pdo->query($query);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $actionMessage = 'Action failed: ' . $e->getMessage();
            $actionType = 'danger';
        }
    } else {
        $actionMessage = 'Please select at least one user';
        $actionType = 'warning';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Admin Panel</h1>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </div>

        <?php if (!empty($actionMessage)): ?>
            <div class="alert alert-<?php echo $actionType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($actionMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Registered Users (<?php echo count($users); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p class="text-muted">No users registered yet.</p>
                <?php else: ?>
                    <form method="POST">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="selectAll"></th>
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
                                            <td><input type="checkbox" name="selected_ids[]" value="<?php echo $user['id']; ?>" class="user-checkbox"></td>
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

                        <div class="mt-3">
                            <label for="action" class="form-label">Select Action:</label>
                            <div class="input-group mb-3">
                                <select name="action" id="action" class="form-select" required>
                                    <option value="">-- Choose Action --</option>
                                    <option value="block">Block Selected</option>
                                    <option value="unblock">Unblock Selected</option>
                                    <option value="delete">Delete Selected</option>
                                    <option value="delete_unverified">Delete Unverified</option>
                                </select>
                                <button type="submit" class="btn btn-primary">Execute</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
    </script>
</body>
</html>
