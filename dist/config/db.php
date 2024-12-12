<?php
class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'grading_db';
    private $conn = null;

    public function connect() {
        if ($this->conn === null) {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            if ($this->conn->connect_error) {
                die('Connection Failed: ' . $this->conn->connect_error);
            }
        }
        return $this->conn;
    }
}
?>