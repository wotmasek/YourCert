<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

return [
    'host'        => 'smtp.gmail.com',
    'username'    => 'TestYourCert@gmail.com',
    'password'    => 'zlkr svan pqcn zvgy',
    'smtp_secure' => PHPMailer::ENCRYPTION_SMTPS,
    'smtp_auth'   => true,
    'port'        => 465,
];
