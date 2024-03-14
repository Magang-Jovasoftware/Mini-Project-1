<?php
include '../Model/Barang.php';

class PengembalianBarangController {
    private $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function prosesPengembalian($idPeminjaman, $idBarang, $jumlahPengembalian) {
        $koneksi = $this->database->koneksi;
    
        // Mulai transaksi
        $koneksi->begin_transaction();
    
        // Insert data pengembalian_barang
        $resultPengembalianBarang = $koneksi->query("INSERT INTO pengembalian_barang (id_barang, jumlah, tanggal_kembali) VALUES ('$idBarang', '$jumlahPengembalian', NOW())");
    
        if (!$resultPengembalianBarang) {
            $koneksi->rollback();
            return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
        }
    
        // Tambah stok barang
        $resultTambahStok = $koneksi->query("UPDATE barang SET stok = stok + $jumlahPengembalian WHERE id = $idBarang");
    
        if (!$resultTambahStok) {
            $koneksi->rollback();
            return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
        }
    
        // Hapus data peminjaman_barang berdasarkan peminjaman_id
        $resultHapusPeminjaman = $koneksi->query("DELETE FROM peminjaman_barang WHERE peminjaman_id = $idPeminjaman AND id_barang = $idBarang AND jumlah = $jumlahPengembalian");
    
        if (!$resultHapusPeminjaman) {
            $koneksi->rollback();
            return ['status' => 'error', 'message' => 'Gagal menghapus data peminjaman'];
        }

        // Cek apakah masih ada barang yang dipinjam oleh peminjaman ini
        $resultSisaBarang = $koneksi->query("SELECT COUNT(*) AS sisa_barang FROM peminjaman_barang WHERE peminjaman_id = $idPeminjaman");
        $dataSisaBarang = $resultSisaBarang->fetch_assoc();
        $sisaBarang = $dataSisaBarang['sisa_barang'];

        if ($sisaBarang == 0) {
            // Hapus data peminjaman
            $resultHapusPeminjaman = $koneksi->query("DELETE FROM peminjaman WHERE id = $idPeminjaman");

            if (!$resultHapusPeminjaman) {
                $koneksi->rollback();
                return ['status' => 'error', 'message' => 'Gagal menghapus data peminjaman'];
            }
        }
    
        // Commit transaksi
        $koneksi->commit();
    
        return ['status' => 'success', 'message' => 'Data berhasil disimpan'];
    }

    public function getDaftarPengembalian() {
        $koneksi = $this->database->koneksi;

        // Ambil data pengembalian barang dari database
        $resultPengembalian = $koneksi->query("SELECT pengembalian_barang.*, barang.nama 
                                                FROM pengembalian_barang 
                                                JOIN barang ON pengembalian_barang.id_barang = barang.id
                                                ORDER BY pengembalian_barang.tanggal_kembali DESC");

        $daftarPengembalian = [];
        while ($rowPengembalian = $resultPengembalian->fetch_assoc()) {
            $daftarPengembalian[] = $rowPengembalian;
        }

        return $daftarPengembalian;
    }
}
