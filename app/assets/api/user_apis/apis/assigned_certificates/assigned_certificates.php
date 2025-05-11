<?php
namespace Api\UserAPI;

use Api\UserAPI; 

require_once __DIR__ . '/../../user_api.php';

class AssignedCertificatesMenagment extends UserAPI {
    public function __construct($user_id, \PDO $conn) {
        parent::__construct($user_id, $conn);
    }

    public function assignCertificate($target_user_id, $certificate_id, $personalized_certificate_image_path = null, $assignment_valid_until = null) {
        if (!$this->checkRateLimitAction('assignCertificate')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_assignCertificate']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($target_user_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_target_user_id']];
        }
        if (!validateInt($certificate_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_id']];
        }
        if ($target_user_id != $this->user_id) {
            $permCheck = $this->checkUserPermission('otherUsersDataManagement');
            if ($permCheck !== true) {
                return $permCheck;
            }
        }
        if ($personalized_certificate_image_path !== null && (!is_string($personalized_certificate_image_path) || !validateText($personalized_certificate_image_path, 255))) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_image_path']];
        }
        
        $sql_cert = "SELECT valid_until FROM certificates WHERE id = :certificate_id LIMIT 1";
        $stmt_cert = $this->conn->prepare($sql_cert);
        $stmt_cert->bindParam(':certificate_id', $certificate_id, \PDO::PARAM_INT);
        $stmt_cert->execute();
        $cert_data = $stmt_cert->fetch(\PDO::FETCH_ASSOC);
        if (!$cert_data) {
            return ['success' => false, 'error' => $this->messages['user_not_found'] ?? 'Certificate not found'];
        }
        
        if ($assignment_valid_until === null) {
            $assignment_valid_until = $cert_data['valid_until'];
        }
        
        $token = bin2hex(random_bytes(16));
        
        $sql = "INSERT INTO user_certificates (user_id, certificate_id, valid_until, personalized_certificate_image_path, token)
                SELECT :user_id, :certificate_id, :valid_until, :personalized_certificate_image_path, :token
                FROM users
                WHERE id = :user_id AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $target_user_id, \PDO::PARAM_INT);
        $stmt->bindParam(':certificate_id', $certificate_id, \PDO::PARAM_INT);
        $stmt->bindParam(':valid_until', $assignment_valid_until, \PDO::PARAM_STR);
        $stmt->bindParam(':personalized_certificate_image_path', $personalized_certificate_image_path, \PDO::PARAM_STR);
        $stmt->bindParam(':token', $token, \PDO::PARAM_STR);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $user_certificate_id = $this->conn->lastInsertId();
            $this->logAction('assignCertificate', 'certificate', $certificate_id, $target_user_id, "Assigned certificate ID $certificate_id to user ID $target_user_id");
            return ['success' => true, 'user_certificate_id' => $user_certificate_id, 'token' => $token, 'message' => $this->messages['certificate_assigned'] ?? 'Certificate assigned'];
        }
        return ['success' => false, 'error' => $this->messages['user_not_found'] ?? 'User not found or inactive'];
    }

    public function deleteUserCertificate($id) {
        if (!$this->checkRateLimitAction('deleteUserCertificate')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_deleteUserCertificate'] ?? 'Rate limit exceeded'];
        }
        
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        
        if (!validateInt($id, 1)) {
            return ['success' => false, 'error' => 'Invalid id'];
        }
        
        $sql = "SELECT user_id, certificate_id FROM user_certificates WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$record) {
            return ['success' => false, 'error' => 'Certificate not found'];
        }
        
        if ($record['user_id'] != $this->user_id) {
            $permOther = $this->checkUserPermission('otherUsersDataManagement');
            if ($permOther !== true) {
                return $permOther;
            }
        }
        
        $sqlDelete = "DELETE FROM user_certificates WHERE id = :id";
        $stmtDelete = $this->conn->prepare($sqlDelete);
        $stmtDelete->bindParam(':id', $id, \PDO::PARAM_INT);
        
        if ($stmtDelete->execute()) {
            $this->logAction('deleteUserCertificate', 'certificate', $record['certificate_id'], $record['user_id'], "Certificate deleted");
            return ['success' => true, 'message' => $this->messages['certificate_removed'] ?? 'Certificate has been deleted'];
        }
        
        return ['success' => false, 'error' => $stmtDelete->errorInfo()];
    }    

    private function getBaseUserCertificateQuery() {
        return "SELECT uc.*, c.title AS certificate_title, c.description AS certificate_description, c.certificate_image_path, c.valid_until AS certificate_valid_until, u.email, u.first_name, u.last_name 
                FROM user_certificates uc 
                LEFT JOIN certificates c ON uc.certificate_id = c.id 
                LEFT JOIN users u ON uc.user_id = u.id";
    }

    public function getUserCertificates($user_id = null) {
        if (!$this->checkRateLimitAction('getUserCertificates')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_getUserCertificates']];
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
        
        $sqlUser = "SELECT id FROM users WHERE id = :user_id AND is_active = 1 AND is_confirmed = 1 LIMIT 1";
        $stmtUser = $this->conn->prepare($sqlUser);
        $stmtUser->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmtUser->execute();
        if (!$stmtUser->fetch(\PDO::FETCH_ASSOC)) {
            return ['success' => false, 'error' => $this->messages['user_not_found'] ?? 'User not found or inactive'];
        }
        
        $isAdmin = $this->checkUserPermission('admin') === true;
        
        if ($isAdmin) {
            $sql = "SELECT 
                        uc.*, 
                        c.title AS certificate_title, 
                        c.description AS certificate_description, 
                        uc.personalized_certificate_image_path AS personalized_certificate_image_path, 
                        c.certificate_image_path AS certificate_image_path,
                        uc.valid_until AS valid_until,
                        uc.token
                    FROM user_certificates uc
                    JOIN certificates c ON uc.certificate_id = c.id
                    WHERE uc.user_id = :user_id";
        } else {
            $sql = "SELECT 
                        uc.id AS user_certificate_id,
                        c.title AS certificate_title, 
                        c.description AS certificate_description, 
                        uc.personalized_certificate_image_path AS personalized_certificate_image_path, 
                        c.certificate_image_path AS certificate_image_path,
                        uc.valid_until AS valid_until,
                        uc.token
                    FROM user_certificates uc
                    JOIN certificates c ON uc.certificate_id = c.id
                    WHERE uc.user_id = :user_id";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, \PDO::PARAM_INT);
        $stmt->execute();
        $certificates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return ['success' => true, 'user_certificates' => $certificates];
    }         
    
    public function getAssignedCertificates($limit = 50, $offset = 0, $filters = []) {
        if (!$this->checkRateLimitAction('getAssignedCertificates')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_getAssignedCertificates']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        $adminCheck = $this->checkUserPermission('admin');
        if ($adminCheck !== true) {
            return ['success' => false, 'error' => 'Admin privileges required to access assigned certificates'];
        }
        if (!validateInt($limit, 0) || !validateInt($offset, 0)) {
            return ['success' => false, 'error' => $this->messages['invalid_limit_offset']];
        }
        
        $baseQuery = $this->getBaseUserCertificateQuery();
        $sql = $baseQuery;
        $where = [];
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $where[] = "u.id = :user_id";
            $params[':user_id'] = $filters['user_id'];
        }
        if (!empty($filters['email'])) {
            $where[] = "u.email LIKE :email";
            $params[':email'] = "%" . $filters['email'] . "%";
        }
        if (!empty($filters['first_name'])) {
            $where[] = "u.first_name LIKE :first_name";
            $params[':first_name'] = "%" . $filters['first_name'] . "%";
        }
        if (!empty($filters['last_name'])) {
            $where[] = "u.last_name LIKE :last_name";
            $params[':last_name'] = "%" . $filters['last_name'] . "%";
        }
        if (!empty($filters['certificate_id'])) {
            $where[] = "c.id = :certificate_id";
            $params[':certificate_id'] = $filters['certificate_id'];
        }
        if (!empty($filters['certificate_title_assigned'])) {
            $where[] = "c.title LIKE :certificate_title";
            $params[':certificate_title'] = "%" . $filters['certificate_title_assigned'] . "%";
        }
        if (!empty($filters['awarded_at'])) {
            $where[] = "DATE(uc.awarded_at) = :awarded_at";
            $params[':awarded_at'] = $filters['awarded_at'];
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY uc.awarded_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $assigned_certificates = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (empty($assigned_certificates)) {
            return ['success' => false, 'error' => $this->messages['assigned_certificates_not_found']];
        }
        
        return ['success' => true, 'assigned_certificates' => $assigned_certificates];
    }

    public function getAssignedCertificate($id) {
        if (!$this->checkRateLimitAction('getAssignedCertificate')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_getAssignedCertificate']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        $adminCheck = $this->checkUserPermission('admin');
        if ($adminCheck !== true) {
            return ['success' => false, 'error' => 'Admin privileges required to access assigned certificates'];
        }
        if (!validateInt($id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_id']];
        }
        
        $baseQuery = $this->getBaseUserCertificateQuery();
        $sql = $baseQuery . " WHERE uc.id = :id ORDER BY uc.awarded_at DESC LIMIT 1";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $assigned_certificate = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$assigned_certificate) {
            return ['success' => false, 'error' => $this->messages['assigned_certificate_not_found'] ?? 'Assigned certificate not found'];
        }
        
        return ['success' => true, 'assigned_certificate' => $assigned_certificate];
    }

    public function updateAssignedCertificateField($id, $field, $value) {
        if (!$this->checkRateLimitAction('updateAssignedCertificateField')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateAssignedCertificateField'] ?? 'Rate limit exceeded'];
        }
    
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
    
        if (!validateInt($id, 1)) {
            return ['success' => false, 'error' => 'Invalid assigned certificate ID.'];
        }
    
        $allowed_fields = ['personalized_certificate_image_path', 'valid_until'];
        if (!in_array($field, $allowed_fields)) {
            return ['success' => false, 'error' => "Field '$field' cannot be updated."];
        }
    
        if ($field === 'personalized_certificate_image_path') {
            if (!is_string($value) || empty($value) || !validateText($value, 255)) {
                return ['success' => false, 'error' => $this->messages['invalid_certificate_image_path'] ?? 'Invalid PDF file path.'];
            }
        } elseif ($field === 'valid_until') {
            if ($value !== null && strtotime($value) === false) {
                return ['success' => false, 'error' => 'Invalid date format.'];
            }
        }
    
        $sql = "UPDATE user_certificates SET {$field} = :value WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
    
        if ($field === 'valid_until' && $value === null) {
            $stmt->bindValue(':value', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':value', $value, \PDO::PARAM_STR);
        }
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
    
        if ($stmt->execute()) {
            $this->logAction('updateAssignedCertificateField', 'assigned_certificate', $id, $this->user_id, "Updated field '$field' to: " . ($value ?? "NULL"));
            return ['success' => true, 'message' => "Field '$field' has been updated."];
        }
    
        return ['success' => false, 'error' => 'Error updating assigned certificate field.'];
    }
}
