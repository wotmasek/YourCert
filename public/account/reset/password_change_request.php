<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/system_api/system_api.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Assets\LayoutRenderer;
use Database\Database; 
use Api\SystemAPI\SystemAPI; 

$database = new Database();
$conn = $database->getConnection();
$renderer = new LayoutRenderer($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['email'])) {
        setFlashMessage('error', 'Empty email');
        header('Location: ' . PASSWORD_REQUEST_PAGE);
        exit;
    }

    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMessage('error', 'Invalid email');
        header('Location: ' . PASSWORD_REQUEST_PAGE);
        exit;
    }

    try {
        $api = new SystemAPI($conn);
        $result = $api->requestPasswordReset($email);

        if ($result['success']) {
            setFlashMessage('success', 'Password reset email has been sent to your email');
            header('Location: ' . LOGIN_PAGE);
        } else {
            setFlashMessage('error', $result['error'] ?? 'Unknown error occurred');
            header('Location: ' . PASSWORD_REQUEST_PAGE);
        }
    } catch (Exception $e) {
        setFlashMessage('error', 'Internal server error');
        header('Location: ' . PASSWORD_REQUEST_PAGE);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Password Reset Request</title>
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

        <form action="" method="post">
          <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input
              type="email"
              name="email"
              id="email"
              class="form-control"
              required
              placeholder="Enter your email"
            >
          </div>
          <button type="submit" class="btn btn-primary w-100">
            Request Password Reset
          </button>
        </form>

      </div>
    </div>
  </main>
</body>
</html>