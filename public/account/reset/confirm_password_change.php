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

if (empty($_GET['token'])) {
    setFlashMessage('error', 'Missing password reset token.');
    header('Location: ' . LOGIN_PAGE);
    exit;
}

$token = $_GET['token'];

$db   = new Database();
$conn = $db->getConnection();
$api  = new SystemAPI($conn);
$renderer = new LayoutRenderer($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword     = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        setFlashMessage('error', 'Passwords do not match.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    $result = $api->resetPassword($token, $newPassword);
    if ($result['success']) {
        setFlashMessage('success', $result['message']);
        header('Location: ' . LOGIN_PAGE);
        exit;
    } else {
        setFlashMessage('error', $result['error'] ?? 'An unknown error occurred.');
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <?php $renderer->renderHead(); ?>
  <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body class="bg-body text-body">
  <?php $renderer->renderMinNav(); ?>

  <main>
    <div class="d-flex justify-content-center align-items-center vh-100">
      <div class="card bg-body-secondary shadow-sm" style="min-width:300px; max-width:400px;">
        <div class="card-body p-4">

          <div class="wrapper-flash-msg mb-3">
            <?= getFlashMessages() ?>
          </div>

          <h2 class="h5 text-center mb-4">Reset Password</h2>

          <form method="post" class="row g-3">
            <div class="col-12">
              <label for="new_password" class="form-label">New Password</label>
              <input type="password" id="new_password" name="new_password"
                     class="form-control form-control-sm" required>
            </div>
            <div class="col-12">
              <label for="confirm_password" class="form-label">Confirm Password</label>
              <input type="password" id="confirm_password" name="confirm_password"
                     class="form-control form-control-sm" required>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-primary w-100 btn-sm">Reset Password</button>
            </div>
          </form>

          <div class="mt-3 text-center">
            <a href="<?= LOGIN_PAGE ?>" class="link-secondary">Back to Login</a>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
