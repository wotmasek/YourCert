<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

$lockFile = __DIR__ . '/../../app/install.lock';
$configFilePath      = __DIR__ . '/../../app/config.php';
$dbConfigFilePath    = __DIR__ . '/../../app/db_config.php';
$emailConfigFilePath = __DIR__ . '/../../app/email_config.php';
$sqlFilePath         = __DIR__ . '/../../db.sql';

if (file_exists($lockFile)) {
    echo '<div class="container py-3"><div class="alert alert-warning">The installation has already been completed. Reconfiguration is disabled.</div></div>';
    exit;
}

function showError($error) {
    echo '<div class="alert alert-danger">'.htmlspecialchars($error).'</div>';
}

function redirect($step) {
    header('Location: index.php?step=' . $step);
    exit;
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>YourCert Installation</title>
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
        <div class="container py-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Application configurator</h4>
                </div>
                <div class="card-body">
                    <?php
                    switch ($step) {
                        case 1:
                            ?>
                            <?php include __DIR__ . '/steps/step_1.php'; ?>
                            <?php
                            break;

                        case 2:
                            ?>
                            <?php include __DIR__ . '/steps/step_2.php'; ?>
                            <?php
                            break;

                        case 3:
                            ?>
                            <?php include __DIR__ . '/steps/step_3.php'; ?>
                            <?php
                            break;

                        case 4:
                            ?>
                            <?php include __DIR__ . '/steps/step_4.php'; ?>
                            <?php
                            break;

                        default:
                            ?>
                            <div class="alert alert-danger">Unknown installation step.</div>
                            <?php
                            break;
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>