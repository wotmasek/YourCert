<?php
require_once __DIR__ . '/../../../app/config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];

session_destroy();

header("Location: " . HTTP_ADRESS);
exit;
?>
