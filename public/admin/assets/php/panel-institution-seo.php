<?php
$institution_id = isset($_GET['institution_id']) ? (int)$_GET['institution_id'] : 1;
$seoResult = $public_api->getInstitutionSEO($institution_id);

if (!$seoResult['success']) {
    echo "<p>SEO data not found.</p>";
    exit;
}

$seo = $seoResult['data'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [];

    $posted = [
        'meta_title'       => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'meta_keywords'    => trim($_POST['meta_keywords'] ?? ''),
        'og_title'         => trim($_POST['og_title'] ?? ''),
        'og_description'   => trim($_POST['og_description'] ?? ''),
        'og_image_url'     => trim($_POST['og_image_url'] ?? ''),
        'og_type'          => trim($_POST['og_type'] ?? ''),
        'canonical_url'    => trim($_POST['canonical_url'] ?? ''),
        'robots_index'     => isset($_POST['robots_index']) ? 1 : 0,
        'robots_follow'    => isset($_POST['robots_follow']) ? 1 : 0,
        'json_ld'          => trim($_POST['json_ld'] ?? ''),
    ];

    foreach ($posted as $key => $value) {
        if (!array_key_exists($key, $seo) || $value !== (string)($seo[$key] ?? '')) {
            $fields[$key] = $value;
        }
    }

    if (!empty($fields)) {
        $result = $api->updateInstitutionSEO($institution_id, $fields);
        if ($result['success']) {
            $message = "SEO updated successfully.";
            $seoResult = $public_api->getInstitutionSEO($institution_id);
            $seo = $seoResult['data'];
        } else {
            $message = "Error: " . $result['error'];
        }
    } else {
        $message = "No changes to update.";
    }
}
?>
<div class="card-header">
    <h4 class="mb-0">Edit SEO Settings</h4>
</div>
<div class="card-body">
    <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <form method="post" class="row g-3">
        <input type="hidden" name="institution_id" value="<?= $institution_id ?>">

        <div class="col-md-6">
            <label class="form-label">Meta Title</label>
            <input type="text" name="meta_title" class="form-control form-control-sm"
                value="<?= htmlspecialchars($seo['meta_title'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Meta Description</label>
            <textarea name="meta_description" rows="2" maxlength="160"
                class="form-control form-control-sm"><?= htmlspecialchars($seo['meta_description'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <label class="form-label">Meta Keywords</label>
            <input type="text" name="meta_keywords" class="form-control form-control-sm"
                value="<?= htmlspecialchars($seo['meta_keywords'] ?? '') ?>">
        </div>

        <div class="col-md-6">
            <label class="form-label">OG Title</label>
            <input type="text" name="og_title" class="form-control form-control-sm"
                value="<?= htmlspecialchars($seo['og_title'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label">OG Description</label>
            <textarea name="og_description" rows="2" maxlength="160"
                class="form-control form-control-sm"><?= htmlspecialchars($seo['og_description'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">OG Image URL</label>
            <input type="url" name="og_image_url" class="form-control form-control-sm"
                value="<?= htmlspecialchars($seo['og_image_url'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">OG Type</label>
            <input type="text" name="og_type" class="form-control form-control-sm"
                value="<?= htmlspecialchars($seo['og_type'] ?? '') ?>">
        </div>

        <div class="col-12">
            <label class="form-label">Canonical URL</label>
            <input type="url" name="canonical_url" class="form-control form-control-sm"
                value="<?= htmlspecialchars($seo['canonical_url'] ?? '') ?>">
        </div>

        <div class="col-6 form-check">
            <input class="form-check-input" type="checkbox" id="robots_index" name="robots_index"
                <?= !empty($seo['robots_index']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="robots_index">Index</label>
        </div>
        <div class="col-6 form-check">
            <input class="form-check-input" type="checkbox" id="robots_follow" name="robots_follow"
                <?= !empty($seo['robots_follow']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="robots_follow">Follow</label>
        </div>

        <div class="col-12">
            <label class="form-label">Custom JSON-LD</label>
            <textarea name="json_ld" rows="4" class="form-control form-control-sm"><?= htmlspecialchars($seo['json_ld'] ?? '') ?></textarea>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary btn-sm">Save SEO</button>
        </div>
    </form>
</div>
