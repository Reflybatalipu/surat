<?php 
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    echo "<script>alert('Akses ditolak. Silakan login terlebih dahulu.'); window.location='login.php';</script>";
    exit;
}



    // Kita simpan nama_role ke dalam variabel agar kodenya lebih rapi dan singkat
$role_sidebar = isset($_SESSION['nama_role']) ? $_SESSION['nama_role'] : ''; 

// Ambil nama file yang sedang dibuka
$current_page = basename($_SERVER['PHP_SELF']);

// 1. Ambil ID User yang berhak jadi IT dari tabel pengaturan
$query_it = mysqli_query($koneksi, "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'helpdesk_user_id'");
$data_it = mysqli_fetch_assoc($query_it);

// Gunakan ternary operator untuk menghindari error jika data kosong
$id_user_it_dari_setting = $data_it ? $data_it['nilai_pengaturan'] : '';

// 2. Ambil ID user yang lagi login saat ini
// (Pastikan nama variabel session ini sesuai dengan yang kamu buat saat proses login)
$user_id = $_SESSION['user_id']; 
$id_user_yang_login = $_SESSION['user_id']; 
?>

<nav class="sidebar d-none d-lg-block">
    <div class="brand-logo">
        <i class="fa-solid fa-envelopes-bulk me-2"></i> SIMPERS
    </div>
    <ul class="sidebar-menu">
        <li><a href="../dashboard.php" class="<?= ($current_page == 'dashboard.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
        
        <li class="mt-3 mb-1 ms-3 text-uppercase text-white-50" style="font-size: 0.75rem;">Menu Utama</li>
        
        <?php if ($role_sidebar == 'Admin_TU' || $role_sidebar == 'Kepala_Sekolah'): ?>
        <li><a href="../modul_surat_masuk/surat_masuk.php" class="<?= ($current_page == 'surat_masuk.php') ? 'active' : ''; ?>"><i class="fa-solid fa-inbox"></i> Surat Masuk</a></li>
        <?php endif; ?>

        <li><a href="../modul_surat_keluar/ambil_nomor.php" class="<?= ($current_page == 'ambil_nomor.php') ? 'active' : ''; ?>"><i class="fa-solid fa-ticket"></i> Nomor Surat</a></li>
        <li><a href="../modul_surat_keluar/surat_keluar.php" class="<?= ($current_page == 'surat_keluar.php') ? 'active' : ''; ?>"><i class="fa-solid fa-paper-plane"></i> Surat Keluar</a></li>
        <li><a href="../disposisi/disposisi.php" class="<?= ($current_page == 'disposisi.php') ? 'active' : ''; ?> id="nav-badge-disposisi""><i class="fa-solid fa-share-nodes"></i> Disposisi</a></li>
        <li><a href="../earchive/earsip.php" class="<?= ($current_page == 'earsip.php') ? 'active' : ''; ?>"><i class="fa-solid fa-box-archive"></i> E-Archive</a></li>

        <?php if ($role_sidebar == 'Admin_TU' || $role_sidebar == 'Kepala_Sekolah'): ?>
        <li><a href="../laporan.php" class="<?= ($current_page == 'laporan.php' || $current_page == 'export_pdf.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice"></i> Laporan</a></li>
        <?php endif; ?>

        <?php if ($role_sidebar == 'Admin_TU'): ?>
        <li class="mt-3 mb-1 ms-3 text-uppercase text-white-50" style="font-size: 0.75rem;">Manajemen</li>
        <li><a href="../pengguna.php" class="<?= ($current_page == 'pengguna.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> Pengguna & Hak Akses</a></li>
        <li><a href="../log/audit_log.php" class="<?= ($current_page == 'audit_log.php') ? 'active' : ''; ?>"><i class="fa-solid fa-shield-halved"></i> Audit Log</a></li>
        <?php endif; ?>
    
    <?php 
if ($user_id == $id_user_it_dari_setting) : 
?>
    <li class="sidebar-item">
        <a href="../pengaduan/dashboard_it.php" class="sidebar-link">
            <i class="fa-solid fa-headset"></i>
            <span>Dashboard IT</span>
        </a>
    </li>
<?php endif; ?>
    </ul>
    
       <?php
// Pastikan timezone sudah disetel di bagian atas file atau di config
date_default_timezone_set('Asia/Jakarta');

$hari_ini     = date('N'); // 1 (Senin) s/d 7 (Minggu)
$jam_sekarang = date('H:i');
$jam_mulai    = "07:00";
$jam_selesai  = "15:30";

// Cek Jam Kerja: Senin-Jumat & di antara jam operasional
$is_jam_kerja = ($hari_ini >= 1 && $hari_ini <= 5) && ($jam_sekarang >= $jam_mulai && $jam_sekarang <= $jam_selesai);
?>

<div class="mt-auto"></div> 
    
<div class="p-3 border-top border-secondary border-opacity-25 mt-3">
    <?php if ($is_jam_kerja): ?>
        <a href="../pengaduan/riwayat_pengaduan.php" 
           class="btn btn-danger w-100 d-flex justify-content-center align-items-center fw-bold shadow-sm" 
           style="border-radius: 10px; transition: 0.3s;"
           title="Klik untuk membuat laporan atau cek riwayat">
            <i class="fa-solid fa-headset fs-5 me-2"></i> 
            <span>Pusat Bantuan IT</span>
        </a>
        <div class="text-center mt-2" style="font-size: 0.7rem; color: #2ecc71; font-weight: bold;">
            <i class="fa-solid fa-circle fa-beat me-1" style="font-size: 0.5rem;"></i> Layanan Online (SLA)
        </div>

    <?php else: ?>
        <button class="btn btn-secondary w-100 d-flex justify-content-center align-items-center fw-bold shadow-sm opacity-75" 
                style="border-radius: 10px; cursor: not_allowed;"
                onclick="alert('Pusat Bantuan IT Sedang Offline.\n\nLayanan pengaduan dibuka kembali pada hari kerja (Senin-Jumat) jam 07:00 s/d 15:30 WIB.')">
            <i class="fa-solid fa-lock fs-5 me-2"></i> 
            <span>Layanan Offline</span>
        </button>
        <div class="text-center mt-2" style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">
            Buka Kembali Jam 07:00 WIB
        </div>
    <?php endif; ?>
</div>
</nav>

<div class="offcanvas offcanvas-start sidebar" tabindex="-1" id="sidebarMobile" aria-labelledby="sidebarMobileLabel" style="width: 250px;">
    <div class="offcanvas-header border-bottom border-light border-opacity-10">
        <h5 class="offcanvas-title fw-bold text-white" id="sidebarMobileLabel"><i class="fa-solid fa-envelopes-bulk me-2"></i> SIMPERS</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <ul class="sidebar-menu mt-2">
            <li><a href="../dashboard.php" class="<?= ($current_page == 'dashboard.php' || $current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard</a></li>
            
            <?php if ($role_sidebar == 'Admin_TU' || $role_sidebar == 'Kepala_Sekolah'): ?>
            <li><a href="../modul_surat_masuk/surat_masuk.php" class="<?= ($current_page == 'surat_masuk.php') ? 'active' : ''; ?>"><i class="fa-solid fa-inbox"></i> Surat Masuk</a></li>
            <?php endif; ?>
<li><a href="../modul_surat_keluar/ambil_nomor.php" class="<?= ($current_page == 'ambil_nomor.php') ? 'active' : ''; ?>"><i class="fa-solid fa-paper-plane"></i> Nomor Surat</a></li>
<li><a href="../modul_surat_keluar/surat_keluar.php" class="<?= ($current_page == 'surat_keluar.php') ? 'active' : ''; ?>"><i class="fa-solid fa-paper-plane"></i> Surat Keluar</a></li>
<li><a href="../disposisi/disposisi.php" class="<?= ($current_page == 'disposisi.php') ? 'active' : ''; ?> id="nav-badge-disposisi""><i class="fa-solid fa-share-nodes"></i> Disposisi</a></li>
            <li><a href="../earchive/earsip.php" class="<?= ($current_page == 'earsip.php') ? 'active' : ''; ?>"><i class="fa-solid fa-box-archive"></i> E-Archive</a></li>
            
            <?php if ($role_sidebar == 'Admin_TU' || $role_sidebar == 'Kepala_Sekolah'): ?>
        <li><a href="../laporan.php" class="<?= ($current_page == 'laporan.php' || $current_page == 'export_pdf.php') ? 'active' : ''; ?>"><i class="fa-solid fa-file-invoice"></i> Laporan</a></li>
        <?php endif; ?>

            <?php if ($role_sidebar == 'Admin_TU'): ?>
            <li class="mt-3 mb-1 ms-3 text-uppercase text-white-50" style="font-size: 0.75rem;">Manajemen</li>
            <li><a href="../pengguna.php" class="<?= ($current_page == 'pengguna.php') ? 'active' : ''; ?>"><i class="fa-solid fa-users"></i> Pengguna & Hak Akses</a></li>
            <li><a href="../log/audit_log.php" class="<?= ($current_page == 'audit_log.php') ? 'active' : ''; ?>"><i class="fa-solid fa-shield-halved"></i> Audit Log</a></li>
            <?php endif; ?>
        </ul>
        
        <?php if ($id_user_yang_login == $id_user_it_dari_setting) : ?>
            <li class="sidebar-item">
                <a href="../pengaduan/dashboard_it.php" class="sidebar-link">
                    <i class="fa-solid fa-headset"></i>
                    <span>Dashboard IT</span>
                </a>
            </li>
        <?php endif; ?>
    </div>
   <?php
// Pastikan timezone sudah disetel di bagian atas file atau di config
date_default_timezone_set('Asia/Jakarta');

$hari_ini     = date('N'); // 1 (Senin) s/d 7 (Minggu)
$jam_sekarang = date('H:i');
$jam_mulai    = "07:00";
$jam_selesai  = "15:30";

// Cek Jam Kerja: Senin-Jumat & di antara jam operasional
$is_jam_kerja = ($hari_ini >= 1 && $hari_ini <= 5) && ($jam_sekarang >= $jam_mulai && $jam_sekarang <= $jam_selesai);
?>

<div class="mt-auto"></div> 
    
<div class="p-3 border-top border-secondary border-opacity-25 mt-3">
    <?php if ($is_jam_kerja): ?>
        <a href="../pengaduan/riwayat_pengaduan.php" 
           class="btn btn-danger w-100 d-flex justify-content-center align-items-center fw-bold shadow-sm" 
           style="border-radius: 10px; transition: 0.3s;"
           title="Klik untuk membuat laporan atau cek riwayat">
            <i class="fa-solid fa-headset fs-5 me-2"></i> 
            <span>Pusat Bantuan IT</span>
        </a>
        <div class="text-center mt-2" style="font-size: 0.7rem; color: #2ecc71; font-weight: bold;">
            <i class="fa-solid fa-circle fa-beat me-1" style="font-size: 0.5rem;"></i> Layanan Online (SLA)
        </div>

    <?php else: ?>
        <button class="btn btn-secondary w-100 d-flex justify-content-center align-items-center fw-bold shadow-sm opacity-75" 
                style="border-radius: 10px; cursor: not_allowed;"
                onclick="alert('Pusat Bantuan IT Sedang Offline.\n\nLayanan pengaduan dibuka kembali pada hari kerja (Senin-Jumat) jam 07:00 s/d 15:30 WIB.')">
            <i class="fa-solid fa-lock fs-5 me-2"></i> 
            <span>Layanan Offline</span>
        </button>
        <div class="text-center mt-2" style="font-size: 0.7rem; color: rgba(255,255,255,0.4);">
            Buka Kembali Jam 07:00 WIB
        </div>
    <?php endif; ?>
</div>
</div>

