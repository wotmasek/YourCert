<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/courses/courses.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\UserAPI\CourseMenagment;
use Api\UserAPI\UserMenagment;
use Assets\LayoutRenderer;

function isUserAdmin($conn) {
    if (!isset($_SESSION['userID'])) return false;
    $api = new UserMenagment($_SESSION['userID'], $conn);
    $perm = $api->getUserPermissions();
    return ($perm['success'] && isset($perm['permissions']['name']) && $perm['permissions']['name'] === 'Administrator');
}

$database   = new Database();
$connection = $database->getConnection();
if (!isUserAdmin($connection)) {
    header('Location:' . LOGIN_PAGE);
    exit;
}

$courseApi = new CourseMenagment($_SESSION['userID'], $connection);
$publicApi = new PublicAPI($connection);

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
if ($course_id <= 0) {
    echo '<div class="container py-3"><div class="alert alert-danger">Invalid course ID.</div></div>';
    exit;
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = $publicApi->getCourse($course_id);
    if (!$res['success']) {
        $message = 'Course not found.';
    } else {
        $course = $res['course'];
        if (isset($_POST['update'])) {
            $fields = [];
            $title  = trim($_POST['title'] ?? '');
            $desc   = trim($_POST['description'] ?? '');
            $author = trim($_POST['course_author'] ?? '');
            if ($title !== $course['title'])            $fields['title'] = $title;
            if ($desc  !== $course['description'])      $fields['description'] = $desc;
            if ($author!== $course['course_author'])    $fields['course_author'] = $author;
            if ($fields) {
                $upd = $courseApi->updateCourse($course_id, $fields);
                $message = $upd['success'] ? 'Course updated.' : 'Error: ' . $upd['error'];
            } else {
                $message = 'No changes to update.';
            }
        }
        if (isset($_POST['delete'])) {
            $del = $courseApi->deleteCourse($course_id);
            if ($del['success']) {
                header('Location: ' . ADMIN_PANEL);
                exit;
            } else {
                $message = 'Error: ' . $del['error'];
            }
        }
    }
}

$res = $publicApi->getCourse($course_id);
if (!$res['success']) {
    echo '<div class="container py-3"><div class="alert alert-danger">Course not found.</div></div>';
    exit;
}
$course = $res['course'];

$renderer = new LayoutRenderer($connection);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Course</title>
  <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
  <?php $renderer->renderNav(); ?>
  <main>
    <div class="container py-3">
      <div class="card mb-4">
        <div class="card-header">
          <h4 class="mb-0">Edit Course</h4>
        </div>
        <div class="card-body">
          <?php if ($message): ?>
            <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
          <form method="post" class="row g-3">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <div class="col-12">
              <label for="title" class="form-label">Title</label>
              <input
                type="text"
                id="title"
                name="title"
                class="form-control form-control-sm"
                value="<?= htmlspecialchars($course['title'] ?? '') ?>"
              >
            </div>
            <div class="col-12">
              <label for="description" class="form-label">Description</label>
              <textarea
                id="description"
                name="description"
                rows="4"
                class="form-control form-control-sm"
              ><?= htmlspecialchars($course['description'] ?? '') ?></textarea>
            </div>
            <div class="col-12 col-md-6">
              <label for="course_author" class="form-label">Course Author</label>
              <input
                type="text"
                id="course_author"
                name="course_author"
                class="form-control form-control-sm"
                value="<?= htmlspecialchars($course['course_author'] ?? '') ?>"
              >
            </div>
            <div class="col-12 d-flex justify-content-end mt-3">
              <button
                type="submit"
                name="delete"
                class="btn btn-danger btn-sm me-2"
                onclick="return confirm('Are you sure you want to delete this course?');"
              >
                Delete
              </button>
              <button type="submit" name="update" class="btn btn-primary btn-sm">
                Update
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
