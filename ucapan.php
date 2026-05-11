<?php
ob_start();
require 'koneksi.php';
require_login();
// Database Komentar Terhubung via koneksi.php
$error_db = "";
if(!$koneksi_wp) { $error_db = "Koneksi ke database komentar gagal. Pastikan database <b>$db_wp</b> dan tabel <b>$tabel_komentar</b> tersedia."; }
$id_user_login=$_SESSION['user_id']??0;
$role_login=$_SESSION['role']??'mempelai';
$parent_id_login=$_SESSION['parent_id']??0;
$effective_uid_login=($role_login=='receptionist')?$parent_id_login:$id_user_login;

// LOGIKA FILTER EVENT (Agar Konsisten dengan Page Lain)
if($role_login != 'admin'){
    $q_events_list = mysqli_query($koneksi, "SELECT * FROM events WHERE user_id = '$effective_uid_login' ORDER BY id DESC");
} else {
    $q_events_list = mysqli_query($koneksi, "SELECT * FROM events ORDER BY id DESC");
}
$selected_event_id = isset($_GET['event_id']) ? esc($_GET['event_id']) : '';
if(empty($selected_event_id) && mysqli_num_rows($q_events_list) > 0) {
    mysqli_data_seek($q_events_list, 0); $first_ev = mysqli_fetch_assoc($q_events_list); $selected_event_id = $first_ev['id'];
}
$nama_mempelai="";$post_id_target=0;$admin_mode_list=false;$list_post_ids=[];
if($role_login=='admin'){
if(isset($_GET['cari_id_post'])&&!empty($_GET['cari_id_post'])){header("Location: ucapan?lihat_id=".(int)$_GET['cari_id_post']);exit;}
if(!isset($_GET['lihat_id'])){
    $admin_mode_list=true;
    $nama_mempelai="Administrator";
    
    if (empty($db_wp)) {
        $admin_mode_list = true; // tetap masuk mode list admin
        $list_post_ids = []; // tapi kosong
        $error_db = "__WP_NOT_CONFIGURED__"; // flag khusus, bukan error biasa
    } elseif (!$koneksi_wp) {
        $error_db = "Koneksi database WP gagal: " . mysqli_connect_error() . " (Pastikan user, password, dan nama DB WP benar)";
    } else {
        $exec_list = mysqli_query($koneksi_wp,"SELECT comment_post_ID, COUNT(*) as total_komen, MAX(comment_date) as last_update FROM $tabel_komentar GROUP BY comment_post_ID ORDER BY last_update DESC LIMIT 5");
        if($exec_list){
            while($row_list=mysqli_fetch_assoc($exec_list)){
                $list_post_ids[]=$row_list;
            }
        } else {
            $error_db = "Gagal membaca tabel komentar WP: " . mysqli_error($koneksi_wp) . " (Pastikan tabel $tabel_komentar dan kolom comment_approved ada)";
        }
    }
} else {$post_id_target=(int)$_GET['lihat_id'];$nama_mempelai="Mode Admin";}
}else{if($effective_uid_login>0){$q_user=mysqli_query($koneksi,"SELECT * FROM users WHERE id='$effective_uid_login'");if($q_user&&mysqli_num_rows($q_user)>0){$d_user=mysqli_fetch_assoc($q_user);if(isset($d_user['post_id'])&&$d_user['post_id']>0)$post_id_target=(int)$d_user['post_id'];$nama_mempelai=$d_user['nama_lengkap'];}}}
$keyword="";$where_search="";
if(isset($_GET['q'])&&!empty($_GET['q'])){$keyword=$koneksi_wp?mysqli_real_escape_string($koneksi_wp,$_GET['q']):addslashes($_GET['q']);$where_search="AND (comment_author LIKE '%$keyword%' OR comment_content LIKE '%$keyword%')";}
$batas_per_halaman=6;$halaman_aktif=isset($_GET['page'])?(int)$_GET['page']:1;$mulai_data=($halaman_aktif>1)?($halaman_aktif*$batas_per_halaman)-$batas_per_halaman:0;$total_halaman=1;$result_ucapan=false;$total_data=0;
if(!$admin_mode_list && $post_id_target > 0){
    if(!$koneksi_wp || empty($db_wp)){
        $error_db = "Koneksi database komentar ($db_wp) belum dikonfigurasi atau gagal.";
    } else {
        $check_table = mysqli_query($koneksi_wp, "SHOW TABLES LIKE '$tabel_komentar'");
        if(!$check_table || mysqli_num_rows($check_table) == 0){
            $error_db = "Tabel database '$tabel_komentar' tidak ditemukan pada database '$db_wp'.";
        } else {
            $sql_count = mysqli_query($koneksi_wp, "SELECT COUNT(*) as total FROM $tabel_komentar WHERE comment_post_ID='$post_id_target' AND comment_approved='1' $where_search");
            if($sql_count){
                $data_count = mysqli_fetch_assoc($sql_count);
                $total_data = $data_count['total'];
                $total_halaman = ceil($total_data / $batas_per_halaman);
                $result_ucapan = mysqli_query($koneksi_wp, "SELECT * FROM $tabel_komentar WHERE comment_post_ID='$post_id_target' AND comment_approved='1' $where_search ORDER BY comment_date DESC LIMIT $mulai_data, $batas_per_halaman");
            } else {
                $error_db = "Gagal mengambil data komentar dari tabel '$tabel_komentar'.";
            }
        }
    }
}
$config=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM pengaturan LIMIT 1"));
function get_pagination_url($p){$pm=$_GET;$pm['page']=$p;return '?'.http_build_query($pm);}
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
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Buku Tamu - <?= isset($config['app_name'])?$config['app_name']:'Wedding' ?></title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Kalam:wght@300;400;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script><style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; }
    .font-serif { font-family: 'Playfair Display', serif; }
    .font-kalam { font-family: 'Kalam', cursive; }
    .text-gold { color: #87714c; }
    .text-brown { color: #1a0f0d; }
    
    .card-ucapan:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(135, 113, 76, 0.1); transition: all 0.3s ease; }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #87714c; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background-color: #f3e9d8; }
    
    @media (max-width: 768px) { body { padding-bottom: 80px; } }
</style>
</head>
<body class="text-[#1a0f0d]" style="background-color:#ffffff; background-image:url('https://www.transparenttextures.com/patterns/cream-paper.png');">
<?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>
<main class="md:ml-64 p-4 lg:p-6 relative">
    
    <!-- Header Section (ALIGNED WITH TAMU.PHP CONCEPT) -->
    <div class="mb-5 lg:mb-6 border-b border-[#d1c7b7] pb-3 no-print flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                <iconify-icon icon="solar:chat-round-like-bold-duotone" class="text-2xl"></iconify-icon>
            </div>
            <div>
                <h1 class="text-2xl lg:text-3xl font-bold text-[#1a0f0d] font-serif">Ucapan & Doa</h1>
                <p class="text-[#87714c] mt-1 text-sm">Lihat kumpulan ucapan dari para tamu undangan.</p>
            </div>
        </div>
        
        <?php if($role_login != 'admin'): ?>
        <div class="w-full md:w-72">
            <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase tracking-widest pl-1">ID Post Aktif:</label>
            <div class="bg-white border border-[#e8e1d5] px-4 py-2.5 rounded-xl text-sm font-bold flex items-center justify-between shadow-sm">
                <span class="text-gray-400">Post ID:</span>
                <span class="text-[#1a0f0d]">#<?= $post_id_target ?></span>
            </div>
        </div>
        <?php else: ?>
        <div class="w-full md:w-72">
             <label class="block text-xs font-bold text-[#87714c] mb-2 uppercase tracking-widest pl-1">Mode:</label>
             <div class="bg-[#1a0f0d] text-white px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest text-center shadow-sm">
                Administrator
             </div>
        </div>
        <?php endif; ?>
    </div>
<?php if($error_db === '__WP_NOT_CONFIGURED__'): ?>
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 flex items-start gap-4">
    <iconify-icon icon="solar:database-bold-duotone" class="text-3xl text-amber-500 shrink-0 mt-1"></iconify-icon>
    <div>
        <h3 class="font-bold text-amber-800 text-base">Fitur Ucapan Belum Terhubung</h3>
        <p class="text-sm text-amber-700 mt-1">Database WordPress belum dikonfigurasi di lokal. Data ucapan akan tampil setelah kamu mengisi <b>$db_wp</b> di file <code class="bg-amber-100 px-1 rounded font-mono text-xs">tables.php</code> dengan nama database WordPress kamu.</p>
        <p class="text-xs text-amber-600 mt-2">Untuk menggunakan di <b>production/hosting</b>, data sudah terisi dan akan bekerja otomatis saat di-upload.</p>
    </div>
</div>
<?php elseif(!empty($error_db)): ?><div class="bg-red-50 border-l-4 border-red-500 text-red-800 p-6 rounded-r-xl shadow-sm flex items-center gap-3"><iconify-icon icon="solar:danger-circle-bold-duotone" class="text-2xl"></iconify-icon><div><h3 class="font-bold text-lg">Kesalahan Database</h3><p class="text-sm"><?= $error_db ?></p></div></div><?php elseif($admin_mode_list): ?><div class="bg-white border border-[#e8e1d5] rounded-xl p-5 mb-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4"><div class="flex items-start md:items-center gap-3 w-full md:w-auto"><div class="min-w-[42px] h-[42px] rounded-full bg-[#faf7f0] flex items-center justify-center text-[#C5A880] border border-[#f0ebe3]"><iconify-icon icon="solar:notes-bold-duotone" class="text-xl"></iconify-icon></div><div><h4 class="font-bold text-[#5D4037] text-sm leading-tight">Mode Ringan Aktif</h4><p class="text-xs text-[#8d6e63] mt-0.5">Menampilkan 5 aktivitas terakhir.</p></div></div><form action="" method="GET" class="flex items-center gap-2 w-full md:w-auto"><div class="relative w-full md:w-64"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><iconify-icon icon="solar:magnifer-linear" class="text-[#C5A880] text-lg"></iconify-icon></div><input type="number" name="cari_id_post" placeholder="Cari ID Post..." required class="w-full pl-10 pr-3 py-2.5 bg-[#faf7f0] border border-[#e8e1d5] rounded-xl text-sm text-[#5D4037] placeholder-[#bcaaa4] focus:outline-none focus:ring-1 focus:ring-[#C5A880] focus:border-[#C5A880] transition"></div><button type="submit" class="bg-[#5D4037] hover:bg-[#4e342e] text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md transition flex items-center gap-2">Cari</button></form></div><div class="hidden md:block bg-white border border-[#e8e1d5] rounded-xl shadow-sm overflow-hidden"><div class="overflow-x-auto"><table class="w-full text-left text-sm text-[#5D4037]"><thead class="bg-[#faf7f0] border-b border-[#e8e1d5] uppercase font-bold text-xs tracking-wider"><tr><th class="px-6 py-4 w-16">No</th><th class="px-6 py-4">ID Post</th><th class="px-6 py-4">Jumlah Ucapan</th><th class="px-6 py-4">Ucapan Terakhir</th><th class="px-6 py-4 text-center w-32">Aksi</th></tr></thead><tbody class="divide-y divide-[#f0ebe3]"><?php if(empty($list_post_ids)): ?><tr><td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Belum ada data komentar masuk. (DB: <?= $db_wp ?>, Tabel: <?= $tabel_komentar ?>)</td></tr><?php else:$no=1;foreach($list_post_ids as $p): ?><tr class="hover:bg-[#fffdf9] transition duration-150"><td class="px-6 py-4 font-medium text-[#C5A880]"><?= $no++ ?></td><td class="px-6 py-4 font-bold">#<?= $p['comment_post_ID'] ?></td><td class="px-6 py-4"><span class="inline-flex items-center gap-1 bg-green-50 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold border border-green-100"><?= $p['total_komen'] ?> Ucapan</span></td><td class="px-6 py-4 text-gray-500"><?= date('d M Y, H:i',strtotime($p['last_update'])) ?></td><td class="px-6 py-4 text-center"><a href="ucapan?lihat_id=<?= $p['comment_post_ID'] ?>" class="inline-flex items-center gap-2 bg-[#5D4037] hover:bg-[#4e342e] text-white px-4 py-2 rounded-lg text-xs font-bold transition shadow-sm whitespace-nowrap"><iconify-icon icon="solar:eye-bold"></iconify-icon> Lihat</a></td></tr><?php endforeach;endif; ?></tbody></table></div></div><div class="block md:hidden space-y-4"><?php if(empty($list_post_ids)): ?><div class="bg-white p-6 rounded-xl text-center border border-[#e8e1d5] text-gray-500 italic">Belum ada data komentar masuk.</div><?php else:foreach($list_post_ids as $p): ?><div class="bg-white p-5 rounded-xl border border-[#e8e1d5] shadow-sm flex flex-col gap-3 relative"><div class="absolute top-4 right-4 bg-[#f0ebe3] text-[#5D4037] text-xs px-2 py-1 rounded font-bold">#<?= $p['comment_post_ID'] ?></div><div><span class="text-[10px] uppercase text-[#C5A880] font-bold tracking-wider">Total Ucapan</span><div class="text-lg font-bold text-[#5D4037] flex items-center gap-2"><iconify-icon icon="solar:chat-round-dots-bold" class="text-[#C5A880]"></iconify-icon> <?= $p['total_komen'] ?></div></div><div><span class="text-[10px] uppercase text-[#C5A880] font-bold tracking-wider">Update Terakhir</span><div class="text-sm text-gray-600 font-medium"><?= date('d M Y, H:i',strtotime($p['last_update'])) ?></div></div><a href="ucapan?lihat_id=<?= $p['comment_post_ID'] ?>" class="mt-2 w-full bg-[#5D4037] text-white py-2.5 rounded-lg text-sm font-bold text-center flex items-center justify-center gap-2 shadow hover:bg-[#4e342e]"><iconify-icon icon="solar:eye-bold"></iconify-icon> Lihat Detail</a></div><?php endforeach;endif; ?></div><?php elseif($post_id_target==0): ?><div class="flex flex-col items-center justify-center py-20 bg-white border border-[#e8e1d5] border-dashed rounded-xl"><div class="bg-orange-50 w-20 h-20 rounded-full flex items-center justify-center mb-4"><iconify-icon icon="solar:settings-minimalistic-bold-duotone" class="text-3xl text-orange-400"></iconify-icon></div><h3 class="font-serif text-lg font-bold text-[#5D4037]">ID Post Tidak Ditemukan</h3><p class="text-sm text-[#8d6e63] mt-1 max-w-md text-center">Anda belum mengatur ID Post di profil atau data tidak valid. Silakan hubungi admin.</p><?php if($role_login=='admin'): ?><a href="ucapan" class="mt-4 text-sm font-bold text-[#5D4037] hover:underline">Kembali ke Daftar</a><?php endif; ?></div><?php else: ?><div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-[#e8e1d5]"><form method="GET" action="" class="relative flex items-center w-full"><?php if(isset($_GET['lihat_id'])): ?><input type="hidden" name="lihat_id" value="<?= $_GET['lihat_id'] ?>"><?php endif; ?><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><iconify-icon icon="solar:magnifer-bold-duotone" class="text-[#a1887f] text-lg"></iconify-icon></div><input type="text" name="q" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama pengirim..." class="w-full pl-10 pr-4 py-2.5 bg-[#fcfbf9] border border-[#e8e1d5] rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C5A880] text-[#5D4037] placeholder-gray-400"><?php if(!empty($keyword)): ?><a href="ucapan<?= isset($_GET['lihat_id'])?'?lihat_id='.$_GET['lihat_id']:'' ?>" class="absolute right-3 text-xs bg-gray-200 hover:bg-gray-300 text-gray-600 px-2 py-1 rounded font-bold transition">Reset</a><?php else: ?><button type="submit" class="absolute right-3 text-[#C5A880] hover:text-[#5D4037] transition"><iconify-icon icon="solar:arrow-right-bold-duotone" class="text-xl"></iconify-icon></button><?php endif; ?></form></div><?php if($result_ucapan&&mysqli_num_rows($result_ucapan)>0): ?><div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5"><?php while($row=mysqli_fetch_assoc($result_ucapan)):
    $cid = $row['comment_ID'];
    $date=date_create($row['comment_date']);
    $author=!empty($row['comment_author'])?$row['comment_author']:'Tanpa Nama';
    $avatar_bg=['bg-[#C5A880]','bg-[#8D6E63]','bg-[#A1887F]','bg-[#D7CCC8]','bg-[#BCAAA4]'][array_rand(['bg-[#C5A880]','bg-[#8D6E63]','bg-[#A1887F]','bg-[#D7CCC8]','bg-[#BCAAA4]'])]; 
    
    // FETCH META (Plugin 'eveent')
    $sticker_html = ""; $attendance_badge = ""; $is_rsvp_only = false;
    $rsvp_phrases = ['Konfirmasi Hadir', 'Konfirmasi Tidak Hadir', 'Konfirmasi Ragu-ragu', 'Konfirmasi Kehadiran'];
    
    if($koneksi_wp){
        $q_meta = mysqli_query($koneksi_wp, "SELECT meta_key, meta_value FROM $tabel_meta WHERE comment_id = '$cid'");
        while($m = mysqli_fetch_assoc($q_meta)){
            if($m['meta_key'] == '_selected_sticker'){
                $sdata = json_decode($m['meta_value'], true);
                if($sdata){
                    $val = $sdata['value']; $lib = $sdata['library'] ?? '';
                    if($lib == 'svg' && is_array($val) && isset($val['url'])){
                        $sticker_html = '<div class="mt-2 text-center"><img src="'.$val['url'].'" style="height:60px; display:inline-block;"></div>';
                    } elseif(is_string($val)){
                        $sticker_html = '<div class="mt-2 text-center text-4xl text-[#87714c]"><i class="'.$val.'"></i></div>';
                    }
                }
            }
            if($m['meta_key'] == 'attendance'){
                if($m['meta_value'] == 'present') $attendance_badge = '<span class="bg-green-100 text-green-700 text-[9px] px-1.5 py-0.5 rounded font-bold uppercase border border-green-200 ml-1">Hadir</span>';
                elseif($m['meta_value'] == 'notpresent') $attendance_badge = '<span class="bg-red-100 text-red-700 text-[9px] px-1.5 py-0.5 rounded font-bold uppercase border border-red-200 ml-1">Absen</span>';
                elseif($m['meta_value'] == 'notsure') $attendance_badge = '<span class="bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded font-bold uppercase border border-amber-200 ml-1">Ragu</span>';
            }
        }
    }

    // Filter RSVP Only (Jika isinya cuma teks konfirmasi & gak ada stiker, jangan tampilkan)
    if(in_array(trim($row['comment_content']), $rsvp_phrases) && empty($sticker_html)){
        continue;
    }
?><div class="card-ucapan bg-white border border-[#e8e1d5] p-5 rounded-xl flex flex-col relative overflow-hidden group h-full"><iconify-icon icon="solar:quote-up-square-bold-duotone" class="absolute top-2 right-2 text-5xl text-[#faf7f0] -z-0 transform rotate-12 group-hover:rotate-0 transition duration-500"></iconify-icon><div class="relative z-10 flex flex-col h-full"><div class="flex items-center gap-3 mb-3"><div class="<?= $avatar_bg ?> w-10 h-10 min-w-[2.5rem] rounded-full flex items-center justify-center text-white font-bold font-serif shadow-md border-2 border-white text-sm"><?= strtoupper(substr($author,0,1)) ?></div><div class="overflow-hidden"><div class="flex items-center"><h4 class="font-bold text-[#5D4037] text-sm truncate" title="<?= $author ?>"><?= strip_tags($author) ?></h4><?= $attendance_badge ?></div><div class="text-[10px] text-[#C5A880] font-medium uppercase mt-0.5 flex items-center gap-1"><iconify-icon icon="solar:calendar-date-bold"></iconify-icon> <?= date_format($date,"d M Y • H:i") ?></div></div></div><div class="bg-[#fcfbf9] p-4 rounded-xl border border-dashed border-[#e8e1d5] flex-grow shadow-inner"><p class="text-[#6d4c41] text-sm font-kalam leading-relaxed">"<?= strip_tags($row['comment_content']) ?>"</p><?= $sticker_html ?></div></div></div><?php endwhile; ?></div><?php if($total_halaman>1): ?><div class="mt-10 flex justify-center pb-8"><nav class="flex flex-wrap justify-center items-center gap-2 bg-white p-3 rounded-xl border border-[#e8e1d5] shadow-sm"><?php if($halaman_aktif>1): ?><a href="<?= get_pagination_url($halaman_aktif-1) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-[#5D4037] hover:bg-[#faf7f0] border border-transparent hover:border-[#e8e1d5] transition"><iconify-icon icon="solar:alt-arrow-left-bold" class="text-lg"></iconify-icon></a><?php else: ?><span class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed"><iconify-icon icon="solar:alt-arrow-left-bold" class="text-lg"></iconify-icon></span><?php endif; ?><?php $start_page=($halaman_aktif-1>1)?$halaman_aktif-1:1;$end_page=($halaman_aktif+1<$total_halaman)?$halaman_aktif+1:$total_halaman;if($start_page>1){echo '<a href="'.get_pagination_url(1).'" class="w-9 h-9 flex items-center justify-center rounded-lg text-[#5D4037] hover:bg-[#faf7f0] text-sm font-bold">1</a>';if($start_page>2)echo '<span class="w-9 h-9 flex items-center justify-center text-gray-400 text-xs">...</span>';}for($i=$start_page;$i<=$end_page;$i++){echo ($i==$halaman_aktif)?'<span class="w-9 h-9 flex items-center justify-center rounded-lg bg-[#5D4037] text-white font-bold text-sm shadow-md transform scale-105">'.$i.'</span>':'<a href="'.get_pagination_url($i).'" class="w-9 h-9 flex items-center justify-center rounded-lg text-[#5D4037] hover:bg-[#faf7f0] text-sm font-medium transition">'.$i.'</a>';}if($end_page<$total_halaman){if($end_page<$total_halaman-1)echo '<span class="w-9 h-9 flex items-center justify-center text-gray-400 text-xs">...</span>';echo '<a href="'.get_pagination_url($total_halaman).'" class="w-9 h-9 flex items-center justify-center rounded-lg text-[#5D4037] hover:bg-[#faf7f0] text-sm font-bold">'.$total_halaman.'</a>';} ?><?php if($halaman_aktif<$total_halaman): ?><a href="<?= get_pagination_url($halaman_aktif+1) ?>" class="w-9 h-9 flex items-center justify-center rounded-lg text-[#5D4037] hover:bg-[#faf7f0] border border-transparent hover:border-[#e8e1d5] transition"><iconify-icon icon="solar:alt-arrow-right-bold" class="text-lg"></iconify-icon></a><?php else: ?><span class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-300 cursor-not-allowed"><iconify-icon icon="solar:alt-arrow-right-bold" class="text-lg"></iconify-icon></span><?php endif; ?></nav></div><?php endif; ?><?php else: ?><div class="flex flex-col items-center justify-center py-20 bg-white border border-[#e8e1d5] border-dashed rounded-xl"><div class="bg-[#faf7f0] w-20 h-20 rounded-full flex items-center justify-center mb-4"><iconify-icon icon="solar:chat-square-like-bold-duotone" class="text-4xl text-[#C5A880]"></iconify-icon></div><?php if(!empty($keyword)): ?><h3 class="font-serif text-lg font-bold text-[#5D4037]">Pencarian Tidak Ditemukan</h3><p class="text-sm text-[#8d6e63] mt-1">Tidak ada ucapan yang cocok dengan kata kunci "<b><?= htmlspecialchars($keyword) ?></b>"</p><?php else: ?><h3 class="font-serif text-lg font-bold text-[#5D4037]">Belum Ada Ucapan</h3><p class="text-sm text-[#8d6e63] mt-1">Belum ada komentar masuk untuk ID Post #<?= $post_id_target ?></p><?php endif; ?></div><?php endif; ?><?php endif; ?></div>
<footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
    <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
</footer>
</main></body></html><?php ob_end_flush(); ?>