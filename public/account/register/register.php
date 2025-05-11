<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/system_api/system_api.php';

use Database\Database; 
use Api\SystemAPI\SystemAPI; 

if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['password'], $_POST['birth_date'])) {

    $database = new Database;
    $connection = $database->getConnection();

    if ($connection !== false) {
        $first_name = trim($_POST['first_name']);
        $last_name  = trim($_POST['last_name']);
        $email      = trim($_POST['email']);
        $password   = trim($_POST['password']);
        $repeated_password = trim($_POST['repeted_password']);
        $birth_date = trim($_POST['birth_date']);
        
        $api = new SystemAPI($connection);
        $result = $api->registerUser($first_name, $last_name, $email, $password, $repeated_password, $birth_date);

        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            header('Location: ' . LOGIN_PAGE);
            exit;
        } else {
            setFlashMessage('error', $result['error']);
            header('Location: ' . REGISTRY_FORM);
            exit;
        }
    } else {
        setFlashMessage('error', 'Database connection error');
        header('Location: ' . REGISTRY_FORM);
        exit;
    }
} else {
    header('Location: ' . REGISTRY_FORM);
    exit;
}
?>
