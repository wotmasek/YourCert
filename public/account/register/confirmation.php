<?php
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Assets\LayoutRenderer;

session_start();

if (isset($_GET['code'])) {
    $confirmationCode = $_GET['code'];

    $database   = new Database;
    $connection = $database->getConnection();

    if ($connection !== false) {
        $query = "SELECT id FROM users WHERE confirmation_code = :code";
        $stmt  = $connection->prepare($query);
        $stmt->bindParam(':code', $confirmationCode);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $query = "UPDATE users SET is_confirmed = 1 WHERE confirmation_code = :code";
            $stmt  = $connection->prepare($query);
            $stmt->bindParam(':code', $confirmationCode);

            if ($stmt->execute()) {
                setFlashMessage('message', 'Your account has been successfully confirmed! You can now log in.');
            } else {
                setFlashMessage('error', 'An error occurred. Please try again later.');
            }

            header('Location: ' . LOGIN_PAGE);
            exit;
        } else {
            setFlashMessage('error', 'Invalid confirmation code.');
        }
    } else {
        setFlashMessage('error', 'Error connecting to the database.');
    }
}

$renderer = new LayoutRenderer((new Database)->getConnection());
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Account Confirmation</title>
  <?php $renderer->renderHead(); ?>
  <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body class="bg-body text-body">
  <?php $renderer->renderMinNav(); ?>
  <main>
    <div class="d-flex justify-content-center align-items-center vh-100">
      <div class="border p-4 rounded bg-body-secondary shadow-sm form-wrapper" style="min-width: 300px;">
        <h4 class="mb-4 text-center">Account Confirmation</h4>
        <div class="wrapper-flash-msg mb-3">
          <?php echo getFlashMessages(); ?>
        </div>
        <div class="text-center">
          <a href="<?php echo LOGIN_PAGE ?>" class="btn btn-primary w-100">Go to Login</a>
        </div>
      </div>
    </div>
  </main>
  <?php $renderer->renderFooter(); ?>
</body>
</html>
