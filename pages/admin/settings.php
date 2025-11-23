<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

// Create settings table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        `setting_key` varchar(50) NOT NULL,
        `setting_value` text,
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    
    // Insert default values if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    if ($stmt->fetchColumn() == 0) {
        $defaults = [
            'site_name' => 'Sarap Local',
            'site_email' => 'admin@saraplocal.com',
            'site_phone' => '+63 900 000 0000',
            'currency' => 'PHP',
            'commission_rate' => '10',
            'delivery_base_fee' => '49.00',
            'maintenance_mode' => '0'
        ];
        
        $insert = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaults as $key => $value) {
            $insert->execute([$key, $value]);
        }
    }
} catch (PDOException $e) {
    // Handle error
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        foreach ($_POST as $key => $value) {
            if ($key !== 'csrf_token') {
                $stmt->execute([$key, $value, $value]);
            }
        }
        
        $success = "Settings updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Get all settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM system_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Fallback defaults
}

// Helper to get setting
function getSetting($key, $default = '') {
    global $settings;
    return $settings[$key] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo SITE_NAME; ?></title>
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
                        <h1>Settings</h1>
                        <p class="header-subtitle">Manage system configurations</p>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                <?php if (isset($success)): ?>
                <div class="alert alert-success" style="margin-bottom: 24px;">
                    <?php echo $success; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 24px;">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
                        
                        <!-- General Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">General Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Site Name</label>
                                    <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars(getSetting('site_name', 'Sarap Local')); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Email</label>
                                    <input type="email" name="site_email" class="form-input" value="<?php echo htmlspecialchars(getSetting('site_email')); ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Contact Phone</label>
                                    <input type="text" name="site_phone" class="form-input" value="<?php echo htmlspecialchars(getSetting('site_phone')); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Business Settings -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Business Configuration</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Currency</label>
                                    <select name="currency" class="form-select">
                                        <option value="PHP" <?php echo getSetting('currency') === 'PHP' ? 'selected' : ''; ?>>Philippine Peso (PHP)</option>
                                        <option value="USD" <?php echo getSetting('currency') === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Default Commission Rate (%)</label>
                                    <input type="number" name="commission_rate" class="form-input" min="0" max="100" step="0.1" value="<?php echo htmlspecialchars(getSetting('commission_rate', '10')); ?>">
                                    <small style="color: var(--gray-600);">Percentage taken from vendor sales</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Base Delivery Fee</label>
                                    <input type="number" name="delivery_base_fee" class="form-input" min="0" step="0.01" value="<?php echo htmlspecialchars(getSetting('delivery_base_fee', '49.00')); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">System Status</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Maintenance Mode</label>
                                    <select name="maintenance_mode" class="form-select">
                                        <option value="0" <?php echo getSetting('maintenance_mode') === '0' ? 'selected' : ''; ?>>Live (Active)</option>
                                        <option value="1" <?php echo getSetting('maintenance_mode') === '1' ? 'selected' : ''; ?>>Maintenance (Offline)</option>
                                    </select>
                                    <small style="color: var(--gray-600);">Enable to prevent users from accessing the site</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 24px; text-align: right;">
                        <button type="submit" class="btn btn-primary btn-lg">Save Settings</button>
                    </div>
                </form>
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
