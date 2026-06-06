<?php

require_once('config.php');
require_once('includes/Swift/swift_required.php');

function sendHTMLemail(
    $femail = '',
    $fname  = '',
    $to,
    $subject,
    $message,
    $remail = null,
    $attachments = null
) {
    $transport = Swift_SmtpTransport::newInstance(SMTP_HOST, SMTP_PORT, 'ssl')
        ->setUsername(SMTP_USER)
        ->setPassword(SMTP_PASS);

    $mailer = Swift_Mailer::newInstance($transport);

    $msg = Swift_Message::newInstance($subject)
        ->setFrom(array($femail))
        ->setReplyTo($femail)
        ->setTo((array) $to)
        ->setDate(time())
        ->setBody($message, 'text/html')
        ->addPart(strip_tags(str_replace('<br />', "\r\n", $message)), 'text/plain');

    if (!empty($attachments)) {
        foreach ($attachments as $a) {
            $msg->attach(Swift_Attachment::fromPath($a));
        }
    }

    return (bool) $mailer->send($msg);
}

if (!empty($_POST['name']) && (!empty($_POST['phone']) || !empty($_POST['email']))) {

    header('Content-Type: text/html; charset=utf-8');

    $name    = htmlspecialchars(strip_tags($_POST['name']));
    $phone   = htmlspecialchars(strip_tags($_POST['phone'] ?? ''));
    $email   = htmlspecialchars(strip_tags($_POST['email'] ?? ''));
    $message = htmlspecialchars(strip_tags($_POST['message'] ?? ''));

    $body  = "New enquiry from loveads.ro<br /><br />";
    $body .= "Name: <b>{$name}</b><br />";
    if ($phone)   $body .= "Phone: <b>{$phone}</b><br />";
    if ($email)   $body .= "Email: <b>{$email}</b><br />";
    if ($message) $body .= "Message: <b>{$message}</b><br />";

    $replyFrom = $email ?: 'noreply@loveads.ro';

    sendHTMLemail(
        $replyFrom,
        $name,
        MAIL_TO,
        "Enquiry from {$name}",
        $body
    );
}

header('Location: https://www.loveads.ro');
exit;
