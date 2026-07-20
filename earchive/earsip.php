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
    exit;
}

// ========================================================
// PARAMETER PENCARIAN & FILTER
// ========================================================
$keyword = isset($_GET['cari']) ? mysqli_real_escape_string($koneksi, $_GET['cari']) : "";
$filter_jenis = isset($_GET['jenis']) ? $_GET['jenis'] : "Semua";

// Mode ditentukan oleh keyword:
// - keyword kosong  -> MODE FOLDER (dikelompokkan per lokasi fisik)
// - keyword diisi   -> MODE PENCARIAN (flat list, lintas folder, seperti sebelumnya)
$mode_folder = ($keyword === "");

$data_arsip = [];
$lampiran_arsip = [];
$data_folder = [];

if (!$mode_folder) {
    // ====================================================
    // MODE PENCARIAN (FLAT) — logika lama, tidak diubah
    // ====================================================
    $sql_masuk = "SELECT id, nomor_surat, perihal, tanggal_surat AS tanggal, klasifikasi, lokasi_fisik, file_path, 'Surat Masuk' AS jenis, created_at 
                  FROM surat_masuk WHERE status_workflow IN ('Selesai', 'Diarsipkan')";

    $sql_keluar = "SELECT id, nomor_surat, perihal, tanggal_keluar AS tanggal, sifat_surat, lokasi_fisik, file_path, 'Surat Keluar' AS jenis, created_at 
                   FROM surat_keluar WHERE status_workflow = 'Terkirim'";

    if ($keyword != "") {
        $sql_masuk .= " AND (nomor_surat LIKE '%$keyword%' OR perihal LIKE '%$keyword%' OR ocr_text LIKE '%$keyword%')";
        $sql_keluar .= " AND (nomor_surat LIKE '%$keyword%' OR perihal LIKE '%$keyword%')";
    }

    if ($role_sekarang != 'Kepala_Sekolah' && $role_sekarang != 'Admin_TU') {
        $sql_keluar .= " AND draft_by = '$user_id_sekarang'";
        $sql_masuk .= " AND created_by = '$user_id_sekarang'";
    }

    if ($filter_jenis == 'Surat Masuk') {
        $sql_final = $sql_masuk . " ORDER BY created_at DESC";
    } elseif ($filter_jenis == 'Surat Keluar') {
        $sql_final = $sql_keluar . " ORDER BY created_at DESC";
    } else {
        $sql_final = "($sql_masuk) UNION ALL ($sql_keluar) ORDER BY created_at DESC";
    }

    $query_arsip = mysqli_query($koneksi, $sql_final);
    $surat_masuk_ids = [];

    while ($row = mysqli_fetch_array($query_arsip)) {
        $data_arsip[] = $row;
        if ($row['jenis'] == 'Surat Masuk') {
            $surat_masuk_ids[] = $row['id'];
        }
    }

    if (!empty($surat_masuk_ids)) {
        $ids_str = implode(',', $surat_masuk_ids);
        $q_lamp = mysqli_query($koneksi, "SELECT * FROM lampiran_surat_masuk WHERE id_surat_masuk IN ($ids_str)");
        while ($lamp = mysqli_fetch_assoc($q_lamp)) {
            $lampiran_arsip[$lamp['id_surat_masuk']][] = $lamp;
        }
    }
} else {
    // ====================================================
    // MODE FOLDER — hitung jumlah surat per lokasi_fisik
    // ====================================================
    $sql_masuk_grp = "SELECT COALESCE(NULLIF(lokasi_fisik,''),'Belum Dikelompokkan') AS lokasi FROM surat_masuk WHERE status_workflow IN ('Selesai', 'Diarsipkan')";
    $sql_keluar_grp = "SELECT COALESCE(NULLIF(lokasi_fisik,''),'Belum Dikelompokkan') AS lokasi FROM surat_keluar WHERE status_workflow = 'Terkirim'";

    if ($role_sekarang != 'Kepala_Sekolah' && $role_sekarang != 'Admin_TU') {
        $sql_keluar_grp .= " AND draft_by = '$user_id_sekarang'";
        $sql_masuk_grp .= " AND created_by = '$user_id_sekarang'";
    }

    if ($filter_jenis == 'Surat Masuk') {
        $sql_gabung_grp = $sql_masuk_grp;
    } elseif ($filter_jenis == 'Surat Keluar') {
        $sql_gabung_grp = $sql_keluar_grp;
    } else {
        $sql_gabung_grp = "$sql_masuk_grp UNION ALL $sql_keluar_grp";
    }

    $sql_folder = "SELECT lokasi, COUNT(*) AS jumlah FROM ($sql_gabung_grp) AS gabungan GROUP BY lokasi ORDER BY (lokasi = 'Belum Dikelompokkan') ASC, lokasi ASC";
    $q_folder = mysqli_query($koneksi, $sql_folder);
    while ($f = mysqli_fetch_assoc($q_folder)) {
        $data_folder[] = $f;
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

/* Folder card */
.folder-card {
    transition: 0.2s ease-in-out;
}
.folder-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.12) !important;
    border-color: #ffc107 !important;
}
.folder-card .fa-folder {
    transition: 0.2s ease-in-out;
}
.folder-card:hover .fa-folder {
    color: #ffca2c !important;
}

/* Tombol toggle List / Card */
.btn-toggle-tampilan.active {
    background-color: #0d6efd;
    color: #fff;
    border-color: #0d6efd;
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
        <?php if ($keyword !== ""): ?>
        <div class="mt-2">
            <a href="earsip.php<?= $filter_jenis !== 'Semua' ? '?jenis=' . urlencode($filter_jenis) : ''; ?>" class="small text-decoration-none">
                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke tampilan folder
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$mode_folder): ?>
<!-- ============================================================ -->
<!-- MODE PENCARIAN (FLAT) — sama seperti sebelumnya               -->
<!-- ============================================================ -->
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
                                <?php if (!empty($data['file_path'])): ?>
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

                        <?php if ($role_sekarang == 'Admin_TU'): ?>
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
                                    foreach ($lampiran_arsip[$id_sm] as $lamp):
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

<?php else: ?>
<!-- ============================================================ -->
<!-- MODE FOLDER — dikelompokkan per Lokasi Fisik (Rak/Lemari)     -->
<!-- ============================================================ -->

<div id="areaFolderGrid">
    <?php if (empty($data_folder)): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body text-center py-5 text-muted">
                <i class="fa-solid fa-folder-open fs-1 d-block mb-3 text-light"></i>
                Tidak ada arsip yang ditemukan.
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($data_folder as $f): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card border shadow-sm rounded-4 folder-card h-100" style="cursor: pointer;" data-lokasi="<?= htmlspecialchars($f['lokasi'], ENT_QUOTES); ?>">
                    <div class="card-body text-center py-4">
                        <i class="fa-solid fa-folder fa-3x text-warning mb-3"></i>
                        <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($f['lokasi']); ?>"><?= htmlspecialchars($f['lokasi']); ?></div>
                        <span class="badge bg-secondary mt-2"><?= (int) $f['jumlah']; ?> surat</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="areaIsiFolder" class="d-none">
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <button type="button" class="btn btn-sm btn-light border" onclick="tutupFolder()">
                    <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Folder
                </button>
                <span class="fw-bold ms-2">
                    <i class="fa-solid fa-folder-open text-warning me-1"></i> <span id="labelFolderAktif"></span>
                </span>
            </div>
            <div class="btn-group" role="group">
                <button type="button" id="btnModeList" class="btn btn-sm btn-outline-primary btn-toggle-tampilan" onclick="setModeTampilan('list')">
                    <i class="fa-solid fa-list me-1"></i> List
                </button>
                <button type="button" id="btnModeCard" class="btn btn-sm btn-outline-primary btn-toggle-tampilan" onclick="setModeTampilan('card')">
                    <i class="fa-solid fa-table-cells-large me-1"></i> Card
                </button>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0 p-md-4">

            <div id="isiFolderTableWrap" class="table-responsive">
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
                    <tbody id="isiFolderTable">
                        <tr><td colspan="5" class="text-center py-4 text-muted">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>

            <div id="isiFolderCardWrap" class="d-none p-3">
                <div id="isiFolderCard" class="row g-3">
                    <div class="col-12 text-center py-4 text-muted">Memuat...</div>
                </div>
            </div>

        </div>
    </div>
</div>

<div id="isiFolderModalContainer"></div>

<?php endif; ?>

<script>
const filterJenisAktif = <?= json_encode($filter_jenis); ?>;
const modeFolderAktif = <?= json_encode($mode_folder); ?>;

function bukaFolder(lokasi) {
    document.getElementById('areaFolderGrid').classList.add('d-none');
    document.getElementById('areaIsiFolder').classList.remove('d-none');
    document.getElementById('labelFolderAktif').innerText = lokasi;

    document.getElementById('isiFolderTable').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Memuat...</td></tr>';
    document.getElementById('isiFolderCard').innerHTML = '<div class="col-12 text-center py-4 text-muted">Memuat...</div>';
    document.getElementById('isiFolderModalContainer').innerHTML = '';

    const params = new URLSearchParams({
        action: 'get_folder_isi',
        lokasi_fisik: lokasi,
        jenis: filterJenisAktif
    });

    fetch('ambil_arsip_ajax.php?' + params.toString())
        .then(function (res) { return res.json(); })
        .then(function (data) {
            document.getElementById('isiFolderTable').innerHTML = data.table && data.table.length
                ? data.table
                : '<tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada surat di folder ini.</td></tr>';

            document.getElementById('isiFolderCard').innerHTML = data.card && data.card.length
                ? data.card
                : '<div class="col-12 text-center py-4 text-muted">Tidak ada surat di folder ini.</div>';

            document.getElementById('isiFolderModalContainer').innerHTML = data.modal || '';
        })
        .catch(function () {
            document.getElementById('isiFolderTable').innerHTML = '<tr><td colspan="5" class="text-center py-4 text-danger">Gagal memuat data. Coba lagi.</td></tr>';
            document.getElementById('isiFolderCard').innerHTML = '<div class="col-12 text-center py-4 text-danger">Gagal memuat data. Coba lagi.</div>';
        });
}

function tutupFolder() {
    document.getElementById('areaIsiFolder').classList.add('d-none');
    document.getElementById('areaFolderGrid').classList.remove('d-none');
}

function setModeTampilan(mode) {
    localStorage.setItem('earsip_view_mode', mode);
    terapkanModeTampilan(mode);
}

function terapkanModeTampilan(mode) {
    const tableWrap = document.getElementById('isiFolderTableWrap');
    const cardWrap = document.getElementById('isiFolderCardWrap');
    const btnList = document.getElementById('btnModeList');
    const btnCard = document.getElementById('btnModeCard');
    if (!tableWrap || !cardWrap) return;

    if (mode === 'card') {
        tableWrap.classList.add('d-none');
        cardWrap.classList.remove('d-none');
        btnCard.classList.add('active');
        btnList.classList.remove('active');
    } else {
        cardWrap.classList.add('d-none');
        tableWrap.classList.remove('d-none');
        btnList.classList.add('active');
        btnCard.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (!modeFolderAktif) return;

    document.querySelectorAll('.folder-card').forEach(function (el) {
        el.addEventListener('click', function () {
            bukaFolder(this.dataset.lokasi);
        });
    });

    const savedMode = localStorage.getItem('earsip_view_mode') || 'list';
    terapkanModeTampilan(savedMode);
});
</script>

<?php include '../layouts/footer.php'; ?>
