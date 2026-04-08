<?php
class Database {
    
    private $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "stockmindd");

        if ($this->conn->connect_error) {
            die("Koneksi gagal: " . $this->conn->connect_error);
        }
    }

    public function getConnection() {
        return $this->conn;
    }
}