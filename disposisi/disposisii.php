<?php
session_start();
include '../config/koneksi.php';

// Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

$user_id_sekarang = $_SESSION['user_id'];
$role_sekarang    = $_SESSION['nama_role'];

// ════════════════════════════════════════════════════════════
// 1. AMBIL SEMUA DATA DISPOSISI
// ════════════════════════════════════════════════════════════
$sql_disposisi = "
    SELECT d.*, sm.nomor_surat, sm.pengirim, sm.perihal, sm.file_path,
           sm.tanggal_surat, sm.klasifikasi, sm.status_workflow AS status_surat,
           u_dari.nama_lengkap AS nama_pengirim, u_dari.nip
    FROM disposisi d
    JOIN surat_masuk sm ON d.surat_id = sm.id
    JOIN users u_dari ON d.dari_user_id = u_dari.id
    WHERE d.ke_user_id = '$user_id_sekarang' AND sm.deleted_at IS NULL
    ORDER BY d.id DESC
";
$query_disp    = mysqli_query($koneksi, $sql_disposisi);
$data_disposisi = [];
$surat_ids      = [];

while ($row = mysqli_fetch_assoc($query_disp)) {
    $data_disposisi[] = $row;
    if (!in_array($row['surat_id'], $surat_ids)) {
        $surat_ids[] = $row['surat_id'];
    }
}

// ════════════════════════════════════════════════════════════
// 2. LAMPIRAN MULTI-UPLOAD
// ════════════════════════════════════════════════════════════
$lampiran_surat = [];
if (!empty($surat_ids)) {
    $ids_str    = implode(',', $surat_ids);
    $q_lampiran = mysqli_query($koneksi, "SELECT * FROM lampiran_surat_masuk WHERE id_surat_masuk IN ($ids_str)");
    if ($q_lampiran) {
        while ($lamp = mysqli_fetch_assoc($q_lampiran)) {
            $lampiran_surat[$lamp['id_surat_masuk']][] = $lamp;
        }
    }
}

// ════════════════════════════════════════════════════════════
// 3. DAFTAR PEGAWAI UNTUK DISPOSISI BERJENJANG
// ════════════════════════════════════════════════════════════
$daftar_pegawai = [];
if ($role_sekarang != 'Guru') {
    $q_user = mysqli_query($koneksi, "SELECT u.id, u.nama_lengkap, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.is_active = 1 AND u.id != '$user_id_sekarang' ORDER BY r.id ASC");
    if ($q_user) {
        while ($u = mysqli_fetch_assoc($q_user)) {
            $daftar_pegawai[] = $u;
        }
    }
}

// ════════════════════════════════════════════════════════════
// 4. HISTORY PER SURAT (untuk modal detail)
// ════════════════════════════════════════════════════════════
$history_surat = [];
if (!empty($surat_ids)) {
    $ids_str = implode(',', $surat_ids);
    $q_hist  = mysqli_query($koneksi, "
        SELECT d.surat_id, d.instruksi, d.status, d.laporan_tindak_lanjut, d.batas_waktu_sla,
               u_dari.nama_lengkap AS dari, u_ke.nama_lengkap AS ke_user,
               r_ke.nama_role AS role_ke, d.id AS disp_id
        FROM disposisi d
        JOIN users u_dari ON d.dari_user_id = u_dari.id
        JOIN users u_ke   ON d.ke_user_id   = u_ke.id
        JOIN roles r_ke   ON u_ke.role_id   = r_ke.id
        WHERE d.surat_id IN ($ids_str)
        ORDER BY d.id ASC
    ");
    if ($q_hist) {
        while ($h = mysqli_fetch_assoc($q_hist)) {
            $history_surat[$h['surat_id']][] = $h;
        }
    }
}

// ════════════════════════════════════════════════════════════
// 5. SUMMARY STATS
// ════════════════════════════════════════════════════════════
$total_disp   = count($data_disposisi);
$menunggu     = 0; $dibaca = 0; $selesai = 0;
foreach ($data_disposisi as $d) {
    if ($d['status'] == 'Menunggu') $menunggu++;
    elseif ($d['status'] == 'Dibaca') $dibaca++;
    elseif ($d['status'] == 'Selesai') $selesai++;
}

include '../layouts/header.php';
?>

<!-- ═══ FONTS & CHART ═══ -->
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* { font-family: 'Plus Jakarta Sans', sans-serif; }
body { background: #f0f4f8; }

/* ── CSS Variables ── */
:root {
    --ink:      #0f1923;
    --ink-m:    #64748b;
    --blue:     #2563eb;
    --blue-lt:  #dbeafe;
    --amber:    #d97706;
    --amber-lt: #fef3c7;
    --green:    #16a34a;
    --green-lt: #dcfce7;
    --red:      #dc2626;
    --red-lt:   #fef2f2;
    --sky:      #0ea5e9;
    --sky-lt:   #e0f2fe;
    --border:   #e2e8f0;
    --radius:   14px;
    --shadow:   0 2px 12px rgba(15,25,35,.07);
    --shadow-lg:0 8px 32px rgba(15,25,35,.13);
}

/* ── Page Header Card ── */
.page-header {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 24px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--blue);
}
.page-header-left { display: flex; align-items: center; gap: 14px; }
.page-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: var(--blue-lt); color: var(--blue);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.page-title { font-size: 1rem; font-weight: 800; color: var(--ink); margin: 0; line-height: 1.2; }
.page-sub   { font-size: .78rem; color: var(--ink-m); margin: 2px 0 0; }

/* ── Stat Pills ── */
.stat-pills { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.stat-pill {
    display: flex; align-items: center; gap: 6px;
    background: #f8fafc; border: 1px solid var(--border);
    border-radius: 20px; padding: 5px 12px;
    font-size: .78rem; font-weight: 600; color: var(--ink-m);
}
.stat-pill .pill-num { font-size: .95rem; font-weight: 800; }
.stat-pill.pill-menunggu { background: var(--red-lt);   border-color: #fecaca; color: var(--red);   }
.stat-pill.pill-selesai  { background: var(--green-lt); border-color: #bbf7d0; color: var(--green); }
.stat-pill.pill-total    { background: var(--blue-lt);  border-color: #bfdbfe; color: var(--blue);  }

/* ── Bulk Action Bar ── */
.bulk-bar {
    background: #fff; border: 1px solid var(--blue); border-radius: 12px;
    padding: 10px 18px; margin-bottom: 16px;
    display: none; align-items: center; gap: 12px;
    box-shadow: 0 2px 12px rgba(37,99,235,.12);
    animation: slideDown .2s ease;
}
.bulk-bar.visible { display: flex; }
@keyframes slideDown { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }

/* ── Panel ── */
.panel { background: #fff; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden; border-top: 3px solid var(--blue); }
.panel-hdr { padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.panel-title { font-weight: 700; font-size: .9rem; color: var(--ink); }

/* ── Table ── */
.disp-table thead th {
    background: #f8fafc; font-size: .72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px; color: var(--ink-m);
    border-bottom: 2px solid var(--border); padding: 12px 14px;
    position: sticky; top: 0; z-index: 10;
    box-shadow: 0 2px 4px rgba(0,0,0,.04);
}
.disp-table tbody tr { transition: background .15s; }
.disp-table tbody tr:hover { background: #f8fafc; }
.disp-table tbody td { padding: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.table-wrap { max-height: 65vh; overflow-y: auto; overflow-x: auto; }

/* ── Badges ── */
.badge-s { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: .7rem; font-weight: 700; }
.bs-menunggu { background: var(--red-lt);   color: var(--red);   }
.bs-dibaca   { background: var(--sky-lt);   color: var(--sky);   }
.bs-selesai  { background: var(--green-lt); color: var(--green); }

/* ── Urgency left border ── */
.urgency-high   { border-left: 3px solid var(--red);   }
.urgency-medium { border-left: 3px solid var(--amber); }
.urgency-low    { border-left: 3px solid var(--border);}

/* ── Action buttons ── */
.btn-act { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 8px; border: 1px solid; transition: all .15s; font-size: .78rem; }
.btn-act:hover { transform: translateY(-1px); }
.btn-act-info   { background: var(--sky-lt);   color: var(--sky);   border-color: #bae6fd; }
.btn-act-success{ background: var(--green-lt); color: var(--green); border-color: #bbf7d0; }
.btn-act-amber  { background: var(--amber-lt); color: var(--amber); border-color: #fde68a; }
.btn-act-detail { background: var(--blue-lt);  color: var(--blue);  border-color: #bfdbfe; }

/* ── Instruksi bubble ── */
.instruksi-bubble {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border-left: 3px solid var(--amber);
    border-radius: 0 8px 8px 0;
    padding: 8px 12px; font-size: .83rem;
    color: #78350f; font-style: italic;
    line-height: 1.4;
}

/* ── SLA indicator ── */
.sla-ok      { color: var(--green); font-size: .78rem; font-weight: 600; }
.sla-warning { color: var(--amber); font-size: .78rem; font-weight: 600; }
.sla-danger  { color: var(--red);   font-size: .78rem; font-weight: 600; }
.sla-none    { color: var(--ink-m); font-size: .78rem; }

/* ── Mobile card ── */
.mob-card { background: #fff; border-radius: 14px; border: 1px solid var(--border); margin-bottom: 12px; overflow: hidden; box-shadow: var(--shadow); transition: box-shadow .2s; }
.mob-card:hover { box-shadow: var(--shadow-lg); }
.mob-card-hdr   { padding: 12px 16px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.mob-card-body  { padding: 12px 16px; }

/* ── Custom Modal ── */
.m-overlay { display: none; position: fixed; inset: 0; background: rgba(15,23,42,.5); backdrop-filter: blur(4px); z-index: 1055; align-items: center; justify-content: center; padding: 16px; }
.m-overlay.open { display: flex; }
.m-box { background: #fff; border-radius: 20px; width: 100%; max-width: 580px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: var(--shadow-lg); animation: popUp .22s cubic-bezier(.34,1.56,.64,1); }
.m-box-lg { max-width: 780px; }
@keyframes popUp { from { opacity:0; transform: scale(.94) translateY(12px); } to { opacity:1; transform: none; } }
.m-hdr { padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; flex-shrink: 0; }
.m-hdr-amber { background: linear-gradient(135deg, #d97706, #b45309); }
.m-hdr-blue  { background: linear-gradient(135deg, #2563eb, #1d4ed8); }
.m-hdr-green { background: linear-gradient(135deg, #16a34a, #15803d); }
.m-hdr-slate { background: linear-gradient(135deg, #475569, #334155); }
.m-title { font-weight: 800; font-size: 1rem; color: #fff; }
.m-subtitle { font-size: .75rem; color: rgba(255,255,255,.75); margin-top: 2px; }
.m-body { padding: 20px 22px; overflow-y: auto; flex: 1; }
.m-footer { padding: 14px 22px; background: #f8fafc; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-shrink: 0; }
.m-close-btn { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,.2); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; transition: background .15s; }
.m-close-btn:hover { background: rgba(255,255,255,.3); }

/* ── Form inputs ── */
.f-label { font-size: .78rem; font-weight: 700; color: var(--ink-m); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; display: block; }
.f-input { width: 100%; border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; font-size: .875rem; font-family: 'Plus Jakarta Sans', sans-serif; transition: border .15s, box-shadow .15s; }
.f-input:focus { outline: none; border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
.f-select { appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; padding-right: 36px; }

/* ── History Timeline ── */
.timeline { position: relative; padding-left: 28px; }
.timeline::before { content: ''; position: absolute; left: 10px; top: 8px; bottom: 8px; width: 2px; background: var(--border); border-radius: 2px; }
.tl-item { position: relative; margin-bottom: 20px; }
.tl-item:last-child { margin-bottom: 0; }
.tl-dot { position: absolute; left: -23px; top: 6px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 2px currentColor; }
.tl-dot-amber { color: var(--amber); background: var(--amber-lt); }
.tl-dot-green { color: var(--green); background: var(--green-lt); }
.tl-dot-blue  { color: var(--blue);  background: var(--blue-lt);  }
.tl-dot-red   { color: var(--red);   background: var(--red-lt);   }
.tl-dot-gray  { color: #94a3b8;      background: #f1f5f9;         }
.tl-card  { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 12px 14px; }
.tl-who   { font-weight: 700; font-size: .85rem; color: var(--ink); }
.tl-role  { font-size: .72rem; color: var(--ink-m); font-weight: 500; }
.tl-instr { font-size: .82rem; color: #78350f; background: var(--amber-lt); border-radius: 8px; padding: 6px 10px; margin-top: 8px; font-style: italic; border-left: 2px solid var(--amber); }
.tl-laporan { font-size: .82rem; color: #14532d; background: var(--green-lt); border-radius: 8px; padding: 6px 10px; margin-top: 8px; border-left: 2px solid var(--green); }

/* ── Surat info card ── */
.surat-info-card { background: linear-gradient(135deg, #f8fafc, #f1f5f9); border: 1px solid var(--border); border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; }
.si-label { font-size: .68rem; font-weight: 700; color: var(--ink-m); text-transform: uppercase; letter-spacing: .6px; }
.si-value { font-size: .875rem; font-weight: 600; color: var(--ink); margin-top: 2px; }

/* ── Klasifikasi ── */
.klas-biasa   { color: var(--green); } .klas-penting { color: var(--amber); } .klas-rahasia { color: var(--red); }

/* ── Checklist pegawai ── */
.pegawai-list { border: 1px solid var(--border); border-radius: 10px; max-height: 180px; overflow-y: auto; }
.pegawai-item { display: flex; align-items: center; gap: 10px; padding: 9px 14px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .12s; }
.pegawai-item:last-child { border-bottom: none; }
.pegawai-item:hover { background: #f8fafc; }
.pegawai-item label { cursor: pointer; flex: 1; font-size: .85rem; font-weight: 500; color: var(--ink); }
.pegawai-item .role-tag { font-size: .68rem; color: var(--ink-m); background: #f1f5f9; border-radius: 6px; padding: 2px 7px; }

/* ── Fade-up animation ── */
.fade-up { opacity: 0; transform: translateY(12px); animation: fuAnim .35s ease forwards; }
@keyframes fuAnim { to { opacity:1; transform:none; } }
.d1{animation-delay:.04s}.d2{animation-delay:.08s}.d3{animation-delay:.12s}
.d4{animation-delay:.16s}.d5{animation-delay:.20s}

/* ── PDF modal ── */
#modalPreviewPDF .m-box { max-width: 95vw; width: 95vw; height: 90vh; }
</style>

<!-- ════════════════════════════════════════
     PAGE HEADER (minimalis)
════════════════════════════════════════ -->
<div class="page-header">
    <div class="page-header-left">
        <div class="page-icon"><i class="fa-solid fa-inbox"></i></div>
        <div>
            <h1 class="page-title">Kotak Masuk Disposisi</h1>
            <p class="page-sub">
                <?php
                if ($role_sekarang == 'Kepala_Sekolah')
                    echo 'Tindak lanjuti, TTE, atau disposisikan berjenjang ke bawahan.';
                elseif ($role_sekarang == 'Guru')
                    echo 'Terima dan selesaikan disposisi yang diberikan kepada Anda.';
                else
                    echo 'Kelola disposisi yang diterima, teruskan jika diperlukan.';
                ?>
            </p>
        </div>
    </div>
    <div class="stat-pills">
        <div class="stat-pill pill-total">
            <span class="pill-num"><?php echo $total_disp; ?></span>
            <span>Total</span>
        </div>
        <?php if ($menunggu > 0): ?>
        <div class="stat-pill pill-menunggu">
            <i class="fa-solid fa-circle-dot" style="font-size:.65rem;"></i>
            <span class="pill-num"><?php echo $menunggu; ?></span>
            <span>Menunggu</span>
        </div>
        <?php endif; ?>
        <?php if ($dibaca > 0): ?>
        <div class="stat-pill" style="background:var(--sky-lt);border-color:#bae6fd;color:var(--sky);">
            <i class="fa-solid fa-eye" style="font-size:.65rem;"></i>
            <span class="pill-num"><?php echo $dibaca; ?></span>
            <span>Dibaca</span>
        </div>
        <?php endif; ?>
        <div class="stat-pill pill-selesai">
            <i class="fa-solid fa-circle-check" style="font-size:.65rem;"></i>
            <span class="pill-num"><?php echo $selesai; ?></span>
            <span>Selesai</span>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════
     BULK ACTION BAR
════════════════════════════════════════ -->
<div class="bulk-bar fade-up d1" id="bulkActionContainer">
    <div style="width:36px;height:36px;border-radius:10px;background:var(--blue-lt);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;">
        <i class="fa-solid fa-check-double"></i>
    </div>
    <span style="font-weight:700;color:var(--blue);font-size:.9rem;"><span id="selectedCount">0</span> surat terpilih</span>
    <select id="bulkActionSelect" style="border:1px solid var(--blue);border-radius:8px;padding:6px 12px;font-size:.85rem;background:#fff;color:var(--ink);outline:none;">
        <option value="selesai">✅ Selesaikan Semua</option>
    </select>
    <button type="button" style="background:var(--blue);color:#fff;border:none;border-radius:8px;padding:6px 16px;font-weight:700;font-size:.85rem;cursor:pointer;" onclick="executeBulkAction()">
        <i class="fa-solid fa-bolt me-1"></i> Terapkan
    </button>
    <button type="button" style="background:#f1f5f9;color:var(--ink-m);border:1px solid var(--border);border-radius:8px;padding:6px 12px;font-size:.85rem;cursor:pointer;" onclick="clearBulk()">
        Batalkan
    </button>
</div>

<form id="formBulkAction" action="aksi_disposisi.php" method="POST" style="display:none;">
    <input type="hidden" name="proses_bulk_action" value="1">
    <input type="hidden" name="bulk_jenis_tindakan" id="inputBulkJenis">
    <input type="hidden" name="bulk_disposisi_ids"  id="inputBulkIds">
</form>

<!-- ════════════════════════════════════════
     MAIN PANEL
════════════════════════════════════════ -->
<?php if (empty($data_disposisi)): ?>
<div class="panel fade-up d2">
    <div style="text-align:center;padding:60px 20px;">
        <div style="width:72px;height:72px;border-radius:20px;background:var(--blue-lt);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.8rem;color:var(--blue);">
            <i class="fa-solid fa-inbox"></i>
        </div>
        <p style="font-size:1rem;font-weight:700;color:var(--ink);margin:0 0 6px;">Kotak Masuk Kosong</p>
        <p style="font-size:.85rem;color:var(--ink-m);margin:0;">Belum ada surat disposisi yang masuk untuk Anda.</p>
    </div>
</div>

<?php else: ?>

<!-- ════ DESKTOP TABLE ════ -->
<div class="panel fade-up d2 d-none d-md-block">
    <div class="panel-hdr">
        <span class="panel-title"><i class="fa-solid fa-list-ul me-2" style="color:var(--blue);"></i>Daftar Disposisi</span>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.82rem;color:var(--ink-m);">
            <input type="checkbox" id="checkAllBulk" onclick="toggleAllBulk(this)" style="width:16px;height:16px;cursor:pointer;accent-color:var(--blue);">
            Pilih Semua
        </label>
    </div>
    <div class="table-wrap">
        <table class="disp-table w-100">
            <thead>
                <tr>
                    <th style="width:3%;text-align:center;"></th>
                    <th style="width:20%;">Surat</th>
                    <th style="width:13%;">Dari</th>
                    <th style="width:26%;">Instruksi</th>
                    <th style="width:13%;">Batas Waktu</th>
                    <th style="width:10%;">Status</th>
                    <th style="width:15%;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data_disposisi as $data):
                    // Status badge
                    $bs_class = match($data['status']) {
                        'Menunggu' => 'bs-menunggu',
                        'Dibaca'   => 'bs-dibaca',
                        'Selesai'  => 'bs-selesai',
                        default    => 'bs-menunggu'
                    };
                    $bs_icon = match($data['status']) {
                        'Menunggu' => 'fa-circle-dot',
                        'Dibaca'   => 'fa-eye',
                        'Selesai'  => 'fa-circle-check',
                        default    => 'fa-circle'
                    };
                    // SLA
                    $sla_html = '<span class="sla-none"><i class="fa-regular fa-clock me-1"></i>Tidak ada</span>';
                    if (!empty($data['batas_waktu_sla'])) {
                        $sla_ts   = strtotime($data['batas_waktu_sla']);
                        $now_ts   = time();
                        $diff_h   = ($sla_ts - $now_ts) / 3600;
                        if ($data['status'] == 'Selesai') {
                            $sla_html = '<span class="sla-ok"><i class="fa-solid fa-check me-1"></i>Selesai tepat waktu</span>';
                        } elseif ($diff_h < 0) {
                            $sla_html = '<span class="sla-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Melewati batas!</span><br><small class="text-muted">' . date('d/m/Y H:i', $sla_ts) . '</small>';
                        } elseif ($diff_h <= 24) {
                            $sla_html = '<span class="sla-warning"><i class="fa-regular fa-clock me-1"></i>' . date('d/m/Y H:i', $sla_ts) . '</span>';
                        } else {
                            $sla_html = '<span class="sla-ok"><i class="fa-regular fa-calendar me-1"></i>' . date('d/m/Y H:i', $sla_ts) . '</span>';
                        }
                    }
                    // Urgency
                    $urgency = ($data['status'] == 'Menunggu') ? 'urgency-high' : (($data['status'] == 'Dibaca') ? 'urgency-medium' : 'urgency-low');
                    // Klasifikasi
                    $klas_class = match($data['klasifikasi'] ?? '') {
                        'Rahasia' => 'klas-rahasia', 'Penting' => 'klas-penting', default => 'klas-biasa'
                    };
                ?>
                <tr class="<?php echo $urgency; ?>">
                    <td style="text-align:center;">
                        <?php if ($data['status'] != 'Selesai'): ?>
                        <input type="checkbox" class="bulk-item" value="<?php echo $data['id']; ?>" onclick="updateBulkUI()" style="width:16px;height:16px;cursor:pointer;accent-color:var(--blue);">
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:700;font-size:.85rem;color:var(--ink);font-family:monospace;"><?php echo htmlspecialchars($data['nomor_surat']); ?></div>
                        <div style="font-size:.75rem;color:var(--ink-m);margin-top:2px;"><i class="fa-solid fa-building me-1"></i><?php echo htmlspecialchars($data['pengirim']); ?></div>
                        <?php if (!empty($data['klasifikasi'])): ?>
                        <span style="font-size:.68rem;font-weight:700;" class="<?php echo $klas_class; ?>"><i class="fa-solid fa-tag me-1"></i><?php echo $data['klasifikasi']; ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:.85rem;color:var(--ink);"><?php echo htmlspecialchars($data['nama_pengirim']); ?></div>
                        <?php if (!empty($data['nip'])): ?>
                        <div style="font-size:.72rem;color:var(--ink-m);">NIP: <?php echo $data['nip']; ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="instruksi-bubble"><?php echo htmlspecialchars($data['instruksi']); ?></div>
                    </td>
                    <td><?php echo $sla_html; ?></td>
                    <td>
                        <span class="badge-s <?php echo $bs_class; ?>">
                            <i class="fa-solid <?php echo $bs_icon; ?>"></i>
                            <?php echo $data['status']; ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:5px;justify-content:center;flex-wrap:wrap;">
                            <!-- Detail & History -->
                            <button class="btn-act btn-act-detail" title="Lihat Detail & Riwayat" onclick="openModal('mDetail<?php echo $data['id']; ?>')">
                                <i class="fa-solid fa-timeline"></i>
                            </button>
                            <!-- File -->
                            <button class="btn-act btn-act-info" title="Lihat Dokumen" onclick="openModal('mFile<?php echo $data['id']; ?>')">
                                <i class="fa-solid fa-folder-open"></i>
                            </button>
                            <!-- Tindak Lanjut -->
                            <?php if ($data['status'] != 'Selesai'): ?>
                            <button class="btn-act btn-act-success" title="Tindak Lanjut" onclick="openModal('mTindak<?php echo $data['id']; ?>')">
                                <i class="fa-solid <?php echo ($role_sekarang == 'Guru') ? 'fa-check-double' : 'fa-check-to-slot'; ?>"></i>
                            </button>
                            <?php else: ?>
                            <!-- Lihat Laporan -->
                            <?php if (!empty($data['laporan_tindak_lanjut'])): ?>
                            <button class="btn-act btn-act-amber" title="Lihat Laporan" onclick="openModal('mLaporan<?php echo $data['id']; ?>')">
                                <i class="fa-solid fa-file-lines"></i>
                            </button>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ════ MOBILE CARDS ════ -->
<div class="d-block d-md-none fade-up d2">
    <!-- Mobile select all -->
    <div style="background:#fff;border:1px solid var(--border);border-radius:12px;padding:10px 16px;margin-bottom:12px;display:flex;align-items:center;gap:10px;">
        <input type="checkbox" id="checkAllBulkMobile" onclick="toggleAllBulk(this)" style="width:16px;height:16px;accent-color:var(--blue);">
        <label for="checkAllBulkMobile" style="font-size:.85rem;font-weight:600;color:var(--ink-m);cursor:pointer;">Pilih Semua Disposisi Aktif</label>
    </div>

    <?php foreach ($data_disposisi as $data):
        $bs_class = match($data['status']) { 'Menunggu' => 'bs-menunggu', 'Dibaca' => 'bs-dibaca', 'Selesai' => 'bs-selesai', default => 'bs-menunggu' };
        $urgency  = ($data['status'] == 'Menunggu') ? 'urgency-high' : (($data['status'] == 'Dibaca') ? 'urgency-medium' : 'urgency-low');
    ?>
    <div class="mob-card <?php echo $urgency; ?>">
        <div class="mob-card-hdr">
            <div style="display:flex;align-items:center;gap:10px;">
                <?php if ($data['status'] != 'Selesai'): ?>
                <input type="checkbox" class="bulk-item" value="<?php echo $data['id']; ?>" onclick="updateBulkUI()" style="width:16px;height:16px;accent-color:var(--blue);">
                <?php endif; ?>
                <span style="font-weight:700;font-size:.83rem;font-family:monospace;color:var(--blue);"><?php echo htmlspecialchars($data['nomor_surat']); ?></span>
            </div>
            <span class="badge-s <?php echo $bs_class; ?>"><?php echo $data['status']; ?></span>
        </div>
        <div class="mob-card-body">
            <div style="font-size:.78rem;color:var(--ink-m);margin-bottom:6px;"><i class="fa-solid fa-user me-1"></i>Dari: <strong style="color:var(--ink);"><?php echo htmlspecialchars($data['nama_pengirim']); ?></strong></div>
            <div class="instruksi-bubble mb-2"><?php echo htmlspecialchars($data['instruksi']); ?></div>
            <?php if (!empty($data['batas_waktu_sla'])): ?>
            <div style="font-size:.75rem;color:var(--red);margin-bottom:8px;"><i class="fa-regular fa-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($data['batas_waktu_sla'])); ?></div>
            <?php endif; ?>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px;">
                <button onclick="openModal('mDetail<?php echo $data['id']; ?>')" style="flex:1;background:var(--blue-lt);color:var(--blue);border:1px solid #bfdbfe;border-radius:8px;padding:7px;font-size:.78rem;font-weight:600;cursor:pointer;">
                    <i class="fa-solid fa-timeline me-1"></i> Detail
                </button>
                <button onclick="openModal('mFile<?php echo $data['id']; ?>')" style="flex:1;background:var(--sky-lt);color:var(--sky);border:1px solid #bae6fd;border-radius:8px;padding:7px;font-size:.78rem;font-weight:600;cursor:pointer;">
                    <i class="fa-solid fa-folder-open me-1"></i> File
                </button>
                <?php if ($data['status'] != 'Selesai'): ?>
                <button onclick="openModal('mTindak<?php echo $data['id']; ?>')" style="flex:1;background:var(--green-lt);color:var(--green);border:1px solid #bbf7d0;border-radius:8px;padding:7px;font-size:.78rem;font-weight:600;cursor:pointer;">
                    <i class="fa-solid fa-check-double me-1"></i> Tindak Lanjut
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php endif; /* end empty check */ ?>


<!-- ════════════════════════════════════════
     MODALS — Loop per disposisi
════════════════════════════════════════ -->
<?php foreach ($data_disposisi as $data):
    $id       = $data['id'];
    $surat_id = $data['surat_id'];
    $klas_class = match($data['klasifikasi'] ?? '') { 'Rahasia' => 'klas-rahasia', 'Penting' => 'klas-penting', default => 'klas-biasa' };
?>

<!-- ─── MODAL DETAIL & HISTORY ─── -->
<div class="m-overlay" id="mDetail<?php echo $id; ?>">
    <div class="m-box m-box-lg">
        <div class="m-hdr m-hdr-blue">
            <div>
                <div class="m-title"><i class="fa-solid fa-timeline me-2"></i>Detail & Riwayat Surat</div>
                <div class="m-subtitle"><?php echo htmlspecialchars($data['nomor_surat']); ?></div>
            </div>
            <button class="m-close-btn" onclick="closeModal('mDetail<?php echo $id; ?>')">&#x2715;</button>
        </div>
        <div class="m-body">
            <!-- Info Surat -->
            <div class="surat-info-card">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <div class="si-label">Nomor Surat</div>
                        <div class="si-value" style="font-family:monospace;"><?php echo htmlspecialchars($data['nomor_surat']); ?></div>
                    </div>
                    <div>
                        <div class="si-label">Pengirim Eksternal</div>
                        <div class="si-value"><?php echo htmlspecialchars($data['pengirim']); ?></div>
                    </div>
                    <div>
                        <div class="si-label">Perihal</div>
                        <div class="si-value"><?php echo htmlspecialchars($data['perihal']); ?></div>
                    </div>
                    <div>
                        <div class="si-label">Tanggal Surat</div>
                        <div class="si-value"><?php echo !empty($data['tanggal_surat']) ? date('d M Y', strtotime($data['tanggal_surat'])) : '-'; ?></div>
                    </div>
                    <?php if (!empty($data['klasifikasi'])): ?>
                    <div>
                        <div class="si-label">Klasifikasi</div>
                        <div class="si-value <?php echo $klas_class; ?>">
                            <i class="fa-solid fa-tag me-1"></i><?php echo $data['klasifikasi']; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div class="si-label">Status Surat</div>
                        <div class="si-value"><?php echo htmlspecialchars($data['status_surat'] ?? '-'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Instruksi disposisi ini -->
            <div style="margin-bottom:20px;">
                <div class="si-label mb-2">Instruksi yang Diterima</div>
                <div class="instruksi-bubble"><?php echo htmlspecialchars($data['instruksi']); ?></div>
            </div>

            <!-- Timeline History -->
            <div class="si-label mb-3">Riwayat Alur Disposisi</div>
            <div class="timeline">
                <?php
                $hist_items = isset($history_surat[$surat_id]) ? $history_surat[$surat_id] : [];
                if (empty($hist_items)) {
                    echo '<p style="color:var(--ink-m);font-size:.85rem;">Belum ada riwayat.</p>';
                } else {
                    foreach ($hist_items as $h):
                        $dot_class = match($h['status']) {
                            'Selesai'  => 'tl-dot-green',
                            'Dibaca'   => 'tl-dot-blue',
                            'Menunggu' => 'tl-dot-amber',
                            default    => 'tl-dot-gray'
                        };
                        $is_me = ($h['disp_id'] == $id);
                ?>
                <div class="tl-item">
                    <div class="tl-dot <?php echo $dot_class; ?>"></div>
                    <div class="tl-card" style="<?php echo $is_me ? 'border-color:var(--blue);box-shadow:0 0 0 2px rgba(37,99,235,.1);' : ''; ?>">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                            <div>
                                <span class="tl-who"><?php echo htmlspecialchars($h['dari']); ?></span>
                                <span style="color:var(--ink-m);font-size:.8rem;"> → </span>
                                <span class="tl-who"><?php echo htmlspecialchars($h['ke_user']); ?></span>
                                <span style="font-size:.7rem;color:var(--ink-m);background:#f1f5f9;border-radius:6px;padding:1px 6px;margin-left:4px;"><?php echo $h['role_ke']; ?></span>
                                <?php if ($is_me): ?>
                                <span style="font-size:.68rem;font-weight:700;color:var(--blue);background:var(--blue-lt);border-radius:6px;padding:1px 6px;margin-left:4px;">← Anda</span>
                                <?php endif; ?>
                            </div>
                            <span class="badge-s <?php echo match($h['status']) { 'Selesai' => 'bs-selesai', 'Dibaca' => 'bs-dibaca', default => 'bs-menunggu' }; ?>"><?php echo $h['status']; ?></span>
                        </div>
                        <?php if (!empty($h['instruksi'])): ?>
                        <div class="tl-instr"><i class="fa-solid fa-quote-left me-1" style="font-size:.7rem;"></i><?php echo htmlspecialchars($h['instruksi']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($h['laporan_tindak_lanjut'])): ?>
                        <div class="tl-laporan"><i class="fa-solid fa-file-lines me-1" style="font-size:.7rem;"></i><strong>Laporan:</strong> <?php echo htmlspecialchars($h['laporan_tindak_lanjut']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($h['batas_waktu_sla'])): ?>
                        <div style="font-size:.72rem;color:var(--ink-m);margin-top:6px;"><i class="fa-regular fa-clock me-1"></i>SLA: <?php echo date('d/m/Y H:i', strtotime($h['batas_waktu_sla'])); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; } ?>
            </div>
        </div>
        <div class="m-footer">
            <button onclick="closeModal('mDetail<?php echo $id; ?>')" style="background:#f1f5f9;color:var(--ink-m);border:1px solid var(--border);border-radius:10px;padding:9px 20px;font-size:.85rem;font-weight:600;cursor:pointer;">Tutup</button>
            <?php if ($data['status'] != 'Selesai'): ?>
            <button onclick="closeModal('mDetail<?php echo $id; ?>');openModal('mTindak<?php echo $id; ?>')" style="background:var(--green);color:#fff;border:none;border-radius:10px;padding:9px 20px;font-size:.85rem;font-weight:700;cursor:pointer;">
                <i class="fa-solid fa-check-to-slot me-1"></i> Tindak Lanjut
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ─── MODAL FILE & LAMPIRAN ─── -->
<div class="m-overlay" id="mFile<?php echo $id; ?>">
    <div class="m-box">
        <div class="m-hdr m-hdr-slate">
            <div>
                <div class="m-title"><i class="fa-solid fa-folder-open me-2"></i>Dokumen & Lampiran</div>
                <div class="m-subtitle"><?php echo htmlspecialchars($data['nomor_surat']); ?></div>
            </div>
            <button class="m-close-btn" onclick="closeModal('mFile<?php echo $id; ?>')">&#x2715;</button>
        </div>
        <div class="m-body">
            <!-- Surat Utama -->
            <div class="si-label mb-2">Surat Utama</div>
            <?php if (!empty($data['file_path'])): ?>
            <div style="display:flex;align-items:center;gap:12px;background:#f8fafc;border:1px solid var(--border);border-radius:12px;padding:12px 16px;margin-bottom:16px;cursor:pointer;" onclick="bukaPreviewPDF('<?php echo $data['file_path']; ?>')">
                <div style="width:44px;height:44px;background:var(--red-lt);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--red);flex-shrink:0;">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <div style="flex:1;overflow:hidden;">
                    <div style="font-weight:600;font-size:.875rem;color:var(--ink);">Lihat Surat Utama</div>
                    <div style="font-size:.72rem;color:var(--ink-m);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($data['file_path']); ?></div>
                </div>
                <div style="display:flex;gap:6px;">
                    <button onclick="event.stopPropagation();bukaPreviewPDF('<?php echo $data['file_path']; ?>')" style="background:var(--blue-lt);color:var(--blue);border:1px solid #bfdbfe;border-radius:8px;padding:6px 10px;font-size:.78rem;font-weight:600;cursor:pointer;">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                    <button onclick="event.stopPropagation();bukaPDF('<?php echo $data['file_path']; ?>')" style="background:var(--green-lt);color:var(--green);border:1px solid #bbf7d0;border-radius:8px;padding:6px 10px;font-size:.78rem;font-weight:600;cursor:pointer;">
                        <i class="fa-solid fa-download"></i>
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div style="background:var(--amber-lt);border:1px solid #fde68a;border-radius:10px;padding:10px 14px;font-size:.82rem;color:#92400e;margin-bottom:16px;">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> File surat utama tidak tersedia.
            </div>
            <?php endif; ?>

            <!-- Lampiran -->
            <div class="si-label mb-2">Lampiran Pendukung</div>
            <?php
            $id_sm = $surat_id;
            if (isset($lampiran_surat[$id_sm]) && count($lampiran_surat[$id_sm]) > 0):
                foreach ($lampiran_surat[$id_sm] as $lamp):
            ?>
            <div style="display:flex;align-items:center;gap:10px;background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:10px 14px;margin-bottom:8px;cursor:pointer;" onclick="bukaPreviewPDF('<?php echo $lamp['file_path']; ?>')">
                <i class="fa-solid fa-file-lines" style="color:var(--sky);font-size:1.2rem;flex-shrink:0;"></i>
                <div style="flex:1;overflow:hidden;">
                    <div style="font-size:.85rem;font-weight:600;color:var(--ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($lamp['nama_file']); ?></div>
                    <div style="font-size:.7rem;color:var(--ink-m);">Lampiran PDF</div>
                </div>
                <a href="../ambil_pdf.php?file=<?php echo urlencode($lamp['file_path']); ?>" target="_blank" onclick="event.stopPropagation();" style="background:var(--green-lt);color:var(--green);border:1px solid #bbf7d0;border-radius:8px;padding:5px 9px;font-size:.78rem;text-decoration:none;">
                    <i class="fa-solid fa-download"></i>
                </a>
            </div>
            <?php
                endforeach;
            else:
            ?>
            <div style="text-align:center;padding:20px;color:var(--ink-m);">
                <i class="fa-solid fa-box-open" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:.4;"></i>
                <span style="font-size:.82rem;">Tidak ada lampiran tambahan.</span>
            </div>
            <?php endif; ?>
        </div>
        <div class="m-footer">
            <button onclick="closeModal('mFile<?php echo $id; ?>')" style="background:#f1f5f9;color:var(--ink-m);border:1px solid var(--border);border-radius:10px;padding:9px 20px;font-size:.85rem;font-weight:600;cursor:pointer;">Tutup</button>
        </div>
    </div>
</div>

<!-- ─── MODAL LAPORAN (jika sudah selesai) ─── -->
<?php if ($data['status'] == 'Selesai' && !empty($data['laporan_tindak_lanjut'])): ?>
<div class="m-overlay" id="mLaporan<?php echo $id; ?>">
    <div class="m-box">
        <div class="m-hdr m-hdr-green">
            <div>
                <div class="m-title"><i class="fa-solid fa-file-lines me-2"></i>Laporan Tindak Lanjut</div>
                <div class="m-subtitle"><?php echo htmlspecialchars($data['nomor_surat']); ?></div>
            </div>
            <button class="m-close-btn" onclick="closeModal('mLaporan<?php echo $id; ?>')">&#x2715;</button>
        </div>
        <div class="m-body">
            <div class="si-label mb-2">Laporan yang Disubmit</div>
            <div style="background:var(--green-lt);border:1px solid #bbf7d0;border-left:3px solid var(--green);border-radius:10px;padding:14px 16px;font-size:.875rem;color:#14532d;line-height:1.6;">
                <?php echo nl2br(htmlspecialchars($data['laporan_tindak_lanjut'])); ?>
            </div>
        </div>
        <div class="m-footer">
            <button onclick="closeModal('mLaporan<?php echo $id; ?>')" style="background:#f1f5f9;color:var(--ink-m);border:1px solid var(--border);border-radius:10px;padding:9px 20px;font-size:.85rem;font-weight:600;cursor:pointer;">Tutup</button>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ─── MODAL TINDAK LANJUT (hanya jika belum selesai) ─── -->
<?php if ($data['status'] != 'Selesai'): ?>
<div class="m-overlay" id="mTindak<?php echo $id; ?>">
    <div class="m-box">
        <div class="m-hdr m-hdr-green">
            <div>
                <div class="m-title"><i class="fa-solid fa-check-to-slot me-2"></i>Tindak Lanjut Surat</div>
                <div class="m-subtitle"><?php echo htmlspecialchars($data['nomor_surat']); ?></div>
            </div>
            <button class="m-close-btn" onclick="closeModal('mTindak<?php echo $id; ?>')">&#x2715;</button>
        </div>
        <form action="aksi_disposisi.php" method="POST">
            <div class="m-body">
                <input type="hidden" name="disposisi_id" value="<?php echo $id; ?>">
                <input type="hidden" name="surat_id"     value="<?php echo $surat_id; ?>">

                <!-- Reminder instruksi -->
                <div style="background:var(--amber-lt);border:1px solid #fde68a;border-left:3px solid var(--amber);border-radius:10px;padding:12px 14px;margin-bottom:16px;">
                    <div style="font-size:.72rem;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Instruksi Atasan</div>
                    <div style="font-size:.875rem;color:#78350f;font-style:italic;">"<?php echo htmlspecialchars($data['instruksi']); ?>"</div>
                </div>

                <?php if ($role_sekarang == 'Guru'): ?>
                <!-- ── GURU: hanya bisa selesaikan ── -->
                <input type="hidden" name="jenis_tindakan" value="selesai">
                <div style="margin-bottom:14px;">
                    <label class="f-label">Laporan Hasil Pekerjaan <span style="color:var(--red);">*</span></label>
                    <textarea class="f-input" name="laporan_tindak_lanjut" rows="4" placeholder="Jelaskan hasil tindak lanjut Anda terhadap disposisi ini..." required></textarea>
                </div>

                <?php else: ?>
                <!-- ── WAKA / ADMIN / KEPSEK: bisa selesai atau teruskan ── -->
                <div style="margin-bottom:14px;">
                    <label class="f-label">Pilih Keputusan <span style="color:var(--red);">*</span></label>
                    <select class="f-input f-select" name="jenis_tindakan" id="jenis_tindakan_<?php echo $id; ?>" required onchange="toggleTeruskan(<?php echo $id; ?>)">
                        <option value="">-- Pilih Aksi --</option>
                        <option value="selesai">✅ Selesaikan & Arsipkan</option>
                        <option value="teruskan">➡️ Teruskan / Disposisi Berjenjang</option>
                    </select>
                </div>

                <!-- Jika selesai -->
                <div id="div_selesai_<?php echo $id; ?>" style="display:none;margin-bottom:14px;">
                    <label class="f-label">Laporan Tindak Lanjut <span style="color:var(--red);">*</span></label>
                    <textarea class="f-input" name="laporan_tindak_lanjut" id="laporan_<?php echo $id; ?>" rows="3" placeholder="Laporan singkat penyelesaian..."></textarea>
                </div>

                <!-- Jika teruskan -->
                <div id="div_teruskan_<?php echo $id; ?>" style="display:none;">
                    <div style="margin-bottom:12px;">
                        <label class="f-label">Teruskan Kepada <span style="color:var(--red);">*</span></label>
                        <!-- Select all shortcut -->
                        <div style="padding:8px 14px;border-bottom:1px solid var(--border);background:#f8fafc;border-radius:10px 10px 0 0;display:flex;align-items:center;gap:8px;">
                            <input type="checkbox" id="checkAllDisp_<?php echo $id; ?>" onchange="toggleCheckboxes(this,'user_cb_disp_<?php echo $id; ?>')" style="width:15px;height:15px;accent-color:var(--blue);">
                            <label for="checkAllDisp_<?php echo $id; ?>" style="font-size:.78rem;font-weight:700;color:var(--blue);cursor:pointer;"><i class="fa-solid fa-users me-1"></i>Pilih Semua Pegawai</label>
                        </div>
                        <div class="pegawai-list" style="border-radius:0 0 10px 10px;">
                            <?php foreach ($daftar_pegawai as $u): ?>
                            <div class="pegawai-item">
                                <input type="checkbox" class="user_cb_disp_<?php echo $id; ?>" name="ke_user_id_baru[]" value="<?php echo $u['id']; ?>" id="upg_<?php echo $u['id']; ?>_<?php echo $id; ?>" style="width:15px;height:15px;accent-color:var(--blue);">
                                <label for="upg_<?php echo $u['id']; ?>_<?php echo $id; ?>"><?php echo htmlspecialchars($u['nama_lengkap']); ?></label>
                                <span class="role-tag"><?php echo htmlspecialchars($u['nama_role']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="f-label">Instruksi Tambahan</label>
                        <textarea class="f-input" name="instruksi_baru" rows="2" placeholder="Contoh: Tolong segera ditindaklanjuti..."></textarea>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label class="f-label">Batas Waktu SLA (Opsional)</label>
                        <input type="datetime-local" class="f-input" name="batas_waktu_sla_baru">
                    </div>
                </div>
                <?php endif; ?>

            </div>
            <div class="m-footer">
                <button type="button" onclick="closeModal('mTindak<?php echo $id; ?>')" style="background:#f1f5f9;color:var(--ink-m);border:1px solid var(--border);border-radius:10px;padding:9px 20px;font-size:.85rem;font-weight:600;cursor:pointer;">Batal</button>
                <button type="submit" name="tindak_lanjut" style="background:var(--green);color:#fff;border:none;border-radius:10px;padding:9px 22px;font-size:.85rem;font-weight:700;cursor:pointer;">
                    <i class="fa-solid fa-save me-1"></i> Simpan Keputusan
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php endforeach; /* end modal loop */ ?>


<!-- ════════════════════════════════════════
     MODAL PDF FULLSCREEN (shared)
════════════════════════════════════════ -->
<div class="modal fade" id="modalPreviewPDF" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen"> <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">Preview Surat</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="overflow: hidden;">
                <iframe id="framePDF" src="" width="100%" height="100%" style="border: none;"></iframe>
            </div>
        </div>
    </div>
</div>


<!-- ════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════ -->
<script>
// ─── Modal helpers ───────────────────────────────────────────
function openModal(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
    var el = document.getElementById(id);
    if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}
// Tutup modal dengan klik backdrop
document.querySelectorAll('.m-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    });
});
// ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.m-overlay.open').forEach(function(el) {
            el.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

// ─── PDF Preview ─────────────────────────────────────────────
function bukaPreviewPDF(namaFile) {
    var viewerUrl = "https://simpers.42web.io/vendor/pdfjs/web/viewer.html";
    var proxyUrl  = "https://simpers.42web.io/ambil_pdf.php?file=" + encodeURIComponent(namaFile);
    var finalUrl  = viewerUrl + "?file=" + encodeURIComponent(proxyUrl);
    var frame = document.getElementById('framePDF');
    if (frame) {
        frame.src = finalUrl + "&v=" + new Date().getTime();
        openModal('modalPreviewPDF');
    }
}
function bukaPDF(namaFile) {
    var fileUrl = window.location.origin + "/uploads/surat_masuk/" + namaFile;
    var frame = document.getElementById('framePDF');
    if (frame) {
        frame.src = fileUrl;
        openModal('modalPreviewPDF');
    }
}

// ─── Toggle Teruskan/Selesai ──────────────────────────────────
function toggleTeruskan(id) {
    var jenis       = document.getElementById('jenis_tindakan_' + id).value;
    var divTeruskan = document.getElementById('div_teruskan_' + id);
    var divSelesai  = document.getElementById('div_selesai_' + id);
    var laporan     = document.getElementById('laporan_' + id);
    if (jenis === 'teruskan') {
        if (divTeruskan) divTeruskan.style.display = 'block';
        if (divSelesai)  divSelesai.style.display  = 'none';
        if (laporan)     laporan.removeAttribute('required');
    } else if (jenis === 'selesai') {
        if (divTeruskan) divTeruskan.style.display = 'none';
        if (divSelesai)  divSelesai.style.display  = 'block';
        if (laporan)     laporan.setAttribute('required', 'required');
    } else {
        if (divTeruskan) divTeruskan.style.display = 'none';
        if (divSelesai)  divSelesai.style.display  = 'none';
        if (laporan)     laporan.removeAttribute('required');
    }
}

// ─── Bulk Action ──────────────────────────────────────────────
function toggleAllBulk(source) {
    document.querySelectorAll('.bulk-item').forEach(function(cb) {
        cb.checked = source.checked;
    });
    var deskAll = document.getElementById('checkAllBulk');
    var mobAll  = document.getElementById('checkAllBulkMobile');
    if (deskAll) deskAll.checked = source.checked;
    if (mobAll)  mobAll.checked  = source.checked;
    updateBulkUI();
}

function updateBulkUI() {
    var checkedBoxes = document.querySelectorAll('.bulk-item:checked');
    var uniqueIds    = new Set();
    checkedBoxes.forEach(function(cb) { uniqueIds.add(cb.value); });

    var container = document.getElementById('bulkActionContainer');
    var countEl   = document.getElementById('selectedCount');
    if (countEl) countEl.innerText = uniqueIds.size;

    if (uniqueIds.size > 0) {
        container.classList.add('visible');
    } else {
        container.classList.remove('visible');
        var da = document.getElementById('checkAllBulk');
        var ma = document.getElementById('checkAllBulkMobile');
        if (da) da.checked = false;
        if (ma) ma.checked = false;
    }
}

function clearBulk() {
    document.querySelectorAll('.bulk-item').forEach(function(cb) { cb.checked = false; });
    updateBulkUI();
}

function executeBulkAction() {
    var checkedBoxes = document.querySelectorAll('.bulk-item:checked');
    var uniqueIds    = new Set();
    checkedBoxes.forEach(function(cb) { uniqueIds.add(cb.value); });

    if (uniqueIds.size === 0) {
        alert('Pilih minimal satu surat terlebih dahulu.');
        return;
    }
    var jenis = document.getElementById('bulkActionSelect').value;
    if (!confirm('Terapkan tindakan "' + jenis + '" ke ' + uniqueIds.size + ' surat terpilih?')) return;

    document.getElementById('inputBulkJenis').value = jenis;
    document.getElementById('inputBulkIds').value   = Array.from(uniqueIds).join(',');
    document.getElementById('formBulkAction').submit();
}

// ─── Checkbox toggle helper ───────────────────────────────────
function toggleCheckboxes(source, className) {
    document.querySelectorAll('.' + className).forEach(function(cb) {
        cb.checked = source.checked;
    });
}
</script>

<?php include '../layouts/footer.php'; ?>