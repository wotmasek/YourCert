<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/courses/courses.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\UserAPI\CourseMenagment;
use Api\UserAPI\UserMenagment;
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
$courseApi = new CourseMenagment($_SESSION['userID'], $connection);
$message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $posted_title       = trim($_POST['title'] ?? '');
    $posted_description = trim($_POST['description'] ?? '');
    $posted_author      = trim($_POST['course_author'] ?? '');

    $createResult = $courseApi->createCourse(
        $posted_title,
        $posted_description,
        $posted_author
    );

    if ($createResult['success']) {
        setFlashMessage('success', 'Course created successfully. ID: ' . $createResult['course_id']);
        header('Location: ' . ADMIN_PANEL . '?data_type=courses');
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
    <title>Create New Course</title>
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
                    <h4 class="mb-0">Create New Course</h4>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <div class="col-12">
                            <label for="title" class="form-label">Course Title</label>
                            <input
                                type="text"
                                id="title"
                                name="title"
                                class="form-control form-control-sm"
                                value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                            >
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Course Description</label>
                            <textarea
                                id="description"
                                name="description"
                                rows="4"
                                class="form-control form-control-sm"
                            ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="course_author" class="form-label">Course Author</label>
                            <input
                                type="text"
                                id="course_author"
                                name="course_author"
                                class="form-control form-control-sm"
                                value="<?= htmlspecialchars($_POST['course_author'] ?? '') ?>"
                            >
                        </div>
                        <div class="col-12 d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">Create Course</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
