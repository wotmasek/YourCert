<?php
namespace Api\SystemAPI;

require_once __DIR__ . '/../action_menager.php';
require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../validation_functions.php';

use Api\ActionMenager;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SystemAPI extends ActionMenager {
    protected $messages;
    protected $rateLimitsConfig;
    
    const SYSTEM_ID = 0; 

    public function __construct(\PDO $conn) {
        parent::__construct($conn);

        if (!$this->loadRateLimitsConfig('system_api/rate_limits_config.json')) {
            throw new \RuntimeException(
                "Failed to load rate limits config (rate_limits_config.json)"
            );
        }
    
        $messagesPath = __DIR__ . '/messages.json';
        if (!is_readable($messagesPath)) {
            throw new \RuntimeException(
                "Cannot read messages file: messages.json"
            );
        }
        $raw = file_get_contents($messagesPath);
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                "Invalid JSON in messages.json: " . json_last_error_msg()
            );
        }
        $this->messages = $decoded;
    }

    public function loginUser($email, $password) {
        $maxAttempts = 5;
        $blockTime   = 10 * 60; 
        $now         = time();
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !validatePassword($password)) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
    
        $query = "SELECT id, password, is_confirmed, is_active, last_login_attempt, failed_login_attempts FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
    
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
    
        $userRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        $userId = $userRow['id'];
        $isConfirmed = $userRow['is_confirmed'];
        $isActive = $userRow['is_active'];
        $lastLoginAttempt = $userRow['last_login_attempt'];
        $failedAttempts = $userRow['failed_login_attempts'];
    
        if ($failedAttempts >= $maxAttempts) {
            $lastAttemptTimestamp = strtotime($lastLoginAttempt);
            if (($now - $lastAttemptTimestamp) < $blockTime) {
                return ['success' => false, 'error' => 'Too many failed login attempts. Please try again later.'];
            }
        }
    
        if (!$isConfirmed) {
            return ['success' => false, 'error' => 'Your account is not confirmed yet. Please check your email for confirmation.'];
        }
    
        if (!$isActive) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
    
        if (password_verify($password . PEPPER, $userRow['password'])) {
            $updateQuery = "UPDATE users SET failed_login_attempts = 0 WHERE email = :email";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->execute();
    
            return ['success' => true, 'message' => 'Login successful!', 'user_id' => $userId];
        } else {
            $updateQuery = "UPDATE users SET failed_login_attempts = failed_login_attempts + 1, last_login_attempt = NOW() WHERE email = :email";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->execute();
    
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
    }
    
    public function registerUser($first_name, $last_name, $email, $password, $reapeted_password, $birth_date, $permission_id = 1) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format.'];
        }
    
        if (!validatePassword($password)) {
            return ['success' => false, 'error' => 'Password does not meet requirements.'];
        }
    
        if ($password !== $reapeted_password) {
            return ['success' => false, 'error' => 'Passwords do not match.'];
        }
    
        if (!validateName($first_name)) {
            return ['success' => false, 'error' => 'Invalid first name.'];
        }
    
        if (!validateName($last_name)) {
            return ['success' => false, 'error' => 'Invalid last name.'];
        }
    
        if (!validate_birth_date($birth_date)) {
            return ['success' => false, 'error' => 'Invalid birth date.'];
        }
    
        if (!filter_var($permission_id, FILTER_VALIDATE_INT)) {
            return ['success' => false, 'error' => 'Invalid permission ID.'];
        }
    
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'error' => 'User already exists.'];
        }
    
        $hashedPassword    = password_hash($password . PEPPER, PASSWORD_BCRYPT);
        $confirmationCode  = bin2hex(random_bytes(16));
    
        $sql = "INSERT INTO users 
                (password, email, is_confirmed, confirmation_code, first_name, last_name, birth_date, permission_id)
                VALUES 
                (:password, :email, 0, :confirmation_code, :first_name, :last_name, :birth_date, :permission_id)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':password',          $hashedPassword);
        $stmt->bindParam(':email',             $email);
        $stmt->bindParam(':confirmation_code', $confirmationCode);
        $stmt->bindParam(':first_name',        $first_name);
        $stmt->bindParam(':last_name',         $last_name);
        $stmt->bindParam(':birth_date',        $birth_date);
        $stmt->bindParam(':permission_id',     $permission_id);
    
        if (!$stmt->execute()) {
            return ['success' => false, 'error' => 'Registration failed. Please try again later.'];
        }
    
        $emailConfig = require __DIR__ . '/../../../email_config.php';
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $emailConfig['host'];
            $mail->SMTPAuth   = $emailConfig['smtp_auth'];
            $mail->Username   = $emailConfig['username'];
            $mail->Password   = $emailConfig['password'];
            $mail->SMTPSecure = $emailConfig['smtp_secure'];
            $mail->Port       = $emailConfig['port'];
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';
    
            $mail->setFrom($emailConfig['username'], 'No Reply');
            $mail->addAddress($email);
    
            $mail->isHTML(true);
            $mail->Subject = 'Account Confirmation';
            $mail->Body    = 'Hello ' . htmlspecialchars($first_name) . ',<br><br>' .
                             'Click the link below to confirm your registration:<br>' .
                             '<a href="' . CONFIRMATION_PAGE . '?code=' . $confirmationCode . '">Confirm my account</a>';
    
            if ($mail->send()) {
                return ['success' => true, 'message' => 'Account successfully created! Please check your email to confirm your account.'];
            } else {
                return ['success' => false, 'error' => 'Failed to send confirmation email.'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Mailer Error: ' . $mail->ErrorInfo];
        }
    }

    public function requestPasswordReset($email) {
        if (!validateEmail($email)) {
            return ['success' => false, 'error' => $this->messages['invalid_email_address']];
        }
        if (!$this->checkRateLimitAction('requestPasswordReset')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_requestPasswordReset']];
        }
        
        $sql = "SELECT id, is_active FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$user) {
            return ['success' => false, 'error' => $this->messages['user_not_found']];
        }

        if ($user['is_active'] != true) {
            return ['success' => false, 'error' => $this->messages['user_not_found']];
        }

        $userId = $user['id'];

        $token = bin2hex(random_bytes(16));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "INSERT INTO password_resets (user_id, token_hash, expires_at)
                VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->bindParam(':expires_at', $expiresAt);
        if (!$stmt->execute()) {
            return ['success' => false, 'error' => $this->messages['failed_create_password_reset_token']];
        }

        $resetLink = PASSWORD_RESET_PAGE . "?uid={$userId}&token={$token}";

        $mail = new PHPMailer(true);
        $emailConfig = require __DIR__ . '/../../../email_config.php';
        try {
            $mail->isSMTP();
            $mail->Host       = $emailConfig['host'];
            $mail->SMTPAuth   = $emailConfig['smtp_auth'];
            $mail->Username   = $emailConfig['username'];
            $mail->Password   = $emailConfig['password'];
            $mail->SMTPSecure = $emailConfig['smtp_secure'];
            $mail->Port       = $emailConfig['port'];

            $mail->setFrom($emailConfig['username'], 'YourCert Support');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = $this->messages['reset_password_subject'];
            $mail->Body    = $this->messages['reset_password_body'] . " <a href='{$resetLink}'>{$resetLink}</a>";

            $mail->send();
            $this->logAction('requestPasswordReset', 'user', $userId, "Reset link sent to $email");
            return ['success' => true, 'message' => $this->messages['password_reset_link_sent'] ?? 'Password reset link sent'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $this->messages['mailer_error'] . $mail->ErrorInfo];
        }
    }

    public function resetPassword($token, $newPassword) {
        if (!validateToken($token, 32)) {
            return ['success' => false, 'error' => $this->messages['invalid_token_format']];
        }
        if (!validatePassword($newPassword, 8)) {
            return ['success' => false, 'error' => $this->messages['invalid_new_password']];
        }
        if (!$this->checkRateLimitAction('resetPassword')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_resetPassword']];
        }
    
        $sql = "SELECT user_id, token_hash FROM password_resets WHERE expires_at >= NOW() ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
    
        $resetRequest = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (password_verify($token, $row['token_hash'])) {
                $resetRequest = $row;
                break;
            }
        }
        if (!$resetRequest) {
            return ['success' => false, 'error' => $this->messages['invalid_or_expired_token']];
        }
    
        $userId = $resetRequest['user_id'];
    
        $hashedPassword = password_hash($newPassword . PEPPER, PASSWORD_BCRYPT);
    
        $sql = "UPDATE users SET password = :password WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        if ($stmt->execute()) {
            $sql = "DELETE FROM password_resets WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            $this->logAction('resetPassword', 'user', $userId, "Password reset successfully");
            return ['success' => true, 'message' => $this->messages['password_reset_success']];
        }
        return ['success' => false, 'error' => $this->messages['failed_reset_password']];
    }    

    public function createRole($name, array $permissions) {
        $permissionColumns = [
            "otherUsersDataManagement",
            "otherUsersInformations",
            "getUsers",
            "requestEmailChange",
            "confirmEmailChange",
            "getCourses",
            "createCourse",
            "updateCourse",
            "updateCourseField",
            "getCourse",
            "deleteCourse",
            "getCertificates",
            "createCertificate",
            "updateCertificate",
            "updateCertificateField",
            "getCertificate",
            "deleteCertificate",
            "assignCertificate",
            "getUserCertificates",
            "getUser",
            "updateUser",
            "updateUserField",
            "getUserPermissions",
            "getInstitution",
            "updateInstitution",
            "updateInstitutionField"
        ];
        
        if (empty($name)) {
            return ['success' => false, 'error' => $this->messages['empty_role_name']];
        }
        
        if (count($permissions) !== count($permissionColumns)) {
            return ['success' => false, 'error' => $this->messages['incorrect_permissions_count']];
        }
        
        $stmt = $this->conn->prepare("SELECT id FROM roles WHERE name = :name");
        $stmt->execute(['name' => $name]);
        if ($stmt->fetch(\PDO::FETCH_ASSOC)) {
            return ['success' => false, 'error' => $this->messages['role_already_exists']];
        }
        
        try {
            $this->conn->beginTransaction();
            
            $columnsStr = implode(', ', $permissionColumns);
            $placeholders = ':' . implode(', :', $permissionColumns);
            $sql = "INSERT INTO permission_settings ($columnsStr) VALUES ($placeholders)";
            $stmt = $this->conn->prepare($sql);
            
            foreach ($permissionColumns as $index => $column) {
                $stmt->bindValue(':' . $column, $permissions[$index], \PDO::PARAM_INT);
            }
            $stmt->execute();
            
            $permissionSettingsId = $this->conn->lastInsertId();
            
            $sql = "INSERT INTO roles (name, permission_settings_id) VALUES (:name, :psid)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':psid', $permissionSettingsId, \PDO::PARAM_INT);
            $stmt->execute();
            $roleId = $this->conn->lastInsertId();
            
            $this->conn->commit();
            return ['success' => true, 'message' => $this->messages['role_created'], 'role_id' => $roleId];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'error' => $this->messages['failed_create_role'] . $e->getMessage()];
        }
    }
}
?>
