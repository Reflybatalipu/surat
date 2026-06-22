<?php
session_start();
include 'config/koneksi.php';
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: index.php");
    exit;
}

$role    = $_SESSION['nama_role'] ?? '';
$user_id = (int)($_SESSION['user_id'] ?? 0);

$stat = [];

function render_empty_state($icon, $title, $desc = '') {
    $safe_icon  = htmlspecialchars($icon);
    $safe_title = htmlspecialchars($title);
    $safe_desc  = htmlspecialchars($desc);

    echo "<div class='empty-state'>
            <div class='empty-illustration'>
                <i class='fa-solid {$safe_icon}'></i>
            </div>
            <div class='empty-title'>{$safe_title}</div>";

    if ($safe_desc !== '') {
        echo "<div class='empty-desc'>{$safe_desc}</div>";
    }

    echo "</div>";
}
// ════════════════════════════════════════════════════════════
//  STATISTIK KARTU — berbeda per role (POV-based)
// ════════════════════════════════════════════════════════════

// ── ADMIN TU ──
if ($role == 'Admin_TU') {
    // Query Admin
    $r1 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_masuk WHERE deleted_at IS NULL");
    $val_a = $r1 ? (int)mysqli_fetch_assoc($r1)['t'] : 0;

    $r2 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_masuk WHERE deleted_at IS NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $sub_a_num = $r2 ? (int)mysqli_fetch_assoc($r2)['t'] : 0;

    $r3 = mysqli_query($koneksi, "SELECT COUNT(sm.id) as t FROM surat_masuk sm LEFT JOIN disposisi d ON sm.id=d.surat_id WHERE sm.status_workflow='Baru' AND sm.deleted_at IS NULL AND d.id IS NULL");
    $val_b = $r3 ? (int)mysqli_fetch_assoc($r3)['t'] : 0;

    $r4 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE status_workflow='Review' AND deleted_at IS NULL");
    $val_c = $r4 ? (int)mysqli_fetch_assoc($r4)['t'] : 0;

    $r5 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE status_workflow IN ('Approved','Terkirim') AND deleted_at IS NULL");
    $val_d = $r5 ? (int)mysqli_fetch_assoc($r5)['t'] : 0;

    $stat = [
        'label_a' => 'Total Surat Masuk',    'icon_a' => 'fa-inbox',          'theme_a' => 'sc-sage',
        'val_a'   => $val_a,                 'sub_a'  => $sub_a_num . ' surat baru (7 hari)',
        'label_b' => 'Belum Disposisi',       'icon_b' => 'fa-share-nodes',    'theme_b' => 'sc-coral',
        'val_b'   => $val_b,                 'sub_b'  => 'Menunggu diteruskan',
        'label_c' => 'Surat Keluar (Review)', 'icon_c' => 'fa-file-signature', 'theme_c' => 'sc-amber',
        'val_c'   => $val_c,                 'sub_c'  => 'Menunggu persetujuan',
        'label_d' => 'Total Surat Keluar',    'icon_d' => 'fa-paper-plane',    'theme_d' => 'sc-sky',
        'val_d'   => $val_d,                 'sub_d'  => 'Approved & Terkirim',
    ];

} elseif ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') {
    // Query Kepsek
    $r1 = mysqli_query($koneksi, "SELECT COUNT(sm.id) as t FROM surat_masuk sm LEFT JOIN disposisi d ON sm.id=d.surat_id WHERE sm.status_workflow='Baru' AND sm.deleted_at IS NULL AND d.id IS NULL");
    $val_a = $r1 ? (int)mysqli_fetch_assoc($r1)['t'] : 0;

    $r2 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE status_workflow='Review' AND deleted_at IS NULL");
    $val_b = $r2 ? (int)mysqli_fetch_assoc($r2)['t'] : 0;

    $r3 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE status_workflow IN ('Approved','Terkirim') AND deleted_at IS NULL");
    $val_c = $r3 ? (int)mysqli_fetch_assoc($r3)['t'] : 0;

    $r4 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_masuk WHERE status_workflow='Diarsipkan' AND deleted_at IS NULL");
    $val_d = $r4 ? (int)mysqli_fetch_assoc($r4)['t'] : 0;

    $stat = [
        'label_a' => 'Perlu Disposisi',       'icon_a' => 'fa-inbox',          'theme_a' => 'sc-coral',
        'val_a'   => $val_a,                  'sub_a'  => 'Surat masuk, belum diteruskan',
        'label_b' => 'Menunggu Persetujuan',   'icon_b' => 'fa-file-signature', 'theme_b' => 'sc-amber',
        'val_b'   => $val_b,                  'sub_b'  => 'Surat keluar butuh acc Anda',
        'label_c' => 'Sudah Disetujui',        'icon_c' => 'fa-circle-check',   'theme_c' => 'sc-sage',
        'val_c'   => $val_c,                  'sub_c'  => 'Approved & Terkirim',
        'label_d' => 'Surat Diarsipkan',       'icon_d' => 'fa-box-archive',    'theme_d' => 'sc-sky',
        'val_d'   => $val_d,                  'sub_d'  => 'Total arsip digital',
    ];

} else {
    // Query GURU / STAFF (FALLBACK)
    $r1 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE status_workflow IN ('Draft','Review') AND draft_by='$user_id' AND deleted_at IS NULL");
    $val_a = $r1 ? (int)mysqli_fetch_assoc($r1)['t'] : 0;

    $r2 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE status_workflow='Approved' AND draft_by='$user_id' AND deleted_at IS NULL");
    $val_b = $r2 ? (int)mysqli_fetch_assoc($r2)['t'] : 0;

    $r3 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM disposisi WHERE ke_user_id='$user_id'");
    $val_c = $r3 ? (int)mysqli_fetch_assoc($r3)['t'] : 0;

    $r4 = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE draft_by='$user_id' AND deleted_at IS NULL");
    $val_d = $r4 ? (int)mysqli_fetch_assoc($r4)['t'] : 0;

    $stat = [
        'label_a' => 'Draft/Review Saya',      'icon_a' => 'fa-file-pen',     'theme_a' => 'sc-amber',
        'val_a'   => $val_a,                  'sub_a'  => 'Belum selesai diproses',
        'label_b' => 'Surat Disetujui',        'icon_b' => 'fa-circle-check', 'theme_b' => 'sc-sage',
        'val_b'   => $val_b,                  'sub_b'  => 'Sudah di-approve',
        'label_c' => 'Disposisi Masuk',        'icon_c' => 'fa-share-nodes',  'theme_c' => 'sc-sky',
        'val_c'   => $val_c,                  'sub_c'  => 'Tugas disposisi ke Anda',
        'label_d' => 'Total Surat Saya',       'icon_d' => 'fa-paper-plane',  'theme_d' => 'sc-coral',
        'val_d'   => $val_d,                  'sub_d'  => 'Semua surat buatan Anda',
    ];
}

// ════════════════════════════════════════════════════════════
//  GRAFIK 6 BULAN — role-based scope
// ════════════════════════════════════════════════════════════
$nama_bulan_indo = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
$label_bulan = []; $grafik_masuk = []; $grafik_keluar = [];

for ($i = 5; $i >= 0; $i--) {
    $bln  = date('m', strtotime("-$i months"));
    $thn  = date('Y', strtotime("-$i months"));
    $thn2 = date('y', strtotime("-$i months"));
    $label_bulan[] = $nama_bulan_indo[(int)$bln - 1] . " '" . $thn2;

    if ($role == 'Admin_TU' || $role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') {
        $r = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_masuk  WHERE MONTH(created_at)='$bln' AND YEAR(created_at)='$thn' AND deleted_at IS NULL");
        $grafik_masuk[]  = $r ? (int)mysqli_fetch_assoc($r)['t'] : 0;
        $r = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE MONTH(created_at)='$bln' AND YEAR(created_at)='$thn' AND deleted_at IS NULL");
        $grafik_keluar[] = $r ? (int)mysqli_fetch_assoc($r)['t'] : 0;
    } else {
        $r = mysqli_query($koneksi, "SELECT COUNT(d.id) as t FROM disposisi d JOIN surat_masuk sm ON d.surat_id = sm.id WHERE d.ke_user_id='$user_id' AND MONTH(d.acted_at)='$bln' AND YEAR(d.acted_at)='$thn' AND sm.deleted_at IS NULL");
		$grafik_masuk[] = $r ? (int)mysqli_fetch_assoc($r)['t'] : 0;
        
        $r = mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE draft_by='$user_id' AND MONTH(created_at)='$bln' AND YEAR(created_at)='$thn' AND deleted_at IS NULL");
        $grafik_keluar[] = $r ? (int)mysqli_fetch_assoc($r)['t'] : 0; 
    }
}

// Label grafik berdasarkan role
$grafik_label_masuk  = ($role == 'Admin_TU' || $role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') ? 'Surat Masuk' : 'Disposisi Masuk';
$grafik_label_keluar = ($role == 'Admin_TU' || $role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') ? 'Surat Keluar' : 'Surat Keluar Saya';
$grafik_total_data   = array_sum($grafik_masuk) + array_sum($grafik_keluar);

// ════════════════════════════════════════════════════════════
//  AKTIVITAS TERBARU — privasi per role
// ════════════════════════════════════════════════════════════
if ($role == 'Admin_TU') {
    $q_recent = mysqli_query($koneksi, "SELECT a.action, a.table_name, a.created_at, u.nama_lengkap 
                                         FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id 
                                         ORDER BY a.created_at DESC LIMIT 6");
} elseif ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') {
    $q_recent = mysqli_query($koneksi, "SELECT a.action, a.table_name, a.created_at, u.nama_lengkap 
                                         FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id
                                         WHERE a.table_name IN ('surat_masuk','surat_keluar','disposisi')
                                         ORDER BY a.created_at DESC LIMIT 6");
} else {
    $q_recent = mysqli_query($koneksi, "SELECT a.action, a.table_name, a.created_at, u.nama_lengkap 
                                         FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id
                                         WHERE a.user_id='$user_id'
                                         ORDER BY a.created_at DESC LIMIT 6");
}

// ════════════════════════════════════════════════════════════
//  3. TABEL BAWAH (SURAT MASUK/DISPOSISI)
// ════════════════════════════════════════════════════════════
if ($role == 'Admin_TU') {
    $q_sm_terbaru = mysqli_query($koneksi, "SELECT sm.nomor_surat, sm.pengirim, sm.perihal, sm.status_workflow, sm.created_at, uk.nama_unit 
                                             FROM surat_masuk sm LEFT JOIN unit_kerja uk ON sm.unit_tujuan_id=uk.id 
                                             WHERE sm.deleted_at IS NULL ORDER BY sm.id DESC LIMIT 5");
    $judul_sm     = '5 Surat Masuk Terbaru';
    $show_sm      = true;

} elseif ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') {
    $q_sm_terbaru = mysqli_query($koneksi, "SELECT sm.nomor_surat, sm.pengirim, sm.perihal, sm.status_workflow, sm.created_at, uk.nama_unit 
                                             FROM surat_masuk sm 
                                             LEFT JOIN unit_kerja uk ON sm.unit_tujuan_id=uk.id
                                             LEFT JOIN disposisi d ON sm.id=d.surat_id
                                             WHERE sm.deleted_at IS NULL AND sm.status_workflow='Baru' AND d.id IS NULL
                                             ORDER BY sm.id DESC LIMIT 5");
    $judul_sm     = 'Surat Masuk — Perlu Disposisi';
    $show_sm      = true;

} else {
    $q_sm_terbaru = mysqli_query($koneksi, "SELECT sm.nomor_surat, sm.pengirim, sm.perihal, sm.status_workflow, sm.created_at, uk.nama_unit
                                             FROM disposisi d
                                             JOIN surat_masuk sm ON d.surat_id = sm.id
                                             LEFT JOIN unit_kerja uk ON sm.unit_tujuan_id = uk.id
                                             WHERE d.ke_user_id = '$user_id' AND sm.deleted_at IS NULL
                                             ORDER BY d.id DESC LIMIT 5");
    $judul_sm = 'Disposisi Masuk ke Anda';
    $show_sm  = true;
}
// ── OCR Pending ──
$q_pending_ocr = mysqli_query($koneksi, "SELECT id FROM surat_masuk WHERE status_ocr='processing'");
$pending_ids = [];
while($row = mysqli_fetch_assoc($q_pending_ocr)) $pending_ids[] = $row['id'];

// ── Surat keluar menunggu persetujuan (untuk Kepsek) ──
$sk_urgent_count = 0;
$sk_urgent_oldest = null;
$q_sk_urgent = false;
if ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') {
    $q_sk_pending = mysqli_query($koneksi, "SELECT sk.nomor_surat, sk.perihal, sk.status_workflow, sk.created_at, u.nama_lengkap as pembuat
                                             FROM surat_keluar sk LEFT JOIN users u ON sk.draft_by=u.id
                                             WHERE sk.status_workflow='Review' AND sk.deleted_at IS NULL
                                             ORDER BY sk.id DESC LIMIT 5");

    $q_sk_urgent_count = mysqli_query($koneksi, "SELECT COUNT(id) as t, MIN(created_at) as oldest_review
                                                  FROM surat_keluar
                                                  WHERE status_workflow='Review'
                                                  AND deleted_at IS NULL
                                                  AND created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    if ($q_sk_urgent_count) {
        $urgent_row = mysqli_fetch_assoc($q_sk_urgent_count);
        $sk_urgent_count  = (int)($urgent_row['t'] ?? 0);
        $sk_urgent_oldest = $urgent_row['oldest_review'] ?? null;
    }

    $q_sk_urgent = mysqli_query($koneksi, "SELECT sk.nomor_surat, sk.perihal, sk.created_at, u.nama_lengkap as pembuat
                                           FROM surat_keluar sk LEFT JOIN users u ON sk.draft_by=u.id
                                           WHERE sk.status_workflow='Review'
                                           AND sk.deleted_at IS NULL
                                           AND sk.created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                           ORDER BY sk.created_at ASC LIMIT 3");
}

include 'layouts/header.php';
?>

<!-- Fonts & Chart -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --ink:       #0f1923;
    --ink-muted: #64748b;
    --sage:      #4a70a9;
    --sage-lt:   #eaf0f8;
    --sage-mid:  #5b83be;
    --amber:     #d97706;
    --amber-lt:  #fef3c7;
    --coral:     #e05252;
    --coral-lt:  #fef2f2;
    --sky:       #0ea5e9;
    --sky-lt:    #e0f2fe;
    --green:     #16a34a;
    --green-lt:  #dcfce7;
    --surface:   #ffffff;
    --bg:        #f2f5f9;
    --border:    #dde4ee;
    --radius:    14px;
    --shadow:    0 2px 16px rgba(15,25,35,.07);
    --shadow-lg: 0 8px 32px rgba(15,25,35,.12);
}

body { background: var(--bg); font-family: 'DM Sans', sans-serif; color: var(--ink); }

/* ── Hero ── */
.dash-hero {
    background: linear-gradient(135deg, var(--sage) 0%, #2d4f85 60%, #1a3260 100%);
    border-radius: 20px; padding: 28px 32px;
    position: relative; overflow: hidden; margin-bottom: 28px;
}
.dash-hero::before {
    content: ''; position: absolute; inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.dash-hero::after {
    content: 'SIMPERS'; position: absolute; right: -20px; top: 50%; transform: translateY(-50%);
    font-family: 'Playfair Display', serif;
    font-size: 9rem; color: rgba(255,255,255,.04);
    letter-spacing: -4px; line-height: 1; pointer-events: none;
}
.hero-greeting { font-family: 'Playfair Display', serif; font-size: 1.65rem; color: #fff; line-height: 1.2; margin: 0 0 4px; }
.hero-sub      { color: rgba(255,255,255,.65); font-size: .9rem; }
.hero-date     { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); border-radius: 10px; padding: 8px 16px; color: #fff; font-size: .85rem; backdrop-filter: blur(6px); white-space: nowrap; }
.hero-badge    { background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.2); color: #fff; border-radius: 20px; padding: 3px 12px; font-size: .75rem; font-weight: 600; letter-spacing: .5px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 10px; }
.pulse-dot     { width: 7px; height: 7px; background: #4ade80; border-radius: 50%; animation: pulse 2s infinite; }
@keyframes pulse { 0%,100%{transform:scale(1);opacity:1} 50%{transform:scale(1.4);opacity:.7} }

/* ── Kepala Sekolah POV Banner ── */
.kepsek-banner {
    background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
    border-radius: 12px; padding: 14px 20px; margin-bottom: 20px;
    display: flex; align-items: center; gap: 14px; color: #fff;
}
.kepsek-banner .kb-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.kepsek-banner p { margin: 0; font-size: .85rem; color: rgba(255,255,255,.75); }
.kepsek-banner strong { font-size: .95rem; display: block; margin-bottom: 1px; }

/* ── Stat Cards ── */
.stat-card {
    background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border);
    padding: 22px 24px; position: relative; overflow: hidden;
    transition: transform .25s, box-shadow .25s; box-shadow: var(--shadow);
}
.stat-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
.stat-card .accent-bar  { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: var(--radius) var(--radius) 0 0; }
.stat-card .stat-icon   { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; margin-bottom: 14px; }
.stat-card .stat-num    { font-family: 'Playfair Display', serif; font-size: 2.4rem; line-height: 1; margin-bottom: 4px; font-weight: 700; }
.stat-card .stat-label  { font-size: .8rem; color: var(--ink-muted); font-weight: 500; text-transform: uppercase; letter-spacing: .6px; }
.stat-card .stat-sub    { font-size: .78rem; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }

.sc-sage  .accent-bar { background: var(--sage); }
.sc-sage  .stat-icon  { background: var(--sage-lt); color: var(--sage); }
.sc-sage  .stat-num   { color: var(--sage); }
.sc-amber .accent-bar { background: var(--amber); }
.sc-amber .stat-icon  { background: var(--amber-lt); color: var(--amber); }
.sc-amber .stat-num   { color: var(--amber); }
.sc-coral .accent-bar { background: var(--coral); }
.sc-coral .stat-icon  { background: var(--coral-lt); color: var(--coral); }
.sc-coral .stat-num   { color: var(--coral); }
.sc-sky   .accent-bar { background: var(--sky); }
.sc-sky   .stat-icon  { background: var(--sky-lt); color: var(--sky); }
.sc-sky   .stat-num   { color: var(--sky); }
.sc-green .accent-bar { background: var(--green); }
.sc-green .stat-icon  { background: var(--green-lt); color: var(--green); }
.sc-green .stat-num   { color: var(--green); }

/* ── Panel ── */
.panel          { background: var(--surface); border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden; }
.panel-header   { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.panel-title    { font-weight: 700; font-size: .95rem; color: var(--ink); }
.panel-body     { padding: 20px; }

/* ── Aktivitas ── */
.act-item   { display: flex; align-items: center; gap: 12px; padding: 10px 20px; transition: background .15s; }
.act-item:hover { background: var(--bg); }
.act-dot    { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0; }
.act-action { font-size: .85rem; font-weight: 600; }
.act-meta   { font-size: .75rem; color: var(--ink-muted); margin-top: 1px; }

/* ── Surat Row ── */
.surat-row       { display: flex; align-items: center; gap: 16px; padding: 14px 20px; border-bottom: 1px solid var(--border); transition: background .15s; }
.surat-row:last-child { border-bottom: none; }
.surat-row:hover { background: var(--bg); }
.surat-num       { font-weight: 700; font-size: .85rem; }
.surat-perihal   { font-size: .88rem; font-weight: 600; }
.surat-meta      { font-size: .75rem; color: var(--ink-muted); }

/* ── Badge Status ── */
.badge-status    { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.bs-baru         { background: #dbeafe; color: #1d4ed8; }
.bs-disposisi    { background: var(--amber-lt); color: #92400e; }
.bs-selesai      { background: var(--green-lt); color: var(--green); }
.bs-arsip        { background: #f1f5f9; color: #475569; }
.bs-review       { background: var(--amber-lt); color: #92400e; }
.bs-approved     { background: var(--green-lt); color: var(--green); }

/* ── Privacy Notice ── */
.privacy-notice {
    background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px;
    padding: 10px 16px; font-size: .78rem; color: var(--ink-muted);
    display: flex; align-items: center; gap: 8px; margin-bottom: 16px;
}

/* ── Urgency Indicator (Kepsek) ── */
.urgency-high   { border-left: 3px solid var(--coral); }
.urgency-medium { border-left: 3px solid var(--amber); }
.urgency-normal { border-left: 3px solid var(--border); }

/* ── Urgent Banner ── */
.urgent-banner {
    background: linear-gradient(135deg, #b91c1c 0%, #ef4444 100%);
    border-radius: 14px; padding: 16px 20px; margin-bottom: 20px;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 16px;
    color: #fff; box-shadow: 0 8px 26px rgba(185,28,28,.18);
}
.urgent-banner .ub-left { display: flex; gap: 14px; align-items: flex-start; }
.urgent-banner .ub-icon { width: 44px; height: 44px; border-radius: 12px; background: rgba(255,255,255,.18); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.urgent-banner .ub-title { font-weight: 800; margin-bottom: 2px; }
.urgent-banner .ub-desc { font-size: .84rem; color: rgba(255,255,255,.82); margin: 0; }
.urgent-banner .ub-list { margin: 8px 0 0; padding-left: 18px; font-size: .78rem; color: rgba(255,255,255,.84); }
.urgent-banner .ub-action { background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.28); color: #fff; border-radius: 10px; padding: 8px 14px; text-decoration: none; font-size: .78rem; font-weight: 700; white-space: nowrap; }
.urgent-banner .ub-action:hover { background: rgba(255,255,255,.25); color: #fff; }

/* ── Empty State ── */
.empty-state { padding: 34px 20px; text-align: center; color: var(--ink-muted); }
.empty-illustration { width: 72px; height: 72px; margin: 0 auto 12px; border-radius: 22px; background: linear-gradient(135deg, #f8fafc, #eaf0f8); display: flex; align-items: center; justify-content: center; color: var(--sage); font-size: 1.8rem; border: 1px solid var(--border); }
.empty-title { font-weight: 800; color: var(--ink); font-size: .95rem; margin-bottom: 4px; }
.empty-desc { font-size: .8rem; max-width: 420px; margin: 0 auto; }

/* ── Skeleton Loader per komponen ── */
.dashboard-page.dashboard-loading .real-content { opacity: 0; }
.dashboard-page:not(.dashboard-loading) .skeleton-shell { display: none; }
.skeleton-host { position: relative; }
.skeleton-shell { position: absolute; inset: 0; z-index: 5; background: var(--surface); border-radius: inherit; padding: 18px; pointer-events: none; }
.skeleton-line, .skeleton-block, .skeleton-circle { background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 37%, #eef2f7 63%); background-size: 400% 100%; animation: shimmer 1.15s ease infinite; }
.skeleton-line { height: 12px; border-radius: 999px; margin-bottom: 10px; }
.skeleton-block { height: 48px; border-radius: 12px; margin-bottom: 12px; }
.skeleton-circle { width: 42px; height: 42px; border-radius: 14px; margin-bottom: 14px; }
.skeleton-card .skeleton-line:nth-child(3) { width: 72%; }
.skeleton-card .skeleton-line:nth-child(4) { width: 48%; }
.skeleton-panel .skeleton-block { height: 170px; }
.skeleton-list-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.skeleton-list-row .skeleton-circle { width: 36px; height: 36px; margin: 0; }
.skeleton-list-row .skeleton-lines { flex: 1; }
@keyframes shimmer { 0% { background-position: 100% 0; } 100% { background-position: -100% 0; } }
@media (max-width: 575.98px) { .urgent-banner { flex-direction: column; } .urgent-banner .ub-action { width: 100%; text-align: center; } }

/* ── Fade-up Animations ── */
.fade-up { opacity: 0; transform: translateY(16px); animation: fadeUp .45s ease forwards; }
@keyframes fadeUp { to { opacity: 1; transform: none; } }
.delay-1 { animation-delay: .05s; } .delay-2 { animation-delay: .10s; }
.delay-3 { animation-delay: .15s; } .delay-4 { animation-delay: .20s; }
.delay-5 { animation-delay: .25s; } .delay-6 { animation-delay: .30s; }
</style>

<div id="dashboardPage" class="dashboard-page dashboard-loading">

<!-- ═══════════════════════════════════════════════════════════
     HERO HEADER
════════════════════════════════════════════════════════════ -->
<div class="dash-hero fade-up">
    <div class="d-flex align-items-start align-items-md-center justify-content-between flex-column flex-md-row gap-3 position-relative">
        <div>
            <?php
            $pov_label = match($role) {
                'Admin_TU'       => 'Admin TU',
                'Kepala_Sekolah', 'Kepala Sekolah' => 'Kepala Sekolah',
                default          => '' . htmlspecialchars($_SESSION['nama_role'])
            };
            ?>
            <div class="hero-badge"><div class="pulse-dot"></div> <?= $pov_label ?> Dashboard</div>
            <h1 class="hero-greeting">
                <?php
                $jam = (int)date('H');
                if($jam >= 5 && $jam < 12)      echo 'Selamat Pagi,';
                elseif($jam >= 12 && $jam < 15)  echo 'Selamat Siang,';
                elseif($jam >= 15 && $jam < 18)  echo 'Selamat Sore,';
                else                              echo 'Selamat Malam,';
                ?>
                <?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Pengguna') ?>
            </h1>
            <p class="hero-sub">
                <?php
                echo match($role) {
                    'Admin_TU'       => 'Pantau seluruh lalu lintas surat dan aktivitas sistem hari ini.',
                    'Kepala_Sekolah', 'Kepala Sekolah' => 'Berikut adalah agenda surat yang membutuhkan keputusan Anda.',
                    default          => 'Berikut adalah ringkasan aktivitas persuratan Anda hari ini.',
                };
                ?>
            </p>
        </div>
        <div class="hero-date text-center">
            <div style="font-size:.75rem;color:rgba(255,255,255,.6);margin-bottom:2px;"><?= date('l, d F Y') ?></div>
            <div style="font-size:1.4rem;font-weight:700;letter-spacing:2px;" id="liveTime">--:--:--</div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     KEPALA SEKOLAH — Urgent Review > 24 Jam
════════════════════════════════════════════════════════════ -->
<?php if (($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') && $sk_urgent_count > 0): ?>
<div class="urgent-banner fade-up delay-1">
    <div class="ub-left">
        <div class="ub-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <div class="ub-title"><?= $sk_urgent_count ?> surat keluar Review melewati 24 jam</div>
            <p class="ub-desc">
                Ada surat keluar yang sudah terlalu lama menunggu persetujuan. Mohon prioritaskan pemeriksaan agar alur surat tidak tertahan.
                <?php if (!empty($sk_urgent_oldest)): ?>
                    Review tertua sejak <?= date('d M Y H:i', strtotime($sk_urgent_oldest)) ?>.
                <?php endif; ?>
            </p>
            <?php if ($q_sk_urgent && mysqli_num_rows($q_sk_urgent) > 0): ?>
            <ul class="ub-list">
                <?php while($urgent = mysqli_fetch_assoc($q_sk_urgent)): ?>
                    <li><?= htmlspecialchars($urgent['nomor_surat']) ?> — <?= htmlspecialchars($urgent['perihal']) ?></li>
                <?php endwhile; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
    <a class="ub-action" href="modul_surat_keluar/surat_keluar.php">
        Review Sekarang <i class="fa-solid fa-arrow-right ms-1"></i>
    </a>
</div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════
     GURU/STAFF — Privacy Notice
════════════════════════════════════════════════════════════ -->
<?php if ($role !== 'Admin_TU' && $role !== 'Kepala_Sekolah' && $role !== 'Kepala Sekolah'): ?>
<div class="privacy-notice fade-up delay-1 mb-4">
    <i class="fa-solid fa-lock text-slate-400"></i>
    Data yang ditampilkan hanya mencakup aktivitas persuratan Anda sendiri.
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     KARTU STATISTIK (4 kartu, konten berbeda per role)
════════════════════════════════════════════════════════════ -->
<div class="row g-3 mb-4 fade-up delay-2">
    <?php
    $cards = [
        ['a', $stat['theme_a'], $stat['icon_a'], $stat['val_a'], $stat['label_a'], $stat['sub_a']],
        ['b', $stat['theme_b'], $stat['icon_b'], $stat['val_b'], $stat['label_b'], $stat['sub_b']],
        ['c', $stat['theme_c'], $stat['icon_c'], $stat['val_c'], $stat['label_c'], $stat['sub_c']],
        ['d', $stat['theme_d'], $stat['icon_d'], $stat['val_d'], $stat['label_d'], $stat['sub_d']],
    ];
    foreach($cards as [$key, $theme, $icon, $val, $label, $sub]):
    ?>
    <div class="col-6 col-lg-3">
        <div class="stat-card <?= $theme ?> skeleton-host">
            <div class="skeleton-shell skeleton-card">
                <div class="skeleton-circle"></div>
                <div class="skeleton-line" style="width:42%;height:28px;"></div>
                <div class="skeleton-line"></div>
                <div class="skeleton-line"></div>
            </div>
            <div class="real-content">
            <div class="accent-bar"></div>
            <div class="stat-icon"><i class="fa-solid <?= $icon ?>"></i></div>
            <div class="stat-num"><?= number_format($val) ?></div>
            <div class="stat-label"><?= $label ?></div>
            <div class="stat-sub text-muted"><?= $sub ?></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════
     BARIS KONTEN: Grafik + Aktivitas
════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- GRAFIK -->
    <div class="col-12 col-lg-7">
        <div class="panel fade-up delay-3 h-100 skeleton-host">
            <div class="panel-header">
                <span class="panel-title">
                    <i class="fa-solid fa-chart-column me-2" style="color:var(--sage);"></i>
                    <?php
                    echo match($role) {
                        'Admin_TU'       => 'Volume Surat — 6 Bulan Terakhir',
                        'Kepala_Sekolah', 'Kepala Sekolah' => 'Tren Surat Institusi — 6 Bulan',
                        default          => 'Aktivitas Surat Saya — 6 Bulan',
                    };
                    ?>
                </span>
                <span class="badge" style="background:var(--sage-lt);color:var(--sage);font-size:.72rem;border-radius:8px;padding:4px 10px;font-weight:600;">
                    <?= date('M Y') ?>
                </span>
            </div>
            <div class="skeleton-shell skeleton-panel">
                <div class="skeleton-line" style="width:45%;"></div>
                <div class="skeleton-block"></div>
                <div class="skeleton-line" style="width:70%;"></div>
            </div>
            <div class="panel-body real-content">
                <?php if ($role !== 'Admin_TU' && $role !== 'Kepala_Sekolah' && $role !== 'Kepala Sekolah'): ?>
                <div class="privacy-notice mb-3">
                    <i class="fa-solid fa-user-shield"></i>
                    Grafik menampilkan disposisi masuk dan surat keluar milik Anda saja.
                </div>
                <?php endif; ?>
                <?php if ($grafik_total_data > 0): ?>
                <canvas id="chartSurat" height="200"></canvas>
                <?php else: ?>
                <?php render_empty_state('chart-column', 'Belum ada data grafik', 'Belum ada aktivitas surat atau disposisi pada rentang 6 bulan terakhir.'); ?>
                <canvas id="chartSurat" height="200" style="display:none;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- AKTIVITAS TERBARU -->
    <div class="col-12 col-lg-5">
        <div class="panel fade-up delay-4 h-100 skeleton-host">
            <div class="panel-header">
                <span class="panel-title">
                    <i class="fa-solid fa-clock-rotate-left me-2" style="color:var(--amber);"></i>
                    <?php
                    echo match($role) {
                        'Admin_TU'       => 'Aktivitas Sistem Terbaru',
                        'Kepala_Sekolah', 'Kepala Sekolah' => 'Aktivitas Surat Relevan',
                        default          => 'Aktivitas Saya',
                    };
                    ?>
                </span>
                <?php if ($role !== 'Admin_TU' && $role !== 'Kepala_Sekolah' && $role !== 'Kepala Sekolah'): ?>
                <span style="font-size:.72rem;color:var(--ink-muted);display:flex;align-items:center;gap:4px;">
                    <i class="fa-solid fa-lock" style="font-size:.65rem;"></i> Privat
                </span>
                <?php endif; ?>
            </div>

            <div class="skeleton-shell skeleton-list">
                <?php for($i=0;$i<5;$i++): ?>
                <div class="skeleton-list-row">
                    <div class="skeleton-circle"></div>
                    <div class="skeleton-lines">
                        <div class="skeleton-line" style="width:68%;"></div>
                        <div class="skeleton-line" style="width:42%;"></div>
                    </div>
                </div>
                <?php endfor; ?>
            </div>
            <div class="real-content">
            <?php
            if($q_recent && mysqli_num_rows($q_recent) > 0) {
                while($act = mysqli_fetch_array($q_recent)) {
                    $action_upper = strtoupper($act['action'] ?? '');

                    // Default
                    $ikon = 'fa-circle-dot'; $bg = '#f1f5f9'; $ic = '#94a3b8';

                    // Deteksi pola dengan strpos — urutan dari spesifik ke umum
                    if(strpos($action_upper,'LOGIN_FAILED')   !== false) { $ikon='fa-right-to-bracket'; $bg='#fef2f2'; $ic='#dc2626'; }
                    elseif(strpos($action_upper,'LOGIN')      !== false) { $ikon='fa-right-to-bracket'; $bg='#dcfce7'; $ic='#15803d'; }
                    elseif(strpos($action_upper,'LOGOUT')     !== false) { $ikon='fa-right-from-bracket';$bg='#f1f5f9'; $ic='#475569'; }
                    elseif(strpos($action_upper,'DISPOSISI')  !== false) { $ikon='fa-share-nodes';      $bg='var(--amber-lt)'; $ic='var(--amber)'; }
                    elseif(strpos($action_upper,'APPROVE')    !== false) { $ikon='fa-circle-check';     $bg='#dcfce7'; $ic='#15803d'; }
                    elseif(strpos($action_upper,'FINISH')     !== false) { $ikon='fa-circle-check';     $bg='#dcfce7'; $ic='#15803d'; }
                    elseif(strpos($action_upper,'TOLAK')      !== false) { $ikon='fa-circle-xmark';     $bg='#fef2f2'; $ic='#dc2626'; }
                    elseif(strpos($action_upper,'HAPUS')     !== false
                        || strpos($action_upper,'HAPUS')      !== false) { $ikon='fa-trash-can';        $bg='#fef2f2'; $ic='#dc2626'; }
                    elseif(strpos($action_upper,'BUAT')     !== false
                        || strpos($action_upper,'REGISTRASI') !== false
                        || strpos($action_upper,'BUAT')       !== false) { $ikon='fa-plus-circle';      $bg='#dbeafe'; $ic='#1d4ed8'; }
                    elseif(strpos($action_upper,'ARSIP')      !== false) { $ikon='fa-box-archive';      $bg='#f1f5f9'; $ic='#475569'; }
                    elseif(strpos($action_upper,'REVIEW')     !== false
                        || strpos($action_upper,'SUBMIT')     !== false) { $ikon='fa-paper-plane';      $bg='var(--amber-lt)'; $ic='var(--amber)'; }
                    elseif(strpos($action_upper,'PERBARUI')     !== false) { $ikon='fa-pen-to-square';    $bg='#fef3c7'; $ic='#92400e'; }

                    // Label: ganti underscore jadi spasi, tampilkan rapi
                    $label_act = str_replace('_', ' ', $act['action'] ?? 'AKTIVITAS');

                    echo "
                    <div class='act-item'>
                        <div class='act-dot' style='background:{$bg};color:{$ic};'>
                            <i class='fa-solid {$ikon}'></i>
                        </div>
                        <div style='flex:1;overflow:hidden;'>
                            <div class='act-action text-truncate'>{$label_act}</div>
                            <div class='act-meta'>" . htmlspecialchars($act['nama_lengkap'] ?: 'Sistem') . " &bull; " . date('d/m H:i', strtotime($act['created_at'])) . "</div>
                        </div>
                    </div>";
                }
            } else {
                render_empty_state('clock-rotate-left', 'Belum ada aktivitas', 'Aktivitas akan muncul setelah ada perubahan data pada sistem.');
            }
            ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     SURAT MASUK TERBARU / PERLU KEPUTUSAN
════════════════════════════════════════════════════════════ -->
<?php if ($show_sm): ?>
<div class="panel fade-up delay-5 mb-4 skeleton-host">
    <div class="panel-header">
        <span class="panel-title">
            <?php if ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah'): ?>
                <i class="fa-solid fa-inbox me-2" style="color:var(--coral);"></i>
            <?php elseif ($role == 'Admin_TU'): ?>
                <i class="fa-solid fa-envelope-open-text me-2" style="color:var(--sage);"></i>
            <?php else: ?>
                <i class="fa-solid fa-share-nodes me-2" style="color:var(--amber);"></i>
            <?php endif; ?>
            <?= $judul_sm ?>
        </span>
        <a href="modul_surat_masuk/surat_masuk.php"
           style="background:var(--sage-lt);color:var(--sage);font-weight:600;font-size:.78rem;border-radius:8px;padding:6px 14px;text-decoration:none;">
            Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="skeleton-shell skeleton-list">
        <?php for($i=0;$i<4;$i++): ?>
        <div class="skeleton-list-row">
            <div class="skeleton-lines" style="flex:0 0 155px;">
                <div class="skeleton-line" style="width:90%;"></div>
                <div class="skeleton-line" style="width:55%;"></div>
            </div>
            <div class="skeleton-lines">
                <div class="skeleton-line" style="width:82%;"></div>
                <div class="skeleton-line" style="width:48%;"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="real-content">
    <?php
    $warna_map = [
        'Baru'       => 'bs-baru',
        'Disposisi'  => 'bs-disposisi',
        'Selesai'    => 'bs-selesai',
        'Diarsipkan' => 'bs-arsip',
        'Review'     => 'bs-review',
        'Approved'   => 'bs-approved',
    ];

    if($q_sm_terbaru && mysqli_num_rows($q_sm_terbaru) > 0):
        while($sm = mysqli_fetch_assoc($q_sm_terbaru)):
            $warna   = $warna_map[$sm['status_workflow']] ?? 'bs-arsip';
            $urgency = ($sm['status_workflow'] == 'Baru') ? 'urgency-high' : (($sm['status_workflow'] == 'Disposisi') ? 'urgency-medium' : 'urgency-normal');
    ?>
    <!-- Desktop -->
    <div class="surat-row d-none d-md-flex <?= $urgency ?>">
        <div style="flex:0 0 155px;">
            <div class="surat-num"><?= htmlspecialchars($sm['nomor_surat']) ?></div>
            <div class="surat-meta"><i class="fa-regular fa-calendar me-1"></i><?= date('d M Y', strtotime($sm['created_at'])) ?></div>
        </div>
        <div style="flex:1;overflow:hidden;">
            <div class="surat-perihal text-truncate"><?= htmlspecialchars($sm['perihal']) ?></div>
            <div class="surat-meta text-truncate">
                <i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($sm['pengirim']) ?>
            </div>
        </div>
        <div style="flex:0 0 130px;text-align:right;">
            <div class="surat-meta mb-1"><?= htmlspecialchars($sm['nama_unit'] ?? '-') ?></div>
            <span class="badge-status <?= $warna ?>"><?= $sm['status_workflow'] ?></span>
        </div>
    </div>

    <!-- Mobile -->
    <div class="d-flex d-md-none p-3 border-bottom <?= $urgency ?>" style="gap:10px;flex-direction:column;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="surat-num"><?= htmlspecialchars($sm['nomor_surat']) ?></span>
            <span class="badge-status <?= $warna ?>"><?= $sm['status_workflow'] ?></span>
        </div>
        <div class="surat-perihal"><?= htmlspecialchars($sm['perihal']) ?></div>
        <div class="surat-meta"><i class="fa-solid fa-building me-1"></i><?= htmlspecialchars($sm['pengirim']) ?> &bull; <?= date('d/m/Y', strtotime($sm['created_at'])) ?></div>
    </div>

    <?php endwhile; ?>
    <?php else: ?>
    <?php
        render_empty_state(
            ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') ? 'inbox' : 'folder-open',
            ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') ? 'Tidak ada surat yang membutuhkan disposisi' : 'Tidak ada data surat ditemukan',
            ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') ? 'Semua surat masuk sudah ditindaklanjuti atau belum ada surat baru.' : 'Data akan tampil ketika surat tersedia sesuai hak akses Anda.'
        );
        ?>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════
     KEPALA SEKOLAH — Surat Keluar Menunggu Persetujuan
════════════════════════════════════════════════════════════ -->
<?php if (($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') && isset($q_sk_pending)): ?>
<div class="panel fade-up delay-6 mb-4 skeleton-host">
    <div class="panel-header">
        <span class="panel-title">
            <i class="fa-solid fa-file-signature me-2" style="color:var(--amber);"></i>
            Surat Keluar — Menunggu Persetujuan Anda
        </span>
        <a href="modul_surat_keluar/surat_keluar.php"
           style="background:var(--amber-lt);color:var(--amber);font-weight:600;font-size:.78rem;border-radius:8px;padding:6px 14px;text-decoration:none;">
            Lihat Semua <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="skeleton-shell skeleton-list">
        <?php for($i=0;$i<4;$i++): ?>
        <div class="skeleton-list-row">
            <div class="skeleton-lines" style="flex:0 0 155px;">
                <div class="skeleton-line" style="width:80%;"></div>
                <div class="skeleton-line" style="width:52%;"></div>
            </div>
            <div class="skeleton-lines">
                <div class="skeleton-line" style="width:76%;"></div>
                <div class="skeleton-line" style="width:46%;"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
    <div class="real-content">
    <?php if($q_sk_pending && mysqli_num_rows($q_sk_pending) > 0):
        while($sk = mysqli_fetch_assoc($q_sk_pending)):
    ?>
    <div class="surat-row urgency-medium d-none d-md-flex">
        <div style="flex:0 0 155px;">
            <div class="surat-num"><?= htmlspecialchars($sk['nomor_surat']) ?></div>
            <div class="surat-meta"><?= date('d M Y', strtotime($sk['created_at'])) ?></div>
        </div>
        <div style="flex:1;overflow:hidden;">
            <div class="surat-perihal text-truncate"><?= htmlspecialchars($sk['perihal']) ?></div>
            <div class="surat-meta"><i class="fa-solid fa-user me-1"></i>Dibuat oleh: <?= htmlspecialchars($sk['pembuat'] ?? '-') ?></div>
        </div>
        <div style="flex:0 0 130px;text-align:right;">
            <span class="badge-status bs-review">Menunggu Acc</span>
        </div>
    </div>
    <div class="d-flex d-md-none p-3 border-bottom urgency-medium" style="flex-direction:column;gap:6px;">
        <div class="d-flex justify-content-between align-items-start">
            <span class="surat-num"><?= htmlspecialchars($sk['nomor_surat']) ?></span>
            <span class="badge-status bs-review">Menunggu Acc</span>
        </div>
        <div class="surat-perihal"><?= htmlspecialchars($sk['perihal']) ?></div>
        <div class="surat-meta">Dibuat oleh: <?= htmlspecialchars($sk['pembuat'] ?? '-') ?></div>
    </div>
    <?php endwhile; ?>
    <?php else: ?>
    <?php render_empty_state('circle-check', 'Tidak ada surat keluar yang menunggu persetujuan', 'Semua surat keluar sudah diproses atau belum ada pengajuan baru.'); ?>
    <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div>

<?php include 'layouts/footer.php'; ?>

<script>
// ── Jam Live ──
function updateTime() {
    const el = document.getElementById('liveTime');
    if (!el) return;
    const now = new Date();
    el.textContent = [now.getHours(), now.getMinutes(), now.getSeconds()]
        .map(n => String(n).padStart(2,'0')).join(':');
}
updateTime(); setInterval(updateTime, 1000);

// ── Skeleton Loader ──
document.addEventListener('DOMContentLoaded', function() {
    const dashboardPage = document.getElementById('dashboardPage');
    if (dashboardPage) dashboardPage.classList.remove('dashboard-loading');
});

// ── Chart ──
const labelBulan      = <?= json_encode($label_bulan) ?>;
const dataSuratMasuk  = <?= json_encode($grafik_masuk) ?>;
const dataSuratKeluar = <?= json_encode($grafik_keluar) ?>;
const labelMasuk      = <?= json_encode($grafik_label_masuk) ?>;
const labelKeluar     = <?= json_encode($grafik_label_keluar) ?>;

const chartEl = document.getElementById('chartSurat');
const ctx = chartEl ? chartEl.getContext('2d') : null;
if (ctx) {
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: labelBulan,
        datasets: [
            {
                label: labelMasuk,
                data: dataSuratMasuk,
                backgroundColor: 'rgba(14,165,233,.75)',
                borderRadius: 6, borderSkipped: false,
            },
            {
                label: labelKeluar,
                data: dataSuratKeluar,
                backgroundColor: 'rgba(74,112,169,.85)',
                borderRadius: 6, borderSkipped: false,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top', labels: { font: { family: 'DM Sans', size: 12 }, padding: 16, boxWidth: 12 } },
            tooltip: { backgroundColor: '#0f1923', titleFont: { family: 'DM Sans', size: 13 }, bodyFont: { family: 'DM Sans', size: 12 }, padding: 12, cornerRadius: 10 }
        },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)', borderDash: [4,4] }, ticks: { stepSize: 1, font: { family: 'DM Sans', size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { family: 'DM Sans', size: 11 } } }
        },
        animation: { duration: 900, easing: 'easeOutQuart' }
    }
});
}

// ── OCR Trigger ──
const pendingOcrIds = <?= json_encode($pending_ids) ?>;
if (pendingOcrIds.length > 0) {
    pendingOcrIds.forEach(id => {
        fetch('modul_surat_masuk/aksi_surat_masuk_folder/worker_ocr.php?id=' + id)
            .then(r => r.text())
            .then(d => console.log('OCR ID ' + id + ': ' + d))
            .catch(e => console.error(e));
    });
}
</script>