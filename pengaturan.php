<?php
require 'koneksi.php';
require_admin(); // Hanya admin yang boleh akses halaman ini
check_csrf(); // Validasi CSRF untuk setiap POST request

// ==========================================
// HANDLE FORM POST (LOGIKA SIMPAN)
// ==========================================

// 1. SIMPAN TAB: LOGIN & IDENTITAS
if (isset($_POST['save_login'])) {
    $app_name   = esc($_POST['app_name']);
    $logo_text  = esc($_POST['logo_text']);
    $copyright  = esc($_POST['copyright']);
    $language   = esc($_POST['language']);
    
    $hero_title = esc($_POST['hero_title']);
    $hero_desc  = esc($_POST['hero_desc']);
    $btn_text   = esc($_POST['btn_text']);
    $btn_link   = esc($_POST['btn_link']);
    $wa_support = esc($_POST['wa_support']);

    // Upload Logo Dashboard (SECURED)
    if (!empty($_FILES['logo_file']['name'])) {
        $uploaded = secure_upload($_FILES['logo_file'], 'logo');
        if ($uploaded) {
            mysqli_query($koneksi, "UPDATE pengaturan SET logo_dashboard='$uploaded' WHERE id=1");
        } else {
            $pesan_error = "Gagal upload logo. Periksa tipe file atau ukuran (Maks 2MB).";
        }
    }

    // Upload Favicon (SECURED)
    if (!empty($_FILES['favicon_file']['name'])) {
        $uploaded = secure_upload($_FILES['favicon_file'], 'fav');
        if ($uploaded) {
            mysqli_query($koneksi, "UPDATE pengaturan SET favicon='$uploaded' WHERE id=1");
        } else {
            $pesan_error = "Gagal upload favicon. Periksa tipe file atau ukuran (Maks 2MB).";
        }
    }

    $query = "UPDATE pengaturan SET 
              app_name='$app_name', logo_text='$logo_text', copyright='$copyright', language='$language',
              hero_title='$hero_title', hero_desc='$hero_desc', btn_text='$btn_text', btn_link='$btn_link',
              wa_support='$wa_support'
              WHERE id=1";

    if(mysqli_query($koneksi, $query)) {
        $pesan = "Identitas & Teks Login berhasil disimpan!";
        $tab_active = 'login';
    } else {
        $pesan_error = "Error: " . mysqli_error($koneksi);
    }
}

// 2. KELOLA SOSIAL MEDIA (TAMBAH, HAPUS, UPDATE)
if (isset($_POST['add_sosmed'])) {
    $nama = mysqli_real_escape_string($koneksi, $_POST['platform_name']);
    $icon = mysqli_real_escape_string($koneksi, $_POST['icon_url']);
    $link = mysqli_real_escape_string($koneksi, $_POST['link_url']);
    
    // Create table if not exists
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS social_media (id INT AUTO_INCREMENT PRIMARY KEY, platform_name VARCHAR(50), icon_url VARCHAR(255), link_url VARCHAR(255))");
    
    mysqli_query($koneksi, "INSERT INTO social_media (platform_name, icon_url, link_url) VALUES ('$nama', '$icon', '$link')");
    $pesan = "Sosial Media berhasil ditambahkan!";
    $tab_active = 'login';
}

if (isset($_POST['update_sosmed'])) {
    $id_upd = (int)$_POST['id_sosmed'];
    $nama   = mysqli_real_escape_string($koneksi, $_POST['platform_name']);
    $icon   = mysqli_real_escape_string($koneksi, $_POST['icon_url']);
    $link   = mysqli_real_escape_string($koneksi, $_POST['link_url']);
    
    mysqli_query($koneksi, "UPDATE social_media SET platform_name='$nama', icon_url='$icon', link_url='$link' WHERE id=$id_upd");
    $pesan = "Data sosial media berhasil diperbarui!";
    $tab_active = 'login';
}

if (isset($_POST['del_sosmed'])) {
    $id_del = (int)$_POST['id_sosmed'];
    mysqli_query($koneksi, "DELETE FROM social_media WHERE id=$id_del");
    header("Location: pengaturan.php"); exit;
}

// 3. SIMPAN TAB: DISPLAY - BACKGROUND
if (isset($_POST['save_display_bg'])) {
    $timezone    = esc($_POST['timezone']);
    $speed_timer = (int) $_POST['speed_timer'];
    $show_frame  = isset($_POST['show_frame']) ? 1 : 0;
    $mode        = esc($_POST['bg_mode']);
    $color_start = esc($_POST['color_start']);
    $color_end   = esc($_POST['color_end']);
    $color_text  = esc($_POST['color_text']);
    $url         = esc($_POST['bg_youtube_url']);

    // Upload Frame Display (SECURED)
    if (!empty($_FILES['frame_file']['name'])) {
        $uploaded = secure_upload($_FILES['frame_file'], 'frame');
        if ($uploaded) {
            mysqli_query($koneksi, "UPDATE pengaturan SET frame_img='$uploaded' WHERE id=1");
        } else {
            $pesan_error = "Gagal upload frame. Periksa tipe file atau ukuran (Maks 2MB).";
        }
    }

    $query = "UPDATE pengaturan SET 
              timezone='$timezone', speed_timer='$speed_timer', show_frame='$show_frame',
              bg_mode='$mode', bg_youtube_url='$url',
              color_start='$color_start', color_end='$color_end', color_text='$color_text'
              WHERE id=1";
              
    if(mysqli_query($koneksi, $query)) {
        $pesan = "Konfigurasi Display & Background berhasil disimpan!";
        $tab_active = 'display';
    }
}

// 4. SIMPAN TAB: DISPLAY - STYLE
if (isset($_POST['save_display_style'])) {
    $wt  = mysqli_real_escape_string($koneksi, $_POST['welcome_text']);
    $wbg = $_POST['welcome_bg_color'];
    $wfc = $_POST['welcome_font_color'];
    $wf  = $_POST['welcome_font'];
    
    $s_acara = $_POST['size_acara']; $s_welcome = $_POST['size_welcome']; $s_tamu = $_POST['size_tamu'];
    $s_tanggal = $_POST['size_tanggal']; $s_lokasi = $_POST['size_lokasi']; $s_waktu = $_POST['size_waktu'];

    $sh_acara = isset($_POST['show_acara']) ? 1 : 0; $sh_kat = isset($_POST['show_kategori']) ? 1 : 0;
    $sh_tgl = isset($_POST['show_tanggal']) ? 1 : 0; $sh_lok = isset($_POST['show_lokasi']) ? 1 : 0;
    $sh_wkt = isset($_POST['show_waktu']) ? 1 : 0;
    $sh_run = isset($_POST['show_running_text']) ? 1 : 0;

    $dw = $_POST['delay_welcome']; $dg = $_POST['delay_gathering'];
    $lo = isset($_POST['looping_overlay']) ? 1 : 0;
    $lt = $_POST['looping_overlay_timer']; $ao = $_POST['animasi_out']; $ad = $_POST['animasi_duration'];

    $query = "UPDATE pengaturan SET 
        welcome_text='$wt', welcome_bg_color='$wbg', welcome_font_color='$wfc', welcome_font='$wf',
        size_acara='$s_acara', size_welcome='$s_welcome', size_tamu='$s_tamu',
        size_tanggal='$s_tanggal', size_lokasi='$s_lokasi', size_waktu='$s_waktu',
        show_acara='$sh_acara', show_kategori='$sh_kat', show_tanggal='$sh_tgl', 
        show_lokasi='$sh_lok', show_waktu='$sh_wkt', show_running_text='$sh_run',
        delay_welcome='$dw', delay_gathering='$dg', looping_overlay='$lo', 
        looping_overlay_timer='$lt', animasi_out='$ao', animasi_duration='$ad'
        WHERE id=1";

    if(mysqli_query($koneksi, $query)) {
        $pesan = "Style Teks & Animasi Display berhasil disimpan!";
        $tab_active = 'display';
    }
}

// AMBIL DATA
$config = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1"));
$logo_src = !empty($config['logo_dashboard']) ? "assets/".$config['logo_dashboard'] : "https://via.placeholder.com/150?text=No+Logo";
$favicon_src = !empty($config['favicon']) ? "assets/".$config['favicon'] : "https://via.placeholder.com/64?text=Fav";
$frame_src = !empty($config['frame_img']) ? "assets/".$config['frame_img'] : "https://via.placeholder.com/300?text=No+Frame";
$q_sosmed = mysqli_query($koneksi, "SELECT * FROM social_media");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - <?= $config['app_name'] ?></title>
    <?php if(!empty($config['favicon'])): ?>
    <link rel="icon" href="assets/<?= $config['favicon'] ?>?v=<?= time() ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; } .font-serif { font-family: 'Playfair Display', serif; }
        .tab-btn.active { border-bottom: 2px solid #87714c; color: #87714c; font-weight: 700; background-color: #fffbf2; }
        .tab-btn { color: #8d6e63; font-weight: 500; } .tab-btn:hover { color: #000000; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="text-[#1a0f0d]" style="background-color:#ffffff; background-image:url('https://www.transparenttextures.com/patterns/cream-paper.png');">

    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <main class="md:ml-64 p-4 lg:p-10 min-h-screen">
        <div class="max-w-6xl mx-auto">
            <!-- Header Section -->
            <div class="mb-5 lg:mb-6 border-b border-[#d1c7b7] pb-3 no-print">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                            <iconify-icon icon="solar:settings-bold-duotone" class="text-2xl"></iconify-icon>
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-[#1a0f0d] font-serif">Pengaturan</h1>
                            <p class="text-[#87714c] mt-1 text-sm">Kelola identitas aplikasi dan tampilan layar sapa.</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if(isset($pesan)): ?>
                <div class="mb-6 bg-[#fffbf2] border-l-4 border-[#87714c] text-[#8d6e63] p-4 rounded shadow-sm flex items-center gap-2 animate-bounce">
                    <i class="fas fa-check-circle text-[#87714c]"></i> <?= $pesan ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-t-2xl shadow-sm border-b border-[#e8e1d5] border-t border-l border-r px-4 lg:px-6 flex gap-2 overflow-x-auto hide-scrollbar">
                <button onclick="switchTab('login')" id="btn-login" class="tab-btn active py-4 px-4 text-sm whitespace-nowrap transition-colors rounded-t-lg"><i class="fas fa-lock mr-2"></i>Login & Identitas</button>
                <button onclick="switchTab('display')" id="btn-display" class="tab-btn py-4 px-4 text-sm whitespace-nowrap transition-colors rounded-t-lg"><i class="fas fa-tv mr-2"></i>Tampilan Display</button>
            </div>

            <div class="bg-white rounded-b-2xl shadow-sm border border-t-0 border-[#e8e1d5] p-6 lg:p-8">

                <div id="tab-login-content" class="block space-y-8">
                    <?php if(isset($pesan_error)): ?>
                        <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i> <?= $pesan_error ?>
                        </div>
                    <?php endif; ?>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <div class="mb-8">
                            <h3 class="font-bold text-[#1a0f0d] mb-4 pb-2 border-b border-[#e8e1d5] flex items-center gap-2 font-serif"><span class="bg-[#87714c] text-white w-6 h-6 rounded flex items-center justify-center text-xs">1</span> Identitas Global</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                                <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Nama Aplikasi</label><input type="text" name="app_name" value="<?= $config['app_name'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"></div>
                                <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Logo Teks (Sidebar)</label><input type="text" name="logo_text" value="<?= $config['logo_text'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"></div>
                                <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Copyright Footer</label><input type="text" name="copyright" value="<?= $config['copyright'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"></div>
                                <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Bahasa Sistem</label><select name="language" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-white"><option value="id" <?= $config['language']=='id'?'selected':'' ?>>Indonesia</option><option value="en" <?= $config['language']=='en'?'selected':'' ?>>English</option></select></div>
                                <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">WhatsApp Support (Tanpa +)</label><input type="text" name="wa_support" value="<?= $config['wa_support'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]" placeholder="Contoh: 628123456789"></div>
                            </div>
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 bg-[#fffbf2] rounded-lg border border-dashed border-[#87714c] flex items-center gap-4">
                                    <img src="<?= $logo_src ?>" class="h-12 w-12 object-contain bg-white rounded p-1 shadow border border-[#e8e1d5]">
                                    <div class="flex-1"><label class="block text-xs font-bold text-[#8d6e63] mb-1">Logo Dashboard</label><input type="file" name="logo_file" class="block w-full text-xs text-[#8d6e63] file:mr-2 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:bg-[#87714c] file:text-white hover:file:bg-[#b08d55] cursor-pointer"></div>
                                </div>
                                <div class="p-4 bg-[#f8f9fa] rounded-lg border border-dashed border-gray-300 flex items-center gap-4">
                                    <img src="<?= $favicon_src ?>" class="h-10 w-10 object-contain bg-white rounded p-1 shadow border border-gray-200">
                                    <div class="flex-1"><label class="block text-xs font-bold text-gray-500 mb-1">Favicon (.ico, .png)</label><input type="file" name="favicon_file" class="block w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 cursor-pointer"></div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="font-bold text-[#1a0f0d] mb-4 pb-2 border-b border-[#e8e1d5] flex items-center gap-2 font-serif"><span class="bg-[#87714c] text-white w-6 h-6 rounded flex items-center justify-center text-xs">2</span> Teks Halaman Login</h3>
                            <div class="space-y-4">
                                <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Judul Utama</label><input type="text" name="hero_title" value="<?= $config['hero_title'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"></div>
                                <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Deskripsi Singkat</label><textarea name="hero_desc" rows="3" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"><?= $config['hero_desc'] ?></textarea></div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Teks Tombol</label><input type="text" name="btn_text" value="<?= $config['btn_text'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"></div>
                                    <div><label class="block text-xs font-bold text-[#87714c] uppercase mb-1">Link Tombol</label><input type="text" name="btn_link" value="<?= $config['btn_link'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 pt-4 border-t border-[#e8e1d5]"><button type="submit" name="save_login" class="w-full bg-[#87714c] hover:bg-[#b08d55] text-white font-bold py-3 px-8 rounded-lg shadow-lg shadow-[#87714c]/30 transition transform hover:-translate-y-0.5"><i class="fas fa-save mr-2"></i> Simpan Pengaturan Login</button></div>
                    </form>

                    <div class="mt-12">
                        <h3 class="font-bold text-[#1a0f0d] mb-4 pb-2 border-b border-[#e8e1d5] flex items-center gap-2 font-serif">
                            <span class="bg-[#87714c] text-white w-6 h-6 rounded flex items-center justify-center text-xs">3</span> Kelola Sosial Media
                        </h3>
                        <div class="bg-[#ffffff] p-5 rounded-lg border border-[#e8e1d5]">
                            <form action="" method="POST" class="flex flex-col lg:flex-row gap-3 mb-6">
                                <?= csrf_field() ?>
                                <input type="text" name="platform_name" placeholder="Nama (cth: Instagram)" class="flex-1 border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-white" required>
                                <input type="text" name="icon_url" placeholder="Link Icon (https://...)" class="flex-1 border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-white" required>
                                <input type="text" name="link_url" placeholder="Link Profil (https://...)" class="flex-1 border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-white" required>
                                <button type="submit" name="add_sosmed" class="bg-[#000000] text-white px-5 py-2 rounded-lg font-bold text-sm hover:bg-[#3e2723] h-[38px]">+ Tambah</button>
                            </form>

                            <div class="overflow-x-auto bg-white rounded-lg border border-[#e8e1d5]">
                                <table class="w-full text-left text-sm">
                                    <thead class="bg-[#f3e9d8] text-[#1a0f0d] uppercase text-xs font-bold">
                                        <tr><th class="p-3 w-16 text-center">Icon</th><th class="p-3 w-1/4">Platform</th><th class="p-3 w-1/3">Link</th><th class="p-3 text-center w-32">Aksi</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#f3e9d8]">
                                        <?php if(mysqli_num_rows($q_sosmed) > 0): 
                                            mysqli_data_seek($q_sosmed, 0); 
                                            while($s = mysqli_fetch_assoc($q_sosmed)): ?>
                                            <tr class="hover:bg-[#fffbf2]" id="row-view-<?= $s['id'] ?>">
                                                <td class="p-3 text-center"><img src="<?= $s['icon_url'] ?>" class="w-6 h-6 object-contain mx-auto" onerror="this.src='https://via.placeholder.com/24'"></td>
                                                <td class="p-3 font-bold text-[#1a0f0d]"><?= $s['platform_name'] ?></td>
                                                <td class="p-3 text-gray-500 text-xs truncate max-w-[200px]"><a href="<?= $s['link_url'] ?>" target="_blank" class="hover:text-[#87714c]"><?= substr($s['link_url'], 0, 30) ?>...</a></td>
                                                <td class="p-3 text-center">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button onclick="toggleEdit('<?= $s['id'] ?>')" class="text-[#87714c] hover:text-[#b08d55] bg-[#fff8e1] px-2 py-1 rounded text-xs font-bold border border-[#ffe0b2]">Edit</button>
                                                        <form method="POST" action="" onsubmit="return confirm('Hapus sosial media ini?')" style="display:inline;">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="id_sosmed" value="<?= $s['id'] ?>">
                                                            <input type="hidden" name="del_sosmed" value="1">
                                                            <button type="submit" class="text-red-500 hover:text-red-700 bg-red-50 px-2 py-1 rounded text-xs font-bold border border-red-100">Hapus</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr class="bg-[#fffbf2] hidden" id="row-edit-<?= $s['id'] ?>">
                                                <form action="" method="POST">
                                                    <input type="hidden" name="id_sosmed" value="<?= $s['id'] ?>">
                                                    <td class="p-3 text-center align-top pt-4"><i class="fas fa-edit text-[#87714c]"></i></td>
                                                    <td class="p-3 align-top">
                                                        <input type="text" name="platform_name" value="<?= $s['platform_name'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-xs mb-1" placeholder="Nama">
                                                        <input type="text" name="icon_url" value="<?= $s['icon_url'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-xs text-gray-500" placeholder="Link Icon">
                                                    </td>
                                                    <td class="p-3 align-top"><input type="text" name="link_url" value="<?= $s['link_url'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-xs" placeholder="Link Tujuan"></td>
                                                    <td class="p-3 text-center align-middle">
                                                        <div class="flex flex-col gap-2">
                                                            <button type="submit" name="update_sosmed" class="text-white bg-green-500 hover:bg-green-600 px-2 py-1 rounded text-xs font-bold">Simpan</button>
                                                            <button type="button" onclick="toggleEdit('<?= $s['id'] ?>')" class="text-gray-600 bg-gray-200 hover:bg-gray-300 px-2 py-1 rounded text-xs font-bold">Batal</button>
                                                        </div>
                                                    </td>
                                                </form>
                                            </tr>
                                            <?php endwhile; else: ?>
                                            <tr><td colspan="4" class="p-4 text-center text-gray-400 italic">Belum ada sosial media.</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="tab-display-content" class="hidden space-y-10">
                    <form action="" method="POST" enctype="multipart/form-data" class="bg-[#fffbf2] p-6 rounded-lg border border-[#e8e1d5]">
                        <?= csrf_field() ?>
                        <h3 class="font-bold text-[#1a0f0d] mb-4 flex items-center gap-2 font-serif"><i class="fas fa-clock text-[#87714c]"></i> Waktu, Frame & Background</h3>
                        <div class="bg-white p-4 rounded-lg border border-[#e8e1d5] mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div><label class="block text-xs font-bold text-[#8d6e63] mb-1">Timezone</label><select name="timezone" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"><option value="Asia/Jakarta" <?= $config['timezone']=='Asia/Jakarta'?'selected':'' ?>>WIB</option><option value="Asia/Makassar" <?= $config['timezone']=='Asia/Makassar'?'selected':'' ?>>WITA</option><option value="Asia/Jayapura" <?= $config['timezone']=='Asia/Jayapura'?'selected':'' ?>>WIT</option></select></div>
                                <div><label class="block text-xs font-bold text-[#8d6e63] mb-1">Refresh (Detik)</label><input type="number" name="speed_timer" value="<?= $config['speed_timer'] ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-[#ffffff]"></div>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-[#8d6e63] mb-2">Mode Background</label>
                                <div class="flex gap-4 mb-4">
                                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 rounded border border-[#e8e1d5]"><input type="radio" name="bg_mode" value="video" class="text-[#87714c]" <?= ($config['bg_mode']??'')=='video'?'checked':'' ?>><span class="text-sm">Video</span></label>
                                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 rounded border border-[#e8e1d5]"><input type="radio" name="bg_mode" value="image" class="text-[#87714c]" <?= ($config['bg_mode']??'')=='image'?'checked':'' ?>><span class="text-sm">Image</span></label>
                                </div>
                                <label class="block text-xs font-bold text-[#8d6e63] mb-1">Link Youtube</label><input type="text" name="bg_youtube_url" value="<?= $config['bg_youtube_url']??'' ?>" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-white">
                                <div class="mt-4"><label class="block text-xs font-bold text-[#8d6e63] mb-2">Overlay</label><div class="flex gap-2"><input type="color" name="color_start" value="<?= $config['color_start']??'#000000' ?>" class="h-8 w-full"><input type="color" name="color_end" value="<?= $config['color_end']??'#000000' ?>" class="h-8 w-full"><input type="color" name="color_text" value="<?= $config['color_text']??'#FFFFFF' ?>" class="h-8 w-full"></div></div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-[#8d6e63] mb-2">Upload Frame</label>
                                <div class="bg-white p-3 rounded-lg border border-[#e8e1d5] text-center"><?php if(!empty($config['frame_img'])): ?><img src="<?= $frame_src??'' ?>" class="h-24 w-auto object-contain mx-auto mb-2 border rounded"><?php endif; ?><input type="file" name="frame_file" class="block w-full text-xs file:bg-[#87714c] file:text-white file:border-0 file:rounded-full file:px-4 file:py-2"></div>
                                <div class="mt-3"><label class="flex items-center gap-2"><input type="checkbox" name="show_frame" value="1" class="text-[#87714c]" <?= ($config['show_frame']??0)?'checked':'' ?>><span class="text-xs font-bold text-[#8d6e63]">Tampilkan Frame</span></label></div>
                            </div>
                        </div>
                        <div class="mt-4 text-right border-t border-[#e8e1d5] pt-3"><button type="submit" name="save_display_bg" class="bg-[#87714c] hover:bg-[#b08d55] text-white font-bold py-2.5 px-6 rounded-lg text-sm shadow-md">Simpan Konfigurasi</button></div>
                    </form>

                    <form action="" method="POST">
                        <?= csrf_field() ?>
                        <h3 class="font-bold text-[#1a0f0d] mb-4 flex items-center gap-2 font-serif"><i class="fas fa-font text-[#87714c]"></i> Style Teks</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <div><label class="block text-xs font-bold text-[#8d6e63] mb-1">Welcome Text</label><textarea name="welcome_text" rows="2" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-white"><?= $config['welcome_text'] ?></textarea></div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div><label class="block text-xs font-bold text-[#8d6e63] mb-1">Font</label><select name="welcome_font" class="w-full border border-[#e8e1d5] rounded-lg px-3 py-2 text-sm bg-white"><option value="Poppins" <?= $config['welcome_font']=='Poppins'?'selected':'' ?>>Poppins</option><option value="Playfair Display" <?= $config['welcome_font']=='Playfair Display'?'selected':'' ?>>Playfair</option></select></div>
                                    <div><label class="block text-xs font-bold text-[#8d6e63] mb-1">Warna</label><input type="color" name="welcome_font_color" value="<?= $config['welcome_font_color'] ?>" class="w-full h-[38px] border border-[#e8e1d5] rounded-lg bg-white p-1"></div>
                                    <input type="hidden" name="welcome_bg_color" value="<?= $config['welcome_bg_color'] ?>">
                                </div>
                                <div class="bg-[#ffffff] p-4 rounded-lg border border-[#e8e1d5]">
                                    <label class="block text-xs font-bold text-[#8d6e63] mb-3 border-b border-[#e8e1d5] pb-1">Ukuran Font</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div><span class="text-[10px] text-gray-400">Acara</span><input type="number" name="size_acara" value="<?= $config['size_acara'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-sm"></div>
                                        <div><span class="text-[10px] text-gray-400">Welcome</span><input type="number" name="size_welcome" value="<?= $config['size_welcome'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-sm"></div>
                                        <div><span class="text-[10px] text-gray-400">Tamu</span><input type="number" name="size_tamu" value="<?= $config['size_tamu'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-sm"></div>
                                        <div><span class="text-[10px] text-gray-400">Tanggal</span><input type="number" name="size_tanggal" value="<?= $config['size_tanggal'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-sm"></div>
                                        <div><span class="text-[10px] text-gray-400">Lokasi</span><input type="number" name="size_lokasi" value="<?= $config['size_lokasi'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-sm"></div>
                                        <div><span class="text-[10px] text-gray-400">Jam</span><input type="number" name="size_waktu" value="<?= $config['size_waktu'] ?>" class="w-full border border-[#e8e1d5] rounded px-2 py-1 text-sm"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-6">
                                <div class="bg-white border border-[#e8e1d5] p-4 rounded-lg shadow-sm">
                                    <label class="block text-xs font-bold text-[#8d6e63] mb-3 border-b border-[#e8e1d5] pb-1">Tampilkan</label>
                                    <div class="space-y-3">
                                        <?php 
                                        $toggles = [
                                            'show_acara'=>'Nama Acara', 
                                            'show_kategori'=>'Kategori', 
                                            'show_tanggal'=>'Tanggal', 
                                            'show_lokasi'=>'Alamat', 
                                            'show_waktu'=>'Jam',
                                            'show_running_text'=>'Running Text'
                                        ]; 
                                        foreach($toggles as $k=>$l): ?>
                                        <div class="flex justify-between items-center"><span class="text-xs text-[#1a0f0d]"><?= $l ?></span><label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" name="<?= $k ?>" value="1" class="sr-only peer" <?= ($config[$k]??0)?'checked':'' ?>><div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-[#87714c] peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div></label></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="bg-[#fff8e1] border border-[#ffe0b2] p-4 rounded-lg">
                                    <label class="block text-xs font-bold text-[#87714c] mb-3 border-b border-[#ffe0b2] pb-1">Animasi</label>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div><span class="text-[10px] text-gray-500">Delay (dtk)</span><input type="number" name="delay_welcome" value="<?= $config['delay_welcome'] ?>" class="w-full border border-[#e8e1d5] rounded text-sm px-2 py-1"></div>
                                        <div><span class="text-[10px] text-gray-500">Durasi (ms)</span><input type="number" name="animasi_duration" value="<?= $config['animasi_duration'] ?>" class="w-full border border-[#e8e1d5] rounded text-sm px-2 py-1"></div>
                                    </div>
                                    <div class="flex gap-2 items-center"><div class="flex-1"><span class="text-[10px] text-gray-500">Out</span><select name="animasi_out" class="w-full border border-[#e8e1d5] rounded text-sm px-2 py-1 bg-white"><option value="Fade" <?= $config['animasi_out']=='Fade'?'selected':'' ?>>Fade</option><option value="Slide Up" <?= $config['animasi_out']=='Slide Up'?'selected':'' ?>>Slide Up</option><option value="Zoom Out" <?= $config['animasi_out']=='Zoom Out'?'selected':'' ?>>Zoom Out</option></select></div><input type="hidden" name="delay_gathering" value="<?= $config['delay_gathering'] ?>"><input type="hidden" name="looping_overlay_timer" value="<?= $config['looping_overlay_timer'] ?>"></div>
                                    <div class="mt-3 flex items-center gap-2"><input type="checkbox" name="looping_overlay" value="1" <?= $config['looping_overlay']?'checked':'' ?> class="rounded text-[#87714c]"><span class="text-xs text-[#8d6e63]">Looping</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 pt-4 border-t border-[#e8e1d5] flex justify-end"><button type="submit" name="save_display_style" class="bg-[#87714c] hover:bg-[#b08d55] text-white font-bold py-3 px-8 rounded-lg shadow-lg">Simpan Style</button></div>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <script>
        function switchTab(tabName) {
            document.getElementById('tab-login-content').classList.add('hidden');
            document.getElementById('tab-display-content').classList.add('hidden');
            document.getElementById('tab-login-content').classList.remove('block');
            document.getElementById('tab-display-content').classList.remove('block');
            document.getElementById('btn-login').classList.remove('active');
            document.getElementById('btn-display').classList.remove('active');
            document.getElementById('tab-' + tabName + '-content').classList.remove('hidden');
            document.getElementById('tab-' + tabName + '-content').classList.add('block');
            document.getElementById('btn-' + tabName).classList.add('active');
        }
        function toggleEdit(id) {
            const viewRow = document.getElementById('row-view-' + id);
            const editRow = document.getElementById('row-edit-' + id);
            if (viewRow.classList.contains('hidden')) {
                viewRow.classList.remove('hidden');
                editRow.classList.add('hidden');
            } else {
                viewRow.classList.add('hidden');
                editRow.classList.remove('hidden');
            }
        }
        <?php if(isset($tab_active) && $tab_active == 'display'): ?>
            switchTab('display');
        <?php else: ?>
            switchTab('login');
        <?php endif; ?>
    </script>
        <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
        </footer>
    </main>
</body></html>