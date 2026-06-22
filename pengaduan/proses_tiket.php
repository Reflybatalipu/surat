<?php
session_start();
include '../config/koneksi.php';

// Pastikan hanya Tim IT yang bisa akses (Sesuaikan dengan sistem session kamu)
// if ($_SESSION['role'] != 'IT') { header("Location: index.php"); exit; }

// =========================================================
// 1. LOGIKA UPDATE STATUS & PENCATATAN WAKTU SLA
// =========================================================
if (isset($_POST['update_tiket'])) {
    $id_tiket = $_POST['id_tiket'];
    $status_baru = $_POST['status'];
    $tanggapan = mysqli_real_escape_string($koneksi, $_POST['tanggapan_it']);

    // Ambil data tiket saat ini untuk mengecek waktu
    $cek_query = mysqli_query($koneksi, "SELECT status, waktu_respon, waktu_selesai FROM pengaduan WHERE id = '$id_tiket'");
    $cek_data = mysqli_fetch_array($cek_query);

    $query_update = "UPDATE pengaduan SET status = '$status_baru', tanggapan_it = '$tanggapan'";

    // LOGIKA SLA: Catat Waktu Respon jika status berubah menjadi 'In Progress' pertama kali
    if ($status_baru == 'In Progress' && is_null($cek_data['waktu_respon'])) {
        $query_update .= ", waktu_respon = NOW()";
    }

    // LOGIKA SLA: Catat Waktu Selesai jika status berubah menjadi 'Resolved' pertama kali
    if ($status_baru == 'Resolved' && is_null($cek_data['waktu_selesai'])) {
        $query_update .= ", waktu_selesai = NOW()";
    }

    $query_update .= " WHERE id = '$id_tiket'";

    if (mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Status tiket berhasil diupdate!'); window.location.href='proses_tiket.php?id=$id_tiket';</script>";
    } else {
        echo "<script>alert('Gagal update tiket!'); window.history.back();</script>";
    }
}

// =========================================================
// 2. MENGAMBIL DATA TIKET UNTUK DITAMPILKAN
// =========================================================
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$query = mysqli_query($koneksi, "SELECT p.*, u.nama_lengkap FROM pengaduan p JOIN users u ON p.id_user = u.id WHERE p.id = '$id'");
$data = mysqli_fetch_array($query);

if (!$data) {
    echo "<script>alert('Tiket tidak ditemukan!'); window.location.href='dashboard_it.php';</script>";
    exit;
}

// Label warna SLA
$sev_badge = [
    'S1' => 'bg-danger',
    'S2' => 'bg-warning text-dark',
    'S3' => 'bg-info text-white',
    'S4' => 'bg-light text-dark border'
][$data['level_gangguan']];

include '../layouts/header.php'; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Proses Tiket IT - <?= $data['no_tiket']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<div class="container mt-4 mb-5">
    <div class="mb-3">
        <a href="dashboard_it.php" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detail Tiket: <?= $data['no_tiket']; ?></h5>
                    <span class="badge <?= $sev_badge; ?> fs-6 px-3 py-2">Level: <?= $data['level_gangguan']; ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted fw-bold">Pelapor</div>
                        <div class="col-sm-8">: <?= $data['nama_lengkap']; ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted fw-bold">Waktu Masuk</div>
                        <div class="col-sm-8">: <span class="text-danger fw-bold"><?= date('d M Y, H:i:s', strtotime($data['waktu_lapor'])); ?> WIB</span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted fw-bold">Subjek Masalah</div>
                        <div class="col-sm-8">: <strong><?= $data['subjek']; ?></strong></div>
                    </div>
                    <div class="mb-4">
                        <div class="text-muted fw-bold mb-2">Deskripsi Kronologi:</div>
                        <div class="p-3 bg-light border rounded">
                            <?= nl2br(htmlspecialchars($data['deskripsi'])); ?>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted fw-bold mb-2">Bukti / Screenshot Error:</div>
                        <a href="../uploads/pengaduan/<?= $data['file_lampiran']; ?>" target="_blank">
                            <img src="../uploads/pengaduan/<?= $data['file_lampiran']; ?>" alt="Lampiran" class="img-fluid rounded border border-secondary p-1" style="max-height: 300px; object-fit: contain;">
                        </a>
                        <div class="small text-muted mt-1"><i class="fa-solid fa-magnifying-glass"></i> Klik gambar untuk memperbesar</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom border-danger">
                    <h6 class="mb-0 text-danger fw-bold"><i class="fa-solid fa-stopwatch"></i> SLA Monitor</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Waktu Dilaporkan:
                            <span class="fw-bold"><?= date('H:i:s', strtotime($data['waktu_lapor'])); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Waktu IT Merespon:
                            <?php if($data['waktu_respon']): ?>
                                <span class="fw-bold text-primary"><?= date('H:i:s', strtotime($data['waktu_respon'])); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Belum Direspon</span>
                            <?php endif; ?>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Waktu Terselesaikan:
                            <?php if($data['waktu_selesai']): ?>
                                <span class="fw-bold text-success"><?= date('H:i:s', strtotime($data['waktu_selesai'])); ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Belum Selesai</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow border-0 border-top border-primary border-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary fw-bold"><i class="fa-solid fa-screwdriver-wrench"></i> Tindakan Tim IT</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="id_tiket" value="<?= $data['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="fw-bold form-label">Ubah Status Tiket</label>
                            <select name="status" class="form-select form-select-lg border-primary" required>
                                <option value="Open" <?= ($data['status'] == 'Open') ? 'selected' : ''; ?>>Open (Baru)</option>
                                <option value="In Progress" <?= ($data['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress (Sedang Dikerjakan)</option>
                                <option value="Resolved" <?= ($data['status'] == 'Resolved') ? 'selected' : ''; ?>>Resolved (Selesai/Telah Diperbaiki)</option>
                                <option value="Closed" <?= ($data['status'] == 'Closed') ? 'selected' : ''; ?>>Closed (Tutup Tiket)</option>
                            </select>
                            <small class="text-muted d-block mt-1">Mengubah ke <strong>In Progress</strong> akan mencatat <em>Waktu Respon</em>. Mengubah ke <strong>Resolved</strong> akan mencatat <em>Waktu Selesai</em>.</small>
                        </div>

                        <div class="mb-4">
                            <label class="fw-bold form-label">Tanggapan / Solusi / Catatan IT</label>
                            <textarea name="tanggapan_it" class="form-control" rows="5" placeholder="Contoh: Database telah direstart. Layanan TTE sudah kembali normal."><?= htmlspecialchars($data['tanggapan_it'] ?? ''); ?></textarea>
                            <small class="text-muted">Tanggapan ini akan bisa dibaca oleh pengguna (pelapor).</small>
                        </div>

                        <button type="submit" name="update_tiket" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Simpan Perubahan & Update
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>