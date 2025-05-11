<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/certificates/certificates.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\UserAPI\UserMenagment;
use Api\UserAPI\CertificatesMenagment;
use Assets\LayoutRenderer;

function isUserAdmin($conn) {
    if (!isset($_SESSION['userID'])) return false;
    $userApi = new UserMenagment($_SESSION['userID'], $conn);
    $permissions = $userApi->getUserPermissions();
    return (
        $permissions['success'] === true
        && isset($permissions['permissions']['name'])
        && $permissions['permissions']['name'] === 'Administrator'
    );
}

$database   = new Database();
$connection = $database->getConnection();

if (!isUserAdmin($connection)) {
    header('Location:' . LOGIN_PAGE);
    exit();
}

$renderer  = new LayoutRenderer($connection);
$certificateApi = new CertificatesMenagment($_SESSION['userID'], $connection);
$publicApi      = new PublicAPI($connection);
$message        = '';
$courses        = [];

$coursesResult = $publicApi->getCourses();
if ($coursesResult['success']) {
    $courses = $coursesResult['courses'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_course_id   = trim($_POST['course_id'] ?? '');
    $posted_title       = trim($_POST['title'] ?? '');
    $posted_description = trim($_POST['description'] ?? '');
    $posted_pdf_path    = trim($_POST['certificate_image_path'] ?? '');
    $posted_valid_until = trim($_POST['valid_until'] ?? '');

    $posted_course_id   = $posted_course_id === '' ? null : (int)$posted_course_id;
    $posted_pdf_path    = $posted_pdf_path === '' ? null : $posted_pdf_path;
    $posted_valid_until = $posted_valid_until === '' ? null : $posted_valid_until;

    if (!empty($_FILES['certificate_image']['tmp_name']) && $_FILES['certificate_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg','image/png','image/gif'];
        $file_type     = mime_content_type($_FILES['certificate_image']['tmp_name']);
        if (in_array($file_type, $allowed_types)) {
            $ext = match ($file_type) {
                'image/jpeg' => '.jpg',
                'image/png'  => '.png',
                'image/gif'  => '.gif',
                default      => ''
            };
            $filename  = bin2hex(random_bytes(16)) . $ext;
            $uploadDir = __DIR__ . '/../../uploads/certificates/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['certificate_image']['tmp_name'], $uploadDir . $filename)) {
                $posted_pdf_path = $filename;
            } else {
                $message = 'Error saving file.';
            }
        } else {
            $message = 'Invalid file type. Allowed: JPEG, PNG, GIF.';
        }
    }

    $createResult = $certificateApi->createCertificate(
        $posted_title,
        $posted_description,
        $posted_course_id,
        $posted_pdf_path,
        $posted_valid_until
    );

    if ($createResult['success']) {
        setFlashMessage('success', 'Certificate created successfully. ID: ' . $createResult['certificate_id']);
        header('Location: ' . ADMIN_PANEL . '?data_type=certificates');
        exit();
    } else {
        $message = 'Error: ' . $createResult['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Create New Certificate</title>
    <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
    <?php $renderer->renderNav(); ?>
    <main>
        <div class="container py-3">
            <div class="mb-3">
                <?= getFlashMessages() ?>
                <?php if ($message): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
            </div>
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Create New Certificate</h4>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="course_id" class="form-label">Related Course (optional)</label>
                            <select id="course_id" name="course_id" class="form-select form-select-sm">
                                <option value="">— None —</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= htmlspecialchars($course['id']) ?>"
                                        <?= (isset($_POST['course_id']) && $_POST['course_id']==$course['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($course['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="title" class="form-label">Certificate Title</label>
                            <input type="text" id="title" name="title" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Certificate Description</label>
                            <textarea id="description" name="description" rows="4" class="form-control form-control-sm"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="valid_until" class="form-label">Valid Until (optional)</label>
                            <input type="date" id="valid_until" name="valid_until" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['valid_until'] ?? '') ?>">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="certificate_image" class="form-label">Certificate Image (optional)</label>
                            <input type="file" id="certificate_image" name="certificate_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif">
                        </div>
                        <div class="col-12 d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">Create Certificate</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
