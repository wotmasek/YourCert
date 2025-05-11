<?php
$emailConfigFilePath = __DIR__ . '/../../../app/email_config.php';

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
    $emailHost   = filter_input(INPUT_POST, 'email_host', FILTER_SANITIZE_URL);
    $emailUser   = filter_input(INPUT_POST, 'email_user', FILTER_SANITIZE_EMAIL);
    $emailPass   = filter_input(INPUT_POST, 'email_pass', FILTER_UNSAFE_RAW);
    $emailPort   = filter_input(INPUT_POST, 'email_port', FILTER_VALIDATE_INT);
    $emailSecure = filter_input(INPUT_POST, 'email_secure', FILTER_SANITIZE_SPECIAL_CHARS);
    $emailAuth   = filter_input(INPUT_POST, 'email_auth', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    if (!$emailHost || !$emailUser || !$emailPass || !$emailPort || $emailSecure === null || $emailAuth === null) {
        $error = "All fields are required and must be valid.";
    }

    if (!isset($error)) {
        require_once __DIR__ . '/../../../vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host     = $emailHost;
            $mail->SMTPAuth = $emailAuth;
            $mail->Username = $emailUser;
            $mail->Password = $emailPass;
            $mail->Port     = $emailPort;

            if ($emailSecure === 'ssl') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($emailSecure === 'tls') {
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = '';
            }

            $mail->smtpConnect();
            $mail->smtpClose();
        } catch (Exception $e) {
            $error = "SMTP connection error: " . $mail->ErrorInfo;
        }

        if (!isset($error)) {
            $config = [
                'host'       => $emailHost,
                'username'   => $emailUser,
                'password'   => $emailPass,
                'smtp_secure'=> $emailSecure,    // będzie nadpisane w pliku konfigu
                'smtp_auth'  => $emailAuth,
                'port'       => $emailPort,
            ];

            // Budujemy nową zawartość pliku email_config.php
            $newContent  = "<?php\n";
            $newContent .= "require __DIR__ . '/../vendor/autoload.php';\n";
            $newContent .= "use PHPMailer\\PHPMailer\\PHPMailer;\n\n";
            $newContent .= "return [\n";
            $newContent .= "    'host'        => " . var_export($config['host'], true) . ",\n";
            $newContent .= "    'username'    => " . var_export($config['username'], true) . ",\n";
            $newContent .= "    'password'    => " . var_export($config['password'], true) . ",\n";
            // tu wstawiamy odpowiedni stały obiekt
            if ($emailSecure === 'ssl') {
                $newContent .= "    'smtp_secure' => PHPMailer::ENCRYPTION_SMTPS,\n";
            } elseif ($emailSecure === 'tls') {
                $newContent .= "    'smtp_secure' => PHPMailer::ENCRYPTION_STARTTLS,\n";
            } else {
                $newContent .= "    'smtp_secure' => '',\n";
            }
            $newContent .= "    'smtp_auth'   => " . ($config['smtp_auth'] ? 'true' : 'false') . ",\n";
            $newContent .= "    'port'        => " . var_export($config['port'], true) . ",\n";
            $newContent .= "];\n";

            if (!safe_file_put_contents($emailConfigFilePath, $newContent)) {
                $error = "Error writing email_config.php file. Check filesystem permissions.";
            } else {
                redirect(4);
            }
        }
    }
}
?>
<div class="mb-4">
    <h5 class="mb-0">Step 3 of 4: Email Configuration</h5>
</div>
<?php if (isset($error)): ?>
    <?php showError($error); ?>
<?php endif; ?>
<form method="post" class="row g-3">
    <div class="col-12 col-md-6">
        <label for="email_host" class="form-label">SMTP Host</label>
        <input
            type="text"
            id="email_host"
            name="email_host"
            class="form-control form-control-sm"
            placeholder="smtp.gmail.com"
            value="<?= htmlspecialchars($_POST['email_host'] ?? 'smtp.gmail.com') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="email_user" class="form-label">Email (User)</label>
        <input
            type="email"
            id="email_user"
            name="email_user"
            class="form-control form-control-sm"
            value="<?= htmlspecialchars($_POST['email_user'] ?? '') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="email_pass" class="form-label">Password</label>
        <input
            type="password"
            id="email_pass"
            name="email_pass"
            class="form-control form-control-sm"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="email_port" class="form-label">Port</label>
        <input
            type="number"
            id="email_port"
            name="email_port"
            class="form-control form-control-sm"
            placeholder="465"
            value="<?= htmlspecialchars($_POST['email_port'] ?? '465') ?>"
            required
        >
    </div>
    <div class="col-12 col-md-6">
        <label for="email_secure" class="form-label">Encryption Method</label>
        <select id="email_secure" name="email_secure" class="form-select form-select-sm" required>
            <option value="ssl" <?= (($_POST['email_secure'] ?? '') === 'ssl') ? 'selected' : '' ?>>ssl</option>
            <option value="tls" <?= (($_POST['email_secure'] ?? '') === 'tls') ? 'selected' : '' ?>>tls</option>
            <option value="" <?= (($_POST['email_secure'] ?? '') === '') ? 'selected' : '' ?>>none</option>
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label for="email_auth" class="form-label">Require SMTP Auth</label>
        <select id="email_auth" name="email_auth" class="form-select form-select-sm" required>
            <option value="1" <?= (($_POST['email_auth'] ?? '') === '1') ? 'selected' : '' ?>>Yes</option>
            <option value="0" <?= (($_POST['email_auth'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
        </select>
    </div>
    <div class="col-12 d-flex justify-content-end mt-3">
        <button type="submit" class="btn btn-primary btn-sm">
            Save Email Configuration
        </button>
    </div>
</form>