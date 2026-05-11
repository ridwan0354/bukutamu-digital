<?php
// 1. Cek Session Aman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'koneksi.php';

// 2. Cek & Update Database
if(mysqli_num_rows(mysqli_query($koneksi,"SHOW COLUMNS FROM tamu LIKE 'is_manual'"))==0){mysqli_query($koneksi,"ALTER TABLE tamu ADD COLUMN is_manual TINYINT(1) DEFAULT 0");}

// 3. Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

// 3. Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// 4. Timezone & Config Global
$q_global = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$config_global = mysqli_fetch_assoc($q_global);
date_default_timezone_set($config_global['timezone'] ?? 'Asia/Jakarta');

$uid  = $_SESSION['user_id'] ?? 0;
$role = $_SESSION['role'] ?? 'mempelai';
$parent_id = $_SESSION['parent_id'] ?? 0;
// Receptionist acts on behalf of parent
$effective_uid = ($role == 'receptionist' && $parent_id > 0) ? $parent_id : $uid;

// ==========================================
// 5. LOGIKA FILTER EVENT
// ==========================================
if ($role == 'admin') {
    $q_events_list = mysqli_query($koneksi, "SELECT * FROM events ORDER BY id DESC");
} else {
    $q_events_list = mysqli_query($koneksi, "SELECT * FROM events WHERE user_id = '$effective_uid' ORDER BY id DESC");
}

$selected_event_id = isset($_GET['event_id']) ? mysqli_real_escape_string($koneksi, $_GET['event_id']) : '';

// Auto Select Event Terakhir
if (empty($selected_event_id) && mysqli_num_rows($q_events_list) > 0) {
    mysqli_data_seek($q_events_list, 0); 
    $first_event = mysqli_fetch_assoc($q_events_list);
    $selected_event_id = $first_event['id'];
    mysqli_data_seek($q_events_list, 0); 
}

// Where Clause Data Tamu
if ($role == 'admin') {
    $where_clause = !empty($selected_event_id) ? " WHERE event_id = '$selected_event_id'" : "";
} else {
    $where_clause = !empty($selected_event_id) ? " WHERE event_id = '$selected_event_id' AND event_id IN (SELECT id FROM events WHERE user_id = '$effective_uid')" : " WHERE event_id IN (SELECT id FROM events WHERE user_id = '$effective_uid')";
}

// ==========================================
// 6. AMBIL DATA EVENT & GAMBAR DINAMIS
// ==========================================
$current_event = null;
if(!empty($selected_event_id)) {
    $q_evt = mysqli_query($koneksi, "SELECT * FROM events WHERE id='$selected_event_id'");
    $current_event = mysqli_fetch_assoc($q_evt);
}

$display_title = $current_event['event_name'] ?? 'Dashboard Administrator';
$display_date  = isset($current_event['event_date']) ? date('l, d F Y', strtotime($current_event['event_date'])) : date('l, d F Y');

// --- LOGIKA LINK UNDANGAN ---
if (!empty($current_event['broadcast_link'])) {
    $display_link = $current_event['broadcast_link'];
} elseif (!empty($config_global['broadcast_link'])) {
    $display_link = $config_global['broadcast_link'];
} else {
    $display_link = '#';
}

// --- LOGIKA GAMBAR ---
$def_cover   = 'https://images.unsplash.com/photo-1519741497674-611481863552?auto=format&fit=crop&w=1350&q=80'; 
$def_profile = 'https://ui-avatars.com/api/?name='.urlencode($current_event['event_name'] ?? 'Admin').'&background=87714c&color=fff&size=256';

// 1. Set Cover
$final_cover = $def_cover;
if (!empty($current_event['event_photo']) && file_exists('assets/' . $current_event['event_photo'])) {
    $final_cover = 'assets/' . $current_event['event_photo'];
} elseif (!empty($config_global['cover_img']) && file_exists('assets/' . $config_global['cover_img'])) {
    $final_cover = 'assets/' . $config_global['cover_img'];
}

// 2. Set Profile
$final_profile = $def_profile;
if (!empty($current_event['event_logo']) && file_exists('assets/' . $current_event['event_logo'])) { 
    $final_profile = 'assets/' . $current_event['event_logo'];
} elseif (!empty($config_global['app_logo']) && file_exists('assets/' . $config_global['app_logo'])) {
    $final_profile = 'assets/' . $config_global['app_logo'];
}

$swal_script = "";

// ==========================================
// 7. PROSES SCAN & CRUD (AJAX SUPPORT)
// ==========================================
if (isset($_POST['ajax_process_qr'])) {
    header('Content-Type: application/json');
    $qr_content = trim($_POST['qr_code_data']);
    $scan_event = isset($_POST['event_id']) ? mysqli_real_escape_string($koneksi, $_POST['event_id']) : $selected_event_id;
    $nama_tamu = $qr_content; 

    // Ambil Parameter URL dari DB
    $q_setting = mysqli_query($koneksi, "SELECT broadcast_param_id FROM events WHERE id='$scan_event'");
    $d_setting = mysqli_fetch_assoc($q_setting);
    if(!$d_setting) {
        $d_setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT broadcast_param_id FROM pengaturan LIMIT 1"));
    }

    $pid = $d_setting['broadcast_param_id'] ?? 1;
    $q_key = mysqli_query($koneksi, "SELECT param_key FROM master_broadcast_params WHERE id='$pid'");
    $d_key = mysqli_fetch_assoc($q_key);
    $param_key_db = $d_key['param_key'] ?? 'to'; 

    // Parsing URL
    $parsed = parse_url($qr_content);
    if(isset($parsed['query'])) {
        parse_str($parsed['query'], $params);
        $found_param = false;
        foreach($params as $k => $v) {
            if(strtolower($k) == strtolower($param_key_db)) {
                $nama_tamu = $v;
                $found_param = true;
                break;
            }
        }
    }
    
    // SECURITY: Verify Event Ownership for QR Scan
    if($role != 'admin'){
        $check_scan_auth = mysqli_query($koneksi, "SELECT id FROM events WHERE id='$scan_event' AND user_id='$effective_uid'");
        if(mysqli_num_rows($check_scan_auth) == 0){
            echo json_encode(['status'=>'error', 'msg'=>'Akses Ditolak! Event bukan milik Anda.']);
            exit;
        }
    }

    $nama_esc = mysqli_real_escape_string($koneksi, $nama_tamu);
    $q_cek = mysqli_query($koneksi, "SELECT * FROM tamu WHERE nama_tamu LIKE '$nama_esc' AND event_id='$scan_event' LIMIT 1");

    if (mysqli_num_rows($q_cek) > 0) {
        $dt = mysqli_fetch_assoc($q_cek);
        $id_tamu = $dt['id'];
        $waktu_checkin = date('Y-m-d H:i:s');
        
        if(empty($dt['checkin_at'])){
            mysqli_query($koneksi, "UPDATE tamu SET checkin_at='$waktu_checkin' WHERE id='$id_tamu'");
            $display_name = htmlspecialchars_decode($dt['nama_tamu'], ENT_QUOTES);
            echo json_encode(['status' => 'success', 'message' => 'Selamat Datang, ' . $display_name, 'is_new' => false]);
        } else {
            $display_name = htmlspecialchars_decode($dt['nama_tamu'], ENT_QUOTES);
            echo json_encode(['status' => 'info', 'message' => $display_name . ' sudah hadir sebelumnya.']);
        }
    } else {
        // Jika tidak ditemukan, otomatis tambah sebagai tamu manual
        $waktu_now = date('Y-m-d H:i:s');
        $q_ins = mysqli_query($koneksi, "INSERT INTO tamu (event_id, nama_tamu, checkin_at, is_manual, jumlah_orang, kategori) VALUES ('$scan_event', '$nama_esc', '$waktu_now', 1, 1, 'UMUM')");
        if($q_ins) {
            $display_name = htmlspecialchars_decode($nama_tamu, ENT_QUOTES);
            echo json_encode(['status' => 'success', 'message' => 'Tamu baru ditambahkan & Check-in!', 'is_new' => true, 'name' => $display_name]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal memproses data.']);
        }
    }
    exit;
}

// (Existing Non-AJAX fallback if needed)
if (isset($_POST['process_qr'])) {
    $qr_content = trim($_POST['qr_code_data']);
    $scan_event = $selected_event_id;
    $nama_tamu = $qr_content; 
    
    // Ambil Parameter URL dari DB
    $q_setting = mysqli_query($koneksi, "SELECT broadcast_param_id FROM events WHERE id='$scan_event'");
    $d_setting = mysqli_fetch_assoc($q_setting);
    if(!$d_setting) {
        $d_setting = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT broadcast_param_id FROM pengaturan LIMIT 1"));
    }

    $pid = $d_setting['broadcast_param_id'] ?? 1;
    $q_key = mysqli_query($koneksi, "SELECT param_key FROM master_broadcast_params WHERE id='$pid'");
    $d_key = mysqli_fetch_assoc($q_key);
    $param_key_db = $d_key['param_key'] ?? 'to'; 

    // Parsing URL
    $parsed = parse_url($qr_content);
    if(isset($parsed['query'])) {
        parse_str($parsed['query'], $params);
        if(isset($params[$param_key_db])) {
            $nama_tamu = urldecode($params[$param_key_db]);
        } 
    }

    $nama_esc = mysqli_real_escape_string($koneksi, $nama_tamu);
    
    // Cek Data
    $q_cek = mysqli_query($koneksi, "SELECT * FROM tamu WHERE nama_tamu LIKE '$nama_esc' AND event_id='$scan_event' LIMIT 1");
    
    if (mysqli_num_rows($q_cek) > 0) {
        $dt = mysqli_fetch_assoc($q_cek);
        $id_tamu = $dt['id'];
        $waktu_checkin = date('Y-m-d H:i:s');
        
        if(empty($dt['checkin_at'])){
            mysqli_query($koneksi, "UPDATE tamu SET checkin_at='$waktu_checkin' WHERE id='$id_tamu'");
            $swal_script = "Swal.fire({title: 'Check-in Berhasil!', text: 'Selamat Datang, $nama_tamu', icon: 'success', timer: 2000, showConfirmButton: false});";
        } else {
            $swal_script = "Swal.fire({title: 'Sudah Check-in!', text: '$nama_tamu sudah hadir sebelumnya.', icon: 'info', timer: 2000, showConfirmButton: false});";
        }
    } else {
        $swal_script = "Swal.fire({title: 'Gagal!', text: 'Tamu \"$nama_tamu\" tidak ditemukan di event ini.', icon: 'error'});";
    }
}

if (isset($_POST['ajax_update_pax'])) {
    header('Content-Type: application/json');
    $id_tamu = (int)$_POST['id_tamu'];
    $jml_pax = (int)$_POST['jml_pax'];
    if($jml_pax < 1) $jml_pax = 1;
    if(mysqli_query($koneksi, "UPDATE tamu SET jumlah_orang='$jml_pax' WHERE id=$id_tamu")){
        echo json_encode(['status'=>'success', 'message'=>'Jumlah Pax berhasil diperbarui.']);
    } else {
        echo json_encode(['status'=>'error', 'message'=>'Gagal memperbarui Pax.']);
    }
    exit;
}

if (isset($_GET['ajax_manual_id'])) {
    header('Content-Type: application/json');
    $id_manual = (int)$_GET['ajax_manual_id'];
    $w_now = date('Y-m-d H:i:s');
    mysqli_query($koneksi, "UPDATE tamu SET checkin_at='$w_now' WHERE id=$id_manual");
    echo json_encode(['status'=>'success', 'message'=>'Manual Check-in Berhasil']); 
    exit;
}
if (isset($_GET['manual_id'])) {
    $id_manual = (int) $_GET['manual_id'];
    $w_now = date('Y-m-d H:i:s');
    mysqli_query($koneksi, "UPDATE tamu SET checkin_at='$w_now' WHERE id=$id_manual");
    header("Location: dashboard?event_id=$selected_event_id"); exit;
}
if (isset($_GET['ajax_reset_id'])) {
    header('Content-Type: application/json');
    $id_reset = (int)$_GET['ajax_reset_id'];
    mysqli_query($koneksi, "UPDATE tamu SET checkin_at=NULL WHERE id=$id_reset");
    echo json_encode(['status'=>'success', 'message'=>'Reset Status Berhasil']); 
    exit;
}
if (isset($_GET['reset_id'])) {
    $id_reset = (int) $_GET['reset_id'];
    mysqli_query($koneksi, "UPDATE tamu SET checkin_at = NULL WHERE id=$id_reset");
    header("Location: dashboard?event_id=$selected_event_id"); exit;
}
if (isset($_GET['hapus_id'])) {
    $id_hapus = (int) $_GET['hapus_id'];
    mysqli_query($koneksi, "DELETE FROM tamu WHERE id=$id_hapus");
    header("Location: dashboard?event_id=$selected_event_id"); exit;
}

// STATISTIK
$q_rows = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tamu $where_clause");
$stat_undangan = mysqli_fetch_assoc($q_rows)['total'];
$q_sum = mysqli_query($koneksi, "SELECT SUM(jumlah_orang) as total FROM tamu $where_clause");
$stat_estimasi = mysqli_fetch_assoc($q_sum)['total'] ?? 0;
$prefix_hadir = empty($where_clause) ? "WHERE" : "AND";
$sql_hadir = "SELECT SUM(jumlah_orang) as total FROM tamu $where_clause $prefix_hadir checkin_at IS NOT NULL";
$stat_hadir = mysqli_fetch_assoc(mysqli_query($koneksi, $sql_hadir))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= $config_global['app_name'] ?></title>
    <?php if(!empty($config_global['favicon'])): ?>
    <link rel="icon" href="assets/<?= $config_global['favicon'] ?>?v=<?= time() ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; background-image: url("https://www.transparenttextures.com/patterns/cream-paper.png"); }
        .font-serif { font-family: 'Playfair Display', serif; }
        .text-gold { color: #f4c78c; }
        .text-brown { color: #1a0f0d; }
        
        .select2-container .select2-selection--single { 
            height: 36px !important; border: none !important; background-color: transparent !important; padding-top: 4px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #000000 !important; font-weight: 700 !important; font-size: 0.875rem !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #000000 transparent transparent transparent !important;
        }
        
        @media (max-width: 768px) { body { padding-bottom: 80px; } }
        
        .dropdown-menu { transform-origin: top left; transition: all 0.2s ease-in-out; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #87714c; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background-color: #f3e9d8; }

        /* Custom Scanner Style Fix - Light Theme */
        #reader { border: none !important; border-radius: 12px; overflow: hidden; background: #ffffff !important; }
        #reader video { object-fit: cover !important; width: 100% !important; height: 100% !important; }
        #reader__scan_region { background: transparent !important; }
        #reader__dashboard { display: none !important; }
        #reader img { display: none !important; }
        #reader__status_span { display: none !important; }
        #reader__header_message { display: none !important; }
        #reader__camera_selection { display: none !important; }
        
        #reader__scan_region canvas { display: none !important; }

        .scan-frame {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none; z-index: 10;
            background: rgba(255,255,255,0.3);
            clip-path: polygon(0% 0%, 0% 100%, 15% 100%, 15% 15%, 85% 15%, 85% 85%, 15% 85%, 15% 100%, 100% 100%, 100% 0%);
        }
        .scan-corner { position: absolute; width: 30px; height: 30px; border: 3px solid #87714c; border-radius: 4px; }
        .scan-corner-tl { top: 12%; left: 12%; border-right: 0; border-bottom: 0; }
        .scan-corner-tr { top: 12%; right: 12%; border-left: 0; border-bottom: 0; }
        .scan-corner-bl { bottom: 12%; left: 12%; border-right: 0; border-top: 0; }
        .scan-corner-br { bottom: 12%; right: 12%; border-left: 0; border-top: 0; }
        
        .scan-line {
            position: absolute; top: 15%; left: 15%; right: 15%; height: 2px;
            background: linear-gradient(to right, transparent, #87714c, transparent);
            animation: scanMove 3s ease-in-out infinite;
        }
        @keyframes scanMove { 0% { top: 15%; opacity: 0; } 20% { opacity: 1; } 80% { opacity: 1; } 100% { top: 85%; opacity: 0; } }
    </style>
</head>
<body class="text-[#1a0f0d]" style="background-color:#ffffff; background-image:url('https://www.transparenttextures.com/patterns/cream-paper.png');">

    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <main class="md:ml-64 p-4 lg:p-6 relative">
        
        <div class="relative w-[calc(100%+2rem)] lg:w-full rounded-b-3xl lg:rounded-3xl bg-[#eae0d5] border-b lg:border border-[#d1c7b7] shadow-md mb-6 overflow-hidden -mt-4 -mx-4 lg:mt-0 lg:mx-0">
            <div class="w-full h-40 lg:h-64 relative">
                <img src="<?= $final_cover ?>" alt="Cover" class="w-full h-full object-cover object-center">
                
                <div class="absolute inset-0 bg-gradient-to-t from-[#eae0d5] via-transparent to-black/10"></div>
                
                <div class="absolute top-4 right-4 z-30">
                    <form action="" method="GET">
                        <div class="relative bg-white/90 backdrop-blur-md rounded-full px-4 py-1.5 shadow-lg border border-white/50 flex items-center hover:bg-white transition cursor-pointer">
                            <i class="fas fa-calendar-alt text-[#87714c] mr-2 text-xs"></i>
                            <select name="event_id" class="w-40 lg:w-64 bg-transparent border-none text-sm font-bold text-[#1a0f0d] focus:ring-0 cursor-pointer appearance-none outline-none" onchange="this.form.submit()">
                                <?php if($q_events_list) { mysqli_data_seek($q_events_list, 0); while($evt = mysqli_fetch_assoc($q_events_list)): ?>
                                    <option value="<?= $evt['id'] ?>" <?= ($selected_event_id == $evt['id']) ? 'selected' : '' ?>>
                                        <?= mb_strimwidth($evt['event_name'], 0, 25, "...") ?>
                                    </option>
                                <?php endwhile; } ?>
                            </select>
                            <i class="fas fa-chevron-down text-[#1a0f0d] ml-2 text-[10px]"></i>
                        </div>
                    </form>
                </div>
            </div>

            <div class="px-6 pb-6 lg:px-10 lg:pb-8 flex flex-col lg:flex-row items-center lg:items-end justify-between relative -mt-16 lg:-mt-20 gap-4">
                <div class="flex flex-col lg:flex-row items-center lg:items-end gap-4 lg:gap-6 z-10 w-full text-center lg:text-left">
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-[#87714c] to-[#b08d55] rounded-full opacity-75 group-hover:opacity-100 transition duration-200 blur-sm"></div>
                        <div class="relative w-32 h-32 lg:w-40 lg:h-40 rounded-full border-4 border-[#ffffff] overflow-hidden bg-white shadow-xl">
                            <img src="<?= $final_profile ?>" alt="Profile" class="w-full h-full object-cover">
                        </div>
                    </div>
                    <div class="mb-2 lg:mb-4">
                        <span class="inline-block py-1.5 px-4 rounded-full bg-[#1a0f0d] text-white text-[10px] font-bold tracking-widest uppercase mb-2 shadow-sm border border-[#1a0f0d]">The Wedding Of</span>
                        <h1 class="text-3xl lg:text-5xl font-serif font-bold text-[#1a0f0d] drop-shadow-sm leading-tight"><?= $display_title ?></h1>
                        <p class="text-sm text-[#87714c] mt-2 flex items-center justify-center lg:justify-start gap-2 font-medium"><i class="far fa-calendar"></i> <?= $display_date ?></p>
                    </div>
                </div>
                <div class="hidden lg:flex flex-col items-end z-10 mb-4">
                    <a href="<?= $display_link ?>" target="_blank" class="group bg-white border border-[#e8e1d5] hover:border-[#87714c] text-[#1a0f0d] text-xs font-bold px-6 py-3 rounded-xl shadow-sm transition flex items-center gap-2 transform hover:-translate-y-1">
                        <span>Buka Undangan</span><i class="fas fa-external-link-alt text-[#87714c] group-hover:rotate-45 transition-transform duration-300"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 lg:gap-6 mb-8" id="stat-grid">
            <div class="bg-white border border-[#e8e1d5] p-5 rounded-xl flex items-center gap-3 shadow-sm hover:translate-y-[-2px] transition duration-300">
                <div class="w-14 h-14 bg-[#fff8e1] text-[#87714c] rounded-2xl flex items-center justify-center text-2xl shadow-inner"><i class="fas fa-database"></i></div>
                <div><p class="text-[#87714c] text-[10px] font-bold uppercase tracking-wider">Total Data</p><h3 class="text-2xl font-serif font-bold text-[#1a0f0d]"><?= $stat_undangan ?> <span class="text-sm font-sans font-normal text-gray-400">Baris</span></h3></div>
            </div>
            <div class="bg-white border border-[#e8e1d5] p-5 rounded-xl flex items-center gap-3 shadow-sm hover:translate-y-[-2px] transition duration-300">
                <div class="w-14 h-14 bg-[#fff8e1] text-[#a1887f] rounded-2xl flex items-center justify-center text-2xl shadow-inner"><i class="fas fa-users"></i></div>
                <div><p class="text-[#a1887f] text-[10px] font-bold uppercase tracking-wider">Estimasi Pax</p><h3 class="text-2xl font-serif font-bold text-[#1a0f0d]"><?= $stat_estimasi ?> <span class="text-sm font-sans font-normal text-gray-400">Orang</span></h3></div>
            </div>
            <div class="bg-white border border-[#e8e1d5] p-5 rounded-xl flex items-center gap-3 shadow-sm hover:translate-y-[-2px] transition duration-300">
                <div class="w-14 h-14 bg-[#fff8e1] text-[#81c784] rounded-2xl flex items-center justify-center text-2xl shadow-inner"><i class="fas fa-user-check"></i></div>
                <div><p class="text-[#81c784] text-[10px] font-bold uppercase tracking-wider">Pax Hadir</p><h3 class="text-2xl font-serif font-bold text-[#1a0f0d]"><?= $stat_hadir ?> <span class="text-sm font-sans font-normal text-gray-400">Orang</span></h3></div>
            </div>
        </div>

        <!-- Quick Actions & Search -->
        <div class="bg-white p-4 lg:p-5 rounded-2xl shadow-sm border border-[#e8e1d5] mb-6 flex flex-col xl:flex-row justify-between items-center gap-5">
            <div class="flex flex-col gap-4 w-full xl:w-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-row gap-3 w-full">
                    <!-- Action Buttons Row -->
                    <div class="flex gap-3 w-full lg:w-auto">
                        <button type="button" onclick="startCamera()" class="flex-1 lg:flex-none bg-[#1a0f0d] hover:bg-black text-white px-6 py-3.5 rounded-xl text-xs font-black tracking-widest transition-all flex justify-center items-center gap-2 shadow-lg active:scale-95">
                            <iconify-icon icon="solar:qr-code-bold-duotone" class="text-lg"></iconify-icon> <span>SCAN</span>
                        </button>
                        <a href="display.php?event_id=<?= $selected_event_id ?>" target="_blank" class="flex-1 lg:flex-none bg-white border border-[#e8e1d5] text-[#1a0f0d] hover:bg-[#fffbf2] px-6 py-3.5 rounded-xl text-xs font-black tracking-widest transition-all flex justify-center items-center gap-2 shadow-sm active:scale-95">
                            <iconify-icon icon="solar:monitor-bold-duotone" class="text-lg text-[#87714c]"></iconify-icon> <span>LAYAR SAPA</span>
                        </a>
                    </div>

                    <!-- Manual Input -->
                    <div class="relative w-full lg:w-80 group/manual">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#87714c]">
                            <iconify-icon icon="solar:user-plus-bold-duotone" width="18"></iconify-icon>
                        </div>
                        <input type="text" id="manualInputName" placeholder="Ketik Nama Tamu..." 
                            class="w-full pl-12 pr-28 py-3.5 bg-[#faf7f0] border border-[#e8e1d5] focus:border-[#87714c] focus:ring-0 rounded-xl text-sm font-bold placeholder:font-medium placeholder:text-gray-400 transition-all outline-none" 
                            onkeypress="if(event.key==='Enter') processManualTyping()">
                        <button type="button" onclick="processManualTyping()" 
                            class="absolute right-1.5 top-1.5 bottom-1.5 px-4 bg-[#87714c] text-white rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-[#1a0f0d] transition-all active:scale-95">
                            Check-in
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Table Search -->
            <div class="w-full xl:w-80 relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <iconify-icon icon="solar:magnifer-linear" class="text-[#a1887f] text-lg"></iconify-icon>
                </div>
                <input type="text" id="searchTable" placeholder="Cari nama tamu..." 
                    class="w-full pl-12 pr-4 py-3.5 border border-[#e8e1d5] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#87714c]/20 focus:border-[#87714c] bg-white text-[#1a0f0d] shadow-sm font-medium transition-all">
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-[#e8e1d5] overflow-hidden min-h-[400px]">
            <div class="overflow-x-auto custom-scrollbar pb-4">
                <table class="w-full text-left border-collapse min-w-[1000px] lg:min-w-full" id="mainTable">
                    <thead>
                        <tr class="bg-[#1a0f0d] text-white text-xs uppercase tracking-wider font-bold">
                            <th class="px-4 py-4 text-center w-[5%] rounded-tl-lg">No</th>
                            <th class="px-4 py-4 text-left w-[30%]">Nama Tamu</th>
                            <th class="px-4 py-4 text-left w-[15%]">Waktu Input</th>
                            <th class="px-4 py-4 text-center w-[15%]">Kategori</th>
                            <th class="px-4 py-4 text-center w-[10%]">Pax</th>
                            <th class="px-4 py-4 text-center w-[15%]">Status</th>
                            <th class="px-4 py-4 text-center w-[10%] rounded-tr-lg">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3e9d8] text-sm text-[#1a0f0d]">
                        <?php 
                        $no=1; 
                        $q_tamu = mysqli_query($koneksi, "SELECT * FROM tamu $where_clause ORDER BY checkin_at DESC, id DESC LIMIT 100"); 
                        
                        if($q_tamu && mysqli_num_rows($q_tamu) > 0):
                            while($row=mysqli_fetch_assoc($q_tamu)): 
                        ?>
                        <tr class="hover:bg-[#fffbf2] transition">
                            <td class="py-3 text-center text-gray-400"><?= $no++ ?></td>
                            
                            <td class="py-3 px-4">
                                <div class="font-bold text-[#1a0f0d] flex items-center gap-2">
                                    <?= $row['nama_tamu'] ?>
                                    <?php if($row['is_manual']): ?>
                                        <span class="inline-flex items-center gap-1 text-[8px] font-black bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded border border-amber-100 uppercase tracking-tighter">
                                            Manual
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-[10px] text-[#87714c]"><?= $row['alamat'] ?? '-' ?></div>
                            </td>

                            <td class="py-3 px-4 text-gray-500 text-xs"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
                            
                            <td class="py-3 text-center">
                                <span class="px-2.5 py-1 rounded-md text-[10px] bg-[#ffffff] border border-[#e8e1d5] font-bold text-[#87714c] inline-flex items-center gap-1 uppercase transition-all shadow-sm">
                                    <?php if(strtoupper($row['kategori']) === 'VIP'): ?>
                                        <iconify-icon icon="solar:crown-minimalistic-bold-duotone" class="text-[#87714c] text-xs"></iconify-icon>
                                    <?php endif; ?>
                                    <?= $row['kategori'] ?>
                                </span>
                            </td>

                            <td class="py-3 text-center">
                                <button type="button" onclick="editPax('<?= $row['id'] ?>', '<?= $row['jumlah_orang'] ?>', '<?= addslashes(htmlspecialchars_decode($row['nama_tamu'], ENT_QUOTES)) ?>')" class="inline-block font-bold text-xl text-[#87714c] font-serif hover:bg-[#fffbf2] px-3 py-1 rounded-lg transition-all border border-transparent hover:border-[#e8e1d5] shadow-sm hover:shadow" title="Klik untuk edit Pax">
                                    <?= $row['jumlah_orang'] ?>
                                </button>
                            </td>

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
                                    <?php if(!empty($row['checkin_at'])): ?>
                                        <button type="button" onclick="confirmAjaxAction('<?= $row['id'] ?>', 'reset', '<?= addslashes($row['nama_tamu']) ?>')" class="bg-amber-50 text-amber-600 hover:bg-amber-100 w-8 h-8 flex items-center justify-center rounded-lg transition border border-amber-200 shadow-sm" title="Reset Check-in">
                                            <iconify-icon icon="solar:undo-left-round-bold-duotone" class="text-lg"></iconify-icon>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" onclick="confirmAjaxAction('<?= $row['id'] ?>', 'manual', '<?= addslashes($row['nama_tamu']) ?>')" class="bg-green-50 text-green-600 hover:bg-green-100 w-8 h-8 flex items-center justify-center rounded-lg transition border border-green-200 shadow-sm" title="Check-in Manual">
                                            <iconify-icon icon="solar:check-circle-bold-duotone" class="text-lg"></iconify-icon>
                                        </button>
                                    <?php endif; ?>
                                    <a href="?hapus_id=<?= $row['id'] ?>&event_id=<?= $selected_event_id ?>" onclick="return confirm('Hapus Data Tamu?')" class="bg-red-50 text-red-500 hover:bg-red-100 w-8 h-8 flex items-center justify-center rounded-lg transition border border-red-200 shadow-sm" title="Hapus">
                                        <iconify-icon icon="solar:trash-bin-trash-bold-duotone" class="text-lg"></iconify-icon>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="7" class="p-8 text-center text-gray-400 italic">Belum ada data tamu.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
        </footer>
    </main>

    <div id="modalScanQR" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4"> 
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="stopCamera()"></div> 
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-100"> 
            <div class="px-5 py-3 border-b border-gray-50 flex justify-between items-center bg-white">
                <h3 class="text-base font-black tracking-tight text-[#1a0f0d] font-serif uppercase">Scan Undangan</h3>
                <button onclick="stopCamera()" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-red-50 hover:text-red-500 text-gray-400 rounded-lg transition-all">
                    <iconify-icon icon="solar:close-circle-bold" class="text-xl"></iconify-icon>
                </button>
            </div> 
            <div class="relative w-full aspect-square bg-[#f8f9fa] overflow-hidden">
                <div id="reader" class="w-full h-full"></div> 
                <div class="scan-frame"></div>
                <div class="scan-corner scan-corner-tl"></div>
                <div class="scan-corner scan-corner-tr"></div>
                <div class="scan-corner scan-corner-bl"></div>
                <div class="scan-corner scan-corner-br"></div>
                <div class="scan-line"></div>
            </div>
            <div class="p-4 bg-white border-t border-gray-50">
                <div class="mb-3">
                    <label class="block text-[9px] font-black text-[#87714c] uppercase tracking-widest mb-1.5 ml-1">Sumber Kamera</label>
                    <div class="relative group/cam">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#87714c]">
                            <iconify-icon icon="solar:camera-bold-duotone" width="16"></iconify-icon>
                        </div>
                        <select id="cameraSelection" class="w-full pl-10 pr-9 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-[11px] font-bold text-[#1a0f0d] transition-all outline-none appearance-none cursor-pointer"></select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-[#87714c]">
                            <iconify-icon icon="solar:alt-arrow-down-bold-duotone" width="12"></iconify-icon>
                        </div>
                    </div>
                </div>
                <div id="formScanResult" class="hidden"></div>
                <p class="text-center text-[9px] text-[#87714c] font-black uppercase tracking-widest opacity-80">Arahkan QR ke dalam kotak scanner.</p>
            </div> 
        </div> 
    </div>

    <script>        $(document).ready(function() {
            $('.select2-event').select2({ placeholder: "Pilih Event...", width: '100%' });
            
            // Search Table
            $("#searchTable").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#mainTable tbody tr").filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) });
            });

            // Logic Auto-Open Camera dari Home Mempelai
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('scan') && urlParams.get('scan') === 'true') {
                const newUrl = window.location.pathname + (window.location.search.replace(/[\?&]scan=true/, '').replace(/^&/, '?') || '');
                window.history.replaceState(null, null, newUrl);
                
                setTimeout(function() {
                    startCamera();
                }, 500);
            }
        });
        
        // --- LOGIKA SCANNER AMAN (AJAX) ---
        var html5QrcodeScanner = null;
        var isScanning = false;

        function startCamera() {
            document.getElementById('modalScanQR').classList.remove('hidden');
            document.getElementById('modalScanQR').classList.add('flex');

            if (html5QrcodeScanner === null) {
                html5QrcodeScanner = new Html5Qrcode("reader");
            }

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    var cameraId = devices[0].id;
                    for (var i = 0; i < devices.length; i++) {
                        if (devices[i].label.toLowerCase().includes('back')) {
                            cameraId = devices[i].id;
                            break;
                        }
                    }
                    
                    const cameraSelect = document.getElementById('cameraSelection');
                    cameraSelect.innerHTML = "";
                    devices.forEach(device => {
                        const option = document.createElement("option");
                        option.value = device.id;
                        option.text = device.label;
                        cameraSelect.appendChild(option);
                    });
                    cameraSelect.value = cameraId;

                    startScanning(cameraId);
                    
                    cameraSelect.onchange = function() {
                        html5QrcodeScanner.stop().then(() => {
                            startScanning(this.value);
                        });
                    };
                } else {
                    Swal.fire('Error', 'Kamera tidak ditemukan.', 'error');
                }
            }).catch(err => {
                Swal.fire('Error', 'Izin kamera ditolak atau tidak ada kamera.', 'error');
            });
        }

        function startScanning(cameraId) {
            html5QrcodeScanner.start(
                cameraId, 
                { 
                    fps: 10, 
                    qrbox: (viewfinderWidth, viewfinderHeight) => {
                        let minEdgeSize = Math.min(viewfinderWidth, viewfinderHeight);
                        let qrboxSize = Math.floor(minEdgeSize * 0.7);
                        return { width: qrboxSize, height: qrboxSize };
                    }
                },
                (decodedText, decodedResult) => {
                    if(isScanning) return;
                    isScanning = true;

                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            ajax_process_qr: 1,
                            qr_code_data: decodedText,
                            event_id: '<?= $selected_event_id ?>'
                        },
                        success: function(res) {
                            if(typeof res === 'string') res = JSON.parse(res);
                            if(res.status === 'success') {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: res.message,
                                    icon: 'success',
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                                // Refresh stats & table
                                let targetUrl = window.location.pathname + '?event_id=<?= $selected_event_id ?>';
                                $('#stat-grid').load(targetUrl + ' #stat-grid > *');
                                $('#mainTable').load(targetUrl + ' #mainTable > *');
                            } else {
                                Swal.fire({
                                    title: res.status === 'info' ? 'Info' : 'Gagal',
                                    text: res.message,
                                    icon: res.status,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                            
                            // Re-enable scanning after a delay to prevent double scan
                            setTimeout(() => { isScanning = false; }, 2500);
                        },
                        error: function(xhr, status, error) {
                            Swal.fire('Error', 'Gagal menghubungi server. Silakan coba lagi.', 'error');
                            isScanning = false;
                        }
                    });
                },
                errorMessage => { /* Silently ignore errors during search */ }
            ).catch(err => { console.log(err); });
        }

        function confirmAjaxAction(id, action, name) {
            const title = action === 'manual' ? 'Check-in Manual?' : 'Reset Status?';
            const text = action === 'manual' ? `Absensi ${name} secara manual?` : `Batalkan status check-in ${name}?`;
            const icon = action === 'manual' ? 'question' : 'warning';
            
            Swal.fire({
                title: title,
                text: text,
                icon: icon,
                showCancelButton: true,
                confirmButtonColor: '#87714c',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const param = action === 'manual' ? 'ajax_manual_id' : 'ajax_reset_id';
                    $.get(`${window.location.pathname}?${param}=${id}&event_id=<?= $selected_event_id ?>`, function(res) {
                        if(typeof res === 'string') res = JSON.parse(res);
                        if(res.status === 'success') {
                            Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false });
                            // Re-load stats & table
                            let targetUrl = window.location.pathname + '?event_id=<?= $selected_event_id ?>';
                            $('#stat-grid').load(targetUrl + ' #stat-grid > *');
                            $('#mainTable').load(targetUrl + ' #mainTable > *');
                        }
                    });
                }
            })
        }

        function editPax(id, currentPax, name) {
            Swal.fire({
                title: 'Ubah Jumlah Pax',
                text: `Tentukan jumlah orang yang hadir untuk tamu: ${name}`,
                input: 'number',
                inputValue: currentPax,
                inputAttributes: { min: 1, step: 1 },
                showCancelButton: true,
                confirmButtonColor: '#87714c',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Simpan Pax',
                cancelButtonText: 'Batal',
                customClass: { input: 'text-center font-bold text-2xl text-[#87714c]' }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    $.ajax({
                        url: window.location.href,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            ajax_update_pax: 1,
                            id_tamu: id,
                            jml_pax: result.value
                        },
                        success: function(res) {
                            if(res.status === 'success') {
                                Swal.fire({ title: 'Berhasil!', text: res.message, icon: 'success', timer: 1500, showConfirmButton: false });
                                let targetUrl = window.location.pathname + '?event_id=<?= $selected_event_id ?>';
                                $('#stat-grid').load(targetUrl + ' #stat-grid > *');
                                $('#mainTable').load(targetUrl + ' #mainTable > *');
                            } else {
                                Swal.fire({ title: 'Gagal', text: res.message, icon: 'error', timer: 1500, showConfirmButton: false });
                            }
                        }
                    });
                }
            });
        }

        function processManualTyping() {
            const name = document.getElementById('manualInputName').value.trim();
            if(!name) {
                Swal.fire({ title: 'Opps!', text: 'Masukkan nama tamu terlebih dahulu', icon: 'warning', timer: 1500, showConfirmButton: false });
                return;
            }

            $.ajax({
                url: window.location.href,
                method: 'POST',
                dataType: 'json',
                data: {
                    ajax_process_qr: 1,
                    qr_code_data: name,
                    event_id: '<?= $selected_event_id ?>'
                },
                success: function(res) {
                    if(typeof res === 'string') res = JSON.parse(res);
                    if(res.status === 'success') {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: res.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        document.getElementById('manualInputName').value = '';
                        // Refresh stats & table
                        let targetUrl = window.location.pathname + '?event_id=<?= $selected_event_id ?>';
                        $('#stat-grid').load(targetUrl + ' #stat-grid > *');
                        $('#mainTable').load(targetUrl + ' #mainTable > *');
                    } else {
                        Swal.fire({
                            title: res.status === 'info' ? 'Info' : 'Gagal',
                            text: res.message,
                            icon: res.status,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    }
                }
            });
        }

        function stopCamera() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    document.getElementById('modalScanQR').classList.add('hidden');
                    document.getElementById('modalScanQR').classList.remove('flex');
                    isScanning = false;
                }).catch(err => {
                    document.getElementById('modalScanQR').classList.add('hidden');
                    document.getElementById('modalScanQR').classList.remove('flex');
                });
            } else {
                document.getElementById('modalScanQR').classList.add('hidden');
                document.getElementById('modalScanQR').classList.remove('flex');
            }
        }
    </script>
</body>
</html>