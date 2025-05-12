<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\UserAPI\UserMenagment;
use Assets\LayoutRenderer;

if (!isset($_SESSION['userID'])) {
    header('Location: ' . LOGIN_PAGE);
    exit;
}

$db       = new Database();
$conn     = $db->getConnection();
$renderer = new LayoutRenderer($conn);

$token = trim($_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['token'] ?? '')
    : ($_GET['token']  ?? '')
);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($token)) {
        setFlashMessage('error', 'Invalid or missing token.');
        header('Location: ' . LOGIN_PAGE);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($token)) {
        setFlashMessage('error', 'Token is missing.');
        header('Location: ' . ACCOUNT_DELETE_PAGE . '?token=' . urlencode($token));
        exit;
    }

    try {
        $api    = new UserMenagment($_SESSION['userID'], $conn);
        $result = $api->confirmAccountDelete($token);

        if ($result['success']) {
            session_destroy();
            setcookie(session_name(), '', time() - 3600, '/');
            setFlashMessage('success', $result['message']);
            header('Location: ' . LOGIN_PAGE);
            exit;
        }

        setFlashMessage('error', $result['error'] ?? 'Unknown error.');
        header('Location: ' . ACCOUNT_DELETE_PAGE . '?token=' . urlencode($token));
        exit;

    } catch (Exception $e) {
        setFlashMessage('error', 'Internal server error.');
        header('Location: ' . ACCOUNT_DELETE_PAGE . '?token=' . urlencode($token));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Confirm Account Deletion</title>
  <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
  <?php $renderer->renderNav(); ?>
  <main>
    <div class="container d-flex justify-content-center align-items-center vh-100">
      <div class="card shadow-sm" style="max-width: 400px; width: 100%;">
        <div class="card-body">
          <?= getFlashMessages() ?>
          <h4 class="card-title mb-3 text-center">Confirm Account Deletion</h4>
          <p>This action will permanently delete your account. Are you sure?</p>
          <form method="post" class="d-grid gap-2">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <button type="submit" class="btn btn-danger btn-sm">Yes, Delete My Account</button>
            <a href="<?= LOGIN_PAGE ?>" class="btn btn-secondary btn-sm">Cancel</a>
          </form>
        </div>
      </div>
    </div>
  </main>
  <?php $renderer->renderFooter(); ?>
</body>
</html>
