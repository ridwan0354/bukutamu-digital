<?php
// 1. Cek Session Aman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'koneksi.php';
require_login();
check_csrf();

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];
$parent_id = $_SESSION['parent_id'] ?? 0;
// Receptionist acts on behalf of parent
$effective_uid = ($role == 'receptionist' && $parent_id > 0) ? $parent_id : $uid;

// 3. Config Global
$q_global = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$config_global = mysqli_fetch_assoc($q_global);

// ==========================================
// 4. LOGIKA FILTER EVENT
// ==========================================
if ($role == 'admin') {
    $q_events_list = mysqli_query($koneksi, "SELECT * FROM events ORDER BY id DESC");
} else {
    $q_events_list = mysqli_query($koneksi, "SELECT * FROM events WHERE user_id = '$effective_uid' ORDER BY id DESC");
}

$selected_event_id = isset($_GET['event_id']) ? mysqli_real_escape_string($koneksi, $_GET['event_id']) : '';

if (empty($selected_event_id) && mysqli_num_rows($q_events_list) > 0) {
    mysqli_data_seek($q_events_list, 0); 
    $first_event = mysqli_fetch_assoc($q_events_list);
    $selected_event_id = $first_event['id'];
    mysqli_data_seek($q_events_list, 0); 
}

// Security Check
if ($role != 'admin' && !empty($selected_event_id)) {
    $cek_milik = mysqli_query($koneksi, "SELECT id FROM events WHERE id='$selected_event_id' AND user_id='$effective_uid'");
    if(mysqli_num_rows($cek_milik) == 0) {
        mysqli_data_seek($q_events_list, 0);
        $first = mysqli_fetch_assoc($q_events_list);
        $selected_event_id = $first['id'];
        mysqli_data_seek($q_events_list, 0);
    }
}

$where_clause = " WHERE event_id = '$selected_event_id' ";

// Fetch Current Event Data for Global Use
$current_event = null;
if (!empty($selected_event_id)) {
    $q_evt = mysqli_query($koneksi, "SELECT * FROM events WHERE id='$selected_event_id'");
    $current_event = mysqli_fetch_assoc($q_evt);
}

$event_title    = $current_event['event_name'] ?? ($config_global['app_name'] ?? 'Acara Kami');
$template_pesan = !empty($current_event['wa_template']) ? $current_event['wa_template'] : ($config_global['wa_template'] ?? "Halo [nama-tamu], ini link undangan: [link-undangan]");
$base_link      = !empty($current_event['broadcast_link']) ? $current_event['broadcast_link'] : ($config_global['broadcast_link'] ?? "");
$param_id       = !empty($current_event['broadcast_param_id']) ? $current_event['broadcast_param_id'] : ($config_global['broadcast_param_id'] ?? 1);

$q_p_key = mysqli_query($koneksi, "SELECT param_key FROM master_broadcast_params WHERE id='$param_id'");
$d_p_key = mysqli_fetch_assoc($q_p_key);
$p_key   = $d_p_key['param_key'] ?? 'to';

// ==========================================
// 5. CRUD LOGIC
// ==========================================
$swal_script = "";

// A. HAPUS TAMU
if (isset($_POST['hapus_id'])) {
    $id_hapus = (int) $_POST['hapus_id'];
    // SECURITY: Ensure guest belongs to selected event
    mysqli_query($koneksi, "DELETE FROM tamu WHERE id=$id_hapus AND event_id='$selected_event_id'");
    header("Location: tamu.php?event_id=$selected_event_id"); exit;
}

// B. UPDATE INFO TEXT
if (isset($_POST['save_info_text']) && $role == 'admin') {
    $new_text = mysqli_real_escape_string($koneksi, $_POST['import_info_text']);
    $check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM pengaturan LIKE 'import_info_text'");
    if(mysqli_num_rows($check_col) == 0) {
        mysqli_query($koneksi, "ALTER TABLE pengaturan ADD COLUMN import_info_text TEXT NULL");
    }
    mysqli_query($koneksi, "UPDATE pengaturan SET import_info_text='$new_text' WHERE id=1");
    header("Location: tamu?event_id=$selected_event_id"); exit;
}

// C. IMPORT CSV
if (isset($_POST['import_csv']) && isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
    $fileName = $_FILES['file_csv']['tmp_name'];
    if (($handle = fopen($fileName, "r")) !== FALSE) {
        fgetcsv($handle, 1000, ","); 
        $sukses = 0;
        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $nama   = mysqli_real_escape_string($koneksi, trim($row[0] ?? ''));
            $hp     = mysqli_real_escape_string($koneksi, trim($row[1] ?? ''));
            $kat    = mysqli_real_escape_string($koneksi, trim($row[2] ?? 'Umum'));
            $alamat = mysqli_real_escape_string($koneksi, trim($row[3] ?? ''));
            $jml    = 1;

            if(!empty($nama)){
                $cek = mysqli_query($koneksi, "SELECT id FROM tamu WHERE nama_tamu='$nama' AND event_id='$selected_event_id'");
                if(mysqli_num_rows($cek) == 0) {
                    mysqli_query($koneksi, "INSERT INTO tamu (event_id, nama_tamu, no_hp, kategori, jumlah_orang, alamat) VALUES ('$selected_event_id', '$nama', '$hp', '$kat', '$jml', '$alamat')");
                    $sukses++;
                }
            }
        }
        fclose($handle);
        $swal_script = "Swal.fire({title: 'Import Selesai!', html: 'Sukses: <b>$sukses</b> Data', icon: 'success', confirmButtonColor: '#87714c'});";
    }
}

// D. SIMPAN MANUAL
if (isset($_POST['simpan_tamu'])) {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_tamu']));
    $cek = mysqli_query($koneksi, "SELECT id FROM tamu WHERE nama_tamu='$nama' AND event_id='$selected_event_id'");
    
    if(!$cek) {
        $error_msg = mysqli_error($koneksi);
        $swal_script = "Swal.fire('Error Query!', 'Gagal cek data: $error_msg', 'error');";
    } else if(mysqli_num_rows($cek) > 0) {
        $swal_script = "Swal.fire('Gagal!', 'Nama tamu sudah ada.', 'error');";
    } else {
        $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
        $no_hp = preg_replace('/[^0-9]/', '', $_POST['no_hp']);
        $jumlah = (int) $_POST['jumlah_orang'];
        
        $q_insert = "INSERT INTO tamu (event_id, nama_tamu, alamat, no_hp, kategori, jumlah_orang) VALUES ('$selected_event_id', '$nama', '$alamat', '$no_hp', '$kategori', '$jumlah')";
        if(mysqli_query($koneksi, $q_insert)) {
            $swal_script = "Swal.fire('Berhasil!', 'Data disimpan.', 'success').then(() => { window.location='tamu.php?event_id=$selected_event_id'; });";
        } else {
            $error_msg = mysqli_error($koneksi);
            $swal_script = "Swal.fire('Gagal Simpan!', 'Error: $error_msg', 'error');";
        }
    }
}

// E. UPDATE TAMU
if (isset($_POST['update_tamu'])) {
    $id = (int) $_POST['id_tamu'];
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_tamu']);
    $hp = preg_replace('/[^0-9]/', '', $_POST['no_hp']);
    $kat = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    $jml = (int) $_POST['jumlah_orang'];
    $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat']);
    // SECURITY: Validate ownership via event_id
    mysqli_query($koneksi, "UPDATE tamu SET nama_tamu='$nama', no_hp='$hp', kategori='$kat', jumlah_orang='$jml', alamat='$alamat' WHERE id=$id AND event_id='$selected_event_id'");
    header("Location: tamu.php?event_id=$selected_event_id"); exit;
}

// F. SYNC TO BROADCAST (Anti Duplikat)
if (isset($_POST['sync_broadcast'])) {
    $evt_id = (int)$selected_event_id;
    $count = 0;
    
    // Get event detail
    $q_conf = mysqli_query($koneksi, "SELECT * FROM events WHERE id='$evt_id'");
    $d_conf = mysqli_fetch_assoc($q_conf);
    
    $base_link = !empty($d_conf['broadcast_link']) ? $d_conf['broadcast_link'] : $config_global['broadcast_link'];
    $tmpl      = !empty($d_conf['wa_template']) ? $d_conf['wa_template'] : $config_global['wa_template'];
    $pid       = !empty($d_conf['broadcast_param_id']) ? $d_conf['broadcast_param_id'] : $config_global['broadcast_param_id'];
    
    $q_p = mysqli_query($koneksi, "SELECT param_key FROM master_broadcast_params WHERE id='$pid'");
    $d_p = mysqli_fetch_assoc($q_p);
    $p_key = $d_p['param_key'] ?? 'to';
    
    $q_t = mysqli_query($koneksi, "SELECT * FROM tamu WHERE event_id='$evt_id'");
    while($t = mysqli_fetch_assoc($q_t)) {
        $nama = mysqli_real_escape_string($koneksi, $t['nama_tamu']);
        $wa = $t['no_hp'];
        
        $wa_clean = preg_replace('/[^0-9]/', '', $wa);
        if(substr($wa_clean, 0, 1) == '0') $wa_clean = '62' . substr($wa_clean, 1);
        if(!empty($wa_clean)) {
            $cek = mysqli_query($koneksi, "SELECT id FROM broadcast_queue WHERE event_id='$evt_id' AND nama_tamu='$nama' AND nomor_wa='$wa_clean'");
            if(mysqli_num_rows($cek) == 0) {
                $sep = (strpos($base_link, '?') !== false) ? '&' : '?';
                $link = $base_link . $sep . $p_key . '=' . urlencode($t['nama_tamu']);
                $msg = str_replace(['[nama-tamu]', '[link-undangan]', '[event]', '[tgl]'], [$t['nama_tamu'], $link, $d_conf['event_name'], date('d-m-Y')], $tmpl);
                
                mysqli_query($koneksi, "INSERT INTO broadcast_queue (event_id, nama_tamu, nomor_wa, pesan, status, link_undangan) VALUES ('$evt_id', '$nama', '$wa_clean', '$msg', 'pending', '$link')");
                $count++;
            }
        }
    }
    $swal_script = "Swal.fire({title: 'Sync Selesai!', html: 'Berhasil menambahkan <b>$count</b> tamu baru ke antrian broadcast.', icon: 'success', confirmButtonColor: '#87714c'});";
}

$kategori_list = mysqli_query($koneksi, "SELECT * FROM kategori_tamu");
$info_import_default = "<li>Import data tamu sekaligus menggunakan file Excel/CSV</li><li>Maksimal jumlah tamu 500 untuk 1x proses import.</li><li>Format file excel harus sesuai dan berformat (.csv).</li><li>Semakin banyak data, maka proses import akan semakin lama.</li>";
$info_import = !empty($config_global['import_info_text']) ? $config_global['import_info_text'] : $info_import_default;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Tamu - <?= $config_global['app_name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .text-gold { color: #f4c78c; }
        .text-brown { color: #1a0f0d; }
        
        /* STYLE DROPDOWN SELECT2 BARU (Mirip Export) */
        .select2-container .select2-selection--single { 
            height: 42px !important; 
            border: 1px solid #e8e1d5 !important; /* Border halus */
            background-color: #ffffff !important; /* Warna dasar cream */
            border-radius: 0.75rem !important; /* Rounded XL */
            padding-top: 6px;
            padding-left: 10px;
            color: #000000 !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #000000 !important;
            font-weight: 600 !important;
            font-size: 0.875rem !important; /* text-sm */
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
            color: #87714c !important;
        }

        @media (max-width: 768px) { body { padding-bottom: 80px; } }
        
        .dropdown-menu { transform-origin: top left; transition: all 0.2s ease-in-out; z-index: 999 !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #87714c; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background-color: #f3e9d8; }
    </style>
</head>
<body class="text-[#1a0f0d]" style="background-color:#ffffff; background-image:url('https://www.transparenttextures.com/patterns/cream-paper.png');">

    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <main class="md:ml-64 p-4 lg:p-6 relative">
        
        <!-- Header Section -->
        <div class="mb-5 lg:mb-6 border-b border-[#d1c7b7] pb-3 no-print flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                    <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-2xl"></iconify-icon>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-[#1a0f0d] font-serif">Daftar Tamu</h1>
                    <p class="text-[#87714c] mt-1 text-sm">Kelola data tamu dan sebar undangan.</p>
                </div>
            </div>
            
            <div class="w-full md:w-72">
                <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase tracking-widest pl-1">Filter Event:</label>
                <form action="" method="GET">
                    <select name="event_id" class="w-full select2-event" onchange="this.form.submit()">
                        <?php if($q_events_list) { mysqli_data_seek($q_events_list, 0); while($evt = mysqli_fetch_assoc($q_events_list)): ?>
                            <option value="<?= $evt['id'] ?>" <?= ($selected_event_id == $evt['id']) ? 'selected' : '' ?>>
                                <?= $evt['event_name'] ?>
                            </option>
                        <?php endwhile; } ?>
                    </select>
                </form>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-[#e8e1d5] mb-4 flex flex-col xl:flex-row justify-between items-center gap-4">
            <div class="flex flex-col gap-3 w-full xl:flex-row xl:w-auto">
                <div class="grid grid-cols-2 gap-3 w-full xl:w-auto xl:flex">
                    <button onclick="toggleModal('modalTambahTamu')" class="bg-[#87714c] hover:bg-[#b08d55] text-white px-6 py-3 rounded-xl text-sm font-bold transition flex justify-center items-center gap-2 shadow-lg shadow-[#87714c]/20 xl:w-auto">
                        <i class="fas fa-plus"></i> <span>Tambah</span>
                    </button>
                    <button onclick="toggleModal('modalImport')" class="bg-white border border-[#e8e1d5] hover:border-[#87714c] text-[#1a0f0d] px-6 py-3 rounded-xl text-sm font-bold transition flex justify-center items-center gap-2 xl:w-auto shadow-sm">
                        <i class="fas fa-file-import text-[#a1887f]"></i> <span class="hidden sm:inline">Import</span>
                    </button>
                </div>
                <div class="relative group w-full xl:w-auto">
                    <button onclick="document.getElementById('exportMenu').classList.toggle('hidden')" class="w-full bg-[#ffffff] border border-[#e8e1d5] hover:bg-[#fffbf2] text-[#1a0f0d] px-6 py-3 rounded-xl text-sm font-bold transition flex justify-center items-center gap-2 xl:w-auto shadow-sm">
                        <i class="fas fa-download text-[#a1887f]"></i> <span class="hidden sm:inline">Export</span> <i class="fas fa-chevron-down text-xs ml-1"></i>
                    </button>
                    <div id="exportMenu" class="absolute left-0 mt-2 w-32 bg-white border border-[#e8e1d5] rounded-xl shadow-xl hidden z-50 animate__animated animate__fadeIn">
                        <a href="export_excel?event_id=<?= $selected_event_id ?>" class="block px-4 py-2 text-sm text-[#1a0f0d] hover:bg-[#fffbf2] rounded-t-lg">Excel</a>
                        <a href="export_pdf?event_id=<?= $selected_event_id ?>" class="block px-4 py-2 text-sm text-[#1a0f0d] hover:bg-[#fffbf2] rounded-b-lg">PDF</a>
                    </div>
                </div>
                <!-- SYNC BUTTON -->
                <button onclick="confirmSync()" class="w-full bg-[#1a0f0d] hover:bg-[#4a332d] text-white px-6 py-3 rounded-xl text-sm font-bold transition flex justify-center items-center gap-2 xl:w-auto shadow-lg shadow-black/10">
                    <iconify-icon icon="solar:cloud-upload-bold-duotone" class="text-lg"></iconify-icon> <span>Sync WA</span>
                </button>
                
                <form id="formSync" method="POST" action="" class="hidden">
                    <?= csrf_field() ?>
                    <input type="hidden" name="sync_broadcast" value="1">
                </form>
            </div>
            
            <div class="w-full xl:w-auto relative">
                <input type="text" id="searchTable" placeholder="Cari nama tamu..." class="pl-10 pr-4 py-3 border border-[#e8e1d5] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#87714c] w-full xl:w-72 bg-[#ffffff] text-[#1a0f0d] shadow-inner font-medium placeholder-gray-400">
                <i class="fas fa-search absolute left-3 top-3.5 text-[#a1887f]"></i>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-[#e8e1d5] overflow-hidden min-h-[400px]">
            <div class="overflow-x-auto custom-scrollbar pb-4">
                
                <table class="w-full text-left border-collapse min-w-[1000px]" id="mainTable">
                    <thead>
                        <tr class="bg-[#1a0f0d] text-white text-xs uppercase tracking-wider font-bold">
                            <th class="py-4 text-center w-[5%] rounded-tl-lg text-white">No</th>
                            <th class="py-4 text-center w-[15%] text-white">Kontak & Share</th>
                            <th class="py-4 px-4 text-left w-[25%] text-white">Nama Tamu</th>
                            <th class="py-4 px-4 text-left w-[15%] text-white">Waktu Input</th>
                            <th class="py-4 text-center w-[10%] text-white">Kategori</th>
                            <th class="py-4 text-center w-[5%] text-white">Pax</th>
                            <th class="py-4 text-center w-[15%] text-white">Status</th>
                            <th class="py-4 text-center w-[10%] rounded-tr-lg text-white">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3e9d8] text-sm text-[#1a0f0d]">
                        <?php 
                        $no=1; 
                        $q_tamu = mysqli_query($koneksi, "SELECT * FROM tamu $where_clause ORDER BY id DESC"); 
                        
                        if($q_tamu && mysqli_num_rows($q_tamu) > 0):
                            while($row=mysqli_fetch_assoc($q_tamu)): 
                                $hp = preg_replace('/[^0-9]/', '', $row['no_hp']);
                                if(substr($hp, 0, 1) == '0') $hp = '62' . substr($hp, 1);
                                if(substr($hp, 0, 2) != '62') $hp = '62' . $hp;

                                // Build Link (Using pre-fetched settings for performance)
                                $separator = (strpos($base_link, '?') !== false) ? '&' : '?';
                                $full_invite_link = $base_link . $separator . $p_key . '=' . urlencode($row['nama_tamu']);

                                $pesan_wa = str_replace(
                                    ['[nama-tamu]', '[link-undangan]', '[tgl]', '[event]'], 
                                    [$row['nama_tamu'], $full_invite_link, date('d-m-Y'), $event_title], 
                                    $template_pesan
                                );
                                // Clean up any literal and real newlines to ensure clean formatting
                                $pesan_wa = str_replace(["\\r\\n", "\\r", "\\n"], "\n", $pesan_wa);
                                $pesan_wa = str_replace(["\r\n", "\r"], "\n", $pesan_wa);
                                $link_wa = "https://wa.me/" . $hp . "?text=" . urlencode($pesan_wa);
                        ?>
                        <tr class="hover:bg-[#fffbf2] transition">
                            <td class="py-3 text-center text-gray-400"><?= $no++ ?></td>
                            
                            <td class="py-3 text-center">
                                <div class="flex items-center gap-2 justify-center">
                                    
                                    <?php if(!empty($row['no_hp'])): ?>
                                        <a href="<?= $link_wa ?>" target="_blank" class="w-8 h-8 bg-green-100 text-green-700 rounded-xl flex items-center justify-center hover:bg-green-200 transition relative z-20 shadow-sm border border-green-200" title="Kirim WA">
                                            <i class="fab fa-whatsapp text-lg"></i>
                                        </a>
                                    <?php else: ?>
                                        <div class="w-8 h-8 bg-gray-100 text-gray-300 rounded-xl flex items-center justify-center border border-gray-200 cursor-not-allowed relative z-20" title="No HP Kosong">
                                            <i class="fab fa-whatsapp text-lg"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="relative inline-block text-left z-10">
                                        <button onclick="toggleRowMenu('share-menu-<?= $row['id'] ?>')" class="w-8 h-8 rounded-xl bg-white border border-[#e8e1d5] text-[#8d6e63] flex items-center justify-center hover:bg-[#fffbf2] hover:text-[#87714c] transition shadow-sm">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        
                                        <div id="share-menu-<?= $row['id'] ?>" class="dropdown-menu hidden absolute left-full top-0 ml-2 w-48 bg-white rounded-xl shadow-xl border border-[#e8e1d5] z-50 overflow-hidden text-left">
                                            <div class="py-1">
                                                <a href="<?= $full_invite_link ?>" target="_blank" class="block px-4 py-2.5 text-xs text-[#000000] hover:bg-[#fffbf2] font-medium flex items-center gap-2">
                                                    <i class="fas fa-eye text-[#87714c] w-4 text-center"></i> Lihat Undangan
                                                </a>
                                                <button onclick="shareLink(<?= htmlspecialchars(json_encode($full_invite_link), ENT_QUOTES, 'UTF-8') ?>)" class="w-full text-left px-4 py-2.5 text-xs text-[#000000] hover:bg-[#fffbf2] font-medium flex items-center gap-2">
                                                    <i class="fas fa-share-alt text-[#87714c] w-4 text-center"></i> Share Undangan
                                                </button>
                                                <button onclick="copyText(<?= htmlspecialchars(json_encode($pesan_wa), ENT_QUOTES, 'UTF-8') ?>)" class="w-full text-left px-4 py-2.5 text-xs text-[#000000] hover:bg-[#fffbf2] font-medium flex items-center gap-2">
                                                    <i class="fas fa-copy text-[#87714c] w-4 text-center"></i> Copy Message
                                                </button>
                                                <button onclick="downloadQR(<?= htmlspecialchars(json_encode($full_invite_link), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($row['nama_tamu']), ENT_QUOTES, 'UTF-8') ?>)" class="w-full text-left px-4 py-2.5 text-xs text-[#000000] hover:bg-[#fffbf2] font-medium flex items-center gap-2">
                                                    <i class="fas fa-qrcode text-[#87714c] w-4 text-center"></i> Download QR
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4">
                                <div class="font-bold text-[#000000]"><?= $row['nama_tamu'] ?></div>
                                <div class="text-[10px] text-[#87714c]"><?= $row['alamat'] ?? '-' ?></div>
                            </td>

                            <td class="py-3 px-4 text-gray-500 text-xs"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
                            
                            <td class="py-3 text-center">
                                <span class="px-2 py-1 rounded-md text-[10px] bg-[#ffffff] border border-[#e8e1d5] font-bold text-[#87714c]"><?= $row['kategori'] ?></span>
                            </td>

                            <td class="py-3 text-center font-bold text-lg text-[#87714c] font-serif"><?= $row['jumlah_orang'] ?></td>

                            <td class="py-3 text-center whitespace-nowrap">
                                <?php if(!empty($row['checkin_at'])): ?>
                                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1 uppercase">
                                        <i class="fas fa-check-circle"></i> <?= date('H:i', strtotime($row['checkin_at'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-400 px-3 py-1 rounded-full text-[10px] font-bold uppercase">Belum</span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3 text-center">
                                <div class="flex justify-center gap-2">
                                    <button onclick="editTamu('<?= $row['id'] ?>','<?= $row['nama_tamu'] ?>','<?= $row['kategori'] ?>','<?= $row['no_hp'] ?>','<?= $row['jumlah_orang'] ?>','<?= $row['alamat'] ?>')" class="text-amber-500 hover:bg-amber-50 w-8 h-8 rounded flex items-center justify-center border border-amber-200 transition"><i class="fas fa-edit"></i></button>
                                    <button onclick="submitHapusTamu(<?= $row['id'] ?>)" class="text-red-500 hover:bg-red-50 w-8 h-8 rounded flex items-center justify-center border border-red-200 transition"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="p-8 text-center text-gray-400 italic">Belum ada data tamu. Silakan tambah data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
        </footer>
    </main>

    <div id="modalTambahTamu" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="toggleModal('modalTambahTamu')"></div>
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl overflow-hidden animate__animated animate__fadeInUp">
            <div class="bg-white px-4 py-4 border-b border-[#e8e1d5] flex justify-between items-center"><h3 class="text-lg font-bold text-[#000000] font-serif">Tambah Tamu</h3><button onclick="toggleModal('modalTambahTamu')" class="text-gray-400">X</button></div>
            <form action="" method="POST">
                <?= csrf_field() ?>
                <div class="p-5 space-y-4">
                    <div class="flex justify-end"><button type="button" onclick="pickContact()" class="text-xs bg-emerald-100 text-emerald-700 px-3 py-1.5 rounded-xl font-bold hover:bg-emerald-200 border border-emerald-200"><i class="fas fa-address-book"></i> Kontak HP</button></div>
                    <input type="hidden" name="event_id_target" value="<?= $selected_event_id ?>">
                    <div><label class="block text-xs font-bold text-[#87714c] mb-1">Nama Tamu</label><input type="text" name="nama_tamu" required class="w-full border rounded-xl px-3 py-2 text-sm border-[#e8e1d5]"></div>
                    <div><label class="block text-xs font-bold text-[#87714c] mb-1">Kategori</label><select name="kategori" class="w-full border rounded-xl px-3 py-2 text-sm bg-white border-[#e8e1d5]"><?php if($kategori_list) { mysqli_data_seek($kategori_list, 0); while($kat = mysqli_fetch_assoc($kategori_list)): ?><option value="<?= $kat['nama_kategori'] ?>"><?= $kat['nama_kategori'] ?></option><?php endwhile; } ?></select></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs font-bold text-[#87714c] mb-1">WhatsApp</label><input type="text" name="no_hp" class="w-full border rounded-xl px-3 py-2 text-sm border-[#e8e1d5]"></div>
                        <div><label class="block text-xs font-bold text-[#87714c] mb-1">Jumlah</label><input type="number" name="jumlah_orang" value="1" min="1" class="w-full border rounded-xl px-3 py-2 text-sm border-[#e8e1d5]"></div>
                    </div>
                    <div><label class="block text-xs font-bold text-[#87714c] mb-1">Alamat</label><textarea name="alamat" rows="2" class="w-full border rounded-xl px-3 py-2 text-sm border-[#e8e1d5]"></textarea></div>
                </div>
                <div class="bg-[#ffffff] px-5 py-3 flex flex-row-reverse gap-2"><button type="submit" name="simpan_tamu" class="bg-[#87714c] text-white px-4 py-2 rounded-xl text-sm font-bold hover:bg-[#b08d55]">Simpan</button><button type="button" onclick="toggleModal('modalTambahTamu')" class="bg-white border text-gray-700 px-4 py-2 rounded-xl text-sm">Batal</button></div>
            </form>
        </div>
    </div>

    <div id="modalImport" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4"> 
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="toggleModal('modalImport')"></div> 
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden animate__animated animate__zoomIn">
            <div class="bg-white px-6 py-4 border-b border-[#e8e1d5] flex justify-between items-center">
                <h3 class="text-xl font-bold text-[#000000] font-serif">Import Data Tamu</h3>
                <button onclick="toggleModal('modalImport')" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            </div>
            <div class="p-6">
                <div id="infoBoxContainer">
                    <div class="bg-pink-50 border border-pink-100 rounded-xl p-4 mb-6 text-sm text-[#000000] leading-relaxed relative group">
                        <?php if($role == 'admin'): ?>
                        <button onclick="toggleEditInfo()" class="absolute top-2 right-2 text-pink-400 hover:text-pink-600 p-1 rounded-md hover:bg-pink-100 transition" title="Edit Info">
                            <i class="fas fa-edit"></i>
                        </button>
                        <?php endif; ?>
                        <ul class="list-disc pl-5 space-y-1 marker:text-pink-400" id="infoTextDisplay">
                            <?= $info_import ?>
                        </ul>
                        <div class="mt-4">
                            <a href="data:text/csv;charset=utf-8,NAMA,WHATSAPP,KATEGORI,ALAMAT%0ABudi Santoso,62812345678,VIP,Jakarta%0ASiti Aminah,08129876543,Reguler,Surabaya" download="template_tamu.csv" class="inline-flex items-center gap-2 bg-[#a1887f] hover:bg-[#8d6e63] text-white text-xs font-bold px-4 py-2 rounded-xl transition shadow-sm">
                                <i class="fas fa-download"></i> Download Template CSV
                            </a>
                        </div>
                    </div>
                </div>
                <?php if($role == 'admin'): ?>
                <form action="" method="POST" id="formEditInfo" class="hidden mb-6 bg-pink-50 p-4 rounded-xl border border-pink-200">
                    <?= csrf_field() ?>
                    <label class="block text-xs font-bold text-pink-700 mb-2 uppercase">Edit Teks Informasi (HTML Support)</label>
                    <textarea name="import_info_text" rows="5" class="w-full border border-pink-200 rounded-xl px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-pink-300"><?= $info_import ?></textarea>
                    <div class="flex justify-end gap-2 mt-3">
                        <button type="button" onclick="toggleEditInfo()" class="text-xs text-gray-500 hover:text-gray-700 px-3 py-1.5">Batal</button>
                        <button type="submit" name="save_info_text" class="text-xs bg-pink-500 hover:bg-pink-600 text-white px-4 py-1.5 rounded-xl font-bold shadow-sm">Simpan</button>
                    </div>
                </form>
                <?php endif; ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-[#000000] mb-2">Pilih File (CSV)</label>
                        <div class="flex items-center gap-2">
                            <input type="file" name="file_csv" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-[#ffffff] file:text-[#87714c] hover:file:bg-[#fffbf2] border border-[#e8e1d5] rounded-xl cursor-pointer">
                        </div>
                    </div>
                    <button type="submit" name="import_csv" class="w-full bg-[#5D6D7E] hover:bg-[#4a5866] text-white font-bold py-3 rounded-xl shadow-lg transition">
                        Import Sekarang
                    </button>
                </form>
            </div>
        </div> 
    </div>
    
    <div id="modalEditTamu" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4"> <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="toggleModal('modalEditTamu')"></div> <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl overflow-hidden"> <div class="bg-white px-4 py-4 border-b flex justify-between items-center"><h3 class="text-lg font-bold text-[#000000] font-serif">Edit Tamu</h3><button onclick="toggleModal('modalEditTamu')">X</button></div> <form action="" method="POST"><?= csrf_field() ?><input type="hidden" name="id_tamu" id="edit_id"><div class="p-5 space-y-4"><div><label class="block text-xs font-bold mb-1 text-[#87714c]">Nama</label><input type="text" name="nama_tamu" id="edit_nama" required class="w-full border rounded px-3 py-2 text-sm border-[#e8e1d5]"></div><div><label class="block text-xs font-bold mb-1 text-[#87714c]">Kategori</label><select name="kategori" id="edit_kategori" class="w-full border rounded px-3 py-2 text-sm border-[#e8e1d5]"><?php if($kategori_list) { mysqli_data_seek($kategori_list, 0); while($kat = mysqli_fetch_assoc($kategori_list)): ?><option value="<?= $kat['nama_kategori'] ?>"><?= $kat['nama_kategori'] ?></option><?php endwhile; } ?></select></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-xs font-bold mb-1 text-[#87714c]">WA</label><input type="text" name="no_hp" id="edit_hp" class="w-full border rounded px-3 py-2 text-sm border-[#e8e1d5]"></div><div><label class="block text-xs font-bold mb-1 text-[#87714c]">Jml</label><input type="number" name="jumlah_orang" id="edit_jml" class="w-full border rounded px-3 py-2 text-sm border-[#e8e1d5]"></div></div><div><label class="block text-xs font-bold mb-1 text-[#87714c]">Alamat</label><textarea name="alamat" id="edit_alamat" rows="2" class="w-full border rounded px-3 py-2 text-sm border-[#e8e1d5]"></textarea></div></div><div class="bg-[#ffffff] px-5 py-3 flex justify-end gap-2"><button type="submit" name="update_tamu" class="bg-amber-500 text-white px-4 py-2 rounded font-bold">Update</button></div></form> </div> </div>
    
    <div id="qrcode-container" style="display:none;"></div>

    <script>
        <?= $swal_script ?>
    </script>

    <script>
        $(document).ready(function() {
            $('.select2-event').select2({ placeholder: "Pilih Event...", width: '100%' });
            $("#searchTable").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#mainTable tbody tr").filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) });
            });
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.relative.group').length) { $('#exportMenu').addClass('hidden'); }
                if (!$(e.target).closest('.relative.inline-block').length) { $('.dropdown-menu').addClass('hidden'); }
            });
        });

        function toggleRowMenu(id) {
            $('.dropdown-menu').not('#'+id).addClass('hidden'); 
            $('#'+id).toggleClass('hidden');
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                const Toast = Swal.mixin({
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 1500, timerProgressBar: true, background: '#fffcf9'
                });
                Toast.fire({ icon: 'success', title: 'Teks disalin!' });
            });
        }

        function shareLink(url) {
            if (navigator.share) {
                navigator.share({ title: 'Undangan Pernikahan', text: 'Link Undangan:', url: url }).catch((e) => console.log(e));
            } else { copyText(url); }
        }

        function downloadQR(link, nama) {
            const container = document.getElementById('qrcode-container'); container.innerHTML = ''; 
            new QRCode(container, { text: link, width: 500, height: 500, colorDark : "#000000", colorLight : "#ffffff", correctLevel : QRCode.CorrectLevel.H });
            setTimeout(() => {
                const img = container.querySelector('img');
                if (img.src) { const a = document.createElement("a"); a.href = img.src; a.download = "QR_" + nama + ".png"; document.body.appendChild(a); a.click(); document.body.removeChild(a); }
            }, 500); 
        }

        function toggleModal(id) { 
            const el = document.getElementById(id);
            if(el.classList.contains('hidden')) { el.classList.remove('hidden'); el.classList.add('flex'); } 
            else { el.classList.add('hidden'); el.classList.remove('flex'); }
        }

        function toggleEditInfo() {
            const infoBox = document.getElementById('infoBoxContainer');
            const formEdit = document.getElementById('formEditInfo');
            if(formEdit.classList.contains('hidden')) {
                formEdit.classList.remove('hidden');
                infoBox.classList.add('hidden');
            } else {
                formEdit.classList.add('hidden');
                infoBox.classList.remove('hidden');
            }
        }

        async function pickContact() {
            try { const contacts = await navigator.contacts.select(['name', 'tel'], { multiple: false }); if (contacts.length) { document.querySelector('#modalTambahTamu input[name="nama_tamu"]').value = contacts[0].name[0]; if(contacts[0].tel.length) document.querySelector('#modalTambahTamu input[name="no_hp"]').value = contacts[0].tel[0].replace(/[^0-9]/g, ''); } } catch (ex) { alert("Fitur kontak tidak didukung."); }
        }

        function editTamu(id, nama, kat, hp, jml, alamat) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_kategori').value = kat;
            document.getElementById('edit_hp').value = hp;
            document.getElementById('edit_jml').value = jml;
            document.getElementById('edit_alamat').value = alamat;
            toggleModal('modalEditTamu');
        }

        function confirmAction(e, url, title, text) {
            e.preventDefault();
            Swal.fire({
                title: title, text: text, icon: 'warning', iconColor: '#87714c', showCancelButton: true, confirmButtonColor: '#87714c', cancelButtonColor: '#d33', confirmButtonText: 'Ya', cancelButtonText: 'Batal', background: '#fffcf9'
            }).then((result) => { if (result.isConfirmed) { window.location.href = url; } })
        }
        
        function submitHapusTamu(id) {
            Swal.fire({
                title: 'Hapus Data Tamu?', text: 'Data yang dihapus tidak dapat dikembalikan.', icon: 'warning', iconColor: '#87714c', showCancelButton: true, confirmButtonColor: '#87714c', cancelButtonColor: '#d33', confirmButtonText: 'Ya', cancelButtonText: 'Batal', background: '#fffcf9'
            }).then((result) => {
                if (result.isConfirmed) {
                    let f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<?= csrf_field() ?><input type="hidden" name="hapus_id" value="'+id+'">';
                    document.body.appendChild(f); f.submit();
                }
            })
        }
    function confirmSync() {
    Swal.fire({
        title: 'Singkronkan ke Broadcast?',
        text: "Semua tamu di event ini akan dimasukkan ke antrian kirim pesan (hanya yang belum ada).",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1a0f0d',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Singkronkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formSync').submit();
        }
    })
}
</script>
</body>
</html>