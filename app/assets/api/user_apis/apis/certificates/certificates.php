<?php
namespace Api\UserAPI;

use Api\UserAPI; 

require_once __DIR__ . '/../../user_api.php';

class CertificatesMenagment extends UserAPI {
    public function __construct($user_id, \PDO $conn) {
        parent::__construct($user_id, $conn);
    }

    public function createCertificate(string $title, string $description, ?int $course_id = null, ?string $certificate_image_path = null, ?string $valid_until = null) {
        if (!$this->checkRateLimitAction('createCertificate')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_createCertificate']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
    
        // title & description
        if (empty($title) || !validateText($title, 255)) {
            return ['success' => false, 'error' => $this->messages['invalid_title']];
        }
        if (!validateText($description, 1000)) {
            return ['success' => false, 'error' => $this->messages['invalid_description']];
        }
    
        // course_id może być null lub ≥1
        if ($course_id !== null && !validateInt($course_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_course_id']];
        }
    
        // opcjonalna ścieżka do obrazka
        if ($certificate_image_path !== null && !validateText($certificate_image_path, 255)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_image_path']];
        }
    
        // valid_until: dodatkowo sprawdzamy że mieści się w zakresie TIMESTAMP
        if ($valid_until !== null) {
            try {
                $dt = new \DateTime($valid_until);
                $ts = $dt->getTimestamp();
                if ($ts < 0 || $ts > 2147483647) {
                    throw new \Exception();
                }
            } catch (\Exception $e) {
                return ['success' => false, 'error' => $this->messages['invalid_valid_until']];
            }
        }
    
        $sql = "INSERT INTO certificates
                   (course_id, title, description, certificate_image_path, valid_until)
                VALUES
                   (:course_id, :title, :description, :certificate_image_path, :valid_until)";
        $stmt = $this->conn->prepare($sql);
    
        // bindowanie course_id
        if ($course_id === null) {
            $stmt->bindValue(':course_id', null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':course_id', $course_id, \PDO::PARAM_INT);
        }
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':certificate_image_path', $certificate_image_path);
        $stmt->bindValue(':valid_until', $valid_until);
    
        if ($stmt->execute()) {
            $certificate_id = $this->conn->lastInsertId();
            $this->logAction('createCertificate', 'certificate', $certificate_id, null, "Title: $title");
            return [
                'success' => true,
                'certificate_id' => $certificate_id,
                'message' => $this->messages['certificate_created'] ?? 'Certificate created'
            ];
        }
    
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }    
    
    public function updateCertificate($certificate_id, array $fields) {
        if (!$this->checkRateLimitAction('updateCertificate')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateCertificate']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($certificate_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_id']];
        }
    
        $allowed = ['course_id', 'title', 'description', 'certificate_image_path', 'valid_until'];
        $setParts = [];
        $params   = [':certificate_id' => $certificate_id];
    
        foreach ($fields as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            if ($key === 'course_id' && $value !== null && !validateInt($value, 1)) {
                return ['success' => false, 'error' => $this->messages['invalid_course_id']];
            }
            if ($key === 'title' && (!is_string($value) || empty($value) || !validateText($value, 255))) {
                return ['success' => false, 'error' => $this->messages['invalid_title']];
            }
            if ($key === 'description' && (!is_string($value) || !validateText($value, 1000))) {
                return ['success' => false, 'error' => $this->messages['invalid_description']];
            }
            if ($key === 'certificate_image_path' && $value !== null && (!is_string($value) || !validateText($value, 255))) {
                return ['success' => false, 'error' => $this->messages['invalid_certificate_image_path']];
            }
            if ($key === 'valid_until' && $value !== null && !validateDateFormat($value, 'Y-m-d')) {
                return ['success' => false, 'error' => $this->messages['invalid_valid_until']];
            }
    
            $setParts[]        = "`$key` = :$key";
            $params[":$key"] = $value;
        }
    
        if (empty($setParts)) {
            return ['success' => false, 'error' => $this->messages['no_valid_fields_provided']];
        }
    
        $sql  = "UPDATE certificates SET " . implode(', ', $setParts) . " WHERE id = :certificate_id";
        $stmt = $this->conn->prepare($sql);
    
        if (array_key_exists(':course_id', $params)) {
            if ($params[':course_id'] === null) {
                $stmt->bindValue(':course_id', null, \PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':course_id', $params[':course_id'], \PDO::PARAM_INT);
            }
            unset($params[':course_id']);
        }

        foreach ($params as $pKey => $pVal) {
            $stmt->bindValue($pKey, $pVal);
        }
    
        if ($stmt->execute()) {
            $updated = implode(', ', array_keys($fields));
            $this->logAction('updateCertificate', 'certificate', $certificate_id, null, "Updated: $updated");
            return ['success' => true, 'message' => $this->messages['certificate_updated'] ?? 'Certificate updated'];
        }
    
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }    

    public function updateCertificateField($certificate_id, $field, $value) {
        if (!$this->checkRateLimitAction('updateCertificateField')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateCertificateField']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($certificate_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_id']];
        }
        return $this->updateCertificate($certificate_id, [$field => $value]);
    }

    public function getCertificate($certificate_id) {
        if (!$this->checkRateLimitAction('getCertificate')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_getCertificate']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($certificate_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_id']];
        }
        $sql = "SELECT * FROM certificates WHERE id = :certificate_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':certificate_id', $certificate_id, \PDO::PARAM_INT);
        $stmt->execute();
        $certificate = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($certificate) {
            return ['success' => true, 'certificate' => $certificate];
        }
        return ['success' => false, 'error' => $this->messages['certificate_not_found']];
    }

    public function deleteCertificate($certificate_id) {
        if (!$this->checkRateLimitAction('deleteCertificate')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_deleteCertificate']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($certificate_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_certificate_id']];
        }
        $sql = "DELETE FROM certificates WHERE id = :certificate_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':certificate_id', $certificate_id, \PDO::PARAM_INT);
        if ($stmt->execute()) {
            $this->logAction('deleteCertificate', 'certificate', $certificate_id, null, "Certificate deleted");
            return ['success' => true, 'message' => $this->messages['certificate_deleted'] ?? 'Certificate deleted'];
        }
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }
}
