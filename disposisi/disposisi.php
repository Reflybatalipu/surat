<?php
session_start();
include '../config/koneksi.php';

// 🛡️ KEAMANAN: Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

include '../layouts/header.php'; 
$user_id_sekarang = $_SESSION['user_id'];
$role_sekarang = $_SESSION['nama_role'];

// ========================================================
// 1. AMBIL SEMUA DATA DISPOSISI & SIMPAN KE ARRAY
// ========================================================
$sql_disposisi = "
    SELECT d.*, sm.nomor_surat, sm.pengirim, sm.perihal, sm.file_path, 
           u_dari.nama_lengkap AS nama_pengirim, u_dari.nip
    FROM disposisi d
    JOIN surat_masuk sm ON d.surat_id = sm.id
    JOIN users u_dari ON d.dari_user_id = u_dari.id
    WHERE d.ke_user_id = '$user_id_sekarang' AND sm.deleted_at IS NULL
    ORDER BY d.id DESC
";
$query_disp = mysqli_query($koneksi, $sql_disposisi);
$data_disposisi = [];
$surat_ids = []; // Array untuk menampung ID surat (guna mencari lampiran)

while ($row = mysqli_fetch_array($query_disp)) {
    $data_disposisi[] = $row;
    
    // Hindari duplikasi ID surat ke dalam array
    if (!in_array($row['surat_id'], $surat_ids)) {
        $surat_ids[] = $row['surat_id'];
    }
}

// ========================================================
// 2. AMBIL DATA LAMPIRAN MULTI-UPLOAD
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
// 3. AMBIL DATA PEGAWAI UNTUK MODAL TERUSKAN
// ========================================================
$daftar_pegawai = [];
$daftar_guru    = []; // khusus grup Guru untuk fitur "Pilih Semua Guru"
if ($role_sekarang != 'Guru') {
    $q_user = mysqli_query($koneksi, "SELECT u.id, u.nama_lengkap, r.nama_role FROM users u JOIN roles r ON u.role_id = r.id WHERE u.is_active = 1 AND u.id != '$user_id_sekarang' ORDER BY r.nama_role ASC, u.nama_lengkap ASC");
    while($u = mysqli_fetch_array($q_user)){
        $daftar_pegawai[] = $u;
        if ($u['nama_role'] == 'Guru') {
            $daftar_guru[] = $u;
        }
    }
}

// ========================================================
// 4. [BARU] AMBIL DATA HISTORY DISPOSISI PER SURAT
// ========================================================
$history_disposisi = [];
if (!empty($surat_ids)) {
    $ids_str = implode(',', $surat_ids);
    $sql_history = "
        SELECT d.*, u_dari.nama_lengkap AS nama_dari, r_dari.nama_role AS role_dari,
               u_ke.nama_lengkap AS nama_ke, r_ke.nama_role AS role_ke
        FROM disposisi d
        JOIN users u_dari ON d.dari_user_id = u_dari.id
        JOIN roles r_dari ON u_dari.role_id = r_dari.id
        JOIN users u_ke ON d.ke_user_id = u_ke.id
        JOIN roles r_ke ON u_ke.role_id = r_ke.id
        WHERE d.surat_id IN ($ids_str)
        ORDER BY d.id ASC
    ";
    $q_history = mysqli_query($koneksi, $sql_history);
    while($h = mysqli_fetch_assoc($q_history)){
        $history_disposisi[$h['surat_id']][] = $h;
    }
}
?>

<style>
/* Membatasi tinggi kontainer tabel agar bisa di-scroll secara independen */
.table-responsive {
    max-height: 65vh; 
    overflow-y: auto;
    overflow-x: auto;
}

/* Membuat Header Tabel (thead) Menempel (Sticky) di atas */
.table-responsive thead th {
    position: sticky;
    top: 0;
    background-color: #f8f9fa; 
    z-index: 10; 
    box-shadow: 0 2px 3px -1px rgba(0,0,0,0.1); 
}

/* Pastikan z-index dropdown tidak tertutup */
.table-responsive tbody td {
    position: relative;
    z-index: 1;
}

/* ========================================================
   TAMBAHAN STYLE: SLA Indicator, Timeline, Detail Modal
   ======================================================== */

/* SLA Badge Indicator */
.sla-badge-critical {
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
    color: white;
    border-radius: 8px;
    padding: 3px 8px;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    animation: sla-pulse 1.5s infinite;
    box-shadow: 0 2px 8px rgba(255,65,108,0.4);
}
.sla-badge-warning {
    background: linear-gradient(135deg, #f7971e, #ffd200);
    color: #333;
    border-radius: 8px;
    padding: 3px 8px;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 8px rgba(247,151,30,0.35);
}
.sla-badge-ok {
    background: linear-gradient(135deg, #11998e, #38ef7d);
    color: white;
    border-radius: 8px;
    padding: 3px 8px;
    font-size: 0.72rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.sla-badge-none {
    color: #adb5bd;
    font-size: 0.78rem;
    font-style: italic;
}
@keyframes sla-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.75; }
}

/* SLA Countdown Bar */
.sla-progress-wrap {
    margin-top: 4px;
}
.sla-progress-bar-inner {
    height: 4px;
    border-radius: 2px;
    transition: width 0.4s ease;
}

/* Timeline Aktivitas di Modal Detail */
.timeline-activity {
    position: relative;
    padding-left: 36px;
}
.timeline-activity::before {
    content: '';
    position: absolute;
    left: 13px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #dee2e6, transparent);
}
.timeline-item {
    position: relative;
    margin-bottom: 18px;
}
.timeline-dot {
    position: absolute;
    left: -24px;
    top: 3px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.65rem;
    color: white;
    z-index: 2;
    box-shadow: 0 1px 4px rgba(0,0,0,0.2);
}
.timeline-dot-masuk    { background: #0d6efd; }
.timeline-dot-baca     { background: #0dcaf0; }
.timeline-dot-forward  { background: #fd7e14; }
.timeline-dot-selesai  { background: #198754; }
.timeline-dot-ttd      { background: #6f42c1; }
.timeline-dot-menunggu { background: #dc3545; }

.timeline-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.85rem;
}
.timeline-card-kepsek {
    border-left: 3px solid #0d6efd;
}
.timeline-card-penerima {
    border-left: 3px solid #198754;
}

/* Rincian surat di modal detail */
.detail-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.detail-info-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 10px 14px;
    border: 1px solid #e9ecef;
}
.detail-info-item .label {
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 2px;
}
.detail-info-item .value {
    font-size: 0.9rem;
    font-weight: 600;
    color: #212529;
}
@media (max-width: 575px) {
    .detail-info-grid { grid-template-columns: 1fr; }
}

/* Nav Tab Styling di Modal Detail */
.nav-tabs-detail .nav-link {
    font-size: 0.82rem;
    padding: 7px 12px;
    color: #6c757d;
    border-radius: 0;
}
.nav-tabs-detail .nav-link.active {
    font-weight: 700;
    color: #0d6efd;
    border-bottom: 2px solid #0d6efd;
    background: transparent;
}

/* POV Badge */
.pov-badge-kepsek {
    background: #e7f1ff;
    color: #0d6efd;
    border: 1px solid #b6d4fe;
    font-size: 0.7rem;
    padding: 2px 7px;
    border-radius: 20px;
    font-weight: 700;
}
.pov-badge-penerima {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #a3cfbb;
    font-size: 0.7rem;
    padding: 2px 7px;
    border-radius: 20px;
    font-weight: 700;
}

/* Tambahan CSS agar UI terasa lebih 'hidup' */
.hover-shadow:hover {
    background-color: #f8f9fa !important;
    border-color: #0d6efd !important;
    transition: all 0.2s ease-in-out;
}
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Bulk selected row highlight */
.row-bulk-selected {
    background-color: #e7f1ff !important;
    transition: background-color 0.2s;
}

/* SLA info banner kepsek */
.sla-info-banner {
    background: linear-gradient(135deg, #fff3cd, #ffeeba);
    border: 1px solid #ffc107;
    border-radius: 10px;
    padding: 10px 16px;
    font-size: 0.83rem;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.sla-info-banner i {
    margin-top: 2px;
    color: #856404;
    flex-shrink: 0;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-inbox me-2 text-warning"></i> Kotak Masuk Disposisi</h4>
</div>

<div class="d-flex align-items-center mb-3 p-2 bg-primary bg-opacity-10 border border-primary rounded shadow-sm" id="bulkActionContainer" style="display: none !important; transition: 0.3s;">
    <span class="me-3 fw-bold text-primary ms-2"><i class="fa-solid fa-check-double me-1"></i> <span id="selectedCount">0</span> Surat Terpilih</span>
    <select id="bulkActionSelect" class="form-select form-select-sm w-auto me-2 border-primary">
        <option value="selesai">✅ Selesaikan Surat Terpilih (Arsip)</option>
    </select>
    <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="executeBulkAction()">
        <i class="fa-solid fa-bolt me-1"></i> Terapkan
    </button>
</div>

<form id="formBulkAction" action="aksi_disposisi.php" method="POST" style="display: none;">
    <input type="hidden" name="proses_bulk_action" value="1">
    <input type="hidden" name="bulk_jenis_tindakan" id="inputBulkJenis">
    <input type="hidden" name="bulk_disposisi_ids" id="inputBulkIds">
</form>

<?php
// ========================================================
// [BARU] BANNER INFORMASI SLA UNTUK KEPALA SEKOLAH
// ========================================================
if ($role_sekarang == 'Kepala Sekolah' && !empty($data_disposisi)):
    $surat_mendekati_sla = 0;
    $surat_lewat_sla = 0;
    foreach($data_disposisi as $d) {
        if (!empty($d['batas_waktu_sla']) && $d['status'] != 'Selesai') {
            $diff = strtotime($d['batas_waktu_sla']) - time();
            if ($diff < 0) $surat_lewat_sla++;
            elseif ($diff <= 86400) $surat_mendekati_sla++; // ≤ 1 hari
        }
    }
    if ($surat_lewat_sla > 0 || $surat_mendekati_sla > 0):
?>
<div class="sla-info-banner mb-3">
    <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
    <div>
        <strong>Peringatan Batas Waktu SLA:</strong>
        <?php if ($surat_lewat_sla > 0): ?>
            <span class="text-danger fw-bold"><?= $surat_lewat_sla ?> surat telah melewati batas waktu SLA.</span>
        <?php endif; ?>
        <?php if ($surat_mendekati_sla > 0): ?>
            <span class="text-warning fw-bold"><?= $surat_mendekati_sla ?> surat mendekati batas waktu SLA (≤ 24 jam).</span>
        <?php endif; ?>
        Segera berikan tindak lanjut pada surat-surat tersebut.
    </div>
</div>
<?php endif; endif; ?>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0 p-md-4">
        
        <?php if(empty($data_disposisi)): ?>
            <div class='text-center py-5 text-muted'>
                <i class='fa-solid fa-inbox fs-1 d-block mb-3 text-light'></i> 
                Belum ada surat disposisi yang masuk.
            </div>
        <?php else: ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="3%" class="text-center ps-3">
                                <input class="form-check-input border-secondary" type="checkbox" id="checkAllBulk" onclick="toggleAllBulk(this)">
                            </th>
                            <th width="20%">Surat Info</th>
                            <th width="15%">Dari</th>
                            <th width="22%">Instruksi Disposisi</th>
                            <th width="15%">Batas Waktu (SLA)</th>
                            <th width="10%">Status</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_disposisi as $data): 
                            $warna_status = match($data['status']) {
                                'Menunggu' => 'bg-danger',
                                'Dibaca' => 'bg-info text-dark',
                                'Selesai' => 'bg-success',
                                default => 'bg-secondary'
                            };
                            // ── [BARU] Kalkulasi SLA ──
                            $sla_html = '<span class="sla-badge-none"><i class="fa-regular fa-clock"></i> Tidak ada</span>';
                            $sla_bar_html = '';
                            if (!empty($data['batas_waktu_sla'])) {
                                $diff_sek = strtotime($data['batas_waktu_sla']) - time();
                                $tgl_format = date('d/m/Y H:i', strtotime($data['batas_waktu_sla']));
                                if ($data['status'] == 'Selesai') {
                                    $sla_html = '<span class="sla-badge-ok"><i class="fa-solid fa-circle-check"></i> ' . $tgl_format . '</span>';
                                } elseif ($diff_sek < 0) {
                                    $sla_html = '<span class="sla-badge-critical"><i class="fa-solid fa-fire"></i> LEWAT SLA</span><br><small class="text-muted" style="font-size:0.7rem;">' . $tgl_format . '</small>';
                                    $sla_bar_html = '<div class="sla-progress-wrap"><div style="background:#dee2e6;height:4px;border-radius:2px;"><div class="sla-progress-bar-inner" style="width:100%;background:#ff416c;"></div></div></div>';
                                } elseif ($diff_sek <= 86400) {
                                    $jam = floor($diff_sek / 3600);
                                    $menit = floor(($diff_sek % 3600) / 60);
                                    $sla_html = '<span class="sla-badge-warning"><i class="fa-solid fa-hourglass-half"></i> ' . $jam . 'j ' . $menit . 'm lagi</span><br><small class="text-muted" style="font-size:0.7rem;">' . $tgl_format . '</small>';
                                    $pct = max(5, min(100, round(($diff_sek / 86400) * 100)));
                                    $sla_bar_html = '<div class="sla-progress-wrap"><div style="background:#dee2e6;height:4px;border-radius:2px;"><div class="sla-progress-bar-inner" style="width:'.$pct.'%;background:#f7971e;"></div></div></div>';
                                } else {
                                    $hari = floor($diff_sek / 86400);
                                    $sla_html = '<span class="sla-badge-ok"><i class="fa-regular fa-clock"></i> ' . $hari . ' hari lagi</span><br><small class="text-muted" style="font-size:0.7rem;">' . $tgl_format . '</small>';
                                }
                            }
                        ?>
                        <tr id="row-<?= $data['id'] ?>" class="<?= ($data['status'] == 'Selesai') ? 'table-light text-muted' : '' ?>">
                            <td class="text-center ps-3">
                                <?php if($data['status'] != 'Selesai'): ?>
                                    <input class="form-check-input border-secondary bulk-item" type="checkbox" value="<?= $data['id']; ?>" onclick="updateBulkUI(); highlightRow(this, 'row-<?= $data['id'] ?>')">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark font-monospace" style="font-size:0.9rem;"><?= $data['nomor_surat']; ?></div>
                                <div class="text-muted small">Asal: <?= $data['pengirim']; ?></div>
                                <?php if(!empty($data['perihal'])): ?>
                                <div class="text-dark small text-truncate" style="max-width:180px;" title="<?= htmlspecialchars($data['perihal']) ?>"><?= htmlspecialchars($data['perihal']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold" style="font-size:0.9rem;"><?= $data['nama_pengirim']; ?></div>
                                <?php if(!empty($data['nip'])): ?><div class="text-muted small">NIP: <?= $data['nip'] ?></div><?php endif; ?>
                            </td>
                            <td>
                                <div class="small bg-light p-2 rounded border border-warning border-start-0 border-end-0 border-bottom-0 border-3">
                                    <?= $data['instruksi']; ?>
                                </div>
                            </td>
                            <td>
                                <?= $sla_html; ?>
                                <?= $sla_bar_html; ?>
                            </td>
                            <td><span class="badge <?= $warna_status; ?>"><?= $data['status']; ?></span></td>
                            <td class="text-center">
                                <!-- [BARU] Tombol Detail/History -->
                                <button class="btn btn-sm btn-outline-secondary mb-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#modalDetail<?= $data['id']; ?>" 
                                        title="Lihat Detail & Riwayat Surat">
                                    <i class="fa-solid fa-timeline"></i> Detail
                                </button>

                                <button class="btn btn-sm btn-outline-info mb-1" data-bs-toggle="modal" data-bs-target="#modalFile<?= $data['id']; ?>" title="Lihat Dokumen & Lampiran">
                                    <i class="fa-solid fa-folder-open"></i> File
                                </button>

                                <?php if($data['status'] != 'Selesai'): ?>
                                    <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalTindakLanjut<?= $data['id']; ?>" title="Tindak Lanjut"><i class="fa-solid <?= ($role_sekarang == 'Guru') ? 'fa-check-double' : 'fa-check-to-slot'; ?>"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none p-3 bg-light" style="max-height: 75vh; overflow-y: auto;">
                <div class="form-check mb-3 ms-1">
                    <input class="form-check-input border-secondary" type="checkbox" id="checkAllBulkMobile" onclick="toggleAllBulk(this)">
                    <label class="form-check-label fw-bold text-secondary small" for="checkAllBulkMobile">Pilih Semua Disposisi Aktif</label>
                </div>

                <?php foreach ($data_disposisi as $data): 
                    $warna_status = match($data['status']) {
                        'Menunggu' => 'bg-danger',
                        'Dibaca' => 'bg-info text-dark',
                        'Selesai' => 'bg-success',
                        default => 'bg-secondary'
                    };
                    // ── [BARU] SLA Mobile ──
                    $sla_text_mob = '<span class="text-muted">Tidak ada</span>';
                    if (!empty($data['batas_waktu_sla'])) {
                        $diff_mob = strtotime($data['batas_waktu_sla']) - time();
                        if ($data['status'] == 'Selesai') {
                            $sla_text_mob = '<span class="text-success fw-bold">'.date('d/m/Y H:i', strtotime($data['batas_waktu_sla'])).'</span>';
                        } elseif ($diff_mob < 0) {
                            $sla_text_mob = '<span class="text-danger fw-bold"><i class="fa-solid fa-fire"></i> LEWAT SLA</span>';
                        } elseif ($diff_mob <= 86400) {
                            $jam = floor($diff_mob/3600);
                            $sla_text_mob = '<span class="text-warning fw-bold"><i class="fa-solid fa-hourglass-half"></i> '.$jam.'j lagi</span>';
                        } else {
                            $sla_text_mob = '<span class="text-success fw-bold">'.date('d/m/Y H:i', strtotime($data['batas_waktu_sla'])).'</span>';
                        }
                    }
                ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3 <?php if($data['status'] != 'Selesai') echo 'border-start border-4 border-warning'; ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <div class="d-flex align-items-center">
                                <?php if($data['status'] != 'Selesai'): ?>
                                    <input class="form-check-input border-secondary bulk-item me-2 mt-0" type="checkbox" value="<?= $data['id']; ?>" onclick="updateBulkUI()">
                                <?php endif; ?>
                                <span class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;"><?= $data['nomor_surat']; ?></span>
                            </div>
                            <span class="badge <?= $warna_status; ?>" style="font-size: 0.7rem;"><?= $data['status']; ?></span>
                        </div>
                        
                        <div class="mb-2">
                            <div class="small text-muted"><i class="fa-solid fa-user me-1"></i> Dari: <strong><?= $data['nama_pengirim']; ?></strong></div>
                            <div class="small text-muted mb-2"><i class="fa-regular fa-clock me-1"></i> SLA: <?= $sla_text_mob; ?></div>
                        </div>
                        <div class="small bg-light p-2 rounded border border-warning border-start-0 border-end-0 border-bottom-0 border-3 mb-2 fst-italic">
                            "<?= $data['instruksi']; ?>"
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-2 flex-wrap">
                            <!-- [BARU] Tombol Detail Mobile -->
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $data['id']; ?>" title="Detail & Riwayat">
                                <i class="fa-solid fa-timeline"></i> Detail
                            </button>

                            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#modalFile<?= $data['id']; ?>" title="Lihat Dokumen & Lampiran">
                                <i class="fa-solid fa-folder-open"></i> File
                            </button>

                            <?php if($data['status'] != 'Selesai'): ?>
                                <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalTindakLanjut<?= $data['id']; ?>"><i class="fa-solid <?= ($role_sekarang == 'Guru') ? 'fa-check-double' : 'fa-check-to-slot'; ?> me-1"></i> Tindak Lanjut</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php foreach ($data_disposisi as $data): ?>

    <!-- ======================================================
         [BARU] MODAL DETAIL & HISTORY SURAT
         ====================================================== -->
    <div class="modal fade" id="modalDetail<?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0">
                            <i class="fa-solid fa-magnifying-glass-chart me-2 text-warning"></i> Detail & Riwayat Surat
                        </h5>
                        <small class="text-white-50 font-monospace"><?= $data['nomor_surat'] ?></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">

                    <!-- Nav Tabs -->
                    <ul class="nav nav-tabs nav-tabs-detail border-bottom px-3 pt-2" id="tabDetail<?= $data['id'] ?>" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-rincian-<?= $data['id'] ?>" type="button">
                                <i class="fa-solid fa-file-lines me-1"></i> Rincian Surat
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-aktivitas-<?= $data['id'] ?>" type="button">
                                <i class="fa-solid fa-timeline me-1"></i> Riwayat Aktivitas
                            </button>
                        </li>
                        <?php if($role_sekarang == 'Kepala Sekolah'): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sla-<?= $data['id'] ?>" type="button">
                                <i class="fa-solid fa-gauge-high me-1"></i> Info SLA
                            </button>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content p-3">

                        <!-- TAB 1: RINCIAN SURAT -->
                        <div class="tab-pane fade show active" id="tab-rincian-<?= $data['id'] ?>">
                            <div class="detail-info-grid mb-3">
                                <div class="detail-info-item">
                                    <div class="label"><i class="fa-solid fa-hashtag me-1"></i>Nomor Surat</div>
                                    <div class="value font-monospace" style="font-size:0.82rem;"><?= htmlspecialchars($data['nomor_surat']) ?></div>
                                </div>
                                <div class="detail-info-item">
                                    <div class="label"><i class="fa-solid fa-circle-dot me-1"></i>Status Disposisi</div>
                                    <div class="value">
                                        <?php
                                        $warna_detail = match($data['status']) {
                                            'Menunggu' => 'bg-danger',
                                            'Dibaca'   => 'bg-info text-dark',
                                            'Selesai'  => 'bg-success',
                                            default    => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $warna_detail ?> rounded-pill"><?= $data['status'] ?></span>
                                    </div>
                                </div>
                                <div class="detail-info-item">
                                    <div class="label"><i class="fa-solid fa-building me-1"></i>Asal Pengirim</div>
                                    <div class="value"><?= htmlspecialchars($data['pengirim']) ?></div>
                                </div>
                                <div class="detail-info-item">
                                    <div class="label"><i class="fa-solid fa-user me-1"></i>Dikirim Oleh</div>
                                    <div class="value"><?= htmlspecialchars($data['nama_pengirim']) ?></div>
                                </div>
                                <?php if(!empty($data['perihal'])): ?>
                                <div class="detail-info-item" style="grid-column: 1 / -1;">
                                    <div class="label"><i class="fa-solid fa-tag me-1"></i>Perihal / Subjek</div>
                                    <div class="value"><?= htmlspecialchars($data['perihal']) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($data['tanggal_surat'])): ?>
                                <div class="detail-info-item">
                                    <div class="label"><i class="fa-regular fa-calendar me-1"></i>Tanggal Surat</div>
                                    <div class="value"><?= date('d/m/Y', strtotime($data['tanggal_surat'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($data['tanggal_diterima'])): ?>
                                <div class="detail-info-item">
                                    <div class="label"><i class="fa-solid fa-inbox me-1"></i>Tanggal Diterima</div>
                                    <div class="value"><?= date('d/m/Y', strtotime($data['tanggal_diterima'])) ?></div>
                                </div>
                                <?php endif; ?>
                                <?php if(!empty($data['batas_waktu_sla'])): ?>
                                <div class="detail-info-item">
                                    <div class="label"><i class="fa-regular fa-clock me-1"></i>Batas Waktu SLA</div>
                                    <div class="value text-danger"><?= date('d/m/Y H:i', strtotime($data['batas_waktu_sla'])) ?></div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Instruksi Disposisi -->
                            <div class="mb-3">
                                <div class="fw-bold text-secondary small mb-1"><i class="fa-solid fa-message-lines me-1"></i> INSTRUKSI DISPOSISI</div>
                                <div class="bg-warning bg-opacity-10 border border-warning border-opacity-50 rounded p-3" style="font-size:0.9rem;">
                                    <i class="fa-solid fa-quote-left text-warning me-1"></i>
                                    <?= htmlspecialchars($data['instruksi']) ?>
                                    <i class="fa-solid fa-quote-right text-warning ms-1"></i>
                                </div>
                            </div>

                            <!-- Laporan Tindak Lanjut jika sudah selesai -->
                            <?php if(!empty($data['laporan_tindak_lanjut'])): ?>
                            <div class="mb-3">
                                <div class="fw-bold text-secondary small mb-1"><i class="fa-solid fa-clipboard-check me-1"></i> LAPORAN TINDAK LANJUT</div>
                                <div class="bg-success bg-opacity-10 border border-success border-opacity-50 rounded p-3" style="font-size:0.9rem;">
                                    <?= nl2br(htmlspecialchars($data['laporan_tindak_lanjut'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- TAB 2: RIWAYAT AKTIVITAS / TIMELINE -->
                        <div class="tab-pane fade" id="tab-aktivitas-<?= $data['id'] ?>">
                            <?php
                            // ── Ambil seluruh chain disposisi untuk surat ini ──
                            $chain_all = $history_disposisi[$data['surat_id']] ?? [];

                            if ($role_sekarang == 'Kepala Sekolah'):
                                // POV Kepala Sekolah: tampilkan semua aktivitas di surat ini
                                // (semua yang dia kirimkan + semua yang diteruskan berjenjang)
                            ?>
                            <div class="pov-badge-kepsek mb-3 d-inline-block">
                                <i class="fa-solid fa-user-tie me-1"></i> Riwayat sebagai Kepala Sekolah
                            </div>
                            <div class="timeline-activity">

                                <!-- Surat Masuk Diterima di Sistem -->
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-masuk"><i class="fa-solid fa-envelope-open"></i></div>
                                    <div class="timeline-card timeline-card-kepsek">
                                        <div class="fw-bold" style="font-size:0.85rem;">Surat Diterima di Sistem</div>
                                        <div class="text-muted" style="font-size:0.78rem;">
                                            Nomor: <span class="font-monospace"><?= $data['nomor_surat'] ?></span> dari <?= htmlspecialchars($data['pengirim']) ?>
                                            <?php if(!empty($data['tanggal_diterima'])): ?>
                                                &bull; <?= date('d M Y', strtotime($data['tanggal_diterima'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <?php
                                // Deduplicate: 1 baris per pasangan (dari_user_id + ke_user_id).
                                // Overwrite terus sehingga yang tersisa adalah baris terakhir (status terbaru).
                                $chain_unik = [];
                                foreach ($chain_all as $ch) {
                                    $key = $ch['dari_user_id'] . '_' . $ch['ke_user_id'];
                                    $chain_unik[$key] = $ch;
                                }
                                // Tampilkan 1 card per penerima, status sudah ter-embed di card.
                                foreach ($chain_unik as $ch):
                                    // Tentukan warna dot & label status berdasarkan status entri
                                    if ($ch['status'] == 'Selesai') {
                                        $dot_class  = 'timeline-dot-selesai';
                                        $dot_icon   = 'fa-check';
                                        $status_badge = '<span class="badge bg-success rounded-pill ms-1" style="font-size:0.68rem;"><i class="fa-solid fa-check me-1"></i>Selesai</span>';
                                    } elseif ($ch['status'] == 'Dibaca') {
                                        $dot_class  = 'timeline-dot-baca';
                                        $dot_icon   = 'fa-eye';
                                        $status_badge = '<span class="badge bg-info text-dark rounded-pill ms-1" style="font-size:0.68rem;"><i class="fa-solid fa-eye me-1"></i>Dibaca</span>';
                                    } else {
                                        $dot_class  = 'timeline-dot-menunggu';
                                        $dot_icon   = 'fa-hourglass-half';
                                        $status_badge = '<span class="badge bg-danger rounded-pill ms-1" style="font-size:0.68rem;"><i class="fa-solid fa-hourglass-half me-1"></i>Menunggu</span>';
                                    }
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot <?= $dot_class ?>"><i class="fa-solid <?= $dot_icon ?>"></i></div>
                                    <div class="timeline-card timeline-card-kepsek">
                                        <div class="fw-bold d-flex align-items-center flex-wrap gap-1" style="font-size:0.85rem;">
                                            <span>Disposisi ke <?= htmlspecialchars($ch['nama_ke']) ?></span>
                                            <span class="badge bg-primary rounded-pill" style="font-size:0.68rem;"><?= htmlspecialchars($ch['role_ke']) ?></span>
                                            <?= $status_badge ?>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size:0.78rem;">
                                            Dari <strong><?= htmlspecialchars($ch['nama_dari']) ?></strong>
                                            &bull; "<em><?= htmlspecialchars($ch['instruksi']) ?></em>"
                                            <?php if(!empty($ch['batas_waktu_sla'])): ?>
                                                &bull; SLA: <?= date('d M Y H:i', strtotime($ch['batas_waktu_sla'])) ?>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($ch['status'] == 'Selesai' && !empty($ch['laporan_tindak_lanjut'])): ?>
                                        <div class="mt-1 p-2 bg-success bg-opacity-10 rounded" style="font-size:0.78rem;">
                                            <i class="fa-solid fa-clipboard-check text-success me-1"></i>
                                            <?= htmlspecialchars(mb_substr($ch['laporan_tindak_lanjut'], 0, 120)) ?>...
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                            </div>

                            <?php else:
                                // POV Penerima (Guru / Waka / TU)
                                // Tampilkan hanya disposisi yang masuk KE user ini,
                                // dan jika Waka: tampilkan juga yang dia teruskan ke bawahan
                                $disposisi_ke_saya = array_filter($chain_all, function($h) use ($user_id_sekarang) {
                                    return $h['ke_user_id'] == $user_id_sekarang;
                                });
                                $disposisi_dari_saya = array_filter($chain_all, function($h) use ($user_id_sekarang) {
                                    return $h['dari_user_id'] == $user_id_sekarang;
                                });
                            ?>
                            <div class="pov-badge-penerima mb-3 d-inline-block">
                                <i class="fa-solid fa-inbox me-1"></i> Riwayat Disposisi Anda
                            </div>
                            <div class="timeline-activity">

                                <?php foreach ($disposisi_ke_saya as $ch): ?>

                                <!-- Disposisi Diterima dari Atasan -->
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-masuk"><i class="fa-solid fa-inbox"></i></div>
                                    <div class="timeline-card timeline-card-penerima">
                                        <div class="fw-bold" style="font-size:0.85rem;">Disposisi Diterima dari <?= htmlspecialchars($ch['nama_dari']) ?> <span class="badge bg-secondary rounded-pill ms-1" style="font-size:0.68rem;"><?= htmlspecialchars($ch['role_dari']) ?></span></div>
                                        <div class="text-muted" style="font-size:0.78rem;">
                                            Surat: <span class="font-monospace"><?= $data['nomor_surat'] ?></span>
                                            <?php if(!empty($ch['batas_waktu_sla'])): ?>
                                                &bull; SLA: <?= date('d M Y H:i', strtotime($ch['batas_waktu_sla'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Instruksi -->
                                <div class="timeline-item">
                                    <div class="timeline-dot" style="background:#6f42c1;"><i class="fa-solid fa-comment-dots"></i></div>
                                    <div class="timeline-card timeline-card-penerima">
                                        <div class="fw-bold" style="font-size:0.85rem;">Instruksi</div>
                                        <div class="text-muted" style="font-size:0.78rem;">"<em><?= htmlspecialchars($ch['instruksi']) ?></em>"</div>
                                    </div>
                                </div>

                                <!-- Status Dibaca -->
                                <?php if(in_array($ch['status'], ['Dibaca', 'Selesai'])): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-baca"><i class="fa-solid fa-eye"></i></div>
                                    <div class="timeline-card timeline-card-penerima">
                                        <div class="fw-bold" style="font-size:0.85rem;">Sudah Dibaca</div>
                                        <div class="text-muted" style="font-size:0.78rem;">Anda telah membuka detail disposisi ini.</div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php endforeach; ?>

                                <!-- Disposisi yang Diteruskan oleh user ini (Waka) -->
                                <?php foreach ($disposisi_dari_saya as $pt): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-forward"><i class="fa-solid fa-share-nodes"></i></div>
                                    <div class="timeline-card timeline-card-penerima">
                                        <div class="fw-bold" style="font-size:0.85rem;">
                                            Diteruskan ke <?= htmlspecialchars($pt['nama_ke']) ?>
                                            <span class="pov-badge-penerima ms-1"><?= htmlspecialchars($pt['role_ke']) ?></span>
                                        </div>
                                        <div class="text-muted" style="font-size:0.78rem;">Instruksi: "<em><?= htmlspecialchars($pt['instruksi']) ?></em>"</div>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                                <!-- Status Akhir: Selesai atau Menunggu -->
                                <?php if($data['status'] == 'Selesai'): ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-selesai"><i class="fa-solid fa-check-double"></i></div>
                                    <div class="timeline-card timeline-card-penerima">
                                        <div class="fw-bold text-success" style="font-size:0.85rem;">Disposisi Diselesaikan ✓</div>
                                        <?php if(!empty($data['laporan_tindak_lanjut'])): ?>
                                        <div class="small mt-1 text-dark"><?= nl2br(htmlspecialchars($data['laporan_tindak_lanjut'])) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot timeline-dot-menunggu"><i class="fa-solid fa-pen-to-square"></i></div>
                                    <div class="timeline-card" style="border-left:3px solid #0dcaf0;">
                                        <div class="fw-bold text-info" style="font-size:0.85rem;">Menunggu Tindak Lanjut Anda</div>
                                        <div class="text-muted" style="font-size:0.78rem;">
                                            <?php if($role_sekarang == 'Guru'): ?>
                                                Tandai selesai setelah menyelesaikan tugas.
                                            <?php else: ?>
                                                Selesaikan atau teruskan disposisi ini.
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- TAB 3: INFO SLA (KHUSUS KEPALA SEKOLAH) -->
                        <?php if($role_sekarang == 'Kepala Sekolah'): ?>
                        <div class="tab-pane fade" id="tab-sla-<?= $data['id'] ?>">
                            <?php if(!empty($data['batas_waktu_sla'])): 
                                $sla_ts = strtotime($data['batas_waktu_sla']);
                                $now_ts = time();
                                $diff_sla_tab = $sla_ts - $now_ts;
                                $tgl_sla_tab = date('l, d F Y - H:i', $sla_ts);
                            ?>
                            <div class="text-center py-3">
                                <?php if($data['status'] == 'Selesai'): ?>
                                    <div class="mb-3">
                                        <i class="fa-solid fa-circle-check fa-3x text-success"></i>
                                        <div class="fw-bold text-success mt-2 fs-5">Disposisi Telah Diselesaikan</div>
                                        <div class="text-muted small">Batas SLA: <?= $tgl_sla_tab ?></div>
                                    </div>
                                <?php elseif($diff_sla_tab < 0): ?>
                                    <div class="mb-3">
                                        <i class="fa-solid fa-fire fa-3x text-danger"></i>
                                        <div class="fw-bold text-danger mt-2 fs-5">SLA Terlewat!</div>
                                        <div class="text-muted small">Batas: <?= $tgl_sla_tab ?></div>
                                    </div>
                                    <div class="alert alert-danger text-start">
                                        <strong>Tindakan diperlukan segera!</strong> Surat ini telah melewati batas waktu penyelesaian yang ditetapkan. Segera koordinasi dengan penerima disposisi.
                                    </div>
                                <?php else:
                                    $jam_sisa = floor($diff_sla_tab / 3600);
                                    $menit_sisa = floor(($diff_sla_tab % 3600) / 60);
                                    $hari_sisa = floor($diff_sla_tab / 86400);
                                    $warna_sla_gauge = $diff_sla_tab <= 86400 ? '#ff416c' : ($diff_sla_tab <= 259200 ? '#f7971e' : '#38ef7d');
                                ?>
                                    <div class="mb-3">
                                        <div style="font-size:3rem; font-weight:900; color:<?= $warna_sla_gauge ?>; line-height:1;">
                                            <?= $hari_sisa ?>
                                        </div>
                                        <div class="text-muted">hari tersisa</div>
                                        <div class="small text-secondary">(<?= $jam_sisa ?>j <?= $menit_sisa ?>m)</div>
                                    </div>
                                    <div class="text-muted small mb-3">Batas Waktu: <strong><?= $tgl_sla_tab ?></strong></div>
                                    <!-- Progress Bar -->
                                    <?php 
                                    // Asumsi SLA dibuat bersamaan dengan tanggal diterima
                                    $durasi_total = !empty($data['tanggal_diterima']) ? ($sla_ts - strtotime($data['tanggal_diterima'])) : 0;
                                    $pct_sisa = $durasi_total > 0 ? max(0, min(100, round(($diff_sla_tab / $durasi_total) * 100))) : ($diff_sla_tab > 0 ? 100 : 0);
                                    ?>
                                    <div class="mb-1 d-flex justify-content-between small">
                                        <span class="text-muted">Waktu Tersisa</span>
                                        <span class="fw-bold" style="color:<?= $warna_sla_gauge ?>"><?= $pct_sisa ?>%</span>
                                    </div>
                                    <div class="rounded" style="background:#dee2e6; height:12px; border-radius:6px; overflow:hidden;">
                                        <div style="height:12px; width:<?= $pct_sisa ?>%; background:<?= $warna_sla_gauge ?>; border-radius:6px; transition: width 0.5s;"></div>
                                    </div>
                                    <?php if($diff_sla_tab <= 86400): ?>
                                    <div class="alert alert-warning mt-3 text-start small">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        Kurang dari 24 jam! Pastikan penerima disposisi telah menyelesaikan tugas.
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fa-regular fa-clock fa-2x mb-2 d-block"></i>
                                <div>Tidak ada batas waktu SLA yang ditetapkan untuk disposisi ini.</div>
                                <div class="small mt-1">SLA dapat diatur saat memberikan disposisi dari surat masuk.</div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    </div><!-- end tab-content -->
                </div><!-- end modal-body -->
                <div class="modal-footer bg-light">
                    <?php if($data['status'] != 'Selesai'): ?>
                    <button type="button" class="btn btn-success btn-sm fw-bold" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#modalTindakLanjut<?= $data['id'] ?>">
                        <i class="fa-solid fa-check-to-slot me-1"></i> Tindak Lanjut Sekarang
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <!-- END MODAL DETAIL -->


    <!-- MODAL FILE (tidak berubah) -->
    <div class="modal fade" id="modalFile<?= $data['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-folder-open text-info me-2"></i> Dokumen & Lampiran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3"><i class="fa-solid fa-file-signature me-2"></i>Surat Utama</h6>
                
                <?php if(!empty($data['file_path'])): ?>
                    <div class="d-flex align-items-center p-2 border rounded mb-4 bg-white hover-shadow">
                        <div class="flex-grow-1 d-flex align-items-center overflow-hidden" onclick="bukaPreviewPDF('<?= $data['file_path']; ?>')" style="cursor: pointer;">
                            <i class="fa-solid fa-file-pdf text-danger fs-3 me-3"></i>
                            <div class="text-truncate">
                                <span class="d-block fw-bold text-primary">Lihat Surat Utama</span>
                                <small class="text-muted text-truncate"><?= htmlspecialchars($data['file_path']); ?></small>
                            </div>
                        </div>
                        <div class="ms-2 border-start ps-2">
                            <button type="button" 
                            onclick="bukaPDF('<?= $data['file_path']; ?>')" 
                            class="btn btn-outline-primary w-100 mb-4 text-start">
                        <i class="fa-solid fa-cloud-arrow-down fs-5"></i>
                    </button>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning small py-2"><i class="fa-solid fa-triangle-exclamation me-1"></i> File surat utama tidak tersedia.</div>
                <?php endif; ?>

                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3 mt-4"><i class="fa-solid fa-paperclip me-2"></i>Lampiran Pendukung</h6>
                
                <?php 
                $id_sm = $data['surat_id']; 
                if (isset($lampiran_surat[$id_sm]) && count($lampiran_surat[$id_sm]) > 0): 
                    foreach($lampiran_surat[$id_sm] as $lamp): 
                ?>
                    <div class="d-flex align-items-center p-2 border rounded mb-2 bg-white shadow-sm">
                        <div class="flex-grow-1 d-flex align-items-center overflow-hidden" onclick="bukaPreviewPDF('<?= $lamp['file_path']; ?>')" style="cursor: pointer;">
                            <i class="fa-solid fa-file-lines text-info fs-4 me-3"></i>
                            <div class="text-truncate">
                                <span class="d-block fw-semibold" style="font-size: 0.9rem;"><?= htmlspecialchars($lamp['nama_file']); ?></span>
                                <small class="text-muted italic" style="font-size: 0.75rem;">Lampiran PDF</small>
                            </div>
                        </div>
                        <div class="ms-2 border-start ps-2">
                            <a href="ambil_pdf.php?file=<?= urlencode($lamp['file_path']); ?>" 
                               target="_blank" 
                               class="btn btn-light btn-sm text-success">
                                <i class="fa-solid fa-download"></i>
                            </a>
                        </div>
                    </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <div class="text-center py-3">
                        <i class="fa-solid fa-box-open d-block text-muted mb-2 fs-3"></i>
                        <p class="text-muted small fst-italic">Tidak ada file lampiran tambahan.</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Tambahan CSS agar UI terasa lebih 'hidup' */
.hover-shadow:hover {
    background-color: #f8f9fa !important;
    border-color: #0d6efd !important;
    transition: all 0.2s ease-in-out;
}
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>


    <?php if($data['status'] != 'Selesai'): ?>
    <div class="modal fade" id="modalTindakLanjut<?= $data['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <form action="aksi_disposisi.php" method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold text-success"><i class="fa-solid fa-check-to-slot me-2"></i> Tindak Lanjut Surat</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="disposisi_id" value="<?= $data['id']; ?>">
                        <input type="hidden" name="surat_id" value="<?= $data['surat_id']; ?>">
                        
                        <div class="alert alert-info py-2 small mb-3 border-info">
                            <strong><i class="fa-solid fa-circle-info"></i> Instruksi Atasan:</strong><br>
                            <span class="fst-italic">"<?= htmlspecialchars($data['instruksi']); ?>"</span>
                        </div>

                        <!-- [BARU] SLA Reminder di Modal Tindak Lanjut -->
                        <?php if(!empty($data['batas_waktu_sla']) && $data['status'] != 'Selesai'): 
                            $diff_tl = strtotime($data['batas_waktu_sla']) - time();
                        ?>
                        <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded border <?= $diff_tl < 0 ? 'border-danger bg-danger bg-opacity-10' : ($diff_tl <= 86400 ? 'border-warning bg-warning bg-opacity-10' : 'border-secondary bg-light') ?>">
                            <i class="fa-regular fa-clock <?= $diff_tl < 0 ? 'text-danger' : ($diff_tl <= 86400 ? 'text-warning' : 'text-secondary') ?>"></i>
                            <div class="small">
                                <strong>Batas SLA:</strong> <?= date('d/m/Y H:i', strtotime($data['batas_waktu_sla'])) ?>
                                <?php if($diff_tl < 0): ?>
                                    <span class="badge bg-danger ms-1">LEWAT BATAS</span>
                                <?php elseif($diff_tl <= 86400): ?>
                                    <span class="badge bg-warning text-dark ms-1">SEGERA!</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if($role_sekarang == 'Guru'): ?>
                            <input type="hidden" name="jenis_tindakan" value="selesai">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Laporan Hasil Pekerjaan <span class="text-danger">*</span></label>
                                <textarea class="form-control border-success shadow-sm" name="laporan_tindak_lanjut" rows="4" placeholder="Ketik hasil pekerjaan Anda di sini..." required></textarea>
                            </div>
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Pilih Aksi / Tindakan <span class="text-danger">*</span></label>
                                <select class="form-select border-success shadow-sm" name="jenis_tindakan" id="jenis_tindakan_<?= $data['id']; ?>" required onchange="toggleTeruskan(<?= $data['id']; ?>)">
                                    <option value="">-- Pilih Keputusan --</option>
                                    <option value="selesai">✅ Selesaikan Surat (Arsip)</option>
                                    <option value="teruskan">➡️ Teruskan / Disposisi ke Bawahan</option>
                                </select>
                            </div>

                            <div id="div_selesai_<?= $data['id']; ?>" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Laporan Tindak Lanjut <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="laporan_tindak_lanjut" id="laporan_<?= $data['id']; ?>" rows="3" placeholder="Laporan singkat penyelesaian..."></textarea>
                                </div>
                            </div>

                            <div id="div_teruskan_<?= $data['id']; ?>" style="display: none;">
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label fw-bold mb-0">Teruskan Kepada <span class="text-danger">*</span></label>
                                        <?php if(!empty($daftar_guru)): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="pilihSemuaGuru('<?= $data['id']; ?>')">
                                            <i class="fa-solid fa-users me-1"></i> Pilih Semua Guru
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="border rounded p-3 bg-white shadow-sm" style="max-height: 220px; overflow-y: auto;" id="list_penerima_<?= $data['id']; ?>">
                                        <?php
                                        // Kelompokkan pegawai per role agar lebih mudah dibaca
                                        $grouped = [];
                                        foreach ($daftar_pegawai as $u) {
                                            $grouped[$u['nama_role']][] = $u;
                                        }
                                        foreach ($grouped as $role_nama => $anggota):
                                        ?>
                                        <div class="mb-2">
                                            <div class="text-uppercase fw-bold text-secondary border-bottom pb-1 mb-1" style="font-size:0.7rem; letter-spacing:0.5px;">
                                                <i class="fa-solid fa-layer-group me-1"></i><?= htmlspecialchars($role_nama) ?>
                                            </div>
                                            <?php foreach($anggota as $u): ?>
                                            <div class="form-check">
                                                <input class="form-check-input user_cb_disp_<?= $data['id']; ?> <?= ($u['nama_role'] == 'Guru') ? 'cb_guru_'.$data['id'] : ''; ?>"
                                                       type="checkbox"
                                                       name="ke_user_id_baru[]"
                                                       value="<?= $u['id']; ?>"
                                                       id="user_<?= $u['id']; ?>_disp_<?= $data['id']; ?>">
                                                <label class="form-check-label" for="user_<?= $u['id']; ?>_disp_<?= $data['id']; ?>">
                                                    <?= htmlspecialchars($u['nama_lengkap']); ?>
                                                    <span class="text-muted small">-(<?= $u['nama_role']; ?>)-</span>
                                                </label>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Instruksi Tambahan</label>
                                    <textarea class="form-control" name="instruksi_baru" rows="2" placeholder="Contoh: Tolong segera dikerjakan..."></textarea>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tindak_lanjut" class="btn btn-success fw-bold"><i class="fa-solid fa-save me-1"></i> Simpan Keputusan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; ?>

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

<!-- [BARU] MODAL KONFIRMASI BULK ACTION -->
<div class="modal fade" id="modalBulkConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning bg-opacity-25 border-warning">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Konfirmasi Tindakan Massal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Anda akan menandai <strong id="confirmBulkCount">0</strong> surat disposisi sebagai <strong class="text-success">Selesai</strong>.</p>
                <div class="alert alert-info small py-2 mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Tindakan ini tidak bisa dibatalkan. Surat akan diarsipkan secara otomatis.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success fw-bold" id="btnConfirmBulkExec">
                    <i class="fa-solid fa-check-double me-1"></i> Ya, Selesaikan Semua
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function bukaPreviewPDF(namaFile) {
    // Gunakan URL lengkap agar Iframe tidak tersesat
    var viewerUrl = "https://simpers.42web.io/vendor/pdfjs/web/viewer.html";
    var proxyUrl = "https://simpers.42web.io/ambil_pdf.php?file=" + encodeURIComponent(namaFile);
    
    var finalUrl = viewerUrl + "?file=" + encodeURIComponent(proxyUrl);
    
    console.log("Mencoba memuat URL ini:", finalUrl);
    
    var frame = document.getElementById('framePDF');
    if (frame) {
        // Tambahkan cache-breaker agar browser tidak mengambil file lama
        frame.src = finalUrl + "&v=" + new Date().getTime();
        
        var modalPDF = new bootstrap.Modal(document.getElementById('modalPreviewPDF'));
        modalPDF.show();
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
// 1. Tampilkan Input Dinamis Berdasarkan Dropdown
function toggleTeruskan(id) {
    var jenis = document.getElementById('jenis_tindakan_' + id).value;
    var divTeruskan = document.getElementById('div_teruskan_' + id);
    var divSelesai = document.getElementById('div_selesai_' + id);
    var laporanInput = document.getElementById('laporan_' + id);

    if (jenis === 'teruskan') {
        divTeruskan.style.display = 'block';
        divSelesai.style.display = 'none';
        if(laporanInput) laporanInput.removeAttribute('required'); 
    } else if (jenis === 'selesai') {
        divTeruskan.style.display = 'none';
        divSelesai.style.display = 'block';
        if(laporanInput) laporanInput.setAttribute('required', 'required'); 
    } else {
        divTeruskan.style.display = 'none';
        divSelesai.style.display = 'none';
        if(laporanInput) laporanInput.removeAttribute('required');
    }
}

// ================= LOGIC BULK ACTION (TINDAKAN MASSAL) =================
// 2. Centang Semua (Sinkronisasi Desktop & Mobile)
function toggleAllBulk(source) {
    let checkboxes = document.querySelectorAll('.bulk-item');
    checkboxes.forEach(function(cb) {
        cb.checked = source.checked;
    });
    
    // Sinkronisasi checkbox header
    document.getElementById('checkAllBulk').checked = source.checked;
    let mobileCheckAll = document.getElementById('checkAllBulkMobile');
    if(mobileCheckAll) mobileCheckAll.checked = source.checked;

    updateBulkUI();
}

// 3. Memperbarui Tampilan Panel & Hitung Ulang Unique Value
function updateBulkUI() {
    let checkedBoxes = document.querySelectorAll('.bulk-item:checked');
    let container = document.getElementById('bulkActionContainer');
    
    // Karena kita pakai 2 struktur HTML (Mobile/Desktop), ID bisa terhitung dua kali.
    // Kita gunakan JavaScript Set untuk menyaring nilai unik.
    let uniqueIds = new Set();
    checkedBoxes.forEach(function(cb) {
        uniqueIds.add(cb.value);
    });

    document.getElementById('selectedCount').innerText = uniqueIds.size;

    if (uniqueIds.size > 0) {
        container.style.setProperty('display', 'flex', 'important');
    } else {
        container.style.setProperty('display', 'none', 'important');
        document.getElementById('checkAllBulk').checked = false;
        let mobileCheckAll = document.getElementById('checkAllBulkMobile');
        if(mobileCheckAll) mobileCheckAll.checked = false;
    }
}

// [BARU] 4. Highlight baris ketika di-centang
function highlightRow(checkbox, rowId) {
    var row = document.getElementById(rowId);
    if (row) {
        if (checkbox.checked) {
            row.classList.add('row-bulk-selected');
        } else {
            row.classList.remove('row-bulk-selected');
        }
    }
}

// [BARU] 5. Eksekusi Bulk Action dengan Konfirmasi Modal
function executeBulkAction() {
    let checkedBoxes = document.querySelectorAll('.bulk-item:checked');
    let uniqueIds = new Set();
    checkedBoxes.forEach(function(cb) { uniqueIds.add(cb.value); });

    if (uniqueIds.size === 0) {
        alert('Pilih minimal satu disposisi terlebih dahulu.');
        return;
    }

    // Tampilkan modal konfirmasi
    document.getElementById('confirmBulkCount').innerText = uniqueIds.size;
    var confirmModal = new bootstrap.Modal(document.getElementById('modalBulkConfirm'));
    confirmModal.show();

    // Tombol konfirmasi eksekusi form
    document.getElementById('btnConfirmBulkExec').onclick = function() {
        var jenis = document.getElementById('bulkActionSelect').value;
        document.getElementById('inputBulkJenis').value = jenis;
        document.getElementById('inputBulkIds').value = Array.from(uniqueIds).join(',');
        confirmModal.hide();
        document.getElementById('formBulkAction').submit();
    };
}

// [BARU] Pilih Semua Guru dalam modal teruskan
function pilihSemuaGuru(dispId) {
    var checkboxesGuru = document.querySelectorAll('.cb_guru_' + dispId);
    // Cek apakah semua sudah tercentang
    var semuaChecked = Array.from(checkboxesGuru).every(function(cb) { return cb.checked; });
    checkboxesGuru.forEach(function(cb) {
        cb.checked = !semuaChecked; // toggle: kalau semua sudah centang → uncheck, kalau belum → check semua
    });
    // Update label tombol
    var btn = document.querySelector('[onclick="pilihSemuaGuru(\'' + dispId + '\')"]');
    if (btn) {
        if (!semuaChecked) {
            btn.innerHTML = '<i class="fa-solid fa-users-slash me-1"></i> Batal Pilih Semua Guru';
            btn.classList.replace('btn-outline-primary', 'btn-outline-danger');
        } else {
            btn.innerHTML = '<i class="fa-solid fa-users me-1"></i> Pilih Semua Guru';
            btn.classList.replace('btn-outline-danger', 'btn-outline-primary');
        }
    }
}

function switchPOV(showId, hideId, btn) {
    document.getElementById(showId).style.display = 'block';
    document.getElementById(hideId).style.display = 'none';

    // Update style tombol
    var parent = btn.closest('ul');
    parent.querySelectorAll('.nav-link').forEach(function(b) {
        b.style.background = '#f8f9fa';
        b.style.color = '#6c757d';
        b.style.borderColor = '#dee2e6';
        b.classList.remove('active');
    });
    btn.style.background = '#e7f1ff';
    btn.style.color = '#0d6efd';
    btn.style.borderColor = '#b6d4fe';
    btn.classList.add('active');
}

// 4. Eksekusi Kirim Form (fungsi lama dipertahankan untuk kompatibilitas)
function bukaPreviewPDF(namaFile) {
    // Kita panggil file PHP bantuan untuk mengambil data PDF
    var viewerUrl = "../vendor/pdfjs/web/viewer.html";
    
    // Kita buat URL yang mengarah ke file PDF kita melalui proxy PHP agar tidak diblokir
    var fileUrl = window.location.origin + "/uploads/surat_masuk/" + namaFile;
    
    var finalUrl = viewerUrl + "?file=" + encodeURIComponent(fileUrl);
    
    var frame = document.getElementById('framePDF');
    if (frame) {
        frame.src = finalUrl;
        var modalPDF = new bootstrap.Modal(document.getElementById('modalPreviewPDF'));
        modalPDF.show();
    }
}
</script>

<?php include '../layouts/footer.php'; ?>