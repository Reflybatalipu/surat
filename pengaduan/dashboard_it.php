<?php
session_start();
include '../config/koneksi.php';

// Ambil ID IT yang sah dari pengaturan
$query_settings = mysqli_query($koneksi, "SELECT nilai_pengaturan FROM pengaturan WHERE nama_pengaturan = 'helpdesk_user_id'");
$data_it = mysqli_fetch_assoc($query_settings);
$id_it_pilihan_admin = $data_it['nilai_pengaturan'];

// Jika yang login bukan staf IT pilihan admin, TENDANG KELUAR!
if ($_SESSION['user_id'] != $id_it_pilihan_admin) {
    echo "<script>alert('Akses Ditolak! Anda bukan staf IT.'); window.location.href='../dashboard.php';</script>";
    exit;
}
include '../layouts/header.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>IT Service Desk Dashboard - SLA Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .bg-s1 { background-color: #721c24; color: white; } /* Kritis */
        .bg-s2 { background-color: #f8d7da; color: #721c24; } /* Tinggi */
        .bg-s3 { background-color: #fff3cd; color: #856404; } /* Sedang */
        .bg-s4 { background-color: #d1ecf1; color: #0c5460; } /* Rendah */
        .card-stat { border-left: 5px solid; }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fa-solid fa-gauge-high"></i> IT Service Desk Dashboard</h4>
        <span class="badge bg-dark p-2">SLA Status: Active (07.00 - 15.30 WIB)</span>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-stat border-danger shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Tiket Open (S1 & S2)</h6>
                    <h3 class="fw-bold text-danger">
                        <?php 
                        $q_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengaduan WHERE status='Open' AND level_gangguan IN ('S1','S2')");
                        echo mysqli_fetch_assoc($q_count)['total'];
                        ?>
                    </h3>
                </div>
            </div>
        </div>
        </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 text-primary fw-bold">Daftar Antrean Insiden (Berdasarkan Prioritas SLA)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tiket & Pelapor</th>
                            <th>Severity</th>
                            <th>Subjek Masalah</th>
                            <th>Waktu Masuk</th>
                            <th>Target Respon</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT p.*, u.nama_lengkap 
                                FROM pengaduan p 
                                JOIN users u ON p.id_user = u.id 
                                WHERE p.status != 'Closed'
                                ORDER BY FIELD(level_gangguan, 'S1', 'S2', 'S3', 'S4'), waktu_lapor ASC";
                        
                        $query = mysqli_query($koneksi, $sql);
                        while($data = mysqli_fetch_array($query)) {
                            
                            // Logika Warna Badge Level berdasarkan SLA
                            $badge_class = "";
                            $target_respon_txt = "";
                            
                            if($data['level_gangguan'] == 'S1') { 
                                $badge_class = "bg-s1"; 
                                $target_respon_txt = "15 Menit"; 
                            }
                            elseif($data['level_gangguan'] == 'S2') { 
                                $badge_class = "bg-s2"; 
                                $target_respon_txt = "30 Menit"; 
                            }
                            elseif($data['level_gangguan'] == 'S3') { 
                                $badge_class = "bg-s3"; 
                                $target_respon_txt = "1 Jam";
                            }
                            else { 
                                $badge_class = "bg-s4"; 
                                $target_respon_txt = "3 Jam";
                            }
                        ?>
                        <tr>
                            <td>
                                <span class="fw-bold"><?= $data['no_tiket']; ?></span><br>
                                <small class="text-muted"><?= $data['nama_lengkap']; ?></small>
                            </td>
                            <td>
                                <span class="badge <?= $badge_class; ?> px-3 py-2 w-100">
                                    <?= $data['level_gangguan']; ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= $data['subjek']; ?></strong><br>
                                <small class="text-truncate d-inline-block" style="max-width: 250px;">
                                    <?= $data['deskripsi']; ?>
                                </small>
                            </td>
                            <td>
                                <small><?= date('d/m/Y H:i', strtotime($data['waktu_lapor'])); ?></small>
                            </td>
                            <td>
                                <span class="text-danger fw-bold"><i class="fa-regular fa-clock"></i> <?= $target_respon_txt; ?></span>
                            </td>
                            <td>
                                <?php if($data['status'] == 'Open'): ?>
                                    <span class="badge bg-secondary">New / Open</span>
                                <?php else: ?>
                                    <span class="badge bg-primary">In Progress</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="proses_tiket.php?id=<?= $data['id']; ?>" class="btn btn-dark btn-sm shadow-sm">
                                    Kelola Tiket <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>

</body>
</html>