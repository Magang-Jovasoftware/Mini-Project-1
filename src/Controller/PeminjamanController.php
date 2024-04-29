<?php

class PeminjamanController {
    private $database;

    public function __construct($database) {
        $this->database = $database;
    }

    public function pinjamBarang($idBarangs, $idPeminjam, $jumlahPeminjaman) {
        // Validasi input
        if (empty($idBarangs) || empty($idPeminjam) || empty($jumlahPeminjaman) || in_array('', $jumlahPeminjaman)) {
            return ['status' => 'error', 'message' => 'Semua kolom harus diisi'];
        }        

        // Mulai transaksi
        $this->database->beginTransaction();

        // Insert data peminjaman
        $resultPeminjaman = $this->database->query("INSERT INTO peminjaman (id_anggota, id_admin, tanggal_pakai) VALUES ('$idPeminjam', '1', NOW())");

        if (!$resultPeminjaman) {
            $this->database->rollback();
            return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
        }

        // Dapatkan ID peminjaman yang baru saja dibuat
        $peminjamanId = $this->database->insertId();

        // Loop untuk setiap barang yang dipinjam
        for ($i = 0; $i < count($idBarangs); $i++) {
            $idBarang = $idBarangs[$i];
            $jumlah = $jumlahPeminjaman[$i];

            // Cek stok barang
            $stokResult = $this->database->query("SELECT stok FROM barang WHERE id = $idBarang");

            if ($stokResult) {
                $rowStok = $stokResult->fetch_assoc();
                $stok = $rowStok['stok'];

                if ($jumlah > $stok) {
                    $this->database->rollback();
                    return ['status' => 'error', 'message' => 'Stok barang tidak cukup'];
                }

                // Insert data peminjaman_barang
                $resultPeminjamanBarang = $this->database->query("INSERT INTO peminjaman_barang (peminjaman_id, id_barang, jumlah) VALUES ('$peminjamanId', '$idBarang', '$jumlah')");

                if (!$resultPeminjamanBarang) {
                    $this->database->rollback();
                    return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
                }

                // Kurangi stok barang
                $resultKurangiStok = $this->database->query("UPDATE barang SET stok = stok - $jumlah WHERE id = $idBarang");

                if (!$resultKurangiStok) {
                    $this->database->rollback();
                    return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
                }
            } else {
                return ['status' => 'error', 'message' => 'Gagal menyimpan data'];
            }
        }

        // Commit transaksi
        $this->database->commit();

        return ['status' => 'success', 'message' => 'Data berhasil disimpan'];
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
                                                WHERE peminjaman.id NOT IN (
                                                    SELECT DISTINCT pengembalian_barang.peminjaman_id 
                                                    FROM pengembalian_barang
                                                    WHERE pengembalian_barang.id_barang = peminjaman_barang.id_barang
                                                )
                                            ");

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

    public function getDaftarAnggota() {
        $result = $this->database->query("SELECT * FROM anggota");
        $daftarAnggota = [];
        while ($row = $result->fetch_assoc()) {
            $anggota = new Anggota($row['id'], $row['nama']);
            $daftarAnggota[] = $anggota;
        }
        return $daftarAnggota;
    }
}

function groupPeminjamanByPeminjam($peminjamans) {
    $peminjamansGrouped = [];

    foreach ($peminjamans as $peminjaman) {
        $namaPeminjam = $peminjaman->getPeminjam()->getNama();
        $peminjamanId = $peminjaman->getId(); // Nomor identifikasi unik untuk setiap peminjaman

        if (!isset($peminjamansGrouped[$namaPeminjam][$peminjamanId])) {
            $peminjamansGrouped[$namaPeminjam][$peminjamanId] = [];
        }

        $peminjamansGrouped[$namaPeminjam][$peminjamanId][] = $peminjaman;
    }

    return $peminjamansGrouped;
}

function renderPeminjamanTable($peminjamansGrouped) {
    foreach ($peminjamansGrouped as $namaPeminjam => $peminjamansById) {
        foreach ($peminjamansById as $peminjamanId => $peminjamans) {
            $spanCount = count($peminjamans);

            foreach ($peminjamans as $index => $peminjaman) {
                $tanggalPakai = date('l, d-m-Y', strtotime($peminjaman->getTanggalPakai()));
                $namaPeminjamCell = ($index === 0) ? "<td rowspan=\"$spanCount\" class=\"p-2 text-center border border-gray-800\">{$namaPeminjam}</td>" : "";

                echo "<tr class=\"border border-gray-800\">";
                echo $namaPeminjamCell;
                echo "<td class=\"p-2 text-center border border-gray-800\">{$peminjaman->getBarang()->getNama()}</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\"><img src=\"{$peminjaman->getBarang()->getGambar()}\" alt=\"{$peminjaman->getBarang()->getNama()}\" class=\"w-20 h-20 object-cover mt-2 mx-auto\"></td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$peminjaman->getJumlah()} unit</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$tanggalPakai}</td>";
                echo "</tr>";
            }
        }
    }
}