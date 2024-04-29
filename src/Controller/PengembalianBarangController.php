<?php
include '../Model/Barang.php';

class PengembalianBarangController {
    private $database;

    public function __construct(Database $database) {
        $this->database = $database;
    }

    public function prosesPengembalian($idPeminjaman, $barangArray) {
        $koneksi = $this->database->koneksi;
        $totalBarangDikembalikan = count($barangArray);
    
        // Mulai transaksi
        $koneksi->begin_transaction();
    
        foreach ($barangArray as $idBarang) {
            // Ambil jumlah barang yang dipinjam
            $queryJumlahPeminjaman = "SELECT jumlah FROM peminjaman_barang WHERE peminjaman_id = $idPeminjaman AND id_barang = $idBarang";
            $resultJumlahPeminjaman = $koneksi->query($queryJumlahPeminjaman);
    
            if ($resultJumlahPeminjaman && $resultJumlahPeminjaman->num_rows > 0) {
                $rowJumlahPeminjaman = $resultJumlahPeminjaman->fetch_assoc();
                $jumlahPeminjaman = $rowJumlahPeminjaman['jumlah'];
    
                // Hitung selisih hari antara tanggal kembali dan tanggal pakai
                $tanggalPakai = $koneksi->query("SELECT tanggal_pakai FROM peminjaman WHERE id = $idPeminjaman")->fetch_assoc()['tanggal_pakai'];
                $tanggalKembali = date("Y-m-d");
                $selisihHari = strtotime($tanggalKembali) - strtotime($tanggalPakai);
                $selisihHari = floor($selisihHari / (60 * 60 * 24));

                // Jika selisih hari lebih dari 3, tambahkan denda
                if ($selisihHari > 3) {
                    $denda = ($selisihHari - 3) * 50000;
                } else {
                    $denda = 0;
                }
                
                // Insert data pengembalian_barang beserta denda
                $resultPengembalianBarang = $koneksi->query("INSERT INTO pengembalian_barang (id_barang, jumlah, tanggal_kembali, denda, peminjaman_id) 
                                                VALUES ('$idBarang', $jumlahPeminjaman, NOW(), $denda, $idPeminjaman)");
    
                if (!$resultPengembalianBarang) {
                    $koneksi->rollback();
                    return ['status' => 'error', 'message' => 'Gagal menyimpan data pengembalian'];
                }
    
                // Tambah stok barang
                $resultTambahStok = $koneksi->query("UPDATE barang SET stok = stok + $jumlahPeminjaman WHERE id = $idBarang");
    
                if (!$resultTambahStok) {
                    $koneksi->rollback();
                    return ['status' => 'error', 'message' => 'Gagal menambah stok barang'];
                }
            } 
            
        }
    
        // Commit transaksi
        $koneksi->commit();
    
        return ['status' => 'success', 'message' => "$totalBarangDikembalikan barang berhasil dikembalikan"];
    }
    
    

    public function getDaftarPengembalian() {
        $koneksi = $this->database->koneksi;

        // Ambil data pengembalian barang dari database
        $resultPengembalian = $koneksi->query("SELECT pengembalian_barang.*, barang.nama, peminjaman_barang.jumlah AS jumlah_peminjaman, 
                                                peminjaman.tanggal_pakai, anggota.nama AS nama_peminjam 
                                                FROM pengembalian_barang 
                                                JOIN barang ON pengembalian_barang.id_barang = barang.id
                                                JOIN peminjaman ON pengembalian_barang.peminjaman_id = peminjaman.id
                                                JOIN anggota ON peminjaman.id_anggota = anggota.id
                                                JOIN peminjaman_barang ON peminjaman_barang.peminjaman_id = peminjaman.id
                                                WHERE pengembalian_barang.peminjaman_id = peminjaman_barang.peminjaman_id
                                                GROUP BY pengembalian_barang.peminjaman_id, pengembalian_barang.id_barang
                                                ORDER BY pengembalian_barang.peminjaman_id");

        $daftarPengembalian = [];
        while ($rowPengembalian = $resultPengembalian->fetch_assoc()) {
            $daftarPengembalian[] = $rowPengembalian;
        }

        return $daftarPengembalian;
    }
}
