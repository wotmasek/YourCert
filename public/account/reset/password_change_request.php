<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/system_api/system_api.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\SystemAPI\SystemAPI;
use Assets\LayoutRenderer;

if (!isset($_SESSION['userID'])) {
    header('Location: ' . LOGIN_PAGE);
    exit;
}

$db       = new Database();
$conn     = $db->getConnection();
$api      = new SystemAPI($conn);
$renderer = new LayoutRenderer($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['token'])) {
        setFlashMessage('error', 'Token is missing.');
        header('Location: ' . ACCOUNT_DELETE_PAGE);
        exit;
    }
    $token = trim($_POST['token']);
    try {
        $result = $api->confirmAccountDelete($token);
        if ($result['success']) {
            session_destroy();
            setFlashMessage('success', $result['message']);
            header('Location: ' . LOGIN_PAGE);
            exit;
        } else {
            setFlashMessage('error', $result['error'] ?? 'Unknown error occurred.');
            header('Location: ' . ACCOUNT_DELETE_PAGE);
            exit;
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Internal server error.');
        header('Location: ' . ACCOUNT_DELETE_PAGE);
        exit;
    }
}

$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
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
            <a href="<?= DASHBOARD_PAGE ?>" class="btn btn-secondary btn-sm">Cancel</a>
          </form>
        </div>
      </div>
    </div>
  </main>
  <?php $renderer->renderFooter(); ?>
</body>
</html>
