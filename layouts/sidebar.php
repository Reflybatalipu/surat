<?php 
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    echo "<script>alert('Akses ditolak. Silakan login terlebih dahulu.'); window.location='login.php';</script>";
    exit;
}

// Kita simpan nama_role ke dalam variabel agar kodenya lebih rapi dan singkat
$role_sidebar = isset($_SESSION['nama_role']) ? $_SESSION['nama_role'] : ''; 
$is_kepsek_sidebar = ($role_sidebar == 'Kepala_Sekolah' || $role_sidebar == 'Kepala Sekolah');
$is_admin_sidebar  = ($role_sidebar == 'Admin_TU');

// Ambil nama file yang sedang dibuka
$current_page = basename($_SERVER['PHP_SELF']);

// 1. Ambil ID User yang berhak jadi IT dari tabel pengaturan
$query_it = mysqli_query($koneksi, "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'helpdesk_user_id'");
$data_it = mysqli_fetch_assoc($query_it);

// Gunakan ternary operator untuk menghindari error jika data kosong
$id_user_it_dari_setting = $data_it ? $data_it['nilai_pengaturan'] : '';

// 2. Ambil ID user yang lagi login saat ini
// (Pastikan nama variabel session ini sesuai dengan yang kamu buat saat proses login)
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; 
$id_user_yang_login = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0; 

// 3. Counter badge sidebar — role-based, mengikuti scope dashboard
$badge_surat_masuk  = 0;
$badge_surat_keluar = 0;
$badge_disposisi    = 0;

if ($is_admin_sidebar) {
    // Admin TU: surat masuk baru yang belum didisposisi + surat keluar menunggu review
    $q_badge_sm = mysqli_query($koneksi, "SELECT COUNT(sm.id) as t 
                                           FROM surat_masuk sm 
                                           LEFT JOIN disposisi d ON sm.id = d.surat_id 
                                           WHERE sm.status_workflow = 'Baru' 
                                           AND sm.deleted_at IS NULL 
                                           AND d.id IS NULL");
    $badge_surat_masuk = $q_badge_sm ? (int)mysqli_fetch_assoc($q_badge_sm)['t'] : 0;

    $q_badge_sk = mysqli_query($koneksi, "SELECT COUNT(id) as t 
                                           FROM surat_keluar 
                                           WHERE status_workflow = 'Review' 
                                           AND deleted_at IS NULL");
    $badge_surat_keluar = $q_badge_sk ? (int)mysqli_fetch_assoc($q_badge_sk)['t'] : 0;

} elseif ($is_kepsek_sidebar) {
    // Kepala Sekolah: surat masuk perlu disposisi + surat keluar yang butuh persetujuan
    $q_badge_sm = mysqli_query($koneksi, "SELECT COUNT(sm.id) as t 
                                           FROM surat_masuk sm 
                                           LEFT JOIN disposisi d ON sm.id = d.surat_id 
                                           WHERE sm.status_workflow = 'Baru' 
                                           AND sm.deleted_at IS NULL 
                                           AND d.id IS NULL");
    $badge_surat_masuk = $q_badge_sm ? (int)mysqli_fetch_assoc($q_badge_sm)['t'] : 0;

    $q_badge_sk = mysqli_query($koneksi, "SELECT COUNT(id) as t 
                                           FROM surat_keluar 
                                           WHERE status_workflow = 'Review' 
                                           AND deleted_at IS NULL");
    $badge_surat_keluar = $q_badge_sk ? (int)mysqli_fetch_assoc($q_badge_sk)['t'] : 0;

} else {
    // Guru/Staff: disposisi aktif untuk dirinya + draft/review surat keluar milik sendiri
    // Catatan: acted_at dipakai sebagai indikator disposisi sudah ditindaklanjuti.
    $q_badge_disposisi = mysqli_query($koneksi, "SELECT COUNT(d.id) as t 
                                                  FROM disposisi d 
                                                  JOIN surat_masuk sm ON d.surat_id = sm.id 
                                                  WHERE d.ke_user_id = '$user_id' 
                                                  AND sm.deleted_at IS NULL
                                                  AND d.acted_at IS NULL
                                                  AND (d.status IS NULL OR d.status NOT IN ('Selesai','Ditindaklanjuti','Done','Completed'))");
    $badge_disposisi = $q_badge_disposisi ? (int)mysqli_fetch_assoc($q_badge_disposisi)['t'] : 0;

    $q_badge_sk = mysqli_query($koneksi, "SELECT COUNT(id) as t 
                                           FROM surat_keluar 
                                           WHERE draft_by = '$user_id' 
                                           AND status_workflow IN ('Draft','Review') 
                                           AND deleted_at IS NULL");
    $badge_surat_keluar = $q_badge_sk ? (int)mysqli_fetch_assoc($q_badge_sk)['t'] : 0;
}

// Pastikan timezone sudah disetel di bagian atas file atau di config
date_default_timezone_set('Asia/Jakarta');

$hari_ini     = date('N'); // 1 (Senin) s/d 7 (Minggu)
$jam_sekarang = date('H:i');
$jam_mulai    = "07:00";
$jam_selesai  = "15:30";

// Cek Jam Kerja: Senin-Jumat & di antara jam operasional
$is_jam_kerja = ($hari_ini >= 1 && $hari_ini <= 5) && ($jam_sekarang >= $jam_mulai && $jam_sekarang <= $jam_selesai);

// Helper tampilan badge agar tidak mengulang kondisi di setiap menu
function render_sidebar_badge($jumlah, $key, $id = '') {
    $jumlah = (int)$jumlah;
    $style_hide = ($jumlah > 0) ? '' : ' style="display:none;"';
    $id_attr = ($id !== '') ? ' id="' . htmlspecialchars($id) . '"' : '';
    $key_attr = ' data-sidebar-badge="' . htmlspecialchars($key) . '"';
    return '<span' . $id_attr . $key_attr . ' class="sidebar-badge"' . $style_hide . '>' . ($jumlah > 99 ? '99+' : $jumlah) . '</span>';
}
?>

<style>
    .sidebar-menu a {
        position: relative;
        gap: 6px;
    }
    .sidebar-menu a .menu-label {
        flex: 1;
        min-width: 0;
    }
    .sidebar-badge {
        min-width: 20px;
        height: 20px;
        padding: 0 6px;
        border-radius: 999px;
        background: #fff;
        color: var(--simpers-primary, #4A70A9);
        font-size: .68rem;
        font-weight: 800;
        line-height: 20px;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,.16);
        margin-left: auto;
        transition: transform .18s ease, opacity .18s ease;
    }
    .sidebar-menu a.active .sidebar-badge,
    .sidebar-menu a:hover .sidebar-badge {
        background: #fef3c7;
        color: #92400e;
    }
    .sidebar-link {
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-radius: 8px;
        transition: 0.2s;
        margin-bottom: 5px;
        gap: 6px;
    }
    .sidebar-link:hover,
    .sidebar-link.active {
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        font-weight: 500;
    }
    .sidebar-link i {
        width: 25px;
        font-size: 1.1rem;
    }
</style>

<nav class="sidebar d-none d-lg-block">
    <div class="brand-logo">
        <i class="fa-solid fa-envelopes-bulk me-2"></i> SIMPERS
    </div>
    <ul class="sidebar-menu">
        <li>
            <a href="../dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span class="menu-label">Dashboard</span>
            </a>
        </li>
        
        <li class="mt-3 mb-1 ms-3 text-uppercase text-white-50" style="font-size: 0.75rem;">Menu Utama</li>
        
        <?php if ($is_admin_sidebar || $is_kepsek_sidebar): ?>
        <li>
            <a href="../modul_surat_masuk/surat_masuk.php" class="<?= ($current_page == 'surat_masuk.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-inbox"></i>
                <span class="menu-label">Surat Masuk</span>
                <?= render_sidebar_badge($badge_surat_masuk, 'surat_masuk', 'nav-badge-surat-masuk') ?>
            </a>
        </li>
        <?php endif; ?>

        <li>
            <a href="../modul_surat_keluar/ambil_nomor.php" class="<?= ($current_page == 'ambil_nomor.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-ticket"></i>
                <span class="menu-label">Nomor Surat</span>
            </a>
        </li>
        <li>
            <a href="../modul_surat_keluar/surat_keluar.php" class="<?= ($current_page == 'surat_keluar.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-paper-plane"></i>
                <span class="menu-label">Surat Keluar</span>
                <?= render_sidebar_badge($badge_surat_keluar, 'surat_keluar', 'nav-badge-surat-keluar') ?>
            </a>
        </li>
        <li>
            <a href="../disposisi/disposisi.php" class="<?= ($current_page == 'disposisi.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-share-nodes"></i>
                <span class="menu-label">Disposisi</span>
                <?= render_sidebar_badge($badge_disposisi, 'disposisi', 'nav-badge-disposisi') ?>
            </a>
        </li>
        <li>
            <a href="../earchive/earsip.php" class="<?= ($current_page == 'earsip.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-box-archive"></i>
                <span class="menu-label">E-Archive</span>
            </a>
        </li>

        <?php if ($is_admin_sidebar || $is_kepsek_sidebar): ?>
        <li>
            <a href="../laporan.php" class="<?= ($current_page == 'laporan.php' || $current_page == 'export_pdf.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-invoice"></i>
                <span class="menu-label">Laporan</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if ($is_admin_sidebar): ?>
        <li class="mt-3 mb-1 ms-3 text-uppercase text-white-50" style="font-size: 0.75rem;">Manajemen</li>
        <li>
            <a href="../pengguna.php" class="<?= ($current_page == 'pengguna.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span class="menu-label">Pengguna & Hak Akses</span>
            </a>
        </li>
        <li>
            <a href="../log/audit_log.php" class="<?= ($current_page == 'audit_log.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-shield-halved"></i>
                <span class="menu-label">Audit Log</span>
            </a>
        </li>
        <?php endif; ?>
    
        <?php if ($user_id == $id_user_it_dari_setting) : ?>
        <li class="sidebar-item">
            <a href="../pengaduan/dashboard_it.php" class="sidebar-link <?= ($current_page == 'dashboard_it.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-headset"></i>
                <span class="menu-label">Dashboard IT</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

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
            <li>
                <a href="../dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span class="menu-label">Dashboard</span>
                </a>
            </li>
            
            <?php if ($is_admin_sidebar || $is_kepsek_sidebar): ?>
            <li>
                <a href="../modul_surat_masuk/surat_masuk.php" class="<?= ($current_page == 'surat_masuk.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-inbox"></i>
                    <span class="menu-label">Surat Masuk</span>
                    <?= render_sidebar_badge($badge_surat_masuk, 'surat_masuk', 'mobile-badge-surat-masuk') ?>
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="../modul_surat_keluar/ambil_nomor.php" class="<?= ($current_page == 'ambil_nomor.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="menu-label">Nomor Surat</span>
                </a>
            </li>
            <li>
                <a href="../modul_surat_keluar/surat_keluar.php" class="<?= ($current_page == 'surat_keluar.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span class="menu-label">Surat Keluar</span>
                    <?= render_sidebar_badge($badge_surat_keluar, 'surat_keluar', 'mobile-badge-surat-keluar') ?>
                </a>
            </li>
            <li>
                <a href="../disposisi/disposisi.php" class="<?= ($current_page == 'disposisi.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-share-nodes"></i>
                    <span class="menu-label">Disposisi</span>
                    <?= render_sidebar_badge($badge_disposisi, 'disposisi', 'mobile-badge-disposisi') ?>
                </a>
            </li>
            <li>
                <a href="../earchive/earsip.php" class="<?= ($current_page == 'earsip.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-box-archive"></i>
                    <span class="menu-label">E-Archive</span>
                </a>
            </li>
            
            <?php if ($is_admin_sidebar || $is_kepsek_sidebar): ?>
            <li>
                <a href="../laporan.php" class="<?= ($current_page == 'laporan.php' || $current_page == 'export_pdf.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-file-invoice"></i>
                    <span class="menu-label">Laporan</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($is_admin_sidebar): ?>
            <li class="mt-3 mb-1 ms-3 text-uppercase text-white-50" style="font-size: 0.75rem;">Manajemen</li>
            <li>
                <a href="../pengguna.php" class="<?= ($current_page == 'pengguna.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-users"></i>
                    <span class="menu-label">Pengguna & Hak Akses</span>
                </a>
            </li>
            <li>
                <a href="../log/audit_log.php" class="<?= ($current_page == 'audit_log.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span class="menu-label">Audit Log</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($id_user_yang_login == $id_user_it_dari_setting) : ?>
            <li class="sidebar-item">
                <a href="../pengaduan/dashboard_it.php" class="sidebar-link <?= ($current_page == 'dashboard_it.php') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-headset"></i>
                    <span class="menu-label">Dashboard IT</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>

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
