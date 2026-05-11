<?php
// 1. Koneksi & Session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'koneksi.php';

// 2. Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

// 3. Tangkap Event ID
$selected_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
if ($selected_event_id == 0) {
    echo "Pilih acara terlebih dahulu.";
    exit;
}

// 4. Ambil Detail Event
$q_event = mysqli_query($koneksi, "SELECT * FROM events WHERE id = '$selected_event_id'");
$event = mysqli_fetch_assoc($q_event);
if (!$event) {
    echo "Acara tidak ditemukan.";
    exit;
}

// 5. Ambil Config Global
$q_conf = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$config = mysqli_fetch_assoc($q_conf);

// 6. Statistik
$total_tamu = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tamu WHERE event_id = '$selected_event_id'"))['total'];
$total_pax = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(jumlah_orang) as total FROM tamu WHERE event_id = '$selected_event_id'"))['total'] ?? 0;
$pax_hadir = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(jumlah_orang) as total FROM tamu WHERE event_id = '$selected_event_id' AND checkin_at IS NOT NULL"))['total'] ?? 0;
$pax_absent = $total_pax - $pax_hadir;

// 7. Ambil Data Tamu
$query_list = mysqli_query($koneksi, "SELECT * FROM tamu WHERE event_id = '$selected_event_id' ORDER BY checkin_at DESC, nama_tamu ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan_<?= str_replace(' ', '_', $event['event_name']) ?>_<?= date('d-m-Y') ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Ukuran F4: 210mm x 330mm */
        @page {
            size: 210mm 330mm;
            margin: 15mm 10mm 15mm 10mm;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #1a0f0d;
            background: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            font-size: 11pt;
        }

        .container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
        }

        /* HEADER */
        .header {
            text-align: center;
            border-bottom: 3px double #87714c;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 22pt;
            margin: 0;
            color: #87714c;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .header p {
            margin: 0;
            font-size: 10pt;
            color: #666;
            font-weight: 500;
        }

        /* EVENT INFO */
        .event-info {
            display: flex;
            align-items: stretch;
            margin-bottom: 25px;
            background: #fffcf9;
            border-radius: 12px;
            border: 1px solid #e8e1d5;
            overflow: hidden;
        }

        .info-col {
            padding: 20px;
            flex: 1;
        }

        .stats-col {
            padding: 20px;
            flex: 1.2;
            border-left: 1px solid #e8e1d5;
            background: #faf7f0;
        }

        .section-title {
            margin: 0 0 15px 0;
            font-size: 13pt;
            color: #87714c;
            font-family: 'Playfair Display', serif;
            border-bottom: 1px solid #e8e1d5;
            padding-bottom: 5px;
            display: inline-block;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 4px 0;
            font-size: 10pt;
            vertical-align: top;
        }

        .info-table td:first-child {
            width: 90px;
            font-weight: 700;
            color: #87714c;
            text-transform: uppercase;
            font-size: 8pt;
        }

        /* STATS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .stat-box {
            background: #fff;
            border: 1px solid #e8e1d5;
            border-radius: 10px;
            height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 4px rgba(135, 113, 76, 0.05);
        }

        .stat-box p {
            margin: 0;
            font-size: 7pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #87714c;
            letter-spacing: 0.5px;
            text-align: center;
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-box h4 {
            margin: 0;
            font-size: 18pt;
            font-family: 'Playfair Display', serif;
            color: #1a0f0d;
            line-height: 1;
        }

        /* TABLE */
        table.main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.main-table th {
            background: #87714c;
            color: white;
            padding: 10px 8px;
            font-size: 9pt;
            text-transform: uppercase;
            border: 1px solid #87714c;
        }

        table.main-table td {
            padding: 8px;
            border: 1px solid #e8e1d5;
            font-size: 9pt;
            vertical-align: middle;
        }

        table.main-table tr:nth-child(even) {
            background: #faf7f0;
        }

        /* FOOTER / SIGNATURE */
        .signatures {
            margin-top: 40px;
            display: flex;
            justify-content: flex-end;
        }

        .sig-box {
            text-align: center;
            width: 300px;
        }

        .sig-box p {
            margin: 0;
            font-size: 10pt;
            white-space: nowrap;
        }

        .sig-space {
            height: 70px;
        }

        /* UTILS */
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        
        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-none { background: #f3f4f6; color: #4b5563; }

        .no-print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1a0f0d;
            color: white;
            padding: 12px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 100;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media print {
            .no-print-btn { display: none; }
            body { background: transparent; }
        }
    </style>
</head>
<body>

    <button onclick="window.print()" class="no-print-btn">
        <span>CETAK LAPORAN</span>
    </button>

    <div class="container">
        
        <!-- HEADER -->
        <div class="header">
            <p><?= $config['app_name'] ?? 'Buku Tamu Digital' ?></p>
            <h1>Laporan Kehadiran Tamu</h1>
            <p>Exported on: <?= date('d F Y, H:i') ?> WIB</p>
        </div>

        <!-- INFO & STATS -->
        <div class="event-info">
            <div class="info-col">
                <h3 class="section-title">Informasi Acara</h3>
                <table class="info-table">
                    <tr>
                        <td>Nama Acara</td>
                        <td>: <?= $event['event_name'] ?></td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td>: <?= date('d F Y', strtotime($event['event_date'])) ?></td>
                    </tr>
                    <tr>
                        <td>Lokasi</td>
                        <td>: <?= $event['event_location'] ?></td>
                    </tr>
                </table>
            </div>
            <div class="stats-col">
                <h3 class="section-title">Ringkasan Kehadiran</h3>
                <div class="stats-grid">
                    <div class="stat-box">
                        <p>Tamu</p>
                        <h4><?= $total_tamu ?></h4>
                    </div>
                    <div class="stat-box">
                        <p>Total<br>Pax</p>
                        <h4><?= $total_pax ?></h4>
                    </div>
                    <div class="stat-box">
                        <p>Hadir</p>
                        <h4><?= $pax_hadir ?></h4>
                    </div>
                    <div class="stat-box">
                        <p>Absen</p>
                        <h4><?= $pax_absent ?></h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN TABLE -->
        <table class="main-table">
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th>Nama Tamu / Keterangan</th>
                    <th style="width: 100px;">Kategori</th>
                    <th style="width: 60px;">Pax</th>
                    <th style="width: 120px;">Waktu Hadir</th>
                    <th style="width: 80px;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if(mysqli_num_rows($query_list) > 0):
                    while($row = mysqli_fetch_assoc($query_list)): 
                        $hadir = !empty($row['checkin_at']);
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td>
                        <div class="font-bold"><?= $row['nama_tamu'] ?></div>
                        <?php if(!empty($row['alamat'])): ?>
                            <div style="font-size: 8pt; color: #888;"><?= $row['alamat'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= !empty($row['kategori']) ? $row['kategori'] : '-' ?></td>
                    <td class="text-center font-bold">× <?= $row['jumlah_orang'] ?></td>
                    <td class="text-center">
                        <?= $hadir ? date('d/m, H:i', strtotime($row['checkin_at'])) : '-' ?>
                    </td>
                    <td class="text-center">
                        <?php if($hadir): ?>
                            <span class="badge badge-success">HADIR</span>
                        <?php else: ?>
                            <span class="badge badge-none">BELUM</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding: 30px; color: #999;">Belum ada data tamu untuk acara ini.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SIGNATURES -->
        <div class="signatures">
            <div class="sig-box">
                <p><?= date('d F Y') ?></p>
                <p style="margin-bottom: 60px;">Koordinator Meja Tamu,</p>
                <p class="font-bold" style="border-bottom: 1px solid #000; display: inline-block; min-width: 200px; padding-bottom: 5px;">( ........................................ )</p>
                <p style="font-size: 8pt; color: #888; margin-top: 5px;">Digital Signature System</p>
            </div>
        </div>

    </div>

    <script>
        window.onload = function() {
            // Optional: Auto open print dialog
            // window.print();
        }
    </script>
</body>
</html>
