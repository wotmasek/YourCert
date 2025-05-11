<?php

namespace Database; 

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    public $conn;
    private $error_message;

    public function __construct() {
        $config = require __DIR__ . '/../../db_config.php';
        $this->host     = $config['host'];
        $this->db_name  = $config['db_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
    }

    public function getConnection(){
        $this->conn = null;
        try {
            $this->conn = new \PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            $this->error_message = "Connection error: " . $exception->getMessage();
            return false;
        }
        return $this->conn;
    }
}
?>
