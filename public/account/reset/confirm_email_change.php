<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';

use Database\Database;
use Api\UserAPI\UserMenagment;

if (!isset($_SESSION['userID'])) {
    header('Location: ' . LOGIN_PAGE);
    exit();
}

$db   = new Database();
$conn = $db->getConnection();

$userId  = $_SESSION['userID'];
$userAPI = new UserMenagment($userId, $conn);

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $result = $userAPI->confirmEmailChange($token);
    if ($result['success']) {
        setFlashMessage('success', $result['message']);
        header('Location: ' . LOGIN_PAGE);
        exit;
    } else {
        setFlashMessage('error', $result['error'] ?? 'Unknown error occurred');
        header('Location: ' . LOGIN_PAGE);
        exit;
    }
} else {
    header('Location: ' . LOGIN_PAGE);
    exit;
}
?>
