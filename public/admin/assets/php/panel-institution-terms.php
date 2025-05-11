<?php
$identifier = $_GET['page'] ?? 'terms';
$message    = '';

$pageResult = $public_api->getPage(is_numeric($identifier) ? (int)$identifier : $identifier);
if (!$pageResult['success']) {
    echo '<p>Page not found.</p>'; 
    return;
}
$page = $pageResult['data'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [];

    $posted_title   = trim($_POST['title']   ?? '');
    $posted_content = trim($_POST['content'] ?? '');

    if ($posted_title !== $page['title']) {
        $fields['title'] = $posted_title;
    }
    if ($posted_content !== $page['content']) {
        $fields['content'] = $posted_content;
    }

    if (!empty($fields)) {
        $res = $api->updatePage($page['id'], $fields);
        if ($res['success']) {
            $message = 'Page updated successfully.';
            $page    = $public_api->getPage($page['id'])['data'];
        } else {
            $message = 'Error: ' . $res['error'];
        }
    } else {
        $message = 'No changes to save.';
    }
}
?>
<div class="card-header">
  <h4 class="mb-0">Edit Regulations</h4>
</div>
<div class="card-body">
  <?php if ($message): ?>
    <div class="alert alert-info alert-sm"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>

  <form method="post" class="row g-3">
    <?php if (is_numeric($identifier)): ?>
      <input type="hidden" name="page" value="<?= (int)$identifier ?>">
    <?php else: ?>
      <input type="hidden" name="page" value="<?= htmlspecialchars($identifier) ?>">
    <?php endif; ?>

    <div class="col-12">
      <label for="title" class="form-label">Page Title</label>
      <input
        type="text"
        id="title"
        name="title"
        class="form-control form-control-sm"
        value="<?= htmlspecialchars($page['title']) ?>"
      >
    </div>

    <div class="col-12">
      <label for="content" class="form-label">Content (HTML)</label>
      <textarea
        id="content"
        name="content"
        rows="10"
        class="form-control form-control-sm"
      ><?= htmlspecialchars($page['content']) ?></textarea>
    </div>

    <div class="col-12 text-end mt-3">
      <button type="submit" class="btn btn-primary btn-sm">Save Regulations</button>
    </div>
  </form>
</div>