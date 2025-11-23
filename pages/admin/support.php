<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Get tickets
$tickets = [];
$sql = "
    SELECT st.*, u.email as user_email, 
           COALESCE(up.name, u.email) as user_name,
           a.email as admin_email
    FROM support_tickets st
    LEFT JOIN users u ON st.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN users a ON st.assigned_to = a.id
    WHERE 1=1
";

if ($statusFilter !== 'all') {
    $sql .= " AND st.status = :status";
}

$sql .= " ORDER BY st.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    if ($statusFilter !== 'all') {
        $stmt->bindValue(':status', $statusFilter);
    }
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet
}

// Get counts
$statusCounts = [];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM support_tickets GROUP BY status");
    while ($row = $stmt->fetch()) {
        $statusCounts[$row['status']] = $row['count'];
    }
} catch (PDOException $e) {
    // Table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin-layout.css">
</head>
<body>
    <div class="admin-dashboard">
        <?php include __DIR__ . '/../../includes/admin-sidebar.php'; ?>

        <div class="admin-main">
            <header class="admin-header">
                <div class="header-left">
                    <button id="mobileMenuBtn" class="btn btn-icon btn-secondary" style="display: none;">☰</button>
                    <div class="header-title">
                        <h1>Support Tickets</h1>
                        <p class="header-subtitle">Manage customer support requests</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <!-- Stats -->
                <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['open'] ?? 0; ?></div>
                            <div class="stat-label">Open</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['in_progress'] ?? 0; ?></div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['resolved'] ?? 0; ?></div>
                            <div class="stat-label">Resolved</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-body">
                            <div class="stat-value"><?php echo $statusCounts['closed'] ?? 0; ?></div>
                            <div class="stat-label">Closed</div>
                        </div>
                    </div>
                </div>

                <!-- Tickets Table -->
                <div class="table-container">
                    <div class="table-header">
                        <h3 class="card-title">All Tickets</h3>
                        <div class="table-filters">
                            <form method="GET" style="display: flex; gap: 12px;">
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="open" <?php echo $statusFilter === 'open' ? 'selected' : ''; ?>>Open</option>
                                    <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="resolved" <?php echo $statusFilter === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="closed" <?php echo $statusFilter === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                </select>
                            </form>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Subject</th>
                                    <th>Customer</th>
                                    <th>Category</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ticket['ticket_number']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ticket['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['user_name'] ?? $ticket['user_email'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?php echo ucfirst($ticket['category']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $priorityColors = [
                                            'low' => 'gray',
                                            'medium' => 'info',
                                            'high' => 'warning',
                                            'urgent' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge badge-<?php echo $priorityColors[$ticket['priority']]; ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'open' => 'warning',
                                            'in_progress' => 'info',
                                            'resolved' => 'success',
                                            'closed' => 'gray'
                                        ];
                                        ?>
                                        <span class="badge badge-<?php echo $statusColors[$ticket['status']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($ticket['admin_email'] ?? 'Unassigned'); ?></td>
                                    <td><?php echo formatDateTime($ticket['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="<?php echo SITE_URL; ?>/assets/js/admin-components.js"></script>
    <script>
        if (window.innerWidth <= 1024) {
            document.getElementById('mobileMenuBtn').style.display = 'flex';
        }
    </script>
</body>
</html>
