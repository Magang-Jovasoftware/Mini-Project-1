<?php
include '../Controller/Database.php';
include '../Controller/Barang.php';
include '../Controller/Anggota.php';
include '../Model/Admin.php';
include '../Mode/Peminjaman.php';
include '../Controller/PeminjamanController.php';

$database = new Database();
$koneksi = $database->koneksi;

$peminjamanController = new PeminjamanController($database);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idBarang = $_POST['barang'];
    $peminjamNama = $_POST['peminjam'];
    $jumlahPeminjaman = $_POST['jumlah'];

    $result = $peminjamanController->pinjamBarang($idBarang, $peminjamNama, $jumlahPeminjaman);

    header("Location: index.php?status={$result['status']}&message={$result['message']}");
    exit();
}

$database->closeConnection();
?>
