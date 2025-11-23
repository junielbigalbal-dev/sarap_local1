<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    redirect(SITE_URL . "/pages/$role/dashboard.php");
}

$error = '';
$success = getFlashMessage();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields';
        } elseif (!isValidEmail($email)) {
            $error = 'Invalid email address';
        } else {
            $userModel = new User($pdo);
            $user = $userModel->findByEmail($email);
            
            if ($user && verifyPassword($password, $user['password_hash'])) {
                if ($user['status'] === 'banned') {
                    $error = 'Your account has been banned';
                } elseif (!$user['email_verified']) {
                    $_SESSION['verify_email'] = $email;
                    $error = 'Email not verified. <a href="verify-email.php" style="color: var(--primary);">Verify now</a>';
                } else {
                    // Get user profile
                    $profile = $userModel->getUserWithProfile($user['id']);
                    loginUser($user['id'], $user['role'], $profile);
                    
                    // Redirect to appropriate dashboard
                    $redirectUrl = $_SESSION['redirect_after_login'] ?? SITE_URL . "/pages/{$user['role']}/dashboard.php";
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirectUrl);
                }
            } else {
                $error = 'Invalid email or password';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/auth-styles.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-logo">
            <img src="<?php echo SITE_URL; ?>/frontend/public/assets/logo.png" alt="<?php echo SITE_NAME; ?>">
            <h1>Welcome Back!</h1>
            <p>Login to continue your foodie journey</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success['text']; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Enter your password">
            </div>
            
            <div class="forgot-password">
                <a href="#">Forgot Password?</a>
            </div>
            
            <button type="submit" class="btn-primary">Login</button>
        </form>
        
        <div class="auth-links">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            <p style="margin-top: 10px;"><a href="<?php echo SITE_URL; ?>">← Back to Home</a></p>
        </div>
        

    </div>
</body>
</html>
