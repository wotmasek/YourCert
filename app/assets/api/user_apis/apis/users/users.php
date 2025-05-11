<?php
namespace Api\UserAPI;

use Api\UserAPI;

require_once __DIR__ . '/../../user_api.php';

class UserMenagment extends UserAPI {
    public function __construct($user_id, \PDO $conn) {
        parent::__construct($user_id, $conn);
    }

    public function requestAccountDelete() {
        if (!$this->checkRateLimitAction('requestAccountDelete')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_requestAccountDelete']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
    
        $token = bin2hex(random_bytes(16));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
        $sql = "INSERT INTO account_delete_requests (user_id, token_hash, expires_at)
                VALUES (:user_id, :token_hash, :expires_at)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->bindParam(':expires_at', $expiresAt);
        if (!$stmt->execute()) {
            return ['success' => false, 'error' => $this->messages['failed_create_account_delete_request']];
        }
    
        $confirmLink = ACCOUNT_DELETE_PAGE . "?uid={$this->user_id}&token={$token}";
    
        $sql = "SELECT email FROM users WHERE id = :user_id AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$userData || empty($userData['email'])) {
            return ['success' => false, 'error' => $this->messages['email_not_found']];
        }
        $oldEmail = $userData['email'];
    
        require_once __DIR__ . '/../../../../vendor/autoload.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
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
            $mail->addAddress($oldEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Confirm Account Deletion';
            $mail->Body = sprintf($this->messages['account_deletion_confirmation_body'], $confirmLink, $confirmLink);
    
            $mail->send();
            $this->logAction('requestAccountDelete', 'user', $this->user_id, $this->user_id, "Account deletion requested");
            return ['success' => true, 'message' => $this->messages['account_deletion_requested'] ?? 'Wysłano link potwierdzający'];
        } catch (PHPMailer\PHPMailer\Exception $e) {
            return ['success' => false, 'error' => $this->messages['mailer_error'] . $mail->ErrorInfo];
        }
    }

    public function confirmAccountDelete($token) {
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateToken($token, 32)) {
            return ['success' => false, 'error' => $this->messages['invalid_token']];
        }
        
        $sql = "SELECT id, token_hash FROM account_delete_requests 
                WHERE user_id = :user_id AND expires_at >= NOW() ORDER BY created_at DESC AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        
        $request = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (password_verify($token, $row['token_hash'])) {
                $request = $row;
                break;
            }
        }
        if (!$request) {
            return ['success' => false, 'error' => $this->messages['invalid_or_expired_token']];
        }
        
        $randToken = bin2hex(random_bytes(8));
        $deletedFirstName = "DELETED_FIRST_NAME_" . $randToken;
        $deletedLastName  = "DELETED_LAST_NAME_" . $randToken;
        $deletedEmail     = "DELETED_EMAIL_" . $randToken;
        
        $sql = "UPDATE users SET 
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    birth_date = '0000-00-00',
                    is_active = 0
                WHERE id = :user_id AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':first_name', $deletedFirstName);
        $stmt->bindParam(':last_name', $deletedLastName);
        $stmt->bindParam(':email', $deletedEmail);
        $stmt->bindParam(':user_id', $this->user_id);
        if ($stmt->execute()) {
            $sql = "DELETE FROM account_delete_requests WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->execute();
        
            $this->logAction('confirmAccountDelete', 'user', $this->user_id, $this->user_id, "Account deleted");
            return ['success' => true, 'message' => $this->messages['account_deleted'] ?? 'Konto zostało usunięte'];
        }
        return ['success' => false, 'error' => $this->messages['failed_account_deletion']];
    }

    public function requestEmailChange($newEmail) {
        if (!$this->checkRateLimitAction('requestEmailChange')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_requestEmailChange']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateEmail($newEmail)) {
            return ['success' => false, 'error' => $this->messages['invalid_email_address']];
        }
        
        $sql = "SELECT COUNT(*) AS count FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $newEmail);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($result && $result['count'] > 0) {
            return ['success' => false, 'error' => $this->messages['email_already_taken']];
        }
    
        $token = bin2hex(random_bytes(16));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
        $sql = "INSERT INTO email_change_requests (user_id, new_email, token_hash, expires_at)
                VALUES (:user_id, :new_email, :token_hash, :expires_at)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':new_email', $newEmail);
        $stmt->bindParam(':token_hash', $tokenHash);
        $stmt->bindParam(':expires_at', $expiresAt);
        if (!$stmt->execute()) {
            return ['success' => false, 'error' => $this->messages['failed_create_email_change_request']];
        }
    
        $confirmLink = EMAIL_RESET_PAGE . "?uid={$this->user_id}&token={$token}";
    
        $sql = "SELECT email FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
        $userData = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$userData || empty($userData['email'])) {
            return ['success' => false, 'error' => $this->messages['old_email_not_found']];
        }
        $oldEmail = $userData['email'];
    
        require_once __DIR__ . '/../../../../../vendor/autoload.php';
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $emailConfig = require __DIR__ . '/../../../../email_config.php';
        try {
            $mail->isSMTP();
            $mail->Host       = $emailConfig['host'];
            $mail->SMTPAuth   = $emailConfig['smtp_auth'];
            $mail->Username   = $emailConfig['username'];
            $mail->Password   = $emailConfig['password'];
            $mail->SMTPSecure = $emailConfig['smtp_secure'];
            $mail->Port       = $emailConfig['port'];
    
            $mail->setFrom($emailConfig['username'], 'YourCert Support');
            $mail->addAddress($oldEmail);
            $mail->isHTML(true);
            $mail->Subject = 'Confirm Email Change';
            $mail->Body    = "Click the link below to confirm the email change to <strong>$newEmail</strong>:<br>
                              <a href='{$confirmLink}'>{$confirmLink}</a>";
    
            $mail->send();
            $this->logAction('requestEmailChange', 'user', $this->user_id, $this->user_id, "Email change requested to $newEmail");
            return ['success' => true, 'message' => $this->messages['email_change_requested'] ?? 'Confirmation email sent'];
        } catch (PHPMailer\PHPMailer\Exception $e) {
            return ['success' => false, 'error' => $this->messages['mailer_error'] . $mail->ErrorInfo];
        }
    }

    public function confirmEmailChange($token) {
        if (!$this->checkRateLimitAction('confirmEmailChange')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_confirmEmailChange']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateToken($token, 32)) {
            return ['success' => false, 'error' => $this->messages['invalid_token']];
        }
    
        $sql = "SELECT id, new_email, token_hash FROM email_change_requests 
                WHERE user_id = :user_id AND expires_at >= NOW() ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->execute();
    
        $request = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (password_verify($token, $row['token_hash'])) {
                $request = $row;
                break;
            }
        }
        if (!$request) {
            return ['success' => false, 'error' => $this->messages['invalid_or_expired_token']];
        }
    
        $sql = "UPDATE users SET email = :new_email WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':new_email', $request['new_email']);
        $stmt->bindParam(':user_id', $this->user_id);
        if ($stmt->execute()) {
            $sql = "DELETE FROM email_change_requests WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->execute();
    
            $this->logAction('confirmEmailChange', 'user', $this->user_id, $this->user_id, "Email changed to " . $request['new_email']);
            return ['success' => true, 'message' => $this->messages['email_changed'] ?? 'Email address updated'];
        }
        return ['success' => false, 'error' => $this->messages['failed_update_email_address']];
    }

    public function getUser($user_id = null) {
        if (!$this->checkRateLimitAction('getUser')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_getUser']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if ($user_id === null) {
            $user_id = $this->user_id;
        } else if (!validateInt($user_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_user_id']];
        }
        if ($user_id != $this->user_id) {
            $permCheck = $this->checkUserPermission('otherUsersInformations');
            if ($permCheck !== true) {
                return $permCheck;
            }
        }
        $sql = "SELECT id, email, first_name, last_name, birth_date, permission_id, created_at, is_confirmed
                FROM users
                WHERE id = :user_id AND is_active = 1 AND is_confirmed = 1
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $user ? ['success' => true, 'user' => $user] : ['success' => false, 'error' => $this->messages['user_not_found'] ?? 'User not found or inactive'];
    }

    public function getUsers($limit = 50, $offset = 0, $filters = []) {
        if (!$this->checkRateLimitAction('getUsers')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_getUsers']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($limit, 0) || !validateInt($offset, 0)) {
            return ['success' => false, 'error' => $this->messages['invalid_limit_offset']];
        }
        
        $where = ["is_active = 1", "is_confirmed = 1"];
        $params = [];
        
        if (!empty($filters['email'])) {
            $where[] = "email LIKE :email";
            $params[':email'] = "%" . $filters['email'] . "%";
        }
        if (!empty($filters['first_name'])) {
            $where[] = "first_name LIKE :first_name";
            $params[':first_name'] = "%" . $filters['first_name'] . "%";
        }
        if (!empty($filters['last_name'])) {
            $where[] = "last_name LIKE :last_name";
            $params[':last_name'] = "%" . $filters['last_name'] . "%";
        }
        if (!empty($filters['birth_date'])) {
            $where[] = "birth_date = :birth_date";
            $params[':birth_date'] = $filters['birth_date'];
        }
        if (!empty($filters['created_at'])) {
            $where[] = "DATE(created_at) = :created_at";
            $params[':created_at'] = $filters['created_at'];
        }
        
        $sql = "SELECT id, email, first_name, last_name, birth_date, created_at FROM users";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->conn->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return ['success' => true, 'users' => $users];
    }
    
    public function updateUser($user_id, array $fields) {
        if (!$this->checkRateLimitAction('updateUser')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateUser']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($user_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_user_id']];
        }
        if ($user_id != $this->user_id) {
            $permCheck = $this->checkUserPermission('otherUsersDataManagement');
            if ($permCheck !== true) {
                return $permCheck;
            }
        }
        $sql = "SELECT id FROM users WHERE id = :user_id AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            return ['success' => false, 'error' => $this->messages['user_not_found'] ?? 'User not found or inactive'];
        }
        
        $allowed = ['first_name', 'last_name', 'email', 'birth_date', 'permission_id', 'is_confirmed', 'is_active', 'failed_login_attempts', 'confirmation_code'];
        $restricted = ['email', 'permission_id', 'is_confirmed', 'is_active', 'failed_login_attempts', 'confirmation_code'];
        $setParts = [];
        $params = [':user_id' => $user_id];
        foreach ($fields as $key => $value) {
            if (in_array($key, $allowed)) {
                if (in_array($key, $restricted)) {
                    $adminCheck = $this->checkUserPermission('admin');
                    if ($adminCheck !== true) {
                        return ['success' => false, 'error' => "Admin privileges required to update $key"];
                    }
                }
                if (in_array($key, ['first_name', 'last_name']) && !validateName($value)) {
                    return ['success' => false, 'error' => $this->messages['invalid_name']];
                }
                if ($key === 'email' && !validateEmail($value)) {
                    return ['success' => false, 'error' => $this->messages['invalid_email_address']];
                }
                if ($key === 'birth_date' && !validate_birth_date($value)) {
                    return ['success' => false, 'error' => $this->messages['invalid_birth_date']];
                }
                if ($key === 'permission_id' && !validateInt($value, 1)) {
                    return ['success' => false, 'error' => $this->messages['invalid_permission_id']];
                }
                if (in_array($key, ['is_confirmed', 'is_active']) && !validateInt($value, 0, 1)) {
                    return ['success' => false, 'error' => $this->messages['invalid_boolean']];
                }
                if ($key === 'failed_login_attempts' && !validateInt($value, 0)) {
                    return ['success' => false, 'error' => $this->messages['invalid_failed_login_attempts']];
                }
                if (in_array($key, ['confirmation_code', 'salt']) && (!is_string($value) || !validateText($value, 100))) {
                    return ['success' => false, 'error' => $this->messages["invalid_$key"]];
                }
                $setParts[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
        }
        if (empty($setParts)) {
            return ['success' => false, 'error' => $this->messages['no_valid_fields_provided']];
        }
        $sql = "UPDATE users SET " . implode(', ', $setParts) . " WHERE id = :user_id AND is_active = 1 AND is_confirmed = 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute($params)) {
            $updatedFields = implode(', ', array_keys($fields));
            $this->logAction('updateUser', 'user', $user_id, "Updated fields: $updatedFields");
            return ['success' => true, 'message' => $this->messages['user_updated'] ?? 'User updated'];
        }
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }
    
    public function updateUserField($user_id, $field, $value) {
        if (!$this->checkRateLimitAction('updateUserField')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateUserField']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($user_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_user_id']];
        }
        if ($user_id != $this->user_id) {
            $permCheck = $this->checkUserPermission('otherUsersDataManagement');
            if ($permCheck !== true) {
                return $permCheck;
            }
        }
        $sql = "SELECT id FROM users WHERE id = :user_id AND is_active = 1 AND is_confirmed = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            return ['success' => false, 'error' => $this->messages['user_not_found'] ?? 'User not found or inactive'];
        }
        $allowed = ['first_name', 'last_name', 'email', 'birth_date', 'permission_id', 'is_confirmed', 'is_active', 'failed_login_attempts', 'confirmation_code'];
        $restricted = ['email', 'permission_id', 'is_confirmed', 'is_active', 'failed_login_attempts', 'confirmation_code'];
        if (!in_array($field, $allowed)) {
            return ['success' => false, 'error' => "Pole '$field' nie jest dozwolone do aktualizacji"];
        }
        if (in_array($field, $restricted)) {
            $adminCheck = $this->checkUserPermission('admin');
            if ($adminCheck !== true) {
                return ['success' => false, 'error' => "Wymagane uprawnienia administratora do aktualizacji pola '$field'"];
            }
        }
        if ($field === 'email' && !validateEmail($value)) {
            return ['success' => false, 'error' => $this->messages['invalid_email_address']];
        }
        if (in_array($field, ['first_name', 'last_name']) && !validateName($value)) {
            return ['success' => false, 'error' => $this->messages['invalid_name']];
        }
        if ($field === 'birth_date' && !validate_birth_date($value)) {
            return ['success' => false, 'error' => $this->messages['invalid_birth_date']];
        }
        if ($field === 'permission_id' && !validateInt($value, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_permission_id']];
        }
        if (in_array($field, ['is_confirmed', 'is_active']) && !validateInt($value, 0, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_boolean']];
        }
        if ($field === 'failed_login_attempts' && !validateInt($value, 0)) {
            return ['success' => false, 'error' => $this->messages['invalid_failed_login_attempts']];
        }
        if (in_array($field, ['confirmation_code', 'salt']) && (!is_string($value) || !validateText($value, 100))) {
            return ['success' => false, 'error' => $this->messages["invalid_$field"]];
        }
        $result = $this->updateUser($user_id, [$field => $value]);
        if ($result['success']) {
            $this->logAction('updateUserField', 'user', $user_id, $this->user_id, "Pole '$field' zaktualizowane do: " . $value);
        }
        return $result;
    }
    
    public function getUserPermissions($user_id = null) {
        if (!$this->checkRateLimitAction('getUserPermissions')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_getUserPermissions']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if ($user_id === null) {
            $user_id = $this->user_id;
        } else if (!validateInt($user_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_user_id']];
        }
        if ($user_id != $this->user_id) {
            $permCheck = $this->checkUserPermission('otherUsersInformations');
            if ($permCheck !== true) {
                return $permCheck;
            }
        }
        $sql = "SELECT id FROM users WHERE id = :user_id AND is_active = 1 AND is_confirmed = 1 LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            return ['success' => false, 'error' => $this->messages['user_not_found'] ?? 'User not found or inactive'];
        }
        $sql = "SELECT u.id AS user_id, u.email, p.id AS permission_id, p.name, p.description
                FROM users u
                LEFT JOIN roles p ON u.permission_id = p.id
                WHERE u.id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? ['success' => true, 'permissions' => $result] : ['success' => false, 'error' => $this->messages['permissions_not_found']];
    }
}
