<?php

$mail_to = 'sebastian.cosmor@loveads.ro';

// Respond like a success without sending mail — used to silently drop bots
// so they don't learn the form is protected and start adapting.
function silent_ok() {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    silent_ok();
}

// --- Anti-spam layer 1: honeypot ----------------------------------------
// Hidden field that humans never see. Bots fill every input they find.
if (trim($_POST['company_website'] ?? '') !== '') {
    silent_ok();
}

// --- Anti-spam layer 2: JS token ----------------------------------------
// Set by JavaScript on submit. Most spam bots POST directly and skip JS.
if (($_POST['_js'] ?? '') !== '1') {
    silent_ok();
}

// --- Anti-spam layer 3: time-to-submit ----------------------------------
// Real people take a few seconds to fill the form; bots submit instantly.
$elapsed = (int) ($_POST['_elapsed'] ?? 0);
if ($elapsed < 3000) {
    silent_ok();
}

$name    = htmlspecialchars(trim($_POST['name']    ?? ''));
$phone   = htmlspecialchars(trim($_POST['phone']   ?? ''));
$email   = htmlspecialchars(trim($_POST['email']   ?? ''));
$message = htmlspecialchars(trim($_POST['message'] ?? ''));

// --- Anti-spam layer 4: basic content sanity ----------------------------
// Reject obviously invalid email and link-stuffed messages.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    silent_ok();
}
if (preg_match('#https?://#i', $message) || preg_match('#https?://#i', $name)) {
    silent_ok();
}
// Newlines in name/email are a header-injection signature.
if (preg_match('/[\r\n]/', $name . $email . $phone)) {
    silent_ok();
}

if ($name) {
    $subject = "Enquiry from {$name} - loveads.ro";
    $body  = "New enquiry from loveads.ro\n\n";
    $body .= "Name:    {$name}\n";
    if ($phone)   $body .= "Phone:   {$phone}\n";
    if ($email)   $body .= "Email:   {$email}\n";
    if ($message) $body .= "Message: {$message}\n";
    $headers  = "From: noreply@loveads.ro\r\n";
    $headers .= "Reply-To: " . ($email ?: 'noreply@loveads.ro') . "\r\n";
    mail($mail_to, $subject, $body, $headers);
}

silent_ok();
