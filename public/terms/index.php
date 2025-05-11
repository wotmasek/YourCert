<?php
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
    die("Database connection error.");
}

$public_api = new PublicAPI($connection);

$instRes = $public_api->getInstitution();
if (!$instRes['success']) {
    die("Institution fetch error: " . htmlspecialchars($instRes['error']));
}
$inst = $instRes['institution'];

$seoRes = $public_api->getInstitutionSEO($inst['id']);
$seo    = $seoRes['success'] ? $seoRes['data'] : [];

$pageRes = $public_api->getPage('terms');
if (!$pageRes['success']) {
    die("Regulations page error: " . htmlspecialchars($pageRes['error']));
}
$page = $pageRes['data'];

$title       = htmlspecialchars($page['title'] . ' – ' . ($seo['meta_title'] ?? $inst['name']));
$description = htmlspecialchars(
    $seo['meta_description']
    ?? substr(strip_tags($page['content']), 0, 160)
);
$canonical   = htmlspecialchars(HTTP_ADRESS . 'terms/');
$robots      = 'index, follow';
$ogTitle     = htmlspecialchars($page['title']);
$ogDesc      = htmlspecialchars(
    $seo['og_description']
    ?? $description
);
$ogImage     = !empty($seo['og_image_url'])
    ? htmlspecialchars($seo['og_image_url'])
    : '';
$ogType      = 'article';

$renderer = new LayoutRenderer($connection);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <?php $renderer->renderHead(); ?>
    <title><?= $title ?></title>
    <meta name="description" content="<?= $description ?>">
    <link rel="canonical" href="<?= $canonical ?>">
    <meta name="robots" content="<?= $robots ?>">
    <meta property="og:type" content="<?= $ogType ?>">
    <meta property="og:title" content="<?= $ogTitle ?>">
    <meta property="og:description" content="<?= $ogDesc ?>">
    <?php if ($ogImage): ?>
        <meta property="og:image" content="<?= $ogImage ?>">
    <?php endif; ?>
    <meta property="og:url" content="<?= $canonical ?>">
</head>
<body class="bg-body text-body">
    <?php $renderer->renderNav(); ?>
    <main class="py-4">
        <div class="container" style="max-width:800px;">
            <h1 class="h3 mb-4 text-center"><?= htmlspecialchars($page['title']) ?></h1>
            <div class="card shadow-sm mb-5">
                <div class="card-body">
                    <?= nl2br($page['content']) ?>
                </div>
            </div>
        </div>
    </main>
    <?php $renderer->renderFooter(); ?>
</body>
</html>