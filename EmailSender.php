<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;

    public function __construct() {
        $this->mail = new PHPMailer(true);

        try {
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = GMAIL_USER;
            $this->mail->Password = GMAIL_PASS;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = 587;
        } catch (Exception $e) {
            throw new Exception('Email configuration error: ' . $e->getMessage());
        }
    }

    public function sendVerificationEmail($email, $token) {
        try {
            $this->mail->setFrom(GMAIL_USER, 'The App');
            $this->mail->clearAddresses();
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Verify Your Email - The App';

            $verificationLink = SITE_URL . 'verify.php?token=' . $token;

            $this->mail->Body = "
                <h2>Email Verification</h2>
                <p>Click the link below to verify your email:</p>
                <a href='{$verificationLink}'>Verify Email</a>
            ";

            return $this->mail->send();
        } catch (Exception $e) {
            throw new Exception('Email send error: ' . $e->getMessage());
        }
    }
}
