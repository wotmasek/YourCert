<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/institution/institution.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\UserAPI\InstitutionMenagment;
use Api\UserAPI\UserMenagment;
use Assets\LayoutRenderer;

function isUserAdmin($conn) {
    if (!empty($_SESSION['userID'])) {
        $userApi = new UserMenagment($_SESSION['userID'], $conn);
        $perm    = $userApi->getUserPermissions();
        return $perm['success'] && ($perm['permissions']['name'] ?? '') === 'Administrator';
    }
    return false;
}

$conn = (new Database())->getConnection();
if (!isUserAdmin($conn)) {
    header('Location:' . LOGIN_PAGE);
    exit;
}

$api      = new InstitutionMenagment($_SESSION['userID'], $conn);
$renderer = new LayoutRenderer($conn);
$message  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'title'   => trim($_POST['title']   ?? ''),
        'slug'    => trim($_POST['slug']    ?? ''),
        'content' => trim($_POST['content'] ?? '')
    ];

    if (in_array('', $fields, true)) {
        $message = 'Title, slug and content are required.';
    } else {
        $imgs = [];
        $uploadDir = __DIR__ . '/../../uploads/posts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif'];
        foreach ($_FILES['image_files']['name'] ?? [] as $i => $nm) {
            if ($_FILES['image_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $tmp  = $_FILES['image_files']['tmp_name'][$i];
            $type = mime_content_type($tmp);
            if (!isset($allowed[$type])) continue;
            $ext = $allowed[$type];
            $fn  = bin2hex(random_bytes(16)) . $ext;
            if (move_uploaded_file($tmp, $uploadDir . $fn)) {
                $imgs[] = [
                    'file_path' => $fn,
                    'caption'   => trim($_POST['captions_img'][$i] ?? ''),
                    'position'  => count($imgs)
                ];
            }
        }
        if ($imgs) $fields['images'] = $imgs;

        $certs = [];
        foreach ($_FILES['cert_files']['name'] ?? [] as $i => $nm) {
            if ($_FILES['cert_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $tmp = $_FILES['cert_files']['tmp_name'][$i];
            $ext = pathinfo($nm, PATHINFO_EXTENSION);
            $fn  = bin2hex(random_bytes(16)) . '.' . $ext;
            if (move_uploaded_file($tmp, $uploadDir . $fn)) {
                $certs[] = [
                    'file_path' => $fn,
                    'title'     => trim($_POST['titles_cert'][$i] ?? ''),
                    'position'  => count($certs)
                ];
            }
        }
        if ($certs) $fields['certificates'] = $certs;

        $res = $api->createPost($fields);
        if ($res['success']) {
            setFlashMessage('success', 'Post created (ID: ' . $res['data']['id'] . ')');
            header('Location:' . ADMIN_PANEL . '?data_type=posts');
            exit;
        } else {
            $message = 'Error: ' . $res['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <title>Create New Post</title>
  <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
  <?php $renderer->renderNav(); ?>
  <main class="container py-4">
    <?= getFlashMessages() ?>
    <?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <div class="card shadow-sm mb-4">
      <div class="card-header"><h4>Create New Post</h4></div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input name="title" class="form-control" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Slug</label>
            <input name="slug" class="form-control" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Content (HTML)</label>
            <textarea name="content" rows="6" class="form-control"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Add Images</label>
            <div id="new-img-rows">
              <div class="input-group mb-2 new-img-row">
                <input type="file" name="image_files[]" accept="image/*" class="form-control">
                <input type="text" name="captions_img[0]" placeholder="Caption" class="form-control">
                <button type="button" class="btn btn-danger remove-row">–</button>
              </div>
            </div>
            <button type="button" id="add-img-row" class="btn btn-outline-secondary">+ Add image</button>
          </div>

          <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">Create Post</button>
          </div>
        </form>
      </div>
    </div>
  </main>

  <script>
  ;(function(){
    document.addEventListener('click',e=>{
      if(e.target.matches('.remove-row')) e.target.closest('.input-group').remove();
      if(e.target.matches('.remove-row-cert')) e.target.closest('.input-group').remove();
    });
    document.getElementById('add-img-row').addEventListener('click',()=>{
      const tpl = document.querySelector('.new-img-row').cloneNode(true);
      tpl.querySelector('input[type=file]').value='';
      tpl.querySelector('input[type=text]').value='';
      document.getElementById('new-img-rows').appendChild(tpl);
    });
    document.getElementById('add-cert-row').addEventListener('click',()=>{
      const tpl = document.querySelector('.new-cert-row').cloneNode(true);
      tpl.querySelector('input[type=file]').value='';
      tpl.querySelector('input[type=text]').value='';
      document.getElementById('new-cert-rows').appendChild(tpl);
    });
  })();
  </script>
</body>
</html>
