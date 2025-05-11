<?php
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/api/system_api/system_api.php';
require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';

use Database\Database;
use Api\SystemAPI\SystemAPI;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName    = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $lastName     = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_SPECIAL_CHARS);
    $adminEmail   = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $adminPass    = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
    $repeatedPass = filter_input(INPUT_POST, 'repeated_password', FILTER_SANITIZE_SPECIAL_CHARS);
    $birthDate    = filter_input(INPUT_POST, 'birth_date', FILTER_SANITIZE_SPECIAL_CHARS);
    
    if (!$firstName || !$lastName || !$adminEmail || !$adminPass || !$repeatedPass || !$birthDate) {
        $error = "All fields are required.";
    }
    if (!isset($error)) {
        $database   = new Database;
        $connection = $database->getConnection();
        
        if ($connection !== false) {
            $api = new SystemAPI($connection);
            $result = $api->registerUser($firstName, $lastName, $adminEmail, $adminPass, $repeatedPass, $birthDate, 2);
            if ($result['success']) {
                $lockFile = __DIR__ . '/../../../app/install.lock';
                file_put_contents($lockFile, "Installation completed: " . date('Y-m-d H:i:s'));
                
                setFlashMessage('success', 'Administrator account created. ' . $result['message'] . ' For security reasons, please delete the /install folder from your server.');
                header("Location: " . HTTP_ADRESS . "install/success.php");
                exit;
            } else {
                $error = $result['error'];
            }
        } else {
            $error = "Database connection error.";
        }
    }
}
?>
<div class="mb-4">
    <h5 class="mb-0">Step 4 of 4: Create Administrator Account</h5>
</div>
<?php if (isset($error)): ?>
    <?php showError($error); ?>
<?php endif; ?>
<form method="post" class="row g-3">
    <div class="col-12 col-md-6">
        <label for="first_name" class="form-label">First Name</label>
        <input
            type="text"
            id="first_name"
            name="first_name"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="last_name" class="form-label">Last Name</label>
        <input
            type="text"
            id="last_name"
            name="last_name"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="email" class="form-label">Administrator Email</label>
        <input
            type="email"
            id="email"
            name="email"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="password" class="form-label">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            class="form-control form-control-sm"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="repeated_password" class="form-label">Repeat Password</label>
        <input
            type="password"
            id="repeated_password"
            name="repeated_password"
            class="form-control form-control-sm"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="birth_date" class="form-label">Birth Date</label>
        <input
            type="date"
            id="birth_date"
            name="birth_date"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>"
            required
        >
    </div>
    <div class="col-12 d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary btn-sm">
            Create Administrator Account
        </button>
    </div>
</form>
