<?php
include '../Controller/Database.php';
include '../Model/Barang.php';
include '../Model/Anggota.php';
include '../Model/Admin.php';
include '../Model/Peminjaman.php';
include '../Controller/PeminjamanController.php';
include '../Controller/BarangController.php';

$database = new Database();
$koneksi = $database->koneksi;

$barangController = new BarangController($database);
$peminjamanController = new PeminjamanController($database);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idBarang = $_POST['barang'];
    $idPeminjam = $_POST['peminjam'];
    $jumlahPeminjaman = $_POST['jumlah'];

    $result = $peminjamanController->pinjamBarang($idBarang, $idPeminjam, $jumlahPeminjaman);

    header("Location: index.php?status={$result['status']}&message={$result['message']}");
}

$peminjamans = $peminjamanController->getDaftarPeminjaman();
$daftarBarang = $barangController->getDaftarBarang();
$daftarAnggota = $peminjamanController->getDaftarAnggota();
$peminjamansGrouped = groupPeminjamanByPeminjam($peminjamans);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Barang</title>
    <link href="../output.css" rel="stylesheet">    
</head>
<body>
<div class="min-h-screen bg-gray-100 flex">
<?php include 'sidebar.php'; ?>
<div class="flex-grow">
<div class="container mx-auto p-8 mt-10 bg-white rounded-lg shadow-lg">
    <h1 class="text-2xl font-bold mb-6">Peminjaman Barang</h1>

    <?php
    // Tampilkan notifikasi jika ada
    if (isset($_GET['status'])) {
        $status = $_GET['status'];
        $message = $_GET['message'];
        $colorClass = ($status == 'success') ? 'text-green-600' : 'text-red-600';

        echo "<p class=\"$colorClass font-semibold mb-4\">$message</p>";
    }
    ?>
    <!-- catatan -->
    <div class="mb-4 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4" role="alert">
        <p class="font-bold">Catatan:</p>
        <p>Peminjaman barang memiliki batas waktu maksimal 3 hari. Melebihi batas waktu tersebut akan dikenakan denda sebesar Rp 50.000 per hari.</p>
    </div>

    <button id="toggleFormButton" class="mb-4 bg-green-500 text-white px-4 py-2 rounded">Pinjam Barang</button>
    <form id="idForm" action="index.php" style="display: none;" method="post" class="mb-6">
        <div id="barang-form">
            <div class="mb-4">
                    <label for="peminjam" class="block text-gray-700 font-bold">Nama Peminjam:</label>
                    <select name="peminjam" id="peminjam" class="w-full p-2 border rounded">
                        <?php
                        // Tampilkan daftar anggota dari controller
                        foreach ($peminjamanController->getDaftarAnggota() as $anggota) {
                            echo "<option value=\"{$anggota->getId()}\">{$anggota->getNama()}</option>";
                        }
                        ?>
                    </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="barang" class="block text-gray-700 font-bold">Pilih Barang:</label>
                    <select name="barang[]" id="barang" class="w-full p-2 border rounded">
                    <?php
                        // Tampilkan daftar barang dari controller
                        foreach ($daftarBarang as $barang) {
                            echo "<option value=\"{$barang->getId()}\">{$barang->getNama()}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label for="jumlah" class="block text-gray-700 font-bold">Jumlah Peminjaman:</label>
                    <input type="number" name="jumlah[]" id="jumlah" class="w-full p-2 border rounded">
                </div>
            </div>
        </div>

        <button type="button" id="tambah-barang" class="bg-blue-500 text-white px-4 py-2 rounded">Tambah Barang</button>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Submit</button>
    </form>

    <!-- Tampilkan daftar peminjaman barang -->
    <h2 class="text-xl font-bold mb-4">Daftar Peminjaman Barang</h2>
    <table class="border-collapse border border-gray-800 w-full mx-auto mb-6">
        <thead>
            <tr class="bg-gray-800 text-white">
                <th class="p-2 text-center">Nama Peminjam</th>
                <th class="p-2 text-center">Nama Barang</th>
                <th class="p-2 text-center">Gambar</th>
                <th class="p-2 text-center">Jumlah Barang</th>
                <th class="p-2 text-center">Tanggal Pakai</th>
            </tr>
        </thead>
        <tbody>
            <?php renderPeminjamanTable($peminjamansGrouped); ?>
        </tbody>
    </table>
</div>
</div>
</div>

<?php
// Tutup koneksi database
$database->closeConnection();
?>

<script>
    // Tambahkan event listener untuk tombol "Tambah Barang"
    document.getElementById('tambah-barang').addEventListener('click', function() {
        // Dapatkan elemen form
        var form = document.getElementById('barang-form');

        // Buat elemen baru untuk baris baru
        var newRow = document.createElement('div');
        newRow.classList.add('grid', 'grid-cols-1', 'md:grid-cols-2', 'gap-4', 'barang-form-row');

        // Tambahkan elemen select untuk barang
        var barangSelect = document.createElement('select');
        barangSelect.name = 'barang[]';
        barangSelect.id = 'barang';
        barangSelect.classList.add('w-full', 'p-2', 'border', 'rounded');

        // Tampilkan daftar barang dari controller
        <?php
        foreach ($daftarBarang as $barang) {
            echo "var option = document.createElement('option');";
            echo "option.value = '{$barang->getId()}';";
            echo "option.text = '{$barang->getNama()}';";
            echo "barangSelect.appendChild(option);";
        }
        ?>

        // Tambahkan elemen input untuk jumlah
        var jumlahInput = document.createElement('input');
        jumlahInput.type = 'number';
        jumlahInput.name = 'jumlah[]';
        jumlahInput.id = 'jumlah';
        jumlahInput.classList.add('w-full', 'p-2', 'border', 'rounded');

        // Tambahkan elemen baru ke dalam form
        newRow.appendChild(barangSelect);
        newRow.appendChild(jumlahInput);
        form.appendChild(newRow);
    });

    // JavaScript untuk menampilkan/menyembunyikan formulir tambah barang
    const toggleFormButton = document.getElementById('toggleFormButton');
        const barangForm = document.getElementById('idForm');

        toggleFormButton.addEventListener('click', function() {
            if (barangForm.style.display === 'none') {
                barangForm.style.display = 'block';
            } else {
                barangForm.style.display = 'none';
            }
        });
</script>

</body>
</html>