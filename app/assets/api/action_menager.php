<?php
namespace Api;

class ActionMenager {
    protected $conn;
    protected $rateLimitsConfig;

    public function __construct(\PDO $conn) {
        $this->conn = $conn;
    }

    public function checkRateLimit($userId, $action, $limit, $timeWindowSeconds) {
        $now = time();
        $sql = "SELECT id, count, UNIX_TIMESTAMP(reset_time) AS reset_time FROM rate_limits 
                WHERE user_id = :user_id AND action = :action";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->execute();
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($record) {
            if ($now > $record['reset_time']) {
                $sqlUpdate = "UPDATE rate_limits SET count = 1, reset_time = FROM_UNIXTIME(:new_reset_time) 
                              WHERE id = :id";
                $stmtUpdate = $this->conn->prepare($sqlUpdate);
                $newResetTime = $now + $timeWindowSeconds;
                $stmtUpdate->bindParam(':new_reset_time', $newResetTime, \PDO::PARAM_INT);
                $stmtUpdate->bindParam(':id', $record['id']);
                $stmtUpdate->execute();
                return true;
            } elseif ($record['count'] < $limit) {
                $sqlUpdate = "UPDATE rate_limits SET count = count + 1 WHERE id = :id";
                $stmtUpdate = $this->conn->prepare($sqlUpdate);
                $stmtUpdate->bindParam(':id', $record['id']);
                $stmtUpdate->execute();
                return true;
            } else {
                return false;
            }
        } else {
            $resetTime = $now + $timeWindowSeconds;
            $sqlInsert = "INSERT INTO rate_limits (user_id, action, count, reset_time)
                          VALUES (:user_id, :action, 1, FROM_UNIXTIME(:reset_time))";
            $stmtInsert = $this->conn->prepare($sqlInsert);
            $stmtInsert->bindParam(':user_id', $userId);
            $stmtInsert->bindParam(':action', $action);
            $stmtInsert->bindParam(':reset_time', $resetTime, \PDO::PARAM_INT);
            $stmtInsert->execute();
            return true;
        }
    }

    protected function logAction($action, $entity, $entity_id = null, $details = '', $performed_by = null) {
        if (func_num_args() === 5 && $details === null) {
            $details = $performed_by;
            $performed_by = 0;
        }
        $performed_by = $performed_by ?? 0;
        
        $sql = "INSERT INTO action_logs (performed_by, target_user, action, entity, entity_id, details)
                VALUES (:performed_by, :target_user, :action, :entity, :entity_id, :details)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':performed_by', $performed_by, \PDO::PARAM_INT);
        $stmt->bindValue(':target_user', null, \PDO::PARAM_NULL);
        $stmt->bindValue(':action', $action);
        $stmt->bindValue(':entity', $entity);
        $stmt->bindValue(':entity_id', $entity_id);
        $stmt->bindValue(':details', $details);
        $stmt->execute();
    }
    
    protected function checkRateLimitAction($userId, $action = null, $limit = null, $timeWindowSeconds = null) {
        if ($action === null) {
            $action = $userId;
            if (isset($this->rateLimitsConfig[$action])) {
                $limit = $this->rateLimitsConfig[$action]['limit'];
                $timeWindowSeconds = $this->rateLimitsConfig[$action]['timeWindow'];
                $userId = property_exists($this, 'user_id') ? $this->user_id : (defined('self::SYSTEM_ID') ? self::SYSTEM_ID : 0);
            } else {
                return true;
            }
        }
        return $this->checkRateLimit($userId, $action, $limit, $timeWindowSeconds);
    }

    protected function loadRateLimitsConfig(string $relativePath): bool {
        $path = __DIR__ . '/' . ltrim($relativePath, '/');
        if (!is_readable($path)) {
            return false;
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        $this->rateLimitsConfig = $data;
        return true;
    }
}
?>
