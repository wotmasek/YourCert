<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/assigned_certificates/assigned_certificates.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\UserAPI\AssignedCertificatesMenagment;
use Api\UserAPI\UserMenagment;
use Assets\LayoutRenderer;

$certificates_folder = '../../uploads/certificates/';
$certificates_url    = HTTP_ADRESS . "uploads/certificates/";

$database   = new Database();
$connection = $database->getConnection();

$renderer = new LayoutRenderer($connection);

function isUserAdmin($conn) {
    if (!isset($_SESSION['userID'])) return false;
    $userApi     = new UserMenagment($_SESSION['userID'], $conn);
    $permissions = $userApi->getUserPermissions();
    return (
        $permissions['success'] === true 
        && isset($permissions['permissions']['name']) 
        && $permissions['permissions']['name'] === 'Administrator'
    );
}
if (!isUserAdmin($connection)) {
    header('Location:' . LOGIN_PAGE);
    exit();
}

$assignedApi = new AssignedCertificatesMenagment($_SESSION['userID'], $connection);
$public_api  = new PublicAPI($connection);

$certificate_id = isset($_GET['certificate_id']) ? (int)$_GET['certificate_id'] : 0;
if ($certificate_id <= 0) {
    echo "Invalid certificate ID.";
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_valid_date'])) {
        $removeDateResult = $assignedApi->updateAssignedCertificateField(
            $certificate_id,
            'valid_until',
            null
        );
        $message = $removeDateResult['success']
            ? "Validity date has been removed."
            : "Error removing validity date: " . $removeDateResult['error'];
    }
    if (isset($_POST['update'])) {
        $currentRecord = $assignedApi->getAssignedCertificate($certificate_id);
        if (!$currentRecord['success']) {
            $message = "Failed to retrieve assigned certificate.";
        } else {
            $assigned_certificate = $currentRecord['assigned_certificate'];
        }
        $updateMessages = [];
        $old_file = !empty($assigned_certificate['personalized_certificate_image_path'])
            ? $assigned_certificate['personalized_certificate_image_path']
            : '';

        if (isset($_FILES['certificate_image']) && $_FILES['certificate_image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type     = mime_content_type($_FILES['certificate_image']['tmp_name']);
            if (!in_array($file_type, $allowed_types)) {
                $updateMessages[] = "Invalid file type. Allowed types: JPEG, PNG, GIF.";
            } else {
                $ext = '';
                switch ($file_type) {
                    case 'image/jpeg': $ext = '.jpg'; break;
                    case 'image/png':  $ext = '.png'; break;
                    case 'image/gif':  $ext = '.gif'; break;
                }
                $random_filename = bin2hex(random_bytes(16)) . $ext;
                if (!is_dir($certificates_folder)) {
                    mkdir($certificates_folder, 0755, true);
                }
                $destination = $certificates_folder . $random_filename;
                if (move_uploaded_file($_FILES['certificate_image']['tmp_name'], $destination)) {
                    $updateResult = $assignedApi->updateAssignedCertificateField(
                        $certificate_id,
                        'personalized_certificate_image_path',
                        $random_filename
                    );
                    if ($updateResult['success']) {
                        if (!empty($old_file)
                            && $old_file !== $random_filename
                            && file_exists($certificates_folder . $old_file)
                        ) {
                            unlink($certificates_folder . $old_file);
                        }
                        $updateMessages[] = "Certificate image has been updated.";
                    } else {
                        $updateMessages[] = "Error updating image: " . $updateResult['error'];
                    }
                } else {
                    $updateMessages[] = "Error saving the image file.";
                }
            }
        }

        if (isset($_POST['assignment_valid_until'])) {
            $new_valid_date    = trim($_POST['assignment_valid_until']);
            $new_valid_date    = ($new_valid_date === '') ? null : $new_valid_date;
            $updateDateResult  = $assignedApi->updateAssignedCertificateField(
                $certificate_id,
                'valid_until',
                $new_valid_date
            );
            $updateMessages[]  = $updateDateResult['success']
                ? "Validity date has been updated."
                : "Error updating validity date: " . $updateDateResult['error'];
        }

        if (empty($updateMessages)) {
            $updateMessages[] = "No changes submitted.";
        }
        $message = implode(' ', $updateMessages);
    }
    if (isset($_POST['delete'])) {
        $deleteResult = $assignedApi->deleteUserCertificate($certificate_id);
        if ($deleteResult['success']) {
            header("Location:" . ADMIN_PANEL);
            exit();
        } else {
            $message = "Error: " . $deleteResult['error'];
        }
    }
    if (isset($_POST['delete_file'])) {
        $currentRecord = $assignedApi->getAssignedCertificate($certificate_id);
        if ($currentRecord['success']
            && !empty($currentRecord['assigned_certificate']['personalized_certificate_image_path'])
        ) {
            $old_file = $currentRecord['assigned_certificate']['personalized_certificate_image_path'];
            if (file_exists($certificates_folder . $old_file)) {
                unlink($certificates_folder . $old_file);
            }
            $assignedApi->updateAssignedCertificateField(
                $certificate_id,
                'personalized_certificate_image_path',
                ''
            );
            $message = "Certificate image has been removed.";
        } else {
            $message = "No certificate image to remove.";
        }
    }
}

$assignedCertResult = $assignedApi->getAssignedCertificate($certificate_id);
if (!$assignedCertResult['success']) {
    echo "Assigned certificate not found.";
    exit();
}
$assigned_certificate = $assignedCertResult['assigned_certificate'];

$certificate_image_path = '';
if (!empty($assigned_certificate['personalized_certificate_image_path'])
    && file_exists($certificates_folder . $assigned_certificate['personalized_certificate_image_path'])
) {
    $certificate_image_path = $certificates_url . $assigned_certificate['personalized_certificate_image_path'];
} else {
    $certificateData = $public_api->getCertificate($assigned_certificate['certificate_id']);
    if ($certificateData['success']
        && !empty($certificateData['certificate']['certificate_image_path'])
    ) {
        $certificate_image_path = $certificates_url . $certificateData['certificate']['certificate_image_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Assigned Certificate</title>
  <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
  <?php $renderer->renderNav(); ?>
  <main>
    <div class="container py-3">

      <!-- Flash & inline message -->
      <div class="mb-3">
        <?php if (!empty($message)): ?>
          <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h4 class="mb-0">Edit Assigned Certificate</h4>
        </div>
        <div class="card-body">
          <form method="post" action="" enctype="multipart/form-data" class="row g-3">

            <?php if (!empty($certificate_image_path)): ?>
              <div class="col-12 text-center">
                <img src="<?= htmlspecialchars($certificate_image_path) ?>"
                     alt="Certificate Preview"
                     class="img-fluid rounded">
              </div>
            <?php endif; ?>

            <div class="col-md-6">
              <label class="form-label">Certificate Token</label>
              <input type="text"
                     class="form-control form-control-sm"
                     value="<?= htmlspecialchars($assigned_certificate['token']) ?>"
                     readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Awarded At</label>
              <input type="text"
                     class="form-control form-control-sm"
                     value="<?= htmlspecialchars(date('Y-m-d H:i', strtotime($assigned_certificate['awarded_at']))) ?>"
                     readonly>
            </div>

            <div class="col-12">
              <label for="certificate_image" class="form-label">Update Certificate Image (optional)</label>
              <input type="file"
                     class="form-control form-control-sm"
                     name="certificate_image"
                     id="certificate_image"
                     accept="image/*">
            </div>

            <div class="col-12">
              <label for="assignment_valid_until" class="form-label">Certificate Valid Until (optional)</label>
              <input type="date"
                     class="form-control form-control-sm"
                     name="assignment_valid_until"
                     id="assignment_valid_until"
                     value="<?= !empty($assigned_certificate['valid_until'])
                              ? htmlspecialchars(date('Y-m-d', strtotime($assigned_certificate['valid_until'])))
                              : '' ?>">
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                <button type="submit"
                      name="delete"
                      class="btn btn-danger btn-sm"
                      onclick="return confirm('Are you sure you want to delete this assigned certificate?');">
                    Delete Assigned Certificate
                </button>
                <button type="submit"
                      name="update"
                      class="btn btn-primary btn-sm">
                    Save Changes
                </button>
            </div>
    
          </form>
        </div>
      </div>

    </div>
  </main>
</body>
</html>
