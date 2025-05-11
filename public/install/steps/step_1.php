<?php

function safe_file_put_contents(string $path, string $content): bool {
    if (!is_writable($path)) {
        @chmod($path, 0664);
        if (!is_writable($path)) {
            return false;
        }
    }
    return (file_put_contents($path, $content) !== false);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appDomain = filter_input(INPUT_POST, 'app_domain', FILTER_SANITIZE_SPECIAL_CHARS);

    if (!$appDomain) {
        $error = "Domain address is required.";
    }

    if (!isset($error)) {
        $newPepper = bin2hex(random_bytes(16));
        $newDomain = rtrim($appDomain, '/') . '/';
        $configContent = file_get_contents($configFilePath);
        if ($configContent === false) {
            $error = "Failed to read config.php file.";
        } else {
            $configContent = preg_replace(
                "/define\(\s*'PEPPER'\s*,\s*'[^']*'\s*\);/",
                "define('PEPPER', '" . $newPepper . "');",
                $configContent
            );
            $configContent = preg_replace(
                "/define\(\s*'HTTP_ADRESS'\s*,\s*'[^']*'\s*\);/",
                "define('HTTP_ADRESS', '" . $newDomain . "');",
                $configContent
            );
            if (!safe_file_put_contents($configFilePath, $configContent)) {
                $error = "Error writing config.php file. Check filesystem permissions.";
            } else {
                redirect(2);
            }
        }
    }
}
?>
<div class="mb-4">
    <h5 class="mb-0">Step 1 of 3: Application Configuration</h5>
</div>
<?php if (isset($error)): ?>
    <?php showError($error); ?>
<?php endif; ?>
<form method="post" class="row g-3">
    <div class="col-12">
        <label for="app_domain" class="form-label">Domain or IP Address</label>
        <input
            type="text"
            id="app_domain"
            name="app_domain"
            class="form-control form-control-sm"
            placeholder="http://localhost/YourCert/public/"
            required
        >
    </div>
    <div class="col-12 d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary btn-sm">
            Save Configuration
        </button>
    </div>
</form>
