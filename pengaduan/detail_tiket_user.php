<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$id_user = $_SESSION['user_id'];
$id_tiket = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Ambil data tiket dan pastikan tiket ini memang milik user yang login (Keamanan Data)
$query = mysqli_query($koneksi, "SELECT * FROM pengaduan WHERE id = '$id_tiket' AND id_user = '$id_user'");
$data = mysqli_fetch_array($query);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan atau Anda tidak memiliki akses.'); window.location.href='riwayat_pengaduan.php';</script>";
    exit;
}

// 2. Logika Konfirmasi Selesai oleh User
if (isset($_POST['konfirmasi_selesai'])) {
    mysqli_query($koneksi, "UPDATE pengaduan SET status = 'Closed' WHERE id = '$id_tiket'");
    echo "<script>alert('Terima kasih! Tiket telah ditutup secara permanen.'); window.location.href='detail_tiket_user.php?id=$id_tiket';</script>";
}

// Warna Badge
$status_badge = [
    'Open' => 'bg-secondary',
    'In Progress' => 'bg-primary',
    'Resolved' => 'bg-success',
    'Closed' => 'bg-dark'
][$data['status']];


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Tiket #<?= $data['no_tiket']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
<?php include '../layouts/header.php'; ?>
<div class="container mt-5 mb-5">
    <div class="mb-3">
        <a href="riwayat_pengaduan.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 rounded-3 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">Informasi Tiket #<?= $data['no_tiket']; ?></h5>
                    <span class="badge <?= $status_badge; ?> px-3 py-2"><?= $data['status']; ?></span>
                </div>
                <div class="card-body p-4">
                    <h4 class="fw-bold"><?= $data['subjek']; ?></h4>
                    <p class="text-muted small mb-4">Dilaporkan pada: <?= date('d F Y, H:i', strtotime($data['waktu_lapor'])); ?> WIB</p>
                    
                    <div class="mb-4">
                        <label class="fw-bold text-muted small text-uppercase">Deskripsi Kendala:</label>
                        <div class="p-3 bg-light rounded border">
                            <?= nl2br(htmlspecialchars($data['deskripsi'])); ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="fw-bold text-muted small text-uppercase">Lampiran Bukti:</label><br>
                        <a href="../uploads/pengaduan/<?= $data['file_lampiran']; ?>" target="_blank">
                            <img src="../uploads/pengaduan/<?= $data['file_lampiran']; ?>" class="img-thumbnail" style="max-height: 200px;">
                        </a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-3 border-start border-success border-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-success fw-bold"><i class="fa-solid fa-comment-dots me-2"></i> Tanggapan / Solusi dari Tim IT</h5>
                </div>
                <div class="card-body p-4">
                    <?php if($data['tanggapan_it']): ?>
                        <div class="p-3 rounded" style="background-color: #f0fff4; border: 1px solid #c6f6d5;">
                            <?= nl2br(htmlspecialchars($data['tanggapan_it'])); ?>
                        </div>
                        <p class="text-muted small mt-2">Diterima pada: <?= $data['waktu_selesai'] ? date('d/m/Y H:i', strtotime($data['waktu_selesai'])) : '-'; ?> WIB</p>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa-solid fa-hourglass-half fa-2x text-muted mb-2"></i>
                            <p class="text-muted">Tim IT sedang menganalisis laporan Anda. Mohon tunggu kabar selanjutnya.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if($data['status'] == 'Resolved'): ?>
                <div class="card-footer bg-light p-3 text-center">
                    <p class="small text-danger fw-bold">Apakah masalah ini sudah benar-benar terselesaikan?</p>
                    <form action="" method="POST">
                        <button type="submit" name="konfirmasi_selesai" class="btn btn-success fw-bold px-4">
                            <i class="fa-solid fa-check-double me-1"></i> Ya, Sudah Selesai (Tutup Tiket)
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Monitoring SLA</h6>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span>Level Gangguan:</span>
                        <span class="badge bg-danger"><?= $data['level_gangguan']; ?></span>
                    </div>
                    <hr>
                    <div class="timeline-sla small">
                        <div class="mb-3">
                            <i class="fa-solid fa-circle-check text-success"></i> <strong>Laporan Masuk</strong><br>
                            <span class="text-muted"><?= date('H:i', strtotime($data['waktu_lapor'])); ?> WIB</span>
                        </div>
                        <div class="mb-3">
                            <i class="fa-solid <?= $data['waktu_respon'] ? 'fa-circle-check text-success' : 'fa-circle-notch fa-spin text-muted'; ?>"></i> 
                            <strong>IT Merespon</strong><br>
                            <span class="text-muted"><?= $data['waktu_respon'] ? date('H:i', strtotime($data['waktu_respon'])) . ' WIB' : 'Menunggu...'; ?></span>
                        </div>
                        <div class="mb-0">
                            <i class="fa-solid <?= $data['waktu_selesai'] ? 'fa-circle-check text-success' : 'fa-circle-notch fa-spin text-muted'; ?>"></i> 
                            <strong>Solusi Diberikan</strong><br>
                            <span class="text-muted"><?= $data['waktu_selesai'] ? date('H:i', strtotime($data['waktu_selesai'])) . ' WIB' : 'Proses Perbaikan...'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning small">
                <i class="fa-solid fa-circle-info"></i> Tiket yang sudah berstatus <strong>Closed</strong> tidak dapat dibuka kembali. Jika ada masalah baru, silakan buat laporan baru.
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>
</body>
</html>