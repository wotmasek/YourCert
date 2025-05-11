<?php
namespace Api\UserAPI;

use Api\UserAPI; 

require_once __DIR__ . '/../../user_api.php';

class CourseMenagment extends UserAPI {
    public function __construct($user_id, \PDO $conn) {
        parent::__construct($user_id, $conn);
    }

    public function createCourse($title, $description, $course_author) {
        if (!$this->checkRateLimitAction('createCourse')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_createCourse']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!is_string($title) || empty($title) || !validateText($title, 255)) {
            return ['success' => false, 'error' => $this->messages['invalid_title']];
        }
        if (!is_string($description) || !validateText($description, 1000)) {
            return ['success' => false, 'error' => $this->messages['invalid_description']];
        }
        if (!is_string($course_author) || empty($course_author) || !validateText($course_author, 255)) {
            return ['success' => false, 'error' => $this->messages['invalid_course_author']];
        }
        
        $sql = "INSERT INTO courses (title, description, course_author) VALUES (:title, :description, :course_author)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':course_author', $course_author);
        if ($stmt->execute()) {
            $course_id = $this->conn->lastInsertId();
            $this->logAction('createCourse', 'course', $course_id, null, "Title: $title");
            return ['success' => true, 'course_id' => $course_id, 'message' => $this->messages['course_created'] ?? 'Course created'];
        }
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }
    
    public function updateCourse($course_id, array $fields) {
        if (!$this->checkRateLimitAction('updateCourse')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateCourse']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($course_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_course_id']];
        }
        
        $allowed = ['title', 'description', 'course_author'];
        $setParts = [];
        $params = [':course_id' => $course_id];
        foreach ($fields as $key => $value) {
            if (in_array($key, $allowed)) {
                if ($key === 'title') {
                    if (!is_string($value) || empty($value) || !validateText($value, 255)) {
                        return ['success' => false, 'error' => $this->messages['invalid_title']];
                    }
                }
                if ($key === 'description') {
                    if (!is_string($value) || !validateText($value, 1000)) {
                        return ['success' => false, 'error' => $this->messages['invalid_description']];
                    }
                }
                if ($key === 'course_author' && !validateInt($value, 1)) {
                    return ['success' => false, 'error' => $this->messages['invalid_course_author']];
                }
                $setParts[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
        }
        if (empty($setParts)) {
            return ['success' => false, 'error' => $this->messages['no_valid_fields_provided']];
        }
        $sql = "UPDATE courses SET " . implode(', ', $setParts) . " WHERE id = :course_id";
        $stmt = $this->conn->prepare($sql);
        if ($stmt->execute($params)) {
            $updatedFields = implode(', ', array_keys($fields));
            $this->logAction('updateCourse', 'course', $course_id, null, "Updated fields: $updatedFields");
            return ['success' => true, 'message' => $this->messages['course_updated'] ?? 'Course updated'];
        }
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }

    public function updateCourseField($course_id, $field, $value) {
        if (!$this->checkRateLimitAction('updateCourseField')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_updateCourseField']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($course_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_course_id']];
        }
        return $this->updateCourse($course_id, [$field => $value]);
    }

    public function deleteCourse($course_id) {
        if (!$this->checkRateLimitAction('deleteCourse')) {
            return ['success' => false, 'error' => $this->messages['rate_limit_exceeded_deleteCourse']];
        }
        $permCheck = $this->checkUserPermission(__FUNCTION__);
        if ($permCheck !== true) {
            return $permCheck;
        }
        if (!validateInt($course_id, 1)) {
            return ['success' => false, 'error' => $this->messages['invalid_course_id']];
        }
        $sql = "DELETE FROM courses WHERE id = :course_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':course_id', $course_id, \PDO::PARAM_INT);
        if ($stmt->execute()) {
            $this->logAction('deleteCourse', 'course', $course_id, null, "Course deleted");
            return ['success' => true, 'message' => $this->messages['course_deleted'] ?? 'Course deleted'];
        }
        return ['success' => false, 'error' => $stmt->errorInfo()];
    }
}