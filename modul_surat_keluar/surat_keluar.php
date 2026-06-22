<?php
session_start();

// Perhatikan penambahan '../' karena file ini ada di dalam folder modul_surat_keluar/
include '../config/koneksi.php';

// Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

$user_id_sekarang = $_SESSION['user_id'];
$role_sekarang = $_SESSION['nama_role'];

// ========================================================
// 1. TANGKAP FILTER
// ========================================================
$show_mode = isset($_GET['show']) ? $_GET['show'] : '';
$limit_sql = "";

if ($show_mode !== 'all') {
    $limit_sql = "LIMIT 20";
}

$filter_bulan = isset($_GET['filter_bulan']) ? mysqli_real_escape_string($koneksi, $_GET['filter_bulan']) : '';
$filter_klasifikasi = isset($_GET['filter_klasifikasi']) ? mysqli_real_escape_string($koneksi, $_GET['filter_klasifikasi']) : '';
$filter_status = isset($_GET['filter_status']) ? mysqli_real_escape_string($koneksi, $_GET['filter_status']) : '';

$kondisi_where = "sk.deleted_at IS NULL"; 

if ($role_sekarang != 'Admin_TU' && $role_sekarang != 'Kepala_Sekolah') {
    // Jika Guru, TAMBAHKAN filter paksa agar hanya melihat miliknya sendiri
    $kondisi_where .= " AND sk.draft_by = '$user_id_sekarang'";
}

if ($filter_bulan != '') {
    $kondisi_where .= " AND MONTH(sk.created_at) = '$filter_bulan'";
}
if ($filter_klasifikasi != '') {
    $kondisi_where .= " AND sk.klasifikasi = '$filter_klasifikasi'";
}
if ($filter_status != '') {
    $kondisi_where .= " AND sk.status_workflow = '$filter_status'";
}

// ========================================================
// 2. EKSEKUSI KUERI UTAMA KE ARRAY
// ========================================================
$sql = "SELECT sk.*, u.nama_lengkap AS pembuat 
        FROM surat_keluar sk
        JOIN users u ON sk.draft_by = u.id
        WHERE $kondisi_where
        ORDER BY sk.id DESC
        $limit_sql";
        
$query = mysqli_query($koneksi, $sql);
$data_surat = [];
$surat_ids = []; // Menyimpan ID surat untuk query log

while ($row = mysqli_fetch_array($query)) {
    $data_surat[] = $row;
    $surat_ids[] = $row['id'];
}

// ========================================================
// 3. EKSEKUSI KUERI AUDIT LOG (OPTIMASI N+1 QUERY)
// ========================================================
$audit_logs = [];
if (!empty($surat_ids)) {
    $ids_str = implode(',', $surat_ids);
    $q_log = mysqli_query($koneksi, "
        SELECT a.action, a.created_at, a.record_id, u.nama_lengkap 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        WHERE a.table_name = 'surat_keluar' AND a.record_id IN ($ids_str) 
        ORDER BY a.created_at ASC
    ");
    while($log = mysqli_fetch_assoc($q_log)){
        $audit_logs[$log['record_id']][] = $log; // Kelompokkan berdasarkan ID Surat
    }
}

include '../layouts/header.php'; 
?>

<style>
.timeline-track { position: relative; padding-left: 30px; margin-bottom: 20px; }
.timeline-track::before { content: ''; position: absolute; top: 0; bottom: 0; left: 11px; width: 2px; background: #e9ecef; }
.timeline-item { position: relative; margin-bottom: 20px; }
.timeline-item::before { content: ''; position: absolute; top: 5px; left: -24px; width: 12px; height: 12px; border-radius: 50%; background: #17a2b8; border: 2px solid #fff; box-shadow: 0 0 0 2px #17a2b8; }
.timeline-date { font-size: 0.85em; color: #6c757d; font-weight: bold; }
.timeline-content { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-paper-plane me-2 text-primary"></i> Data Surat Keluar</h4>
    
    <a href="ambil_nomor.php" class="btn btn-primary fw-bold shadow-sm">
        <i class="fa-solid fa-ticket me-1"></i> Ambil Nomor Baru
    </a>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3 bg-light border-bottom">
        <form method="GET" action="surat_keluar.php" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="filter_bulan" class="form-select form-select-sm">
                    <option value="">-- Semua Bulan --</option>
                    <?php
                    $nama_bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                    for ($i = 1; $i <= 12; $i++) {
                        $selected = ($filter_bulan == $i) ? 'selected' : '';
                        echo "<option value='$i' $selected>".$nama_bulan[$i-1]."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-3">
                <select name="filter_klasifikasi" class="form-select form-select-sm">
                    <option value="">-- Semua Klasifikasi --</option>
                    <?php
                    $q_klas_filter = mysqli_query($koneksi, "SELECT * FROM klasifikasi_surat ORDER BY kode ASC");
                    while ($kf = mysqli_fetch_array($q_klas_filter)) {
                        $selected = ($filter_klasifikasi == $kf['kode']) ? 'selected' : '';
                        echo "<option value='{$kf['kode']}' $selected>{$kf['kode']} - {$kf['keterangan']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <select name="filter_status" class="form-select form-select-sm">
                    <option value="">-- Semua Status --</option>
                    <?php
                    $status_list = ['Dialokasikan', 'Draft', 'Review', 'Revisi', 'Approved', 'Terkirim'];
                    foreach ($status_list as $st) {
                        $selected = ($filter_status == $st) ? 'selected' : '';
                        echo "<option value='$st' $selected>$st</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-2 d-grid gap-1 d-md-flex">
                <button type="submit" class="btn btn-sm btn-secondary flex-grow-1" title="Terapkan Filter"><i class="fa-solid fa-filter"></i></button>
                <a href="surat_keluar.php" class="btn btn-sm btn-outline-danger flex-grow-1" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i></a>
            </div>
        </form>
    </div>
</div>

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
            <div class='text-center py-5 text-muted'>
                <i class='fa-solid fa-folder-open fs-1 d-block mb-3 text-light'></i> 
                Belum ada data surat keluar atau pengajuan.
            </div>
        <?php else: ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="ps-3">No</th>
                            <th width="20%">No. Surat / Tgl Keluar</th>
                            <th width="25%">Perihal & Tujuan</th>
                            <th width="15%">Pembuat</th>
                            <th width="15%">Status</th>
                            <th width="20%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($data_surat as $data): 
                            $warna_status = match($data['status_workflow']) {
                                'Dialokasikan' => 'bg-info text-dark',
                                'Draft' => 'bg-secondary',
                                'Review' => 'bg-warning text-dark',
                                'Revisi' => 'bg-danger',
                                'Approved' => 'bg-success',
                                'Terkirim' => 'bg-primary',
                                default => 'bg-light text-dark'
                            };
                            $tgl_keluar = !empty($data['tanggal_keluar']) ? date('d/m/Y', strtotime($data['tanggal_keluar'])) : '<span class="text-muted small fst-italic">Belum di-set</span>';
                        ?>
                        <tr>
                            <td class="ps-3"><?= $no++; ?></td>
                            <td>
                                <div class="fw-bold text-dark font-monospace" style="font-size:0.9rem;"><?= $data['nomor_surat'] ?: '<span class="text-muted fst-italic">Draft/Belum ada</span>'; ?></div>
                                <div class="small text-muted"><i class="fa-regular fa-calendar me-1"></i> <?= $tgl_keluar; ?></div>
                            </td>
                            <td>
                                <div class="fw-bold" style="font-size:0.95rem;"><?= $data['perihal']; ?></div>
                                <div class="small text-muted">Tujuan: <?= $data['tujuan']; ?></div>
                                <?php if($data['status_workflow'] == 'Revisi' && !empty($data['catatan_revisi'])): ?>
                                    <div class="alert alert-danger mt-2 p-2 small mb-0 border-danger border-start border-4">
                                        <strong>Catatan:</strong> <?= htmlspecialchars($data['catatan_revisi']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><i class="fa-solid fa-user me-1"></i> <?= $data['pembuat']; ?></td>
                            <td><span class="badge <?= $warna_status; ?> px-2 py-1"><?= $data['status_workflow']; ?></span></td>
                            <td class="text-center">
                                <?php if(!empty($data['file_path'])): ?>
                                <button type="button" 
                            onclick="bukaPreviewPDF('<?= $data['file_path']; ?>')" 
                            class="btn btn-sm btn-outline-info" title="Lihat Dokumen">
                        <i class="fa-solid fa-file-pdf"></i>
                    </button>
                                  <?php endif; ?>
                                
                                <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalTimeline<?= $data['id']; ?>" title="Lacak Surat"><i class="fas fa-history"></i></button>

                                <?php if ($data['status_workflow'] == 'Dialokasikan' && $data['draft_by'] == $user_id_sekarang): ?>
                                    <button class="btn btn-sm btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#modalLengkapi<?= $data['id']; ?>" title="Lengkapi"><i class="fa-solid fa-file-arrow-up"></i></button>
                                <?php endif; ?>

                                <?php if(($data['status_workflow'] == 'Draft' || $data['status_workflow'] == 'Revisi') && $data['draft_by'] == $user_id_sekarang): ?>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit_<?= $data['id']; ?>" title="Edit Draft"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <a href="aksi_surat_keluar.php?aksi=ajukan&id=<?= $data['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Yakin ingin mengajukan draf ini ke Kepala Sekolah?');" title="Ajukan"><i class="fa-solid fa-paper-plane"></i></a>
                                <?php endif; ?>

                                <?php if($role_sekarang == 'Kepala_Sekolah' && $data['status_workflow'] == 'Review'): ?>
                                    <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalReview_<?= $data['id']; ?>" title="Tindak Lanjut"><i class="fa-solid fa-signature"></i></button>
                                <?php endif; ?>

                                <?php if ($role_sekarang != 'Kepala_Sekolah' && $data['status_workflow'] == 'Approved'): ?>
                                    <form action="aksi_surat_keluar.php" method="POST" class="d-inline">
                                        <input type="hidden" name="id_surat" value="<?= $data['id']; ?>">
                                        <button type="submit" name="tandai_terkirim" class="btn btn-sm btn-primary" onclick="return confirm('Tandai sudah terkirim?');" title="Tandai Terkirim"><i class="fa-solid fa-check-double"></i></button>
                                    </form>
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
                        'Dialokasikan' => 'bg-info text-dark',
                        'Draft' => 'bg-secondary',
                        'Review' => 'bg-warning text-dark',
                        'Revisi' => 'bg-danger',
                        'Approved' => 'bg-success',
                        'Terkirim' => 'bg-primary',
                        default => 'bg-light text-dark'
                    };
                    $tgl_keluar = !empty($data['tanggal_keluar']) ? date('d/m/Y', strtotime($data['tanggal_keluar'])) : 'Belum di-set';
                ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-3">
                        
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;"><?= $data['nomor_surat'] ?: 'Draft'; ?></span>
                            <span class="badge <?= $warna_status; ?>" style="font-size: 0.7rem;"><?= $data['status_workflow']; ?></span>
                        </div>
                        
                        <div class="d-flex align-items-start mb-2">
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="fa-solid fa-paper-plane fs-5"></i>
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.95rem;"><?= $data['perihal']; ?></h6>
                                <div class="text-muted small mb-1 text-truncate"><i class="fa-solid fa-bullseye me-1"></i> <?= $data['tujuan']; ?></div>
                                <div class="text-muted small mb-1"><i class="fa-regular fa-calendar me-1"></i> <?= $tgl_keluar; ?></div>
                                <div class="text-muted small"><i class="fa-solid fa-user-pen me-1"></i> <?= $data['pembuat']; ?></div>
                            </div>
                        </div>

                        <?php if($data['status_workflow'] == 'Revisi' && !empty($data['catatan_revisi'])): ?>
                            <div class="alert alert-danger p-2 small mb-2 border-danger border-start border-4">
                                <strong>Revisi:</strong> <?= htmlspecialchars($data['catatan_revisi']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-end align-items-center bg-light p-2 rounded-3 gap-1 mt-2">
                            <?php if(!empty($data['file_path'])): ?>
                            <button type="button" 
                            onclick="bukaPreviewPDF('<?= $data['file_path']; ?>')" 
                            class="btn btn-sm btn-outline-info flex-grow-1" title="Dokumen">
                        <i class="fa-solid fa-file-pdf"></i>PDF
                    </button> <?php endif; ?>
                            
                            <button type="button" class="btn btn-info btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalTimeline<?= $data['id']; ?>" title="Lacak"><i class="fas fa-history"></i></button>

                            <?php if ($data['status_workflow'] == 'Dialokasikan' && $data['draft_by'] == $user_id_sekarang): ?>
                                <button class="btn btn-sm btn-warning fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#modalLengkapi<?= $data['id']; ?>" title="Lengkapi"><i class="fa-solid fa-file-arrow-up"></i></button>
                            <?php endif; ?>

                            <?php if(($data['status_workflow'] == 'Draft' || $data['status_workflow'] == 'Revisi') && $data['draft_by'] == $user_id_sekarang): ?>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit_<?= $data['id']; ?>"><i class="fa-solid fa-pen-to-square"></i></button>
                                <a href="aksi_surat_keluar.php?aksi=ajukan&id=<?= $data['id']; ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Ajukan ke Kepala Sekolah?');"><i class="fa-solid fa-paper-plane"></i></a>
                            <?php endif; ?>

                            <?php if($role_sekarang == 'Kepala_Sekolah' && $data['status_workflow'] == 'Review'): ?>
                                <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalReview_<?= $data['id']; ?>"><i class="fa-solid fa-signature"></i> Proses</button>
                            <?php endif; ?>

                            <?php if ($role_sekarang != 'Kepala_Sekolah' && $data['status_workflow'] == 'Approved'): ?>
                                <form action="aksi_surat_keluar.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id_surat" value="<?= $data['id']; ?>">
                                    <button type="submit" name="tandai_terkirim" class="btn btn-sm btn-primary" onclick="return confirm('Tandai terkirim?');"><i class="fa-solid fa-check-double"></i></button>
                                </form>
                            <?php endif; ?>
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
                    <h5 class="modal-title"><i class="fas fa-route"></i> Jejak Surat Keluar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6>Perihal: <strong><?= $data['perihal']; ?></strong></h6> 
                    <?php if (!empty($data['catatan_revisi']) && $data['status_workflow'] == 'Revisi'): ?>
                    <div class="alert alert-danger p-2 mt-2 mb-3 small">
                        <strong><i class="fas fa-exclamation-triangle"></i> Revisi dari Kepsek:</strong><br>
                        <span class="fst-italic">"<?= $data['catatan_revisi']; ?>"</span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="timeline-track">
                        <?php
                        // Gunakan array $audit_logs yang sudah di-query di atas!
                        $id_surat_keluar = $data['id'];
                        if(isset($audit_logs[$id_surat_keluar]) && count($audit_logs[$id_surat_keluar]) > 0) {
                            foreach ($audit_logs[$id_surat_keluar] as $log) {
                                $pesan_aksi = $log['action'];
                                $icon = "fas fa-circle"; $color = "text-secondary";

                                if ($log['action'] == 'CREATE_DRAFT') {
                                    $pesan_aksi = "Draf Surat Dibuat"; $icon = "fas fa-file-signature"; $color = "text-primary";
                                } elseif ($log['action'] == 'REVIEW_SURAT' || $log['action'] == 'SUBMIT_SURAT_KELUAR') {
                                    $pesan_aksi = "Diajukan ke Kepsek (Menunggu Review)"; $icon = "fas fa-paper-plane"; $color = "text-warning";
                                } elseif ($log['action'] == 'REVISI_SURAT' || $log['action'] == 'REJECT_SURAT_KELUAR') {
                                    $pesan_aksi = "Dikembalikan (Perlu Revisi)"; $icon = "fas fa-times-circle"; $color = "text-danger";
                                } elseif ($log['action'] == 'APPROVE_SURAT' || $log['action'] == 'APPROVE_SURAT_KELUAR') {
                                    $pesan_aksi = "Disetujui/TTE oleh Kepsek"; $icon = "fas fa-check-double"; $color = "text-success";
                                } elseif ($log['action'] == 'TERBIT_SURAT' || $log['action'] == 'SEND_SURAT_KELUAR') {
                                    $pesan_aksi = "Surat Diterbitkan & Dikirim"; $icon = "fas fa-envelope-open-text"; $color = "text-info";
                                }
                                $tanggal_log = date('d M Y, H:i', strtotime($log['created_at']));
                        ?>
                                <div class="timeline-item mb-3">
                                    <div class="timeline-date fw-bold" style="font-size: 0.85rem;"><?= $tanggal_log; ?></div>
                                    <div class="timeline-content">
                                        <strong class="<?= $color; ?>"><i class="<?= $icon; ?> me-1"></i> <?= $pesan_aksi; ?></strong><br>
                                        <small class="text-muted">Oleh: <?= $log['nama_lengkap'] ?: 'Sistem'; ?></small>
                                    </div>
                                </div>
                        <?php 
                            } 
                        } else {
                            echo "<p class='text-center text-muted small'>Belum ada riwayat tercatat.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($data['status_workflow'] == 'Dialokasikan' && $data['draft_by'] == $user_id_sekarang): ?>
    <div class="modal fade" id="modalLengkapi<?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog text-start">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-file-arrow-up me-2"></i> Lengkapi Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="aksi_surat_keluar.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id_surat" value="<?= $data['id']; ?>">
                        <div class="alert alert-light border small mb-3">
                            <strong>Nomor Surat Anda:</strong><br>
                            <span class="fs-6 text-primary fw-bold"><?= $data['nomor_surat']; ?></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tanggal Keluar Surat <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="tanggal_keluar" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tujuan Surat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="tujuan" placeholder="Contoh: Dinas Pendidikan Provinsi" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Sifat Surat (Keamanan)</label>
                            <select class="form-select" name="sifat_surat" required>
                                <option value="Biasa" <?= (isset($data['sifat_surat']) && $data['sifat_surat'] == 'Biasa') ? 'selected' : ''; ?>>Biasa</option>
                                <option value="Penting" <?= (isset($data['sifat_surat']) && $data['sifat_surat'] == 'Penting') ? 'selected' : ''; ?>>Penting</option>
                                <option value="Rahasia" <?= (isset($data['sifat_surat']) && $data['sifat_surat'] == 'Rahasia') ? 'selected' : ''; ?>>Rahasia</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Upload File PDF <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="file_surat" accept="application/pdf" required>
                            <small class="text-muted">Pastikan file berisi nomor surat di atas.</small>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="lengkapi_surat" class="btn btn-primary fw-bold"><i class="fa-solid fa-save me-1"></i> Simpan Draft</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(($data['status_workflow'] == 'Draft' || $data['status_workflow'] == 'Revisi') && $data['draft_by'] == $user_id_sekarang): ?>
    <div class="modal fade" id="modalEdit_<?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg text-start">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Draft</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="aksi_surat_keluar.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id_surat" value="<?= $data['id']; ?>">
                        <input type="hidden" name="file_lama" value="<?= $data['file_path']; ?>">

                        <div class="mb-3 border-bottom pb-3">
                            <label class="form-label fw-bold text-primary">Nomor Surat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border-primary" name="nomor_surat" value="<?= $data['nomor_surat'] ?? ''; ?>" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tujuan</label>
                                <input type="text" class="form-control" name="tujuan" value="<?= $data['tujuan']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Sifat Surat</label>
                                <select class="form-select" name="sifat_surat" required>
                                    <option value="Biasa" <?= (isset($data['sifat_surat']) && $data['sifat_surat'] == 'Biasa') ? 'selected' : ''; ?>>Biasa</option>
                                    <option value="Penting" <?= (isset($data['sifat_surat']) && $data['sifat_surat'] == 'Penting') ? 'selected' : ''; ?>>Penting</option>
                                    <option value="Rahasia" <?= (isset($data['sifat_surat']) && $data['sifat_surat'] == 'Rahasia') ? 'selected' : ''; ?>>Rahasia</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Perihal</label>
                            <input type="text" class="form-control" name="perihal" value="<?= $data['perihal']; ?>" required>
                        </div>
                        <div class="mb-3 border rounded p-3 bg-light">
                            <label class="form-label fw-bold">Ganti Dokumen PDF (Opsional)</label>
                            <input type="file" class="form-control" name="file_surat" accept="application/pdf">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_draft" class="btn btn-primary fw-bold"><i class="fa-solid fa-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if($role_sekarang == 'Kepala_Sekolah' && $data['status_workflow'] == 'Review'): ?>
    <div class="modal fade" id="modalReview_<?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog text-start">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-signature me-2"></i>Review & Persetujuan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="aksi_surat_keluar.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id_surat" value="<?= $data['id']; ?>">
                    
                    <div class="alert alert-secondary p-2 mb-3 small">
                        <strong>Pembuat:</strong> <?= $data['pembuat']; ?><br>
                        <strong>Tujuan:</strong> <?= $data['tujuan']; ?><br>
                        <strong>Perihal:</strong> <?= $data['perihal']; ?>
                    </div>
                    
                    <button type="button" 
                            onclick="bukaPreviewPDF('<?= $data['file_path']; ?>')" 
                            class="btn btn-outline-info w-100 fw-bold mb-4" title="Lihat Dokumen">
                        <i class="fa-solid fa-file-pdf me-1"></i> Buka Dokumen PDF
                    </button>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keputusan Kepala Sekolah <span class="text-danger">*</span></label>
                        <select class="form-select border-success" name="keputusan" required onchange="toggleApprovalFields(this, <?= $data['id']; ?>)">
                            <option value="">-- Pilih Tindakan --</option>
                            <option value="Approved">Setujui Surat Ini</option>
                            <option value="Revisi">Tolak & Kembalikan (Revisi)</option>
                        </select>
                    </div>

                    <div id="form_revisi_<?= $data['id']; ?>" style="display: none;" class="p-3 bg-white border border-danger rounded mb-3">
                        <label class="form-label fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation"></i> Instruksi Revisi <span class="text-danger">*</span></label>
                        <textarea name="catatan_revisi" class="form-control border-danger mb-3" rows="3" placeholder="Sebutkan bagian yang salah."></textarea>
                        
                        <label class="form-label fw-bold small text-secondary">Upload Hasil Coretan (Jika ada)</label>
                        <input type="file" name="file_revisi" class="form-control form-control-sm border-danger" accept=".pdf">
                        <div class="form-text small">Unggah file PDF yang sudah di-download dari menu "Buka Dokumen" di atas.</div>
                    </div>

                    <div id="form_approved_<?= $data['id']; ?>" style="display: none;" class="p-3 bg-light border rounded">
                        <div class="alert alert-warning small py-2 mb-3">
                            <i class="fa-solid fa-circle-info"></i> Dokumen akan disetujui dengan Nomor: <strong><?= $data['nomor_surat']; ?></strong>
                        </div>
                        <label class="form-label fw-bold small">Metode Tanda Tangan <span class="text-danger">*</span></label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="is_tte" id="tte1_<?= $data['id']; ?>" value="1" checked>
                            <label class="form-check-label text-success fw-bold" for="tte1_<?= $data['id']; ?>">
                                <i class="fa-solid fa-qrcode"></i> TTE Digital (Beri stempel QR)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-input" type="radio" name="is_tte" id="tte0_<?= $data['id']; ?>" value="0">
                            <label class="form-check-label text-dark" for="tte0_<?= $data['id']; ?>">
                                <i class="fa-solid fa-pen"></i> Tanda Tangan Basah (Manual)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" name="proses_review" class="btn btn-success fw-bold"><i class="fa-solid fa-check me-1"></i> Proses</button>
                </div>
            </form>
        </div>
    </div>
</div>
    <?php endif; ?>

<?php endforeach; ?>

<!-- ============================================================
     MODAL PREVIEW PDF — identik pola surat masuk
     ============================================================ -->
<div class="modal fade" id="modalPreviewPDF" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-fullscreen-lg-down" style="max-width: 95vw;">
        <div class="modal-content border-0" style="height: 92vh;">

            <!-- TOOLBAR ATAS -->
            <div class="modal-header py-2 px-3 d-flex align-items-center gap-2"
                 style="background: #1a3a5c; border-bottom: 2px solid #0d6efd;">

                <!-- Tombol Tutup -->
                <button type="button"
                        class="btn btn-sm btn-outline-light d-flex align-items-center gap-1 me-1"
                        data-bs-dismiss="modal"
                        title="Tutup Preview">
                    <i class="fa-solid fa-arrow-left"></i>
                </button>

                <!-- Judul -->
                <span id="previewPDFTitle"
                      class="text-white fw-semibold text-truncate flex-grow-1"
                      style="font-size: 0.9rem; max-width: 50%;">
                    Dokumen PDF
                </span>

                <!-- Badge mode aktif -->
                <span id="labelModePDF" class="badge bg-info text-dark ms-auto" style="white-space:nowrap;">
                    <i class="fa-solid fa-layer-group me-1"></i>PDF.js
                </span>

                <!-- Tombol: Buka Bawaan (tampil saat mode PDF.js) -->
                <button type="button" id="btnModeBawaan"
                        onclick="bukaPDF()"
                        class="btn btn-warning btn-sm fw-bold d-flex align-items-center gap-1"
                        title="Tampilkan dengan PDF Viewer bawaan browser"
                        style="white-space:nowrap;">
                    <i class="fa-solid fa-file-pdf"></i>
                    <span class="d-none d-sm-inline">Buka Bawaan</span>
                </button>

                <!-- Tombol: Kembali PDF.js (tampil saat mode bawaan) -->
                <button type="button" id="btnModePdfJs"
                        onclick="bukaPreviewPDFJs()"
                        class="btn btn-outline-light btn-sm d-flex align-items-center gap-1"
                        title="Kembali ke PDF.js"
                        style="white-space:nowrap; display:none;">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span class="d-none d-sm-inline">PDF.js</span>
                </button>
            </div>

            <!-- AREA IFRAME — satu iframe statis, src-nya yang diganti -->
            <div class="modal-body p-0 flex-grow-1" style="overflow:hidden;">
                <iframe id="framePDF"
                        src=""
                        style="width:100%; height:100%; border:none; display:block;"
                        title="Preview Dokumen PDF">
                </iframe>
            </div>

        </div>
    </div>
</div>

<script>
// Simpan nama file aktif agar bisa dipakai oleh kedua tombol
var _namaFilePDFAktif = '';

// ── Dipanggil dari tombol PDF di tabel / kartu ──────────────────
// Buka modal + langsung tampilkan via PDF.js (pola sama dengan surat masuk)
function bukaPreviewPDF(namaFile) {
    _namaFilePDFAktif = namaFile;

    var viewerUrl = window.location.origin + '/vendor/pdfjs/web/viewer.html';
    var proxyUrl  = window.location.origin + '/ambil_pdf.php?file=' + encodeURIComponent(namaFile);
    var finalUrl  = viewerUrl + '?file=' + encodeURIComponent(proxyUrl) + '&v=' + new Date().getTime();

    document.getElementById('previewPDFTitle').textContent = namaFile;
    document.getElementById('framePDF').src = finalUrl;

    // Pastikan tombol di kondisi mode PDF.js
    document.getElementById('btnModeBawaan').style.display = '';
    document.getElementById('btnModePdfJs').style.display  = 'none';
    document.getElementById('labelModePDF').innerHTML = '<i class="fa-solid fa-layer-group me-1"></i>PDF.js';
    document.getElementById('labelModePDF').className = 'badge bg-info text-dark ms-auto';

    var bsModal = new bootstrap.Modal(document.getElementById('modalPreviewPDF'));
    bsModal.show();
}

// ── Tombol "Buka Bawaan" — set iframe src langsung ke URL file ──
// Persis seperti fungsi bukaPDF() di surat masuk
function bukaPDF() {
    if (!_namaFilePDFAktif) return;
    var fileUrl = window.location.origin + '/uploads/surat_keluar/' + _namaFilePDFAktif;

    document.getElementById('framePDF').src = fileUrl;

    document.getElementById('labelModePDF').innerHTML = '<i class="fa-solid fa-file-pdf me-1"></i>Bawaan';
    document.getElementById('labelModePDF').className = 'badge bg-warning text-dark ms-auto';
    document.getElementById('btnModeBawaan').style.display = 'none';
    document.getElementById('btnModePdfJs').style.display  = '';
}

// ── Tombol "PDF.js" — kembali ke mode PDF.js ────────────────────
function bukaPreviewPDFJs() {
    if (!_namaFilePDFAktif) return;

    var viewerUrl = window.location.origin + '/vendor/pdfjs/web/viewer.html';
    var proxyUrl  = window.location.origin + '/ambil_pdf.php?file=' + encodeURIComponent(_namaFilePDFAktif);
    var finalUrl  = viewerUrl + '?file=' + encodeURIComponent(proxyUrl) + '&v=' + new Date().getTime();

    document.getElementById('framePDF').src = finalUrl;

    document.getElementById('labelModePDF').innerHTML = '<i class="fa-solid fa-layer-group me-1"></i>PDF.js';
    document.getElementById('labelModePDF').className = 'badge bg-info text-dark ms-auto';
    document.getElementById('btnModeBawaan').style.display = '';
    document.getElementById('btnModePdfJs').style.display  = 'none';
}

// ── Bersihkan saat modal ditutup ────────────────────────────────
document.getElementById('modalPreviewPDF').addEventListener('hidden.bs.modal', function () {
    document.getElementById('framePDF').src = '';
    _namaFilePDFAktif = '';
    document.getElementById('btnModeBawaan').style.display = '';
    document.getElementById('btnModePdfJs').style.display  = 'none';
    document.getElementById('labelModePDF').innerHTML = '<i class="fa-solid fa-layer-group me-1"></i>PDF.js';
    document.getElementById('labelModePDF').className = 'badge bg-info text-dark ms-auto';
});
</script>

<script>
function toggleApprovalFields(selectObj, id) {
    let formApproved = document.getElementById('form_approved_' + id);
    let formRevisi = document.getElementById('form_revisi_' + id);
    let textareaRevisi = formRevisi.querySelector('textarea[name="catatan_revisi"]');
    
    if (selectObj.value === 'Approved') {
        formApproved.style.display = 'block';
        formRevisi.style.display = 'none';
        textareaRevisi.removeAttribute('required');
    } else if (selectObj.value === 'Revisi') {
        formApproved.style.display = 'none';
        formRevisi.style.display = 'block';
        textareaRevisi.setAttribute('required', 'required');
    } else {
        formApproved.style.display = 'none';
        formRevisi.style.display = 'none';
        textareaRevisi.removeAttribute('required');
    }
}
</script>

<?php include '../layouts/footer.php'; ?>