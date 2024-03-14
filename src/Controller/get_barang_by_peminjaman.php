<?php
include '../Controller/Database.php';

// Ambil ID peminjaman dari URL
$idPeminjaman = $_GET['id'];

$database = new Database();
$koneksi = $database->koneksi;

// Query untuk mengambil barang berdasarkan ID peminjaman
$query = "SELECT peminjaman_barang.*, barang.nama AS nama_barang
        FROM peminjaman_barang
        JOIN barang ON peminjaman_barang.id_barang = barang.id
        WHERE peminjaman_barang.peminjaman_id = $idPeminjaman";

$result = $koneksi->query($query);

$daftarBarang = [];
while ($row = $result->fetch_assoc()) {
    $daftarBarang[] = [
        'id_barang' => $row['id_barang'],
        'nama_barang' => $row['nama_barang'],
        'jumlah' => $row['jumlah']
    ];
}

// Mengembalikan data dalam format JSON
header('Content-Type: application/json');
echo json_encode($daftarBarang);
?>
