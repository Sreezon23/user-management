<?php
require 'config.php';
requireAuth();

// Check if user is blocked
if (isUserBlocked($pdo, $_SESSION['user_id'])) {
    session_destroy();
    header('Location: ' . SITE_URL . 'login.php?blocked=1');
    exit;
}

$sortBy = $_GET['sort'] ?? 'last_login';
$sortOrder = $_GET['order'] ?? 'DESC';

// Validate sort parameters
$allowedSorts = ['name', 'email', 'last_login', 'status', 'created_at'];
if (!in_array($sortBy, $allowedSorts)) $sortBy = 'last_login';
if (!in_array($sortOrder, ['ASC', 'DESC'])) $sortOrder = 'DESC';

// Fetch users
$query = "SELECT id, name, email, status, last_login, created_at FROM users 
          ORDER BY {$sortBy} {$sortOrder}";
$stmt = $pdo->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle actions
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
                    $pdo->prepare('UPDATE users SET status = "blocked" WHERE id = ?')
                        ->execute([$userId]);
                } elseif ($action === 'unblock') {
                    $pdo->prepare('UPDATE users SET status = "active" WHERE id = ?')
                        ->execute([$userId]);
                } elseif ($action === 'delete') {
                    $pdo->prepare('DELETE FROM users WHERE id = ?')
                        ->execute([$userId]);
                } elseif ($action === 'delete_unverified') {
                    $pdo->prepare('DELETE FROM users WHERE id = ? AND status = "unverified"')
                        ->execute([$userId]);
                }
            }
            $actionMessage = ucfirst($action) . ' action completed successfully';
            $actionType = 'success';

            // Refresh users list
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .toolbar {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .table-container {
            background: white;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-unverified {
            background: #fff3cd;
            color: #856404;
        }
        .status-blocked {
            background: #f8d7da;
            color: #721c24;
        }
        .btn-toolbar {
            margin-right: 5px;
        }
        .sortable {
            cursor: pointer;
        }
        .sortable:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <span class="navbar-brand">The App - Admin Panel</span>
            <div class="ms-auto">
                <span class="me-3">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <?php if (!empty($actionMessage)): ?>
            <div class="alert alert-<?php echo $actionType; ?> alert-dismissible fade show">
                <?php echo htmlspecialchars($actionMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Toolbar -->
        <div class="toolbar">
            <form method="POST" id="actionForm" class="d-flex gap-2 align-items-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="submitAction('block')">
                    <i class="fas fa-ban"></i> Block
                </button>
                <button type="button" class="btn btn-success btn-sm" onclick="submitAction('unblock')">
                    <i class="fas fa-check"></i> Unblock
                </button>
                <button type="button" class="btn btn-info btn-sm" onclick="submitAction('delete')">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <button type="button" class="btn btn-warning btn-sm" onclick="submitAction('delete_unverified')">
                    <i class="fas fa-times"></i> Delete Unverified
                </button>
                <input type="hidden" name="action" id="actionInput">

                <div class="ms-auto">
                    <input type="text" class="form-control" placeholder="Filter by name or email" id="filterInput">
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th class="sortable" onclick="sort('name')">
                                Name <i class="fas fa-sort"></i>
                            </th>
                            <th class="sortable" onclick="sort('email')">
                                Email <i class="fas fa-sort"></i>
                            </th>
                            <th class="sortable" onclick="sort('status')">
                                Status <i class="fas fa-sort"></i>
                            </th>
                            <th class="sortable" onclick="sort('last_login')">
                                Last Login <i class="fas fa-sort"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>" 
                                           onchange="updateSelectAll()">
                                </td>
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const actionForm = document.getElementById('actionForm');
        const actionInput = document.getElementById('actionInput');
        const selectAllCheckbox = document.getElementById('selectAll');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');

        function getSelectedIds() {
            return Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
        }

        function submitAction(action) {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) {
                alert('Please select at least one user');
                return;
            }

            if (confirm('Are you sure?')) {
                actionInput.value = action;
                
                // Add hidden inputs for selected IDs
                selectedIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_ids[]';
                    input.value = id;
                    actionForm.appendChild(input);
                });

                actionForm.submit();
            }
        }

        function toggleSelectAll() {
            userCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
        }

        function updateSelectAll() {
            const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
            selectAllCheckbox.checked = checkedCount === userCheckboxes.length;
        }

        function sort(field) {
            const currentOrder = new URLSearchParams(window.location.search).get('order') || 'DESC';
            const newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
            window.location.href = `admin.php?sort=${field}&order=${newOrder}`;
        }

        // Filter table
        document.getElementById('filterInput').addEventListener('keyup', function(e) {
            const filter = e.target.value.toLowerCase();
            document.querySelectorAll('#usersTableBody tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>
</html>
