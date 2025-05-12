<?php
session_start();

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../app/assets/flash_messages.php';
require_once __DIR__ . '/../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Assets\LayoutRenderer;

$database   = new Database();
$connection = $database->getConnection();
if (!$connection) {
    die("Error connecting to the database.");
}

$public_api = new PublicAPI($connection);
$certificateData = null;
$errorMessage = '';

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    $result = $public_api->getUserCertificateByToken($token);
    if ($result['success']) {
        $certificateData = $result['certificate'];
    } else {
        $errorMessage = $result['error'];
    }
}

$renderer = new LayoutRenderer($connection);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <?php $renderer->renderHead(); ?>
    <title>Certificate Verification</title>
    <style>
        .amain .card { max-width: 500px; margin: 2rem auto; }
    </style>
</head>
<body class="bg-body text-body">
    <?php $renderer->renderNav(); ?>
    <main>
        <div class="amain">
            <div class="container py-4">
                <h1 class="mb-4 text-center">Certificate Verification</h1>
                <form method="get" class="d-flex justify-content-center mb-4">
                    <div class="input-group" style="max-width:500px;width:100%;">
                        <input
                            type="text"
                            name="token"
                            class="form-control form-control-sm"
                            value="<?= htmlspecialchars($token ?? '') ?>"
                            placeholder="Certificate token"
                            required
                        >
                        <button class="btn btn-primary btn-sm" type="submit">Verify</button>
                    </div>
                </form>
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger mt-4"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                <?php if ($certificateData): ?>
                    <?php
                        $imageFilename = $certificateData['personalized_certificate_image_path']
                            ?: $certificateData['certificate_image_path'];
                        $filePath = UPLOADS_FOLDER . 'certificates/' . htmlspecialchars($imageFilename);
                    ?>
                    <div class="card mt-4 shadow-sm">
                        <?php if ($imageFilename): ?>
                            <img src="<?= $filePath ?>" class="card-img-top" alt="Certificate Preview">
                            <div class="text-center my-3">
                                <a href="<?= $filePath ?>" download class="btn btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>Download Certificate
                                </a>
                                <button id="shareBtn" class="btn btn-outline-secondary ms-2">
                                    <i class="bi bi-share me-1"></i>Share
                                </button>
                            </div>
                            <script>
                                document.getElementById('shareBtn').addEventListener('click', function() {
                                    var btn = this;
                                    if (navigator.share) {
                                        navigator.share({
                                            title: '<?= addslashes($certificateData['certificate_title']) ?>',
                                            text: 'Check out my certificate!',
                                            url: window.location.href
                                        }).catch(console.error);
                                    } else if (navigator.clipboard) {
                                        navigator.clipboard.writeText(window.location.href)
                                            .then(function() {
                                                btn.innerHTML = '<i class="bi bi-clipboard-check me-1"></i>Copied!';
                                                btn.classList.replace('btn-outline-secondary', 'btn-success');
                                            })
                                            .catch(err => alert('Copy failed: ' + err));
                                    } else {
                                        alert('Sharing not supported on this browser.');
                                    }
                                });
                            </script>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title">Certificate Details</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Title:</dt>
                                <dd class="col-sm-8">
                                    <a href="<?= HTTP_ADRESS . '#cert-' . htmlspecialchars($certificateData['certificate_id']) ?>">
                                        <?= htmlspecialchars($certificateData['certificate_title']) ?>
                                    </a>
                                </dd>
                                <dt class="col-sm-4">Description:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($certificateData['certificate_description']) ?></dd>
                                <dt class="col-sm-4">Valid Until:</dt>
                                <dd class="col-sm-8">
                                    <?= $certificateData['certificate_valid_until']
                                        ? htmlspecialchars($certificateData['certificate_valid_until'])
                                        : 'No expiration date' ?>
                                </dd>
                                <dt class="col-sm-4">User Email:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($certificateData['email']) ?></dd>
                                <dt class="col-sm-4">First Name:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($certificateData['first_name']) ?></dd>
                                <dt class="col-sm-4">Last Name:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($certificateData['last_name']) ?></dd>
                                <dt class="col-sm-4">Birth Date:</dt>
                                <dd class="col-sm-8"><?= htmlspecialchars($certificateData['birth_date']) ?></dd>
                            </dl>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    <?php $renderer->renderFooter(); ?>
</body>
</html>