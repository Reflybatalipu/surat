<?php
    $role_sidebar = isset($_SESSION['nama_role']) ? $_SESSION['nama_role'] : ''; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIMPERS - Sistem Informasi Manajemen Persuratan Sekolah</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            /* Warna Identitas Pilihanmu */
            --simpers-primary: #4A70A9; 
            --simpers-hover: #3b5a87;   
        }
        
        /* 1. Kunci tinggi body layar penuh, cegah scroll ganda */
        body { 
            background-color: #f4f6f9; 
            overflow: hidden; 
            height: 100vh;
        }

        /* 2. Wrapper Utama Flexbox */
        .wrapper-utama {
            height: 100vh;
            display: flex;
            overflow: hidden;
        }

        /* 3. Styling Sidebar: Menempel & Bisa Scroll Internal */
        .sidebar { 
            width: 250px; 
            background-color: var(--simpers-primary); 
            color: white; 
            height: 100vh; 
            overflow-y: auto; /* Jika menu banyak, sidebar bisa scroll sendiri */
            transition: all 0.3s; 
            z-index: 1000; 
            flex-shrink: 0;
        }
        .sidebar::-webkit-scrollbar { width: 6px; } /* Percantik scrollbar sidebar */
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
        
        .sidebar .brand-logo { font-size: 1.5rem; font-weight: bold; padding: 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); letter-spacing: 1px; }
        .sidebar-menu { padding: 15px 10px; list-style: none; margin: 0; }
        .sidebar-menu a { color: rgba(255, 255, 255, 0.85); text-decoration: none; display: flex; align-items: center; padding: 12px 15px; border-radius: 8px; transition: 0.2s; margin-bottom: 5px;}
        .sidebar-menu a i { width: 25px; font-size: 1.1rem; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background-color: rgba(255, 255, 255, 0.15); color: white; font-weight: 500; }

        /* Area Kanan (Header + Konten) */
        .content-area {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
            height: 100vh;
            overflow: hidden;
        }

        /* 4. Topbar: Menempel (Sticky) di atas area konten */
        .topbar { 
            background-color: #ffffff; 
            height: 60px; 
            flex-shrink: 0; /* Cegah header mengecil */
            box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            padding: 0 20px; 
            z-index: 1010;
        }

        /* 5. Konten Utama: Satu-satunya area yang bisa discroll ke bawah */
        .main-content {
            flex-grow: 1;
            overflow-y: auto; /* Fungsi scroll aktif di sini */
            padding: 1.5rem;
            background-color: #f4f6f9;
        }
        
        @media (max-width: 991.98px) { .sidebar.d-none.d-lg-block { display: none !important; } }
        
        .btn-simpers { background-color: var(--simpers-primary); color: white; border: none; }
        .btn-simpers:hover { background-color: var(--simpers-hover); color: white; }
        .text-simpers { color: var(--simpers-primary); }
        
        #sidebar, .sidebar, aside { z-index: 1050 !important; }
        #overlay, .overlay, .sidebar-backdrop, .offcanvas-backdrop { z-index: 1040 !important; }


        /* Live Clock Topbar Global */
        .mobile-brand {
            display: none;
            font-weight: 800;
            color: var(--simpers-primary);
            letter-spacing: 0.8px;
            font-size: 1rem;
            line-height: 1;
        }
        .topbar-clock {
            text-align: right;
            line-height: 1.1;
            padding-right: 14px;
            margin-right: 14px;
            border-right: 1px solid #eef2f7;
            min-width: 150px;
        }
        .topbar-clock-date {
            font-size: 0.72rem;
            color: #6b7280;
            white-space: nowrap;
        }
        .topbar-clock-time {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--simpers-primary);
            letter-spacing: 1px;
            white-space: nowrap;
        }
        @media (max-width: 767.98px) {
            .topbar {
                height: 58px;
                padding: 0 12px;
                gap: 10px;
            }
            .mobile-brand {
                display: block;
                flex: 1;
                text-align: left;
                margin-left: 2px;
            }
            .topbar-clock {
                min-width: auto;
                margin-right: 10px;
                padding: 6px 10px;
                border-right: none;
                border-radius: 999px;
                background: #eef4fb;
                box-shadow: inset 0 0 0 1px rgba(74,112,169,.08);
            }
            .topbar-clock-date {
                display: none;
            }
            .topbar-clock-time {
                font-size: 0.82rem;
                letter-spacing: .4px;
                line-height: 1;
            }
            .topbar .btn.btn-light {
                width: 38px;
                height: 38px;
                padding: 0;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .topbar img.rounded-circle {
                width: 36px !important;
                height: 36px !important;
            }
        }


        /* Perbaikan Tabel Responsive yang bisa scroll di dalam halaman konten */
        .table-responsive { 
            max-height: 65vh; 
            overflow-y: auto; 
            overflow-x: auto; 
        }
        .table-responsive thead th { position: sticky; top: 0; background-color: #f8f9fa; z-index: 10; box-shadow: 0 2px 3px -1px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

    <div class="wrapper-utama">
        
        <?php include 'sidebar.php'; ?>

        <div class="content-area w-100">
            
            <header class="topbar">
                <button class="btn btn-light d-lg-none shadow-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile">
                    <i class="fa-solid fa-bars text-simpers"></i>
                </button>

                <div class="mobile-brand">SIMPERS</div>
                
                <div class="d-none d-lg-block fw-bold text-muted">
                    Sistem Informasi Manajemen Persuratan
                </div>

                <div class="d-flex align-items-center ms-auto">
                    <div class="topbar-clock">
                        <div class="topbar-clock-date" id="topbarLiveDate">Memuat tanggal...</div>
                        <div class="topbar-clock-time" id="topbarLiveTime">--:--:--</div>
                    </div>
                    <div class="dropdown">
                        <a class="text-decoration-none text-dark dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <div class="me-2 text-end d-none d-sm-block">
                                <div class="fw-bold" style="font-size: 0.9rem;"><?= $_SESSION['nama_lengkap'] ?? 'User Name'; ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= $_SESSION['nama_role'] ?? 'Role'; ?> | <?= $_SESSION['nama_unit'] ?? 'Unit'; ?></div>
                            </div>
                            
                            <?php 
                                $foto_header = (!empty($_SESSION['foto_profil'])) ? $_SESSION['foto_profil'] : 'default.png';
                            ?>
                            <img src="../assets/img/<?= $foto_header ?>?v=<?= time() ?>" alt="User" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover; border: none;">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li><a class="dropdown-item" href="../profile/profile.php"><i class="fa-solid fa-user-gear me-2"></i> Profil Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($role_sidebar == 'Admin_TU'): ?>
                             <li><a class="dropdown-item" href="../profile/setting.php"><i class="fa-solid fa-gear me-2"></i> Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fa-solid fa-right-from-bracket me-2"></i> Keluar (Logout)</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
            
			<script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.addEventListener("touchstart", function(e) {
                        if (!window.AndroidCamera) return; // Abaikan jika di browser biasa

                        const mainContent = document.querySelector('.main-content');

                        // 1. Cek apakah halaman utama sudah mentok di atas
                        let isMainMentokAtas = mainContent ? Math.ceil(mainContent.scrollTop) <= 1 : true;

                        // 2. Dapatkan posisi vertikal (Y) jempol user saat pertama kali menyentuh layar
                        let posisiJariY = e.touches[0].clientY;

                        // 3. Buat "Zona Aman Refresh". 
                        let jariDiPuncakLayar = posisiJariY <= 400;

                        // KESIMPULAN: Refresh HANYA aktif jika halaman mentok atas DAN jari mulai mengusap dari puncak layar!
                        let izinkanRefresh = isMainMentokAtas && jariDiPuncakLayar;

                        // Kirim perintah ke Android
                        window.AndroidCamera.aturSwipeRefresh(izinkanRefresh);

                    }, { passive: true });
                });
                
            </script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // 0. FUNGSI LIVE CLOCK TOPBAR GLOBAL
    function updateTopbarClock() {
        const now = new Date();
        const dateEl = document.getElementById('topbarLiveDate');
        const timeEl = document.getElementById('topbarLiveTime');

        const tanggal = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });

        const timeParts = [now.getHours(), now.getMinutes(), now.getSeconds()]
            .map(n => String(n).padStart(2, '0'));
        const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
        const jam = isMobile ? timeParts.slice(0, 2).join(':') : timeParts.join(':');

        if (dateEl) dateEl.textContent = tanggal;
        if (timeEl) timeEl.textContent = jam;
    }

    // 1. FUNGSI PREVIEW PDF VIA IFRAME
    function bukaPreviewPDF(namaFile) {
        var viewerUrl = "https://simpers.42web.io/vendor/pdfjs/web/viewer.html";
        var proxyUrl = "https://simpers.42web.io/ambil_pdf.php?file=" + encodeURIComponent(namaFile);
        var finalUrl = viewerUrl + "?file=" + encodeURIComponent(proxyUrl);
        
        console.log("Mencoba memuat URL ini:", finalUrl);
        
        var frame = document.getElementById('framePDF');
        if (frame) {
            frame.src = finalUrl + "&v=" + new Date().getTime();
            
            var modalElement = document.getElementById('modalPreviewPDF');
            if (modalElement) {
                var modalPDF = new bootstrap.Modal(modalElement);
                modalPDF.show();
            }
        }
    }

        function bukaPDF(namaFile) {
    var fileUrl = window.location.origin + "/uploads/surat_masuk/" + namaFile;
    
    console.log("Mencoba memuat PDF langsung:", fileUrl);
    
    var frame = document.getElementById('framePDF');
    if (frame) {
        // Langsung masukkan URL PDF ke iframe
        // Browser akan otomatis membuka PDF viewer bawaannya
        frame.src = fileUrl; 
        
        var modalPDF = new bootstrap.Modal(document.getElementById('modalPreviewPDF'));
        modalPDF.show();
    }
}
    // 2. FUNGSI CHECKBOX PILIH SEMUA
    function toggleCheckboxes(source, checkboxClassName) {
        let checkboxes = document.querySelectorAll('.' + checkboxClassName);
        checkboxes.forEach(cb => {
            cb.checked = source.checked;
        });
    }

    // 3. FUNGSI SIMPAN TOKEN FCM ANDROID
    function simpanFCMToken(tokenFcmAndroid) {
        let formData = new FormData();
        formData.append('token', tokenFcmAndroid);

        fetch('../simpan_token.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => console.log("Laporan FCM: " + data))
        .catch(error => console.error("Error FCM:", error));
    }

    // 4. FUNGSI NOTIFIKASI KE APLIKASI ANDROID (Amankan dari Error Parsing JSON)
    function kirimNotifKeApp() {
        if (typeof Android !== 'undefined' && Android.updateBadgeCount) {
            fetch('../pengaduan/get_notif_count.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text(); // Ambil teks mentah dulu untuk menghindari crash JSON
                })
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if (data && data.jumlah !== undefined) {
                            Android.updateBadgeCount(data.jumlah);
                        }
                    } catch (jsonError) {
                        console.error("Gagal parse data notifikasi Android:", jsonError, "Respons: ", text);
                    }
                })
                .catch(error => console.error("Error fetch Notif Android:", error));
        }
    }

    // 5. FUNGSI UPDATE BADGE SIDEBAR REALTIME
    function formatSidebarBadgeValue(value) {
        const numberValue = Number(value) || 0;
        return numberValue > 99 ? '99+' : String(numberValue);
    }

    function setSidebarBadge(key, value) {
        const numberValue = Number(value) || 0;
        const badges = document.querySelectorAll('[data-sidebar-badge="' + key + '"]');

        badges.forEach(function(badge) {
            if (numberValue > 0) {
                badge.textContent = formatSidebarBadgeValue(numberValue);
                badge.style.display = 'inline-block';
                badge.setAttribute('aria-label', numberValue + ' tugas aktif');
            } else {
                badge.textContent = '';
                badge.style.display = 'none';
                badge.removeAttribute('aria-label');
            }
        });
    }

    function applySidebarBadges(data) {
        if (!data || !data.badges) return;

        setSidebarBadge('surat_masuk', data.badges.surat_masuk);
        setSidebarBadge('surat_keluar', data.badges.surat_keluar);
        setSidebarBadge('disposisi', data.badges.disposisi);
    }

    function fetchSidebarBadges(urlList, index) {
        if (!urlList[index]) return;

        fetch(urlList[index], {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function(data) {
            if (data && data.success) {
                applySidebarBadges(data);
            }
        })
        .catch(function(error) {
            if (index + 1 < urlList.length) {
                fetchSidebarBadges(urlList, index + 1);
            } else {
                console.log('Sidebar badge sync failed:', error.message);
            }
        });
    }

    function updateSidebarBadges() {
        // Dua path dipakai agar tetap aman saat halaman berada di root maupun subfolder modul.
        fetchSidebarBadges(['../api_sidebar.php', './api_sidebar.php'], 0);
    }

    // 6. FUNGSI UPDATE DATA REALTIME VIA AJAX JQUERY
    function globalRealtimeUpdate() {
        // Pastikan jQuery ($) sudah siap sebelum dijalankan
        if (typeof $ !== 'undefined') {
            $.ajax({
                url: '../api_realtime.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response && response.stats) {
                        // Update Statistik jika elemennya eksis di halaman
                        if(response.stats.val_a !== undefined) $('#stat-val-a').text(response.stats.val_a);
                        if(response.stats.val_b !== undefined) $('#stat-val-b').text(response.stats.val_b);
                        if(response.stats.val_c !== undefined) $('#stat-val-c').text(response.stats.val_c);

                        // Badge sidebar sekarang disinkronkan oleh updateSidebarBadges().
                    }
                },
                error: function() { 
                    console.log("Realtime sync failed. Menunggu siklus berikutnya..."); 
                }
            });
        }
    }

    // 7. INISIALISASI UTAMA SAAT HALAMAN SELESAI DIMUAT
    $(document).ready(function() {
        // Jalankan live clock topbar global
        updateTopbarClock();
        setInterval(updateTopbarClock, 1000);

        // Jalankan fungsi notifikasi Android dengan aman di dalam document ready
        kirimNotifKeApp();

        // Jalankan sinkronisasi realtime pertama kali
        globalRealtimeUpdate();
        updateSidebarBadges();

        // Ulangi realtime sync setiap 15 detik
        setInterval(globalRealtimeUpdate, 15000);
        setInterval(updateSidebarBadges, 15000); 
    });
    
</script>
            <script>
            $(document).ready(function() {
    // Cari semua elemen modal yang menggunakan ID dinamis seperti modalFile83
    const semuaModal = document.querySelectorAll('.modal');

    semuaModal.forEach(function(modalElement) {
        // Event 1: Saat modal SELESAI ditutup/disembunyikan sepenuhnya
        modalElement.addEventListener('hidden.bs.modal', function () {
            // Lepaskan fokus dari modal agar browser tidak bingung
            if (document.activeElement === modalElement || modalElement.contains(document.activeElement)) {
                document.activeElement.blur(); 
            }
            
            // Terapkan atribut 'inert' untuk memastikan elemen benar-benar mati dari sistem fokus
            modalElement.setAttribute('inert', '');
            
            // Pastikan aria-hidden sinkron
            modalElement.setAttribute('aria-hidden', 'true');
        });

        // Event 2: Saat modal MULAI dibuka kembali oleh user
        modalElement.addEventListener('show.bs.modal', function () {
            // Hapus atribut 'inert' agar elemen bisa menerima fokus kembali
            modalElement.removeAttribute('inert');
            
            // Ubah aria-hidden menjadi false karena modal sekarang terlihat
            modalElement.setAttribute('aria-hidden', 'false');
        });
    });
});

            </script>
            <main class="main-content">
                
            

 