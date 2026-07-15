<?php
session_start();
include '../config/koneksi.php';

// 🛡️ KEAMANAN: Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

$user_id_sekarang = $_SESSION['user_id'];
$role_sekarang = $_SESSION['nama_role'];

// 🛡️ CSRF token untuk semua form POST di halaman ini
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Helper tampilan status OCR agar konsisten di tabel dan kartu mobile
function renderOcrBadge($status_ocr) {
    $status = strtolower(trim((string)$status_ocr));
    if ($status === 'completed') {
        return '<span class="badge bg-success-subtle text-success border border-success-subtle mt-1"><i class="fa-solid fa-check-circle me-1"></i>OCR selesai</span>';
    }
    if ($status === 'failed') {
        return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>OCR gagal</span>';
    }
    if ($status === 'processing') {
        return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle mt-1"><span class="spinner-border spinner-border-sm me-1" style="width:.65rem;height:.65rem;"></span>OCR proses</span>';
    }
    return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle mt-1"><i class="fa-regular fa-clock me-1"></i>OCR antrean</span>';
}

// Escaping aman untuk nilai yang disisipkan ke dalam string JS
// yang berada di dalam atribut HTML (mis. onclick="fungsi('...')").
// addslashes() saja TIDAK cukup: HTML tidak mengenal escape backslash,
// jadi tanda kutip literal tetap bisa memutus atribut. Karena itu hasil
// addslashes() masih perlu di-htmlspecialchars() dengan ENT_QUOTES.
function jsAttr($value) {
    return htmlspecialchars(addslashes((string)$value), ENT_QUOTES, 'UTF-8');
}


$keyword = "";
$query_pencarian = "";
if (isset($_GET['cari']) && $_GET['cari'] != '') {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['cari']);
    $query_pencarian = " AND (sm.nomor_surat LIKE '%$keyword%' 
                              OR sm.pengirim LIKE '%$keyword%' 
                              OR sm.perihal LIKE '%$keyword%' 
                              OR sm.ocr_text LIKE '%$keyword%')";
}

// ========================================================
// 1. AMBIL SEMUA DATA SURAT & SIMPAN KE ARRAY
// ========================================================
$show_mode = isset($_GET['show']) ? $_GET['show'] : '';
$limit_sql = "";

if ($show_mode !== 'all') {
    $limit_sql = "LIMIT 20";
}

if ($role_sekarang == 'Admin_TU' || $role_sekarang == 'Kepala_Sekolah') {
    $sql = "SELECT sm.*, uk.nama_unit 
            FROM surat_masuk sm
            LEFT JOIN unit_kerja uk ON sm.unit_tujuan_id = uk.id
            WHERE sm.deleted_at IS NULL 
            $query_pencarian
            ORDER BY sm.id DESC
        	 $limit_sql";
} else {
    $sql = "SELECT DISTINCT sm.*, uk.nama_unit 
            FROM surat_masuk sm
            LEFT JOIN unit_kerja uk ON sm.unit_tujuan_id = uk.id
            LEFT JOIN disposisi d ON sm.id = d.surat_id
            WHERE sm.deleted_at IS NULL 
            $query_pencarian
            AND (sm.unit_tujuan_id = (SELECT unit_id FROM users WHERE id = '$user_id_sekarang') 
                 OR d.ke_user_id = '$user_id_sekarang')
            ORDER BY sm.id DESC
             $limit_sql";
}

$query = mysqli_query($koneksi, $sql);
$data_surat = [];
$surat_ids = [];
while ($row = mysqli_fetch_array($query)) {
    $data_surat[] = $row;
    $surat_ids[] = $row['id'];
}

// ========================================================
// 2. AMBIL DATA PEGAWAI UNTUK MODAL DISPOSISI
// ========================================================
$q_user = mysqli_query($koneksi, "SELECT u.id, u.nama_lengkap, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.is_active = 1 AND u.id != '$user_id_sekarang' ORDER BY r.id ASC");
$daftar_pegawai = [];
while($u = mysqli_fetch_array($q_user)){
    $daftar_pegawai[] = $u;
}

// ========================================================
// 3. AMBIL DATA LAMPIRAN MULTI-UPLOAD
// ========================================================
$lampiran_surat = [];
if (!empty($surat_ids)) {
    $ids_str = implode(',', $surat_ids);
    $q_lampiran = mysqli_query($koneksi, "SELECT * FROM lampiran_surat_masuk WHERE id_surat_masuk IN ($ids_str)");
    while($lamp = mysqli_fetch_assoc($q_lampiran)){
        $lampiran_surat[$lamp['id_surat_masuk']][] = $lamp;
    }
}

// ========================================================
// 4. AMBIL DATA AUDIT LOG UNTUK TRACKING TIMELINE
// ========================================================
$audit_logs = [];
if (!empty($surat_ids)) {
    $q_log = mysqli_query($koneksi, "
        SELECT a.action, a.created_at, a.record_id, a.user_id, u.nama_lengkap 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.table_name = 'surat_masuk' AND a.record_id IN ($ids_str) 
        ORDER BY a.created_at ASC
    ");
    while($log = mysqli_fetch_assoc($q_log)){
        $audit_logs[$log['record_id']][] = $log;
    }
}

// ========================================================
// 5. AMBIL DETAIL DISPOSISI UNTUK TIMELINE
//    Ditampilkan agar lacak jejak tidak hanya menyebut aksi,
//    tetapi juga kepada siapa surat diteruskan, instruksi, SLA, dan statusnya.
// ========================================================
$detail_disposisi = [];
if (!empty($surat_ids)) {
    $ids_str = implode(',', array_map('intval', $surat_ids));
    $q_disposisi_detail = mysqli_query($koneksi, "
        SELECT 
            d.id,
            d.surat_id,
            d.dari_user_id,
            d.ke_user_id,
            d.instruksi,
            d.laporan_tindak_lanjut,
            d.status_disposisi,
            d.status,
            d.batas_waktu_sla,
            d.read_at,
            d.acted_at,
            d.waktu_selesai,
            penerima.nama_lengkap AS nama_penerima,
            rp.nama_role AS role_penerima,
            pengirim.nama_lengkap AS nama_pengirim,
            log_disposisi.created_at AS disposisi_created_at,
            log_disposisi.action AS disposisi_log_action
        FROM disposisi d
        LEFT JOIN users penerima ON d.ke_user_id = penerima.id
        LEFT JOIN roles rp ON penerima.role_id = rp.id
        LEFT JOIN users pengirim ON d.dari_user_id = pengirim.id
        LEFT JOIN audit_logs log_disposisi 
            ON log_disposisi.table_name = 'disposisi'
            AND log_disposisi.record_id = d.id
            AND log_disposisi.action IN ('CREATE_DISPOSISI','FORWARD_DISPOSISI')
        WHERE d.surat_id IN ($ids_str)
        ORDER BY d.id ASC
    ");
    if ($q_disposisi_detail) {
        while($dis = mysqli_fetch_assoc($q_disposisi_detail)){
            $detail_disposisi[$dis['surat_id']][] = $dis;
        }
    }
}


// ========================================================
// 6. HELPER TIMELINE DISPOSISI
//    Penting: jangan tampilkan semua disposisi milik surat pada semua event.
//    Tiap event timeline hanya menampilkan penerima disposisi yang relevan
//    dengan aktor dan waktu batch tindakan tersebut.
// ========================================================
function ambilDetailDisposisiUntukLog($semua_disposisi_surat, $log) {
    if (empty($semua_disposisi_surat) || empty($log['created_at'])) {
        return [];
    }

    $log_user_id = isset($log['user_id']) ? (int)$log['user_id'] : 0;
    $log_time = strtotime($log['created_at']);

    // 1) Kandidat utama: sama aktor/pemberi disposisi.
    $candidates = [];
    foreach ($semua_disposisi_surat as $dis) {
        $dari_user_id = isset($dis['dari_user_id']) ? (int)$dis['dari_user_id'] : 0;
        if ($log_user_id > 0 && $dari_user_id !== $log_user_id) {
            continue;
        }
        $candidates[] = $dis;
    }

    if (empty($candidates)) {
        return [];
    }

    // 2) Jika ada waktu audit per baris disposisi, ambil batch yang waktunya paling dekat
    //    dengan audit log surat_masuk saat DISPOSISI_SURAT / TERUSKAN_DISPOSISI dicatat.
    $with_time = [];
    foreach ($candidates as $dis) {
        if (!empty($dis['disposisi_created_at'])) {
            $dis_time = strtotime($dis['disposisi_created_at']);
            if ($dis_time !== false) {
                $dis['_diff_seconds'] = abs($log_time - $dis_time);
                $dis['_time_seconds'] = $dis_time;
                $with_time[] = $dis;
            }
        }
    }

    if (!empty($with_time)) {
        usort($with_time, function($a, $b) {
            return ($a['_diff_seconds'] ?? PHP_INT_MAX) <=> ($b['_diff_seconds'] ?? PHP_INT_MAX);
        });

        $closest_time = $with_time[0]['_time_seconds'];
        $closest_diff = $with_time[0]['_diff_seconds'];

        // Batas 5 menit agar satu batch disposisi yang dicatat berdekatan tetap masuk,
        // tetapi batch lama dari aktor yang sama tidak ikut tampil.
        if ($closest_diff <= 300) {
            $batch = [];
            foreach ($with_time as $dis) {
                if (abs($dis['_time_seconds'] - $closest_time) <= 15) {
                    unset($dis['_diff_seconds'], $dis['_time_seconds']);
                    $batch[] = $dis;
                }
            }
            return $batch;
        }
    }

    // 3) Fallback aman jika audit log disposisi lama belum lengkap:
    //    tampilkan hanya disposisi dari aktor yang sama, bukan semua surat.
    return $candidates;
}

function hitungRingkasanDisposisi($daftar_disposisi) {
    $ringkasan = [
        'total' => 0,
        'selesai' => 0,
        'waktu_selesai_terakhir' => null,
    ];

    if (empty($daftar_disposisi) || !is_array($daftar_disposisi)) {
        return $ringkasan;
    }

    foreach ($daftar_disposisi as $dis) {
        $ringkasan['total']++;
        $status_raw = $dis['status'] ?: ($dis['status_disposisi'] ?? 'Menunggu');
        $status_lower = strtolower(trim((string)$status_raw));

        if (in_array($status_lower, ['selesai', 'completed', 'finish', 'finished'])) {
            $ringkasan['selesai']++;
            if (!empty($dis['acted_at'])) {
                $time = strtotime($dis['acted_at']);
                if ($time && ($ringkasan['waktu_selesai_terakhir'] === null || $time > $ringkasan['waktu_selesai_terakhir'])) {
                    $ringkasan['waktu_selesai_terakhir'] = $time;
                }
            }
        }
    }

    return $ringkasan;
}

include '../layouts/header.php'; 
?>

<style>
/* Style Khusus Timeline Tracking */
.timeline-track { position: relative; padding-left: 30px; margin-bottom: 20px; }
.timeline-track::before { content: ''; position: absolute; top: 0; bottom: 0; left: 11px; width: 2px; background: #e9ecef; }
.timeline-item { position: relative; margin-bottom: 20px; }
.timeline-item::before { content: ''; position: absolute; top: 5px; left: -24px; width: 12px; height: 12px; border-radius: 50%; background: #17a2b8; border: 2px solid #fff; box-shadow: 0 0 0 2px #17a2b8; }
.timeline-date { font-size: 0.85em; color: #6c757d; font-weight: bold; }
.timeline-content { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px; }
.timeline-disposisi-detail { margin-top:10px; border:1px solid #dbeafe; background:#f8fbff; border-radius:12px; overflow:hidden; }
.timeline-disposisi-head { padding:9px 12px; background:#eaf2ff; color:#1d4ed8; font-weight:800; font-size:.82rem; display:flex; align-items:center; gap:7px; }
.timeline-disposisi-body { padding:10px 12px; }
.timeline-disposisi-person { display:flex; align-items:flex-start; gap:9px; padding:9px 0; border-bottom:1px dashed #dbeafe; }
.timeline-disposisi-person:last-child { border-bottom:none; padding-bottom:0; }
.timeline-disposisi-avatar { width:34px; height:34px; border-radius:12px; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.timeline-disposisi-name { font-weight:800; color:#0f172a; font-size:.86rem; }
.timeline-disposisi-role { color:#64748b; font-size:.74rem; }
.timeline-disposisi-meta { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
.timeline-mini-badge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:999px; font-size:.68rem; font-weight:700; }
.timeline-mini-badge.wait { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; }
.timeline-mini-badge.done { background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.timeline-mini-badge.info { background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; }
.timeline-disposisi-instruksi { margin-top:8px; padding:8px 10px; background:#fff; border-left:3px solid #4A70A9; border-radius:8px; color:#475569; font-size:.78rem; }
.timeline-disposisi-finish { margin:10px 12px 12px; padding:10px 12px; border-radius:10px; background:#ecfdf5; border:1px solid #bbf7d0; color:#166534; font-size:.8rem; font-weight:700; display:flex; align-items:flex-start; gap:8px; }
.timeline-disposisi-finish small { display:block; color:#15803d; font-weight:500; margin-top:2px; }
	.timeline-stage-finish { background:#f8fafc; border-color:#e2e8f0; color:#475569; }
	.timeline-stage-finish small { color:#64748b; }
	.timeline-global-finish .timeline-content { background:#ecfdf5; border:1px solid #bbf7d0; color:#166534; }
	.timeline-global-finish .timeline-content strong { color:#15803d !important; }
.disposisi-search-box { position:sticky; top:0; z-index:2; background:#fff; padding-bottom:10px; margin-bottom:8px; border-bottom:1px solid #eef2f7; }
.disposisi-user-item { border-radius:9px; padding:6px 8px; transition:background .15s ease; }
.disposisi-user-item:hover { background:#f8fafc; }
.disposisi-empty-result { display:none; padding:12px; text-align:center; color:#94a3b8; font-size:.82rem; }


/* UI Modern Surat Masuk */
.sm-page-content { opacity: 0; transform: translateY(8px); transition: opacity .25s ease, transform .25s ease; }
body.sm-ready .sm-page-content { opacity: 1; transform: none; }
body.sm-ready #smPageSkeleton { display: none !important; }
.sm-skeleton-card { background:#fff; border:1px solid #e9eef5; border-radius:16px; box-shadow:0 3px 18px rgba(15,25,35,.06); padding:18px; }
.sm-skeleton-line, .sm-skeleton-pill, .sm-skeleton-box { display:block; background:linear-gradient(90deg,#eef2f7 25%,#f8fafc 37%,#eef2f7 63%); background-size:400% 100%; animation:smShimmer 1.25s ease-in-out infinite; border-radius:10px; }
.sm-skeleton-line { height:14px; margin-bottom:12px; }
.sm-skeleton-pill { height:34px; border-radius:999px; }
.sm-skeleton-box { height:48px; border-radius:12px; margin-bottom:10px; }
@keyframes smShimmer { 0%{background-position:100% 0} 100%{background-position:0 0} }
.sm-empty-state { padding:54px 24px; text-align:center; color:#64748b; }
.sm-empty-icon { width:82px; height:82px; border-radius:28px; margin:0 auto 16px; display:flex; align-items:center; justify-content:center; background:#eaf0f8; color:#4A70A9; font-size:2rem; box-shadow:inset 0 0 0 1px rgba(74,112,169,.08); }
.sm-empty-title { font-weight:800; color:#0f172a; margin-bottom:4px; }
.sm-empty-text { font-size:.9rem; max-width:420px; margin:0 auto 16px; }
.sm-ocr-row { display:flex; flex-wrap:wrap; gap:6px; margin-top:6px; }
.sm-action-needed { background:#fff7ed; color:#c2410c; border:1px solid #fed7aa; border-radius:999px; padding:2px 8px; font-size:.68rem; font-weight:700; display:inline-flex; align-items:center; gap:4px; }
.sm-upload-feedback { display:none; margin-top:8px; font-size:.78rem; border-radius:8px; padding:8px 10px; }
.sm-upload-feedback.is-error { display:block; background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
.sm-upload-feedback.is-ok { display:block; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; }
.sm-processing-overlay { position:fixed; inset:0; z-index:20000; background:rgba(15,23,42,.28); backdrop-filter:blur(3px); display:none; align-items:center; justify-content:center; }
.sm-processing-overlay.show { display:flex; }
.sm-processing-box { background:#fff; border-radius:16px; padding:20px 24px; box-shadow:0 18px 60px rgba(15,23,42,.22); display:flex; align-items:center; gap:12px; color:#0f172a; font-weight:700; }
@media(max-width:767.98px){ .sm-page-content > .d-flex:first-child { align-items:stretch !important; } .sm-page-content > .d-flex:first-child > .d-flex { width:100%; flex-direction:column; } .sm-page-content form.d-flex { width:100%; } .sm-page-content .input-group { width:100%; } .sm-page-content .input-group input { min-width:0 !important; } }

</style>

<div id="smProcessingOverlay" class="sm-processing-overlay" aria-hidden="true">
    <div class="sm-processing-box">
        <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
        <span>Memproses permintaan...</span>
    </div>
</div>

<div id="smPageSkeleton" class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <span class="sm-skeleton-line" style="width:240px;height:28px;"></span>
        <span class="sm-skeleton-pill" style="width:330px;"></span>
    </div>
    <div class="sm-skeleton-card mb-3">
        <span class="sm-skeleton-line" style="width:160px;"></span>
        <span class="sm-skeleton-box"></span>
        <span class="sm-skeleton-box"></span>
        <span class="sm-skeleton-box"></span>
    </div>
</div>

<div id="smPageContent" class="sm-page-content">
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-inbox me-2 text-primary"></i> Data Surat Masuk</h4>
    
    <div class="d-flex gap-2">
        <form action="" method="GET" class="d-flex js-loading-form">
            <div class="input-group shadow-sm">
                <input type="text" name="cari" class="form-control" placeholder="Cari isi surat, nomor..." value="<?= htmlspecialchars($keyword) ?>" style="min-width: 250px;">
                <button type="submit" class="btn btn-primary" title="Cari Data"><i class="fa-solid fa-search"></i></button>
                <?php if($keyword != ''): ?>
                    <a href="surat_masuk.php" class="btn btn-danger" title="Reset Pencarian"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </div>
        </form>

        <?php if($_SESSION['nama_role'] == 'Admin_TU'): ?>
        <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            <i class="fa-solid fa-plus me-1"></i> Registrasi Surat
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if($keyword != ''): ?>
    <div class="alert alert-info py-2 shadow-sm mb-4">
        <i class="fa-solid fa-magnifying-glass me-2"></i> Menampilkan hasil pencarian untuk: <strong>"<?= htmlspecialchars($keyword) ?>"</strong>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0 p-md-4">
        <div class="mb-3 text-end px-3 pt-3">
            <?php if ($show_mode === 'all'): ?>
                <?php 
                $params = $_GET;
                unset($params['show']);
                ?>
                <a href="?<?= http_build_query($params) ?>" class="btn btn-sm btn-secondary">
                    <i class="fa-solid fa-list"></i> Tampilkan 20 Data
                </a>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['show' => 'all'])) ?>" class="btn btn-sm btn-primary">
                    <i class="fa-solid fa-eye"></i> Tampilkan Semua
                </a>
            <?php endif; ?>
        </div>
        <?php if (empty($data_surat)): ?>
            <div class='sm-empty-state'>
                <div class='sm-empty-icon'><i class='fa-solid fa-envelope-open-text'></i></div>
                <div class='sm-empty-title'>Tidak ada surat masuk ditemukan</div>
                <div class='sm-empty-text'>Belum ada surat yang sesuai dengan pencarian atau akses Anda saat ini.</div>
                <?php if($_SESSION['nama_role'] == 'Admin_TU'): ?>
                    <button type='button' class='btn btn-primary btn-sm fw-bold' data-bs-toggle='modal' data-bs-target='#modalTambah'>
                        <i class='fa-solid fa-plus me-1'></i> Registrasi Surat Masuk
                    </button>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="15%" class="ps-3">No. & Tgl Surat</th>
                            <th width="20%">Pengirim</th>
                            <th width="25%">Perihal</th>
                            <th width="15%">Keamanan & Tujuan</th>
                            <th width="10%">Status</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_surat as $data): 
                            $warna_status = match($data['status_workflow']) {
                                'Baru' => 'bg-primary',
                                'Disposisi' => 'bg-warning text-dark',
                                'Selesai' => 'bg-success',
                                'Diarsipkan' => 'bg-secondary',
                                default => 'bg-light text-dark'
                            };
                            $warna_sifat = match($data['klasifikasi']) {
                                'Biasa' => 'text-success',
                                'Penting' => 'text-warning',
                                'Rahasia' => 'text-danger',
                                default => 'text-muted'
                            };
                        ?>
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold font-monospace text-primary" style="font-size:0.9rem;"><?= htmlspecialchars($data['nomor_surat']); ?></div>
                                <div class="text-muted small"><i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($data['tanggal_surat'])); ?></div>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars($data['pengirim']); ?></td>
                            <td style="font-size:0.9rem;">
                                <?= htmlspecialchars($data['perihal']); ?>
                                <?php if(!empty($data['ocr_text']) && $keyword != '' && stripos($data['ocr_text'], $keyword) !== false): ?>
                                    <br><span class="badge bg-light text-primary border border-primary mt-1 small" title="Kata kunci ditemukan di dalam gambar fisik surat"><i class="fa-solid fa-microchip me-1"></i> Ditemukan via OCR</span>
                                <?php endif; ?>
                                <div class="sm-ocr-row"><?= renderOcrBadge($data['status_ocr'] ?? '') ?></div>
                            </td>
                            <td>
                                <div class="fw-bold <?= $warna_sifat; ?> small"><i class="fa-solid fa-shield-halved me-1"></i> <?= htmlspecialchars($data['klasifikasi']); ?></div>
                                <div class="text-muted small">Tujuan: <?= htmlspecialchars($data['nama_unit'] ?? 'Belum ditentukan'); ?></div>
                            </td>
                            <td><span class="badge <?= $warna_status; ?>"><?= htmlspecialchars($data['status_workflow']); ?></span><?php if($_SESSION['nama_role'] == 'Admin_TU' && $data['status_workflow'] == 'Baru'): ?><div class="mt-1"><span class="sm-action-needed"><i class="fa-solid fa-bolt"></i> Perlu disposisi</span></div><?php endif; ?></td>
                            <td class="text-center">
                                
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalTimeline<?= $data['id']; ?>" title="Lacak Jejak Surat">
                                    <i class="fas fa-history"></i>
                                </button>
                                
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFile<?= $data['id']; ?>" title="Lihat Dokumen">
                                    <i class="fa-solid fa-folder-open"></i>
                                </button>

                                <?php if($_SESSION['nama_role'] == 'Admin_TU' && $data['status_workflow'] == 'Baru'): ?>
                                    <button class="btn btn-sm btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $data['id']; ?>" title="Edit Surat"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="btn btn-sm btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalDisposisi<?= $data['id']; ?>" title="Teruskan Surat"><i class="fa-solid fa-share-nodes"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $data['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none p-3 bg-light" style="max-height: 75vh; overflow-y: auto;">
                <?php foreach ($data_surat as $data): 
                    $warna_status = match($data['status_workflow']) {
                        'Baru' => 'bg-primary',
                        'Disposisi' => 'bg-warning text-dark',
                        'Selesai' => 'bg-success',
                        'Diarsipkan' => 'bg-secondary',
                        default => 'bg-light text-dark'
                    };
                    $warna_sifat = match($data['klasifikasi']) {
                        'Biasa' => 'text-success',
                        'Penting' => 'text-warning',
                        'Rahasia' => 'text-danger',
                        default => 'text-muted'
                    };
                ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;"><?= htmlspecialchars($data['nomor_surat']); ?></span>
                            <span class="badge <?= $warna_status; ?>" style="font-size: 0.7rem;"><?= htmlspecialchars($data['status_workflow']); ?></span>
                        </div>
                        
                        <div class="d-flex align-items-start mb-3">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="fa-solid fa-envelope-open-text fs-5"></i>
                            </div>
                            
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                                    <?= htmlspecialchars($data['perihal']); ?>
                                </h6>
                                <div class="text-muted small mb-1 text-truncate">
                                    <i class="fa-solid fa-building me-1"></i> <?= htmlspecialchars($data['pengirim']); ?>
                                </div>
                                <div class="text-muted small">
                                    <i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($data['tanggal_surat'])); ?>
                                </div>
                                <?php if(!empty($data['ocr_text']) && $keyword != '' && stripos($data['ocr_text'], $keyword) !== false): ?>
                                    <span class="badge bg-light text-primary border border-primary mt-2 small"><i class="fa-solid fa-microchip me-1"></i> Match OCR</span>
                                <?php endif; ?>
                                <div class="sm-ocr-row"><?= renderOcrBadge($data['status_ocr'] ?? '') ?></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded-3">
                            <div class="small fw-bold <?= $warna_sifat; ?>">
                                <i class="fa-solid fa-shield-halved me-1"></i> <?= htmlspecialchars($data['klasifikasi']); ?>
                            </div>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#modalTimeline<?= $data['id']; ?>" title="Lacak Jejak">
                                    <i class="fas fa-history"></i>
                                </button>
                                
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalFile<?= $data['id']; ?>">
                                    <i class="fa-solid fa-folder-open"></i>
                                </button>

                                <?php if($_SESSION['nama_role'] == 'Admin_TU' && $data['status_workflow'] == 'Baru'): ?>
                                    <button class="btn btn-sm btn-warning text-dark fw-bold" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $data['id']; ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <button class="btn btn-sm btn-outline-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalDisposisi<?= $data['id']; ?>"><i class="fa-solid fa-share-nodes"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $data['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php foreach ($data_surat as $data): ?>
    
    <div class="modal fade" id="modalTimeline<?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog text-start">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-route"></i> Lacak Perjalanan Surat Masuk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-light border small mb-4">
                        <strong>Nomor Surat:</strong> <?= htmlspecialchars($data['nomor_surat']); ?><br>
                        <strong>Perihal:</strong> <?= htmlspecialchars($data['perihal']); ?><br>
                        <strong>Status Saat Ini:</strong> <span class="badge bg-primary"><?= htmlspecialchars($data['status_workflow']); ?></span>
                    </div>
                    
                    <div class="timeline-track">
                        <?php
                        $id_surat = $data['id'];
                        if(isset($audit_logs[$id_surat]) && count($audit_logs[$id_surat]) > 0) {
                            foreach ($audit_logs[$id_surat] as $log) {
                                $action_raw = $log['action'] ?? '';

                                // FINISH/FOLLOW_UP adalah status lokal pada penerima disposisi.
                                // Jangan tampilkan sebagai baris timeline utama agar tidak menumpuk.
                                if (in_array($action_raw, ['FINISH_SURAT_MASUK', 'FOLLOW_UP_DISPOSISI'])) {
                                    continue;
                                }

                                $pesan_aksi = $action_raw;
                                $icon = "fas fa-circle"; $color = "text-secondary";

                                if ($log['action'] == 'CREATE_SURAT_MASUK') {
                                    $pesan_aksi = "Surat Diregistrasi (Masuk Sistem)"; $icon = "fas fa-envelope-open-text"; $color = "text-primary";
                                } elseif ($log['action'] == 'UPDATE_SURAT_MASUK') {
                                    $pesan_aksi = "Data Surat Diperbarui / Diedit"; $icon = "fas fa-pen-to-square"; $color = "text-warning";
                                } elseif (strpos($log['action'], 'DISPOSISI') !== false) {
                                    $pesan_aksi = "Surat Didisposisikan / Diteruskan"; $icon = "fas fa-share-nodes"; $color = "text-info";
                                } elseif (strpos($log['action'], 'SELESAI') !== false || $log['action'] == 'ARCHIVE_SURAT_MASUK') {
                                    $pesan_aksi = "Surat Selesai Diproses / Diarsipkan"; $icon = "fas fa-check-double"; $color = "text-success";
                                } else {
                                    $pesan_aksi = str_replace('_', ' ', $log['action']);
                                }
                                
                                $tanggal_log = date('d M Y, H:i', strtotime($log['created_at']));
                        ?>
                                <div class="timeline-item mb-3">
                                    <div class="timeline-date fw-bold" style="font-size: 0.85rem;"><?= $tanggal_log; ?></div>
                                    <div class="timeline-content">
                                        <strong class="<?= $color; ?>"><i class="<?= $icon; ?> me-1"></i> <?= $pesan_aksi; ?></strong><br>
                                        <small class="text-muted">Aktor: <?= $log['nama_lengkap'] ?: 'Sistem (Auto)'; ?></small>

                                        <?php
                                            $detail_disposisi_log = [];
                                            if (strpos($log['action'], 'DISPOSISI') !== false && !empty($detail_disposisi[$id_surat])) {
                                                $detail_disposisi_log = ambilDetailDisposisiUntukLog($detail_disposisi[$id_surat], $log);
                                            }
                                        ?>
                                        <?php if (!empty($detail_disposisi_log)): ?>
                                            <div class="timeline-disposisi-detail">
                                                <div class="timeline-disposisi-head">
                                                    <i class="fa-solid fa-users-viewfinder"></i>
                                                    Detail Penerima Disposisi
                                                </div>
                                                <div class="timeline-disposisi-body">
                                                    <?php
                                                        $total_penerima_disposisi = count($detail_disposisi_log);
                                                        $total_selesai_disposisi = 0;
                                                        $waktu_selesai_terakhir = null;

                                                        foreach($detail_disposisi_log as $cek_dis) {
                                                            $cek_status = strtolower($cek_dis['status'] ?: ($cek_dis['status_disposisi'] ?? 'Menunggu'));
                                                            if (in_array($cek_status, ['selesai', 'completed', 'finish', 'finished'])) {
                                                                $total_selesai_disposisi++;
                                                                if (!empty($cek_dis['acted_at'])) {
                                                                    $cek_time = strtotime($cek_dis['acted_at']);
                                                                    if ($cek_time && ($waktu_selesai_terakhir === null || $cek_time > $waktu_selesai_terakhir)) {
                                                                        $waktu_selesai_terakhir = $cek_time;
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                    <?php foreach($detail_disposisi_log as $dis):
                                                        $status_raw = $dis['status'] ?: ($dis['status_disposisi'] ?? 'Menunggu');
                                                        $status_lower = strtolower($status_raw);
                                                        $is_done = in_array($status_lower, ['selesai', 'completed', 'finish', 'finished']);
                                                        $status_label = $is_done ? 'Selesai' : 'Pending';
                                                        $badge_class = $is_done ? 'done' : 'wait';
                                                        $sla_text = !empty($dis['batas_waktu_sla']) ? date('d M Y, H:i', strtotime($dis['batas_waktu_sla'])) : 'Tanpa SLA';
                                                        $acted_text = !empty($dis['acted_at']) ? date('d M Y, H:i', strtotime($dis['acted_at'])) : '';
                                                    ?>
                                                    <div class="timeline-disposisi-person">
                                                        <div class="timeline-disposisi-avatar"><i class="fa-solid fa-user-check"></i></div>
                                                        <div class="flex-grow-1">
                                                            <div class="timeline-disposisi-name"><?= htmlspecialchars($dis['nama_penerima'] ?? 'Penerima tidak diketahui'); ?></div>
                                                            <div class="timeline-disposisi-role"><?= htmlspecialchars($dis['role_penerima'] ?? 'Role tidak diketahui'); ?></div>
                                                            <div class="timeline-disposisi-meta">
                                                                <span class="timeline-mini-badge <?= $badge_class; ?>">
                                                                    <i class="fa-solid <?= $is_done ? 'fa-circle-check' : 'fa-clock'; ?>"></i>
                                                                    <?= htmlspecialchars($status_label); ?>
                                                                </span>
                                                                <span class="timeline-mini-badge info">
                                                                    <i class="fa-regular fa-calendar"></i>
                                                                    SLA: <?= htmlspecialchars($sla_text); ?>
                                                                </span>
                                                                <?php if($acted_text): ?>
                                                                <span class="timeline-mini-badge done">
                                                                    <i class="fa-solid fa-check-double"></i>
                                                                    Ditindak: <?= htmlspecialchars($acted_text); ?>
                                                                </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <?php if(!empty($dis['instruksi'])): ?>
                                                                <div class="timeline-disposisi-instruksi">
                                                                    <strong>Instruksi:</strong> <?= nl2br(htmlspecialchars($dis['instruksi'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if(!empty($dis['laporan_tindak_lanjut'])): ?>
                                                                <div class="timeline-disposisi-instruksi" style="border-left-color:#16a34a;">
                                                                    <strong>Laporan tindak lanjut:</strong> <?= nl2br(htmlspecialchars($dis['laporan_tindak_lanjut'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <?php if($total_penerima_disposisi > 0 && $total_penerima_disposisi === $total_selesai_disposisi): ?>
                                                    <div class="timeline-disposisi-finish timeline-stage-finish">
                                                        <i class="fa-solid fa-circle-check mt-1"></i>
                                                        <div>
                                                            TAHAP DISPOSISI SELESAI
                                                            <small>Semua penerima pada tahap ini telah menindaklanjuti tugas<?= $waktu_selesai_terakhir ? ' pada ' . date('d M Y, H:i', $waktu_selesai_terakhir) : ''; ?>.</small>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                        <?php 
                            }

                            $ringkasan_surat_disposisi = hitungRingkasanDisposisi($detail_disposisi[$id_surat] ?? []);
                            if ($ringkasan_surat_disposisi['total'] > 0 && $ringkasan_surat_disposisi['total'] === $ringkasan_surat_disposisi['selesai']):
                                $tanggal_finish_global = $ringkasan_surat_disposisi['waktu_selesai_terakhir'] ? date('d M Y, H:i', $ringkasan_surat_disposisi['waktu_selesai_terakhir']) : date('d M Y, H:i');
                        ?>
                                <div class="timeline-item mb-3 timeline-global-finish">
                                    <div class="timeline-date fw-bold" style="font-size: 0.85rem;"><?= $tanggal_finish_global; ?></div>
                                    <div class="timeline-content">
                                        <strong><i class="fa-solid fa-check-double me-1"></i> FINISH SURAT MASUK</strong><br>
                                        <small>Seluruh penerima disposisi pada surat ini telah menyelesaikan tugasnya.</small>
                                    </div>
                                </div>
                        <?php
                            endif;
                        } else {
                            echo "<p class='text-center text-muted small'>Belum ada riwayat pergerakan tercatat.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit<?= $data['id'] ?? ''; ?>" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="aksi_surat_masuk.php" method="POST" enctype="multipart/form-data" class="js-loading-form">
                <div class="modal-content text-start">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-pen-to-square me-2"></i> Edit Surat Masuk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body bg-light">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id_surat" value="<?= htmlspecialchars($data['id'] ?? ''); ?>">
                        <input type="hidden" name="file_lama" value="<?= htmlspecialchars($data['file_path'] ?? ''); ?>">

                        <div class="row bg-white p-3 rounded shadow-sm mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Nomor Surat</label>
                                <input type="text" class="form-control" name="nomor_surat" value="<?= htmlspecialchars($data['nomor_surat'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Pengirim</label>
                                <input type="text" class="form-control" name="pengirim" value="<?= htmlspecialchars($data['pengirim']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Tanggal Surat</label>
                                <input type="date" class="form-control" name="tanggal_surat" value="<?= $data['tanggal_surat']; ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small">Tanggal Terima</label>
                                <input type="date" class="form-control" name="tanggal_terima" value="<?= $data['tanggal_terima']; ?>" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold small">Perihal</label>
                                <textarea class="form-control" name="perihal" rows="2" required><?= htmlspecialchars($data['perihal']); ?></textarea>
                            </div>
                        </div>

                        <div class="row bg-white p-3 rounded shadow-sm mb-3">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold small">Sifat Surat</label>
                                <select class="form-select" name="klasifikasi" required>
                                    <option value="Biasa" <?= ($data['klasifikasi'] == 'Biasa') ? 'selected' : ''; ?>>Biasa</option>
                                    <option value="Penting" <?= ($data['klasifikasi'] == 'Penting') ? 'selected' : ''; ?>>Penting</option>
                                    <option value="Rahasia" <?= ($data['klasifikasi'] == 'Rahasia') ? 'selected' : ''; ?>>Rahasia</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold small">Unit Tujuan</label>
                                <select class="form-select" name="unit_tujuan_id" required>
                                    <option value="">-- Pilih Unit --</option>
                                    <?php
                                    $q_unit_edit = mysqli_query($koneksi, "SELECT * FROM unit_kerja");
                                    while ($ue = mysqli_fetch_array($q_unit_edit)) {
                                        $sel = ($data['unit_tujuan_id'] == $ue['id']) ? 'selected' : '';
                                        echo "<option value='{$ue['id']}' $sel>{$ue['nama_unit']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-warning small mb-0 border-warning">
                            <i class="fa-solid fa-circle-info me-1"></i> Biarkan kosong jika <strong>Dokumen Surat Utama (PDF)</strong> tidak ingin diubah.
                            <input type="file" class="form-control mt-2" name="file_path" accept=".pdf">
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 bg-white">
                        <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_surat" class="btn btn-warning fw-bold text-dark"><i class="fa-solid fa-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>


   <div class="modal fade" id="modalFile<?= $data['id'] ?? 0; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark">
                    <i class="fa-solid fa-folder-open text-primary me-2"></i> Dokumen & Lampiran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Surat Utama</h6>
                
                <?php $file_utama = $data['file_path'] ?? ''; ?>
                
                <?php if(!empty($file_utama)): ?>
                     <div class="d-flex align-items-center p-2 border rounded mb-4 bg-white hover-shadow">
                        <div class="flex-grow-1 d-flex align-items-center overflow-hidden" onclick="bukaPreviewPDF('<?= jsAttr($data['file_path']); ?>')" style="cursor: pointer;">
                            <i class="fa-solid fa-file-pdf text-danger fs-3 me-3"></i>
                            <div class="text-truncate">
                                <span class="d-block fw-bold text-primary">Lihat Surat Utama</span>
                                <small class="text-muted text-truncate"><?= htmlspecialchars($data['file_path']); ?></small>
                            </div>
                        </div>
                        <div class="ms-2 border-start ps-2">
                            <button type="button" 
                            onclick="bukaPDF('<?= jsAttr($data['file_path']); ?>')" 
                            class="btn btn-outline-primary w-100 mb-4 text-start">
                        <i class="fa-solid fa-cloud-arrow-down fs-5"></i>
                    </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning small">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> File surat utama tidak tersedia di database.
                    </div>
                <?php endif; ?>

                                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3 mt-3">Lampiran Pendukung</h6>
                <?php 
                    $id_sm = $data['id'] ?? 0; 

                    if (isset($lampiran_surat[$id_sm]) && is_array($lampiran_surat[$id_sm]) && count($lampiran_surat[$id_sm]) > 0): 
                        foreach($lampiran_surat[$id_sm] as $lamp): 
                            // 1. Ambil nilai nama file dari database
                            $nama_file_db = $lamp['file_path'] ?? $lamp['path_file'] ?? ''; 

                            // 2. Bersihkan dan bangun path publik untuk URL
                            if (!empty($nama_file_db)) { 
                                // Ambil hanya nama filenya saja jika path di DB mengandung folder
                                $nama_file_murni = basename($nama_file_db);

                                // Sesuaikan dengan folder tempat file PDF Anda benar-benar berada di hosting
                                // Jika berada di folder 'uploads', gunakan baris ini:
                                $path_lampiran = '' . $nama_file_murni; 
                            } else { 
                                $path_lampiran = '#'; 
                            } 
                    ?>

                    <div class="d-flex align-items-center p-2 border rounded mb-2 bg-white hover-shadow">
                        <!-- Area Klik Kiri: Buka Preview -->
                        <div class="flex-grow-1 d-flex align-items-center overflow-hidden" 
                             onclick="bukaPreviewPDF('<?= jsAttr($path_lampiran); ?>')" 
                             style="cursor: pointer;">
                            <i class="fa-solid fa-file-pdf text-danger fs-3 me-3"></i>
                            <div class="text-truncate">
                                <span class="d-block fw-bold text-primary">Lihat Surat Utama</span>
                                <small class="text-muted text-truncate"><?= htmlspecialchars($lamp['nama_file'] ?? 'Lampiran'); ?></small>
                            </div>
                        </div>
                        <!-- Area Klik Kanan: Tombol Aksi Buka/Download -->
                        <div class="ms-2 border-start ps-2">
                            <button type="button" 
                                    onclick="bukaPDF('<?= jsAttr($path_lampiran); ?>')" 
                                    class="btn btn-outline-primary">
                                <i class="fa-solid fa-cloud-arrow-down fs-5"></i>
                            </button>
                        </div>
                    </div>
                <?php 
                    endforeach; 

                else: 
                ?>
                    <p class="text-muted small fst-italic text-center">Tidak ada file lampiran tambahan.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

    <?php if($_SESSION['nama_role'] == 'Admin_TU' && $data['status_workflow'] == 'Baru'): ?>
    
    <div class="modal fade" id="modalDisposisi<?= $data['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <form action="../disposisi/aksi_disposisi.php" method="POST" class="js-loading-form">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-share-nodes text-success me-2"></i> Teruskan Surat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="surat_id" value="<?= htmlspecialchars($data['id']); ?>">
                        <div class="alert alert-secondary py-2 small mb-3">
                            <i class="fa-solid fa-file-lines me-1"></i> <strong><?= htmlspecialchars($data['nomor_surat']); ?></strong><br>
                            Pengirim: <?= htmlspecialchars($data['pengirim']); ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Teruskan Kepada</label>
                            <div class="border rounded p-3 bg-white" style="max-height: 310px; overflow-y: auto;" data-disposisi-list="<?= $data['id']; ?>">
                                <div class="disposisi-search-box">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                                        <input type="text" class="form-control disposisi-search-input" data-target-list="<?= $data['id']; ?>" placeholder="Cari nama atau role pegawai...">
                                    </div>
                                    <small class="text-muted d-block mt-1">Pilih satu atau beberapa pegawai tujuan disposisi.</small>
                                </div>
                                <div class="form-check border-bottom pb-2 mb-2 disposisi-check-all-row">
                                    <input class="form-check-input" type="checkbox" id="checkAll_sm_<?= $data['id']; ?>" onchange="toggleVisibleDisposisiCheckboxes(this, '<?= $data['id']; ?>')">
                                    <label class="form-check-label fw-bold text-primary" for="checkAll_sm_<?= $data['id']; ?>">
                                        <i class="fa-solid fa-users me-1"></i> Pilih Semua yang Tampil
                                    </label>
                                </div>
                                <div class="disposisi-empty-result" data-empty-list="<?= $data['id']; ?>">
                                    <i class="fa-regular fa-face-frown me-1"></i> Pegawai tidak ditemukan.
                                </div>
                                <?php foreach($daftar_pegawai as $u): 
                                    $search_text = strtolower(($u['nama_lengkap'] ?? '') . ' ' . ($u['nama_role'] ?? ''));
                                ?>
                                <div class="form-check mb-1 disposisi-user-item" data-user-item="<?= $data['id']; ?>" data-search-text="<?= htmlspecialchars($search_text); ?>">
                                    <input class="form-check-input user_cb_sm_<?= $data['id']; ?>" type="checkbox" name="ke_user_id[]" value="<?= $u['id']; ?>" id="user_<?= $u['id']; ?>_sm_<?= $data['id']; ?>">
                                    <label class="form-check-label" for="user_<?= $u['id']; ?>_sm_<?= $data['id']; ?>">
                                        <span class="fw-semibold"><?= htmlspecialchars($u['nama_lengkap']); ?></span>
                                        <span class="text-muted small">— <?= htmlspecialchars($u['nama_role']); ?></span>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Instruksi / Pesan Pengantar</label>
                            <textarea class="form-control" name="instruksi" rows="3" required placeholder="Contoh: Mohon arahan..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Batas Waktu Penyelesaian (SLA)</label>
                            <input type="datetime-local" class="form-control" name="batas_waktu_sla">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="kirim_disposisi" class="btn btn-success fw-bold text-white"><i class="fa-solid fa-paper-plane me-1"></i> Kirim Disposisi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>          
    
    <div class="modal fade" id="modalHapus<?= $data['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <form action="aksi_surat_masuk.php" method="POST" class="js-loading-form">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger">Hapus Data Surat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($data['id']); ?>">
                        <p>Yakin ingin menghapus surat nomor <strong><?= htmlspecialchars($data['nomor_surat']); ?></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus" class="btn btn-danger">Ya, Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <?php endif; ?>
<?php endforeach; ?>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="aksi_surat_masuk.php" method="POST" enctype="multipart/form-data" class="js-loading-form">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-file-circle-plus me-2"></i> Registrasi Surat Masuk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="row bg-white p-3 rounded shadow-sm mb-3">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Informasi Surat</h6>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-primary" name="nomor_surat" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Pengirim <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-primary" name="pengirim" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Surat <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_surat" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold small">Tanggal Terima <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_terima" value="<?= date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold small">Perihal <span class="text-danger">*</span></label>
                            <textarea class="form-control border-primary" name="perihal" rows="2" required></textarea>
                        </div>
                    </div>

                    <div class="row bg-white p-3 rounded shadow-sm mb-3">
                        <h6 class="fw-bold text-warning border-bottom pb-2 mb-3">Klasifikasi & Tujuan</h6>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Sifat Surat <span class="text-danger">*</span></label>
                            <select class="form-select border-warning" name="klasifikasi" required>
                                <option value="Biasa">Biasa</option>
                                <option value="Penting">Penting</option>
                                <option value="Rahasia">Rahasia</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold small">Unit Tujuan <span class="text-danger">*</span></label>
                            <select class="form-select border-warning" name="unit_tujuan_id" required>
                                <option value="">-- Pilih Unit --</option>
                                <?php
                                $q_unit = mysqli_query($koneksi, "SELECT * FROM unit_kerja");
                                while ($u = mysqli_fetch_array($q_unit)) {
                                    echo "<option value='{$u['id']}'>{$u['nama_unit']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="surat_utama_base64" id="suratUtamaBase64">
                    <input type="hidden" name="lampiran_base64" id="lampiranBase64">
                    <input type="hidden" name="surat_utama_mode" id="suratUtamaMode" value="file">
                    <input type="hidden" name="lampiran_mode" id="lampiranMode" value="file">

                    <div class="row bg-white p-3 rounded shadow-sm">
                        <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-cloud-arrow-up me-2"></i>Unggah Dokumen
                        </h6>

                        <!-- ======================== -->
                        <!-- SURAT UTAMA              -->
                        <!-- ======================== -->
                        <div class="col-12 mb-4">
                            <label class="form-label fw-bold small">
                                File Surat Utama <span class="text-danger">*</span>
                                <span class="badge bg-success ms-1">PDF</span>
                            </label>

                            <!-- Tombol Pilih Sumber -->
                            <div class="d-flex gap-2 mb-2" id="sumberSuratUtama">
                                <button type="button" class="btn btn-outline-primary flex-fill"
                                    onclick="bukaPilihan('utama', 'file')">
                                    <i class="fa-solid fa-folder-open me-1"></i> Pilih File
                                </button>
                                <button type="button" class="btn btn-outline-success flex-fill"
                                    onclick="bukaPilihan('utama', 'kamera')">
                                    <i class="fa-solid fa-camera me-1"></i> Gunakan Kamera
                                </button>
                            </div>

                            <!-- Input file biasa (tersembunyi, dipanggil oleh tombol) -->
                            <input type="file" id="inputFileSuratUtama" name="file_path"
                                accept=".pdf,.jpg,.jpeg,.png" class="d-none"
                                onchange="handleFileInput(this, 'utama')">
                            <div class="sm-upload-feedback" data-upload-feedback="utama"></div>

                            <!-- Area preview Surat Utama -->
                            <div id="previewSuratUtama" class="d-none">
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-success">
                                            <i class="fa-solid fa-check-circle me-1"></i>
                                            <span id="labelSuratUtama">Dokumen siap</span>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="resetUpload('utama')">
                                            <i class="fa-solid fa-xmark"></i> Ganti
                                        </button>
                                    </div>
                                    <!-- Preview gambar-gambar (untuk mode kamera) -->
                                    <div id="thumbsSuratUtama" class="d-flex flex-wrap gap-2 mt-2"></div>
                                </div>
                            </div>

                            <!-- Area Kamera Surat Utama -->
                            <div id="areKameraSuratUtama" class="d-none">
                                <div class="border rounded-3 overflow-hidden bg-dark position-relative mb-2" style="max-height: 340px;">
                                    <video id="videoSuratUtama" autoplay playsinline muted
                                        class="w-100 d-block" style="max-height: 340px; object-fit: cover;"></video>
                                    <div class="position-absolute bottom-0 start-0 end-0 p-2 d-flex justify-content-between align-items-center"
                                        style="background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);">
                                        <span class="text-white small">
                                            <i class="fa-solid fa-images me-1"></i>
                                            <span id="counterSuratUtama">0</span> foto diambil
                                        </span>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-light btn-sm"
                                                onclick="ambilFoto('utama')">
                                                <i class="fa-solid fa-camera"></i> Foto
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm"
                                                onclick="selesaiFoto('utama')"
                                                id="btnSelesaiSuratUtama" disabled>
                                                <i class="fa-solid fa-check"></i> Selesai
                                            </button>
                                            <button type="button" class="btn btn-outline-light btn-sm"
                                                onclick="tutupKamera('utama')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Thumbnail foto yang sudah diambil -->
                                <div id="thumbKameraSuratUtama" class="d-flex flex-wrap gap-2 mb-2"></div>
                                <canvas id="canvasSuratUtama" class="d-none"></canvas>
                            </div>

                            <small class="text-muted">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Pilih file PDF/Gambar, atau scan langsung dari kamera.
                                Beberapa foto akan digabung menjadi 1 PDF otomatis.
                            </small>
                        </div>

                        <!-- ======================== -->
                        <!-- LAMPIRAN PENDUKUNG       -->
                        <!-- ======================== -->
                        <div class="col-12 mb-1">
                            <label class="form-label fw-bold small">
                                Lampiran Pendukung
                                <span class="badge bg-secondary ms-1">Opsional</span>
                            </label>

                            <!-- Tombol Pilih Sumber -->
                            <div class="d-flex gap-2 mb-2" id="sumberLampiran">
                                <button type="button" class="btn btn-outline-primary flex-fill"
                                    onclick="bukaPilihan('lampiran', 'file')">
                                    <i class="fa-solid fa-folder-open me-1"></i> Pilih File
                                </button>
                                <button type="button" class="btn btn-outline-success flex-fill"
                                    onclick="bukaPilihan('lampiran', 'kamera')">
                                    <i class="fa-solid fa-camera me-1"></i> Gunakan Kamera
                                </button>
                            </div>

                            <!-- Input file biasa (tersembunyi) -->
                            <input type="file" id="inputFileLampiran" name="lampiran[]"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                                multiple class="d-none"
                                onchange="handleFileInput(this, 'lampiran')">
                            <div class="sm-upload-feedback" data-upload-feedback="lampiran"></div>

                            <!-- Area preview Lampiran -->
                            <div id="previewLampiran" class="d-none">
                                <div class="border rounded-3 p-3 bg-light">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold small text-success">
                                            <i class="fa-solid fa-check-circle me-1"></i>
                                            <span id="labelLampiran">Lampiran siap</span>
                                        </span>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            onclick="resetUpload('lampiran')">
                                            <i class="fa-solid fa-xmark"></i> Ganti
                                        </button>
                                    </div>
                                    <div id="thumbsLampiran" class="d-flex flex-wrap gap-2 mt-2"></div>
                                </div>
                            </div>

                            <!-- Area Kamera Lampiran -->
                            <div id="areKameraLampiran" class="d-none">
                                <div class="border rounded-3 overflow-hidden bg-dark position-relative mb-2" style="max-height: 340px;">
                                    <video id="videoLampiran" autoplay playsinline
                                        class="w-100 d-block" style="max-height: 340px; object-fit: cover;"></video>
                                    <div class="position-absolute bottom-0 start-0 end-0 p-2 d-flex justify-content-between align-items-center"
                                        style="background: rgba(0,0,0,0.55); backdrop-filter: blur(4px);">
                                        <span class="text-white small">
                                            <i class="fa-solid fa-images me-1"></i>
                                            <span id="counterLampiran">0</span> foto diambil
                                        </span>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-light btn-sm"
                                                onclick="ambilFoto('lampiran')">
                                                <i class="fa-solid fa-camera"></i> Foto
                                            </button>
                                            <button type="button" class="btn btn-success btn-sm"
                                                onclick="selesaiFoto('lampiran')"
                                                id="btnSelesaiLampiran" disabled>
                                                <i class="fa-solid fa-check"></i> Selesai
                                            </button>
                                            <button type="button" class="btn btn-outline-light btn-sm"
                                                onclick="tutupKamera('lampiran')">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="thumbKameraLampiran" class="d-flex flex-wrap gap-2 mb-2"></div>
                                <canvas id="canvasLampiran" class="d-none"></canvas>
                            </div>

                            <small class="text-muted">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Bisa pilih banyak file sekaligus. Foto dari kamera akan digabung jadi 1 PDF.
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary fw-bold"><i class="fa-solid fa-save me-1"></i> Simpan Registrasi</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>

<?php
// Cari tahu apakah ada surat yang status OCR-nya masih menggantung ('processing')
$q_pending_ocr = mysqli_query($koneksi, "SELECT id FROM surat_masuk WHERE status_ocr = 'processing'");
$pending_ids = [];
while($row = mysqli_fetch_assoc($q_pending_ocr)){
    $pending_ids[] = $row['id'];
}
?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Ambil daftar ID dari PHP ke dalam JavaScript
        const pendingOcrIds = <?= json_encode($pending_ids ?? []); ?>;
        
        if (pendingOcrIds && pendingOcrIds.length > 0) {
            console.log("Ditemukan " + pendingOcrIds.length + " dokumen antrean OCR. Memulai proses...");
            
            pendingOcrIds.forEach(id => {
                fetch('worker_ocr.php?id=' + id)
                    .then(response => response.text())
                    .then(data => {
                        console.log('Status Surat ID ' + id + ': ' + data);
                    })
                    .catch(error => console.error('Error memproses OCR:', error));
            });
        }

        // 2. Validasi Ukuran File Surat Utama (Max 1.5MB)
        const fileUtamaInput = document.getElementById('file_path');
        if (fileUtamaInput) {
            fileUtamaInput.addEventListener('change', function() {
                const max_size = 1.5 * 1024 * 1024; 
                if (this.files && this.files[0] && this.files[0].size > max_size) {
                    alert('Maaf, ukuran file Surat Utama tidak boleh lebih dari 1.5 MB!');
                    this.value = ''; // Reset form inputan
                }
            });
        }

        // 3. Validasi Ukuran File Lampiran (Max 1.5MB per file)
        const lampiranInputs = document.querySelectorAll('input[name="lampiran[]"]');
        if (lampiranInputs.length > 0) {
            lampiranInputs.forEach(input => {
                input.addEventListener('change', function() {
                    const max_size = 1.5 * 1024 * 1024;
                    if (this.files && this.files[0] && this.files[0].size > max_size) {
                        alert('Maaf, ukuran salah satu file lampiran melebihi 1.5 MB!');
                        this.value = ''; 
                    }
                });
            });
        }
    });
</script>


<script>
// ============================================================
// STATE: Menyimpan foto yang diambil per sesi (utama/lampiran)
// ============================================================
const state = {
    utama:    { foto: [], stream: null },
    lampiran: { foto: [], stream: null }
};

// Mapping nama elemen per target
function el(target, suffix) {
    const map = {
        utama: {
            area:      'areKameraSuratUtama',
            video:     'videoSuratUtama',
            canvas:    'canvasSuratUtama',
            counter:   'counterSuratUtama',
            thumbKam:  'thumbKameraSuratUtama',
            btnSelesai:'btnSelesaiSuratUtama',
            preview:   'previewSuratUtama',
            thumbsPrev:'thumbsSuratUtama',
            label:     'labelSuratUtama',
            inputFile: 'inputFileSuratUtama',
            sumber:    'sumberSuratUtama',
            hidden:    'suratUtamaBase64',
            modeField: 'suratUtamaMode',
        },
        lampiran: {
            area:      'areKameraLampiran',
            video:     'videoLampiran',
            canvas:    'canvasLampiran',
            counter:   'counterLampiran',
            thumbKam:  'thumbKameraLampiran',
            btnSelesai:'btnSelesaiLampiran',
            preview:   'previewLampiran',
            thumbsPrev:'thumbsLampiran',
            label:     'labelLampiran',
            inputFile: 'inputFileLampiran',
            sumber:    'sumberLampiran',
            hidden:    'lampiranBase64',
            modeField: 'lampiranMode',
        }
    };
    return document.getElementById(map[target][suffix]);
}

// ============================================================
// 1. BUKA PILIHAN (file / kamera)
// ============================================================
function bukaPilihan(target, mode) {
    if (mode === 'file') {
        el(target, 'inputFile').click();
    } else {
        bukaKamera(target);
    }
}

// ============================================================
// 2. HANDLE FILE INPUT BIASA
// ============================================================
function handleFileInput(input, target) {
    if (!input.files || input.files.length === 0) return;

    const files = input.files;
    const thumbsEl = el(target, 'thumbsPrev');
    const labelEl  = el(target, 'label');
    thumbsEl.innerHTML = '';

    if (files.length === 1) {
        labelEl.textContent = files[0].name;
        // Tampilkan thumbnail jika gambar
        if (files[0].type.startsWith('image/')) {
            buatThumbFile(files[0], thumbsEl);
        } else {
            buatThumbDokumen(files[0].name, thumbsEl);
        }
    } else {
        labelEl.textContent = files.length + ' file dipilih';
        Array.from(files).forEach(f => {
            if (f.type.startsWith('image/')) {
                buatThumbFile(f, thumbsEl);
            } else {
                buatThumbDokumen(f.name, thumbsEl);
            }
        });
    }

    // Set mode = file (bukan base64)
    el(target, 'modeField').value = 'file';
    el(target, 'sumber').classList.add('d-none');
    el(target, 'preview').classList.remove('d-none');
    setUploadFeedback(target, 'ok', target === 'utama' ? 'Dokumen utama siap diunggah.' : 'Lampiran siap diunggah.');
}


function buatThumbFile(file, container) {
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.createElement('img');
        img.src = e.target.result;
        img.className = 'rounded border';
        img.style.cssText = 'width:60px;height:60px;object-fit:cover;';
        container.appendChild(img);
    };
    reader.readAsDataURL(file);
}

function buatThumbDokumen(nama, container) {
    const div = document.createElement('div');
    div.className = 'border rounded d-flex align-items-center justify-content-center bg-white';
    div.style.cssText = 'width:60px;height:60px;font-size:0.6rem;text-align:center;padding:4px;word-break:break-all;';
    const ext = nama.split('.').pop().toUpperCase();
    div.innerHTML = `<div><i class="fa-solid fa-file text-secondary d-block mb-1" style="font-size:1.2rem;"></i>${ext}</div>`;
    container.appendChild(div);
}

// ============================================================
// 3. BUKA KAMERA
// ============================================================
async function bukaKamera(target) {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } },
            audio: false
        });
        state[target].stream = stream;
        state[target].foto   = [];

        const video = el(target, 'video');
        video.srcObject = stream;

        el(target, 'sumber').classList.add('d-none');
        el(target, 'area').classList.remove('d-none');
        el(target, 'thumbKam').innerHTML = '';
        el(target, 'counter').textContent = '0';
        el(target, 'btnSelesai').disabled = true;

    } catch (err) {
        alert('Tidak dapat mengakses kamera. Pastikan izin kamera diaktifkan di browser.\n\nError: ' + err.message);
    }
}

// ============================================================
// 4. AMBIL FOTO (bisa berkali-kali)
// ============================================================
function ambilFoto(target) {
    const video  = el(target, 'video');
    const canvas = el(target, 'canvas');

    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
    state[target].foto.push(dataUrl);

    // Update counter & enable tombol selesai
    const jumlah = state[target].foto.length;
    el(target, 'counter').textContent = jumlah;
    el(target, 'btnSelesai').disabled = false;

    // Tampilkan thumbnail + tombol hapus
    const thumbsEl = el(target, 'thumbKam');
    const idx = jumlah - 1;

    const wrap = document.createElement('div');
    wrap.className = 'position-relative';
    wrap.id = `thumb_${target}_${idx}`;

    const img = document.createElement('img');
    img.src = dataUrl;
    img.className = 'rounded border';
    img.style.cssText = 'width:64px;height:64px;object-fit:cover;';

    // Badge nomor halaman
    const badge = document.createElement('span');
    badge.className = 'position-absolute top-0 start-0 badge bg-dark rounded-circle';
    badge.style.cssText = 'font-size:0.65rem;margin:2px;';
    badge.textContent = jumlah;

    // Tombol hapus per foto
    const btnHapus = document.createElement('button');
    btnHapus.type = 'button';
    btnHapus.className = 'position-absolute top-0 end-0 btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center';
    btnHapus.style.cssText = 'width:18px;height:18px;font-size:0.6rem;margin:2px;';
    btnHapus.innerHTML = '×';
    btnHapus.onclick = () => hapusFoto(target, idx);

    wrap.appendChild(img);
    wrap.appendChild(badge);
    wrap.appendChild(btnHapus);
    thumbsEl.appendChild(wrap);

    // Animasi flash kecil sebagai feedback
    canvas.classList.remove('d-none');
    setTimeout(() => canvas.classList.add('d-none'), 80);
}

// ============================================================
// 5. HAPUS SATU FOTO
// ============================================================
function hapusFoto(target, idx) {
    state[target].foto.splice(idx, 1);
    renderUlangThumbs(target);
    const jumlah = state[target].foto.length;
    el(target, 'counter').textContent = jumlah;
    el(target, 'btnSelesai').disabled = jumlah === 0;
}

function renderUlangThumbs(target) {
    const thumbsEl = el(target, 'thumbKam');
    thumbsEl.innerHTML = '';
    state[target].foto.forEach((dataUrl, idx) => {
        const wrap  = document.createElement('div');
        wrap.className = 'position-relative';

        const img   = document.createElement('img');
        img.src     = dataUrl;
        img.className = 'rounded border';
        img.style.cssText = 'width:64px;height:64px;object-fit:cover;';

        const badge = document.createElement('span');
        badge.className = 'position-absolute top-0 start-0 badge bg-dark rounded-circle';
        badge.style.cssText = 'font-size:0.65rem;margin:2px;';
        badge.textContent = idx + 1;

        const btnHapus = document.createElement('button');
        btnHapus.type = 'button';
        btnHapus.className = 'position-absolute top-0 end-0 btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center';
        btnHapus.style.cssText = 'width:18px;height:18px;font-size:0.6rem;margin:2px;';
        btnHapus.innerHTML = '×';
        btnHapus.onclick = () => hapusFoto(target, idx);

        wrap.appendChild(img);
        wrap.appendChild(badge);
        wrap.appendChild(btnHapus);
        thumbsEl.appendChild(wrap);
    });
}

// ============================================================
// 6. SELESAI FOTO → GABUNG JADI PDF
// ============================================================
async function selesaiFoto(target) {
    const fotos = state[target].foto;
    if (fotos.length === 0) return;

    // Hentikan kamera
    tutupKamera(target);

    // Tampilkan loading di tombol
    const btnSelesai = el(target, 'btnSelesai');
    btnSelesai.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
    btnSelesai.disabled = true;

    try {
        // Buat PDF menggunakan jsPDF
        const { jsPDF } = window.jspdf;

        // Tentukan orientasi dari foto pertama
        const img0     = await loadImage(fotos[0]);
        const isLandscape = img0.width > img0.height;
        const pdf      = new jsPDF({
            orientation: isLandscape ? 'landscape' : 'portrait',
            unit: 'mm',
            format: 'a4'
        });

        const pageW = pdf.internal.pageSize.getWidth();
        const pageH = pdf.internal.pageSize.getHeight();

        for (let i = 0; i < fotos.length; i++) {
            if (i > 0) pdf.addPage();

            const img  = await loadImage(fotos[i]);
            // Hitung dimensi agar proporsional mengisi halaman
            const ratio = Math.min(pageW / img.width, pageH / img.height);
            const w     = img.width  * ratio;
            const h     = img.height * ratio;
            const x     = (pageW - w) / 2;
            const y     = (pageH - h) / 2;

            pdf.addImage(fotos[i], 'JPEG', x, y, w, h);
        }

        // Dapatkan base64 PDF (tanpa prefix data:)
        const pdfBase64 = pdf.output('datauristring').split(',')[1];

        // Simpan ke hidden input
        el(target, 'hidden').value    = pdfBase64;
        el(target, 'modeField').value = 'kamera';

        // Tampilkan preview
        const thumbsEl = el(target, 'thumbsPrev');
        thumbsEl.innerHTML = '';
        fotos.forEach((dataUrl, idx) => {
            const img = document.createElement('img');
            img.src   = dataUrl;
            img.className = 'rounded border';
            img.style.cssText = 'width:60px;height:60px;object-fit:cover;';
            const wrap = document.createElement('div');
            wrap.className = 'position-relative';

            const badge = document.createElement('span');
            badge.className = 'position-absolute top-0 start-0 badge bg-success rounded-circle';
            badge.style.cssText = 'font-size:0.6rem;margin:2px;';
            badge.textContent = idx + 1;

            wrap.appendChild(img);
            wrap.appendChild(badge);
            thumbsEl.appendChild(wrap);
        });

        el(target, 'label').textContent = fotos.length + ' foto → 1 PDF (siap unggah)';
        el(target, 'area').classList.add('d-none');
        el(target, 'preview').classList.remove('d-none');

    } catch (err) {
        alert('Gagal membuat PDF: ' + err.message);
        resetUpload(target);
    }
}

function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload  = () => resolve(img);
        img.onerror = reject;
        img.src     = src;
    });
}

// ============================================================
// 7. TUTUP KAMERA
// ============================================================
function tutupKamera(target) {
    if (state[target].stream) {
        state[target].stream.getTracks().forEach(t => t.stop());
        state[target].stream = null;
    }
    el(target, 'area').classList.add('d-none');
    if (state[target].foto.length === 0) {
        el(target, 'sumber').classList.remove('d-none');
    }
}

// ============================================================
// 8. RESET UPLOAD
// ============================================================
function resetUpload(target) {
    tutupKamera(target);
    state[target].foto = [];

    el(target, 'hidden').value    = '';
    el(target, 'modeField').value = 'file';
    el(target, 'preview').classList.add('d-none');
    el(target, 'area').classList.add('d-none');
    el(target, 'sumber').classList.remove('d-none');

    // Reset input file
    const inputEl = el(target, 'inputFile');
    inputEl.value = '';

    // Reset thumbs
    el(target, 'thumbsPrev').innerHTML = '';
    const thumbKam = document.getElementById(
        target === 'utama' ? 'thumbKameraSuratUtama' : 'thumbKameraLampiran'
    );
    if (thumbKam) thumbKam.innerHTML = '';
    el(target, 'counter').textContent = '0';
    clearUploadFeedback(target);
}


// ============================================================
// 9. VALIDASI FORM SEBELUM SUBMIT
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    const formTambah = document.querySelector('#modalTambah form');
    if (!formTambah) return;

    formTambah.addEventListener('submit', function (e) {
        const modeUtama     = document.getElementById('suratUtamaMode').value;
        const base64Utama   = document.getElementById('suratUtamaBase64').value;
        const inputFileUtama = document.getElementById('inputFileSuratUtama');

        // Validasi: surat utama wajib diisi
        if (modeUtama === 'kamera' && !base64Utama) {
            e.preventDefault();
            alert('Surat utama belum selesai diproses! Tekan tombol "Selesai" setelah mengambil foto.');
            return;
        }
        if (modeUtama === 'file' && (!inputFileUtama.files || inputFileUtama.files.length === 0)) {
            e.preventDefault();
            alert('File Surat Utama wajib diisi!');
            return;
        }

        // Tampilkan loading saat submit
       const btnSubmit = formTambah.querySelector('[name="tambah"]');
        if (btnSubmit) {
            // 1. BUAT INPUT TERSEMBUNYI (Hidden Input)
            // Ini berfungsi sebagai "pengganti" tombol submit yang akan didisable
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'tambah';
            hiddenInput.value = '1';
            formTambah.appendChild(hiddenInput);

            // 2. Ubah tampilan tombol & matikan
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
            btnSubmit.disabled = true; 
        }
    });

    // === VALIDASI UKURAN FILE (ganti listener lama) ===
    document.getElementById('inputFileSuratUtama').addEventListener('change', function () {
        const max = 1.5 * 1024 * 1024;
        if (this.files[0] && this.files[0].size > max) {
            setUploadFeedback('utama', 'error', 'Ukuran file Surat Utama tidak boleh lebih dari 1,5 MB.');
            this.value = '';
        }
    });

    document.getElementById('inputFileLampiran').addEventListener('change', function () {
        const max = 1.5 * 1024 * 1024;
        Array.from(this.files).forEach(f => {
            if (f.size > max) {
                setUploadFeedback('lampiran', 'error', `File "${f.name}" melebihi 1,5 MB.`);
                this.value = '';
            }
        });
    });
});

// ============================================================
// 10. FUNGSI LAMA (tetap dipertahankan)
// ============================================================
function toggleCheckboxes(source, className) {
    const checkboxes = document.querySelectorAll('.' + className);
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function toggleVisibleDisposisiCheckboxes(source, listId) {
    const list = document.querySelector('[data-disposisi-list="' + listId + '"]');
    if (!list) return;
    list.querySelectorAll('[data-user-item="' + listId + '"]').forEach(function(item){
        if (item.style.display !== 'none') {
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (checkbox) checkbox.checked = source.checked;
        }
    });
}

document.addEventListener('input', function(e){
    if (!e.target.classList.contains('disposisi-search-input')) return;
    const listId = e.target.dataset.targetList;
    const keyword = e.target.value.trim().toLowerCase();
    const items = document.querySelectorAll('[data-user-item="' + listId + '"]');
    let visibleCount = 0;
    items.forEach(function(item){
        const haystack = (item.dataset.searchText || '').toLowerCase();
        const isVisible = haystack.includes(keyword);
        item.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });
    const empty = document.querySelector('[data-empty-list="' + listId + '"]');
    if (empty) empty.style.display = visibleCount === 0 ? 'block' : 'none';
    const checkAll = document.getElementById('checkAll_sm_' + listId);
    if (checkAll) checkAll.checked = false;
});

// ============================================================
// 11. OCR TRIGGER (sama persis dengan kode lama)
// ============================================================
document.addEventListener("DOMContentLoaded", function () {
    const pendingOcrIds = <?= json_encode($pending_ids); ?>;
    if (pendingOcrIds.length > 0) {
        pendingOcrIds.forEach(id => {
            fetch('worker_ocr.php?id=' + id)
                .then(r => r.text())
                .then(d => console.log('OCR ID ' + id + ': ' + d))
                .catch(err => console.error('OCR error:', err));
        });
    }
});
</script>

<script>
// ============================================================
// UI Enhancement Surat Masuk: skeleton, loading state, feedback file
// ============================================================
(function(){
    function ready(){ document.body.classList.add('sm-ready'); }
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(ready, 160);
    } else {
        document.addEventListener('DOMContentLoaded', function(){ setTimeout(ready, 160); });
    }
    window.addEventListener('load', ready);
    setTimeout(ready, 2500); // fallback agar skeleton tidak pernah menggantung jika library eksternal lambat
})();

function showSmProcessing(message) {
    const overlay = document.getElementById('smProcessingOverlay');
    if (!overlay) return;
    const text = overlay.querySelector('.sm-processing-box span:last-child');
    if (text && message) text.textContent = message;
    overlay.classList.add('show');
}

function setUploadFeedback(target, type, message) {
    const box = document.querySelector('[data-upload-feedback="' + target + '"]');
    if (!box) return;
    box.className = 'sm-upload-feedback ' + (type === 'ok' ? 'is-ok' : 'is-error');
    box.innerHTML = (type === 'ok' ? '<i class="fa-solid fa-check-circle me-1"></i>' : '<i class="fa-solid fa-triangle-exclamation me-1"></i>') + message;
}

function clearUploadFeedback(target) {
    const box = document.querySelector('[data-upload-feedback="' + target + '"]');
    if (!box) return;
    box.className = 'sm-upload-feedback';
    box.textContent = '';
}

document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.js-loading-form').forEach(function(form){
        form.addEventListener('submit', function(){
            const submitter = document.activeElement && document.activeElement.matches('button, input[type="submit"]') ? document.activeElement : form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitter && submitter.name && !form.querySelector('input[type="hidden"][name="' + submitter.name + '"]')) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = submitter.name;
                hidden.value = submitter.value || '1';
                form.appendChild(hidden);
            }
            if (submitter && submitter.tagName === 'BUTTON') {
                submitter.dataset.originalHtml = submitter.innerHTML;
                submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
                submitter.disabled = true;
            }
            showSmProcessing('Memproses permintaan...');
        });
    });
});
</script>

<?php include '../layouts/footer.php'; ?>
