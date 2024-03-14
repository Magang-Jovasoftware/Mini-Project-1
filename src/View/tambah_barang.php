<?php 
include '../Controller/Database.php';
include '../Model/Barang.php';
include '../Controller/BarangController.php';

$database = new Database();
$koneksi = $database->koneksi;

$barangController = new BarangController($database);

// Proses tambah barang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaBarang = $_POST['nama_barang'];
    $stok = $_POST['stok'];

    // Validasi input
    if (empty($namaBarang) || empty($stok)) {
        $pesanError = "Nama barang dan stok harus diisi.";
    } else {
        // Upload gambar
        $gambar = null;
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
            $gambarNama = $_FILES['gambar']['name'];
            $gambarTmp = $_FILES['gambar']['tmp_name'];
            $location = '../images/' . $gambarNama;  // Update the location

            if (move_uploaded_file($gambarTmp, $location)) {
                $gambar = $location;

                $result = $barangController->tambahBarang($namaBarang, $stok, $gambar);

                if ($result['status'] === 'success') {
                    $pesanSuccess = $result['message'];
                } else {
                    $pesanError = $result['message'];
                }
            } else {
                // Handle file upload error
                $pesanError = "Gagal mengunggah file.";
            }
        }
    }
}

// Ambil data barang dari database untuk ditampilkan
$daftarBarang = $barangController->getDaftarBarang();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang</title>
    <link href="../output.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-8 mt-10 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold mb-6">Tambah Barang</h1>

    <?php
    // Tampilkan notifikasi jika ada
    if (isset($pesanSuccess)) {
        echo "<p class=\"text-green-600 font-semibold mb-4\">$pesanSuccess</p>";
    } elseif (isset($pesanError)) {
        echo "<p class=\"text-red-600 font-semibold mb-4\">$pesanError</p>";
    }
    ?>
    <button id="toggleFormButton" class="mb-4 bg-green-500 text-white px-4 py-2 rounded">Tambah Barang</button>
    <!-- Formulir untuk menambah barang -->
    <form id="barangForm" action="" method="post" class="mb-6" style="display: none;" enctype="multipart/form-data">
        <div class="mb-4">
            <label for="nama_barang" class="block text-gray-700 font-bold">Nama Barang:</label>
            <input type="text" name="nama_barang" id="nama_barang" class="w-full p-2 border rounded">
        </div>

        <div class="mb-4">
            <label for="stok" class="block text-gray-700 font-bold">Stok Barang:</label>
            <input type="number" name="stok" id="stok" class="w-full p-2 border rounded">
        </div>

        <div class="mb-4">
            <label for="gambar" class="block text-gray-700 font-bold">Gambar Barang:</label>
            <input type="file" name="gambar" id="gambar" class="w-full p-2 border rounded">
        </div>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Tambah</button>
    </form>

    <!-- Tampilkan daftar barang -->
    <h2 class="text-xl font-bold mb-4">Daftar Barang</h2>
    <table class="border-collapse border border-gray-800 w-full mx-auto">
        <thead>
            <tr class="bg-gray-800 text-white">
                <th class="p-2 text-center">Nama Barang</th>
                <th class="p-2 text-center">Stok</th>
                <th class="p-2 text-center">Gambar</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($daftarBarang as $barang) {
                echo "<tr class=\"border border-gray-800\">";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$barang->getNama()}</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">Stok: {$barang->getStok()}</td>";

                // Tampilkan gambar jika ada
                $gambar = $barang->getGambar();
                echo "<td class=\"p-2 text-center border border-gray-800\">";
                if (!empty($gambar)) {
                    echo "<img src=\"$gambar\" alt=\"{$barang->getNama()}\" class=\"w-20 h-20 object-cover mx-auto\">";
                }
                echo "</td>";

                echo "</tr>";
            }
            ?>
        </tbody>
    </table>

    <a href="../View/index.php" class="text-blue-500">Kembali ke Daftar Peminjaman</a>
</div>

<script>
    // JavaScript untuk menampilkan/menyembunyikan formulir tambah barang
    const toggleFormButton = document.getElementById('toggleFormButton');
    const barangForm = document.getElementById('barangForm');

    toggleFormButton.addEventListener('click', function() {
        if (barangForm.style.display === 'none') {
            barangForm.style.display = 'block';
        } else {
            barangForm.style.display = 'none';
        }
    });
</script>

<?php
// Tutup koneksi database
$koneksi->close();
?>

</body>
</html>