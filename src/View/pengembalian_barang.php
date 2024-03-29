<?php
include '../Controller/Database.php';
include '../Controller/BarangController.php';
include '../Controller/PengembalianBarangController.php';

$database = new Database();
$koneksi = $database->koneksi;  

$barangController = new BarangController($database);
$pengembalianController = new PengembalianBarangController($database);

// Proses pengembalian barang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPeminjaman = $_POST['peminjaman'];
    $idBarang = $_POST['barang'];
    $jumlahPengembalian = $_POST['jumlah'];

    // Validasi input
    if (empty($idPeminjaman) || empty($idBarang) || empty($jumlahPengembalian)) {
        header("Location: pengembalian_barang.php?status=error&message=Semua%20kolom%20harus%20diisi");
        exit();
    }

    // Proses pengembalian menggunakan PengembalianController
    $result = $pengembalianController->prosesPengembalian($idPeminjaman, $idBarang, $jumlahPengembalian);

    if ($result['status'] === 'error') {
        header("Location: pengembalian_barang.php?status=error&message=" . urlencode($result['message']));
        exit();
    }

    header("Location: pengembalian_barang.php?status=success&message=" . urlencode($result['message']));
    exit();
}

// Ambil data pengembalian barang dari database
$daftarPengembalian = $pengembalianController->getDaftarPengembalian();

// Ambil data peminjaman dari database untuk dropdown
$daftarPeminjaman = $koneksi->query("SELECT peminjaman.*, anggota.nama AS nama_peminjam
                                    FROM peminjaman
                                    JOIN anggota ON peminjaman.id_anggota = anggota.id
                                    ORDER BY peminjaman.id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Barang</title>
    <link href="../output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-8 mt-10 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold mb-6">Pengembalian Barang</h1>

    <?php
    // Tampilkan notifikasi jika ada
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $message = $_GET['message'];
        $colorClass = ($status == 'success') ? 'text-green-600' : 'text-red-600';

        echo "<p class=\"$colorClass font-semibold mb-4\">$message</p>";
    }
    ?>

    <form action="pengembalian_barang.php" method="post" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="peminjaman" class="block text-gray-700 font-bold">Pilih Peminjaman:</label>
                <select name="peminjaman" id="peminjaman" class="w-full p-2 border rounded">
                    <option value="" selected disabled>Pilih Peminjaman</option>
                    <?php
                    // Tampilkan daftar peminjaman yang sedang berlangsung
                    while ($rowPeminjaman = $daftarPeminjaman->fetch_assoc()) {
                        echo "<option value=\"{$rowPeminjaman['id']}\">{$rowPeminjaman['nama_peminjam']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="barang" class="block text-gray-700 font-bold">Barang yang Dipinjam:</label>
                <select name="barang" id="barang" class="w-full p-2 border rounded">
                </select>   
            </div>
            <div class="mb-4">
                <label for="jumlah" class="block text-gray-700 font-bold">Jumlah Pengembalian:</label>
                <input type="number" name="jumlah" id="jumlah" class="w-full p-2 border rounded" value="<?php echo $rowBarangDipinjam['jumlah']; ?>" readonly>
            </div>
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Kembalikan Barang</button>
    </form>

    <!-- Tampilkan daftar pengembalian barang -->
    <h2 class="text-xl font-bold mb-4">Daftar Pengembalian Barang</h2>
    <table class="border-collapse border border-gray-800 w-full mx-auto">
        <thead>
            <tr class="bg-gray-800 text-white">
                <th class="p-2 text-center">Nama Barang</th>
                <th class="p-2 text-center">Jumlah Barang</th>
                <th class="p-2 text-center">Tanggal Kembali</th>
                <th class="p-2 text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($daftarPengembalian as $pengembalian) {
                echo "<tr class=\"border border-gray-800\">";
                $tanggalKembali = date("d-m-Y", strtotime($pengembalian['tanggal_kembali']));
                echo "<td class=\"p-2 text-center border border-gray-800\">{$pengembalian['nama']}</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$pengembalian['jumlah']} unit</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$tanggalKembali}</td>";
                
                // Tambahkan kondisi untuk menampilkan status
                $statusKeterangan = $pengembalian['pengembalian_id'] ? 'Sudah Kembali' : 'Belum Kembali';
                $statusWarna = $pengembalian['pengembalian_id'] ? 'text-green-600' : 'text-red-600';
                echo "<td class=\"p-2 text-center border border-gray-800 {$statusWarna}\">{$statusKeterangan}</td>";

                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <a href="../View/index.php" class="text-blue-500">Kembali ke Daftar Peminjaman</a>
</div>

<script>
document.getElementById('peminjaman').addEventListener('change', function() {
    var idPeminjaman = this.value;
    fetch('../Controller/get_barang_by_peminjaman.php?id=' + idPeminjaman)
        .then(response => response.json())
        .then(data => {
            document.getElementById('barang').innerHTML = '';
            data.forEach(barang => {
                var option = document.createElement('option');
                option.value = barang.id_barang;
                option.textContent = barang.nama_barang;
                option.setAttribute('data-jumlah-peminjaman', barang.jumlah);
                document.getElementById('barang').appendChild(option);
            });
            updateJumlahPeminjaman();
        });
});

document.getElementById('barang').addEventListener('change', function() {
    updateJumlahPeminjaman();
});

function updateJumlahPeminjaman() {
    var jumlahPeminjaman = document.getElementById('barang').selectedOptions[0].getAttribute('data-jumlah-peminjaman');
    document.getElementById('jumlah').value = jumlahPeminjaman;
}
</script>


</body>
</html>