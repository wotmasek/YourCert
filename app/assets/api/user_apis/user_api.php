<?php
namespace Api;

require_once __DIR__ . '/../action_menager.php';
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../validation_functions.php';

use Api\ActionMenager;

class UserAPI extends ActionMenager {
    protected $user_id;
    protected $permissions;
    protected $messages; 

    public function __construct($user_id, \PDO $conn) {
        parent::__construct($conn);
        $this->user_id = $user_id;
    
        if (!$this->loadRateLimitsConfig('user_apis/rate_limits_config.json')) {
            throw new \RuntimeException("Failed to load rate limits config (rate_limits_config.json)");
        }
    
        $messagesPath = __DIR__ . '/messages.json';
        if (!is_readable($messagesPath)) {
            throw new \RuntimeException("Cannot read messages file: messages.json");
        }
        $msgs = file_get_contents($messagesPath);
        $decoded = json_decode($msgs, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in messages.json: " . json_last_error_msg());
        }
        $this->messages = $decoded;
    
        $this->loadPermissions();
    }

    protected function loadPermissions() {
        $sql = "SELECT ps.*
                FROM users u
                JOIN roles p ON u.permission_id = p.id
                JOIN permission_settings ps ON p.permission_settings_id = ps.id
                WHERE u.id = :user_id AND u.is_active = 1 AND u.is_confirmed = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->permissions = $result ? $result : [];
    }

    public function hasPermission($action) {
        return isset($this->permissions[$action]) && (bool)$this->permissions[$action];
    }

    protected function checkUserPermission($action) {
        if (!$this->hasPermission($action)) {
            $msg = $this->messages["permission_denied_$action"] ?? "Permission denied for action: $action";
            return ['success' => false, 'error' => $msg];
        }
        return true;
    }
}
?>
