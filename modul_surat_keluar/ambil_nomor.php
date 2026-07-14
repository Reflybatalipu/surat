<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config/koneksi.php';

// 🛡️ Cek Login
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

$user_id_sekarang = $_SESSION['user_id'];
$tahun_sekarang = date('Y');
$bulan_sekarang = date('n'); // 1-12

// Fungsi konversi bulan ke Romawi
function getBulanRomawi($bulan) {
    $romawi = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
    return $romawi[$bulan] ?? '';
}

function set_nomor_flash($type, $title, $message, $detail = '') {
    $_SESSION['nomor_flash'] = [
        'type'    => $type,
        'title'   => $title,
        'message' => $message,
        'detail'  => $detail
    ];
}

function redirect_ambil_nomor($query = '') {
    $target = 'ambil_nomor.php' . ($query ? '?' . $query : '');
    header("Location: $target");
    exit;
}

// ========================================================
// 1. CARI REKOMENDASI NOMOR GLOBAL (AGENDA TUNGGAL)
// ========================================================
$query_max_saran = mysqli_query($koneksi, "SELECT MAX(nomor_urut) AS urut_terakhir FROM surat_keluar WHERE YEAR(created_at) = '$tahun_sekarang'");
$data_max_saran = $query_max_saran ? mysqli_fetch_assoc($query_max_saran) : ['urut_terakhir' => null];
$rekomendasi_nomor = ($data_max_saran['urut_terakhir'] != null) ? ((int)$data_max_saran['urut_terakhir'] + 1) : 1;

// ========================================================
// PROSES PENGAMBILAN NOMOR (SISTEM RESERVASI)
// ========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ambil_nomor'])) {
    $kode_klasifikasi = mysqli_real_escape_string($koneksi, trim($_POST['kode_klasifikasi'] ?? ''));
    $keterangan_unit  = mysqli_real_escape_string($koneksi, trim($_POST['keterangan_unit'] ?? ''));
    $perihal          = mysqli_real_escape_string($koneksi, trim($_POST['perihal'] ?? ''));
    $nomor_urut_input = (int)($_POST['nomor_urut_manual'] ?? 0);

    if ($kode_klasifikasi === '' || $keterangan_unit === '' || $perihal === '' || $nomor_urut_input < 1) {
        set_nomor_flash('danger', 'Data belum lengkap', 'Pastikan nomor urut, klasifikasi, unit pengolah, dan perihal sudah diisi dengan benar.');
        redirect_ambil_nomor();
    }

    // 💡 VALIDASI ANTI-DUPLIKAT (Cek Global Tahun Berjalan)
    $cek_duplikat = mysqli_query($koneksi, "SELECT id, nomor_surat, status_workflow FROM surat_keluar WHERE nomor_urut = '$nomor_urut_input' AND YEAR(created_at) = '$tahun_sekarang' LIMIT 1");

    if ($cek_duplikat && mysqli_num_rows($cek_duplikat) > 0) {
        $data_bentrok = mysqli_fetch_assoc($cek_duplikat);

        // Ambil ulang rekomendasi terkini agar user tidak perlu menebak nomor berikutnya.
        $q_saran_baru = mysqli_query($koneksi, "SELECT MAX(nomor_urut) AS urut_terakhir FROM surat_keluar WHERE YEAR(created_at) = '$tahun_sekarang'");
        $d_saran_baru = $q_saran_baru ? mysqli_fetch_assoc($q_saran_baru) : ['urut_terakhir' => $nomor_urut_input];
        $saran_baru = ($d_saran_baru['urut_terakhir'] != null) ? ((int)$d_saran_baru['urut_terakhir'] + 1) : 1;

        set_nomor_flash(
            'warning',
            'Nomor urut sudah dipakai',
            'Nomor urut ' . sprintf('%03d', $nomor_urut_input) . ' sudah digunakan pada tahun ' . $tahun_sekarang . '.',
            'Surat bentrok: ' . ($data_bentrok['nomor_surat'] ?: '-') . ' • Status: ' . ($data_bentrok['status_workflow'] ?: '-') . '. Sistem menyarankan nomor terbaru: ' . sprintf('%03d', $saran_baru) . '.'
        );
        redirect_ambil_nomor('suggest=' . urlencode($saran_baru));
    }

    // Jika aman (tidak duplikat), format angka jadi 3 digit (Contoh: 5 jadi 005)
    $format_urut = sprintf("%03d", $nomor_urut_input);
    $romawi = getBulanRomawi($bulan_sekarang);

    // 💡 RAKIT NOMOR SURAT FULL (Sesuai kesepakatan SMKN 4)
    $nomor_surat_full = "$kode_klasifikasi/SMKN4-$keterangan_unit/$format_urut/$romawi/$tahun_sekarang";

    // INSERT ke Database
    // Catatan: sifat_surat disimpan sebagai default Biasa karena sifat final masih bisa dilengkapi saat upload dokumen.
    $query_insert = "INSERT INTO surat_keluar (nomor_urut, nomor_surat, perihal, sifat_surat, draft_by, status_workflow)
                     VALUES ('$nomor_urut_input', '$nomor_surat_full', '$perihal', 'Biasa', '$user_id_sekarang', 'Dialokasikan')";

    if (mysqli_query($koneksi, $query_insert)) {
        set_nomor_flash(
            'success',
            'Nomor surat berhasil dialokasikan',
            'Nomor surat Anda: ' . $nomor_surat_full,
            'Gunakan nomor ini pada dokumen Word, lalu lengkapi dan unggah dokumen melalui menu Surat Keluar.'
        );
        redirect_ambil_nomor();
    } else {
        set_nomor_flash('danger', 'Gagal mengambil nomor', 'Terjadi kesalahan sistem saat menyimpan alokasi nomor. Silakan coba lagi atau hubungi Admin TU.');
        redirect_ambil_nomor();
    }
}

$flash_nomor = $_SESSION['nomor_flash'] ?? null;
unset($_SESSION['nomor_flash']);

if (isset($_GET['suggest']) && (int)$_GET['suggest'] > 0) {
    $rekomendasi_nomor = (int)$_GET['suggest'];
}

include '../layouts/header.php'; // Sesuaikan path header
?>

<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">

<style>
:root {
    --nomor-primary: #4A70A9;
    --nomor-primary-dark: #3b5a87;
    --nomor-soft: #eef4fb;
    --nomor-border: #dde6f2;
    --nomor-muted: #64748b;
    --nomor-ink: #152033;
}

.nomor-page-title {
    display: flex;
    align-items: center;
    gap: 12px;
}
.nomor-title-icon {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--nomor-primary), #6b8fc8);
    color: #fff;
    box-shadow: 0 10px 22px rgba(74,112,169,.22);
}
.nomor-subtitle { font-size: .86rem; color: var(--nomor-muted); margin-top: 2px; }

.nomor-card {
    border: 0;
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(15, 25, 35, .075);
    overflow: hidden;
}
.nomor-card-header {
    background: #fff;
    padding: 18px 20px;
    border-bottom: 1px solid var(--nomor-border);
}
.nomor-helper-card {
    background: linear-gradient(135deg, #edf7ff 0%, #f8fbff 100%);
    border-left: 4px solid #0ea5e9;
}
.nomor-format-box {
    background: rgba(255,255,255,.9);
    border: 1px dashed #b9c8dc;
    border-radius: 12px;
    padding: 10px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .86rem;
    color: var(--nomor-ink);
}

.recommendation-pill {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff7ed;
    color: #9a3412;
    border: 1px solid #fed7aa;
    border-radius: 999px;
    padding: 7px 12px;
    font-size: .8rem;
    font-weight: 700;
}
.live-preview-card {
    border: 1px solid #c7d8ee;
    background: linear-gradient(135deg, #f8fbff, #eef6ff);
    border-radius: 16px;
    padding: 15px 16px;
    margin-bottom: 18px;
}
.live-preview-label {
    color: var(--nomor-muted);
    font-size: .76rem;
    text-transform: uppercase;
    letter-spacing: .6px;
    font-weight: 700;
}
.live-preview-number {
    color: var(--nomor-primary-dark);
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: .98rem;
    font-weight: 800;
    word-break: break-word;
    margin-top: 4px;
}
.live-preview-meta {
    margin-top: 8px;
    color: var(--nomor-muted);
    font-size: .78rem;
}

.ts-wrapper.form-select,
.ts-wrapper.single .ts-control {
    min-height: 38px;
}
.ts-control {
    border-color: #dee2e6 !important;
    border-radius: .5rem !important;
    box-shadow: none !important;
}
.ts-control:focus,
.ts-wrapper.focus .ts-control {
    border-color: var(--nomor-primary) !important;
    box-shadow: 0 0 0 .2rem rgba(74,112,169,.12) !important;
}
.ts-dropdown {
    border-radius: 14px;
    border: 1px solid var(--nomor-border);
    box-shadow: 0 14px 35px rgba(15,25,35,.12);
    overflow: hidden;
}
.ts-dropdown .option {
    padding: 10px 12px;
}
.ts-option-main {
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.ts-option-icon {
    width: 30px;
    height: 30px;
    border-radius: 10px;
    background: var(--nomor-soft);
    color: var(--nomor-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.ts-option-title { font-weight: 800; color: var(--nomor-ink); font-size: .88rem; }
.ts-option-sub { color: var(--nomor-muted); font-size: .76rem; margin-top: 1px; }

.status-badge-modern {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: .72rem;
    font-weight: 800;
}
.st-dialokasikan { background: #e0f2fe; color: #075985; }
.st-draft { background: #f1f5f9; color: #475569; }
.st-review { background: #fef3c7; color: #92400e; }
.st-revisi { background: #fee2e2; color: #991b1b; }
.st-approved { background: #dcfce7; color: #166534; }
.st-terkirim { background: #dbeafe; color: #1d4ed8; }
.st-batal { background: #fee2e2; color: #991b1b; }

.empty-state-modern {
    padding: 48px 24px;
    text-align: center;
    color: var(--nomor-muted);
}
.empty-state-icon {
    width: 76px;
    height: 76px;
    border-radius: 24px;
    background: var(--nomor-soft);
    color: var(--nomor-primary);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 14px;
}

.page-skeleton {
    display: block;
}
.page-ready {
    display: none;
}
body.page-loaded .page-skeleton {
    display: none;
}
body.page-loaded .page-ready {
    display: block;
}
.skel-line,
.skel-box,
.skel-pill {
    position: relative;
    overflow: hidden;
    background: #e9eef5;
}
.skel-line::after,
.skel-box::after,
.skel-pill::after {
    content: '';
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.65), transparent);
    animation: shimmer 1.15s infinite;
}
.skel-line { height: 14px; border-radius: 8px; }
.skel-box { height: 150px; border-radius: 18px; }
.skel-pill { height: 38px; border-radius: 999px; }
@keyframes shimmer { 100% { transform: translateX(100%); } }

.processing-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 25, 35, .28);
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
}
.processing-overlay.show { display: flex; }
.processing-box {
    background: #fff;
    border-radius: 18px;
    padding: 20px 22px;
    box-shadow: 0 18px 45px rgba(15,25,35,.18);
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
    color: var(--nomor-ink);
}

@media (max-width: 576px) {
    .nomor-page-title h4 { font-size: 1rem; }
    .nomor-title-icon { width: 40px; height: 40px; border-radius: 12px; }
    .live-preview-number { font-size: .88rem; }
}
</style>

<div class="processing-overlay" id="processingOverlay">
    <div class="processing-box">
        <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
        <span>Memproses alokasi nomor...</span>
    </div>
</div>

<div class="page-skeleton">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div style="width: 45%;">
            <div class="skel-line mb-2" style="width:70%;"></div>
            <div class="skel-line" style="width:45%;"></div>
        </div>
        <div class="skel-pill" style="width:150px;"></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="skel-box mb-4"></div>
            <div class="skel-box" style="height:420px;"></div>
        </div>
        <div class="col-lg-7">
            <div class="skel-box" style="height:560px;"></div>
        </div>
    </div>
</div>

<div class="page-ready">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div class="nomor-page-title">
            <div class="nomor-title-icon"><i class="fa-solid fa-ticket"></i></div>
            <div>
                <h4 class="fw-bold text-dark mb-0">Pengambilan Nomor Surat</h4>
                <div class="nomor-subtitle">Reservasi nomor surat keluar sebelum dokumen diunggah.</div>
            </div>
        </div>
        <span class="recommendation-pill">
            <i class="fa-solid fa-star"></i>
            Rekomendasi: <?= sprintf('%03d', $rekomendasi_nomor); ?>
        </span>
    </div>

    <?php if ($flash_nomor): ?>
        <?php
        $flash_class = match($flash_nomor['type']) {
            'success' => 'alert-success border-success',
            'warning' => 'alert-warning border-warning',
            'danger'  => 'alert-danger border-danger',
            default   => 'alert-info border-info'
        };
        $flash_icon = match($flash_nomor['type']) {
            'success' => 'fa-circle-check',
            'warning' => 'fa-triangle-exclamation',
            'danger'  => 'fa-circle-xmark',
            default   => 'fa-circle-info'
        };
        ?>
        <div class="alert <?= $flash_class; ?> border-start border-4 shadow-sm mb-4" role="alert">
            <div class="d-flex gap-3 align-items-start">
                <i class="fa-solid <?= $flash_icon; ?> mt-1"></i>
                <div>
                    <div class="fw-bold"><?= htmlspecialchars($flash_nomor['title']); ?></div>
                    <div><?= htmlspecialchars($flash_nomor['message']); ?></div>
                    <?php if (!empty($flash_nomor['detail'])): ?>
                        <div class="small mt-1 opacity-75"><?= htmlspecialchars($flash_nomor['detail']); ?></div>
                    <?php endif; ?>
                    <?php if ($flash_nomor['type'] === 'success'): ?>
                        <a href="surat_keluar.php" class="btn btn-sm btn-success fw-bold mt-3">
                            <i class="fa-solid fa-file-arrow-up me-1"></i> Lanjut Lengkapi Surat
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card nomor-card nomor-helper-card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-info-emphasis mb-2"><i class="fa-solid fa-book-open me-1"></i> Buku Saku Penomoran</h6>
                    <p class="small text-muted mb-2">Format penomoran surat keluar sekolah:</p>
                    <div class="nomor-format-box text-center mb-2">
                        [Kode] / SMKN4-[Unit] / [No.Urut] / [Bulan] / [Tahun]
                    </div>
                    <ul class="small text-muted ps-3 mb-0" style="font-size: 0.85rem;">
                        <li><strong>No.Urut:</strong> Sistem menyarankan urutan terakhir secara global untuk tahun berjalan.</li>
                        <li><strong>Preview:</strong> Nomor surat akan dirangkai otomatis sebelum tombol alokasi ditekan.</li>
                    </ul>
                </div>
            </div>

            <div class="card nomor-card mb-4">
                <div class="nomor-card-header">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-hand-pointer me-1"></i> Form Ambil Nomor</h6>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST" class="js-loading-form" data-loading-text="Mengalokasikan nomor...">
                        <input type="hidden" name="ambil_nomor" value="1">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Nomor Urut Surat <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">No.</span>
                                <input type="number" class="form-control fw-bold text-primary" id="nomor_urut_manual" name="nomor_urut_manual" value="<?= (int)$rekomendasi_nomor; ?>" required min="1">
                            </div>
                            <small class="text-warning fw-bold" style="font-size:0.8rem;">
                                <i class="fa-solid fa-triangle-exclamation"></i> Ubah hanya jika tidak sinkron dengan buku TU.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pilih Indeks / Klasifikasi <span class="text-danger">*</span></label>
                            <select name="kode_klasifikasi" id="kode_klasifikasi" class="form-select smart-select-klasifikasi" required>
                                <option value="">Cari atau pilih klasifikasi surat...</option>
                                <?php
                                $q_klasifikasi = mysqli_query($koneksi, "SELECT * FROM klasifikasi_surat ORDER BY kode ASC");
                                while ($k = mysqli_fetch_array($q_klasifikasi)) {
                                    $kode = htmlspecialchars($k['kode']);
                                    $ket  = htmlspecialchars($k['keterangan']);
                                    echo "<option value='{$kode}' data-keterangan='{$ket}'>{$kode} - {$ket}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Unit Pengolah / Pembuat <span class="text-danger">*</span></label>
                            <select name="keterangan_unit" id="keterangan_unit" class="form-select smart-select-unit" required>
                                <option value="">Cari atau pilih unit pengolah...</option>
                                <?php
                                $q_unit_pengolah = mysqli_query($koneksi, "SELECT * FROM unit_pengolah ORDER BY id ASC");
                                while ($u = mysqli_fetch_array($q_unit_pengolah)) {
                                    $kode_unit = htmlspecialchars($u['kode_unit']);
                                    $nama_unit = htmlspecialchars($u['nama_unit']);
                                    $selected = ($u['kode_unit'] == 'UMUM') ? 'selected' : '';
                                    echo "<option value='{$kode_unit}' data-nama='{$nama_unit}' $selected>{$kode_unit} - {$nama_unit}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="live-preview-card" aria-live="polite">
                            <div class="live-preview-label"><i class="fa-solid fa-eye me-1"></i> Preview Nomor Surat</div>
                            <div class="live-preview-number" id="previewNomorSurat">Pilih klasifikasi dan unit terlebih dahulu</div>
                            <div class="live-preview-meta" id="previewMeta">Nomor akan mengikuti format resmi sekolah.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Perihal Singkat <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="perihal" placeholder="Contoh: Undangan Rapat Komite" required>
                            <small class="text-muted" style="font-size:0.8rem;">Digunakan sebagai pengingat dokumen apa yang menggunakan nomor ini.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary fw-bold py-2 js-submit-btn">
                                <i class="fa-solid fa-bolt me-1"></i> Alokasikan Nomor Saya
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card nomor-card">
                <div class="nomor-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-ol me-1"></i> Riwayat Nomor Terakhir</h6>
                    <span class="badge bg-secondary">Tahun <?= $tahun_sekarang; ?></span>
                </div>

                <div class="card-body p-3 bg-light border-bottom">
                    <form method="GET" action="ambil_nomor.php" class="row g-2 align-items-center js-loading-form" data-loading-text="Menerapkan filter...">
                        <div class="col-md-3">
                            <select name="filter_bulan" class="form-select form-select-sm smart-select-simple">
                                <option value="">Semua Bulan</option>
                                <?php
                                $nama_bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                                for ($i = 1; $i <= 12; $i++) {
                                    $selected = (isset($_GET['filter_bulan']) && $_GET['filter_bulan'] == $i) ? 'selected' : '';
                                    echo "<option value='$i' $selected>".$nama_bulan[$i-1]."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="filter_klasifikasi" class="form-select form-select-sm smart-select-filter-klasifikasi">
                                <option value="">Semua Klasifikasi</option>
                                <?php
                                $q_klas_filter = mysqli_query($koneksi, "SELECT * FROM klasifikasi_surat ORDER BY kode ASC");
                                while ($kf = mysqli_fetch_array($q_klas_filter)) {
                                    $kode = htmlspecialchars($kf['kode']);
                                    $ket  = htmlspecialchars($kf['keterangan']);
                                    $selected = (isset($_GET['filter_klasifikasi']) && $_GET['filter_klasifikasi'] == $kf['kode']) ? 'selected' : '';
                                    echo "<option value='{$kode}' data-keterangan='{$ket}' $selected>{$kode} - {$ket}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="filter_pembuat" class="form-select form-select-sm smart-select-simple">
                                <option value="">Semua Pembuat</option>
                                <?php
                                $q_user_filter = mysqli_query($koneksi, "SELECT DISTINCT u.id, u.nama_lengkap FROM users u JOIN surat_keluar sk ON u.id = sk.draft_by ORDER BY u.nama_lengkap ASC");
                                while ($uf = mysqli_fetch_array($q_user_filter)) {
                                    $selected = (isset($_GET['filter_pembuat']) && $_GET['filter_pembuat'] == $uf['id']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($uf['id']) . "' $selected>" . htmlspecialchars($uf['nama_lengkap']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-grid gap-1 d-md-flex">
                            <button type="submit" class="btn btn-sm btn-secondary flex-grow-1 js-submit-btn" title="Terapkan Filter"><i class="fa-solid fa-filter"></i></button>
                            <a href="ambil_nomor.php" class="btn btn-sm btn-outline-danger flex-grow-1" title="Reset Filter"><i class="fa-solid fa-rotate-left"></i></a>
                        </div>
                    </form>
                </div>

                <div class="card-body p-0">
                    <?php
                    // Eksekusi Query dipindah ke atas agar bisa di-loop 2 kali
                    $filter_bulan = isset($_GET['filter_bulan']) ? mysqli_real_escape_string($koneksi, $_GET['filter_bulan']) : '';
                    $filter_klasifikasi = isset($_GET['filter_klasifikasi']) ? mysqli_real_escape_string($koneksi, $_GET['filter_klasifikasi']) : '';
                    $filter_pembuat = isset($_GET['filter_pembuat']) ? mysqli_real_escape_string($koneksi, $_GET['filter_pembuat']) : '';

                    $sql_where = "WHERE YEAR(sk.created_at) = '$tahun_sekarang'";

                    if ($filter_bulan != '') $sql_where .= " AND MONTH(sk.created_at) = '$filter_bulan'";
                    if ($filter_klasifikasi != '') { $sql_where .= " AND sk.nomor_surat LIKE '$filter_klasifikasi/%'";}
                    if ($filter_pembuat != '') $sql_where .= " AND sk.draft_by = '$filter_pembuat'";

                    $sql_limit = ($filter_bulan == '' && $filter_klasifikasi == '' && $filter_pembuat == '') ? "LIMIT 15" : "";

                    $query_riwayat = "
                        SELECT sk.nomor_surat, sk.perihal, sk.status_workflow, sk.created_at, u.nama_lengkap
                        FROM surat_keluar sk
                        JOIN users u ON sk.draft_by = u.id
                        $sql_where
                        ORDER BY sk.nomor_urut DESC
                        $sql_limit
                    ";
                    $q_riwayat = mysqli_query($koneksi, $query_riwayat);

                    // Masukkan data ke array
                    $data_riwayat = [];
                    if ($q_riwayat) {
                        while ($row = mysqli_fetch_array($q_riwayat)) {
                            $data_riwayat[] = $row;
                        }
                    }

                    function nomor_status_class($status) {
                        return match($status) {
                            'Dialokasikan' => 'st-dialokasikan',
                            'Draft'        => 'st-draft',
                            'Review'       => 'st-review',
                            'Revisi'       => 'st-revisi',
                            'Approved'     => 'st-approved',
                            'Terkirim'     => 'st-terkirim',
                            'Batal'        => 'st-batal',
                            default        => 'st-draft'
                        };
                    }
                    function nomor_status_icon($status) {
                        return match($status) {
                            'Dialokasikan' => 'fa-ticket',
                            'Draft'        => 'fa-file-pen',
                            'Review'       => 'fa-hourglass-half',
                            'Revisi'       => 'fa-rotate-left',
                            'Approved'     => 'fa-circle-check',
                            'Terkirim'     => 'fa-paper-plane',
                            'Batal'        => 'fa-ban',
                            default        => 'fa-circle-dot'
                        };
                    }

                    if (empty($data_riwayat)): ?>
                        <div class="empty-state-modern">
                            <div class="empty-state-icon"><i class="fa-regular fa-folder-open"></i></div>
                            <h6 class="fw-bold text-dark mb-1">Belum ada nomor surat ditemukan</h6>
                            <p class="mb-3 small">Riwayat akan tampil setelah nomor surat dialokasikan atau filter diubah.</p>
                            <a href="ambil_nomor.php" class="btn btn-sm btn-primary fw-bold"><i class="fa-solid fa-plus me-1"></i> Mulai dari Data Terbaru</a>
                        </div>
                    <?php else: ?>

                        <div class="table-responsive d-none d-md-block" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="25%" class="ps-3">No. Surat</th>
                                        <th width="32%">Perihal</th>
                                        <th width="18%">Pembuat</th>
                                        <th width="12%">Tanggal</th>
                                        <th width="13%">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data_riwayat as $r): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold font-monospace text-primary" style="font-size:0.9rem;">
                                            <?= htmlspecialchars($r['nomor_surat']); ?>
                                        </td>
                                        <td style="font-size:0.9rem;">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($r['perihal']); ?></div>
                                        </td>
                                        <td class="small text-muted"><i class="fa-solid fa-user me-1"></i> <?= htmlspecialchars($r['nama_lengkap']); ?></td>
                                        <td class="small text-muted"><?= !empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : '-'; ?></td>
                                        <td>
                                            <span class="status-badge-modern <?= nomor_status_class($r['status_workflow']); ?>">
                                                <i class="fa-solid <?= nomor_status_icon($r['status_workflow']); ?>"></i><?= htmlspecialchars($r['status_workflow']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-block d-md-none p-3 bg-light" style="max-height: 600px; overflow-y: auto;">
                            <?php foreach ($data_riwayat as $r): ?>
                            <div class="card border-0 shadow-sm rounded-4 mb-3">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom gap-2">
                                        <span class="fw-bold text-primary font-monospace text-truncate" style="font-size: 0.8rem;">
                                            <?= htmlspecialchars($r['nomor_surat']); ?>
                                        </span>
                                        <span class="status-badge-modern <?= nomor_status_class($r['status_workflow']); ?>" style="font-size: 0.66rem;">
                                            <i class="fa-solid <?= nomor_status_icon($r['status_workflow']); ?>"></i><?= htmlspecialchars($r['status_workflow']); ?>
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                            <i class="fa-solid fa-envelope-open-text fs-5"></i>
                                        </div>

                                        <div class="flex-grow-1 overflow-hidden">
                                            <h6 class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                                                <?= htmlspecialchars($r['perihal']); ?>
                                            </h6>
                                            <div class="text-muted small text-truncate">
                                                <i class="fa-solid fa-user-pen me-1"></i> <?= htmlspecialchars($r['nama_lengkap']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <i class="fa-regular fa-calendar me-1"></i> <?= !empty($r['created_at']) ? date('d/m/Y', strtotime($r['created_at'])) : '-'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light text-center py-2">
                    <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i> Gunakan filter dan kolom pencarian dropdown untuk mencari riwayat spesifik.</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.classList.add('page-loaded');

    const bulanRomawi = <?= json_encode(getBulanRomawi($bulan_sekarang)); ?>;
    const tahun = <?= json_encode((string)$tahun_sekarang); ?>;

    function formatUrut(value) {
        const n = parseInt(value || '0', 10);
        if (!n || n < 1) return '000';
        return String(n).padStart(3, '0');
    }

    function selectedValue(selector) {
        const el = document.querySelector(selector);
        return el ? el.value : '';
    }

    function selectedText(selector) {
        const el = document.querySelector(selector);
        if (!el) return '';
        const opt = el.options[el.selectedIndex];
        return opt ? opt.text : '';
    }

    function updatePreviewNomor() {
        const nomor = document.getElementById('nomor_urut_manual');
        const preview = document.getElementById('previewNomorSurat');
        const meta = document.getElementById('previewMeta');
        const kode = selectedValue('#kode_klasifikasi');
        const unit = selectedValue('#keterangan_unit');

        if (!preview || !meta) return;

        if (!kode || !unit) {
            preview.textContent = 'Pilih klasifikasi dan unit terlebih dahulu';
            meta.textContent = 'Nomor akan mengikuti format resmi sekolah.';
            return;
        }

        const nomorFull = kode + '/SMKN4-' + unit + '/' + formatUrut(nomor ? nomor.value : 0) + '/' + bulanRomawi + '/' + tahun;
        preview.textContent = nomorFull;
        meta.textContent = 'Format siap digunakan pada dokumen Word sebelum upload.';
    }

    function renderKlasifikasiOption(data, escape) {
        return '<div class="ts-option-main">' +
            '<div class="ts-option-icon"><i class="fa-solid fa-folder-tree"></i></div>' +
            '<div><div class="ts-option-title">' + escape(data.value || '-') + '</div>' +
            '<div class="ts-option-sub">' + escape(data.keterangan || data.text || '') + '</div></div>' +
        '</div>';
    }

    function renderUnitOption(data, escape) {
        return '<div class="ts-option-main">' +
            '<div class="ts-option-icon"><i class="fa-solid fa-building"></i></div>' +
            '<div><div class="ts-option-title">' + escape(data.value || '-') + '</div>' +
            '<div class="ts-option-sub">' + escape(data.nama || data.text || '') + '</div></div>' +
        '</div>';
    }

    if (window.TomSelect) {
        document.querySelectorAll('.smart-select-klasifikasi').forEach(function(el) {
            new TomSelect(el, {
                create: false,
                maxItems: 1,
                allowEmptyOption: true,
                searchField: ['text', 'value', 'keterangan'],
                placeholder: 'Cari atau pilih klasifikasi surat...',
                render: { option: renderKlasifikasiOption },
                onChange: updatePreviewNomor
            });
        });

        document.querySelectorAll('.smart-select-filter-klasifikasi').forEach(function(el) {
            new TomSelect(el, {
                create: false,
                maxItems: 1,
                allowEmptyOption: true,
                searchField: ['text', 'value', 'keterangan'],
                placeholder: 'Semua Klasifikasi',
                render: { option: renderKlasifikasiOption }
            });
        });

        document.querySelectorAll('.smart-select-unit').forEach(function(el) {
            new TomSelect(el, {
                create: false,
                maxItems: 1,
                allowEmptyOption: true,
                searchField: ['text', 'value', 'nama'],
                placeholder: 'Cari atau pilih unit pengolah...',
                render: { option: renderUnitOption },
                onChange: updatePreviewNomor
            });
        });

        document.querySelectorAll('.smart-select-simple').forEach(function(el) {
            new TomSelect(el, {
                create: false,
                maxItems: 1,
                allowEmptyOption: true
            });
        });
    }

    const nomorInput = document.getElementById('nomor_urut_manual');
    if (nomorInput) {
        nomorInput.addEventListener('input', updatePreviewNomor);
        nomorInput.addEventListener('blur', function () {
            if (parseInt(this.value || '0', 10) < 1) this.value = '1';
            updatePreviewNomor();
        });
    }

    document.querySelectorAll('.js-loading-form').forEach(function(form) {
        form.addEventListener('submit', function() {
            const btn = form.querySelector('.js-submit-btn, button[type="submit"]');
            const overlay = document.getElementById('processingOverlay');
            const text = form.dataset.loadingText || 'Memproses...';

            if (btn) {
                btn.disabled = true;
                btn.dataset.originalHtml = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' + text;
            }
            if (overlay && form.method.toLowerCase() === 'post') {
                overlay.classList.add('show');
            }
        });
    });

    updatePreviewNomor();
});
</script>

<?php include '../layouts/footer.php'; ?>
