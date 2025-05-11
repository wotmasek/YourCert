<?php
session_start();

require_once __DIR__ . '/../../app/config.php';
require_once __DIR__ . '/../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/../../app/assets/api/system_api/system_api.php';
require_once __DIR__ . '/../../app/assets/api/user_apis/apis/assigned_certificates/assigned_certificates.php';
require_once __DIR__ . '/../../app/assets/api/user_apis/apis/institution/institution.php';
require_once __DIR__ . '/../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../app/assets/flash_messages.php';
require_once __DIR__ . '/../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\PublicAPI\PublicAPI;
use Api\SystemAPI\SystemAPI;
use Api\UserAPI\UserMenagment;
use Api\UserAPI\AssignedCertificatesMenagment;
use Api\UserAPI\InstitutionMenagment;
use Assets\LayoutRenderer;

function isUserAdmin($conn) {
    if (!isset($_SESSION['userID'])) return false;
    $userApi = new UserMenagment($_SESSION['userID'], $conn);
    $perm    = $userApi->getUserPermissions();
    return $perm['success'] === true 
        && isset($perm['permissions']['name']) 
        && $perm['permissions']['name'] === 'Administrator';
}

$database   = new Database();
$connection = $database->getConnection();

if (!isUserAdmin($connection)) {
    header('Location:' . LOGIN_PAGE);
    exit();
}

$renderer = new LayoutRenderer($connection);

$data_type = $_GET['data_type'] ?? 'users';
$filters   = $_GET;
unset($filters['data_type']);

$public_api = new PublicAPI($connection);

switch ($data_type) {
    case 'users':
        $api         = new UserMenagment($_SESSION['userID'], $connection);
        $result      = $api->getUsers(50, 0, $filters);
        $includeFile = 'panel-users-data.php';
        $heading     = 'Users Management';
        break;
    case 'courses':
        $result      = $public_api->getCourses(50, 0, $filters);
        $includeFile = 'panel-courses-data.php';
        $heading     = 'Courses Management';
        break;
    case 'certificates':
        $result      = $public_api->getCertificates(50, 0, $filters);
        $includeFile = 'panel-certificates-data.php';
        $heading     = 'Certificates Management';
        break;
    case 'assigned_certificates':
        $api         = new AssignedCertificatesMenagment($_SESSION['userID'], $connection);
        $result      = $api->getAssignedCertificates(50, 0, $filters);
        $includeFile = 'panel-assigned_certificates-data.php';
        $heading     = 'Assigned Certificates';
        break;
    case 'posts':
        $result      = $public_api->getPosts(50, 0, $filters);
        $includeFile = 'panel-posts-data.php';
        $heading     = 'Posts Management';
        break;
    case 'institution_data':
        $api         = new InstitutionMenagment($_SESSION['userID'], $connection);
        $result      = $public_api->getInstitution($_SESSION['userID'], $filters['institution_id'] ?? 1);
        $includeFile = 'panel-institution-data.php';
        $heading     = 'Institution Data';
        break;
    case 'institution_seo':
        $api         = new InstitutionMenagment($_SESSION['userID'], $connection);
        $result      = $public_api->getInstitutionSEO((int)($filters['institution_id'] ?? 1));
        $includeFile = 'panel-institution-seo.php';
        $heading     = 'SEO Management';
        break;
    case 'page_regulamin':
        $api         = new InstitutionMenagment($_SESSION['userID'], $connection);
        $result      = $public_api->getPage('terms');
        $includeFile = 'panel-institution-terms.php';
        $heading     = 'Regulations';
        break;
    default:
        $heading     = 'Unknown Data Type';
        $result      = ['success' => false, 'error' => 'Invalid data type'];
        $includeFile = '';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Panel – <?= htmlspecialchars($heading) ?></title>
    <?php $renderer->renderHead(); ?>
</head>
<body class="bg-body text-body">
    <?php $renderer->renderNav(); ?>
    <main class="py-4">
        <div class="container">
            <div class="d-flex flex-column flex-md-row">
                <button class="btn btn-sm mb-2 d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarNav" aria-expanded="false" aria-controls="sidebarNav">
                    <i class="bi bi-list"></i> Menu
                </button>

                <div class="collapse d-md-block flex-shrink-0 mb-3" id="sidebarNav" style="width: 260px;">
                    <div class="rounded">
                        <div class="accordion" id="sidebarAccordion">
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingUsers">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers" aria-expanded="false" aria-controls="collapseUsers">
                                        <i class="bi bi-people me-2"></i> User Management
                                    </button>
                                </h2>
                                <div id="collapseUsers" class="accordion-collapse collapse <?=in_array($data_type, ['users', 'assigned_certificates'])?'show':''?>" aria-labelledby="headingUsers" data-bs-parent="#sidebarAccordion">
                                    <div class="accordion-body p-0">
                                        <nav class="nav flex-column">
                                            <a class="nav-link <?= $data_type=='users'?'active':'' ?> ps-4" href="?data_type=users">
                                                <i class="bi bi-person me-2"></i> Users
                                            </a>
                                            <a class="nav-link <?= $data_type=='assigned_certificates'?'active':'' ?> ps-4" href="?data_type=assigned_certificates">
                                                <i class="bi bi-check2-circle me-2"></i> Assigned Certificates
                                            </a>
                                        </nav>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingCourses">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCourses" aria-expanded="false" aria-controls="collapseCourses">
                                        <i class="bi bi-journal-bookmark me-2"></i> Courses & Certificates
                                    </button>
                                </h2>
                                <div id="collapseCourses" class="accordion-collapse collapse <?=in_array($data_type, ['courses','certificates'])?'show':''?>" aria-labelledby="headingCourses" data-bs-parent="#sidebarAccordion">
                                    <div class="accordion-body p-0">
                                        <nav class="nav flex-column">
                                            <a class="nav-link <?= $data_type == 'courses' ? 'active' : '' ?> ps-4" href="?data_type=courses">
                                                <i class="bi bi-journal-bookmark me-2"></i> Courses
                                            </a>
                                            <a class="nav-link <?= $data_type=='certificates'?'active':'' ?> ps-4" href="?data_type=certificates">
                                                <i class="bi bi-award me-2"></i> Certificates
                                            </a>
                                        </nav>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingContent">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContent" aria-expanded="false" aria-controls="collapseContent">
                                        <i class="bi bi-file-text me-2"></i> Content
                                    </button>
                                </h2>
                                <div id="collapseContent" class="accordion-collapse collapse <?=($data_type=='posts')?'show':''?>" aria-labelledby="headingContent" data-bs-parent="#sidebarAccordion">
                                    <div class="accordion-body p-0">
                                        <nav class="nav flex-column">
                                            <a class="nav-link <?= $data_type=='posts'?'active':'' ?> ps-4" href="?data_type=posts">
                                                <i class="bi bi-file-text me-2"></i> Posts
                                            </a>
                                        </nav>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="headingInstitution">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInstitution" aria-expanded="false" aria-controls="collapseInstitution">
                                        <i class="bi bi-building me-2"></i> Institution Settings
                                    </button>
                                </h2>
                                <div id="collapseInstitution" class="accordion-collapse collapse <?=in_array($data_type,['institution_data','institution_seo','page_regulamin'])?'show':''?>" aria-labelledby="headingInstitution" data-bs-parent="#sidebarAccordion">
                                    <div class="accordion-body p-0">
                                        <nav class="nav flex-column">
                                            <a class="nav-link <?= $data_type=='institution_data'?'active':'' ?> ps-4" href="?data_type=institution_data">
                                                <i class="bi bi-building me-2"></i> Institution Data
                                            </a>
                                            <a class="nav-link <?= $data_type=='institution_seo'?'active':'' ?> ps-4" href="?data_type=institution_seo">
                                                <i class="bi bi-graph-up me-2"></i> SEO Management
                                            </a>
                                            <a class="nav-link <?= $data_type=='page_regulamin'?'active':'' ?> ps-4" href="?data_type=page_regulamin">
                                                <i class="bi bi-file-earmark-text me-2"></i> Regulations
                                            </a>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>

                <div class="flex-grow-1 ms-md-4">
                    <div>
                        <?= getFlashMessages() ?>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-body p-3">
                            <?php if ($includeFile): ?>
                                <?php include __DIR__ . "/assets/php/" . $includeFile; ?>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">No data to display.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>