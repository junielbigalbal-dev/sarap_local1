<?php
/**
 * Mail Service
 * Wrapper for sending emails using Brevo API or PHPMailer
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class MailService {
    private $mailer;
    private $useSMTP;

    public function __construct() {
        // Check if PHPMailer is installed via Composer
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            $this->useSMTP = true;
            $this->mailer = new PHPMailer(true);
            $this->configureSMTP();
        } else {
            $this->useSMTP = false;
        }
    }

    private function configureSMTP() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host       = getenv('SMTP_HOST') ?: (defined('SMTP_HOST') ? SMTP_HOST : '');
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = getenv('SMTP_USER') ?: (defined('SMTP_USER') ? SMTP_USER : '');
            $this->mailer->Password   = getenv('SMTP_PASS') ?: (defined('SMTP_PASS') ? SMTP_PASS : '');
            
            // Get port and set encryption accordingly
            $port = getenv('SMTP_PORT') ?: (defined('SMTP_PORT') ? SMTP_PORT : 587);
            $this->mailer->Port = $port;
            
            // Use SSL for port 465, TLS for 587
            if ($port == 465) {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            
            // Timeout settings for Render
            $this->mailer->Timeout = 10;
            $this->mailer->SMTPDebug = 0;

            // Default sender
            $fromEmail = getenv('SMTP_USER') ?: (defined('SMTP_USER') ? SMTP_USER : '');
            $fromName = defined('SITE_NAME') ? SITE_NAME : 'Sarap Local';
            $this->mailer->setFrom($fromEmail, $fromName);
        } catch (Exception $e) {
            error_log("MailService Configuration Error: " . $e->getMessage());
            $this->useSMTP = false;
        }
    }

    public function send($to, $subject, $body, $isHtml = true) {
        // Try Brevo API first (works on Render)
        $brevoApiKey = getenv('BREVO_API_KEY');
        if ($brevoApiKey) {
            return $this->sendViaBrevoAPI($to, $subject, $body, $isHtml, $brevoApiKey);
        }
        
        // Fallback to SMTP
        if ($this->useSMTP) {
            try {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($to);
                $this->mailer->isHTML($isHtml);
                $this->mailer->Subject = $subject;
                $this->mailer->Body    = $body;
                
                if ($isHtml) {
                    $this->mailer->AltBody = strip_tags($body);
                }

                return $this->mailer->send();
            } catch (Exception $e) {
                error_log("SMTP Error: {$this->mailer->ErrorInfo}");
                return false;
            }
        } else {
            // Fallback to PHP mail()
            $headers = "From: " . SITE_NAME . " <" . SITE_EMAIL . ">\r\n";
            $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
            if ($isHtml) {
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            }
            return @mail($to, $subject, $body, $headers);
        }
    }
    
    /**
     * Send email via Brevo API (works on Render)
     */
    private function sendViaBrevoAPI($to, $subject, $body, $isHtml, $apiKey) {
        $fromEmail = getenv('SMTP_USER') ?: 'noreply@saraplocal.com';
        $fromName = defined('SITE_NAME') ? SITE_NAME : 'Sarap Local';
        
        $data = [
            'sender' => [
                'name' => $fromName,
                'email' => $fromEmail
            ],
            'to' => [
                ['email' => $to]
            ],
            'subject' => $subject,
            'htmlContent' => $isHtml ? $body : nl2br(htmlspecialchars($body))
        ];
        
        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'api-key: ' . $apiKey,
            'content-type: application/json'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("Brevo API Error: " . $response);
            return false;
        }
    }
}
