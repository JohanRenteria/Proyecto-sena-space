<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/libs/vendor/autoload.php';

function sendEmail(string $to, ?string $toName, string $subject, string $body): bool
{
    $username = getenv('MAIL_USERNAME');
    $password = getenv('MAIL_PASSWORD');

    if (!is_string($username) || $username === '' || !is_string($password) || $password === '') {
        error_log('SMTP is not configured: MAIL_USERNAME and MAIL_PASSWORD are required.');
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'logpsin@gmail.com';
        $mail->Password   = 'zghrjtqkvuxvnamc';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->Timeout = 15;
        $mail->CharSet = PHPMailer::CHARSET_UTF8;

        $mail->setFrom($username, 'LOG-IN');
        $mail->addAddress($to, $toName ?: $to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = trim(strip_tags($body));

        return $mail->send();
    } catch (Exception $exception) {
        error_log('Mailer Error: ' . $exception->getMessage());
        return false;
    }
}