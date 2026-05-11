<?php
$page = basename($_SERVER['PHP_SELF']);
// Ambil role dari session
$role = $_SESSION['role'] ?? 'mempelai';

// Ambil Config jika belum ada
if(!isset($config)){
    require_once 'koneksi.php';
    $q_conf = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
    $config = mysqli_fetch_assoc($q_conf);
}

// LOGIKA LINK HOME
$home_url = 'dashboard.php';

// LOGIKA LOGO (BARU)
$logo_path = 'assets/' . ($config['logo_dashboard'] ?? '');
$has_logo  = !empty($config['logo_dashboard']) && file_exists($logo_path);
?>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>

<style>
    /* Custom Colors untuk Sidebar */
    .bg-gold { background-color: #87714c; }
    .text-gold { color: #87714c; }
    .border-gold { border-color: #87714c; }
    .text-brown { color: #1a0f0d; }
    .bg-cream { background-color: #ffffff; }
    .hover-cream:hover { background-color: #f9f6f0; }
    .font-serif { font-family: 'Playfair Display', serif; }
    
    /* Shadow Halus Emas */
    .shadow-gold { box-shadow: 0 4px 15px -3px rgba(135, 113, 76, 0.3); }
    .shadow-gold-sm { box-shadow: 0 2px 10px -2px rgba(135, 113, 76, 0.2); }

    /* ANIMASI PULSE UNTUK SCAN */
    @keyframes pulse-gold {
        0% { box-shadow: 0 0 0 0 rgba(135, 113, 76, 0.7); }
        70% { box-shadow: 0 0 0 15px rgba(135, 113, 76, 0); }
        100% { box-shadow: 0 0 0 0 rgba(135, 113, 76, 0); }
    }
    .animate-pulse-gold {
        animation: pulse-gold 2s infinite;
    }

    /* Global Scrollbar Styles */
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #87714c; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background-color: #ffffff; }
    
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<nav class="bg-white border-b border-[#e8e1d5] fixed w-full z-40 top-0 h-16 transition-all shadow-sm">
    <div class="px-4 lg:px-6 flex justify-between items-center h-full">
        
        <a href="<?= $home_url ?>" class="flex items-center gap-3 hover:opacity-80 transition group">
            
            <?php if($has_logo): ?>
                <img src="<?= $logo_path ?>" alt="Logo App" class="w-9 h-9 md:w-10 md:h-10 object-contain bg-white rounded-xl shadow-sm border border-[#e8e1d5] p-0.5 group-hover:scale-105 transition-transform">
            <?php else: ?>
                <div class="bg-gold text-white w-9 h-9 md:w-10 md:h-10 rounded-xl flex items-center justify-center font-bold text-sm md:text-base shadow-gold group-hover:scale-105 transition-transform">
                    <?= substr($config['app_name'] ?? 'BT', 0, 2) ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col">
                <span class="font-serif font-bold text-lg text-brown tracking-tight leading-tight">
                    <?= $config['app_name'] ?? 'GuestBook' ?>
                </span>
                <span class="text-[10px] text-gray-400 hidden md:block tracking-wide">Digital Guest Book System</span>
            </div>
        </a>

        <div class="flex items-center gap-3">
            <div class="text-right hidden md:block">
                <p class="text-sm font-bold text-brown"><?= $_SESSION['nama_lengkap'] ?? 'Admin' ?></p>
                <p class="text-[10px] text-gold uppercase tracking-widest font-bold opacity-80"><?= $role ?></p>
            </div>
            <a href="profil.php" class="w-9 h-9 bg-[#faf7f0] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] hover:bg-[#87714c] hover:text-white transition shadow-sm" title="Profil Saya">
                <iconify-icon icon="solar:user-bold-duotone" class="text-xl"></iconify-icon>
            </a>
            <a href="#" onclick="confirmLogout(event)" class="text-gray-400 hover:text-red-500 transition ml-1 p-2 hidden md:block" title="Keluar">
                <iconify-icon icon="solar:logout-bold-duotone" class="text-2xl"></iconify-icon>
            </a>
        </div>
    </div>
</nav>

<aside class="hidden md:block w-64 bg-white border-r border-[#e8e1d5] fixed h-full left-0 top-16 overflow-y-auto z-30 pb-20 scrollbar-hide">
    <div class="p-4">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Main Menu</p>
        <nav class="space-y-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'dashboard.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:widget-3-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Dashboard</span>
            </a>
            
            <a href="event.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'event.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:calendar-date-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Event</span>
            </a>

            <a href="hadiah.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'hadiah.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:gift-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Konfirmasi Hadiah</span>
            </a>

            <a href="tamu.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'tamu.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Daftar Tamu</span>
            </a>
            
            <a href="broadcast.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'broadcast.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:mailbox-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Setting Pesan</span>
            </a>
            
            <a href="qr.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'qr.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:qr-code-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">QR Generator</span>
            </a>

            <a href="ucapan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'ucapan.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:chat-round-dots-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Ucapan & Doa</span>
            </a>
            
            <a href="report.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'report.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:chart-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Laporan</span>
            </a>

            <?php if($role == 'admin'): ?>
            <hr class="my-3 border-[#f0ebe3]">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Admin Control</p>
            
            <a href="kelola_mempelai.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'kelola_mempelai.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:heart-bold-duotone" class="text-xl text-pink-400"></iconify-icon> <span class="text-sm">Kelola Mempelai</span>
            </a>
            
            <a href="pengaturan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'pengaturan.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:settings-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Settings</span>
            </a>
            <?php endif; ?>
            
            <a href="petunjuk.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl font-medium transition-all <?= ($page == 'petunjuk.php') ? 'bg-gold text-white shadow-gold' : 'text-brown hover-cream hover:text-gold' ?>">
                <iconify-icon icon="solar:info-square-bold-duotone" class="text-xl"></iconify-icon> <span class="text-sm">Panduan Aplikasi</span>
            </a>
        </nav>
    </div>
</aside>

<!-- MOBILE NAVIGATION -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 w-full bg-white border-t border-[#e8e1d5] shadow-[0_-5px_15px_rgba(0,0,0,0.03)] z-[50] h-[70px]">
    <div class="grid grid-cols-5 h-full items-center">
        
        <!-- HOME -->
        <?php $is_home = ($page == 'dashboard.php' || $page == 'home_mempelai.php' || $page == 'index.php'); ?>
        <a href="<?= $home_url ?>" class="flex flex-col items-center justify-center gap-1 h-full <?= $is_home ? 'text-[#87714c]' : 'text-gray-400' ?> hover:text-[#87714c]">
            <iconify-icon icon="solar:home-2-bold-duotone" class="text-2xl"></iconify-icon>
            <span class="text-[10px] font-bold">Home</span>
        </a>

        <!-- EVENT -->
        <?php $is_event = ($page == 'event.php'); ?>
        <a href="event.php" class="flex flex-col items-center justify-center gap-1 h-full <?= $is_event ? 'text-[#87714c]' : 'text-gray-400' ?> hover:text-[#87714c]">
            <iconify-icon icon="solar:calendar-minimalistic-bold-duotone" class="text-2xl"></iconify-icon>
            <span class="text-[10px] font-bold">Event</span>
        </a>

        <div class="relative flex flex-col items-center justify-center h-full">
            <!-- SCAN ADA PULSE DISINI -->
            <button onclick="if(typeof startCamera === 'function'){ startCamera() } else { window.location.href='dashboard?scan=true' }" 
                    class="absolute -top-6 w-14 h-14 bg-gold rounded-full text-white shadow-gold flex items-center justify-center border-[4px] border-white transform active:scale-90 transition-all duration-200 animate-pulse-gold">
                <iconify-icon icon="solar:scanner-2-bold-duotone" class="text-2xl"></iconify-icon>
            </button>
            <span class="mt-8 text-[10px] font-bold text-[#87714c] uppercase tracking-tighter">Scan</span>
        </div>

        <!-- TAMU -->
        <?php $is_tamu = ($page == 'tamu.php'); ?>
        <a href="tamu.php" class="flex flex-col items-center justify-center gap-1 h-full <?= $is_tamu ? 'text-[#87714c]' : 'text-gray-400' ?> hover:text-[#87714c]">
            <iconify-icon icon="solar:users-group-rounded-bold-duotone" class="text-2xl"></iconify-icon>
            <span class="text-[10px] font-bold">Tamu</span>
        </a>

        <!-- MENU (Active if others are open) -->
        <?php $is_others = !($is_home || $is_event || $is_tamu); ?>
        <button onclick="toggleMobileMenu()" class="flex flex-col items-center justify-center gap-1 h-full <?= $is_others ? 'text-[#87714c]' : 'text-gray-400' ?> hover:text-[#87714c]">
            <iconify-icon icon="solar:hamburger-menu-bold-duotone" class="text-2xl"></iconify-icon>
            <span class="text-[10px] font-bold">Menu</span>
        </button>

    </div>
</nav>

<div id="mobileMenuOverlay" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="toggleMobileMenu()"></div>
    
    <div class="absolute bottom-0 w-full bg-[#ffffff] rounded-t-3xl p-6 shadow-2xl animate-slide-up">
        <div class="flex justify-center mb-4">
            <div class="w-10 h-1 bg-gray-300 rounded-full opacity-50"></div>
        </div>
        
        <h3 class="text-center font-bold text-brown mb-6 uppercase tracking-widest text-[11px] font-serif">System Menu</h3>
        
        <div class="grid grid-cols-4 gap-4 mb-6">

            <a href="broadcast.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-[#faf7f0] text-gold rounded-xl flex items-center justify-center text-xl shadow-sm border border-[#e8e1d5] transition active:scale-95">
                    <iconify-icon icon="solar:mailbox-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Pesan WA</span>
            </a>

            <a href="hadiah.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-[#faf7f0] text-gold rounded-xl flex items-center justify-center text-xl shadow-sm border border-[#e8e1d5] transition active:scale-95">
                    <iconify-icon icon="solar:gift-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Hadiah</span>
            </a>

            <a href="profil.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl shadow-sm border border-blue-100 transition active:scale-95">
                    <iconify-icon icon="solar:user-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Profil Saya</span>
            </a>

            <a href="qr.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-[#faf7f0] text-gold rounded-xl flex items-center justify-center text-xl shadow-sm border border-[#e8e1d5] transition active:scale-95">
                    <iconify-icon icon="solar:qr-code-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">QR Gen</span>
            </a>

            <a href="ucapan.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-[#faf7f0] text-gold rounded-xl flex items-center justify-center text-xl shadow-sm border border-[#e8e1d5] transition active:scale-95">
                    <iconify-icon icon="solar:chat-round-dots-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Ucapan</span>
            </a>

            <a href="report.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl shadow-sm border border-green-100 transition active:scale-95">
                    <iconify-icon icon="solar:chart-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Laporan</span>
            </a>

            <?php if($role == 'admin'): ?>
            <a href="kelola_mempelai.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-pink-50 text-pink-500 rounded-xl flex items-center justify-center text-xl border border-pink-100 shadow-sm transition active:scale-95">
                    <iconify-icon icon="solar:heart-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Mempelai</span>
            </a>

            <a href="pengaturan.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center text-xl shadow-sm border border-gray-200 transition active:scale-95">
                    <iconify-icon icon="solar:settings-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Settings</span>
            </a>
            <?php endif; ?>

            <a href="petunjuk.php" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-xl shadow-sm border transition active:scale-95 <?= ($page == 'petunjuk.php') ? 'bg-gold text-white border-gold shadow-gold' : 'bg-amber-50 text-amber-600 border-amber-100' ?>">
                    <iconify-icon icon="solar:notebook-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Panduan</span>
            </a>

            <a href="#" onclick="confirmLogout(event)" class="flex flex-col items-center gap-2">
                <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-xl shadow-sm border border-red-100 transition active:scale-95">
                    <iconify-icon icon="solar:logout-bold-duotone"></iconify-icon>
                </div>
                <span class="text-[9px] font-bold text-brown truncate w-full text-center">Logout</span>
            </a>
        </div>
        
        <button onclick="toggleMobileMenu()" class="w-full py-3 bg-white border border-[#e8e1d5] rounded-2xl text-[12px] font-bold text-gray-400 hover:bg-gray-50 transition">Kembali</button>
    </div>
</div>

<div id="modalPanduan" class="fixed inset-0 z-[100] hidden flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-[#1a0f0d]/80 backdrop-blur-sm" onclick="togglePanduan()"></div>
    <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-slide-up max-h-[90vh] flex flex-col">
        
        <div class="p-6 pb-2 text-center">
            <h2 class="text-xl md:text-2xl font-serif font-black text-[#1a0f0d] tracking-tight">Alur Penggunaan Aplikasi</h2>
            <div class="w-12 h-1 bg-[#87714c] mx-auto mt-2 rounded-full"></div>
        </div>

        <div class="flex-1 overflow-y-auto custom-scrollbar px-6 py-4">
            <div class="space-y-5 relative">
                <!-- Garis Vertikal -->
                <div class="absolute left-[9px] top-2 bottom-2 w-0.5 bg-[#e8e1d5] -z-0"></div>

                <!-- Step 1 -->
                <div class="flex gap-4 relative z-10">
                    <div class="w-5 h-5 rounded-full bg-[#87714c] border-4 border-white shadow-sm shrink-0"></div>
                    <div>
                        <h4 class="font-black text-[#1a0f0d] text-sm">1. Buat & Aktifkan Acara</h4>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Masuk ke menu <span class="font-bold text-[#87714c]">Event</span>. Tambahkan acara baru sesuai hari & tanggal anda.</p>
                        <div class="mt-1.5 inline-flex items-center gap-2 bg-amber-50 text-amber-700 text-[9px] font-black px-2 py-0.5 rounded-lg border border-amber-100 uppercase tracking-wider">
                            PENTING
                        </div>
                        <span class="text-[11px] text-gray-400 ml-1 italic leading-tight">Pastikan status event "Aktif" agar data masuk ke database.</span>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="flex gap-4 relative z-10">
                    <div class="w-5 h-5 rounded-full bg-[#d1c7b7] border-4 border-white shadow-sm shrink-0"></div>
                    <div>
                        <h4 class="font-black text-[#1a0f0d] text-sm">2. Atur Template Pesan</h4>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Ke menu <span class="font-bold text-[#87714c]">Setting Pesan</span>. Tulis undangan. Gunakan variabel <span class="font-bold text-[#1a0f0d]">[nama]</span> agar personal otomatis.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="flex gap-4 relative z-10">
                    <div class="w-5 h-5 rounded-full bg-[#d1c7b7] border-4 border-white shadow-sm shrink-0"></div>
                    <div>
                        <h4 class="font-black text-[#1a0f0d] text-sm">3. Input Tamu & Sebar WA</h4>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Ke menu <span class="font-bold text-[#87714c]">Daftar Tamu</span>. Input manual/ <span class="font-bold text-[#1a0f0d]">Import Excel</span>. Klik tombol <iconify-icon icon="logos:whatsapp-icon" class="inline-block align-middle"></iconify-icon> untuk kirim QR Code.</p>
                    </div>
                </div>

                <!-- Step 4 -->
                <div class="flex gap-4 relative z-10">
                    <div class="w-5 h-5 rounded-full bg-[#d1c7b7] border-4 border-white shadow-sm shrink-0"></div>
                    <div>
                        <h4 class="font-black text-[#1a0f0d] text-sm">4. Persiapan Layar Sapa</h4>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Laptop/PC hubungkan ke Proyektor. Buka menu <span class="font-bold text-[#87714c]">Layar Sapa</span>. Halaman ini akan menyapa tamu saat di-scan.</p>
                    </div>
                </div>

                <!-- Step 5 -->
                <div class="flex gap-4 relative z-10">
                    <div class="w-5 h-5 rounded-full bg-[#d1c7b7] border-4 border-white shadow-sm shrink-0"></div>
                    <div>
                        <h4 class="font-black text-[#1a0f0d] text-sm">5. Proses Check-in (Scan)</h4>
                        <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">Gunakan HP ini. Klik tombol <span class="font-bold text-[#87714c]">SCAN QR</span> di bawah. Arahkan ke QR Tamu. Data otomatis tampil di layar.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 pt-2">
            <button onclick="togglePanduan()" class="w-full bg-[#4e342e] hover:bg-[#1a0f0d] text-white font-black py-3.5 rounded-2xl text-sm shadow-xl shadow-brown/20 transition-all active:scale-95">Saya Mengerti</button>
        </div>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenuOverlay');
    if(menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; 
    } else {
        menu.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

function togglePanduan() {
    const modal = document.getElementById('modalPanduan');
    if(modal.classList.contains('hidden')) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; 
    } else {
        modal.classList.add('hidden');
        if(!document.getElementById('mobileMenuOverlay').classList.contains('hidden')) {
             document.body.style.overflow = 'hidden'; 
        } else {
             document.body.style.overflow = 'auto';
        }
    }
}

function confirmLogout(e) {
    e.preventDefault();
    Swal.fire({
        title: 'Keluar Aplikasi?',
        text: "Anda harus login kembali untuk masuk.",
        icon: 'warning',
        iconColor: '#87714c',
        showCancelButton: true,
        confirmButtonColor: '#87714c',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Keluar',
        cancelButtonText: 'Batal',
        background: '#fffcf9'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    })
}
</script>

<style>
    /* Prevent content overlap */
    @media (min-width: 768px) { body { padding-top: 64px; } }
    @media (max-width: 767px) { body { padding-top: 64px; padding-bottom: 80px !important; } }

    /* Mobile Slide Up Animation */
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .animate-slide-up { animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1) forwards; }
</style>