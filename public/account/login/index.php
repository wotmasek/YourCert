<?php
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php'; 

use Assets\LayoutRenderer;
use Database\Database;

$db = new Database();
$pdo = $db->getConnection();

$renderer = new LayoutRenderer($pdo);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <?php $renderer->renderHead(); ?>
  <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body class="bg-body text-body">
  <?php $renderer->renderMinNav(); ?>

  <main>
    <div class="d-flex justify-content-center align-items-center vh-100">
      <div class="border p-4 rounded bg-body-secondary shadow-sm form-wrapper" style="min-width: 300px;">

        <div class="wrapper-flash-msg mb-3">
          <?php echo getFlashMessages(); ?>
        </div>

        <form action="login.php" method="post">
          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" name="email" id="email" class="form-control" required placeholder="Enter email">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password">
          </div>
          <button type="submit" class="btn btn-primary w-100">Log In</button>
        </form>

        <div class="mt-3 text-center">
          <a href="<?php echo REGISTRY_FORM; ?>" class="link-primary d-block">Don't have an account? Register</a>
          <a href="<?php echo PASSWORD_REQUEST_PAGE; ?>" class="link-secondary d-block">Reset password</a>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
