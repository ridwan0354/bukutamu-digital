<?php
// 1. Cek Session Aman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'koneksi.php';
require_login();
check_csrf();

$user_id = $_SESSION['user_id'];
$pesan = "";
$status_pesan = "success";

// 3. AMBIL DATA USER SAAT INI
$query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$user_id'");
$user_data = mysqli_fetch_assoc($query_user);

// 4. PROSES UPDATE PROFIL & PASSWORD
if (isset($_POST['update_profile'])) {
    $nama_lengkap = esc($_POST['nama_lengkap']);
    $username = esc($_POST['username']);
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    // Cek duplikat username (kecuali milik sendiri)
    $cek_username = mysqli_query($koneksi, "SELECT id FROM users WHERE username = '$username' AND id != '$user_id'");
    if (mysqli_num_rows($cek_username) > 0) {
        $pesan = "Gagal! Username sudah digunakan oleh orang lain.";
        $status_pesan = "error";
    } else {
        // Update Identitas Dasar
        $update_query = "UPDATE users SET nama_lengkap = '$nama_lengkap', username = '$username'";
        
        if($user_data['role'] == 'mempelai' && isset($_POST['post_id'])) {
            $post_id = (int)$_POST['post_id'];
            $update_query .= ", post_id = '$post_id'";
        }
        
        $update_query .= " WHERE id = '$user_id'";
        
        if (mysqli_query($koneksi, $update_query)) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            $_SESSION['username'] = $username;
            $pesan = "Profil berhasil diperbarui!";
            
            // Jika Password diisi
            if (!empty($password_baru)) {
                if ($password_baru === $konfirmasi_password) {
                    // Gunakan password_hash untuk keamanan modern
                    $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
                    mysqli_query($koneksi, "UPDATE users SET password = '$hashed_password' WHERE id = '$user_id'");
                    $pesan .= " Password juga berhasil diganti.";
                } else {
                    $pesan = "Profil diperbarui, tapi konfirmasi password tidak cocok!";
                    $status_pesan = "error";
                }
            }
        } else {
            $pesan = "Gagal memperbarui profil: " . mysqli_error($koneksi);
            $status_pesan = "error";
        }
    }
}
    // Refresh data user
    $query_user = mysqli_query($koneksi, "SELECT * FROM users WHERE id = '$user_id'");
    $user_data = mysqli_fetch_assoc($query_user);
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - <?= $user_data['nama_lengkap'] ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; background-image: url("https://www.transparenttextures.com/patterns/cream-paper.png"); }
        .font-serif { font-family: 'Playfair Display', serif; }
    </style>
</head>
<body class="text-[#1a0f0d]">

    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <main class="md:ml-64 p-4 lg:p-10">
        <div class="max-w-4xl mx-auto">
            
            <div class="mb-8 flex items-center gap-4">
                <div class="w-16 h-16 bg-[#87714c] text-white rounded-2xl flex items-center justify-center text-3xl shadow-lg border-4 border-white">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-serif font-bold text-[#1a0f0d]">Profil & Keamanan</h1>
                    <p class="text-[#87714c]">Kelola informasi akun dan kata sandi Anda</p>
                </div>
            </div>

            <?php if(!empty($pesan)): ?>
            <script>
                Swal.fire({
                    title: '<?= $status_pesan == "success" ? "Berhasil!" : "Opps!" ?>',
                    text: '<?= $pesan ?>',
                    icon: '<?= $status_pesan ?>',
                    confirmButtonColor: '#87714c'
                });
            </script>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Info Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-3xl border border-[#e8e1d5] shadow-sm text-center">
                        <div class="w-24 h-24 bg-[#faf7f0] rounded-full mx-auto mb-4 flex items-center justify-center border-2 border-[#87714c] text-[#87714c] overflow-hidden">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_data['nama_lengkap']) ?>&background=87714c&color=fff&size=128" alt="Avatar">
                        </div>
                        <h2 class="font-bold text-xl text-[#1a0f0d]"><?= $user_data['nama_lengkap'] ?></h2>
                        <p class="text-xs font-bold text-[#87714c] uppercase tracking-widest mt-1"><?= ['admin' => 'Administrator', 'mempelai' => 'Member', 'receptionist' => 'Resepsionis'][$user_data['role']] ?? 'Member' ?></p>
                        
                        <div class="mt-6 pt-6 border-t border-[#f3e9d8] space-y-3 text-left">
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-id-badge text-[#87714c] w-5"></i>
                                <span class="text-gray-500">ID: <?= $user_data['id'] ?></span>
                            </div>
                            <div class="flex items-center gap-3 text-sm">
                                <i class="fas fa-user-circle text-[#87714c] w-5"></i>
                                <span class="text-gray-500">@<?= $user_data['username'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="lg:col-span-2">
                    <div class="bg-white p-6 md:p-8 rounded-3xl border border-[#e8e1d5] shadow-sm">
                        <form action="" method="POST" class="space-y-6">
                            <?= csrf_field() ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-[#1a0f0d] mb-2">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" value="<?= $user_data['nama_lengkap'] ?>" required
                                           class="w-full px-4 py-3 rounded-xl border border-[#e8e1d5] focus:ring-2 focus:ring-[#87714c]/20 focus:border-[#87714c] outline-none transition">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-[#1a0f0d] mb-2">Username</label>
                                    <input type="text" name="username" value="<?= $user_data['username'] ?>" required
                                           class="w-full px-4 py-3 rounded-xl border border-[#e8e1d5] focus:ring-2 focus:ring-[#87714c]/20 focus:border-[#87714c] outline-none transition">
                                </div>
                                <?php if($user_data['role'] == 'mempelai'): ?>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-[#1a0f0d] mb-2">ID Post WordPress (Untuk Ucapan)</label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-[#87714c]">
                                            <i class="fas fa-link"></i>
                                        </div>
                                        <input type="number" name="post_id" value="<?= $user_data['post_id'] ?? 0 ?>" required
                                            class="w-full pl-12 pr-4 py-3 rounded-xl border border-[#e8e1d5] focus:ring-2 focus:ring-[#87714c]/20 focus:border-[#87714c] outline-none transition font-bold" 
                                            placeholder="Contoh: 123">
                                    </div>
                                    <p class="text-[10px] text-[#87714c] mt-1.5 ml-1">ID ini digunakan untuk menyinkronkan ucapan & doa dari platform WordPress Anda.</p>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="pt-6 border-t border-[#f3e9d8]">
                                <h3 class="font-bold text-[#87714c] mb-4 flex items-center gap-2">
                                    <i class="fas fa-key"></i> Ganti Password (Opsional)
                                </h3>
                                <p class="text-[10px] text-gray-400 mb-4 italic leading-tight">Kosongkan jika tidak ingin mengganti password.</p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-bold text-[#1a0f0d] mb-2">Password Baru</label>
                                        <input type="password" name="password_baru" id="pw1" placeholder="••••••••"
                                               class="w-full px-4 py-3 rounded-xl border border-[#e8e1d5] focus:ring-2 focus:ring-[#87714c]/20 focus:border-[#87714c] outline-none transition">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-[#1a0f0d] mb-2">Konfirmasi Password</label>
                                        <input type="password" name="konfirmasi_password" id="pw2" placeholder="••••••••"
                                               class="w-full px-4 py-3 rounded-xl border border-[#e8e1d5] focus:ring-2 focus:ring-[#87714c]/20 focus:border-[#87714c] outline-none transition">
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 flex flex-col md:flex-row gap-4">
                                <button type="submit" name="update_profile" 
                                        class="flex-1 bg-[#87714c] hover:bg-[#1a0f0d] text-white font-bold py-4 rounded-2xl shadow-lg shadow-[#87714c]/20 transition transform active:scale-95">
                                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                </button>
                                <a href="dashboard" 
                                   class="px-8 py-4 bg-[#faf7f0] text-[#87714c] font-bold rounded-2xl border border-[#e8e1d5] hover:bg-[#f3e9d8] transition text-center">
                                    Batal
                                </a>
                            </div>

                        </form>
                    </div>
                </div>

            </div>

        </div>
        <script>
            document.querySelector('form').onsubmit = function(e) {
                const pw1 = document.getElementById('pw1').value;
                const pw2 = document.getElementById('pw2').value;
                if(pw1 !== "" && pw1 !== pw2) {
                    Swal.fire('Opps!', 'Konfirmasi password tidak cocok!', 'error');
                    return false;
                }
                return true;
            };
        </script>

        <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
        </footer>
    </main>
</body></html>
