<?php
$institution_id = isset($_GET['institution_id']) ? (int)$_GET['institution_id'] : 1;

$instResult = $public_api->getInstitution($institution_id);

if (!$instResult['success']) {
    echo "<p>Nie znaleziono danych instytucji.</p>";
    exit;
} else {
    $institution = $instResult['institution'];
    $message = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $fields = [];

        // Text fields
        $posted_name           = trim($_POST['name'] ?? '');
        $posted_description    = trim($_POST['description'] ?? '');
        $posted_contact_email  = trim($_POST['contact_email'] ?? '');
        $posted_phone_number   = trim($_POST['phone_number'] ?? '');
        $posted_website_url    = trim($_POST['website_url'] ?? '');

        if ($posted_name !== $institution['name']) {
            $fields['name'] = $posted_name;
        }
        if ($posted_description !== $institution['description']) {
            $fields['description'] = $posted_description;
        }
        if ($posted_contact_email !== $institution['contact_email']) {
            $fields['contact_email'] = $posted_contact_email;
        }
        if ($posted_phone_number !== $institution['phone_number']) {
            $fields['phone_number'] = $posted_phone_number;
        }
        if ($posted_website_url !== $institution['website_url']) {
            $fields['website_url'] = $posted_website_url;
        }

        // Upload directories
        $uploadDir     = __DIR__ . '/../../../uploads/institutions/';
        $relativeDir   = '/uploads/institutions/';
        
        // Old files
        $old_avatar    = $institution['profile_image_url'] ?? null;
        $old_banner    = $institution['banner_url'] ?? null;
        $old_logo      = $institution['logo_url'] ?? null;
        $old_favicon   = $institution['favicon_url'] ?? null;

        // AVATAR upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $tmpPath   = $_FILES['avatar']['tmp_name'];
            $fileName  = $_FILES['avatar']['name'];
            $ext       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed   = ['jpg','jpeg','png','gif'];
            if (in_array($ext, $allowed, true)) {
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    $message .= "Nie udało się utworzyć katalogu dla awatarów. ";
                }
                $newName = 'avatar_' . $institution_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmpPath, $uploadDir . $newName)) {
                    if ($old_avatar && file_exists($uploadDir . $old_avatar)) {
                        unlink($uploadDir . $old_avatar);
                    }
                    $fields['profile_image_url'] = $newName;
                } else {
                    $message .= "Błąd podczas przesyłania awatara. ";
                }
            } else {
                $message .= "Niedozwolony format pliku dla awatara. ";
            }
        } elseif (!empty($_POST['profile_image_url'])) {
            $posted = trim($_POST['profile_image_url']);
            if ($posted !== $institution['profile_image_url']) {
                $fields['profile_image_url'] = $posted;
            }
        }

        // BANNER upload
        if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
            $tmpPath   = $_FILES['banner']['tmp_name'];
            $fileName  = $_FILES['banner']['name'];
            $ext       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed   = ['jpg','jpeg','png','gif'];
            if (in_array($ext, $allowed, true)) {
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    $message .= "Nie udało się utworzyć katalogu dla bannerów. ";
                }
                $newName = 'banner_' . $institution_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmpPath, $uploadDir . $newName)) {
                    if ($old_banner && file_exists($uploadDir . $old_banner)) {
                        unlink($uploadDir . $old_banner);
                    }
                    $fields['banner_url'] = $newName;
                } else {
                    $message .= "Błąd podczas przesyłania bannera. ";
                }
            } else {
                $message .= "Niedozwolony format pliku dla bannera. ";
            }
        } elseif (!empty($_POST['banner_url'])) {
            $posted = trim($_POST['banner_url']);
            if ($posted !== $institution['banner_url']) {
                $fields['banner_url'] = $posted;
            }
        }

        // LOGO upload
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath   = $_FILES['logo']['tmp_name'];
            $fileName  = $_FILES['logo']['name'];
            $ext       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed   = ['jpg','jpeg','png','gif','svg','webp'];
            if (in_array($ext, $allowed, true)) {
                if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                    $message .= "Nie udało się utworzyć katalogu dla logo. ";
                }
                $newName = 'logo_' . $institution_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmpPath, $uploadDir . $newName)) {
                    if ($old_logo && file_exists($uploadDir . $old_logo)) {
                        unlink($uploadDir . $old_logo);
                    }
                    $fields['logo_url'] = $newName;
                } else {
                    $message .= "Błąd podczas przesyłania logo. ";
                }
            } else {
                $message .= "Niedozwolony format pliku dla logo. ";
            }
        } elseif (!empty($_POST['logo_url'])) {
            $posted = trim($_POST['logo_url']);
            if ($posted !== $institution['logo_url']) {
                $fields['logo_url'] = $posted;
            }
        }

        // FAVICON upload
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $tmpPath  = $_FILES['favicon']['tmp_name'];
            $ext      = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            $allowed  = ['ico','png','svg','gif'];
            if (in_array($ext, $allowed, true)) {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName = 'favicon_' . $institution_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($tmpPath, $uploadDir . $newName)) {
                    if ($old_favicon && file_exists($uploadDir . $old_favicon)) {
                        unlink($uploadDir . $old_favicon);
                    }
                    $fields['favicon_url'] = $newName;
                } else {
                    $message .= "Błąd podczas przesyłania favicony. ";
                }
            } else {
                $message .= "Niedozwolony format pliku dla favicony. ";
            }
        } elseif (!empty($_POST['favicon_url'])) {
            $posted = trim($_POST['favicon_url']);
            if ($posted !== $institution['favicon_url']) {
                $fields['favicon_url'] = $posted;
            }
        }

        // Update API
        if (!empty($fields)) {
            $updateResult = $api->updateInstitution($institution_id, $fields);
            if ($updateResult['success']) {
                $message .= "Dane instytucji zostały zaktualizowane.";
                $instResult = $public_api->getInstitution($institution_id);
                $institution = $instResult['institution'];
            } else {
                $message .= "Błąd: " . $updateResult['error'];
            }
        } elseif ($message === '') {
            $message = "Brak zmian do aktualizacji.";
        }
    }
}
?>
<div class="card-header">
    <h4 class="mb-0">Edit Institution</h4>
</div>
<div class="card-body">
    <?php if (!empty($message)): ?>
    <div class="alert alert-info alert-sm" role="alert">
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="institution_id" value="<?= $institution_id ?>">

        <div class="col-12 col-md-6">
            <label for="name" class="form-label">Institution Name</label>
            <input type="text" id="name" name="name" class="form-control form-control-sm" value="<?= htmlspecialchars($institution['name'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="3" class="form-control form-control-sm"><?= htmlspecialchars($institution['description'] ?? '') ?></textarea>
        </div>

        <div class="col-12 col-md-4">
            <label for="contact_email" class="form-label">Contact Email</label>
            <input type="email" id="contact_email" name="contact_email" class="form-control form-control-sm" value="<?= htmlspecialchars($institution['contact_email'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-4">
            <label for="phone_number" class="form-label">Phone Number</label>
            <input type="text" id="phone_number" name="phone_number" class="form-control form-control-sm" value="<?= htmlspecialchars($institution['phone_number'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-4">
            <label for="website_url" class="form-label">Website URL</label>
            <input type="url" id="website_url" name="website_url" class="form-control form-control-sm" value="<?= htmlspecialchars($institution['website_url'] ?? '') ?>">
        </div>

        <div class="col-12 col-md-4">
            <label for="avatar" class="form-label">Avatar</label>
            <input type="file" id="avatar" name="avatar" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif">
        </div>

        <div class="col-12 col-md-4">
            <label for="banner" class="form-label">Banner</label>
            <input type="file" id="banner" name="banner" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif">
        </div>

        <div class="col-12 col-md-4">
            <label for="logo" class="form-label">Institution Logo</label>
            <input type="file" id="logo" name="logo" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.gif,.svg,.webp">
        </div>

        <div class="col-12 col-md-4">
            <label for="favicon" class="form-label">Favicon</label>
            <input type="file" id="favicon" name="favicon" class="form-control form-control-sm" accept=".ico,.png,.svg,.gif">
        </div>


        <div class="col-12 d-flex justify-content-end mt-3">
            <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="window.location.href='<?= ADMIN_PANEL ?>create/course.php';">Create Course</button>
            <button type="button" class="btn btn-outline-secondary btn-sm me-2" onclick="window.location.href='<?= ADMIN_PANEL ?>create/certificate.php';">Create Certificate</button>
            <button type="submit" name="update" class="btn btn-primary btn-sm">Update Data</button>
        </div>
    </form>
</div>
