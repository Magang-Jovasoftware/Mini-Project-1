<?php

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "mini_project_1";
    public $koneksi;

    public function __construct() {
        $this->koneksi = new mysqli($this->host, $this->username, $this->password, $this->database);

        // Cek koneksi
        if ($this->koneksi->connect_error) {
            die("Koneksi Gagal: " . $this->koneksi->connect_error);
        }
    }

    public function query($sql) {
        return $this->koneksi->query($sql);
    }

    public function beginTransaction() {
        $this->koneksi->begin_transaction();
    }

    public function commit() {
        $this->koneksi->commit();
    }

    public function rollback() {
        $this->koneksi->rollback();
    }

    public function insertId() {
        return $this->koneksi->insert_id;
    }

    public function closeConnection() {
        $this->koneksi->close();
    }
}
