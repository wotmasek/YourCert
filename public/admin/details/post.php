<?php
session_start();
require_once __DIR__.'/../../../app/config.php';
require_once __DIR__.'/../../../app/assets/api/db_connect.php';
require_once __DIR__.'/../../../app/assets/api/public_api/public_api.php';
require_once __DIR__.'/../../../app/assets/api/user_apis/apis/institution/institution.php';
require_once __DIR__.'/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__.'/../../../app/assets/flash_messages.php';
require_once __DIR__.'/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\UserAPI\InstitutionMenagment;
use Api\UserAPI\UserMenagment;
use Assets\LayoutRenderer;

function isUserAdmin($conn) {
    if (!empty($_SESSION['userID'])) {
        $perm = (new UserMenagment($_SESSION['userID'], $conn))->getUserPermissions();
        return $perm['success'] && (($perm['permissions']['name'] ?? '') === 'Administrator');
    }
    return false;
}

$conn = (new Database())->getConnection();
if (!isUserAdmin($conn)) {
    header('Location:'.LOGIN_PAGE);
    exit;
}

$pub      = new PublicAPI($conn);
$api      = new InstitutionMenagment($_SESSION['userID'], $conn);

$post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
if (!$post_id) {
    die('<div class="alert alert-danger">No post ID</div>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $del = $api->deletePost($post_id);
    if ($del['success']) {
        setFlashMessage('success', 'Post deleted successfully.');
        header('Location: ' . ADMIN_PANEL . '?data_type=posts');
        exit;
    }
    setFlashMessage('danger', 'Error deleting post: ' . htmlspecialchars($del['error']));
    header('Refresh:0');
    exit;
}

$res = $pub->getPost($post_id);
if (!$res['success']) {
    die('<div class="alert alert-danger">'.htmlspecialchars($res['error']).'</div>');
}

$post    = $res['data'];
$images  = $post['images'] ?? [];
$certs   = $post['certificates'] ?? [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'title'   => trim($_POST['title'] ?? ''),
        'slug'    => trim($_POST['slug'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
    ];
    $removeImg  = array_keys($_POST['remove_img'] ?? []);
    $removeCert = array_keys($_POST['remove_cert'] ?? []);
    if ($removeImg)  $fields['remove_images'] = $removeImg;
    if ($removeCert) $fields['remove_certificates'] = $removeCert;

    $keepImgs = [];
    foreach ($_POST['existing_img'] ?? [] as $idx => $file) {
        if (!in_array($idx, $removeImg, true)) {
            $keepImgs[] = [
                'file_path' => $file,
                'caption'   => trim($_POST['captions_img'][$idx] ?? ''),
                'position'  => intval($_POST['positions_img'][$idx] ?? $idx),
            ];
        }
    }

    $keepCerts = [];
    foreach ($_POST['existing_cert'] ?? [] as $idx => $file) {
        if (!in_array($idx, $removeCert, true)) {
            $keepCerts[] = [
                'file_path' => $file,
                'title'     => trim($_POST['titles_cert'][$idx] ?? ''),
                'position'  => intval($_POST['positions_cert'][$idx] ?? $idx),
            ];
        }
    }

    $uploadDir = __DIR__.'/../../uploads/posts/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/gif' => '.gif'];

    foreach ($_FILES['image_files']['tmp_name'] ?? [] as $i => $tmp) {
        if ($_FILES['image_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $type = mime_content_type($tmp);
        if (!isset($allowed[$type])) continue;
        $fn = bin2hex(random_bytes(16)) . $allowed[$type];
        if (move_uploaded_file($tmp, $uploadDir . $fn)) {
            $keepImgs[] = [
                'file_path' => $fn,
                'caption'   => trim($_POST['new_captions_img'][$i] ?? ''),
                'position'  => count($keepImgs),
            ];
        }
    }

    foreach ($_FILES['cert_files']['tmp_name'] ?? [] as $i => $tmp) {
        if ($_FILES['cert_files']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = pathinfo($_FILES['cert_files']['name'][$i], PATHINFO_EXTENSION);
        $fn  = bin2hex(random_bytes(16)) . '.' . $ext;
        if (move_uploaded_file($tmp, $uploadDir . $fn)) {
            $keepCerts[] = [
                'file_path' => $fn,
                'title'     => trim($_POST['new_titles_cert'][$i] ?? ''),
                'position'  => count($keepCerts),
            ];
        }
    }

    if ($keepImgs)  $fields['images']       = $keepImgs;
    if ($keepCerts) $fields['certificates'] = $keepCerts;

    $upd = $api->updatePost($post_id, $fields);
    if ($upd['success']) {
        setFlashMessage('success','Post updated');
        header('Refresh:0; url=' . $_SERVER['REQUEST_URI']);
        exit;
    }
    $message = 'Error: ' . $upd['error'];
}

$title   = $_POST['title']   ?? $post['title'];
$slug    = $_POST['slug']    ?? $post['slug'];
$content = $_POST['content'] ?? $post['content'];

$renderer = new LayoutRenderer($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Post</title>
  <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
  <?php $renderer->renderNav(); ?>
  <main class="container py-4">
    <?= getFlashMessages() ?>
    <?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <div class="card shadow-sm mb-4">
      <div class="card-header"><h4 class="mb-0">Edit Post</h4></div>
      <div class="card-body">
        <form method="post" enctype="multipart/form-data" class="row g-3">
          <input type="hidden" name="post_id" value="<?= $post_id ?>">
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input name="title" class="form-control" value="<?= htmlspecialchars($title) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Slug</label>
            <input name="slug" class="form-control" value="<?= htmlspecialchars($slug) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Content (HTML)</label>
            <textarea name="content" rows="6" class="form-control"><?= htmlspecialchars($content) ?></textarea>
          </div>
          <?php if ($images): ?>
          <div class="col-12">
            <label class="form-label">Existing Images</label>
            <div id="existing-images">
              <?php foreach ($images as $idx => $img): ?>
              <div class="img-row d-flex align-items-center mb-2" data-idx="<?= $idx ?>">
                <div class="me-3">
                  <img src="<?= UPLOADS_FOLDER . 'posts/' . htmlspecialchars($img['file_path']) ?>" class="rounded" style="width:50px;height:50px;object-fit:cover;" alt="thumb">
                </div>
                <input type="hidden" name="existing_img[<?= $idx ?>]" value="<?= htmlspecialchars($img['file_path']) ?>">
                <div class="flex-grow-1 me-3">
                  <input type="text" name="captions_img[<?= $idx ?>]" class="form-control" placeholder="Caption" value="<?= htmlspecialchars($img['caption'] ?? '') ?>">
                </div>
                <div class="btn-group me-3" role="group">
                  <button type="button" class="btn btn-outline-secondary up-btn" title="Move up">↑</button>
                  <button type="button" class="btn btn-outline-secondary down-btn" title="Move down">↓</button>
                  <button type="button" class="btn btn-danger remove-row" title="Remove">–</button>
                </div>
                <input type="hidden" name="positions_img[<?= $idx ?>]" class="pos-field" value="<?= $idx ?>">
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
          <div class="col-12">
            <label class="form-label">Add New Images</label>
            <div id="new-img-rows">
              <div class="input-group mb-2 new-img-row">
                <input type="file" name="image_files[]" accept="image/*" class="form-control">
                <input type="text" name="new_captions_img[0]" placeholder="Caption" class="form-control">
                <button type="button" class="btn btn-danger remove-row">–</button>
              </div>
            </div>
            <button type="button" id="add-img-row" class="btn btn-outline-secondary">+ Add image</button>
          </div>
          <div class="col-12 text-end">
            <button type="submit" name="action" value="delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this post?');">Delete Post</button>
            <button type="submit" class="btn btn-primary">Update Post</button>
          </div>
        </form>
      </div>
    </div>
  </main>
<script>
(function(){
  function refresh(container){
    container.querySelectorAll('.img-row').forEach((row,i)=>{row.querySelector('.pos-field').value=i;});
  }
  document.addEventListener('click',e=>{
    if(e.target.matches('.up-btn')){let r=e.target.closest('.img-row'),p=r.previousElementSibling; if(p){r.parentNode.insertBefore(r,p);refresh(r.parentNode);} }
    if(e.target.matches('.down-btn')){let r=e.target.closest('.img-row'),n=r.nextElementSibling; if(n){r.parentNode.insertBefore(n,r);refresh(r.parentNode);} }
    if(e.target.matches('.remove-row')){let r=e.target.closest('.img-row, .new-img-row'),c=r.parentNode; r.remove(); refresh(c);}  
  });
  document.getElementById('add-img-row').addEventListener('click',()=>{
    const tpl=document.querySelector('.new-img-row').cloneNode(true);
    tpl.querySelector('input[type=file]').value='';tpl.querySelector('input[type=text]').value='';
    document.getElementById('new-img-rows').appendChild(tpl);
  });
})();
</script>
</body>
</html>