<?php

$mail_to = 'sebastian.cosmor@loveads.ro';

if (!empty($_POST['name']) && (!empty($_POST['phone']) || !empty($_POST['email']))) {

    $name    = htmlspecialchars(strip_tags($_POST['name']));
    $phone   = htmlspecialchars(strip_tags($_POST['phone'] ?? ''));
    $email   = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars(strip_tags($_POST['message'] ?? ''));

    $subject = "Enquiry from {$name} — loveads.ro";

    $body  = "New enquiry from loveads.ro\n\n";
    $body .= "Name:    {$name}\n";
    if ($phone)   $body .= "Phone:   {$phone}\n";
    if ($email)   $body .= "Email:   {$email}\n";
    if ($message) $body .= "Message: {$message}\n";

    $headers  = "From: noreply@loveads.ro\r\n";
    $headers .= "Reply-To: " . ($email ?: 'noreply@loveads.ro') . "\r\n";

    mail($mail_to, $subject, $body, $headers);
}

header('Location: https://www.loveads.ro');
exit;
