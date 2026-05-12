<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'koneksi.php';
require_login();
check_csrf();

$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];
$parent_id = $_SESSION['parent_id'] ?? 0;
$effective_uid = ($role == 'receptionist' && $parent_id > 0) ? $parent_id : $uid;

$q_global = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$config_global = mysqli_fetch_assoc($q_global);

$swal_script = "";

// A. HAPUS HADIAH
if (isset($_POST['hapus_id'])) {
    $id_hapus = (int) $_POST['hapus_id'];
    
    // Verifikasi kepemilikan (kecuali admin)
    if ($role != 'admin') {
        $q_cek_own = mysqli_query($koneksi, "SELECT h.id FROM hadiah h 
            INNER JOIN users u ON h.wordpress_post_id = u.post_id 
            WHERE h.id=$id_hapus AND u.id='$effective_uid' LIMIT 1");
        if (!$q_cek_own || mysqli_num_rows($q_cek_own) == 0) {
            header("Location: hadiah.php"); exit;
        }
    }
    
    // Ambil file bukti untuk dihapus
    $q_bukti = mysqli_query($koneksi, "SELECT proof_file FROM hadiah WHERE id=$id_hapus");
    if($q_bukti && mysqli_num_rows($q_bukti) > 0) {
        $data = mysqli_fetch_assoc($q_bukti);
        if(!empty($data['proof_file']) && file_exists('assets/gifts/' . $data['proof_file'])) {
            unlink('assets/gifts/' . $data['proof_file']);
        }
    }
    
    mysqli_query($koneksi, "DELETE FROM hadiah WHERE id=$id_hapus");
    header("Location: hadiah.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Hadiah - <?= htmlspecialchars($config_global['app_name'] ?? 'Buku Tamu Digital') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; }
        .font-serif { font-family: 'Playfair Display', serif; }
        .text-gold { color: #f4c78c; }
        .text-brown { color: #1a0f0d; }
        
        @media (max-width: 768px) { body { padding-bottom: 80px; } }
        
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
                    <iconify-icon icon="solar:gift-bold-duotone" class="text-2xl"></iconify-icon>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-[#1a0f0d] font-serif">Konfirmasi Hadiah</h1>
                    <p class="text-[#87714c] mt-1 text-sm">Kelola data tamu yang memberikan hadiah.</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-[#e8e1d5] mb-4 flex flex-col xl:flex-row justify-end items-center gap-4">
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
                            <th class="py-4 px-4 text-left w-[20%] text-white">Nama Tamu</th>
                            <th class="py-4 px-4 text-left w-[15%] text-white">Bank</th>
                            <th class="py-4 px-4 text-left w-[15%] text-white">A.N Rekening</th>
                            <th class="py-4 text-right px-4 w-[15%] text-white">Jumlah (Rp)</th>
                            <th class="py-4 text-center w-[15%] text-white">Waktu Konfirmasi</th>
                            <th class="py-4 text-center w-[10%] text-white">Bukti</th>
                            <th class="py-4 text-center w-[5%] rounded-tr-lg text-white">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f3e9d8] text-sm text-[#1a0f0d]">
                        <?php 
                        $no=1; 
                        // Cek apakah tabel hadiah ada (jika tidak ada akan di-create oleh koneksi)
                        $cek_tabel = mysqli_query($koneksi, "SHOW TABLES LIKE 'hadiah'");
                        if(mysqli_num_rows($cek_tabel) > 0) {
                            // Filter per user: hanya tampilkan data milik event user yang login
                            // Admin bisa lihat semua
                            if ($role == 'admin') {
                                $q_hadiah = mysqli_query($koneksi, "SELECT * FROM hadiah ORDER BY id DESC");
                            } else {
                                // Ambil post_id milik user yang sedang login
                                $q_postid = mysqli_query($koneksi, "SELECT post_id FROM users WHERE id='$effective_uid' LIMIT 1");
                                $row_postid = mysqli_fetch_assoc($q_postid);
                                $my_post_id = (int)($row_postid['post_id'] ?? 0);
                                $q_hadiah = mysqli_query($koneksi, "SELECT * FROM hadiah WHERE wordpress_post_id='$my_post_id' ORDER BY id DESC");
                            }
                            
                            if($q_hadiah && mysqli_num_rows($q_hadiah) > 0):
                                while($row=mysqli_fetch_assoc($q_hadiah)): 
                                    $amount_fmt = is_numeric($row['amount']) ? number_format((float)$row['amount'], 0, ',', '.') : $row['amount'];
                            ?>
                            <tr class="hover:bg-[#fffbf2] transition">
                                <td class="py-3 text-center text-gray-400"><?= $no++ ?></td>
                                
                                <td class="py-3 px-4 font-bold text-[#000000]"><?= htmlspecialchars($row['guest_name']) ?></td>
                                <td class="py-3 px-4 text-[#87714c] font-bold"><?= htmlspecialchars($row['bank_name']) ?></td>
                                <td class="py-3 px-4 text-gray-600"><?= htmlspecialchars($row['account_name']) ?></td>
                                
                                <td class="py-3 px-4 text-right font-bold text-green-700">Rp <?= $amount_fmt ?></td>
                                
                                <td class="py-3 text-center text-gray-500 text-xs"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
                                
                                <td class="py-3 text-center">
                                    <?php if(!empty($row['proof_file']) && file_exists('assets/gifts/' . $row['proof_file'])): ?>
                                        <button onclick="lihatBukti('assets/gifts/<?= $row['proof_file'] ?>')" class="bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1 rounded-lg text-xs font-bold transition inline-flex items-center gap-1 shadow-sm border border-blue-200">
                                            <i class="fas fa-image"></i> Lihat
                                        </button>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Tidak ada</span>
                                    <?php endif; ?>
                                </td>

                                <td class="py-3 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="submitHapusHadiah(<?= $row['id'] ?>)" class="text-red-500 hover:bg-red-50 w-8 h-8 rounded flex items-center justify-center border border-red-200 transition" title="Hapus Data"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="8" class="p-8 text-center text-gray-400 italic">Belum ada data konfirmasi hadiah.</td></tr>
                            <?php 
                            endif;
                        } else {
                            ?>
                                <tr><td colspan="8" class="p-8 text-center text-gray-400 italic">Tabel hadiah belum tersedia atau sistem sedang menyesuaikan. Silakan refresh halaman.</td></tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
            <?= $config_global['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
        </footer>
    </main>

    <div id="modalBukti" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm" onclick="toggleModal('modalBukti')"></div>
        <div class="relative w-full max-w-lg bg-white rounded-xl shadow-xl overflow-hidden animate__animated animate__zoomIn">
            <div class="bg-white px-4 py-4 border-b border-[#e8e1d5] flex justify-between items-center">
                <h3 class="text-lg font-bold text-[#000000] font-serif">Bukti Transfer</h3>
                <button onclick="toggleModal('modalBukti')" class="text-gray-400 hover:text-red-500 text-2xl">&times;</button>
            </div>
            <div class="p-4 text-center">
                <img id="imgBukti" src="" alt="Bukti Transfer" class="max-w-full max-h-[70vh] rounded-lg shadow-sm mx-auto border border-gray-200">
            </div>
            <div class="bg-[#ffffff] px-4 py-3 flex justify-end">
                <button type="button" onclick="toggleModal('modalBukti')" class="bg-gray-100 border text-gray-700 px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-200">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        <?= $swal_script ?>
    </script>

    <script>
        $(document).ready(function() {
            $("#searchTable").on("keyup", function() {
                var value = $(this).val().toLowerCase();
                $("#mainTable tbody tr").filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) });
            });
        });

        function toggleModal(id) { 
            const el = document.getElementById(id);
            if(el.classList.contains('hidden')) { el.classList.remove('hidden'); el.classList.add('flex'); } 
            else { el.classList.add('hidden'); el.classList.remove('flex'); }
        }

        function lihatBukti(url) {
            document.getElementById('imgBukti').src = url;
            toggleModal('modalBukti');
        }

        function submitHapusHadiah(id) {
            Swal.fire({
                title: 'Hapus Data Hadiah?', text: 'Data yang dihapus tidak dapat dikembalikan.', icon: 'warning', iconColor: '#87714c', showCancelButton: true, confirmButtonColor: '#87714c', cancelButtonColor: '#d33', confirmButtonText: 'Ya', cancelButtonText: 'Batal', background: '#fffcf9'
            }).then((result) => {
                if (result.isConfirmed) {
                    let f = document.createElement('form'); f.method = 'POST'; f.innerHTML = '<?= csrf_field() ?><input type="hidden" name="hapus_id" value="'+id+'">';
                    document.body.appendChild(f); f.submit();
                }
            })
        }
    </script>
</body>
</html>
