<?php
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/assigned_certificates/assigned_certificates.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\UserAPI\AssignedCertificatesMenagment;
use Assets\LayoutRenderer;

session_start();

if (!isset($_SESSION['userID'])) {
    header("Location: " . LOGIN_PAGE);
    exit;
}

$db         = new Database();
$connection = $db->getConnection();

if ($connection === false) {
    die("Error connecting to the database.");
}

$userId               = $_SESSION['userID'];
$userApi              = new AssignedCertificatesMenagment($userId, $connection);
$certificatesResult   = $userApi->getUserCertificates($userId);

if (!$certificatesResult['success']) {
    die("Error fetching certificates: " . $certificatesResult['error']);
}

$certificatesInformation = $certificatesResult['user_certificates'];
$certificatesUrl         = HTTP_ADRESS . "uploads/certificates/";

$renderer = new LayoutRenderer($connection);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>My Certificates</title>
    <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
    <?php $renderer->renderNav(); ?>
    <main>
        <div class="amain">
            <div class="container py-4">
                <h1 class="mb-4 text-center">My Certificates</h1>
                <div class="row g-4">
                    <?php if (!empty($certificatesInformation)): ?>
                        <?php foreach ($certificatesInformation as $certificate):
                            $title       = htmlspecialchars($certificate['certificate_title'] ?? 'No Title');
                            $description = htmlspecialchars($certificate['certificate_description'] ?? 'No Description');
                            $awardedAt   = htmlspecialchars($certificate['awarded_at'] ?? 'No Award Date');
                            $validUntil  = !empty($certificate['valid_until'])
                                            ? htmlspecialchars($certificate['valid_until'])
                                            : 'No Expiry';
                            $imageFile   = !empty($certificate['personalized_certificate_image_path'])
                                            ? htmlspecialchars($certificate['personalized_certificate_image_path'])
                                            : (isset($certificate['certificate_image_path'])
                                                ? htmlspecialchars($certificate['certificate_image_path'])
                                                : '');
                            $token       = htmlspecialchars($certificate['token'] ?? '');
                            if (empty($token)) continue;
                            $verifyUrl   = VERIFY_CERTIFICATE_PAGE . "?token=" . urlencode($token);
                        ?>
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm" onclick="location.href='<?= $verifyUrl ?>'" style="cursor:pointer;">
                                    <?php if ($imageFile): ?>
                                        <img src="<?= $certificatesUrl . $imageFile ?>" class="card-img-top" alt="Certificate preview">
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?= $title ?></h5>
                                        <p class="card-text"><strong>Description:</strong> <?= $description ?></p>
                                        <p class="card-text"><strong>Awarded:</strong> <?= $awardedAt ?></p>
                                        <p class="card-text"><strong>Valid Until:</strong> <?= $validUntil ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col">
                            <p class="text-center">No certificates to display.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <?php $renderer->renderFooter(); ?>
</body>
</html>
