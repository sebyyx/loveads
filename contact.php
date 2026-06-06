<?php

$mail_to = 'sebastian.cosmor@loveads.ro';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = htmlspecialchars(trim($_POST['name']    ?? ''));
    $phone   = htmlspecialchars(trim($_POST['phone']   ?? ''));
    $email   = htmlspecialchars(trim($_POST['email']   ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

    if ($name) {
        $subject = "Enquiry from {$name} - loveads.ro";
        $body  = "New enquiry from loveads.ro\n\n";
        $body .= "Name:    {$name}\n";
        if ($phone)   $body .= "Phone:   {$phone}\n";
        if ($email)   $body .= "Email:   {$email}\n";
        if ($message) $body .= "Message: {$message}\n";
        $headers  = "From: noreply@loveads.ro\r\n";
        $headers .= "Reply-To: " . ($email ?: 'noreply@loveads.ro') . "\r\n";
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;
