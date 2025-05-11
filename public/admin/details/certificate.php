<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/certificates/certificates.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\UserAPI\CertificatesMenagment;
use Assets\LayoutRenderer;

$database       = new Database();
$connection     = $database->getConnection();
$certificateApi = new CertificatesMenagment($_SESSION['userID'], $connection);
$publicApi      = new PublicAPI($connection);
$message        = '';
$courses        = [];

$coursesResult = $publicApi->getCourses();
if ($coursesResult['success']) {
    $courses = $coursesResult['courses'];
}

if (!isset($_GET['certificate_id'])) {
    echo '<div class="container py-3"><div class="alert alert-danger">No certificate ID provided.</div></div>';
    exit;
}

$certificate_id = (int)$_GET['certificate_id'];
$res = $publicApi->getCertificate($certificate_id);
if (!$res['success']) {
    echo '<div class="container py-3"><div class="alert alert-danger">Error fetching certificate: ' . htmlspecialchars($res['error']) . '</div></div>';
    exit;
}
$certificate = $res['certificate'];

$validUntil = $_POST['valid_until'] ?? (!empty($certificate['valid_until']) ? date('Y-m-d', strtotime($certificate['valid_until'])) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_course_id       = trim($_POST['course_id'] ?? '');
    $posted_title           = trim($_POST['title'] ?? '');
    $posted_description     = trim($_POST['description'] ?? '');
    $posted_pdf_path        = trim($_POST['certificate_image_path'] ?? '');
    $posted_valid_until     = trim($_POST['valid_until'] ?? '');

    $posted_course_id   = $posted_course_id === '' ? null : (int)$posted_course_id;
    $posted_pdf_path    = $posted_pdf_path === '' ? null : $posted_pdf_path;
    $posted_valid_until = $posted_valid_until === '' ? null : $posted_valid_until;

    if (!empty($_FILES['certificate_image']['tmp_name']) && $_FILES['certificate_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/gif'];
        $type    = mime_content_type($_FILES['certificate_image']['tmp_name']);
        if (in_array($type, $allowed)) {
            $ext  = str_replace('image/', '.', $type);
            $name = bin2hex(random_bytes(8)) . $ext;
            $dir  = __DIR__ . '/../../uploads/certificates/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($_FILES['certificate_image']['tmp_name'], $dir . $name)) {
                $posted_pdf_path = $name;
            } else {
                $message = 'Error saving uploaded file.';
            }
        } else {
            $message = 'Invalid file type. Accepted: JPEG, PNG, GIF.';
        }
    }

    $fields = [
        'course_id'   => $posted_course_id,
        'title'       => $posted_title,
        'description' => $posted_description,
        'valid_until' => $posted_valid_until,
    ];

    if ($posted_pdf_path !== null) {
        $fields['certificate_image_path'] = $posted_pdf_path;
    }

    $imageChanged = false;
    if ($posted_pdf_path !== null) {
        $imageChanged = ($posted_pdf_path !== $certificate['certificate_image_path']);
    }

    $noChange = (
      $posted_course_id      == $certificate['course_id']
      && $posted_title       === $certificate['title']
      && $posted_description === $certificate['description']
      && $posted_valid_until === $certificate['valid_until']
      && $imageChanged === false
  );

  if ($noChange) {
    $message = 'No changes detected.';
  } else {
    $upd = $certificateApi->updateCertificate($certificate_id, $fields);
    if ($upd['success']) {
        setFlashMessage('success', 'Certificate updated.');
        header('Location: ' . ADMIN_PANEL . '?data_type=certificates');
        exit;
    } else {
        $message = 'Error: ' . $upd['error'];
    }
  }
}

$renderer = new LayoutRenderer($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Certificate</title>
  <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
  <?php $renderer->renderNav(); ?>
  <main>
    <div class="container py-3">

      <div class="mb-3">
        <?php echo getFlashMessages(); ?>
        <?php if ($message): ?>
          <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h4 class="mb-0">Edit Certificate</h4>
        </div>
        <div class="card-body">
          <form method="post" enctype="multipart/form-data" class="row g-3">
            <?php if (!empty($certificate['certificate_image_path'])): ?>
              <div class="col-12 text-center">
                <img src="<?= htmlspecialchars(UPLOADS_FOLDER . 'certificates/' . $certificate['certificate_image_path']) ?>" alt="Certificate Preview" class="img-fluid rounded">
              </div>
            <?php endif; ?>
            <input type="hidden" name="certificate_id" value="<?= $certificate_id ?>">

            <div class="col-12 col-md-6">
              <label for="course_id" class="form-label">Related Course (optional)</label>
              <select id="course_id" name="course_id" class="form-select form-select-sm">
                <option value="">— None —</option>
                <?php foreach ($courses as $c): ?>
                  <option value="<?= htmlspecialchars($c['id']) ?>"
                    <?= ((isset($_POST['course_id']) && $_POST['course_id']==$c['id'])
                        || (!isset($_POST['course_id']) && $certificate['course_id']==$c['id']))
                        ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['title']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-12 col-md-6">
              <label for="title" class="form-label">Certificate Title</label>
              <input
                type="text"
                id="title"
                name="title"
                class="form-control form-control-sm"
                value="<?= htmlspecialchars($_POST['title'] ?? $certificate['title'] ?? '') ?>"
              >
            </div>

            <div class="col-12">
              <label for="description" class="form-label">Description</label>
              <textarea
                id="description"
                name="description"
                rows="4"
                class="form-control form-control-sm"
              ><?= htmlspecialchars($_POST['description'] ?? $certificate['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12 col-md-6">
              <label for="valid_until" class="form-label">Valid Until (optional)</label>
              <input
                type="date"
                id="valid_until"
                name="valid_until"
                class="form-control form-control-sm"
                value="<?= htmlspecialchars($validUntil) ?>"
              >
            </div>

            <div class="col-12 col-md-6">
              <label for="certificate_image" class="form-label">Certificate Image (optional)</label>
              <input
                type="file"
                id="certificate_image"
                name="certificate_image"
                class="form-control form-control-sm"
                accept="image/jpeg,image/png,image/gif"
              >
            </div>

            <div class="col-12 d-flex justify-content-end mt-3">
              <button type="submit" class="btn btn-primary btn-sm">
                Update Certificate
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>
</body>
</html>