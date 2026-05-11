<?php
require 'koneksi.php';
require_login();

// ==========================================
// 1. LOGIKA PILIH EVENT
// ==========================================
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Filter event sesuai role
if ($role == 'admin') {
    $q_events = mysqli_query($koneksi, "SELECT * FROM events ORDER BY event_date DESC");
} else {
    $q_events = mysqli_query($koneksi, "SELECT * FROM events WHERE user_id='$uid' ORDER BY event_date DESC");
}

// Tentukan Event Aktif
$selected_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($selected_event_id == 0) {
    if ($role == 'admin') {
        $latest = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM events ORDER BY id DESC LIMIT 1"));
    } else {
        $latest = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM events WHERE user_id='$uid' ORDER BY id DESC LIMIT 1"));
    }
    $selected_event_id = $latest['id'] ?? 0;
}

// Ambil Detail Event dengan Validasi Ownership (Proteksi IDOR)
$auth_check = ($role == 'admin') ? "" : " AND user_id = '$uid'";
$q_evt = mysqli_query($koneksi, "SELECT * FROM events WHERE id = '$selected_event_id' $auth_check");
$current_event = mysqli_fetch_assoc($q_evt);

if (!$current_event && $selected_event_id != 0) {
    // Jika event_id dimanipulasi, tendang balik
    echo "<script>alert('Akses Ditolak: Event tidak ditemukan atau bukan milik Anda.'); window.location='dashboard';</script>";
    exit;
}
if (!$current_event) {
    $current_event = [
        'event_name' => 'Belum ada acara',
        'event_date' => date('Y-m-d'),
        'event_location' => '-',
        'event_time_start' => '00:00'
    ];
}

$app_config = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT app_name FROM pengaturan LIMIT 1"));

// ==========================================
// 2. STATISTIK & DATA
// ==========================================
$q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tamu WHERE event_id = '$selected_event_id'");
$total_tamu = ($q_total) ? mysqli_fetch_assoc($q_total)['total'] : 0;

$q_pax_hadir = mysqli_query($koneksi, "SELECT SUM(jumlah_orang) as total FROM tamu WHERE event_id = '$selected_event_id' AND checkin_at IS NOT NULL");
$total_pax_hadir = ($q_pax_hadir) ? mysqli_fetch_assoc($q_pax_hadir)['total'] : 0;
$total_pax_hadir = $total_pax_hadir ?? 0;

$q_pax_belum = mysqli_query($koneksi, "SELECT SUM(jumlah_orang) as total FROM tamu WHERE event_id = '$selected_event_id' AND checkin_at IS NULL");
$total_pax_belum = ($q_pax_belum) ? mysqli_fetch_assoc($q_pax_belum)['total'] : 0;
$total_pax_belum = $total_pax_belum ?? 0;

// Data untuk Chart Kategori
$cat_labels = [];
$cat_data = [];
$q_cat = mysqli_query($koneksi, "SELECT kategori, COUNT(*) as total FROM tamu WHERE event_id='$selected_event_id' GROUP BY kategori");
while($c = mysqli_fetch_assoc($q_cat)){
    $cat_labels[] = $c['kategori'];
    $cat_data[] = $c['total'];
}

$query_list = mysqli_query($koneksi, "SELECT * FROM tamu WHERE event_id = '$selected_event_id' ORDER BY checkin_at DESC, nama_tamu ASC");
?>

<!--
    ============================================================
    APPLICATION : BUKU TAMU DIGITAL Eksklusif
    VERSION     : 2.1 Standard Edition
    LICENSE     : Licensed for Exclusive Use
    DEVELOPED BY: ACHMAD BUKHORI
    CONTACT     : WhatsApp (0823 2222 6900)
    ============================================================
    Copyright © 2026. All Rights Reserved.
-->
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - <?= $app_config['app_name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; }
        .font-serif { font-family: 'Playfair Display', serif; }
        
        /* UPDATED SELECT2 STYLE (SAMAKAN DENGAN BROADCAST.PHP) */
        .select2-container .select2-selection--single { 
            height: 42px !important; 
            border: 1px solid #e8e1d5 !important;
            background-color: #ffffff !important;
            border-radius: 0.75rem !important;
            padding-top: 6px;
            padding-left: 10px;
            color: #000000 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #000000 !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            top: 8px !important;
            right: 10px !important;
        }
        .select2-dropdown {
            border: 1px solid #e8e1d5 !important;
            border-radius: 0.75rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            overflow: hidden !important;
        }
        .select2-results__option--highlighted {
            background-color: #fffbf2 !important;
            color: #EAB676 !important;
        }

        /* --- RESET CSS DATATABLES --- */
        table.dataTable thead th {
            background-color: #87714c !important;
            color: white !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 0.75rem !important;
            padding: 1rem !important;
            border-bottom: none !important;
            letter-spacing: 0.05em;
        }
        table.dataTable thead th:first-child { border-top-left-radius: 0.75rem; }
        table.dataTable thead th:last-child { border-top-right-radius: 0.75rem; }
        table.dataTable tbody tr { background-color: #ffffff !important; transition: all 0.2s; }
        table.dataTable tbody tr:hover { background-color: #fffbf2 !important; }
        table.dataTable tbody td {
            padding: 1rem !important;
            border-bottom: 1px solid #f3e9d8 !important;
            color: #000000 !important;
            font-size: 0.875rem !important;
            vertical-align: middle;
        }
        table.dataTable.no-footer { border-bottom: none !important; }
        .dataTables_length select, .dataTables_filter input {
            border: 1px solid #e8e1d5 !important; border-radius: 0.75rem !important; padding: 0.4rem 0.8rem !important; outline: none; color: #000000; background: #fff;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #87714c !important; color: white !important; border: none; border-radius: 0.75rem;
        }

        /* Print */
        @media print {
            nav, aside, .no-print { display: none !important; }
            main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .print-break { page-break-inside: avoid; }
        }
        
        /* Scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #87714c; border-radius: 4px; }
    </style>
</head>
<body class="text-[#1a0f0d]" style="background-color:#ffffff; background-image:url('https://www.transparenttextures.com/patterns/cream-paper.png');">

    <div class="no-print"><?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?></div>

    <main class="md:ml-64 p-4 lg:p-10 relative">
        <div class="max-w-7xl mx-auto">
            
            <!-- Header Section -->
            <div class="mb-5 lg:mb-6 border-b border-[#d1c7b7] pb-3 no-print">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                            <iconify-icon icon="solar:document-bold-duotone" class="text-2xl"></iconify-icon>
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-[#1a0f0d] font-serif">Laporan Tamu</h1>
                            <p class="text-[#87714c] mt-1 text-sm">Ringkasan kehadiran dan statistik acara.</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <div class="flex-1 md:w-64">
                            <label class="block text-[10px] font-bold text-[#87714c] mb-1.5 uppercase tracking-widest pl-1">Filter Acara</label>
                            <form method="GET" action="">
                                <select name="event_id" id="eventSelector" class="w-full select2-event" onchange="this.form.submit()">
                                    <option value="">-- Pilih Acara --</option>
                                    <?php if($q_events) { mysqli_data_seek($q_events, 0); while($evt = mysqli_fetch_assoc($q_events)): ?>
                                        <option value="<?= $evt['id'] ?>" <?= $evt['id'] == $selected_event_id ? 'selected' : '' ?>>
                                            <?= $evt['event_name'] ?>
                                        </option>
                                    <?php endwhile; } ?>
                                </select>
                            </form>
                        </div>
                        <div class="flex items-end self-end">
                            <a href="print_report.php?event_id=<?= $selected_event_id ?>" target="_blank" class="h-[42px] px-5 bg-[#87714c] hover:bg-[#3e2723] text-white rounded-xl shadow-md transition flex items-center justify-center gap-2 text-sm font-bold" title="Cetak Laporan">
                                <i class="fas fa-print"></i>
                                <span class="hidden sm:inline">Cetak</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-[#87714c] to-[#5d4d34] rounded-xl p-4 lg:p-5 mb-6 shadow-lg shadow-[#87714c]/20 text-white relative overflow-hidden flex flex-col md:flex-row justify-between items-start md:items-center gap-5">
                <div class="flex flex-col gap-1 z-10">
                    <p class="text-white/80 text-xs uppercase tracking-widest font-bold">Laporan Buku Tamu</p>
                    <h2 class="text-2xl lg:text-3xl font-bold font-serif leading-tight"><?= $current_event['event_name'] ?></h2>
                    <div class="flex gap-4 text-sm mt-2 text-white/90">
                        <span class="flex items-center gap-2"><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($current_event['event_date'])) ?></span>
                        <span class="flex items-center gap-2"><i class="fas fa-map-marker-alt"></i> <?= $current_event['event_location'] ?></span>
                    </div>
                </div>
                <div class="flex gap-3 z-10 w-full md:w-auto">
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-3 rounded-xl flex-1 text-center min-w-[90px]">
                        <h4 class="text-xl font-bold font-serif"><?= $total_tamu ?></h4><p class="text-[9px] uppercase opacity-80">Data</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-3 rounded-xl flex-1 text-center min-w-[90px]">
                        <h4 class="text-xl font-bold font-serif"><?= $total_pax_hadir ?></h4><p class="text-[9px] uppercase opacity-80">Hadir</p>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md border border-white/20 p-3 rounded-xl flex-1 text-center min-w-[90px]">
                        <h4 class="text-xl font-bold font-serif"><?= $total_pax_belum ?></h4><p class="text-[9px] uppercase opacity-80">Belum</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 no-print">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-[#e8e1d5] flex flex-col items-center">
                    <h3 class="font-bold text-[#1a0f0d] mb-2 font-serif text-sm w-full text-left">Status Kehadiran (Pax)</h3>
                    <div class="w-full h-64 relative">
                        <canvas id="chartKehadiran"></canvas>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-[#e8e1d5] flex flex-col items-center">
                    <h3 class="font-bold text-[#1a0f0d] mb-2 font-serif text-sm w-full text-left">Sebaran Kategori</h3>
                    <div class="w-full h-64 relative">
                        <canvas id="chartKategori"></canvas>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-[#e8e1d5] p-4">
                <div class="overflow-x-auto custom-scrollbar">
                    <table id="reportTable" class="w-full text-left border-collapse" style="width:100% !important;">
                        <thead>
                            <tr>
                                <th class="text-center w-12">No</th>
                                <th>Nama Tamu</th>
                                <th>Waktu Hadir</th>
                                <th class="text-center">Kategori</th>
                                <th class="text-center w-16">Pax</th>
                                <th class="text-center w-32">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($query_list && mysqli_num_rows($query_list) > 0) {
                                $no = 1;
                                while($row = mysqli_fetch_assoc($query_list)): 
                                    $hadir = !empty($row['checkin_at']);
                                    $jam_hadir = $hadir ? date('H:i', strtotime($row['checkin_at'])) : '-';
                                    $tgl_hadir = $hadir ? date('d/m', strtotime($row['checkin_at'])) : '-';
                            ?>
                            <tr>
                                <td class="text-center text-[#8d6e63]"><?= $no++ ?></td>
                                <td>
                                    <div class="font-bold text-[#1a0f0d] text-sm md:text-base"><?= $row['nama_tamu'] ?></div>
                                    <?php if(!empty($row['alamat'])): ?>
                                    <div class="text-[10px] text-[#EAB676] font-normal no-print"><?= $row['alamat'] ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-[#8d6e63]">
                                    <?php if($hadir): ?>
                                        <div class="font-bold text-sm"><?= $jam_hadir ?></div>
                                        <div class="text-[10px] text-gray-400"><?= $tgl_hadir ?></div>
                                    <?php else: ?>
                                        <span class="text-gray-300">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[10px] bg-[#ffffff] border border-[#e8e1d5] font-bold text-[#87714c] inline-flex items-center gap-1 uppercase transition-all shadow-sm">
                                        <?php if(strtoupper($row['kategori']) === 'VIP'): ?>
                                            <iconify-icon icon="solar:crown-minimalistic-bold-duotone" class="text-[#87714c] text-xs"></iconify-icon>
                                        <?php endif; ?>
                                        <?= $row['kategori'] ?>
                                    </span>
                                </td>
                                <td class="text-center font-bold text-lg text-[#87714c] font-serif"><?= $row['jumlah_orang'] ?></td>
                                <td class="text-center">
                                    <?php if($hadir): ?>
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-bold inline-flex items-center justify-center gap-1 uppercase w-full">
                                            <i class="fas fa-check-circle"></i> Hadir
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-400 px-3 py-1 rounded-full text-[10px] font-bold uppercase w-full block">
                                            Belum
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Setup DataTable
            $('#reportTable').DataTable({
                responsive: false,
                scrollX: false,
                autoWidth: false,
                pageLength: 50,
                stripeClasses: [],
                language: {
                    search: "", searchPlaceholder: "Cari Data...",
                    lengthMenu: "Tampil _MENU_", info: "Hal _PAGE_ dari _PAGES_",
                    paginate: { first: "«", last: "»", next: "›", previous: "‹" },
                    emptyTable: "Belum ada data tamu."
                },
                dom: '<"flex flex-col md:flex-row justify-between items-center gap-4 mb-4 no-print"lf>rt<"flex flex-col md:flex-row justify-between items-center gap-4 mt-4 no-print"ip>',
            });

            $('#eventSelector').select2({ placeholder: "Pilih Event...", width: '100%' });

            // --- CHARTS ---
            const ctxHadir = document.getElementById('chartKehadiran').getContext('2d');
            new Chart(ctxHadir, {
                type: 'doughnut',
                data: {
                    labels: ['Hadir', 'Belum Hadir'],
                    datasets: [{
                        data: [<?= $total_pax_hadir ?>, <?= $total_pax_belum ?>],
                        backgroundColor: ['#87714c', '#e8e1d5'],
                        borderWidth: 0
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });

            const ctxKat = document.getElementById('chartKategori').getContext('2d');
            new Chart(ctxKat, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($cat_labels) ?>,
                    datasets: [{
                        label: 'Jumlah Tamu',
                        data: <?= json_encode($cat_data) ?>,
                        backgroundColor: '#87714c',
                        borderRadius: 8
                    }]
                },
                options: { 
                    responsive: true, maintainAspectRatio: false, 
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
                }
            });
        });
    </script>
        <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
        </footer>
    </main>
</body></html>