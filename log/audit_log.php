<?php
session_start();
include '../config/koneksi.php';

// 🛡️ Cek Login & Hak Akses
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    header("Location: ../login.php");
    exit;
}

if ($_SESSION['nama_role'] != 'Admin_TU' && $_SESSION['nama_role'] != 'Superadmin' && $_SESSION['nama_role'] != 'Kepala_Sekolah') {
    echo "<script>alert('Akses Ditolak! Anda tidak memiliki izin untuk melihat halaman ini.'); history.back();</script>";
    exit;
}

// 🔍 Logika Pencarian & Filter Card
$keyword = "";
$where_clause = "";
if (isset($_GET['cari'])) {
    $keyword = mysqli_real_escape_string($koneksi, $_GET['cari']);
    // Alias tabel 'audit_logs' di bawah adalah 'a', jadi kita pakai 'a.action'
    $where_clause = " WHERE u.nama_lengkap LIKE '%$keyword%' 
                      OR u.nip LIKE '%$keyword%' 
                      OR a.action LIKE '%$keyword%' 
                      OR a.table_name LIKE '%$keyword%'";
}

// ========================================================
// 1. AMBIL DATA AUDIT LOG & SIMPAN KE ARRAY
// ========================================================
$sql = "SELECT a.*, u.nama_lengkap, u.nip 
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        $where_clause
        ORDER BY a.created_at DESC LIMIT 500";
        
$query = mysqli_query($koneksi, $sql); 
$data_logs = [];
while ($row = mysqli_fetch_array($query)) {
    $data_logs[] = $row;
}

include '../layouts/header.php'; 
?>

<style>
    /* Efek Hover untuk Kartu Filter */
    .filter-card {
        transition: all 0.2s ease-in-out;
        border: 1px solid transparent;
        cursor: pointer;
    }
    .filter-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
        border-color: #0d6efd !important; /* Biru Primary Bootstrap */
    }
    
    /* Sticky Header untuk Tabel */
    .table-responsive {
        max-height: 60vh;
        overflow-y: auto;
    }
    .table-responsive thead th {
        position: sticky;
        top: 0;
        background-color: #f8f9fa;
        z-index: 10;
        box-shadow: 0 2px 3px -1px rgba(0,0,0,0.1);
    }
    
    .user-agent { 
        font-size: 0.8rem; 
        color: #6c757d; 
        max-width: 180px; 
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis; 
    }
</style>

<div class="mb-4">
    <h4 class="fw-bold text-dark mb-1">
        <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i> Rekaman Aktivitas (CCTV)
    </h4>
    <p class="text-muted small">Pantau seluruh jejak dan tindakan pengguna di dalam sistem untuk keamanan.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="audit_log.php?cari=LOGIN" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 filter-card rounded-3">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3 text-primary">
                        <i class="fa-solid fa-right-to-bracket fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark mb-0">Filter Log</div>
                        <div class="small text-muted">Akses & Login</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="audit_log.php?cari=SURAT" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 filter-card rounded-3">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="bg-info bg-opacity-10 p-3 rounded-3 me-3 text-info">
                        <i class="fa-solid fa-envelope-open-text fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark mb-0">Filter Log</div>
                        <div class="small text-muted">Dokumen Surat</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="audit_log.php" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 filter-card rounded-3">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded-3 me-3 text-success">
                        <i class="fa-solid fa-list-check fs-4"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark mb-0">Tampilkan</div>
                        <div class="small text-muted">Semua Aktivitas</div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-3">
        <form action="" method="GET">
            <div class="input-group" style="max-width: 500px;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                <input type="text" name="cari" class="form-control border-start-0 ps-0 shadow-none" placeholder="Cari nama pengguna, NIP, atau jenis aksi..." value="<?= htmlspecialchars($keyword) ?>">
                <?php if($keyword != ''): ?>
                    <a href="audit_log.php" class="btn btn-light border text-danger" title="Reset Pencarian"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary px-4 fw-bold">Cari Data</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0 p-md-3"> 
        
        <?php if (empty($data_logs)): ?>
            <div class='text-center py-5 text-muted'>
                <i class='fa-solid fa-folder-open fs-2 mb-3 d-block text-light'></i> 
                Data log aktivitas tidak ditemukan.
            </div>
        <?php else: ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0" id="tableAudit">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="ps-4">No</th>
                            <th width="15%">Waktu</th>
                            <th width="20%">Pengguna</th>
                            <th width="15%">Aksi</th>
                            <th width="20%">Modul & Target</th>
                            <th width="25%" class="pe-4">IP & Perangkat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($data_logs as $data):
                            // Pewarnaan Badge berdasarkan jenis aksi
                            $action = $data['action'];
                            $badge_class = 'bg-secondary';
                            
                            if (strpos($action, 'LOGIN') !== false || strpos($action, 'ACTIVATE') !== false) {
                                $badge_class = 'bg-info text-dark';
                            } elseif (strpos($action, 'CREATE') !== false || strpos($action, 'ADD') !== false) {
                                $badge_class = 'bg-success';
                            } elseif (strpos($action, 'UPDATE') !== false || strpos($action, 'EDIT') !== false) {
                                $badge_class = 'bg-primary';
                            } elseif (strpos($action, 'DELETE') !== false || strpos($action, 'REJECT') !== false) {
                                $badge_class = 'bg-danger';
                            } elseif (strpos($action, 'APPROVE') !== false || strpos($action, 'FINISH') !== false) {
                                $badge_class = 'bg-success';
                            } elseif (strpos($action, 'SEND') !== false || strpos($action, 'SUBMIT') !== false) {
                                $badge_class = 'bg-warning text-dark';
                            }
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-muted"><?= $no++; ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= date('d M Y', strtotime($data['created_at'])); ?></div>
                                <div class="small text-muted"><?= date('H:i:s', strtotime($data['created_at'])); ?> WIB</div>
                            </td>
                            <td>
                                <div class="text-dark fw-bold"><?= $data['nama_lengkap'] ?: 'Sistem / Guest'; ?></div>
                                <div class="small text-muted">NIP: <?= htmlspecialchars($data['nip'] ?? '-'); ?></div>
                            </td>
                            <td>
                                <span class="badge <?= $badge_class; ?> px-2 py-1 shadow-sm">
                                    <?= str_replace('_', ' ', $action); ?>
                                </span>
                            </td>
                            <td>
                                <div class="text-uppercase small fw-bold text-muted"><?= $data['table_name']; ?></div>
                                <div class="small">Record ID: <span class="badge bg-light text-dark border"><?= $data['record_id']; ?></span></div>
                            </td>
                            <td class="pe-4">
                                <div class="small text-dark"><i class="fa-solid fa-globe me-1 text-muted"></i> <?= $data['ip_address']; ?></div>
                                <div class="user-agent mt-1" title="<?= htmlspecialchars($data['user_agent']); ?>">
                                    <?= htmlspecialchars($data['user_agent']); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none bg-light p-3" style="max-height: 75vh; overflow-y: auto;">
                <?php foreach ($data_logs as $data): 
                    // Pewarnaan Badge dan Border (Konsisten dengan Desktop)
                    $action = $data['action'];
                    $badge_class = 'bg-secondary';
                    $border_color = 'border-secondary';
                    
                    if (strpos($action, 'LOGIN') !== false || strpos($action, 'ACTIVATE') !== false) {
                        $badge_class = 'bg-info text-dark'; $border_color = 'border-info';
                    } elseif (strpos($action, 'CREATE') !== false || strpos($action, 'ADD') !== false) {
                        $badge_class = 'bg-success'; $border_color = 'border-success';
                    } elseif (strpos($action, 'UPDATE') !== false || strpos($action, 'EDIT') !== false) {
                        $badge_class = 'bg-primary'; $border_color = 'border-primary';
                    } elseif (strpos($action, 'DELETE') !== false || strpos($action, 'REJECT') !== false) {
                        $badge_class = 'bg-danger'; $border_color = 'border-danger';
                    } elseif (strpos($action, 'APPROVE') !== false || strpos($action, 'FINISH') !== false) {
                        $badge_class = 'bg-success'; $border_color = 'border-success';
                    } elseif (strpos($action, 'SEND') !== false || strpos($action, 'SUBMIT') !== false) {
                        $badge_class = 'bg-warning text-dark'; $border_color = 'border-warning';
                    }
                ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3 border-start border-4 <?= $border_color; ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="badge <?= $badge_class; ?> shadow-sm"><?= str_replace('_', ' ', $action); ?></span>
                            <div class="text-end">
                                <div class="fw-bold text-dark" style="font-size: 0.8rem;"><?= date('d M Y', strtotime($data['created_at'])); ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?= date('H:i:s', strtotime($data['created_at'])); ?> WIB</div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <h6 class="mb-1 fw-bold text-dark"><i class="fa-solid fa-user-shield text-muted me-1"></i> <?= $data['nama_lengkap'] ?: 'Sistem / Guest'; ?></h6>
                            <div class="text-muted small mb-2 ms-4">NIP: <?= htmlspecialchars($data['nip'] ?? '-'); ?></div>
                            
                            <div class="bg-light p-2 rounded border small">
                                <div><span class="text-muted">Target Modul:</span> <span class="fw-bold text-uppercase"><?= $data['table_name']; ?></span></div>
                                <div><span class="text-muted">Record ID:</span> <?= $data['record_id']; ?></div>
                            </div>
                        </div>

                        <div class="mt-2 pt-2 border-top small text-muted text-truncate" title="<?= htmlspecialchars($data['user_agent']); ?>">
                            <i class="fa-solid fa-globe me-1"></i> <?= $data['ip_address']; ?> | <?= htmlspecialchars($data['user_agent']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>