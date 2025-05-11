<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost     = filter_input(INPUT_POST, 'db_host', FILTER_SANITIZE_SPECIAL_CHARS);
    $dbName     = filter_input(INPUT_POST, 'db_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $dbUser     = filter_input(INPUT_POST, 'db_user', FILTER_SANITIZE_SPECIAL_CHARS);
    $dbPassword = filter_input(INPUT_POST, 'db_pass', FILTER_SANITIZE_SPECIAL_CHARS);
    if (!$dbHost || !$dbName || !$dbUser) {
        $error = "All fields (host, database name, user) are required.";
    }
    if (!isset($error)) {
        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $error = "Database connection error: " . $e->getMessage();
        }
        if (!isset($error)) {
            $dbConfigContent = file_get_contents($dbConfigFilePath);
            if ($dbConfigContent === false) {
                $error = "Failed to read db_config.php file.";
            } else {
                $dbConfigContent = preg_replace("/('host'\\s*=>\\s*')[^']*(')/", "$1" . addslashes($dbHost) . "$2", $dbConfigContent);
                $dbConfigContent = preg_replace("/('db_name'\\s*=>\\s*')[^']*(')/", "$1" . addslashes($dbName) . "$2", $dbConfigContent);
                $dbConfigContent = preg_replace("/('username'\\s*=>\\s*')[^']*(')/", "$1" . addslashes($dbUser) . "$2", $dbConfigContent);
                $dbConfigContent = preg_replace("/('password'\\s*=>\\s*')[^']*(')/", "$1" . addslashes($dbPassword) . "$2", $dbConfigContent);
                
                if (file_put_contents($dbConfigFilePath, $dbConfigContent) === false) {
                    $error = "Error writing db_config.php file.";
                } else {
                    if (!file_exists($sqlFilePath)) {
                        $error = "db.sql file not found: $sqlFilePath";
                    } else {
                        $sqlCommands = file_get_contents($sqlFilePath);
                        try {
                            $pdo->exec($sqlCommands);
                        } catch (PDOException $e) {
                            $error = "Error executing SQL script: " . $e->getMessage();
                        }
                    }
                    if (!isset($error)) {
                        redirect(3);
                    }
                }
            }
        }
    }
}
?>
<div class="mb-4">
    <h5 class="mb-0">Step 2 of 4: Database Configuration</h5>
</div>
<?php if (isset($error)): ?>
    <?php showError($error); ?>
<?php endif; ?>
<form method="post" class="row g-3">
    <div class="col-12 col-md-6">
        <label for="db_host" class="form-label">Database Host</label>
        <input
            type="text"
            id="db_host"
            name="db_host"
            class="form-control form-control-sm"
            placeholder="localhost"
            value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="db_name" class="form-label">Database Name</label>
        <input
            type="text"
            id="db_name"
            name="db_name"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="db_user" class="form-label">Database User</label>
        <input
            type="text"
            id="db_user"
            name="db_user"
            class="form-control form-control-sm"
            placeholder="root"
            value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="db_pass" class="form-label">Database Password</label>
        <input
            type="password"
            id="db_pass"
            name="db_pass"
            class="form-control form-control-sm"
            value=""
        >
    </div>
    <div class="col-12 d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary btn-sm">
            Save Database Configuration
        </button>
    </div>
</form>
