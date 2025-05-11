<?php
session_start();

require_once __DIR__ . '/../../../app/config.php';
require_once __DIR__ . '/../../../app/assets/api/db_connect.php';
require_once __DIR__ . '/../../../app/assets/flash_messages.php';
require_once __DIR__ . '/../../../app/assets/api/user_apis/apis/users/users.php';
require_once __DIR__ . '/../../../app/assets/api/system_api/system_api.php';
require_once __DIR__ . '/../../assets/php/elements/layout_menager.php';

use Database\Database;
use Api\UserAPI\UserMenagment;
use Api\SystemAPI\SystemAPI;
use Assets\LayoutRenderer;

if (!isset($_SESSION['userID'])) {
    header("Location: " . LOGIN_PAGE);
    exit;
}

$database = new Database();
$conn     = $database->getConnection();
if ($conn === false) {
    setFlashMessage('error', 'Database connection error.');
}

$userId    = $_SESSION['userID'];
$userApi   = new UserMenagment($userId, $conn);
$userResult = $userApi->getUser();
if (!$userResult['success']) {
    setFlashMessage('error', "Error fetching account data: " . $userResult['error']);
}

$userData = $userResult['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reset password
    if (isset($_POST['reset_password'])) {
        $systemApi   = new SystemAPI($conn);
        $resetResult = $systemApi->requestPasswordReset($userData['email']);
        if ($resetResult['success']) {
            setFlashMessage('success', 'Password reset link has been sent to your email address.');
        } else {
            setFlashMessage('error', 'Password reset error: ' . $resetResult['error']);
        }
    }
    // Delete account
    elseif (isset($_POST['delete_account'])) {
        $deleteResult = $userApi->requestAccountDelete();
        if ($deleteResult['success']) {
            $msg = $deleteResult['message'] ?? 'Account deletion link has been sent to your email address.';
            setFlashMessage('success', $msg);
        } else {
            setFlashMessage('error', 'Account deletion error: ' . $deleteResult['error']);
        }
    }
    // Update profile
    else {
        $fields = ['first_name', 'last_name', 'email', 'birth_date'];
        foreach ($fields as $field) {
            $$field = $_POST[$field] ?? $userData[$field];
        }
        $fieldsToUpdate = [];
        foreach ($fields as $field) {
            if (!empty($$field) && $$field !== $userData[$field]) {
                $fieldsToUpdate[$field] = $$field;
            }
        }
        // Email change
        if (isset($fieldsToUpdate['email'])) {
            $emailChange = $userApi->requestEmailChange($fieldsToUpdate['email']);
            if ($emailChange['success']) {
                setFlashMessage('success', 'Email change request sent. Check your inbox to confirm.');
            } else {
                setFlashMessage('error', 'Email change request error: ' . $emailChange['error']);
            }
            unset($fieldsToUpdate['email']);
        }
        // Other updates
        if (!empty($fieldsToUpdate)) {
            $updateResult = $userApi->updateUser($userId, $fieldsToUpdate);
            if ($updateResult['success']) {
                setFlashMessage('success', 'User data updated successfully.');
            } else {
                setFlashMessage('error', 'Update error: ' . $updateResult['error']);
            }
        }
    }
}

function formatDate($date, $format) {
    return date($format, strtotime($date));
}

$birth_date_iso   = formatDate($userData['birth_date'], 'Y-m-d');
$account_created  = formatDate($userData['created_at'], 'd/m/Y');

$renderer = new LayoutRenderer($conn);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Account Settings</title>
    <?php $renderer->renderHead(); ?>
    <link rel="stylesheet" href="../../assets/css/forms.css">
</head>
<body class="bg-body text-body">
    <?php $renderer->renderNav(); ?>
    <main>
        <div class="form-wrapper">
            <div class="border p-4 rounded bg-body-secondary shadow-sm">
                <h2 class="mb-4 text-center">Account Settings</h2>

                <?= getFlashMessages() ?>

                <form action="" method="post">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" placeholder="<?= htmlspecialchars($userData['first_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" placeholder="<?= htmlspecialchars($userData['last_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="birth_date" class="form-label">Birth Date</label>
                        <input type="date" id="birth_date" name="birth_date" class="form-control" value="<?= $birth_date_iso; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="<?= htmlspecialchars($userData['email']); ?>">
                    </div>
                    <p class="text-muted mb-4">Account Created: <?= $account_created; ?></p>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="submit" name="reset_password" class="btn btn-outline-secondary">Reset Password</button>
                        <button type="submit" name="delete_account" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete your account?');">Delete Account</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
