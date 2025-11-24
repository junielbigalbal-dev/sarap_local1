<?php
/**
 * Email Test Page
 * Test SMTP configuration by sending a test email
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/MailService.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Email Test</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>Email Test</h1>";

// Check environment variables
$smtp_host = getenv('SMTP_HOST');
$smtp_port = getenv('SMTP_PORT');
$smtp_user = getenv('SMTP_USER');
$smtp_pass = getenv('SMTP_PASS');
$brevo_api = getenv('BREVO_API_KEY');

echo "<h2>SMTP Configuration:</h2><ul>";
echo "<li>SMTP_HOST: " . ($smtp_host ? "<span class='success'>✅ " . htmlspecialchars($smtp_host) . "</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "<li>SMTP_PORT: " . ($smtp_port ? "<span class='success'>✅ " . htmlspecialchars($smtp_port) . "</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "<li>SMTP_USER: " . ($smtp_user ? "<span class='success'>✅ " . htmlspecialchars($smtp_user) . "</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "<li>SMTP_PASS: " . ($smtp_pass ? "<span class='success'>✅ Set (length: " . strlen($smtp_pass) . " chars)</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "<li><strong>BREVO_API_KEY: " . ($brevo_api ? "<span class='success'>✅ Set (length: " . strlen($brevo_api) . " chars)</span>" : "<span class='error'>❌ NOT SET - ADD THIS!</span>") . "</strong></li>";
echo "</ul>";

if (isset($_POST['send_test'])) {
    $testEmail = $_POST['test_email'] ?? '';
    
    if (empty($testEmail)) {
        echo "<p class='error'>❌ Please enter an email address</p>";
    } else {
        echo "<hr><h2>Sending Test Email...</h2>";
        
        $mailService = new MailService();
        $subject = "Test Email from Sarap Local";
        $body = "
            <h2>Test Email</h2>
            <p>If you received this email, your SMTP configuration is working correctly!</p>
            <p>Sent at: " . date('Y-m-d H:i:s') . "</p>
        ";
        
        $result = $mailService->send($testEmail, $subject, $body, true);
        
        if ($result) {
            echo "<p class='success'>✅ Email sent successfully to " . htmlspecialchars($testEmail) . "!</p>";
            echo "<p class='info'>Check your inbox (and spam folder)</p>";
        } else {
            echo "<p class='error'>❌ Failed to send email</p>";
            echo "<p class='error'>Check error logs for details</p>";
        }
    }
}

echo "<hr>";
echo "<h2>Send Test Email:</h2>";
echo "<form method='POST'>";
echo "<input type='email' name='test_email' placeholder='your-email@example.com' required style='padding:10px;width:300px;'>";
echo "<button type='submit' name='send_test' style='padding:10px 20px;background:#C67D3B;color:white;border:none;cursor:pointer;'>Send Test Email</button>";
echo "</form>";

echo "</body></html>";
