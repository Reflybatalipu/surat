<?php
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

session_start();
include '../config/koneksi.php';

// 🛡️ Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

$user_id_sekarang = $_SESSION['user_id'];
$role_sekarang = $_SESSION['nama_role'];

// ========================================================
// PROSES UPDATE LOKASI FISIK ARSIP
// ========================================================
if (isset($_POST['update_lokasi'])) {
    $id_surat = mysqli_real_escape_string($koneksi, $_POST['id_surat']);
    $jenis_surat = mysqli_real_escape_string($koneksi, $_POST['jenis_surat']);
    $lokasi_fisik = mysqli_real_escape_string($koneksi, $_POST['lokasi_fisik']);

    if ($jenis_surat == 'Surat Masuk') {
        $query_update = "UPDATE surat_masuk SET lokasi_fisik = '$lokasi_fisik' WHERE id = '$id_surat'";
    } else {
        $query_update = "UPDATE surat_keluar SET lokasi_fisik = '$lokasi_fisik' WHERE id = '$id_surat'";
    }

    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Lokasi fisik berhasil diperbarui!'); window.location.href='earsip.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui lokasi fisik!'); window.location.href='earsip.php';</script>";
    }
}

// ========================================================
// LOGIKA PENCARIAN & FILTER GABUNGAN (DEEP SEARCH)
// ========================================================
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : "";
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : "Semua";

// 1. Query Dasar Surat Masuk
$sql_masuk = "SELECT id, nomor_surat, perihal, tanggal_surat AS tanggal, klasifikasi, lokasi_fisik, file_path, 'Surat Masuk' AS jenis, created_at 
              FROM surat_masuk WHERE status_workflow IN ('Selesai', 'Diarsipkan')";

// 2. Query Dasar Surat Keluar (Hanya yang Terkirim)
$sql_keluar = "SELECT id, nomor_surat, perihal, tanggal_keluar AS tanggal, sifat_surat, lokasi_fisik, file_path, 'Surat Keluar' AS jenis, created_at 
               FROM surat_keluar WHERE status_workflow = 'Terkirim'";

// --- Filter Pencarian Kata Kunci ---
if ($keyword != "") {
    // Deep Search: Mencari di nomor_surat, perihal, DAN ocr_text (untuk surat masuk)
    $sql_masuk .= " AND (nomor_surat LIKE '%$keyword%' OR perihal LIKE '%$keyword%' OR ocr_text LIKE '%$keyword%')";
    $sql_keluar .= " AND (nomor_surat LIKE '%$keyword%' OR perihal LIKE '%$keyword%')";
}

// --- Filter Hak Akses Berdasarkan Role ---
if ($role_sekarang != 'Kepala_Sekolah' && $role_sekarang != 'Admin_TU') {
    $sql_keluar .= " AND draft_by = '$user_id_sekarang'";
    $sql_masuk .= " AND created_by = '$user_id_sekarang'"; 
}

// --- Penggabungan Tabel (UNION) berdasarkan Filter Dropdown ---
$sql_final = "";
if ($filter_jenis == 'Surat Masuk') {
    $sql_final = $sql_masuk . " ORDER BY created_at DESC";
} elseif ($filter_jenis == 'Surat Keluar') {
    $sql_final = $sql_keluar . " ORDER BY created_at DESC";
} else {
    // Tampilkan SEMUA dengan UNION ALL
    $sql_final = "($sql_masuk) UNION ALL ($sql_keluar) ORDER BY created_at DESC";
}

// ========================================================
// AMBIL SEMUA DATA & SIMPAN KE ARRAY
// ========================================================
$query_arsip = mysqli_query($koneksi, $sql_final);
$data_arsip = [];
$surat_masuk_ids = []; // Untuk menampung ID surat masuk agar bisa mencari lampiran

while ($row = mysqli_fetch_array($query_arsip)) {
    $data_arsip[] = $row;
    if ($row['jenis'] == 'Surat Masuk') {
        $surat_masuk_ids[] = $row['id'];
    }
}

// ========================================================
// AMBIL DATA LAMPIRAN (HANYA UNTUK SURAT MASUK)
// ========================================================
$lampiran_arsip = [];
if (!empty($surat_masuk_ids)) {
    $ids_str = implode(',', $surat_masuk_ids);
    $q_lamp = mysqli_query($koneksi, "SELECT * FROM lampiran_surat_masuk WHERE id_surat_masuk IN ($ids_str)");
    while ($lamp = mysqli_fetch_assoc($q_lamp)) {
        $lampiran_arsip[$lamp['id_surat_masuk']][] = $lamp;
    }
}

include '../layouts/header.php'; 
?>

<style>
/* Efek hover untuk baris tabel dan card arsip */
.hover-arsip:hover {
    background-color: #f8f9fa;
    transform: translateY(-2px);
    transition: 0.2s ease-in-out;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1) !important;
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-box-archive me-2 text-primary"></i> Arsip Digital</h4>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
    <div class="card-body">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-3">
                <select name="jenis" class="form-select">
                    <option value="Semua" <?= $filter_jenis == 'Semua' ? 'selected' : ''; ?>>Semua Arsip</option>
                    <option value="Surat Masuk" <?= $filter_jenis == 'Surat Masuk' ? 'selected' : ''; ?>>Hanya Surat Masuk</option>
                    <option value="Surat Keluar" <?= $filter_jenis == 'Surat Keluar' ? 'selected' : ''; ?>>Hanya Surat Keluar</option>
                </select>
            </div>
            <div class="col-md-7">
                <input type="text" class="form-control" name="cari" value="<?= htmlspecialchars($keyword); ?>" placeholder="Cari nomor, perihal, atau isi teks dalam surat (OCR)...">
            </div>
            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary fw-bold"><i class="fa-solid fa-magnifying-glass me-1"></i> Cari Arsip</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0 p-md-4">
        
        <?php if (empty($data_arsip)): ?>
            <div class='text-center py-5 text-muted'>
                <i class='fa-solid fa-folder-open fs-1 d-block mb-3 text-light'></i> 
                Tidak ada arsip yang ditemukan.
            </div>
        <?php else: ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="ps-3">No</th>
                            <th width="15%">Jenis Surat</th>
                            <th width="20%">No. Surat / Tanggal</th>
                            <th width="25%">Perihal & Klasifikasi</th>
                            <th width="20%">Lokasi Fisik (Rak/Lemari)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($data_arsip as $data): 
                            $warna_badge = ($data['jenis'] == 'Surat Masuk') ? 'bg-success' : 'bg-primary';
                            $tgl = !empty($data['tanggal']) ? date('d/m/Y', strtotime($data['tanggal'])) : '-';
                            $jenis_id = str_replace(' ', '', $data['jenis']);
                        ?>
                        <tr style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $jenis_id; ?><?= $data['id']; ?>" title="Klik untuk melihat detail" class="hover-arsip">
                            <td class="ps-3"><?= $no++; ?></td>
                            <td><span class="badge <?= $warna_badge; ?> px-2 py-1"><i class="fa-solid fa-file-signature me-1"></i> <?= $data['jenis']; ?></span></td>
                            <td>
                                <div class="fw-bold text-dark font-monospace" style="font-size: 0.9rem;"><?= $data['nomor_surat'] ?: '-'; ?></div>
                                <div class="small text-muted"><i class="fa-regular fa-calendar me-1"></i> <?= $tgl; ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-truncate" style="max-width: 250px; font-size:0.95rem;"><?= $data['perihal']; ?></div>
                                <span class="badge bg-secondary mt-1" style="font-size: 0.7em;"><?= $data['klasifikasi']; ?></span>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    <i class="fa-solid fa-folder-open me-1 text-warning"></i> <strong><?= $data['lokasi_fisik'] ?: '<span class="fst-italic fw-normal">Belum di-set</span>'; ?></strong>
                                </div>
                            </td>
                            
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none p-3 bg-light" style="max-height: 75vh; overflow-y: auto;">
                <?php foreach ($data_arsip as $data): 
                    $warna_badge = ($data['jenis'] == 'Surat Masuk') ? 'bg-success' : 'bg-primary';
                    $warna_icon = ($data['jenis'] == 'Surat Masuk') ? 'text-success' : 'text-primary';
                    $bg_icon = ($data['jenis'] == 'Surat Masuk') ? 'bg-success' : 'bg-primary';
                    $tgl = !empty($data['tanggal']) ? date('d/m/Y', strtotime($data['tanggal'])) : '-';
                    $jenis_id = str_replace(' ', '', $data['jenis']);
                ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3 hover-arsip" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $jenis_id; ?><?= $data['id']; ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="badge <?= $warna_badge; ?>" style="font-size: 0.7rem;"><i class="fa-solid fa-file-signature me-1"></i> <?= $data['jenis']; ?></span>
                            <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i> <?= $tgl; ?></span>
                        </div>
                        
                        <div class="d-flex align-items-start mb-2">
                            <div class="<?= $bg_icon; ?> bg-opacity-10 <?= $warna_icon; ?> rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="fa-solid <?= ($data['jenis'] == 'Surat Masuk') ? 'fa-inbox' : 'fa-paper-plane'; ?> fs-5"></i>
                            </div>
                            
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-bold text-dark font-monospace text-truncate mb-1" style="font-size: 0.85rem;"><?= $data['nomor_surat'] ?: 'Tanpa Nomor'; ?></div>
                                <h6 class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                                    <?= $data['perihal']; ?>
                                </h6>
                                <div class="text-muted small">
                                    <i class="fa-solid fa-folder-open me-1 text-warning"></i> Fisik: <strong><?= $data['lokasi_fisik'] ?: '-'; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php foreach ($data_arsip as $data): 
    $folder_file = ($data['jenis'] == 'Surat Masuk') ? 'surat_masuk' : 'surat_keluar';
    $jenis_id = str_replace(' ', '', $data['jenis']);
    $tgl = !empty($data['tanggal']) ? date('d/m/Y', strtotime($data['tanggal'])) : '-';
?>
<div class="modal fade" id="modalDetail<?= $jenis_id; ?><?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content text-start">
            <div class="modal-header <?= ($data['jenis'] == 'Surat Masuk') ? 'bg-success text-white' : 'bg-primary text-white'; ?>">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-file-lines me-2"></i> Detail <?= $data['jenis']; ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body bg-light">
                <div class="row g-3">
                    
                    <div class="col-md-7">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Informasi Utama</h6>
                                <table class="table table-borderless table-sm mb-0">
                                    <tr><td width="35%" class="text-muted">Nomor Surat</td><td class="fw-bold">: <?= $data['nomor_surat'] ?: '-'; ?></td></tr>
                                    <tr><td class="text-muted">Tanggal</td><td class="fw-bold">: <?= $tgl; ?></td></tr>
                                    <tr><td class="text-muted">Klasifikasi</td><td>: <span class="badge bg-secondary"><?= $data['klasifikasi']; ?></span></td></tr>
                                    <tr><td class="text-muted pb-0">Perihal</td><td class="fw-bold text-dark pb-0">: <?= $data['perihal']; ?></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body text-center p-3">
                                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3">Dokumen Utama</h6>
                                <?php if(!empty($data['file_path'])): ?>
                                    <button type="button" 
                            onclick="bukaPreviewPDF('<?= $data['file_path']; ?>')" 
                            class="btn btn-outline-info w-100 fw-bold mb-4" title="Lihat Dokumen">
                        <i class="fa-solid fa-file-pdf me-1"></i> Buka Dokumen PDF
                    </button>
                                <?php else: ?>
                                    <span class="text-danger small"><i class="fa-solid fa-triangle-exclamation"></i> File tidak tersedia</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if($role_sekarang == 'Admin_TU'): ?>
                        <div class="card border-0 shadow-sm border-start border-warning border-4 mb-3">
                            <div class="card-body p-3">
                                <form action="" method="POST">
                                    <input type="hidden" name="id_surat" value="<?= $data['id']; ?>">
                                    <input type="hidden" name="jenis_surat" value="<?= $data['jenis']; ?>">
                                    
                                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-map-location-dot me-1"></i> Update Lokasi Fisik</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" name="lokasi_fisik" value="<?= htmlspecialchars($data['lokasi_fisik']); ?>" required>
                                        <button type="submit" name="update_lokasi" class="btn btn-warning fw-bold text-dark" title="Simpan Lokasi"><i class="fa-solid fa-save"></i></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($data['jenis'] == 'Surat Masuk'): ?>
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <h6 class="fw-bold text-muted border-bottom pb-2 mb-3"><i class="fa-solid fa-paperclip me-2"></i>Lampiran Pendukung</h6>
                                
                                <div class="row g-2">
                                <?php 
                                $id_sm = $data['id'];
                                if (isset($lampiran_arsip[$id_sm]) && count($lampiran_arsip[$id_sm]) > 0): 
                                    foreach($lampiran_arsip[$id_sm] as $lamp): 
                                ?>
                                    <div class="col-md-6">
                                        <a href="../uploads/surat_masuk/<?= $lamp['path_file']; ?>" target="_blank" class="btn btn-light border w-100 text-start text-dark d-flex align-items-center p-2 shadow-sm">
                                            <i class="fa-solid fa-file text-secondary fs-5 me-3 ms-1"></i>
                                            <div class="flex-grow-1 text-truncate small fw-bold" style="max-width: 85%;">
                                                <?= htmlspecialchars($lamp['nama_file']); ?>
                                            </div>
                                        </a>
                                    </div>
                                <?php 
                                    endforeach; 
                                else: 
                                ?>
                                    <div class="col-12 text-center py-2">
                                        <span class="text-muted small fst-italic">Tidak ada file lampiran tambahan.</span>
                                    </div>
                                <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <div class="modal-footer border-top-0 bg-white">
                <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include '../layouts/footer.php'; ?>