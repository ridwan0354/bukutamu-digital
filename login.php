<?php
session_start();
require 'koneksi.php';

// 1. AMBIL KONFIGURASI
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$config = mysqli_fetch_assoc($query_setting);

// Default Config
if (!$config) {
    $config = [
        'app_name' => 'GuestBook App', 'logo_text' => 'GB', 'logo_dashboard' => '', 
        'hero_title' => 'Sistem Buku Tamu<br>Digital', 'hero_desc' => 'Mohon isi tabel pengaturan.', 
        'btn_text' => 'Info', 'btn_link' => '#', 'copyright' => 'Â© 2026 GuestBook'
    ];
}

// Logo Logic: Jika ada file upload pakai itu, jika tidak pakai teks
$logo_src = !empty($config['logo_dashboard']) ? "assets/".$config['logo_dashboard'] : null;

// 2. AMBIL SOSMED
$q_sosmed = mysqli_query($koneksi, "SELECT * FROM social_media");

// 3. PROSES LOGIN
if (isset($_POST['login'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Sesi tidak valid atau telah kedaluwarsa. Silakan muat ulang halaman.";
    } else {
        $username = esc($_POST['username']);
        $raw_password = $_POST['password'];
    
    // Cari user berdasarkan username saja dulu
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        
        // Cek password menggunakan password_verify (modern) ATAU md5 (kompatibilitas admin lama)
        if (password_verify($raw_password, $data['password']) || md5($raw_password) === $data['password']) {
            session_regenerate_id(true); // Prevent Session Fixation
            $_SESSION['user_id'] = $data['id']; 
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap']; 
            $_SESSION['role'] = $data['role']; 
            $_SESSION['parent_id'] = $data['parent_id'] ?? 0;
            $_SESSION['status'] = "login";
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $config['app_name'] ?> - Login</title>
<?php if(!empty($config['favicon'])): ?>
    <link rel="icon" href="assets/<?= $config['favicon'] ?>?v=<?= time() ?>">
<?php endif; ?>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #ffffff url("https://www.transparenttextures.com/patterns/cream-paper.png");
}
.font-serif { font-family: 'Playfair Display', serif; }
</style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 md:p-6">

<div class="w-full max-w-6xl bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2">

    <!-- HERO / INFO -->
    <section class="bg-[#000000] text-white p-8 md:p-12 flex flex-col justify-between relative">
        <div class="absolute -top-20 -left-20 w-72 h-72 bg-[#EAB676]/20 rounded-full blur-3xl"></div>

        <!-- LOGO -->
        <div class="relative z-10">
            <?php if($logo_src && file_exists($logo_src)): ?>
                <img src="<?= $logo_src ?>" class="w-20 h-20 object-contain bg-white/10 p-3 rounded-2xl">
            <?php else: ?>
                <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center font-serif text-2xl font-bold text-[#87714c]">
                    <?= substr($config['logo_text'] ?? 'GB', 0, 2) ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TEXT -->
        <div class="relative z-10 my-10">
            <h1 class="font-serif text-3xl md:text-4xl lg:text-5xl leading-tight mb-4">
                <?= $config['hero_title'] ?>
            </h1>
            <p class="text-[#e8e1d5] text-sm md:text-base max-w-md">
                <?= $config['hero_desc'] ?>
            </p>

            <a href="<?= $config['btn_link'] ?>"
               class="inline-flex items-center gap-2 mt-6 px-6 py-3 border border-[#EAB676] text-[#87714c] rounded-xl hover:bg-[#EAB676] hover:text-white transition">
                <?= $config['btn_text'] ?>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <!-- SOSMED -->
        <div class="relative z-10">
            <p class="text-xs uppercase tracking-widest mb-3 text-[#d7ccc8]">Temukan Kami</p>
            <div class="flex gap-3 flex-wrap">
                <?php while($s = mysqli_fetch_assoc($q_sosmed)): ?>
                    <a href="<?= $s['link_url'] ?>" target="_blank"
                       class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow hover:-translate-y-1 transition">
                        <img src="<?= $s['icon_url'] ?>" class="w-5 h-5">
                    </a>
                <?php endwhile; ?>
            </div>
            <p class="text-xs mt-6 text-[#d7ccc8]"><?= $config['copyright'] ?></p>
        </div>
    </section>

    <!-- LOGIN FORM -->
    <section class="p-8 md:p-12 flex items-center">
        <div class="w-full max-w-md mx-auto">
            <h2 class="font-serif text-3xl text-[#1a0f0d] mb-2">Welcome Back 👋</h2>
            <p class="text-[#87714c] mb-6">Silakan login untuk melanjutkan</p>

            <?php if(isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-600 p-4 mb-5 rounded">
                <?= $error ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label class="text-sm font-semibold text-[#1a0f0d]">Username</label>
                    <input name="username" required
       placeholder="Masukkan username"
       class="w-full mt-1 px-4 py-3 rounded-xl border focus:ring focus:ring-[#EAB676]/20 outline-none">
                </div>
                <div>
                    <label class="text-sm font-semibold text-[#1a0f0d]">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
       placeholder="Masukkan password"
       class="w-full mt-1 px-4 py-3 rounded-xl border focus:ring focus:ring-[#EAB676]/20 outline-none">
                        <span onclick="togglePassword()"
                              class="absolute right-4 top-4 cursor-pointer text-gray-400">
                            <i class="fas fa-eye" id="eye-icon"></i>
                        </span>
                    </div>
                </div>

                <button name="login"
                        class="w-full bg-[#87714c] hover:bg-[#b08d55] text-white font-bold py-4 rounded-xl transition">
                    MASUK APLIKASI
                </button>
            </form>
        </div>
    </section>

</div>

<script>
function togglePassword() {
    const input = document.getElementById("password");
    const icon = document.getElementById("eye-icon");
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}
</script>

</body>
</html>
