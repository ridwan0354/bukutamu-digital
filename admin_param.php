<?php
require 'koneksi.php';
require_admin();
check_csrf();

// Tambah Parameter
if (isset($_POST['tambah'])) {
    $param = esc($_POST['param_key']);
    $param = str_replace(['?', '='], '', $param); // Bersihkan input
    if(!empty($param)) {
        mysqli_query($koneksi, "INSERT INTO master_broadcast_params (param_key) VALUES ('$param')");
    }
}

// Hapus Parameter
if (isset($_POST['hapus'])) {
    $id = (int)$_POST['id_param'];
    mysqli_query($koneksi, "DELETE FROM master_broadcast_params WHERE id=$id");
    header("Location: admin_param.php");
    exit;
}

$data = mysqli_query($koneksi, "SELECT * FROM master_broadcast_params");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Parameter URL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Plus Jakarta Sans',sans-serif;}</style>
</head>
<body class="bg-gray-50 p-10">
    <div class="max-w-md mx-auto bg-white p-6 rounded-xl shadow-md border border-gray-200">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-xl font-bold text-gray-800">Master Parameter URL</h1>
            <a href="dashboard.php" class="text-sm text-blue-600 hover:underline">Kembali ke Dashboard</a>
        </div>

        <form method="POST" class="flex gap-2 mb-6">
            <?= csrf_field() ?>
            <input type="text" name="param_key" placeholder="Contoh: yth" class="border p-2 rounded w-full text-sm focus:ring-2 focus:ring-blue-500 outline-none" required>
            <button type="submit" name="tambah" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-bold hover:bg-blue-700">Tambah</button>
        </form>

        <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">Parameter</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php while($row = mysqli_fetch_assoc($data)): ?>
                    <tr class="bg-white">
                        <td class="px-4 py-3 font-mono text-blue-600">?<?= $row['param_key'] ?>=</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="" onsubmit="return confirm('Hapus parameter ini?')" style="display:inline;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hapus" value="1">
                                <input type="hidden" name="id_param" value="<?= $row['id'] ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>