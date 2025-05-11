<?php
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/assets/api/db_connect.php';
require_once __DIR__ . '/../app/assets/api/public_api/public_api.php';
require_once __DIR__ . '/assets/php/elements/layout_menager.php';

use Api\PublicAPI\PublicAPI;
use Database\Database;
use Assets\LayoutRenderer;

session_start();

$database   = new Database();
$connection = $database->getConnection();
if ($connection === false) {
    die("Error connecting to the database.");
}

$layout = new LayoutRenderer($connection);

$public_api = new PublicAPI($connection);

// Fetch institution
$instRes = $public_api->getInstitution();
if (!$instRes['success']) {
    die("Error fetching institution data: " . $instRes['error']);
}
$institution = $instRes['institution'];

// Fetch courses & certificates
$coursesRes = $public_api->getCourses();
if (!$coursesRes['success']) {
    die("Error fetching courses: " . $coursesRes['error']);
}
$courses = $coursesRes['courses'];

$certRes = $public_api->getCertificates();
if (!$certRes['success']) {
    die("Error fetching certificates: " . $certRes['error']);
}
$certificates = $certRes['certificates'];

// Fetch news posts
$postsRes = $public_api->getPosts();
if (!$postsRes['success']) {
    die("Error fetching news: " . $postsRes['error']);
}
$posts = $postsRes['data'];

// Group certificates by course
$certsByCourse = [];
foreach ($certificates as $cert) {
    if (!empty($cert['course_id'])) {
        $certsByCourse[$cert['course_id']][] = $cert;
    }
}

// SEO & meta
$title       = htmlspecialchars($institution['meta_title']       ?? $institution['name']);
$description = htmlspecialchars($institution['meta_description'] ?? substr(strip_tags($institution['description'] ?? ''), 0, 160));
$canonical   = htmlspecialchars(HTTP_ADRESS);
$robots      = (!empty($institution['robots_index']) ? 'index' : 'noindex')
             . ', '
             . (!empty($institution['robots_follow']) ? 'follow' : 'nofollow');
$ogTitle     = htmlspecialchars($institution['og_title']       ?? $title);
$ogDesc      = htmlspecialchars($institution['og_description'] ?? $description);
$ogImage     = !empty($institution['og_image_url'])
             ? htmlspecialchars(HTTP_ADRESS . 'uploads/institutions/' . $institution['og_image_url'])
             : '';
$ogType      = htmlspecialchars($institution['og_type'] ?? 'website');
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- global head -->
    <?php $layout->renderHead() ?>

    <title><?= $title ?></title>
    <meta name="description" content="<?= $description ?>">
    <link rel="canonical" href="<?= $canonical ?>">
    <meta name="robots" content="<?= $robots ?>">

    <!-- OpenGraph -->
    <meta property="og:type"        content="<?= $ogType ?>">
    <meta property="og:title"       content="<?= $ogTitle ?>">
    <meta property="og:description" content="<?= $ogDesc ?>">
    <?php if ($ogImage): ?>
    <meta property="og:image"       content="<?= $ogImage ?>">
    <?php endif; ?>
    <meta property="og:url"         content="<?= $canonical ?>">

    <style>
    .profile-banner {
        width:100%; height:200px;
        background:center/cover no-repeat;
        border-radius:.25rem; position:relative;
    }
    @media(max-width:576px){ .profile-banner{height:150px;} }
    .profile-avatar {
        position:absolute; bottom:-75px; left:50%;
        transform:translateX(-50%);
        width:150px; height:150px;
        border:5px solid #fff; border-radius:50%;
        background-color:#fff;
    }
    .profile-content {
        max-width:600px; margin:80px auto 0; text-align:center;
    }
    .desc-text{display:inline;text-align:justify;}
    .desc-toggle{cursor:pointer;text-decoration:none;margin-left:4px;}
    html[data-bs-theme="dark"] .profile-avatar {
        border-color:var(--bs-body-bg)!important;
        background-color:var(--bs-body-bg)!important;
    }
    </style>
</head>
<body class="bg-body text-body">
    <?php $layout->renderNav() ?>

    <main class="container py-4">
        <!-- PROFILE -->
        <section id="profile" class="mb-5">
            <?php if (!empty($institution['banner_url'])): ?>
            <div class="profile-banner mb-3"
                 style="background-image:url('<?= UPLOADS_FOLDER . 'institutions/' . htmlspecialchars($institution['banner_url']) ?>')">
                <?php if (!empty($institution['profile_image_url'])): ?>
                <img src="<?= UPLOADS_FOLDER . 'institutions/' . htmlspecialchars($institution['profile_image_url']) ?>"
                     alt="<?= htmlspecialchars($institution['name']) ?>"
                     class="profile-avatar">
                <?php endif; ?>
            </div>
            <?php elseif (!empty($institution['profile_image_url'])): ?>
            <div class="text-center mb-3">
                <img src="<?= UPLOADS_FOLDER . 'institutions/' . htmlspecialchars($institution['profile_image_url']) ?>"
                     alt="<?= htmlspecialchars($institution['name']) ?>"
                     class="rounded-circle"
                     style="width:150px;height:150px;border:5px solid #fff;">
            </div>
            <?php endif; ?>

            <div class="profile-content">
                <h1 class="h3 mb-2"><?= htmlspecialchars($institution['name']) ?></h1>
                <?php
                    $raw = nl2br(htmlspecialchars($institution['description'] ?? ''));
                    $rawText = strip_tags($raw);
                    $limit = 200;
                ?>
                <p data-limit="<?= $limit ?>" class="toggle-wrapper">
                <span class="short-text"></span>
                <span class="full-text d-none"><?= $raw ?></span>
                <a href="#" class="toggle-text d-none"></a>
                </p>
                <ul class="list-inline mt-3">
                    <?php if (!empty($institution['contact_email'])): ?>
                    <li class="list-inline-item me-3">
                        <i class="bi bi-envelope-fill"></i>
                        <a href="mailto:<?= htmlspecialchars($institution['contact_email']) ?>">
                            <?= htmlspecialchars($institution['contact_email']) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($institution['phone_number'])): ?>
                    <li class="list-inline-item me-3">
                        <i class="bi bi-telephone-fill"></i>
                        <?= htmlspecialchars($institution['phone_number']) ?>
                    </li>
                    <?php endif; ?>
                    <?php if (!empty($institution['website_url'])): ?>
                    <li class="list-inline-item">
                        <i class="bi bi-globe2"></i>
                        <a href="<?= htmlspecialchars($institution['website_url']) ?>" target="_blank">
                            <?= htmlspecialchars($institution['website_url']) ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </section>

        <!-- TABS -->
        <ul class="nav nav-pills mb-4" id="main-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="courses-tab" data-bs-toggle="pill" data-bs-target="#courses-pane" type="button">Courses</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="certs-tab" data-bs-toggle="pill" data-bs-target="#certs-pane" type="button">Certificates</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="news-tab" data-bs-toggle="pill" data-bs-target="#news-pane" type="button">News</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- COURSES -->
            <div class="tab-pane fade show active" id="courses-pane">
                <div class="row gx-4">
                    <aside class="col-md-3 mb-3">
                        <div class="list-group rounded">
                            <?php foreach ($courses as $course): ?>
                            <a href="#course-<?= $course['id'] ?>" class="list-group-item list-group-item-action">
                                <?= htmlspecialchars($course['title']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                    <div class="col-md-9">
                        <?php foreach ($courses as $course): ?>
                        <div id="course-<?= $course['id'] ?>" class="card mb-3 rounded">
                            <div class="card-body d-flex">
                                <div class="flex-shrink-0 me-3"><i class="bi bi-journal-code h1"></i></div>
                                <div class="w-100">
                                    <h5 class="card-title"><?= htmlspecialchars($course['title']) ?></h5>
                                    <?php
                                    $d = htmlspecialchars($course['description']);
                                    if (strlen($d) > 100): $s = substr($d, 0, 100);
                                    ?>
                                        <p><?= $s ?>... <a href="#" data-bs-toggle="collapse" data-bs-target="#course-full-<?= $course['id'] ?>">Show More</a></p>
                                        <div id="course-full-<?= $course['id'] ?>" class="collapse"><p><?= nl2br($d) ?></p></div>
                                    <?php else: ?>
                                        <p><?= nl2br($d) ?></p>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        Author: <?= htmlspecialchars($course['course_author']) ?>
                                        &middot; Created: <?= date('Y-m-d', strtotime($course['created_at'])) ?>
                                    </small>
                                    <?php if (!empty($certsByCourse[$course['id']])): ?>
                                    <div class="mt-2">
                                        <small>Related certificates:
                                        <?php foreach ($certsByCourse[$course['id']] as $c): ?>
                                            <a href="#" class="js-link-cert text-decoration-none" data-id="<?= $c['id'] ?>">
                                                <?= htmlspecialchars($c['title']) ?>
                                            </a><?= end($certsByCourse[$course['id']]) !== $c ? ', ' : '' ?>
                                        <?php endforeach; ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- CERTIFICATES -->
            <div class="tab-pane fade" id="certs-pane">
                <div class="row gx-4">
                    <aside class="col-md-3 mb-3">
                        <div class="list-group rounded">
                            <?php foreach ($certificates as $cert): ?>
                            <a href="#cert-<?= $cert['id'] ?>" class="list-group-item list-group-item-action">
                                <?= htmlspecialchars($cert['title']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </aside>
                    <div class="col-md-9">
                        <?php foreach ($certificates as $cert): ?>
                        <div id="cert-<?= $cert['id'] ?>" class="card mb-3 rounded">
                            <div class="card-body d-flex">
                                <?php if (!empty($cert['certificate_image_path'])): ?>
                                <img src="<?= HTTP_ADRESS . 'uploads/certificates/' . htmlspecialchars($cert['certificate_image_path']) ?>"
                                     alt="<?= htmlspecialchars($cert['title']) ?>"
                                     class="img-thumbnail me-3" style="width:80px;height:80px;object-fit: cover;">
                                <?php else: ?>
                                <div class="bg-secondary text-white d-flex align-items-center justify-content-center me-3"
                                     style="width:80px;height:80px;border-radius:.25rem;">
                                    <i class="bi bi-award h2"></i>
                                </div>
                                <?php endif; ?>
                                <div class="w-100">
                                    <h5 class="card-title"><?= htmlspecialchars($cert['title']) ?></h5>
                                    <?php
                                    $d = htmlspecialchars($cert['description']);
                                    if (strlen($d) > 100): $s = substr($d, 0, 100);
                                    ?>
                                        <p><?= $s ?>... <a href="#" data-bs-toggle="collapse" data-bs-target="#cert-full-<?= $cert['id'] ?>">Show More</a></p>
                                        <div id="cert-full-<?= $cert['id'] ?>" class="collapse"><p><?= nl2br($d) ?></p></div>
                                    <?php else: ?>
                                        <p><?= nl2br($d) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($cert['course_id'])):
                                        $parentArr = array_filter($courses, fn($c) => $c['id'] === $cert['course_id']);
                                        $parent    = array_shift($parentArr);
                                        if ($parent):
                                    ?>
                                    <div class="mt-2">
                                        <small>Related course:
                                            <a href="#" class="js-link-course text-decoration-none" data-id="<?= $parent['id'] ?>">
                                                <?= htmlspecialchars($parent['title']) ?>
                                            </a>
                                        </small>
                                    </div>
                                    <?php endif; endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- NEWS -->
            <div class="tab-pane fade" id="news-pane">
                <div class="row gx-4">
                    <aside class="col-md-3 mb-3">
                        <div class="list-group rounded">
                            <?php foreach ($posts as $post): ?>
                                <a href="#post-<?= $post['id'] ?>" class="list-group-item list-group-item-action">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </aside>

                    <div class="col-md-9">
                        <?php foreach ($posts as $post): ?>
                            <div id="post-<?= $post['id'] ?>" class="card mb-4 rounded post-toggle" data-limit="300">
                                <div class="card-body p-3">
                                    <h4 class="card-title mb-1"><?= htmlspecialchars($post['title']) ?></h4>
                                    <small class="text-muted d-block mb-2">
                                    <?= date('Y-m-d', strtotime($post['created_at'])) ?>
                                    </small>

                                    <div class="full-text d-none">
                                    <?= nl2br(htmlspecialchars(strip_tags($post['content']))) ?>
                                    </div>
                                    <div class="short-text"></div>
                                    <a href="#" class="toggle-text d-none"></a>
                                </div>

                                <?php if (!empty($post['images'])): ?>
                                    <div class="position-relative p-0">
                                        <img src="<?= UPLOADS_FOLDER . 'posts/' . htmlspecialchars($post['images'][0]['file_path']) ?>"
                                            class="w-100" alt="<?= htmlspecialchars($post['images'][0]['caption']) ?>">

                                        <?php if (count($post['images']) > 1): ?>
                                            <button class="btn btn-primary btn-sm rounded-pill position-absolute bottom-0 start-50 translate-middle-x mb-2 toggle-images"
                                                    type="button"
                                                    data-bs-toggle="collapse"
                                                    data-bs-target="#images-<?= $post['id'] ?>"
                                                    aria-expanded="false"
                                                    aria-controls="images-<?= $post['id'] ?>"
                                                    data-text-more="More photos"
                                                    data-text-less="Less photos">
                                                More photos
                                            </button>
                                            <div class="collapse" id="images-<?= $post['id'] ?>">
                                                <?php foreach (array_slice($post['images'], 1) as $img): ?>
                                                    <img src="<?= UPLOADS_FOLDER . 'posts/' . htmlspecialchars($img['file_path']) ?>"
                                                        class="w-100 mb-2" alt="<?= htmlspecialchars($img['caption']) ?>">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div> 
                        <?php endforeach; ?>
                    </div> 
                </div> 
            </div> 
        </div>
    </main>
    <?php $layout->renderFooter() ?>

    <script type="module" src="<?= HTTP_ADRESS ?>assets/js/toggle-text.js"></script>
    <script>
    // smooth scroll for list links
    document.querySelectorAll('.js-link-cert').forEach(el => el.addEventListener('click', e => {
        e.preventDefault();
        new bootstrap.Tab(document.querySelector('#certs-tab')).show();
        setTimeout(() => document.querySelector('#cert-' + e.currentTarget.dataset.id).scrollIntoView({behavior:'smooth'}), 200);
    }));
    document.querySelectorAll('.js-link-course').forEach(el => el.addEventListener('click', e => {
        e.preventDefault();
        new bootstrap.Tab(document.querySelector('#courses-tab')).show();
        setTimeout(() => document.querySelector('#course-' + e.currentTarget.dataset.id).scrollIntoView({behavior:'smooth'}), 200);
    }));
    document.addEventListener('DOMContentLoaded', function() {
        const hash = window.location.hash;
        let tabBtn, targetId;

        if (hash.startsWith('#cert-')) {
        tabBtn   = document.querySelector('#certs-tab');
        targetId = hash;
        } 
        else if (hash.startsWith('#course-')) {
        tabBtn   = document.querySelector('#courses-tab');
        targetId = hash;
        } 
        else if (hash.startsWith('#post-')) {
        tabBtn   = document.querySelector('#news-tab');
        targetId = hash;
        }

        if (tabBtn && targetId) {
        new bootstrap.Tab(tabBtn).show();

        setTimeout(() => {
            const target = document.querySelector(targetId);
            if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
            }
        }, 200);
        }
    });
    </script>
</body>
</html>
