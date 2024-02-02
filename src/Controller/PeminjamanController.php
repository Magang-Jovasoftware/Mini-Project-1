<?php

class PeminjamanController {
    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function pinjamBarang($idBarang, $peminjamNama, $jumlahPeminjaman) {
        
        // Validasi input
        if (empty($idBarang) || empty($peminjamNama) || empty($jumlahPeminjaman)) {
            return ['status' => 'error', 'message' => 'Semua kolom harus diisi'];
        }

        // Cek stok barang
        $stokResult = $this->database->query("SELECT stok FROM barang WHERE id = $idBarang");

        if ($stokResult) {
            $rowStok = $stokResult->fetch_assoc();
            $stok = $rowStok['stok'];

            if ($jumlahPeminjaman > $stok) {
                return ['status' => 'error', 'message' => 'Stok barang tidak cukup'];
            }

            // Mulai transaksi
            $this->database->beginTransaction();

            // Insert data anggota (jika belum ada)
            $resultAnggota = $this->database->query("SELECT id FROM anggota WHERE nama = '$peminjamNama'");

            if ($resultAnggota->num_rows === 0) {
                $resultInsertAnggota = $this->database->query("INSERT INTO anggota (nama) VALUES ('$peminjamNama')");

                if (!$resultInsertAnggota) {
                    $this->database->rollback();
                    return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
                }
            }

            // Dapatkan ID anggota
            $resultAnggota = $this->database->query("SELECT id FROM anggota WHERE nama = '$peminjamNama'");
            $rowAnggota = $resultAnggota->fetch_assoc();
            $idAnggota = $rowAnggota['id'];

            // Insert data peminjaman
            $resultPeminjaman = $this->database->query("INSERT INTO peminjaman (id_anggota, id_admin, tanggal_pakai) VALUES ('$idAnggota', '1', NOW())");

            if (!$resultPeminjaman) {
                $this->database->rollback();
                return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
            }

            // Dapatkan ID peminjaman yang baru saja dibuat
            $peminjamanId = $this->database->insertId();

            // Insert data peminjaman_barang
            $resultPeminjamanBarang = $this->database->query("INSERT INTO peminjaman_barang (peminjaman_id, id_barang, jumlah) VALUES ('$peminjamanId', '$idBarang', '$jumlahPeminjaman')");

            if (!$resultPeminjamanBarang) {
                $this->database->rollback();
                return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
            }

            // Kurangi stok barang
            $resultKurangiStok = $this->database->query("UPDATE barang SET stok = stok - $jumlahPeminjaman WHERE id = $idBarang");

            if (!$resultKurangiStok) {
                $this->database->rollback();
                return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
            }

            // Commit transaksi
            $this->database->commit();

            return ['status' => 'success', 'message' => 'Data berhasil disimpan'];
        } else {
            return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
        }
    }

    // Metode untuk mengambil data peminjaman dari database
    public function getDaftarPeminjaman()
    {
        $koneksi = $this->database->koneksi;
        $resultPeminjaman = $koneksi->query("SELECT peminjaman.*, anggota.nama AS nama_peminjam, barang.nama AS nama_barang, peminjaman_barang.jumlah, barang.gambar, admin.nama AS nama_admin
                                    FROM peminjaman 
                                    JOIN peminjaman_barang ON peminjaman.id = peminjaman_barang.peminjaman_id
                                    JOIN barang ON peminjaman_barang.id_barang = barang.id
                                    JOIN anggota ON peminjaman.id_anggota = anggota.id
                                    JOIN admin ON peminjaman.id_admin = admin.id
                                    ORDER BY peminjaman.id DESC");

        $peminjamans = [];
        while ($rowPeminjaman = $resultPeminjaman->fetch_assoc()) {
            $barang = new Barang($rowPeminjaman['id'], $rowPeminjaman['nama_barang'], 0, $rowPeminjaman['gambar']);
            $peminjam = new Anggota($rowPeminjaman['id'], $rowPeminjaman['nama_peminjam']);
            $admin = new Admin($rowPeminjaman['id'], $rowPeminjaman['nama_admin']);
            $peminjaman = new Peminjaman($rowPeminjaman['id'], $barang, $peminjam, $admin, $rowPeminjaman['tanggal_pakai'], $rowPeminjaman['jumlah']);

            $peminjamans[] = $peminjaman;
        }

        return $peminjamans;
    }
}
