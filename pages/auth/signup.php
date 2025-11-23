<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/MailService.php';
require_once __DIR__ . '/../../models/User.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    redirect(SITE_URL . "/pages/$role/dashboard.php");
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        // Verify ReCAPTCHA
        $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
        $recaptchaSecret = RECAPTCHA_SECRET_KEY;
        
        if (!empty($recaptchaSecret)) {
            $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $recaptchaSecret . '&response=' . $recaptchaResponse);
            $responseData = json_decode($verifyResponse);
            
            if (!$responseData->success) {
                $error = 'Please complete the captcha verification';
            }
        }

        if (empty($error)) {
            $name = sanitize($_POST['name'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $role = sanitize($_POST['role'] ?? 'customer');
            
            // Validation
            if (empty($name) || empty($email) || empty($password)) {
                $error = 'Please fill in all fields';
            } elseif (!isValidEmail($email)) {
                $error = 'Invalid email address';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match';
            } elseif (!in_array($role, ['customer', 'vendor'])) {
                $error = 'Invalid role selected';
            } else {
                $userModel = new User($pdo);
                
                // Check if email already exists
                if ($userModel->findByEmail($email)) {
                    $error = 'Email already registered';
                } else {
                    try {
                        // Create user
                        $userId = $userModel->create($email, $password, $role);
                        
                        // Create profile
                        $userModel->createProfile($userId, ['name' => $name]);
                        
                        // Generate 6-digit OTP
                        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO email_verifications (user_id, token, expires_at)
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$userId, $otp, $expiresAt]);
                        
                        // Send verification email
                        $mailService = new MailService();
                        $emailBody = "
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                                <div style='text-align: center; margin-bottom: 20px;'>
                                    <h2 style='color: #D70F64;'>" . SITE_NAME . "</h2>
                                </div>
                                <p>Hi $name,</p>
                                <p>Thank you for joining us! To complete your registration, please use the verification code below:</p>
                                <div style='background-color: #f9f9f9; padding: 15px; text-align: center; border-radius: 4px; margin: 20px 0;'>
                                    <span style='font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #333;'>$otp</span>
                                </div>
                                <p>This code will expire in 15 minutes.</p>
                                <p>If you didn't create an account, you can safely ignore this email.</p>
                            </div>
                        ";
                        
                        if ($mailService->send($email, 'Verify Your Email - ' . SITE_NAME, $emailBody, true)) {
                            // Store email in session for verification page
                            $_SESSION['verify_email'] = $email;
                            redirect('verify-email.php');
                        } else {
                            $error = 'Failed to send verification email. Please try again.';
                        }
                        
                    } catch (Exception $e) {
                        $error = 'Registration failed. Please try again.';
                        error_log($e->getMessage());
                    }
                }
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
    <title>Sign Up - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/auth-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-logo">
            <img src="<?php echo SITE_URL; ?>/frontend/public/assets/logo.png" alt="<?php echo SITE_NAME; ?>">
            <h1>Create Account</h1>
            <p>Join our foodie community today!</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       placeholder="Juan Dela Cruz">
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label for="role">I am a:</label>
                <select id="role" name="role" required>
                    <option value="customer" <?php echo ($_POST['role'] ?? '') === 'customer' ? 'selected' : ''; ?>>
                        Customer (I want to order food)
                    </option>
                    <option value="vendor" <?php echo ($_POST['role'] ?? '') === 'vendor' ? 'selected' : ''; ?>>
                        Vendor (I want to sell food)
                    </option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       placeholder="At least 6 characters">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Re-enter password">
            </div>

            <?php if (defined('RECAPTCHA_SITE_KEY') && !empty(RECAPTCHA_SITE_KEY)): ?>
            <div class="form-group" style="display: flex; justify-content: center;">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"></div>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-primary">Create Account</button>
        </form>
        
        <div class="auth-links">
            <p>Already have an account? <a href="login.php">Login here</a></p>
            <p style="margin-top: 10px;"><a href="<?php echo SITE_URL; ?>">← Back to Home</a></p>
        </div>
    </div>
</body>
</html>
