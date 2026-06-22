<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: login.php");
    exit;
}

$jenis_surat = $_GET['jenis']     ?? 'keluar';
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_akhir   = $_GET['tgl_akhir'] ?? date('Y-m-d');
$status      = $_GET['status']    ?? 'all';

$tabel        = ($jenis_surat == 'masuk') ? 'surat_masuk' : 'surat_keluar';
$where_clause = "DATE(created_at) BETWEEN '$tgl_mulai' AND '$tgl_akhir' AND deleted_at IS NULL";

if ($jenis_surat == 'keluar' && $status !== 'all') {
    $where_clause .= " AND status_workflow = '$status'";
}

$stat_total   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as t FROM $tabel WHERE $where_clause"))['t'] ?? 0;
$stat_selesai = 0; $stat_pending = 0;
if ($jenis_surat == 'keluar') {
    $stat_selesai = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as t FROM $tabel WHERE $where_clause AND status_workflow IN ('Approved','Terkirim')"))['t'] ?? 0;
    $stat_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as t FROM $tabel WHERE $where_clause AND status_workflow IN ('Draft','Review','Revisi')"))['t'] ?? 0;
}

$result_data = mysqli_query($koneksi, "SELECT * FROM $tabel WHERE $where_clause ORDER BY created_at DESC");

include 'layouts/header.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
/* ── Design System (selaras dashboard & surat_masuk) ── */
:root {
    --blue:     #4a70a9;
    --blue-dk:  #3a5a8f;
    --blue-lt:  #eaf0f8;
    --amber:    #d97706;
    --amber-lt: #fef3c7;
    --coral:    #e05252;
    --coral-lt: #fef2f2;
    --sage:     #16a34a;
    --sage-lt:  #dcfce7;
    --ink:      #0f1923;
    --muted:    #64748b;
    --surface:  #ffffff;
    --bg:       #f2f5f9;
    --border:   #dde4ee;
    --radius:   12px;
    --shadow:   0 2px 12px rgba(74,112,169,.08);
    --shadow-md:0 4px 20px rgba(74,112,169,.13);
}
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--ink); }

/* ── PAGE HEADER ── */
.page-header {
    background: var(--surface); border-radius: var(--radius);
    padding: 18px 24px; margin-bottom: 20px;
    border: 1px solid var(--border); box-shadow: var(--shadow);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 14px;
}
.page-title-icon {
    width: 42px; height: 42px; border-radius: 10px;
    background: var(--blue-lt); color: var(--blue);
    display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
}
.page-title h4 { margin: 0; font-weight: 700; font-size: 1.15rem; }
.page-title small { color: var(--muted); font-size: .78rem; }

/* ── EXPORT BUTTONS ── */
.btn-export {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 16px; border-radius: 10px; font-weight: 600;
    font-size: .84rem; border: none; cursor: pointer;
    transition: all .2s; text-decoration: none; white-space: nowrap;
}
.btn-export:hover { transform: translateY(-1px); box-shadow: var(--shadow-md); }
.btn-pdf   { background: var(--coral-lt); color: var(--coral); border: 1.5px solid #fca5a5; }
.btn-pdf:hover   { background: var(--coral); color: #fff; }
.btn-excel { background: var(--sage-lt);  color: var(--sage);  border: 1.5px solid #86efac; }
.btn-excel:hover { background: var(--sage); color: #fff; }

/* ── FILTER PANEL ── */
.filter-panel {
    background: var(--surface); border-radius: var(--radius);
    border: 1px solid var(--border); box-shadow: var(--shadow);
    padding: 20px 24px; margin-bottom: 20px;
}
.filter-panel .filter-title {
    font-size: .75rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: var(--muted); margin-bottom: 14px;
    display: flex; align-items: center; gap: 8px;
}
.form-control, .form-select {
    border-radius: 8px; border: 1.5px solid var(--border);
    font-size: .875rem; font-family: 'DM Sans', sans-serif;
    background: var(--bg); transition: border-color .2s, box-shadow .2s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--blue); box-shadow: 0 0 0 3px rgba(74,112,169,.12);
    background: var(--surface); outline: none;
}
.form-label { font-size: .78rem; font-weight: 600; color: var(--ink); margin-bottom: 5px; }
.btn-filter {
    background: var(--blue); color: #fff; border: none;
    border-radius: 8px; padding: 10px 20px; font-weight: 600;
    font-size: .875rem; cursor: pointer; transition: all .2s;
    display: inline-flex; align-items: center; gap: 7px;
    font-family: 'DM Sans', sans-serif; width: 100%;
    justify-content: center;
}
.btn-filter:hover { background: var(--blue-dk); }

/* ── STAT CARDS ── */
.stat-card {
    background: var(--surface); border-radius: var(--radius);
    border: 1px solid var(--border); box-shadow: var(--shadow);
    padding: 18px 20px; position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
.stat-card .accent { position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: var(--radius) var(--radius) 0 0; }
.stat-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; margin-bottom: 12px; }
.stat-num  { font-size: 2rem; font-weight: 700; line-height: 1; margin-bottom: 3px; }
.stat-lbl  { font-size: .75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.stat-sub  { font-size: .75rem; color: var(--muted); margin-top: 8px; padding-top: 8px; border-top: 1px solid var(--border); }

/* ── PERIOD BADGE ── */
.period-badge {
    display: inline-flex; align-items: center; gap: 7px;
    background: var(--blue-lt); color: var(--blue);
    border: 1px solid #c7d9f0; border-radius: 8px;
    padding: 7px 14px; font-size: .8rem; font-weight: 600;
    margin-bottom: 16px;
}

/* ── TABLE PANEL ── */
.table-panel {
    background: var(--surface); border-radius: var(--radius);
    border: 1px solid var(--border); box-shadow: var(--shadow); overflow: hidden;
}
.table-toolbar {
    padding: 14px 20px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
}
.table-toolbar .toolbar-title { font-weight: 700; font-size: .9rem; }
.table-count { background: var(--blue-lt); color: var(--blue); border-radius: 20px; padding: 3px 12px; font-size: .75rem; font-weight: 700; }
table { margin: 0; width: 100%; border-collapse: collapse; }
thead th {
    background: var(--bg); color: var(--muted);
    font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; padding: 12px 16px;
    border-bottom: 2px solid var(--border); white-space: nowrap;
}
tbody tr { border-bottom: 1px solid var(--border); transition: background .15s; }
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f7faff; }
tbody td { padding: 13px 16px; font-size: .855rem; vertical-align: middle; }

.nomor-surat { font-weight: 700; color: var(--blue); font-family: monospace; font-size: .82rem; }

/* Badge status */
.badge-st {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .71rem; font-weight: 700; padding: 4px 10px;
    border-radius: 20px; white-space: nowrap;
}
.bs-approved  { background: var(--sage-lt);  color: var(--sage);  }
.bs-terkirim  { background: var(--sage-lt);  color: var(--sage);  }
.bs-review    { background: var(--amber-lt); color: var(--amber); }
.bs-revisi    { background: var(--amber-lt); color: var(--amber); }
.bs-draft     { background: #f1f5f9;         color: #64748b;     }
.bs-default   { background: #f1f5f9;         color: #64748b;     }

/* ── MOBILE CARD LIST ── */
.mobile-list { padding: 12px; }
.lap-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; margin-bottom: 10px; box-shadow: var(--shadow);
    overflow: hidden; transition: box-shadow .2s;
}
.lap-card:hover { box-shadow: var(--shadow-md); }
.lap-card-top {
    padding: 11px 14px; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
}
.lap-card-body { padding: 11px 14px; }
.lap-card-perihal { font-weight: 700; font-size: .88rem; color: var(--ink); margin-bottom: 5px; }
.lap-card-meta    { font-size: .75rem; color: var(--muted); margin-bottom: 3px; }
.lap-no { font-size: .72rem; background: var(--bg); color: var(--muted); padding: 2px 8px; border-radius: 20px; font-weight: 600; }

/* ── EMPTY STATE ── */
.empty-state { padding: 56px 20px; text-align: center; color: var(--muted); }
.empty-state i { font-size: 2.8rem; color: var(--border); display: block; margin-bottom: 14px; }
.empty-state p { font-size: .875rem; }

/* ── ANIMATIONS ── */
.fade-up { opacity:0; transform:translateY(14px); animation: fadeUp .4s ease forwards; }
@keyframes fadeUp { to { opacity:1; transform:translateY(0); } }
.d1{animation-delay:.05s} .d2{animation-delay:.10s} .d3{animation-delay:.15s} .d4{animation-delay:.20s}

/* ── PRINT ── */
@media print {
    body { background: white; font-family: 'DM Sans', sans-serif; }
    .no-print, .sidebar, .navbar { display: none !important; }
    .print-only { display: block !important; }
    .table-panel { box-shadow: none; border: 1px solid #ccc; }
    thead th { background: #f0f0f0 !important; color: #000 !important; }
    tbody td { color: #000 !important; }
    @page { size: landscape; margin: 1.2cm; }
}
.print-only { display: none; }

@media (max-width: 767px) {
    .page-header { padding: 14px 16px; }
    .filter-panel { padding: 16px; }
    .stat-num { font-size: 1.7rem; }
}
</style>

<!-- Print Header -->
<div class="print-only text-center mb-4">
    <h3 class="fw-bold mb-1">LAPORAN ADMINISTRASI PERSURATAN</h3>
    <p class="mb-0">Periode: <?= date('d/m/Y', strtotime($tgl_mulai)) ?> s.d. <?= date('d/m/Y', strtotime($tgl_akhir)) ?></p>
    <p class="fw-bold">Jenis: <?= strtoupper($jenis_surat == 'masuk' ? 'Surat Masuk' : 'Surat Keluar') ?></p>
    <hr style="border-top:2px solid #000;">
</div>

<!-- ══ PAGE HEADER ══ -->
<div class="page-header fade-up no-print">
    <div class="d-flex align-items-center gap-3">
        <div class="page-title-icon"><i class="fa-solid fa-file-invoice"></i></div>
        <div class="page-title">
            <h4>Laporan Persuratan</h4>
            <small>Rekapitulasi & ekspor data surat · <?= date('d M Y') ?></small>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="export_pdf.php?jenis=<?= $jenis_surat ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_akhir=<?= $tgl_akhir ?>&status=<?= $status ?>"
            class="btn-export btn-pdf">
            <i class="fa-solid fa-file-pdf"></i> Ekspor PDF
        </a>
        <a href="export_excel.php?jenis=<?= $jenis_surat ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_akhir=<?= $tgl_akhir ?>&status=<?= $status ?>"
            class="btn-export btn-excel">
            <i class="fa-solid fa-file-excel"></i> Ekspor Excel
        </a>
    </div>
</div>

<!-- ══ FILTER PANEL ══ -->
<div class="filter-panel no-print fade-up d1">
    <div class="filter-title">
        <i class="fa-solid fa-sliders" style="color:var(--blue);"></i>
        Filter Laporan
    </div>
    <form method="GET" action="">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-md-3">
                <label class="form-label">Jenis Laporan</label>
                <select name="jenis" class="form-select">
                    <option value="keluar" <?= $jenis_surat=='keluar' ? 'selected':'' ?>>Surat Keluar</option>
                    <option value="masuk"  <?= $jenis_surat=='masuk'  ? 'selected':'' ?>>Surat Masuk</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai" class="form-control" value="<?= $tgl_mulai ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tgl_akhir" class="form-control" value="<?= $tgl_akhir ?>">
            </div>
            <?php if ($jenis_surat == 'keluar'): ?>
            <div class="col-6 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="all"          <?= $status=='all'         ?'selected':'' ?>>Semua</option>
                    <option value="Terkirim"      <?= $status=='Terkirim'    ?'selected':'' ?>>Terkirim</option>
                    <option value="Approved"      <?= $status=='Approved'    ?'selected':'' ?>>Disetujui</option>
                    <option value="Review"        <?= $status=='Review'      ?'selected':'' ?>>Review</option>
                    <option value="Draft"         <?= $status=='Draft'       ?'selected':'' ?>>Draft</option>
                    <option value="Dialokasikan"  <?= $status=='Dialokasikan'?'selected':'' ?>>Dialokasikan</option>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn-filter">
                    <i class="fa-solid fa-magnifying-glass"></i> Tampilkan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- ══ STAT CARDS ══ -->
<div class="row g-3 mb-4">
    <div class="<?= $jenis_surat=='keluar' ? 'col-6 col-md-4' : 'col-12 col-md-6' ?> fade-up d2">
        <div class="stat-card">
            <div class="accent" style="background:var(--blue);"></div>
            <div class="stat-icon" style="background:var(--blue-lt);color:var(--blue);">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="stat-num" style="color:var(--blue);"><?= $stat_total ?></div>
            <div class="stat-lbl">Total Surat</div>
            <div class="stat-sub">
                <i class="fa-regular fa-calendar me-1"></i>
                <?= date('d M', strtotime($tgl_mulai)) ?> – <?= date('d M Y', strtotime($tgl_akhir)) ?>
            </div>
        </div>
    </div>

    <?php if ($jenis_surat == 'keluar'): ?>
    <div class="col-6 col-md-4 fade-up d3">
        <div class="stat-card">
            <div class="accent" style="background:var(--sage);"></div>
            <div class="stat-icon" style="background:var(--sage-lt);color:var(--sage);">
                <i class="fa-solid fa-check-double"></i>
            </div>
            <div class="stat-num" style="color:var(--sage);"><?= $stat_selesai ?></div>
            <div class="stat-lbl">Selesai / Approved</div>
            <div class="stat-sub">
                <?php $pct = $stat_total > 0 ? round($stat_selesai/$stat_total*100) : 0; ?>
                <div style="background:var(--border);height:5px;border-radius:99px;margin-bottom:4px;overflow:hidden;">
                    <div style="background:var(--sage);height:100%;width:<?= $pct ?>%;border-radius:99px;transition:width 1s;"></div>
                </div>
                <?= $pct ?>% dari total
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 fade-up d4">
        <div class="stat-card">
            <div class="accent" style="background:var(--amber);"></div>
            <div class="stat-icon" style="background:var(--amber-lt);color:var(--amber);">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
            <div class="stat-num" style="color:var(--amber);"><?= $stat_pending ?></div>
            <div class="stat-lbl">Pending / Proses</div>
            <div class="stat-sub <?= $stat_pending > 0 ? 'fw-bold' : '' ?>" style="color:<?= $stat_pending > 0 ? 'var(--amber)' : 'var(--muted)' ?>;">
                <?= $stat_pending > 0
                    ? '<i class="fa-solid fa-triangle-exclamation me-1"></i>Perlu ditindaklanjuti'
                    : '<i class="fa-solid fa-check me-1"></i>Semua selesai' ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══ PERIODE INDICATOR ══ -->
<div class="period-badge no-print">
    <i class="fa-solid fa-calendar-range"></i>
    Menampilkan data:
    <strong><?= date('d M Y', strtotime($tgl_mulai)) ?></strong>
    &nbsp;→&nbsp;
    <strong><?= date('d M Y', strtotime($tgl_akhir)) ?></strong>
    <?php if ($jenis_surat == 'keluar' && $status !== 'all'): ?>
        · Status: <strong><?= htmlspecialchars($status) ?></strong>
    <?php endif; ?>
</div>

<!-- ══ TABEL DESKTOP ══ -->
<div class="table-panel d-none d-md-block fade-up d3">
    <div class="table-toolbar no-print">
        <span class="toolbar-title">
            <i class="fa-solid fa-table-list me-2" style="color:var(--blue);"></i>
            <?= $jenis_surat == 'keluar' ? 'Data Surat Keluar' : 'Data Surat Masuk' ?>
        </span>
        <span class="table-count"><?= $stat_total ?> data</span>
    </div>
    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th>Tgl Surat</th>
                    <th>Nomor Surat</th>
                    <th><?= $jenis_surat == 'keluar' ? 'Tujuan' : 'Pengirim' ?></th>
                    <th>Perihal</th>
                    <?php if ($jenis_surat == 'keluar') echo '<th>Status</th>'; ?>
                </tr>
            </thead>
            <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($result_data) > 0) {
                while ($row = mysqli_fetch_array($result_data)) {
                    $tgl     = date('d/m/Y', strtotime($row['created_at']));
                    $nomor   = htmlspecialchars($row['nomor_surat'] ?? '-');
                    $perihal = htmlspecialchars($row['perihal']     ?? '-');
                    $pihak   = htmlspecialchars(($jenis_surat == 'keluar') ? ($row['tujuan'] ?? '-') : ($row['pengirim'] ?? '-'));
                    $sw      = $row['status_workflow'] ?? '';
                    $bs      = match($sw) {
                        'Approved'  => 'bs-approved',
                        'Terkirim'  => 'bs-terkirim',
                        'Review'    => 'bs-review',
                        'Revisi'    => 'bs-revisi',
                        'Draft'     => 'bs-draft',
                        default     => 'bs-default'
                    };
                    echo "<tr>
                        <td style='text-align:center;color:var(--muted);font-size:.78rem;'>{$no}</td>
                        <td style='color:var(--muted);font-size:.82rem;white-space:nowrap;'><i class='fa-regular fa-calendar me-1'></i>{$tgl}</td>
                        <td><span class='nomor-surat'>{$nomor}</span></td>
                        <td style='font-size:.84rem;color:var(--ink);'>{$pihak}</td>
                        <td style='font-size:.84rem;'>{$perihal}</td>";
                    if ($jenis_surat == 'keluar') echo "<td><span class='badge-st {$bs}'>{$sw}</span></td>";
                    echo "</tr>";
                    $no++;
                }
            } else {
                $colspan = $jenis_surat == 'keluar' ? 6 : 5;
                echo "<tr><td colspan='{$colspan}'><div class='empty-state'>
                    <i class='fa-solid fa-folder-open'></i>
                    <p>Tidak ada data pada rentang tanggal ini.</p>
                </div></td></tr>";
            }
            ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══ CARD MOBILE ══ -->
<?php
// Reset result pointer
mysqli_data_seek($result_data, 0);
?>
<div class="d-md-none fade-up d3">
    <div class="table-toolbar no-print" style="background:var(--surface);border-radius:var(--radius) var(--radius) 0 0;border:1px solid var(--border);border-bottom:none;">
        <span class="toolbar-title" style="font-size:.85rem;">
            <i class="fa-solid fa-table-list me-2" style="color:var(--blue);"></i>
            <?= $jenis_surat == 'keluar' ? 'Surat Keluar' : 'Surat Masuk' ?>
        </span>
        <span class="table-count"><?= $stat_total ?> data</span>
    </div>

    <?php if (mysqli_num_rows($result_data) > 0): ?>
    <div class="mobile-list" style="background:var(--surface);border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);">
        <?php
        $no2 = 1;
        while ($row = mysqli_fetch_array($result_data)) {
            $tgl     = date('d M Y', strtotime($row['created_at']));
            $nomor   = htmlspecialchars($row['nomor_surat'] ?? '-');
            $perihal = htmlspecialchars($row['perihal']     ?? '-');
            $pihak   = htmlspecialchars(($jenis_surat == 'keluar') ? ($row['tujuan'] ?? '-') : ($row['pengirim'] ?? '-'));
            $sw      = $row['status_workflow'] ?? '';
            $bs      = match($sw) {
                'Approved' => 'bs-approved', 'Terkirim' => 'bs-terkirim',
                'Review'   => 'bs-review',   'Revisi'   => 'bs-revisi',
                'Draft'    => 'bs-draft',    default    => 'bs-default'
            };
            echo "
            <div class='lap-card'>
                <div class='lap-card-top'>
                    <span class='nomor-surat'>{$nomor}</span>
                    " . ($jenis_surat == 'keluar' ? "<span class='badge-st {$bs}'>{$sw}</span>" : "<span class='lap-no'>#{$no2}</span>") . "
                </div>
                <div class='lap-card-body'>
                    <div class='lap-card-perihal'>{$perihal}</div>
                    <div class='lap-card-meta'><i class='fa-solid fa-" . ($jenis_surat=='keluar' ? 'paper-plane' : 'building') . " me-1'></i>{$pihak}</div>
                    <div class='lap-card-meta'><i class='fa-regular fa-calendar me-1'></i>{$tgl}</div>
                </div>
            </div>";
            $no2++;
        }
        ?>
    </div>
    <?php else: ?>
    <div style="background:var(--surface);border:1px solid var(--border);border-top:none;border-radius:0 0 var(--radius) var(--radius);">
        <div class="empty-state">
            <i class="fa-solid fa-folder-open"></i>
            <p>Tidak ada data pada rentang tanggal ini.</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'layouts/footer.php'; ?>