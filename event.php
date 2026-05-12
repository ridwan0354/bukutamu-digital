<?php
// Cek Session Aman
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

// Cek & Update Database (Otomatis Tambah Kolom jika belum ada)
$check_kat = mysqli_query($koneksi, "SHOW COLUMNS FROM kategori_tamu LIKE 'user_id'");
if($check_kat && mysqli_num_rows($check_kat) == 0){ mysqli_query($koneksi, "ALTER TABLE kategori_tamu ADD COLUMN user_id INT DEFAULT 1 AFTER id"); }
// Tambah kolom event_subtitle jika belum ada
$check_sub = mysqli_query($koneksi, "SHOW COLUMNS FROM events LIKE 'event_subtitle'");
if($check_sub && mysqli_num_rows($check_sub) == 0){ mysqli_query($koneksi, "ALTER TABLE events ADD COLUMN event_subtitle VARCHAR(100) DEFAULT 'The Wedding Of' AFTER event_name"); }
// 1. LOGIKA: SET STATUS AKTIF (MULTI SUPPORT)
// ==========================================
if (isset($_POST['action']) && isset($_POST['id'])) {
    $id_target = (int)$_POST['id'];
    $status_target = ($_POST['action'] == 'aktifkan') ? 'active' : 'inactive';
    $auth_check = ($role == 'admin') ? "" : " AND user_id = '$effective_uid'";
    
    // Update status tanpa mereset event lain (Mendukung Multi-event)
    mysqli_query($koneksi, "UPDATE events SET status = '$status_target' WHERE id = $id_target $auth_check");
    
    header("Location: event");
    exit;
}

// ==========================================
// 2. LOGIKA: TAMBAH EVENT BARU (CEK LIMIT)
// ==========================================
if (isset($_POST['tambah_event'])) {
    
    // 1. Ambil Limit User
    $q_user = mysqli_query($koneksi, "SELECT event_limit FROM users WHERE id='$effective_uid'");
    $d_user = mysqli_fetch_assoc($q_user);
    $limit_kuota = $d_user['event_limit'] ?? 1;

    // 2. Hitung Event User Saat Ini
    $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM events WHERE user_id='$effective_uid'");
    $d_count = mysqli_fetch_assoc($q_count);
    $current_total = $d_count['total'];

    // 3. Cek Apakah Melebihi Limit?
    if ($role != 'admin' && $current_total >= $limit_kuota) {
        $pesan_err = "Gagal! Kuota event Anda habis (Maks: $limit_kuota). Hubungi Admin.";
    } else {
        // Lanjut Simpan
        $name     = esc($_POST['event_name']);
        $subtitle = esc($_POST['event_subtitle'] ?? 'The Wedding Of');
        $date     = $_POST['event_date'];
        $loc      = esc($_POST['event_location']);
        $desc     = esc($_POST['deskripsi_acara']); 
        
        $nama_logo = ""; 
        if (!empty($_FILES['event_logo']['name'])) {
            $uploaded = secure_upload($_FILES['event_logo'], 'logo');
            if($uploaded) $nama_logo = $uploaded;
        }

        $nama_foto = ""; 
        if (!empty($_FILES['event_photo']['name'])) {
            $uploaded = secure_upload($_FILES['event_photo'], 'photo');
            if($uploaded) $nama_foto = $uploaded;
        }

        $query = "INSERT INTO events (user_id, event_name, event_subtitle, event_date, event_location, deskripsi_acara, event_logo, event_photo, status) 
                  VALUES ('$effective_uid', '$name', '$subtitle', '$date', '$loc', '$desc', '$nama_logo', '$nama_foto', 'inactive')";
        
        if(mysqli_query($koneksi, $query)) {
            $pesan = "Event berhasil dibuat!";
        } else {
            $pesan_err = "Gagal tambah: " . mysqli_error($koneksi);
        }
    }
}

// ==========================================
// 3. LOGIKA: EDIT DATA ACARA
// ==========================================
if (isset($_POST['simpan_edit_event'])) {
    $id_edit = (int)$_POST['id_event_edit'];
    
    $cek = mysqli_query($koneksi, "SELECT id FROM events WHERE id=$id_edit" . ($role=='admin'?'':" AND user_id='$effective_uid'"));
    if(mysqli_num_rows($cek) > 0) {
        $name     = esc($_POST['edit_event_name']);
        $subtitle = esc($_POST['edit_event_subtitle'] ?? 'The Wedding Of');
        $date     = $_POST['edit_event_date'];
        $loc      = esc($_POST['edit_event_location']);
        $desc     = esc($_POST['edit_deskripsi_acara']);
        
        $q_update = "UPDATE events SET 
                     event_name='$name', event_subtitle='$subtitle', event_date='$date', event_location='$loc', deskripsi_acara='$desc' 
                     WHERE id=$id_edit";
        
        if(mysqli_query($koneksi, $q_update)) {
            $pesan = "Data acara berhasil diperbarui!";
        } else {
            $pesan_err = "Gagal update: " . mysqli_error($koneksi);
        }
    }
}

// ==========================================
// 4. LOGIKA: UPDATE TAMPILAN (VISUAL)
// ==========================================
if (isset($_POST['update_tampilan_event'])) {
    $id_event = (int)$_POST['id_event'];
    $cek_milik = mysqli_query($koneksi, "SELECT id FROM events WHERE id=$id_event" . ($role=='admin'?'':" AND user_id='$effective_uid'"));
    
    if(mysqli_num_rows($cek_milik) > 0) {
        $bg_mode    = esc($_POST['bg_mode']);
        $bg_youtube = esc($_POST['bg_youtube']);
        $show_frame = isset($_POST['show_frame']) ? 1 : 0;
        
        $sql_files = "";
        if (!empty($_FILES['bg_image_file']['name'])) {
            $uploaded = secure_upload($_FILES['bg_image_file'], 'bg');
            if($uploaded) $sql_files .= ", bg_image='$uploaded'";
        }
        if (!empty($_FILES['frame_image_file']['name'])) {
            $uploaded = secure_upload($_FILES['frame_image_file'], 'frm');
            if($uploaded) $sql_files .= ", frame_img='$uploaded'";
        }
        if (!empty($_FILES['logo_event_file']['name'])) {
            $uploaded = secure_upload($_FILES['logo_event_file'], 'lg');
            if($uploaded) $sql_files .= ", event_logo='$uploaded'";
        }
        if (!empty($_FILES['event_photo_file']['name'])) {
            $uploaded = secure_upload($_FILES['event_photo_file'], 'ph');
            if($uploaded) $sql_files .= ", event_photo='$uploaded'";
        }

        $sql_mode = ($bg_mode == 'default') ? "bg_mode=NULL, bg_youtube=NULL, show_frame=NULL" : "bg_mode='$bg_mode', bg_youtube='$bg_youtube', show_frame='$show_frame'";
        
        if(mysqli_query($koneksi, "UPDATE events SET $sql_mode $sql_files WHERE id=$id_event")) {
            $pesan = "Tampilan acara berhasil diperbarui!";
            $swal_icon = "success";
        }
    }
}

// ==========================================
// 5. LOGIKA HAPUS
// ==========================================
if (isset($_POST['hapus_event'])) {
    $id_del = (int)$_POST['hapus_event'];
    $auth_del = ($role == 'admin') ? "" : " AND user_id = '$effective_uid'";
    
    // Hapus Tamu dulu
    mysqli_query($koneksi, "DELETE FROM tamu WHERE event_id=$id_del");
    // Hapus Event
    mysqli_query($koneksi, "DELETE FROM events WHERE id=$id_del $auth_del");
    
    header("Location: event");
    exit;
}
if (isset($_POST['tambah_kategori'])) {
    $kat = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    if(!empty($kat)) {
        if(mysqli_query($koneksi, "INSERT INTO kategori_tamu (user_id, nama_kategori) VALUES ('$effective_uid', '$kat')")) {
            $pesan = "Kategori berhasil ditambahkan!";
        }
    }
}
if (isset($_POST['hapus_kategori'])) {
    $id_kat = (int)$_POST['hapus_kategori'];
    $auth_kat = ($role == 'admin') ? "" : " AND user_id = '$effective_uid'";
    mysqli_query($koneksi, "DELETE FROM kategori_tamu WHERE id=$id_kat $auth_kat");
    header("Location: event");
    exit;
}

// AMBIL DATA
$config = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1"));
if ($role == 'admin') {
    $all_events = mysqli_query($koneksi, "SELECT * FROM events ORDER BY id DESC");
    $kategori_data = mysqli_query($koneksi, "SELECT * FROM kategori_tamu ORDER BY id DESC");
} else {
    $all_events = mysqli_query($koneksi, "SELECT * FROM events WHERE user_id = '$effective_uid' ORDER BY id DESC");
    $kategori_data = mysqli_query($koneksi, "SELECT * FROM kategori_tamu WHERE user_id = '$effective_uid' ORDER BY id DESC");
}
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
    <title>Manajemen Acara - <?= $config['app_name'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .modal { transition: opacity 0.25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #EAB676; border-radius: 4px; }
    </style>
</head>
<body class="text-[#1a0f0d]" style="background-color:#ffffff; background-image:url('https://www.transparenttextures.com/patterns/cream-paper.png');">

    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <main class="md:ml-64 p-4 lg:p-6 relative">
        <div class="max-w-7xl mx-auto">
            
            <!-- Header Section -->
            <div class="mb-5 lg:mb-6 border-b border-[#d1c7b7] pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                        <iconify-icon icon="solar:calendar-mark-bold-duotone" class="text-2xl"></iconify-icon>
                    </div>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold text-[#1a0f0d] font-serif">Manajemen Acara</h1>
                        <p class="text-[#87714c] mt-1 text-sm">Kelola acara pernikahan. Anda bisa mengaktifkan lebih dari satu acara sekaligus.</p>
                    </div>
                </div>
            </div>

            <?php if(isset($pesan) || isset($pesan_err)): ?>
            <script>
                Swal.fire({
                    icon: '<?= isset($pesan) ? ($swal_icon ?? "success") : "error" ?>',
                    title: '<?= isset($pesan) ? "Berhasil!" : "Opps!" ?>',
                    text: '<?= isset($pesan) ? $pesan : $pesan_err ?>',
                    confirmButtonColor: '#87714c',
                    timer: 3000,
                    timerProgressBar: true
                });
            </script>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
                
                <div class="xl:col-span-2 space-y-6">
                    
                    <form action="" method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded-xl shadow-sm border border-[#e8e1d5]">
                        <?= csrf_field() ?>
                        <h3 class="font-bold text-[#000000] mb-5 flex items-center gap-2 text-lg font-serif">
                            <i class="fas fa-plus-circle text-[#87714c]"></i> Buat Acara Baru
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Nama Acara</label><input type="text" name="event_name" required placeholder="Contoh: Wedding Romeo & Juliet" class="w-full border border-[#e8e1d5] rounded-xl px-4 py-2.5 text-sm bg-[#ffffff]"></div>
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Lokasi</label><input type="text" name="event_location" required placeholder="Nama Gedung / Hotel" class="w-full border border-[#e8e1d5] rounded-xl px-4 py-2.5 text-sm bg-[#ffffff]"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Label / Subtitle <span class="font-normal normal-case text-gray-400">(contoh: The Wedding Of)</span></label><input type="text" name="event_subtitle" placeholder="The Wedding Of" value="The Wedding Of" class="w-full border border-[#e8e1d5] rounded-xl px-4 py-2.5 text-sm bg-[#ffffff]"></div>
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Tanggal</label><input type="date" name="event_date" required class="w-full border border-[#e8e1d5] rounded-xl px-4 py-2.5 text-sm bg-[#ffffff]"></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-4">
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Deskripsi (Opsional)</label><input type="text" name="deskripsi_acara" class="w-full border border-[#e8e1d5] rounded-xl px-4 py-2.5 text-sm bg-[#ffffff]" placeholder="Contoh: Mohon doa restu..."></div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Logo (Opsional)</label><input type="file" name="event_logo" class="block w-full text-xs text-[#8d6e63] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#fffbf2] file:text-[#87714c] hover:file:bg-[#f3e9d8] border border-[#e8e1d5] rounded-xl"></div>
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Foto Mempelai</label><input type="file" name="event_photo" class="block w-full text-xs text-[#8d6e63] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#fbe9e7] file:text-[#8d6e63] hover:file:bg-[#ffccbc] border border-[#e8e1d5] rounded-xl"></div>
                        </div>
                        <button type="submit" name="tambah_event" class="w-full bg-[#87714c] hover:bg-[#b08d55] text-white font-bold py-3 rounded-xl text-sm transition shadow-lg shadow-[#3d2b1f]/30">Simpan Event</button>
                    </form>

                    <div class="bg-white rounded-xl shadow-sm border border-[#e8e1d5] overflow-hidden">
                        <div class="p-5 bg-[#fffbf2] border-b border-[#e8e1d5] font-bold text-[#000000] flex justify-between items-center text-sm font-serif">
                            <span>Daftar Acara Saya</span>
                            <span class="text-xs text-[#87714c] bg-white px-3 py-1 rounded-full border border-[#EAB676]">Total: <?= mysqli_num_rows($all_events) ?></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm min-w-[600px]">
                                <thead class="bg-[#1a0f0d] text-white uppercase text-[10px] font-bold tracking-wider">
                                    <tr>
                                        <th class="px-6 py-4 text-white">Info Acara</th>
                                        <th class="px-6 py-4 text-center text-white">Status</th>
                                        <th class="px-6 py-4 text-center text-white">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#e8e1d5]">
                                    <?php 
                                    if(mysqli_num_rows($all_events) > 0) {
                                        mysqli_data_seek($all_events, 0);
                                        while($ev = mysqli_fetch_assoc($all_events)): 
                                            // Prep data for JS
                                            $ev_logo = !empty($ev['event_logo']) ? 'assets/'.$ev['event_logo'] : '';
                                            $ev_photo= !empty($ev['event_photo']) ? 'assets/'.$ev['event_photo'] : '';
                                            $ev_bg   = !empty($ev['bg_image']) ? 'assets/'.$ev['bg_image'] : '';
                                            $ev_frame= !empty($ev['frame_img']) ? 'assets/'.$ev['frame_img'] : '';
                                        ?>
                                        <tr class="<?= $ev['status'] == 'active' ? 'bg-[#fffbf2]' : '' ?> hover:bg-[#ffffff] transition">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-4">
                                                    <?php if(!empty($ev['event_logo'])): ?>
                                                        <img src="assets/<?= $ev['event_logo'] ?>" class="w-12 h-12 object-contain bg-white rounded-xl p-1 border border-[#e8e1d5]">
                                                    <?php else: ?>
                                                        <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center font-bold text-xs border border-[#e8e1d5]">GB</div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="font-bold text-[#000000] text-base font-serif"><?= $ev['event_name'] ?></div>
                                                        <div class="text-[11px] text-[#8d6e63] mt-1 flex flex-col sm:flex-row gap-1 sm:gap-3">
                                                            <span><i class="fas fa-calendar-alt text-[#87714c] mr-1"></i> <?= date('d M Y', strtotime($ev['event_date'])) ?></span>
                                                            <span class="hidden sm:inline text-[#e8e1d5]">|</span>
                                                            <span><i class="fas fa-map-marker-alt text-[#87714c] mr-1"></i> <?= $ev['event_location'] ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <?php if($ev['status'] == 'active'): ?>
                                                    <div class="flex flex-col items-center gap-1">
                                                        <span class="bg-gradient-to-r from-[#87714c] to-[#b08d55] text-white px-4 py-1 rounded-full text-[10px] font-bold shadow-md">AKTIF</span>
                                                        <button onclick="submitAction('nonaktifkan', <?= $ev['id'] ?>)" class="text-[10px] text-red-400 hover:text-red-600 hover:underline">Non-aktifkan?</button>
                                                    </div>
                                                <?php else: ?>
                                                    <button onclick="submitAction('aktifkan', <?= $ev['id'] ?>)" class="text-[#8d6e63] hover:text-[#87714c] hover:bg-[#fffbf2] border border-[#d7ccc8] hover:border-[#EAB676] px-4 py-1.5 rounded-full text-[10px] font-bold transition block w-max mx-auto shadow-sm">
                                                        Aktifkan
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="flex justify-center items-center gap-2">
                                                    <?php 
                                                    $js_data = json_encode([
                                                        $ev['id'],
                                                        $ev['event_name'],
                                                        $ev['event_date'],
                                                        $ev['event_location'],
                                                        $ev['deskripsi_acara'] ?? ''
                                                    ], JSON_HEX_QUOT | JSON_HEX_APOS);
                                                    ?>
                                                    <button onclick='openEditEvent(<?= htmlspecialchars($js_data, ENT_QUOTES) ?>)' class="text-[#8d6e63] hover:text-[#87714c] bg-[#fffbf2] hover:bg-[#ffffff] p-2 rounded-xl transition border border-[#efebe9]" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <button onclick="openModalSettings('<?= $ev['id'] ?>', '<?= $ev['bg_mode'] ?? 'default' ?>', '<?= $ev['bg_youtube'] ?>', '<?= $ev['show_frame'] ?? 0 ?>', '<?= $ev_bg ?>', '<?= $ev_frame ?>', '<?= $ev_logo ?>', '<?= $ev_photo ?>')" class="text-[#87714c] hover:text-[#b08d55] bg-[#fff8e1] hover:bg-[#fff3e0] p-2 rounded-xl transition border border-[#ffe0b2]" title="Tampilan"><i class="fas fa-paint-brush"></i></button>
                                                    
                                                    <button onclick="submitHapusEvent(<?= $ev['id'] ?>)" class="text-red-400 hover:text-red-600 bg-red-50 hover:bg-red-100 p-2 rounded-xl transition border border-red-100"><i class="fas fa-trash-alt"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; } else { echo '<tr><td colspan="3" class="p-8 text-center text-gray-400 text-sm italic">Belum ada acara. Silakan buat baru.</td></tr>'; } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#e8e1d5]">
                        <h3 class="font-bold text-[#000000] mb-4 flex items-center gap-2 text-sm font-serif"><i class="fas fa-tags text-[#87714c]"></i> Kategori Tamu</h3>
                        <form action="" method="POST" class="flex gap-2 mb-4">
                            <?= csrf_field() ?>
                            <input type="text" name="nama_kategori" class="flex-1 border border-[#e8e1d5] rounded-xl px-3 py-2 text-xs bg-[#ffffff]" placeholder="Kategori Baru" required>
                            <button type="submit" name="tambah_kategori" class="bg-[#87714c] text-white px-3 py-2 rounded-xl text-xs hover:bg-[#b08d55]"><i class="fas fa-plus"></i></button>
                        </form>
                        <div class="max-h-60 overflow-y-auto border border-[#f3e9d8] rounded-xl custom-scrollbar">
                            <table class="w-full text-xs">
                                <?php while($kat = mysqli_fetch_assoc($kategori_data)): ?>
                                <tr class="border-b border-[#f3e9d8] last:border-0 hover:bg-[#fffbf2]">
                                    <td class="p-3 font-semibold text-[#8d6e63]"><?= $kat['nama_kategori'] ?></td>
                                    <td class="p-3 text-right"><button onclick="submitHapusKategori(<?= $kat['id'] ?>)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash-alt"></i></button></td>
                                </tr>
                                <?php endwhile; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="modalEditEvent" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeEditEvent()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_event_edit" id="edit_id_event">
                    <input type="hidden" name="simpan_edit_event" value="1">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-bold text-[#000000] mb-4 border-b border-[#e8e1d5] pb-2 font-serif">Edit Detail Acara</h3>
                        <div class="grid grid-cols-1 gap-4">
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Nama Acara</label><input type="text" name="edit_event_name" id="edit_name" required class="w-full border border-[#e8e1d5] rounded-xl px-3 py-2 text-sm bg-[#ffffff]"></div>
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Label / Subtitle <span class="font-normal normal-case text-gray-400">(misal: The Wedding Of)</span></label><input type="text" name="edit_event_subtitle" id="edit_subtitle" class="w-full border border-[#e8e1d5] rounded-xl px-3 py-2 text-sm bg-[#ffffff]" placeholder="The Wedding Of"></div>
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Tanggal</label><input type="date" name="edit_event_date" id="edit_date" required class="w-full border border-[#e8e1d5] rounded-xl px-3 py-2 text-sm bg-[#ffffff]"></div>
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Lokasi</label><input type="text" name="edit_event_location" id="edit_loc" required class="w-full border border-[#e8e1d5] rounded-xl px-3 py-2 text-sm bg-[#ffffff]"></div>
                            <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Deskripsi</label><textarea name="edit_deskripsi_acara" id="edit_desc" rows="3" class="w-full border border-[#e8e1d5] rounded-xl px-3 py-2 text-sm bg-[#ffffff]"></textarea></div>
                        </div>
                    </div>
                    <div class="bg-[#ffffff] px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-[#e8e1d5]">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-[#87714c] text-base font-medium text-white hover:bg-[#b08d55] sm:ml-3 sm:w-auto sm:text-sm">Simpan Perubahan</button>
                        <button type="button" onclick="closeEditEvent()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-[#e8e1d5] shadow-sm px-4 py-2 bg-white text-base font-medium text-[#8d6e63] hover:bg-[#ffffff] sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalSettings" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeModalSettings()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="" method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_event" id="modal_id_event">
                    <input type="hidden" name="update_tampilan_event" value="1">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-bold text-[#000000] mb-4 border-b border-[#e8e1d5] pb-2 font-serif">Atur Tampilan & Foto</h3>
                        <div class="space-y-4">
                            
                            <div class="bg-[#fbe9e7] p-3 rounded-xl border border-[#ffccbc]">
                                <label class="block text-xs font-bold text-pink-700 mb-1">Ganti Foto Mempelai</label>
                                <div id="current_photo_preview" class="mb-2 hidden flex justify-center">
                                    <img src="" id="img_photo_preview" class="h-24 w-24 rounded-full border-4 border-white shadow-sm object-cover">
                                </div>
                                <input type="file" name="event_photo_file" class="block w-full text-xs text-[#8d6e63] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-white file:text-pink-700">
                                <p class="text-[9px] text-gray-500 mt-1">Muncul di Home Mempelai (Lingkaran).</p>
                            </div>

                            <div class="bg-[#e3f2fd] p-3 rounded-xl border border-[#bbdefb]">
                                <label class="block text-xs font-bold text-blue-700 mb-1">Ganti Logo Acara</label>
                                <div id="current_logo_preview" class="mb-2 hidden">
                                    <img src="" id="img_logo_preview" class="h-10 w-auto rounded border p-1 bg-white object-contain">
                                </div>
                                <input type="file" name="logo_event_file" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-white file:text-blue-700">
                            </div>

                            <div class="border-t border-[#e8e1d5] pt-3 mt-2">
                                <label class="block text-xs font-bold text-[#87714c] uppercase mb-2">Mode Background</label>
                                <select name="bg_mode" id="modal_bg_mode" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm bg-gray-50">
                                    <option value="default">Ikuti Universal (Default)</option>
                                    <option value="image">Gambar Khusus (Upload)</option>
                                    <option value="video">Video YouTube</option>
                                </select>
                            </div>
                            
                            <div id="field_youtube" class="hidden">
                                <label class="block text-xs font-bold text-[#000000] mb-1">Link YouTube</label>
                                <input type="text" name="bg_youtube" id="modal_bg_youtube" class="w-full border border-[#e8e1d5] rounded-xl px-3 py-2 text-sm bg-[#ffffff]" placeholder="https://youtube.com/...">
                            </div>
                            
                            <div id="field_image" class="hidden">
                                <label class="block text-xs font-bold text-[#000000] mb-1">Upload Background Baru</label>
                                <div id="current_bg_preview" class="mb-2 hidden">
                                    <img src="" id="img_bg_preview" class="h-16 w-full rounded border p-1 object-cover">
                                </div>
                                <input type="file" name="bg_image_file" class="block w-full text-xs text-[#8d6e63] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-[#fffbf2] file:text-[#87714c]">
                            </div>

                            <div class="border-t border-[#e8e1d5] pt-3">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-[#87714c] uppercase">Frame / Bingkai</label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="show_frame" id="modal_show_frame" value="1" class="rounded text-[#87714c] focus:ring-[#EAB676]">
                                        <span class="text-xs text-[#000000]">Tampilkan?</span>
                                    </label>
                                </div>
                                <div id="current_frame_preview" class="mb-2 hidden">
                                    <img src="" id="img_frame_preview" class="h-16 w-auto rounded border p-1 bg-white object-contain">
                                </div>
                                <input type="file" name="frame_image_file" class="block w-full text-xs text-[#8d6e63] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-[#fce4ec] file:text-[#880e4f]">
                            </div>
                        </div>
                    </div>
                    <div class="bg-[#ffffff] px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-[#e8e1d5]">
                        <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-[#87714c] text-base font-medium text-white hover:bg-[#b08d55] sm:ml-3 sm:w-auto sm:text-sm">Simpan</button>
                        <button type="button" onclick="closeModalSettings()" class="mt-3 w-full inline-flex justify-center rounded-xl border border-[#e8e1d5] shadow-sm px-4 py-2 bg-white text-base font-medium text-[#8d6e63] hover:bg-[#ffffff] sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('aside');
            if(sidebar) {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('fixed'); sidebar.classList.toggle('inset-0'); sidebar.classList.toggle('z-50'); sidebar.classList.toggle('bg-white'); sidebar.classList.toggle('w-64');
            }
        }

        const modalSettings = document.getElementById('modalSettings');
        const modalEdit = document.getElementById('modalEditEvent');
        const selectMode = document.getElementById('modal_bg_mode');
        const fieldYt = document.getElementById('field_youtube');
        const fieldImg = document.getElementById('field_image');

        function submitAction(actionStr, id) {
            let f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<?= csrf_field() ?><input type="hidden" name="action" value="'+actionStr+'"><input type="hidden" name="id" value="'+id+'">';
            document.body.appendChild(f); f.submit();
        }
        function submitHapusEvent(id) {
            Swal.fire({
                title: 'Hapus Acara?',
                text: "Semua data tamu pada acara ini akan hilang secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#87714c',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                background: '#ffffff',
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'rounded-xl',
                    cancelButton: 'rounded-xl'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    let f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<?= csrf_field() ?><input type="hidden" name="hapus_event" value="'+id+'">';
                    document.body.appendChild(f); f.submit();
                }
            });
        }
        function submitHapusKategori(id) {
            Swal.fire({
                title: 'Hapus Kategori?',
                text: "Kategori ini akan dihapus dari daftar.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#87714c',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    let f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<?= csrf_field() ?><input type="hidden" name="hapus_kategori" value="'+id+'">';
                    document.body.appendChild(f); f.submit();
                }
            });
        }

        const prevBgDiv = document.getElementById('current_bg_preview');
        const prevBgImg = document.getElementById('img_bg_preview');
        const prevFrameDiv = document.getElementById('current_frame_preview');
        const prevFrameImg = document.getElementById('img_frame_preview');
        const prevLogoDiv = document.getElementById('current_logo_preview');
        const prevLogoImg = document.getElementById('img_logo_preview');
        const prevPhotoDiv = document.getElementById('current_photo_preview');
        const prevPhotoImg = document.getElementById('img_photo_preview');

        function openModalSettings(id, mode, youtube, showFrame, bgImg, frameImg, logoImg, photoImg) {
            document.getElementById('modal_id_event').value = id;
            selectMode.value = (mode === '' || mode === null) ? 'default' : mode;
            document.getElementById('modal_bg_youtube').value = youtube;
            document.getElementById('modal_show_frame').checked = (showFrame == 1);
            
            if(bgImg) { prevBgDiv.classList.remove('hidden'); prevBgImg.src = bgImg; } else { prevBgDiv.classList.add('hidden'); }
            if(frameImg) { prevFrameDiv.classList.remove('hidden'); prevFrameImg.src = frameImg; } else { prevFrameDiv.classList.add('hidden'); }
            if(logoImg) { prevLogoDiv.classList.remove('hidden'); prevLogoImg.src = logoImg; } else { prevLogoDiv.classList.add('hidden'); }
            if(photoImg) { prevPhotoDiv.classList.remove('hidden'); prevPhotoImg.src = photoImg; } else { prevPhotoDiv.classList.add('hidden'); }

            toggleFields();
            modalSettings.classList.remove('hidden');
            document.body.classList.add('modal-active');
        }

        function closeModalSettings() {
            modalSettings.classList.add('hidden');
            document.body.classList.remove('modal-active');
        }

        function openEditEvent(data) {
            let [id, name, subtitle, date, location, desc] = data;
            document.getElementById('edit_id_event').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_subtitle').value = subtitle;
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_loc').value = location;
            document.getElementById('edit_desc').value = desc;
            modalEdit.classList.remove('hidden');
            document.body.classList.add('modal-active');
        }

        function closeEditEvent() {
            modalEdit.classList.add('hidden');
            document.body.classList.remove('modal-active');
        }

        function toggleFields() {
            const val = selectMode.value;
            fieldYt.classList.add('hidden');
            fieldImg.classList.add('hidden');
            if(val === 'video') fieldYt.classList.remove('hidden');
            else if (val === 'image') fieldImg.classList.remove('hidden');
        }

        selectMode.addEventListener('change', toggleFields);
    </script>
        <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
        </footer>
    </main>
</body></html>