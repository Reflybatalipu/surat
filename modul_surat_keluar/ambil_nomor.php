<?php
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
    return $romawi[$bulan];
}

// ========================================================
// 1. CARI REKOMENDASI NOMOR GLOBAL (AGENDA TUNGGAL)
// ========================================================
$query_max_saran = mysqli_query($koneksi, "SELECT MAX(nomor_urut) AS urut_terakhir FROM surat_keluar WHERE YEAR(created_at) = '$tahun_sekarang'");
$data_max_saran = mysqli_fetch_assoc($query_max_saran);
$rekomendasi_nomor = ($data_max_saran['urut_terakhir'] != null) ? $data_max_saran['urut_terakhir'] + 1 : 1;


// ========================================================
// PROSES PENGAMBILAN NOMOR (SISTEM RESERVASI)
// ========================================================
if (isset($_POST['ambil_nomor'])) {
    $kode_klasifikasi = mysqli_real_escape_string($koneksi, $_POST['kode_klasifikasi']);
    $keterangan_unit  = mysqli_real_escape_string($koneksi, $_POST['keterangan_unit']); 
    $perihal          = mysqli_real_escape_string($koneksi, $_POST['perihal']);
    $nomor_urut_input = (int)$_POST['nomor_urut_manual']; 
    
    // 💡 VALIDASI ANTI-DUPLIKAT (Cek Global Tahun Berjalan)
    $cek_duplikat = mysqli_query($koneksi, "SELECT id, nomor_surat, status_workflow FROM surat_keluar WHERE nomor_urut = '$nomor_urut_input' AND YEAR(created_at) = '$tahun_sekarang'");
    
    if (mysqli_num_rows($cek_duplikat) > 0) {
        $data_bentrok = mysqli_fetch_assoc($cek_duplikat);
        echo "<script>
            alert('⚠️ GAGAL! Nomor Urut $nomor_urut_input sudah dipakai oleh surat lain di tahun $tahun_sekarang.\\n\\nSurat Bentrok: {$data_bentrok['nomor_surat']}\\nStatus: {$data_bentrok['status_workflow']}\\n\\nSilakan gunakan nomor urut yang lebih tinggi.');
            window.history.back();
        </script>";
        exit; // Hentikan proses
    }
    
    // Jika aman (tidak duplikat), format angka jadi 3 digit (Contoh: 5 jadi 005)
    $format_urut = sprintf("%03d", $nomor_urut_input);
    $romawi = getBulanRomawi($bulan_sekarang);

    // 💡 RAKIT NOMOR SURAT FULL (Sesuai kesepakatan SMKN 4)
    $nomor_surat_full = "$kode_klasifikasi/SMKN4-$keterangan_unit/$format_urut/$romawi/$tahun_sekarang";

    // INSERT ke Database
    $query_insert = "INSERT INTO surat_keluar (nomor_urut, nomor_surat, perihal, sifat_surat, draft_by, status_workflow) 
                     VALUES ('$nomor_urut_input', '$nomor_surat_full', '$perihal', 'sifat_surat', '$user_id_sekarang', 'Dialokasikan')";

    if (mysqli_query($koneksi, $query_insert)) {
        echo "<script>
            alert('✅ BERHASIL! Nomor Surat Anda:\\n\\n$nomor_surat_full\\n\\nSilakan gunakan nomor ini di dokumen Word Anda, lalu upload di menu Surat Keluar.');
            window.location.href = 'surat_keluar.php';
        </script>";
    } else {
        echo "<script>alert('Gagal mengambil nomor! Kesalahan sistem.');</script>";
    }
}

include '../layouts/header.php'; // Sesuaikan path header
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-ticket me-2 text-primary"></i> Pengambilan Nomor Surat</h4>
</div>

<div class="row">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-info bg-opacity-10 border-start border-4 border-info">
            <div class="card-body">
                <h6 class="fw-bold text-info-emphasis mb-2"><i class="fa-solid fa-book-open me-1"></i> Buku Saku Penomoran</h6>
                <p class="small text-muted mb-2">Format penomoran surat keluar sekolah kita adalah:</p>
                <div class="bg-white p-2 rounded border text-center fw-bold text-dark mb-2 font-monospace" style="font-size:0.85rem;">
                    [Kode] / SMKN4-[Unit] / [No.Urut] / [Bulan] / [Tahun]
                </div>
                <ul class="small text-muted ps-3 mb-0" style="font-size: 0.85rem;">
                    <li><strong>No.Urut:</strong> Sistem otomatis melanjutkan urutan terakhir secara global. Bisa diedit jika buku TU melompat.</li>
                </ul>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-hand-pointer me-1"></i> Form Ambil Nomor</h6>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nomor Urut Surat <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">No.</span>
                            <input type="number" class="form-control fw-bold text-primary" name="nomor_urut_manual" value="<?= $rekomendasi_nomor; ?>" required min="1">
                        </div>
                        <small class="text-warning fw-bold" style="font-size:0.8rem;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Sistem menyarankan angka di atas. Ubah hanya jika tidak sinkron dengan TU.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Pilih Indeks / Klasifikasi <span class="text-danger">*</span></label>
                        <select name="kode_klasifikasi" class="form-select" required>
                            <option value="">-- Cari dan Pilih Kode --</option>
                            <?php
                            $q_klasifikasi = mysqli_query($koneksi, "SELECT * FROM klasifikasi_surat ORDER BY kode ASC");
                            while ($k = mysqli_fetch_array($q_klasifikasi)) {
                                echo "<option value='{$k['kode']}'>{$k['kode']} - {$k['keterangan']}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Unit Pengolah / Pembuat <span class="text-danger">*</span></label>
                        <select name="keterangan_unit" class="form-select" required>
                            <option value="">-- Cari dan Pilih Unit --</option>
                            <?php
                            $q_unit_pengolah = mysqli_query($koneksi, "SELECT * FROM unit_pengolah ORDER BY id ASC");
                            while ($u = mysqli_fetch_array($q_unit_pengolah)) {
                                // Buat UMUM menjadi default selected jika ada
                                $selected = ($u['kode_unit'] == 'UMUM') ? 'selected' : '';
                                echo "<option value='{$u['kode_unit']}' $selected>{$u['kode_unit']} - {$u['nama_unit']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">Perihal Singkat <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="perihal" placeholder="Contoh: Undangan Rapat Komite" required>
                        <small class="text-muted" style="font-size:0.8rem;">Digunakan sebagai pengingat dokumen apa yang menggunakan nomor ini.</small>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" name="ambil_nomor" class="btn btn-primary fw-bold py-2">
                            <i class="fa-solid fa-bolt me-1"></i> Alokasikan Nomor Saya
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-list-ol me-1"></i> Riwayat Nomor Terakhir</h6>
                <span class="badge bg-secondary">Tahun <?= $tahun_sekarang; ?></span>
            </div>
            
            <div class="card-body p-3 bg-light border-bottom">
                <form method="GET" action="ambil_nomor.php" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <select name="filter_bulan" class="form-select form-select-sm">
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
                        <select name="filter_klasifikasi" class="form-select form-select-sm">
                            <option value="">Semua Klasifikasi</option>
                            <?php
                            $q_klas_filter = mysqli_query($koneksi, "SELECT * FROM klasifikasi_surat ORDER BY kode ASC");
                            while ($kf = mysqli_fetch_array($q_klas_filter)) {
                                $selected = (isset($_GET['filter_klasifikasi']) && $_GET['filter_klasifikasi'] == $kf['kode']) ? 'selected' : '';
                                echo "<option value='{$kf['kode']}' $selected>{$kf['kode']} - {$kf['keterangan']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="filter_pembuat" class="form-select form-select-sm">
                            <option value="">Semua Pembuat</option>
                            <?php
                            $q_user_filter = mysqli_query($koneksi, "SELECT DISTINCT u.id, u.nama_lengkap FROM users u JOIN surat_keluar sk ON u.id = sk.draft_by ORDER BY u.nama_lengkap ASC");
                            while ($uf = mysqli_fetch_array($q_user_filter)) {
                                $selected = (isset($_GET['filter_pembuat']) && $_GET['filter_pembuat'] == $uf['id']) ? 'selected' : '';
                                echo "<option value='{$uf['id']}' $selected>{$uf['nama_lengkap']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid gap-1 d-md-flex">
                        <button type="submit" class="btn btn-sm btn-secondary flex-grow-1" title="Terapkan Filter"><i class="fa-solid fa-filter"></i></button>
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
                if ($filter_klasifikasi != '') $sql_where .= " AND sk.klasifikasi = '$filter_klasifikasi'";
                if ($filter_pembuat != '') $sql_where .= " AND sk.draft_by = '$filter_pembuat'";

                $sql_limit = ($filter_bulan == '' && $filter_klasifikasi == '' && $filter_pembuat == '') ? "LIMIT 15" : "";

                $query_riwayat = "
                    SELECT sk.nomor_surat, sk.perihal, sk.status_workflow, u.nama_lengkap 
                    FROM surat_keluar sk 
                    JOIN users u ON sk.draft_by = u.id 
                    $sql_where 
                    ORDER BY sk.nomor_urut DESC 
                    $sql_limit
                ";
                $q_riwayat = mysqli_query($koneksi, $query_riwayat);

                // Masukkan data ke array
                $data_riwayat = [];
                while ($row = mysqli_fetch_array($q_riwayat)) {
                    $data_riwayat[] = $row;
                }

                if (empty($data_riwayat)): ?>
                    <div class='text-center py-4 text-muted'><i class='fa-solid fa-folder-open fs-3 d-block mb-2 text-light'></i> Data surat tidak ditemukan.</div>
                <?php else: ?>

                    <div class="table-responsive d-none d-md-block" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th width="25%" class="ps-3">No. Surat</th>
                                    <th width="35%">Perihal</th>
                                    <th width="20%">Pembuat</th>
                                    <th width="20%">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data_riwayat as $r): 
                                    $badge_warna = 'bg-secondary';
                                    if ($r['status_workflow'] == 'Dialokasikan') $badge_warna = 'bg-info text-dark';
                                    if ($r['status_workflow'] == 'Terkirim') $badge_warna = 'bg-success';
                                    if ($r['status_workflow'] == 'Batal') $badge_warna = 'bg-danger';
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold font-monospace text-primary" style="font-size:0.9rem;">
                                        <?= $r['nomor_surat']; ?>
                                    </td>
                                    <td style="font-size:0.9rem;"><?= $r['perihal']; ?></td>
                                    <td class="small text-muted"><i class="fa-solid fa-user me-1"></i> <?= $r['nama_lengkap']; ?></td>
                                    <td><span class="badge <?= $badge_warna; ?>"><?= $r['status_workflow']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-block d-md-none p-3 bg-light" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($data_riwayat as $r): 
                            $badge_warna = 'bg-secondary';
                            if ($r['status_workflow'] == 'Dialokasikan') $badge_warna = 'bg-info text-dark';
                            if ($r['status_workflow'] == 'Terkirim') $badge_warna = 'bg-success';
                            if ($r['status_workflow'] == 'Batal') $badge_warna = 'bg-danger';
                        ?>
                        
                        <div class="card border-0 shadow-sm rounded-4 mb-3">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <span class="fw-bold text-primary font-monospace" style="font-size: 0.8rem;">
                                        <?= $r['nomor_surat']; ?>
                                    </span>
                                    <span class="badge <?= $badge_warna; ?>" style="font-size: 0.7rem;">
                                        <?= $r['status_workflow']; ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                        <i class="fa-solid fa-envelope-open-text fs-5"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                                            <?= $r['perihal']; ?>
                                        </h6>
                                        <div class="text-muted small text-truncate">
                                            <i class="fa-solid fa-user-pen me-1"></i> <?= $r['nama_lengkap']; ?>
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
                <small class="text-muted"><i class="fa-solid fa-circle-info me-1"></i> Gunakan filter di atas untuk mencari riwayat spesifik.</small>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>