<?php
session_start();
require_once 'koneksi.php';

// Cek login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login");
    exit;
}

// Ambil Config
$q_conf = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$config = mysqli_fetch_assoc($q_conf);

$page_title = "Petunjuk Penggunaan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= $config['app_name'] ?? 'GuestBook' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .bg-gold { background-color: #87714c; }
        .text-gold { color: #87714c; }
        .border-gold { border-color: #87714c; }
        .shadow-gold { box-shadow: 0 10px 25px -5px rgba(135, 113, 76, 0.3); }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
        
        .step-number {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #87714c;
            color: white;
            border-radius: 8px;
            font-weight: 800;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(135, 113, 76, 0.4);
            flex-shrink: 0;
            z-index: 10;
        }

        .step-line {
            position: absolute;
            left: 20px;
            top: 20px;
            bottom: -40px;
            width: 2px;
            background: linear-gradient(to bottom, #d1c7b7, transparent);
            z-index: 0;
        }

        .step-container:last-child .step-line {
            display: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out forwards; }
    </style>
</head>
<body class="bg-[#faf7f0] text-slate-800 min-h-screen pb-20">

    <!-- Sidebar & Header -->
    <?php include 'sidebar.php'; ?>

    <main class="md:ml-64 pt-10 px-4 md:px-8 max-w-5xl mx-auto">
        
        <!-- Header Section -->
        <div class="mb-10 text-center md:text-left animate-fade-in">
            <h1 class="text-3xl md:text-4xl font-serif font-black text-slate-900 mb-3 tracking-tight">
                Pusat <span class="text-gold italic">Bantuan</span> & Panduan
            </h1>
            <p class="text-slate-500 max-w-2xl">
                Selamat datang di panduan resmi BUKTAM. Ikuti langkah-langkah di bawah ini untuk mengoptimalkan penggunaan sistem buku tamu digital Anda.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left: Flow Penggunaan -->
            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-[#e8e1d5] animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-10 h-10 bg-gold/10 text-gold rounded-lg flex items-center justify-center text-2xl">
                            <iconify-icon icon="solar:route-bold-duotone"></iconify-icon>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Alur Kerja Aplikasi</h2>
                    </div>

                    <div class="space-y-12">
                        <!-- Step 1 -->
                        <div class="flex gap-6 step-container relative">
                            <div class="step-line"></div>
                            <div class="step-number">01</div>
                            <div class="flex-1">
                                <h3 class="font-black text-slate-900 text-lg mb-1">Aktivasi Acara (Event)</h3>
                                <p class="text-slate-500 text-sm leading-relaxed mb-3">
                                    Langkah pertama adalah membuat nama acara. Tanpa acara yang aktif, database tidak akan tahu kemana data harus disimpan.
                                </p>
                                <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 flex gap-3 items-start">
                                    <iconify-icon icon="solar:info-circle-bold-duotone" class="text-amber-500 text-xl shrink-0 mt-0.5"></iconify-icon>
                                    <div class="text-[12px] text-amber-800 font-medium">
                                        <b class="uppercase block mb-1">Penting:</b>
                                        Pastikan tombol <span class="text-gold font-bold">Status Aktif</span> menyala pada event yang sedang berlangsung hari ini.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div class="flex gap-6 step-container relative">
                            <div class="step-line"></div>
                            <div class="step-number">02</div>
                            <div class="flex-1">
                                <h3 class="font-black text-slate-900 text-lg mb-1">Atur Pesan Undangan</h3>
                                <p class="text-slate-500 text-sm leading-relaxed mb-3">
                                    Buka menu <span class="font-bold text-gold">Setting Pesan</span>. Siapkan teks undangan yang akan dikirim ke WhatsApp tamu.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <span class="px-3 py-1 bg-slate-100 rounded-lg text-[11px] font-bold text-slate-600 border border-slate-200">[nama] - Nama Tamu</span>
                                    <span class="px-3 py-1 bg-slate-100 rounded-lg text-[11px] font-bold text-slate-600 border border-slate-200">[link] - Link QR Code</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="flex gap-6 step-container relative">
                            <div class="step-line"></div>
                            <div class="step-number">03</div>
                            <div class="flex-1">
                                <h3 class="font-black text-slate-900 text-lg mb-1">Input Tamu & Sebar QR</h3>
                                <p class="text-slate-500 text-sm leading-relaxed mb-3">
                                    Masukkan data tamu di menu <span class="font-bold text-gold">Daftar Tamu</span>. Anda bisa menggunakan <span class="font-bold text-slate-900 italic">Import Excel</span> untuk mempercepat proses.
                                </p>
                                <div class="bg-[#faf7f0] border border-[#e8e1d5] rounded-xl p-4 flex gap-4 items-center">
                                    <iconify-icon icon="logos:whatsapp-icon" class="text-3xl"></iconify-icon>
                                    <span class="text-[12px] text-slate-600">Klik icon WhatsApp di daftar tamu untuk mengirimkan link QR Code secara instan.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4 -->
                        <div class="flex gap-6 step-container relative">
                            <div class="step-line"></div>
                            <div class="step-number">04</div>
                            <div class="flex-1">
                                <h3 class="font-black text-slate-900 text-lg mb-1">Setup Layar Sapa & Audio</h3>
                                <p class="text-slate-500 text-sm leading-relaxed mb-3">
                                    Buka <span class="font-bold text-gold">Layar Sapa</span> di Laptop/PC yang terhubung ke proyektor. Halaman ini akan menyambut tamu secara otomatis.
                                </p>
                                <div class="bg-red-50 border border-red-100 rounded-xl p-4 flex gap-3 items-start">
                                    <iconify-icon icon="solar:volume-loud-bold-duotone" class="text-red-500 text-xl shrink-0 mt-0.5"></iconify-icon>
                                    <div class="text-[12px] text-red-800 font-medium">
                                        <b class="uppercase block mb-1 text-red-700">Audio Permission:</b>
                                        Browser biasanya memblokir suara otomatis. Klik di mana saja pada halaman <span class="italic font-bold">Layar Sapa</span> agar suara "Selamat Datang" aktif.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 5 -->
                        <div class="flex gap-6 step-container relative">
                            <div class="step-number">05</div>
                            <div class="flex-1">
                                <h3 class="font-black text-slate-900 text-lg mb-1">Proses Check-in (Scan)</h3>
                                <p class="text-slate-500 text-sm leading-relaxed">
                                    Saat hari-H, petugas menggunakan kamera HP untuk memindai QR tamu. Gunakan tombol <span class="font-bold text-gold">SCAN</span> di navigasi bawah HP Anda.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fitur Unggulan -->
                <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-[#e8e1d5] animate-fade-in" style="animation-delay: 0.2s;">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-lg flex items-center justify-center text-2xl">
                            <iconify-icon icon="solar:stars-minimalistic-bold-duotone"></iconify-icon>
                        </div>
                        <h2 class="text-xl font-bold text-slate-900">Fitur Premium</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 bg-blue-50/50 rounded-xl border border-blue-100">
                            <div class="flex items-center gap-2 mb-2">
                                <iconify-icon icon="solar:reorder-bold-duotone" class="text-blue-600"></iconify-icon>
                                <h4 class="font-bold text-sm text-slate-900">Manajemen Event</h4>
                            </div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Kelola banyak acara sekaligus dalam satu dashboard administrator yang rapi.</p>
                        </div>
                        <div class="p-4 bg-emerald-50/50 rounded-xl border border-emerald-100">
                            <div class="flex items-center gap-2 mb-2">
                                <iconify-icon icon="solar:graph-bold-duotone" class="text-emerald-600"></iconify-icon>
                                <h4 class="font-bold text-sm text-slate-900">Analitik Real-time</h4>
                            </div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Pantau jumlah tamu yang sudah hadir vs tidak hadir secara langsung di dashboard.</p>
                        </div>
                        <div class="p-4 bg-amber-50/50 rounded-xl border border-amber-100">
                            <div class="flex items-center gap-2 mb-2">
                                <iconify-icon icon="solar:pen-bold-duotone" class="text-amber-600"></iconify-icon>
                                <h4 class="font-bold text-sm text-slate-900">Digital Wishes</h4>
                            </div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Menyimpan ucapan & doa dari tamu secara digital sebagai kenangan abadi.</p>
                        </div>
                        <div class="p-4 bg-slate-50/50 rounded-xl border border-slate-200">
                            <div class="flex items-center gap-2 mb-2">
                                <iconify-icon icon="solar:document-text-bold-duotone" class="text-slate-600"></iconify-icon>
                                <h4 class="font-bold text-sm text-slate-900">Export Laporan</h4>
                            </div>
                            <p class="text-[11px] text-slate-600 leading-relaxed">Unduh hasil absensi tamu dalam format Excel atau PDF secara instan.</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right: Sidebar Info -->
            <div class="space-y-6">
                
                <!-- Quick Help -->
                <div class="bg-gold rounded-2xl p-6 text-white shadow-gold animate-fade-in" style="animation-delay: 0.3s;">
                    <iconify-icon icon="solar:question-square-bold-duotone" class="text-4xl mb-4 opacity-50"></iconify-icon>
                    <h3 class="text-xl font-bold font-serif mb-2">Butuh Bantuan?</h3>
                    <p class="text-white/80 text-sm mb-6 leading-relaxed">Tim support kami siap membantu anda jika mengalami kendala sistem atau membutuhkan kustomisasi.</p>
                    <a href="https://wa.me/<?= $config['wa_support'] ?? '6282322226900' ?>" target="_blank" class="block w-full bg-white text-gold font-bold py-3 rounded-xl text-center text-sm shadow-xl active:scale-95 transition-all">
                        Hubungi Support
                    </a>
                </div>

                <!-- FAQ Card -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-[#e8e1d5] animate-fade-in" style="animation-delay: 0.4s;">
                    <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <iconify-icon icon="solar:chat-round-check-bold-duotone" class="text-gold"></iconify-icon>
                        Tanya Jawab
                    </h3>
                    <div class="space-y-4">
                        <details class="group">
                            <summary class="list-none cursor-pointer flex justify-between items-center font-bold text-[12px] text-slate-700 hover:text-gold transition">
                                Cara Import Excel?
                                <iconify-icon icon="solar:alt-arrow-down-bold-duotone" class="group-open:rotate-180 transition-transform"></iconify-icon>
                            </summary>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                Di menu Daftar Tamu, klik tombol Import. Download template Excel yang disediakan, isi data, lalu upload kembali.
                            </p>
                        </details>
                        <hr class="border-slate-100">
                        <details class="group">
                            <summary class="list-none cursor-pointer flex justify-between items-center font-bold text-[12px] text-slate-700 hover:text-gold transition">
                                Mengapa suara tidak bunyi?
                                <iconify-icon icon="solar:alt-arrow-down-bold-duotone" class="group-open:rotate-180 transition-transform"></iconify-icon>
                            </summary>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                Browser modern melarang suara diputar tanpa interaksi user. Anda harus melakukan klik 1x di manapun setelah halaman Layar Sapa terbuka.
                            </p>
                        </details>
                        <hr class="border-slate-100">
                        <details class="group">
                            <summary class="list-none cursor-pointer flex justify-between items-center font-bold text-[12px] text-slate-700 hover:text-gold transition">
                                Batas jumlah tamu?
                                <iconify-icon icon="solar:alt-arrow-down-bold-duotone" class="group-open:rotate-180 transition-transform"></iconify-icon>
                            </summary>
                            <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                                Tidak ada batasan sistem. Namun disarankan maksimal 5.000 tamu per event agar performa database tetap optimal.
                            </p>
                        </details>
                    </div>
                </div>

                <!-- Footer Info -->
                <div class="text-center p-4">
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mb-1">Built for Excellence</p>
                    <p class="text-[11px] text-gold font-serif italic">Digital Guest Book System v2.1</p>
                </div>

            </div>

        </div>

    </main>

    <!-- Script tambahan jika butuh interactivity -->
    <script>
        // Smooth scroll atau interaksi lain
    </script>
</body>
</html>
