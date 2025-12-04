<?php

class EmailSender
{
    private string $apiKey;
    private string $from;

    public function __construct()
    {
        $this->apiKey = getenv('RESEND_API_KEY') ?: '';
        $this->from = getenv('EMAIL_FROM') ?: 'onboarding@resend.dev';

        if ($this->apiKey === '') {
            error_log('EmailSender: RESEND_API_KEY is missing or empty');
            throw new Exception("Resend API key missing.");
        }
    }

    public function sendVerificationEmail(string $email, string $token): bool
    {
        $verificationLink = SITE_URL . "verify.php?token=" . urlencode($token);

        $payload = [
            "from" => $this->from,
            "to" => [$email],
            "subject" => "Verify Your Email",
            "html" => "
                <h2>Email Verification</h2>
                <p>Please click the link below:</p>
                <a href='$verificationLink'>Verify Email</a>
            "
        ];

        $json = json_encode($payload);

        if ($json === false) {
            error_log('EmailSender: json_encode failed: ' . json_last_error_msg());
            throw new Exception("Failed to encode email payload.");
        }

        $ch = curl_init("https://api.resend.com/emails");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $this->apiKey,
                "Content-Type: application/json"
            ],
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("Resend cURL error: " . $error);
            throw new Exception("Email sending failed (network).");
        }

        if ($httpCode < 200 || $httpCode > 299) {
            error_log("Resend API error ($httpCode): $response");
            throw new Exception("Resend API error.");
        }

        return true;
    }
}
