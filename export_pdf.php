<?php
session_start();
require 'koneksi.php';

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

$data = mysqli_query($koneksi, "SELECT * FROM tamu ORDER BY id DESC");
$config = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1"));
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Tamu - PDF</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header p { margin: 2px 0; color: #555; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background-color: #eee; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        /* Sembunyikan tombol print saat dicetak */
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 5px 10px; cursor: pointer;">Cetak PDF</button>
        <button onclick="window.close()" style="padding: 5px 10px; cursor: pointer;">Tutup</button>
    </div>

    <div class="header">
        <h1>LAPORAN DATA TAMU</h1>
        <p><?= $config['app_name'] ?? 'Aplikasi Buku Tamu Digital' ?></p>
        <p>Tanggal Cetak: <?= date('d F Y') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 20%;">Nama Tamu</th>
                <th style="width: 15%;">Kategori</th>
                <th style="width: 15%;">No. WA</th>
                <th style="width: 20%;">Alamat</th>
                <th style="width: 5%;">Jml</th>
                <th style="width: 10%;">Tanggal</th>
                <th style="width: 10%;">Jam</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($row = mysqli_fetch_assoc($data)): 
            ?>
            <tr>
                <td style="text-align: center;"><?= $no++ ?></td>
                <td><?= $row['nama_tamu'] ?></td>
                <td><?= $row['kategori'] ?></td>
                <td><?= $row['no_hp'] ?></td>
                <td><?= $row['alamat'] ?></td>
                <td style="text-align: center;"><?= $row['jumlah_orang'] ?></td>
                <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                <td><?= substr($row['waktu'], 0, 5) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right;">
        <p>Mengetahui,</p>
        <br><br><br>
        <p><strong>Administrator</strong></p>
    </div>

</body>
</html>