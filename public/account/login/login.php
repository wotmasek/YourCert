<?php
session_start();

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/system_api/system_api.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email'], $_POST['password'])) {
    $database = new Database\Database;
    $connection = $database->getConnection();

    if ($connection !== false) {
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $api = new Api\SystemAPI\SystemAPI($connection);
        $result = $api->loginUser($email, $password);

        if ($result['success']) {
            session_regenerate_id(true);
            $_SESSION['userID'] = $result['user_id'];
            setFlashMessage('success', $result['message'] ?? 'Login successful!');
            header('Location: ' . HTTP_ADRESS);
            exit;
        } else {
            setFlashMessage('error', $result['error']);
            header('Location: ' . LOGIN_PAGE);
            exit;
        }
    } else {
        setFlashMessage('error', 'Database connection error');
        header('Location: ' . LOGIN_PAGE);
        exit;
    }
} else {
    header('Location: ' . LOGIN_PAGE);
    exit;
}
?>
