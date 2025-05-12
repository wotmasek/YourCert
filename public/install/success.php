<?php
session_start();

require_once __DIR__ . '/../../app/assets/flash_messages.php';

$lockFile = __DIR__ . '/../../app/install.lock';
if (!file_exists($lockFile)) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installation Complete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-SgO…9m7"
      crossorigin="anonymous">
    <script defer
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-k6d…Nliq"
    crossorigin="anonymous"></script>
</head>
<body class="bg-body text-body">
    <main>
        <div class="container py-3">
            <div class="card text-center">
                <div class="card-header">
                    <h4 class="mb-0">Installation Complete</h4>
                </div>
                <div class="card-body">
                    <?= getFlashMessages() ?>
                    <p class="card-text">Your installation has been successfully completed.</p>
                    <p class="card-text text-muted">
                        For security reasons, please delete the <code>/install</code> folder from your server.
                    </p>
                    <a href="../index.php" class="btn btn-primary btn-sm mt-2">Go to Website</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>