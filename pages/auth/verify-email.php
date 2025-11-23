<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
$pdo = require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/MailService.php';
require_once __DIR__ . '/../../models/User.php';

// Check if we have an email to verify
if (!isset($_SESSION['verify_email'])) {
    redirect('login.php');
}

$email = $_SESSION['verify_email'];
$error = '';
$success = '';

$userModel = new User($pdo);
$user = $userModel->findByEmail($email);

if (!$user) {
    unset($_SESSION['verify_email']);
    redirect('signup.php');
}

// Handle OTP Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request';
        } else {
            $otp = implode('', $_POST['otp'] ?? []);
            
            if (strlen($otp) !== 6 || !ctype_digit($otp)) {
                $error = 'Please enter a valid 6-digit code';
            } else {
                // Verify OTP
                $stmt = $pdo->prepare("
                    SELECT * FROM email_verifications 
                    WHERE user_id = ? AND token = ? AND expires_at > NOW()
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmt->execute([$user['id'], $otp]);
                $verification = $stmt->fetch();
                
                if ($verification) {
                    // Mark email as verified
                    $userModel->verifyEmail($user['id']);
                    
                    // Clean up verifications
                    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Login the user
                    $profile = $userModel->getProfile($user['id']);
                    loginUser($user['id'], $user['role'], $profile);
                    
                    unset($_SESSION['verify_email']);
                    
                    // Redirect to dashboard
                    redirect(SITE_URL . "/pages/{$user['role']}/dashboard.php");
                } else {
                    $error = 'Invalid or expired verification code';
                }
            }
        }
    } elseif (isset($_POST['resend_code'])) {
        // Resend Logic
        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid request';
        } else {
            // Check cooldown (1 minute)
            $stmt = $pdo->prepare("
                SELECT created_at FROM email_verifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$user['id']]);
            $lastSent = $stmt->fetch();
            
            if ($lastSent && (time() - strtotime($lastSent['created_at'])) < 60) {
                $error = 'Please wait a minute before resending';
            } else {
                // Generate new OTP
                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO email_verifications (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user['id'], $otp, $expiresAt]);
                
                // Send email
                $mailService = new MailService();
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
                        <div style='text-align: center; margin-bottom: 20px;'>
                            <h2 style='color: #D70F64;'>" . SITE_NAME . "</h2>
                        </div>
                        <p>Hi there,</p>
                        <p>Here is your new verification code:</p>
                        <div style='background-color: #f9f9f9; padding: 15px; text-align: center; border-radius: 4px; margin: 20px 0;'>
                            <span style='font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #333;'>$otp</span>
                        </div>
                        <p>This code will expire in 15 minutes.</p>
                    </div>
                ";
                
                if ($mailService->send($email, 'New Verification Code - ' . SITE_NAME, $emailBody, true)) {
                    $success = 'New code sent! Please check your email.';
                } else {
                    $error = 'Failed to send verification email.';
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
    <title>Verify Email - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/auth-styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .otp-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        .otp-input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            transition: all 0.2s;
        }
        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(215, 15, 100, 0.1);
        }
        .resend-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        .resend-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            font-family: inherit;
        }
        .resend-btn:disabled {
            color: var(--gray-400);
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-logo">
            <img src="<?php echo SITE_URL; ?>/frontend/public/assets/logo.png" alt="<?php echo SITE_NAME; ?>">
            <h1>Verify Email</h1>
            <p>We sent a code to <strong><?php echo htmlspecialchars($email); ?></strong></p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" class="auth-form" id="otpForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="verify_otp" value="1">
            
            <div class="otp-container">
                <?php for($i=0; $i<6; $i++): ?>
                <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="[0-9]" required 
                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(this.value.length === 1) { var next = this.nextElementSibling; if(next) next.focus(); }"
                       onkeydown="if(event.key === 'Backspace' && this.value.length === 0) { var prev = this.previousElementSibling; if(prev) prev.focus(); }">
                <?php endfor; ?>
            </div>
            
            <button type="submit" class="btn-primary">Verify Account</button>
        </form>
        
        <form method="POST" action="" id="resendForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="resend_code" value="1">
            <div class="resend-link">
                Didn't receive the code? 
                <button type="submit" class="resend-btn" id="resendBtn">Resend Code</button>
            </div>
        </form>
        
        <div class="auth-links">
            <p><a href="logout.php">Use a different email</a></p>
        </div>
    </div>
</body>
</html>
