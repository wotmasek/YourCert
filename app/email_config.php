<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

return [
    'host'        => '',
    'username'    => '',
    'password'    => '',
    'smtp_secure' => PHPMailer::ENCRYPTION_SMTPS,
    'smtp_auth'   => true,
    'port'        => 465,
];
