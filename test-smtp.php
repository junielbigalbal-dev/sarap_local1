<?php
/**
 * SMTP Configuration Test
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>SMTP Test</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";

echo "<h1>SMTP Configuration Test</h1>";

// Check environment variables
$smtp_host = getenv('SMTP_HOST');
$smtp_port = getenv('SMTP_PORT');
$smtp_user = getenv('SMTP_USER');
$smtp_pass = getenv('SMTP_PASS');

echo "<h2>Environment Variables:</h2><ul>";
echo "<li>SMTP_HOST: " . ($smtp_host ? "<span class='success'>✅ " . htmlspecialchars($smtp_host) . "</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "<li>SMTP_PORT: " . ($smtp_port ? "<span class='success'>✅ " . htmlspecialchars($smtp_port) . "</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "<li>SMTP_USER: " . ($smtp_user ? "<span class='success'>✅ " . htmlspecialchars($smtp_user) . "</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "<li>SMTP_PASS: " . ($smtp_pass ? "<span class='success'>✅ Set (hidden for security)</span>" : "<span class='error'>❌ Not set</span>") . "</li>";
echo "</ul>";

if ($smtp_host && $smtp_port && $smtp_user && $smtp_pass) {
    echo "<p class='success'>✅ All SMTP variables are configured!</p>";
    echo "<p class='info'>Email sending should work. Try signing up to test.</p>";
} else {
    echo "<p class='error'>❌ Some SMTP variables are missing!</p>";
    echo "<hr>";
    echo "<h3>How to fix:</h3>";
    echo "<ol>";
    echo "<li>Go to your Render Dashboard</li>";
    echo "<li>Select your Web Service</li>";
    echo "<li>Go to 'Environment' tab</li>";
    echo "<li>Add these variables:</li>";
    echo "<ul>";
    echo "<li>SMTP_HOST = smtp.gmail.com</li>";
    echo "<li>SMTP_PORT = 587</li>";
    echo "<li>SMTP_USER = your-email@gmail.com</li>";
    echo "<li>SMTP_PASS = your-app-password (16 chars from Google)</li>";
    echo "</ul>";
    echo "<li>Save and redeploy</li>";
    echo "</ol>";
}

echo "</body></html>";
