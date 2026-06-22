<?php
// Masukkan koneksi database
require_once 'config/koneksi.php';

// Ambil ID dari URL
$id_surat = isset($_GET['id']) ? mysqli_real_escape_string($koneksi, $_GET['id']) : 0;

// Query untuk mengambil data surat dan siapa yang menyetujui (Kepala Sekolah)
$query = "SELECT sk.*, u.nama_lengkap as nama_kepsek, u.nip as nip_kepsek 
          FROM surat_keluar sk 
          LEFT JOIN users u ON sk.approved_by = u.id 
          WHERE sk.id = '$id_surat' 
          AND sk.status_workflow IN ('Approved', 'Terkirim') 
          AND sk.is_tte = 1";

$result = mysqli_query($koneksi, $query);
$data = mysqli_fetch_assoc($result);

// Tentukan apakah surat valid
$is_valid = ($data) ? true : false;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Dokumen Elektronik - SIMPERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .validation-card {
            max-width: 500px;
            margin: 50px auto;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header-valid {
            background: linear-gradient(135deg, #198754 0%, #20c997 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .header-invalid {
            background: linear-gradient(135deg, #dc3545 0%, #f8d7da 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
        }
        .detail-row {
            border-bottom: 1px dashed #eee;
            padding: 12px 0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }
        .detail-value {
            font-weight: 600;
            color: #2b2b2b;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card validation-card border-0">
        
        <?php if ($is_valid): ?>
            <div class="header-valid">
                <div class="icon-circle">
                    <i class="fa-solid fa-check text-white"></i>
                </div>
                <h4 class="fw-bold mb-0">Dokumen Valid</h4>
                <p class="mb-0 small opacity-75">Tanda Tangan Elektronik Terverifikasi</p>
            </div>
            
            <div class="card-body p-4 bg-white">
                <div class="text-center mb-4">
                    <img src="assets/img/logo.png" alt="Logo Instansi" style="max-height: 60px; opacity: 0.8;" onerror="this.style.display='none'">
                    <h6 class="mt-2 fw-bold text-dark">SMK NEGERI 4 GORONTALO</h6>
                </div>

               <div class="detail-row">
                    <div class="detail-label">Nomor Surat</div>
                    <div class="detail-value fs-5 text-primary"><?= htmlspecialchars($data['nomor_surat'] ?? '-'); ?></div>
                </div>
                
                <div class="detail-row">
                    <div class="detail-label">Perihal</div>
                    <div class="detail-value"><?= htmlspecialchars($data['perihal'] ?? '-'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Tujuan</div>
                    <div class="detail-value"><?= htmlspecialchars($data['tujuan'] ?? '-'); ?></div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">Tanggal Disetujui</div>
                    <div class="detail-value">
                        <i class="fa-regular fa-calendar-check text-success me-1"></i> 
                        <?php 
                            // Cek jika tanggal_keluar ada dan tidak kosong/null
                            if (!empty($data['tanggal_keluar']) && $data['tanggal_keluar'] != '0000-00-00') {
                                echo date('d F Y', strtotime($data['tanggal_keluar']));
                            } else {
                                // Jika kosong, tampilkan tanggal surat ini dibuat (created_at)
                                echo date('d F Y', strtotime($data['created_at'] ?? date('Y-m-d')));
                            }
                        ?>
                    </div>
                </div>

                <div class="detail-row bg-light p-3 rounded mt-3 text-center border">
                    <div class="detail-label">Ditandatangani Secara Elektronik Oleh:</div>
                    <div class="detail-value text-success mt-1">
                        <i class="fa-solid fa-signature"></i> <?= htmlspecialchars($data['nama_kepsek'] ?? 'Tidak Diketahui'); ?><br>
                        <small class="text-muted">NIP. <?= htmlspecialchars($data['nip_kepsek'] ?? '-'); ?></small>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="uploads/surat_keluar/<?= htmlspecialchars($data['file_path']); ?>" target="_blank" class="btn btn-outline-primary w-100 fw-bold rounded-pill">
                        <i class="fa-solid fa-file-pdf me-2"></i> Lihat Dokumen Asli
                    </a>
                </div>
            </div>

        <?php else: ?>
            <div class="header-invalid">
                <div class="icon-circle">
                    <i class="fa-solid fa-xmark text-white"></i>
                </div>
                <h4 class="fw-bold mb-0">Dokumen Tidak Valid</h4>
                <p class="mb-0 small opacity-75">Data tidak ditemukan atau belum disetujui</p>
            </div>
            
            <div class="card-body p-4 text-center bg-white">
                <div class="alert alert-danger mb-4">
                    <i class="fa-solid fa-triangle-exclamation fs-3 mb-2 d-block"></i>
                    Maaf, dokumen yang Anda pindai tidak terdaftar di dalam sistem kami, atau dokumen tersebut telah dicabut/dibatalkan.
                </div>
                <p class="text-muted small">Pastikan Anda memindai QR Code dari dokumen resmi yang dikeluarkan oleh sistem SIMPERS.</p>
                
                <a href="dashboard.php" class="btn btn-secondary rounded-pill mt-2">Kembali ke Beranda</a>
            </div>
        <?php endif; ?>

        <div class="card-footer text-center bg-white border-top-0 pb-4 pt-0">
            <small class="text-muted" style="font-size: 0.7rem;">&copy; <?= date('Y'); ?> Sistem Manajemen Persuratan Sekolah (SIMPERS).<br>Digenerate secara sistem, tidak memerlukan cap basah.</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>