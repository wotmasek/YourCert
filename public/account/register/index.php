<?php
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Assets\LayoutRenderer;

session_start();

$db         = new Database();
$connection = $db->getConnection();
$renderer   = new LayoutRenderer($connection);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Registration</title>
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

        <form action="register.php" method="post">
          <div class="mb-3">
            <label for="first_name" class="form-label">First Name:</label>
            <input type="text" name="first_name" id="first_name" class="form-control" required placeholder="Enter first name">
          </div>
          <div class="mb-3">
            <label for="last_name" class="form-label">Last Name:</label>
            <input type="text" name="last_name" id="last_name" class="form-control" required placeholder="Enter last name">
          </div>
          <div class="mb-3">
            <label for="birth_date" class="form-label">Date of Birth:</label>
            <input type="date" name="birth_date" id="birth_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" name="email" id="email" class="form-control" required placeholder="Enter email">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password:</label>
            <input type="password" name="password" id="password" class="form-control" required placeholder="Enter password">
          </div>
          <div class="mb-3">
            <label for="repeated_password" class="form-label">Repeat Password:</label>
            <input type="password" name="repeated_password" id="repeated_password" class="form-control" required placeholder="Repeat password">
          </div>
          <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>

        <div class="mt-3 text-center">
          <a href="<?php echo LOGIN_PAGE; ?>" class="link-primary d-block">Already have an account? Log in</a>
          <a href="<?php echo PASSWORD_REQUEST_PAGE; ?>" class="link-secondary d-block">Reset password</a>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
