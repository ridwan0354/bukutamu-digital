<?php
require_once 'koneksi.php';
require_admin();
check_csrf();

// AUTO-FIX: Cek dan tambah kolom post_id jika belum ada
$check_col = mysqli_query($koneksi, "SHOW COLUMNS FROM users LIKE 'post_id'");
if (mysqli_num_rows($check_col) == 0) {
    mysqli_query($koneksi, "ALTER TABLE users ADD COLUMN post_id INT(11) DEFAULT 0 AFTER event_limit");
}
$swal_script="";
function redirectWithMsg($url,$msg_key){echo "<script>window.location.href='$url".(strpos($url,'?')?'&':'?')."msg=$msg_key';</script>";exit;}

if(isset($_POST['add_mempelai'])){
    // CSRF Verified via check_csrf()
    $user = trim($_POST['username']);
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama = trim($_POST['nama_lengkap']);
    $limit = isset($_POST['event_limit']) ? (int)$_POST['event_limit'] : 1;
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    
    $cek_stmt = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($cek_stmt, "s", $user);
    mysqli_stmt_execute($cek_stmt);
    $cek_res = mysqli_stmt_get_result($cek_stmt);
    
    if(mysqli_num_rows($cek_res) > 0){
        $swal_script = "ModalAlert.fire('Gagal', 'Username sudah digunakan, pilih yang lain.', 'error');";
    } else {
        $query = "INSERT INTO users (username, password, nama_lengkap, role, event_limit, post_id) VALUES (?, ?, ?, 'mempelai', ?, ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "sssii", $user, $pass, $nama, $limit, $post_id);
        if(mysqli_stmt_execute($stmt)){
            redirectWithMsg("kelola_mempelai.php","added");
        } else {
            $err = mysqli_error($koneksi);
            $swal_script = "ModalAlert.fire('Gagal', 'Database error: $err', 'error');";
        }
    }
}

if(isset($_POST['edit_mempelai'])){
    // CSRF Verified via check_csrf()
    $id = (int)$_POST['id_user'];
    $user = trim($_POST['username']);
    $nama = trim($_POST['nama_lengkap']);
    $limit = (int)$_POST['event_limit'];
    $post_id = (int)$_POST['post_id'];
    
    // 1. Cek duplikat username (kecuali milik sendiri)
    $stmt_cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE username = ? AND id != ?");
    mysqli_stmt_bind_param($stmt_cek, "si", $user, $id);
    mysqli_stmt_execute($stmt_cek);
    mysqli_stmt_store_result($stmt_cek);
    $duplikat = mysqli_stmt_num_rows($stmt_cek);
    mysqli_stmt_close($stmt_cek);

    if($duplikat > 0) {
        $swal_script = "ModalAlert.fire('Gagal', 'Username sudah digunakan oleh akun lain.', 'error');";
    } else {
        // 2. Proses Update
        if(!empty($_POST['password'])){
            $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username=?, nama_lengkap=?, password=?, event_limit=?, post_id=? WHERE id=? AND role='mempelai'";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "sssiii", $user, $nama, $pass, $limit, $post_id, $id);
        } else {
            $sql = "UPDATE users SET username=?, nama_lengkap=?, event_limit=?, post_id=? WHERE id=? AND role='mempelai'";
            $stmt = mysqli_prepare($koneksi, $sql);
            mysqli_stmt_bind_param($stmt, "ssiii", $user, $nama, $limit, $post_id, $id);
        }
        
        if(mysqli_stmt_execute($stmt)){
            mysqli_stmt_close($stmt);
            redirectWithMsg("kelola_mempelai.php","updated");
        } else {
            $err = mysqli_error($koneksi);
            $swal_script = "ModalAlert.fire('Gagal', 'Terjadi kesalahan sistem: $err', 'error');";
        }
    }
}

if(isset($_POST['hapus_mempelai'])){
    $id_hapus=(int)$_POST['id_user'];
    $q_ev=mysqli_query($koneksi,"SELECT id FROM events WHERE user_id='$id_hapus'");
    while($ev=mysqli_fetch_assoc($q_ev)){
        $eid=$ev['id'];
        mysqli_query($koneksi,"DELETE FROM tamu WHERE event_id='$eid'");
        mysqli_query($koneksi,"DELETE FROM events WHERE id='$eid'");
    }
    mysqli_query($koneksi, "DELETE FROM kategori_tamu WHERE user_id='$id_hapus'");
    mysqli_query($koneksi,"DELETE FROM users WHERE id=$id_hapus AND role='mempelai'");
    redirectWithMsg("kelola_mempelai.php","deleted");
}

if(isset($_GET['msg'])){
    if($_GET['msg']=='deleted') $swal_script="Toast.fire({icon: 'success', title: 'Akun mempelai dihapus'});";
    if($_GET['msg']=='added') $swal_script="ModalAlert.fire({title: 'Berhasil', text: 'Akun mempelai berhasil didaftarkan!', icon: 'success'});";
    if($_GET['msg']=='updated') $swal_script="Toast.fire({icon: 'success', title: 'Data akun diperbarui'});";
}

$config=mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT * FROM pengaturan LIMIT 1"));
$users=mysqli_query($koneksi,"SELECT * FROM users WHERE role='mempelai' ORDER BY id DESC");
$total_mempelai=mysqli_num_rows($users);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mempelai - <?= $config['app_name'] ?></title>
    <?php if(!empty($config['favicon'])): ?>
        <link rel="icon" href="assets/<?= $config['favicon'] ?>?v=<?= time() ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config={theme:{extend:{colors:{primary:'#C5A880',secondary:'#000000',surface:'#fffcf9'},fontFamily:{sans:['Plus Jakarta Sans','sans-serif'],serif:['Playfair Display','serif']}}}}
    </script>
    <style>
        body { background-color:#ffffff; background-image:url("https://www.transparenttextures.com/patterns/cream-paper.png"); }
        div:where(.swal2-container) div:where(.swal2-popup) { border-radius: 1rem!important; border: 1px solid #e8e1d5; font-family:'Plus Jakarta Sans',sans-serif; }
        .modal { transition: opacity .25s ease; }
        body.modal-active { overflow-x: hidden; overflow-y: visible!important; }
    </style>
</head>
<body class="text-[#000000]">
    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>
    
    <main class="md:ml-64 p-4 lg:p-10 relative min-w-0 overflow-x-hidden transition-all duration-300">
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Section -->
            <div class="mb-5 lg:mb-6 border-b border-[#d1c7b7] pb-3 no-print">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                            <iconify-icon icon="solar:users-group-two-rounded-bold-duotone" class="text-2xl"></iconify-icon>
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-[#000000] font-serif">Kelola Mempelai</h1>
                            <p class="text-[#87714c] mt-1 text-sm">Manajemen Akun & Hak Akses.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 w-full md:w-auto">
                        <div class="bg-white border border-[#e8e1d5] p-2 px-4 rounded-xl shadow-sm min-w-[120px]">
                            <div class="text-[8px] font-black text-blue-400 uppercase tracking-widest mb-0.5">Total Akun</div>
                            <div class="text-sm font-black text-[#000000]"><?= $total_mempelai ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tambah Mempelai -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-[#e8e1d5] mb-8 transition-all hover:shadow-md">
                <h3 class="font-bold text-[#000000] mb-5 flex items-center gap-2 text-lg font-serif border-b border-[#faf7f0] pb-3">
                    <iconify-icon icon="solar:user-plus-bold-duotone" class="text-[#C5A880] text-xl"></iconify-icon> Daftarkan Mempelai Baru
                </h3>
                <form action="" method="POST" class="grid grid-cols-2 lg:grid-cols-12 gap-4 items-end">
                    <?= csrf_field() ?>
                    <div class="col-span-2 lg:col-span-3">
                        <label class="block text-[10px] font-bold text-[#C5A880] uppercase mb-1 ml-2">Nama Lengkap</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><iconify-icon icon="solar:user-id-bold-duotone" class="text-[#C5A880] text-lg"></iconify-icon></div>
                            <input type="text" name="nama_lengkap" placeholder="Romeo & Juliet" required class="w-full bg-[#ffffff] border border-[#e8e1d5] rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C5A880] outline-none text-[#000000] placeholder-gray-300">
                        </div>
                    </div>
                    <div class="col-span-2 lg:col-span-2">
                        <label class="block text-[10px] font-bold text-[#C5A880] uppercase mb-1 ml-2">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><iconify-icon icon="solar:mention-circle-bold-duotone" class="text-[#C5A880] text-lg"></iconify-icon></div>
                            <input type="text" name="username" placeholder="user_login" required class="w-full bg-[#ffffff] border border-[#e8e1d5] rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C5A880] outline-none font-mono text-[#000000] placeholder-gray-300">
                        </div>
                    </div>
                    <div class="col-span-2 lg:col-span-2">
                        <label class="block text-[10px] font-bold text-[#C5A880] uppercase mb-1 ml-2">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><iconify-icon icon="solar:lock-password-bold-duotone" class="text-[#C5A880] text-lg"></iconify-icon></div>
                            <input type="password" name="password" placeholder="••••••" required class="w-full bg-[#ffffff] border border-[#e8e1d5] rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-[#C5A880] outline-none text-[#000000] placeholder-gray-300">
                        </div>
                    </div>
                    <div class="col-span-1 lg:col-span-2">
                        <label class="block text-[10px] font-bold text-[#C5A880] uppercase mb-1 ml-2 truncate">ID Post</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none"><iconify-icon icon="solar:document-add-bold-duotone" class="text-[#C5A880] text-lg"></iconify-icon></div>
                            <input type="number" name="post_id" placeholder="123" required class="w-full bg-[#ffffff] border border-[#e8e1d5] rounded-xl pl-9 pr-2 py-2.5 text-sm focus:ring-2 focus:ring-[#C5A880] outline-none text-[#000000] font-bold text-center placeholder-gray-300">
                        </div>
                    </div>
                    <div class="col-span-1 lg:col-span-1">
                        <label class="block text-[10px] font-bold text-[#C5A880] uppercase mb-1 ml-2">Limit</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none"><iconify-icon icon="solar:hourglass-line-bold-duotone" class="text-[#C5A880] text-lg"></iconify-icon></div>
                            <input type="number" name="event_limit" value="1" min="1" required class="w-full bg-[#ffffff] border border-[#e8e1d5] rounded-xl pl-7 pr-1 py-2.5 text-sm focus:ring-2 focus:ring-[#C5A880] outline-none text-[#000000] text-center font-bold">
                        </div>
                    </div>
                    <div class="col-span-2 lg:col-span-2">
                        <button type="submit" name="add_mempelai" class="w-full bg-gradient-to-br from-[#C5A880] to-[#000000] text-white font-bold py-2.5 px-4 rounded-xl shadow-lg transition transform active:scale-95 text-sm flex items-center justify-center gap-2">
                            <iconify-icon icon="solar:diskette-bold-duotone" class="text-lg"></iconify-icon> SIMPAN
                        </button>
                    </div>
                </form>
            </div>

            <!-- Daftar Mempelai -->
            <div class="w-full bg-white rounded-xl shadow-sm border border-[#e8e1d5] overflow-hidden">
                <div class="p-5 border-b border-[#e8e1d5] bg-[#faf7f0] flex justify-between items-center">
                    <h3 class="font-bold text-[#000000] font-serif flex items-center gap-2 text-sm">
                        <iconify-icon icon="solar:list-check-bold-duotone" class="text-lg text-[#C5A880]"></iconify-icon> Daftar Akun Mempelai
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] font-bold uppercase text-[#000000] tracking-widest border-b border-[#e8e1d5] bg-[#ffffff]">
                                <th class="p-5 text-center w-16">#</th>
                                <th class="p-5">Nama Mempelai</th>
                                <th class="p-5">Username</th>
                                <th class="p-5 text-center">ID Post</th>
                                <th class="p-5 text-center">Limit Event</th>
                                <th class="p-5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#faf7f0]">
                            <?php 
                            $no=1;
                            mysqli_data_seek($users, 0);
                            if(mysqli_num_rows($users)>0): 
                                while($u=mysqli_fetch_assoc($users)):
                                    $q_cnt = mysqli_fetch_assoc(mysqli_query($koneksi,"SELECT COUNT(*) as total FROM events WHERE user_id='".$u['id']."'"));
                                    $used = $q_cnt['total'];
                                    $limit = $u['event_limit'];
                                    $persen = ($limit>0) ? ($used/$limit)*100 : 0;
                                    $color_bar = ($used >= $limit) ? 'bg-red-400' : 'bg-green-400';
                            ?>
                            <tr class="hover:bg-[#fffbf2] transition">
                                <td class="p-5 text-center text-[#d7ccc8] font-medium border-none"><?= $no++ ?></td>
                                <td class="p-5 border-none">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 min-w-[2.5rem] bg-[#faf7f0] text-[#C5A880] rounded-full flex items-center justify-center font-bold border border-[#e8e1d5]">
                                            <iconify-icon icon="solar:heart-bold-duotone" class="text-lg"></iconify-icon>
                                        </div>
                                        <span class="font-bold text-[#000000] font-serif"><?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES) ?></span>
                                    </div>
                                </td>
                                <td class="p-5 font-mono text-sm text-[#8d6e63] border-none"><?= htmlspecialchars($u['username'], ENT_QUOTES) ?></td>
                                <td class="p-5 text-center border-none">
                                    <span class="inline-block bg-[#ffffff] border border-[#e8e1d5] px-1.5 py-1 rounded text-xs font-mono font-bold"><?= $u['post_id'] ?></span>
                                </td>
                                <td class="p-5 border-none">
                                    <div class="flex flex-col items-center">
                                        <div class="w-24 h-1.5 bg-[#faf7f0] rounded-full overflow-hidden border border-[#e8e1d5]">
                                            <div class="h-full <?= $color_bar ?>" style="width: <?= min($persen,100) ?>%"></div>
                                        </div>
                                        <span class="text-[10px] font-bold mt-1"><?= $used ?> / <?= $limit ?></span>
                                    </div>
                                </td>
                                <td class="p-5 border-none">
                                    <div class="flex justify-center gap-2">
                                        <?php 
                                        $js_edit = json_encode([
                                            $u['id'], 
                                            $u['nama_lengkap'], 
                                            $u['username'], 
                                            (int)$u['event_limit'], 
                                            $u['post_id']
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT);
                                        ?>
                                        <button onclick='openEditModal(<?= htmlspecialchars($js_edit, ENT_QUOTES) ?>)' class="text-[#C5A880] hover:text-[#000000] bg-[#faf7f0] w-9 h-9 flex items-center justify-center rounded-xl border border-[#e8e1d5] transition shadow-sm">
                                            <iconify-icon icon="solar:pen-new-square-bold-duotone" class="text-lg"></iconify-icon>
                                        </button>
                                        <button onclick="confirmAction(event, '<?= $u['id'] ?>', 'Hapus Akun?', 'Semua data milik akun ini akan terhapus!')" class="text-red-400 hover:text-red-600 bg-red-50 w-9 h-9 flex items-center justify-center rounded-xl border border-red-100 transition shadow-sm">
                                            <iconify-icon icon="solar:trash-bin-trash-bold-duotone" class="text-lg"></iconify-icon>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="6" class="p-8 text-center text-gray-400 italic">Belum ada data mempelai.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
                <?= $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL' ?>
            </footer>
        </div>
    </main>

    <!-- Modal Edit -->
    <div id="editModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeEditModal()"></div>
        <div class="relative bg-white w-full max-w-md rounded-xl p-8 shadow-2xl border border-[#e8e1d5]">
            <div class="flex justify-between items-center mb-6 border-b border-[#e8e1d5] pb-4">
                <h3 class="text-xl font-bold text-[#000000] font-serif flex items-center gap-2">
                    <iconify-icon icon="solar:pen-new-square-bold-duotone" class="text-[#C5A880]"></iconify-icon> Edit Akun Mempelai
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-red-500 transition"><iconify-icon icon="solar:close-circle-bold-duotone" class="text-2xl"></iconify-icon></button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <input type="hidden" name="id_user" id="edit_id">
                <div>
                    <label class="text-[10px] font-bold text-[#C5A880] uppercase mb-1 block">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama" class="w-full bg-[#ffffff] border border-[#e8e1d5] p-3 rounded-xl text-sm focus:ring-2 focus:ring-[#C5A880] outline-none" required>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-[#C5A880] uppercase mb-1 block">Username</label>
                    <input type="text" name="username" id="edit_user" class="w-full bg-[#ffffff] border border-[#e8e1d5] p-3 rounded-xl text-sm font-mono focus:ring-2 focus:ring-[#C5A880] outline-none" required>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-[#C5A880] uppercase mb-1 block">ID Post</label>
                    <input type="number" name="post_id" id="edit_post_id" class="w-full bg-[#ffffff] border border-[#e8e1d5] p-3 rounded-xl text-sm font-bold text-center focus:ring-2 focus:ring-[#C5A880] outline-none" required>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-[#C5A880] uppercase mb-1 block">Limit Event</label>
                        <input type="number" name="event_limit" id="edit_limit" min="1" class="w-full bg-[#ffffff] border border-[#e8e1d5] p-3 rounded-xl text-sm font-bold text-center focus:ring-2 focus:ring-[#C5A880] outline-none" required>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-[#C5A880] uppercase mb-1 block">Password (Opsional)</label>
                        <input type="password" name="password" placeholder="••••" class="w-full bg-[#ffffff] border border-[#e8e1d5] p-3 rounded-xl text-sm focus:ring-2 focus:ring-[#C5A880] outline-none">
                    </div>
                </div>
                <div class="flex gap-3 pt-4 border-t border-[#e8e1d5]">
                    <button type="button" onclick="closeEditModal()" class="flex-1 py-3 bg-white border border-[#e8e1d5] text-[#8d6e63] rounded-xl font-bold text-sm">Batal</button>
                    <button type="submit" name="edit_mempelai" class="flex-1 py-3 bg-[#C5A880] text-white rounded-xl font-bold text-sm shadow-lg flex items-center justify-center gap-1">
                        <iconify-icon icon="solar:diskette-bold-duotone" class="text-lg"></iconify-icon> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Hapus Hidden -->
    <form id="formHapus" method="POST" style="display:none;">
        <?= csrf_field() ?>
        <input type="hidden" name="hapus_mempelai" value="1">
        <input type="hidden" name="id_user" id="hapus_id">
    </form>

    <script>
    const Toast = Swal.mixin({toast: true, position: 'top-end', showConfirmButton: false, timer: 3000, timerProgressBar: true, background: '#fffcf9', color: '#000000', iconColor: '#C5A880', customClass: {popup: 'rounded-xl border border-[#e8e1d5] shadow-lg', timerProgressBar: 'bg-[#C5A880]'}});
    const ModalAlert = Swal.mixin({customClass: {popup: 'rounded-xl border border-[#e8e1d5] shadow-xl', title: 'font-serif text-[#000000] text-xl', htmlContainer: 'text-[#000000] text-sm leading-relaxed', confirmButton: 'bg-[#000000] text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-[#4e342e] focus:outline-none transition-all', cancelButton: 'bg-white text-gray-500 border border-[#e8e1d5] px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-50 focus:outline-none transition-all', actions: 'gap-3 flex-row-reverse'}, buttonsStyling: false, background: '#fffcf9', color: '#000000', iconColor: '#C5A880'});

    <?= $swal_script ?>

    function confirmAction(e, id, t, tx) {
        e.preventDefault();
        ModalAlert.fire({
            title: t,
            text: tx,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then((r) => {
            if (r.isConfirmed) {
                document.getElementById('hapus_id').value = id;
                document.getElementById('formHapus').submit();
            }
        });
    }

    function openEditModal(data) {
        let [id, nama, user, limit, postId] = data;
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_user').value = user;
        document.getElementById('edit_limit').value = limit;
        document.getElementById('edit_post_id').value = postId;
        document.getElementById('editModal').classList.remove('hidden');
        document.body.classList.add('modal-active');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
        document.body.classList.remove('modal-active');
    }
    </script>
</body>
</html>