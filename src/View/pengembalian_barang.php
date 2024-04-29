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
    $barangArray = $_POST['barang']; // Array of selected barang IDs

    // Validasi input
    if (empty($idPeminjaman) || empty($barangArray)) {
        header("Location: pengembalian_barang.php?status=error&message=Harap%20pilih%20barang%20yang%20akan%20dikembalikan");
        exit();
    }

    // Proses pengembalian menggunakan PengembalianController
    $result = $pengembalianController->prosesPengembalian($idPeminjaman, $barangArray);

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
                                        WHERE peminjaman.id NOT IN (
                                            SELECT pengembalian_barang.peminjaman_id
                                            FROM pengembalian_barang
                                            JOIN (
                                                SELECT peminjaman_id, COUNT(*) as jumlah_barang_dikembalikan
                                                FROM pengembalian_barang
                                                GROUP BY peminjaman_id
                                            ) AS barang_dikembalikan ON pengembalian_barang.peminjaman_id = barang_dikembalikan.peminjaman_id
                                            JOIN (
                                                SELECT peminjaman_id, COUNT(*) as jumlah_barang_dipinjam
                                                FROM peminjaman_barang
                                                GROUP BY peminjaman_id
                                            ) AS barang_dipinjam ON pengembalian_barang.peminjaman_id = barang_dipinjam.peminjaman_id
                                            WHERE barang_dikembalikan.jumlah_barang_dikembalikan = barang_dipinjam.jumlah_barang_dipinjam
                                        )
                                    ");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Barang</title>
    <link href="../output.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;800&display=swap"
    rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
<div class="min-h-screen bg-gray-100 flex">
<?php include 'sidebar.php'; ?>
<div class="flex-grow">
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

    <button id="toggleFormButton" class="mb-4 bg-green-500 text-white px-4 py-2 rounded">Pengembalian</button>
    <form id="pengembalianForm" action="pengembalian_barang.php" style="display: none;" method="post" class="mb-6">
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
        </div>

            <!-- Tambahkan tabel untuk menampilkan barang yang dipinjam -->
            <table class="border-collapse border border-gray-800 w-full mx-auto mb-6">
                <thead>
                    <tr class="bg-gray-800 text-white">
                        <th class="p-2 text-center">Nama Barang</th>
                        <th class="p-2 text-center">Jumlah Barang</th>
                        <th class="p-2 text-center">Kembalikan</th>
                    </tr>
                </thead>
                <tbody id="daftarBarang">
                    <!-- Daftar barang yang dipinjam akan ditampilkan di sini -->
                </tbody>
            </table>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Kembalikan Barang</button>
    </form>

    <!-- Tampilkan daftar pengembalian barang -->
    <h2 class="text-xl font-bold mb-4">Daftar Pengembalian Barang</h2>
    <table class="border-collapse border border-gray-800 w-full mx-auto">
        <thead>
            <tr class="bg-gray-800 text-white">
                <th class="p-2 text-center">Nama Peminjam</th>
                <th class="p-2 text-center">Nama Barang</th>
                <th class="p-2 text-center">Jumlah Barang</th>
                <th class="p-2 text-center">Tanggal Pakai</th>
                <th class="p-2 text-center">Tanggal Kembali</th>
                <th class="p-2 text-center">Denda</th>
                <th class="p-2 text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($daftarPengembalian as $pengembalian) {
                echo "<tr class=\"border border-gray-800\">";
                $tanggalPakai = date('l, d-m-Y', strtotime($pengembalian['tanggal_pakai']));
                $tanggalKembali = date('l, d-m-Y', strtotime($pengembalian['tanggal_kembali']));
                echo "<td class=\"p-2 text-center border border-gray-800\">{$pengembalian['nama_peminjam']}</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$pengembalian['nama']}</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$pengembalian['jumlah']} unit</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$tanggalPakai}</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">{$tanggalKembali}</td>";
                echo "<td class=\"p-2 text-center border border-gray-800\">Rp.{$pengembalian['denda']}</td>";
                
                // Tambahkan kondisi untuk menampilkan status
                $statusKeterangan = $pengembalian['pengembalian_id'] ? 'Sudah Kembali' : 'Belum Kembali';
                $statusWarna = $pengembalian['pengembalian_id'] ? 'text-green-600' : 'text-red-600';
                echo "<td class=\"p-2 text-center border border-gray-800 {$statusWarna}\">{$statusKeterangan}</td>";

                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
</div>
</div>

<script>
document.getElementById('peminjaman').addEventListener('change', function() {
    var idPeminjaman = this.value;
    fetch('../Controller/get_barang_by_peminjaman.php?id=' + idPeminjaman)
        .then(response => response.json())
        .then(data => {
            var daftarBarang = document.getElementById('daftarBarang');
            daftarBarang.innerHTML = '';

            data.forEach(barang => {
                var row = document.createElement('tr');
                row.innerHTML = `
                    <td class="p-2 text-center border border-gray-800">${barang.nama_barang}</td>
                    <td class="p-2 text-center border border-gray-800">${barang.jumlah}</td>
                    <td class="p-2 text-center border border-gray-800">
                        <input type="checkbox" name="barang[]" value="${barang.id_barang}">
                    </td>
                `;
                daftarBarang.appendChild(row);
            });
        });
});

// JavaScript untuk menampilkan/menyembunyikan formulir tambah barang
const toggleFormButton = document.getElementById('toggleFormButton');
    const barangForm = document.getElementById('pengembalianForm');

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