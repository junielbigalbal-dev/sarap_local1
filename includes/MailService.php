<?php
/**
 * Mail Service
 * Wrapper for sending emails using PHPMailer or PHP mail() fallback
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
            $this->mailer->SMTPDebug = 0; // Disable debug output

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
        if ($this->useSMTP) {
            try {
                $this->mailer->clearAddresses();
                $this->mailer->addAddress($to);
                $this->mailer->isHTML($isHtml);
                $this->mailer->Subject = $subject;
                $this->mailer->Body    = $body;
                
                // Plain text alternative
                if ($isHtml) {
                    $this->mailer->AltBody = strip_tags($body);
                }

                return $this->mailer->send();
            } catch (Exception $e) {
                error_log("Message could not be sent. Mailer Error: {$this->mailer->ErrorInfo}");
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
}
