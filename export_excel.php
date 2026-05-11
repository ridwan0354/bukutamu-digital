<?php
session_start();
require 'koneksi.php';

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

// Nama File saat didownload
$filename = "Data_Tamu_" . date('Ymd') . ".xls";

// Header agar dibaca sebagai Excel
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");

// Ambil Data
$data = mysqli_query($koneksi, "SELECT * FROM tamu ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h3>Laporan Data Tamu</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Tamu</th>
                <th>Kategori</th>
                <th>Alamat</th>
                <th>No HP</th>
                <th>Jumlah</th>
                <th>Tanggal</th>
                <th>Jam</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            while($row = mysqli_fetch_assoc($data)): 
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['nama_tamu'] ?></td>
                <td><?= $row['kategori'] ?></td>
                <td><?= $row['alamat'] ?></td>
                <td>'<?= $row['no_hp'] ?></td>
                <td><?= $row['jumlah_orang'] ?></td>
                <td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td><?= $row['waktu'] ?></td>
                <td><?= $row['status_undangan'] ?? 'Pending' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>