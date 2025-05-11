<?php
session_start();
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/assigned_certificates/assigned_certificates.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\UserAPI\UserMenagment;
use Api\UserAPI\AssignedCertificatesMenagment;
use Assets\LayoutRenderer;

function isUserAdmin($conn) {
    if (!isset($_SESSION['userID'])) return false;
    $api = new UserMenagment($_SESSION['userID'], $conn);
    $perm = $api->getUserPermissions();
    return ($perm['success'] && (($perm['permissions']['name'] ?? '') === 'Administrator'));
}

$database   = new Database();
$connection = $database->getConnection();
if (!isUserAdmin($connection)) {
    header('Location:' . LOGIN_PAGE);
    exit;
}

$userApi   = new UserMenagment($_SESSION['userID'], $connection);
$certApi   = new AssignedCertificatesMenagment($_SESSION['userID'], $connection);
$publicApi = new PublicAPI($connection);

$user_id = max(0, (int)($_GET['user_id'] ?? 0));
if ($user_id <= 0) {
    echo '<div class="container py-3"><div class="alert alert-danger">Invalid user ID.</div></div>';
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_permission'])) {
        $newPerm = (int)($_POST['permission_id'] ?? 1);
        $res = $userApi->updateUserField($user_id, 'permission_id', $newPerm);
        $message = $res['success'] ? 'Permissions updated.' : 'Error: ' . $res['error'];
    }
    if (isset($_POST['assign_certificate'])) {
        $certId = (int)($_POST['certificate_id'] ?? 0);
        if ($certId <= 0) {
            $message = 'Invalid certificate ID.';
        } else {
            $res = $certApi->assignCertificate($user_id, $certId);
            $message = $res['success'] ? 'Certificate assigned.' : 'Error: ' . $res['error'];
        }
    }
}

$userRes = $userApi->getUser($user_id);
if (!$userRes['success']) {
    echo '<div class="container py-3"><div class="alert alert-danger">User not found.</div></div>';
    exit;
}
$user = $userRes['user'];

$ucRes = $certApi->getUserCertificates($user_id);
$userCertificates = $ucRes['success'] ? $ucRes['user_certificates'] : [];
$ucError = $ucRes['success'] ? '' : $ucRes['error'];

$availRes = $publicApi->getCertificates(50, 0, []);
$availableCertificates = $availRes['success'] ? $availRes['certificates'] : [];
$availError = $availRes['success'] ? '' : $availRes['error'];

$permRes = $userApi->getUserPermissions($user_id);
$currentPermissionId = $permRes['success'] ? ($permRes['permissions']['permission_id'] ?? 1) : 1;
$permissionsName = $permRes['success'] ? $permRes['permissions']['name'] : '';
$permissionsDesc = $permRes['success'] ? $permRes['permissions']['description'] : '';

$renderer = new LayoutRenderer($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage User</title>
  <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
  <?php $renderer->renderNav(); ?>
  <main>
    <div class="container py-3">

      <?php if ($message): ?>
      <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <div class="card mb-4">
        <div class="card-header"><h4 class="mb-0">User Information</h4></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-3">ID</dt><dd class="col-sm-9"><?= htmlspecialchars($user['id']) ?></dd>
            <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= htmlspecialchars($user['email']) ?></dd>
            <dt class="col-sm-3">First Name</dt><dd class="col-sm-9"><?= htmlspecialchars($user['first_name']) ?></dd>
            <dt class="col-sm-3">Last Name</dt><dd class="col-sm-9"><?= htmlspecialchars($user['last_name']) ?></dd>
            <dt class="col-sm-3">Birth Date</dt><dd class="col-sm-9"><?= htmlspecialchars($user['birth_date']) ?></dd>
            <dt class="col-sm-3">Registered At</dt><dd class="col-sm-9"><?= htmlspecialchars($user['created_at']) ?></dd>
            <dt class="col-sm-3">Permissions</dt>
            <dd class="col-sm-9">
              <?= $permissionsName
                ? htmlspecialchars($permissionsName) . ' (' . htmlspecialchars($permissionsDesc) . ')'
                : '<span class="text-danger">Error: ' . htmlspecialchars($permRes['error'] ?? '') . '</span>'
              ?>
            </dd>
          </dl>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h4 class="mb-0">User's Certificates</h4></div>
        <div class="card-body p-0">
          <?php if ($userCertificates): ?>
            <div class="table-responsive">
              <table class="table table-sm table-striped table-hover mb-0 align-middle">
                <thead class="table"><tr>
                  <th>ID</th><th>Title</th><th>Description</th><th>Valid Until</th>
                </tr></thead>
                <tbody>
                  <?php foreach ($userCertificates as $cert): ?>
                  <tr class="clickable-row" data-href="<?= ADMIN_PANEL . 'details/assigned_certificate.php?certificate_id=' . urlencode($cert['id']) ?>">
                    <td><?= htmlspecialchars($cert['id']) ?></td>
                    <td><?= htmlspecialchars($cert['certificate_title']) ?></td>
                    <td><?= htmlspecialchars($cert['certificate_description']) ?></td>
                    <td><?= $cert['valid_until'] ? htmlspecialchars($cert['valid_until']) : 'Unlimited' ?></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="p-3"><em>No certificates for this user.</em><?= $ucError ? ' Error: ' . htmlspecialchars($ucError) : '' ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h4 class="mb-0">Assign Certificate</h4></div>
        <div class="card-body">
          <?php if ($availableCertificates): ?>
          <form method="post" class="input-group input-group-sm mb-0">
            <select name="certificate_id" class="form-select" required>
              <option value="">Select certificate...</option>
              <?php foreach ($availableCertificates as $cert): ?>
                <option value="<?= htmlspecialchars($cert['id']) ?>"><?= htmlspecialchars($cert['title']) ?> — <?= htmlspecialchars($cert['description']) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" name="assign_certificate" class="btn btn-primary">Assign</button>
          </form>
          <?php else: ?>
            <div><em>No available certificates.</em><?= $availError ? ' Error: ' . htmlspecialchars($availError) : '' ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header"><h4 class="mb-0">Manage Permissions</h4></div>
        <div class="card-body">
          <form method="post" class="input-group input-group-sm mb-0">
            <select name="permission_id" class="form-select">
              <option value="1" <?= $currentPermissionId === 1 ? 'selected' : '' ?>>User</option>
              <option value="2" <?= $currentPermissionId === 2 ? 'selected' : '' ?>>Administrator</option>
            </select>
            <button type="submit" name="update_permission" class="btn btn-primary">Update</button>
          </form>
        </div>
      </div>

    </div>
  </main>
  <?php $renderer->renderFooter(); ?>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.clickable-row').forEach(row => {
      row.addEventListener('click', () => window.location = row.dataset.href);
    });
  });
</script>
</body>
</html>
