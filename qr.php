<?php
session_start();
require 'koneksi.php';

// Cek Login
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];

// ==========================================
// HANDLER ADMIN: KELOLA PARAMETER
// ==========================================
if ($role == 'admin') {
    if (isset($_POST['add_param'])) {
        $p = mysqli_real_escape_string($koneksi, str_replace(['?','='], '', $_POST['new_param']));
        if(!empty($p)) {
            $cek = mysqli_query($koneksi, "SELECT id FROM master_broadcast_params WHERE param_key='$p'");
            if(mysqli_num_rows($cek) == 0) mysqli_query($koneksi, "INSERT INTO master_broadcast_params (param_key) VALUES ('$p')");
        }
    }
    if (isset($_GET['del_param'])) {
        $id_p = (int)$_GET['del_param'];
        mysqli_query($koneksi, "DELETE FROM master_broadcast_params WHERE id=$id_p");
        header("Location: qr.php"); exit;
    }
}

// PERBAIKAN 1: AMBIL SEMUA DATA CONFIG (SELECT *) SUPAYA LOGO MUNCUL
$query_setting = mysqli_query($koneksi, "SELECT * FROM pengaturan LIMIT 1");
$config = mysqli_fetch_assoc($query_setting);
$default_link = $config['broadcast_link'] ?? 'https://undangan.com';

$master_params = mysqli_query($koneksi, "SELECT * FROM master_broadcast_params");
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
    <title>QR Generator - <?= $config['app_name'] ?? 'GuestBook' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
    
    <script type="text/javascript" src="https://unpkg.com/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #ffffff; }
        .font-serif { font-family: 'Playfair Display', serif; }
        
        #qr-preview svg, #qr-preview canvas { max-width: 100% !important; height: auto !important; }
        
        /* Slider Gold */
        input[type=range] { -webkit-appearance: none; appearance: none; width: 100%; background: transparent; }
        input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; height: 16px; width: 16px; border-radius: 50%; background: #87714c; cursor: pointer; margin-top: -6px; box-shadow: 0 1px 3px rgba(0,0,0,0.3); }
        input[type=range]::-webkit-slider-runnable-track { width: 100%; height: 4px; cursor: pointer; background: #e8e1d5; border-radius: 2px; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #87714c; border-radius: 10px; }
    </style>
</head>
<body class="text-[#1a0f0d]" style="background-color:#ffffff; background-image:url('https://www.transparenttextures.com/patterns/cream-paper.png');">

    <?php if(file_exists('sidebar.php')) include 'sidebar.php'; ?>

    <main class="md:ml-64 p-4 lg:p-6 relative">
        <div class="max-w-7xl mx-auto">
            
            <!-- Header Section -->
            <div class="mb-5 lg:mb-6 border-b border-[#d1c7b7] pb-3 no-print">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-[#fffbf2] text-[#87714c] rounded-xl flex items-center justify-center border border-[#e8e1d5] shadow-sm">
                            <iconify-icon icon="solar:qr-code-bold-duotone" class="text-2xl"></iconify-icon>
                        </div>
                        <div>
                            <h1 class="text-2xl lg:text-3xl font-bold text-[#1a0f0d] font-serif">QR Generator</h1>
                            <p class="text-[#87714c] mt-1 text-sm">Buat dan kustomisasi QR Code tamu secara masal.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <div class="lg:col-span-7 space-y-6">
                    
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#e8e1d5]">
                        <h3 class="font-bold text-[#1a0f0d] mb-4 text-base border-b border-[#f3e9d8] pb-2 font-serif">
                            <i class="fas fa-link text-[#87714c] mr-2"></i> Konfigurasi Link
                        </h3>
                        
                        <div class="mb-5">
                            <label class="block text-xs font-bold text-[#87714c] mb-1 uppercase">Link Undangan</label>
                            <input type="text" id="base_link" value="<?= $default_link ?>" class="w-full bg-[#ffffff] border border-[#e8e1d5] rounded-xl px-4 py-2.5 text-sm text-[#1a0f0d] focus:ring-2 focus:ring-[#87714c] outline-none" placeholder="https://...">
                        </div>

                        <div class="bg-[#fffbf2] p-4 rounded-xl border border-[#ffe0b2]">
                            <div class="flex justify-between items-center mb-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <div class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="use_param" class="sr-only peer" checked onchange="toggleQrParam()">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#87714c]"></div>
                                    </div>
                                    <span class="text-xs font-bold text-[#8d6e63] uppercase">Gunakan Parameter URL</span>
                                </label>
                                <?php if($role == 'admin'): ?>
                                    <button type="button" onclick="toggleModal('modalAdminParam')" class="text-[10px] text-[#87714c] hover:underline font-bold">+ Kelola</button>
                                <?php endif; ?>
                            </div>
                            
                            <div id="paramOptions" class="transition-all duration-300">
                                <div class="flex flex-wrap gap-3">
                                    <?php 
                                    mysqli_data_seek($master_params, 0); 
                                    $first = true;
                                    while($p = mysqli_fetch_assoc($master_params)): 
                                    ?>
                                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-1.5 rounded-xl border border-[#e8e1d5] hover:border-[#87714c] transition">
                                        <input type="radio" name="custom_param_name" value="<?= $p['param_key'] ?>" class="text-[#87714c] focus:ring-[#87714c]" <?= $first ? 'checked' : '' ?>>
                                        <span class="text-sm font-mono text-[#1a0f0d]">?<?= $p['param_key'] ?>=</span>
                                    </label>
                                    <?php $first = false; endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#e8e1d5]">
                        <h3 class="font-bold text-[#1a0f0d] mb-4 text-base border-b border-[#f3e9d8] pb-2 font-serif">
                            <i class="fas fa-users text-[#87714c] mr-2"></i> Data Tamu
                        </h3>
                        
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-[#87714c] mb-1 uppercase">Daftar Nama (1 baris 1 nama)</label>
                            <textarea id="guest_list" rows="8" class="w-full border border-[#e8e1d5] rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[#87714c] outline-none font-mono bg-[#ffffff] text-[#1a0f0d]" placeholder="Budi Santoso&#10;Ani Lestari&#10;Pak RT 01"></textarea>
                            <p class="text-[10px] text-[#8d6e63] mt-1 text-right italic" id="guest_count">0 tamu terdeteksi</p>
                        </div>

                        <button onclick="generateBulkQR()" id="btn-generate" class="w-full bg-[#87714c] hover:bg-[#b08d55] text-white font-bold py-3.5 rounded-xl shadow-lg shadow-[#87714c]/20 transition flex justify-center items-center gap-2 text-sm">
                            <i class="fas fa-cogs"></i> <span>Generate & Download ZIP</span>
                        </button>
                    </div>

                </div>

                <div class="lg:col-span-5 space-y-6">
                    
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-[#e8e1d5] sticky top-4">
                        <h3 class="font-bold text-[#1a0f0d] mb-4 text-base border-b border-[#f3e9d8] pb-2 font-serif">
                            <i class="fas fa-eye text-[#87714c] mr-2"></i> Live Preview
                        </h3>

                        <div class="flex justify-center mb-6 bg-[#ffffff] p-6 rounded-xl border border-[#e8e1d5]">
                            <div id="qr-preview"></div>
                        </div>

                        <div class="space-y-4 max-h-[500px] overflow-y-auto custom-scrollbar pr-2">
                            
                            <div class="border border-[#e8e1d5] rounded-xl p-4 bg-white">
                                <div class="flex justify-between items-center mb-2">
                                    <h4 class="text-xs font-bold text-[#87714c] uppercase">Logo Tengah</h4>
                                    <span class="text-[10px] text-gray-400 font-bold" id="logo-size-text">40%</span>
                                </div>
                                <input type="file" id="logo-file" accept="image/*" class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded-xl file:border-0 file:text-xs file:bg-[#87714c] file:text-white hover:file:bg-[#b08d55] mb-2" onchange="updateLogo()">
                                <input type="range" id="logo-size" min="0.1" max="0.9" step="0.05" value="0.4" oninput="updateQR()">
                            </div>

                            <div class="border border-[#e8e1d5] rounded-xl p-4 bg-white">
                                <h4 class="text-xs font-bold text-[#87714c] uppercase mb-3">Bentuk QR</h4>
                                <div class="space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-500">Titik</span>
                                        <select id="dots-type" onchange="updateQR()" class="text-xs border border-[#e8e1d5] rounded px-2 py-1 bg-[#ffffff]">
                                            <option value="square">Kotak</option>
                                            <option value="dots">Bulat</option>
                                            <option value="rounded">Rounded</option>
                                            <option value="classy">Classy</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-500">Sudut Luar</span>
                                        <select id="corner-square-type" onchange="updateQR()" class="text-xs border border-[#e8e1d5] rounded px-2 py-1 bg-[#ffffff]">
                                            <option value="square">Kotak</option>
                                            <option value="dot">Bulat</option>
                                            <option value="extra-rounded">Halus</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-500">Sudut Dalam</span>
                                        <select id="corner-dot-type" onchange="updateQR()" class="text-xs border border-[#e8e1d5] rounded px-2 py-1 bg-[#ffffff]">
                                            <option value="square">Kotak</option>
                                            <option value="dot">Bulat</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="border border-[#e8e1d5] rounded-xl p-4 bg-white">
                                <h4 class="text-xs font-bold text-[#87714c] uppercase mb-3">Warna & Resolusi</h4>
                                <div class="grid grid-cols-3 gap-2 text-center mb-4">
                                    <div><input type="color" id="color-dots" value="#000000" onchange="updateQR()" class="block w-full h-8 cursor-pointer rounded border border-[#e8e1d5]"><span class="text-[10px] text-gray-500">Titik</span></div>
                                    <div><input type="color" id="color-corner" value="#87714c" onchange="updateQR()" class="block w-full h-8 cursor-pointer rounded border border-[#e8e1d5]"><span class="text-[10px] text-gray-500">Sudut</span></div>
                                    <div><input type="color" id="color-bg" value="#ffffff" onchange="updateQR()" class="block w-full h-8 cursor-pointer rounded border border-[#e8e1d5]"><span class="text-[10px] text-gray-500">Bg</span></div>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <span class="text-xs text-gray-500 font-bold">Resolusi (px)</span>
                                    <span id="qr-size-val" class="text-xs font-bold text-[#87714c]">1000</span>
                                </div>
                                <input type="range" id="qr-size" min="300" max="2000" step="100" value="1000" oninput="document.getElementById('qr-size-val').innerText=this.value">
                            </div>

                            <button onclick="resetStyle()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold py-2.5 rounded-xl transition">Reset Default</button>
                        </div>
                    </div>

                </div>
            </div>
            <footer class="mt-12 mb-6 text-center text-xs text-gray-400 border-t border-gray-100 pt-6">
                <?= $config_global['copyright'] ?? $config['copyright'] ?? '© ' . date('Y') . ' BUKU TAMU DIGITAL Eksklusif' ?>
            </footer>
        </div>
    </main>

    <?php if($role == 'admin'): ?>
    <div id="modalAdminParam" class="fixed inset-0 z-[60] hidden items-center justify-center p-4">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="toggleModal('modalAdminParam')"></div>
        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl border border-[#e8e1d5] animate__animated animate__zoomIn">
            <div class="bg-[#ffffff] px-5 py-4 border-b border-[#e8e1d5] flex justify-between items-center">
                <h3 class="font-bold text-[#1a0f0d] font-serif">Kelola Parameter URL</h3>
                <button onclick="toggleModal('modalAdminParam')" class="text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-5">
                <form action="" method="POST" class="flex gap-2 mb-4">
                    <input type="text" name="new_param" placeholder="cth: yth" class="border border-[#e8e1d5] rounded-xl px-4 py-2 w-full text-sm outline-none focus:ring-2 focus:ring-[#87714c]" required>
                    <button type="submit" name="add_param" class="bg-[#87714c] text-white px-4 py-2 rounded-xl text-sm font-bold shadow hover:bg-[#b08d55]">Tambah</button>
                </form>
                <div class="max-h-60 overflow-y-auto border border-[#e8e1d5] rounded-xl bg-[#ffffff] custom-scrollbar">
                    <ul class="divide-y divide-[#e8e1d5] text-sm">
                        <?php mysqli_data_seek($master_params, 0); while($mp = mysqli_fetch_assoc($master_params)): ?>
                        <li class="flex justify-between items-center px-4 py-3 hover:bg-[#fffbf2]">
                            <span class="font-mono text-[#87714c] font-bold">?<?= $mp['param_key'] ?>=</span>
                            <a href="?del_param=<?= $mp['id'] ?>" onclick="return confirm('Hapus?')" class="text-red-400 hover:text-red-600 text-xs font-bold border border-red-100 px-2 py-1 rounded bg-red-50">Hapus</a>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        let qrCode;
        
        document.addEventListener("DOMContentLoaded", () => {
            initQR();
            document.getElementById('guest_list').addEventListener('input', updateCount);
            toggleQrParam();
        });

        function toggleQrParam() {
            const checkbox = document.getElementById('use_param');
            const container = document.getElementById('paramOptions');
            if (checkbox.checked) {
                container.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                container.classList.add('opacity-50', 'pointer-events-none');
            }
        }

        function initQR() {
            qrCode = new QRCodeStyling({
                width: 300, height: 300, type: "svg", data: "https://undangan.com/Preview", image: "",
                dotsOptions: { color: "#000000", type: "square" }, 
                backgroundOptions: { color: "#ffffff" },
                cornersSquareOptions: { type: "square", color: "#87714c" }, 
                cornersDotOptions: { type: "square", color: "#000000" },
                imageOptions: { crossOrigin: "anonymous", margin: 10, imageSize: 0.4 }
            });
            qrCode.append(document.getElementById("qr-preview"));
        }

        function updateQR() {
            qrCode.update({
                dotsOptions: { color: document.getElementById("color-dots").value, type: document.getElementById("dots-type").value },
                backgroundOptions: { color: document.getElementById("color-bg").value },
                cornersSquareOptions: { type: document.getElementById("corner-square-type").value, color: document.getElementById("color-corner").value },
                cornersDotOptions: { type: document.getElementById("corner-dot-type").value, color: document.getElementById("color-corner").value },
                imageOptions: { imageSize: parseFloat(document.getElementById("logo-size").value) }
            });
            document.getElementById("logo-size-text").innerText = Math.round(parseFloat(document.getElementById("logo-size").value) * 100) + "%";
        }

        function updateLogo() {
            const file = document.getElementById('logo-file').files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = () => { qrCode.update({ image: reader.result }); };
                reader.readAsDataURL(file);
            }
        }

        async function generateBulkQR() {
            const btn = document.getElementById('btn-generate');
            const originalHTML = btn.innerHTML;
            const listText = document.getElementById('guest_list').value;
            const baseLink = document.getElementById('base_link').value;
            const useParam = document.getElementById('use_param').checked;
            const qrSize = parseInt(document.getElementById("qr-size").value) || 1000;
            
            let paramName = 'to';
            if(useParam) {
                const radios = document.getElementsByName('custom_param_name');
                for (var i = 0; i < radios.length; i++) { if (radios[i].checked) paramName = radios[i].value; }
            }

            const names = listText.split('\n').filter(n => n.trim() !== '');
            if (names.length === 0) { alert("Masukkan minimal satu nama tamu!"); return; }

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            btn.disabled = true; btn.classList.add('opacity-75');

            const zip = new JSZip();
            const folder = zip.folder("QR_Codes_Undangan");
            qrCode.update({ width: qrSize, height: qrSize }); 

            try {
                for (let name of names) {
                    name = name.trim();
                    let finalUrl = baseLink;
                    if (useParam) {
                        const separator = baseLink.includes('?') ? '&' : '?';
                        finalUrl += `${separator}${paramName}=${encodeURIComponent(name)}`;
                    }
                    qrCode.update({ data: finalUrl });
                    const blob = await qrCode.getRawData('png');
                    folder.file(`${name}.png`, blob);
                }
                const content = await zip.generateAsync({ type: "blob" });
                saveAs(content, "QR_Codes_Tamu.zip");
                qrCode.update({ width: 300, height: 300 }); 
            } catch (err) {
                alert("Gagal generate QR.");
            } finally {
                btn.innerHTML = originalHTML; btn.disabled = false; btn.classList.remove('opacity-75');
            }
        }

        function updateCount() {
            const count = document.getElementById('guest_list').value.split('\n').filter(n => n.trim() !== '').length;
            document.getElementById('guest_count').innerText = `${count} tamu terdeteksi`;
        }

        function resetStyle() {
            document.getElementById('color-dots').value = "#000000";
            document.getElementById('color-corner').value = "#87714c";
            document.getElementById('color-bg').value = "#ffffff";
            document.getElementById('dots-type').value = "square";
            document.getElementById('corner-square-type').value = "square";
            document.getElementById('corner-dot-type').value = "square";
            document.getElementById('logo-size').value = 0.4;
            updateQR();
        }
        
        function toggleModal(id) { 
            const el = document.getElementById(id);
            if(el.classList.contains('hidden')) { el.classList.remove('hidden'); el.classList.add('flex'); } 
            else { el.classList.add('hidden'); el.classList.remove('flex'); }
        }
    </script>
</body>
</html>