<?php

class BarangController {
    private $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function tambahBarang($namaBarang, $stok, $gambar) {
        $koneksi = $this->database->koneksi;

        // Insert data ke database menggunakan prepared statement
        $stmt = $koneksi->prepare("INSERT INTO barang (nama, stok, gambar) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $namaBarang, $stok, $gambar);
        $result = $stmt->execute();

        if ($result) {
            return ['status' => 'success', 'message' => 'Barang berhasil ditambahkan.'];
        } else {
            return ['status' => 'error', 'message' => 'Gagal menambahkan barang. Error: ' . $stmt->error];
        }
    }

    public function getDaftarBarang() {
        $koneksi = $this->database->koneksi;

        // Ambil data barang dari database untuk ditampilkan
        $result = $koneksi->query("SELECT * FROM barang");
        $daftarBarang = [];
        while ($row = $result->fetch_assoc()) {
            $barang = new Barang($row['id'], $row['nama'], $row['stok'], $row['gambar']);
            $daftarBarang[] = $barang;
        }

        return $daftarBarang;
    }
}
