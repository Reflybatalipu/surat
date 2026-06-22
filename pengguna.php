<?php
session_start();
include 'config/koneksi.php';

// 🛡️ KEAMANAN RBAC: Hanya Admin_TU dan Kepala_Sekolah yang boleh akses
if (!isset($_SESSION['status_login']) || ($_SESSION['nama_role'] != 'Admin_TU' && $_SESSION['nama_role'] != 'Kepala_Sekolah')) {
    echo "<script>
        alert('Akses Ditolak! Anda tidak memiliki hak akses ke halaman Manajemen Pengguna.');
        location.href='index.php';
    </script>";
    exit;
}

// ========================================================
// 1. AMBIL MASTER DATA (ROLES & UNIT KERJA) UNTUK DROPDOWN
// ========================================================
$q_roles = mysqli_query($koneksi, "SELECT * FROM roles");
$daftar_roles = [];
while ($r = mysqli_fetch_assoc($q_roles)) {
    $daftar_roles[] = $r;
}

$q_units = mysqli_query($koneksi, "SELECT * FROM unit_kerja");
$daftar_units = [];
while ($u = mysqli_fetch_assoc($q_units)) {
    $daftar_units[] = $u;
}

// ========================================================
// 2. AMBIL SEMUA DATA USER & SIMPAN KE ARRAY
// ========================================================
$query_users = mysqli_query($koneksi, 
    "SELECT users.*, roles.nama_role, unit_kerja.nama_unit 
     FROM users 
     JOIN roles ON users.role_id = roles.id 
     JOIN unit_kerja ON users.unit_id = unit_kerja.id 
     ORDER BY users.req_reset_pass DESC, users.id DESC"
);
$data_users = [];
while ($row = mysqli_fetch_assoc($query_users)) {
    $data_users[] = $row;
}

include 'layouts/header.php'; 
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <h4 class="fw-bold text-dark mb-0"><i class="fa-solid fa-users me-2 text-primary"></i> Manajemen Pengguna</h4>
    <button type="button" class="btn btn-primary shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah">
        <i class="fa-solid fa-plus me-1"></i> Tambah Pegawai
    </button>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-body p-0 p-md-4">
        
        <?php if (empty($data_users)): ?>
            <div class='text-center py-5 text-muted'>
                <i class='fa-solid fa-users-slash fs-1 d-block mb-3 text-light'></i> 
                Belum ada data pegawai.
            </div>
        <?php else: ?>

            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="ps-3">No</th>
                            <th width="15%">NIP</th>
                            <th width="20%">Nama Lengkap</th>
                            <th width="15%">Role Akses</th>
                            <th width="15%">Unit Kerja</th>
                            <th width="15%">Status Akun</th>
                            <th width="15%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($data_users as $data): 
                            // Logika Status
                            if ($data['is_active'] == 0) {
                                $badge_status = '<span class="badge bg-danger">Nonaktif / Mutasi</span>';
                            } elseif (empty($data['password_hash']) && empty($data['password'])) {
                                $badge_status = '<span class="badge bg-warning text-dark">Belum Aktivasi</span>';
                            } else {
                                $badge_status = '<span class="badge bg-success">Aktif</span>';
                            }

                            // Minta Reset Indikator
                            $is_minta_reset = (isset($data['req_reset_pass']) && $data['req_reset_pass'] == '1');
                            if ($is_minta_reset) {
                                $badge_status .= '<br><span class="badge bg-danger mt-1 shadow-sm"><i class="fa-solid fa-bell"></i> Minta Reset!</span>';
                            }
                        ?>
                        <tr class="<?= $is_minta_reset ? 'table-warning' : ''; ?>">
                            <td class="ps-3"><?= $no++; ?></td>
                            <td class="fw-bold text-primary font-monospace" style="font-size:0.9rem;"><?= $data['nip']; ?></td>
                            <td class="fw-bold text-dark"><?= $data['nama_lengkap']; ?></td>
                            <td><span class="badge bg-secondary"><?= $data['nama_role']; ?></span></td>
                            <td class="small text-muted"><i class="fa-solid fa-building me-1"></i> <?= $data['nama_unit']; ?></td>
                            <td><?= $badge_status; ?></td>
                            <td class="text-center">
                                <?php if ($is_minta_reset): ?>
                                    <form action="aksi_reset_pass.php" method="POST" class="d-inline-block" onsubmit="return confirm('Kirim password baru ke Telegram pegawai ini?');">
                                        <input type="hidden" name="id_user" value="<?= $data['id']; ?>">
                                        <button type="submit" name="reset_telegram" class="btn btn-sm btn-success shadow-sm mb-1" title="Kirim Password Baru">
                                            <i class="fa-brands fa-telegram"></i> Kirim
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <button class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $data['id']; ?>" title="Edit Data"><i class="fa-solid fa-pen"></i></button>
                                <button class="btn btn-sm btn-outline-danger mb-1" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $data['id']; ?>" title="Hapus/Nonaktifkan"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-block d-md-none p-3 bg-light" style="max-height: 75vh; overflow-y: auto;">
                <?php foreach ($data_users as $data): 
                    // Logika Status untuk Card
                    $border_card = 'border-success';
                    if ($data['is_active'] == 0) {
                        $badge_status = '<span class="badge bg-danger">Nonaktif</span>';
                        $border_card = 'border-danger';
                    } elseif (empty($data['password_hash']) && empty($data['password'])) {
                        $badge_status = '<span class="badge bg-warning text-dark">Belum Aktivasi</span>';
                        $border_card = 'border-warning';
                    } else {
                        $badge_status = '<span class="badge bg-success">Aktif</span>';
                    }

                    $is_minta_reset = (isset($data['req_reset_pass']) && $data['req_reset_pass'] == '1');
                    if ($is_minta_reset) {
                        $badge_status .= ' <span class="badge bg-danger shadow-sm"><i class="fa-solid fa-bell text-warning"></i> Minta Reset</span>';
                        $border_card = 'border-danger bg-warning bg-opacity-10'; // Highlight card
                    }
                ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3 border-start border-4 <?= $border_card; ?>">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <span class="fw-bold text-primary font-monospace" style="font-size: 0.85rem;"><?= $data['nip']; ?></span>
                            <div><?= $badge_status; ?></div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-2">
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex justify-content-center align-items-center me-3 flex-shrink-0" style="width: 45px; height: 45px;">
                                <i class="fa-solid fa-user-tie fs-5"></i>
                            </div>
                            
                            <div class="flex-grow-1 overflow-hidden">
                                <h6 class="mb-1 fw-bold text-dark text-truncate" style="font-size: 0.95rem;">
                                    <?= $data['nama_lengkap']; ?>
                                </h6>
                                <div class="text-muted small mb-1"><i class="fa-solid fa-id-badge me-1"></i> <?= $data['nama_role']; ?></div>
                                <div class="text-muted small"><i class="fa-solid fa-building me-1"></i> <?= $data['nama_unit']; ?></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-1 mt-2 pt-2 border-top">
                            <?php if ($is_minta_reset): ?>
                                <form action="aksi_reset_pass.php" method="POST" class="d-inline-block me-auto" onsubmit="return confirm('Kirim password baru ke Telegram pegawai ini?');">
                                    <input type="hidden" name="id_user" value="<?= $data['id']; ?>">
                                    <button type="submit" name="reset_telegram" class="btn btn-sm btn-success fw-bold">
                                        <i class="fa-brands fa-telegram"></i> Kirim Akses
                                    </button>
                                </form>
                            <?php endif; ?>

                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $data['id']; ?>"><i class="fa-solid fa-pen"></i></button>
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapus<?= $data['id']; ?>"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php foreach ($data_users as $data): ?>
    
    <div class="modal fade" id="modalEdit<?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="aksi_pengguna.php" method="POST">
                <div class="modal-content text-start">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title fw-bold text-primary"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Pegawai</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $data['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold small">NIP / ID Pegawai</label>
                            <input type="text" class="form-control bg-light" name="nip" value="<?= $data['nip']; ?>" required readonly title="NIP tidak dapat diubah">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Nama Lengkap</label>
                            <input type="text" class="form-control border-primary" name="nama_lengkap" value="<?= $data['nama_lengkap']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Role Akses</label>
                            <select class="form-select" name="role_id" required>
                                <?php foreach ($daftar_roles as $r): ?>
                                    <option value="<?= $r['id']; ?>" <?= ($data['role_id'] == $r['id']) ? 'selected' : ''; ?>><?= $r['nama_role']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Unit Kerja</label>
                            <select class="form-select" name="unit_id" required>
                                <?php foreach ($daftar_units as $u): ?>
                                    <option value="<?= $u['id']; ?>" <?= ($data['unit_id'] == $u['id']) ? 'selected' : ''; ?>><?= $u['nama_unit']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-danger">Status Akun</label>
                            <select class="form-select border-danger" name="is_active" required>
                                <option value="1" <?= ($data['is_active'] == 1) ? 'selected' : ''; ?>>Aktif (Diizinkan Login)</option>
                                <option value="0" <?= ($data['is_active'] == 0) ? 'selected' : ''; ?>>Nonaktif / Pindah (Dilarang Login)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit" class="btn btn-primary fw-bold"><i class="fa-solid fa-save me-1"></i> Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalHapus<?= $data['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="aksi_pengguna.php" method="POST">
                <div class="modal-content text-start">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Konfirmasi Penonaktifan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $data['id']; ?>">
                        <p>Apakah Anda yakin ingin menonaktifkan akun pegawai <strong><?= $data['nama_lengkap']; ?></strong>?</p>
                        <p class="small text-muted mb-0">Pegawai yang dinonaktifkan tidak akan dihapus dari database (agar riwayat surat tetap aman), namun mereka tidak akan bisa login lagi ke sistem.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="hapus" class="btn btn-danger fw-bold">Ya, Nonaktifkan!</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php endforeach; ?>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="aksi_pengguna.php" method="POST">
            <div class="modal-content text-start">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus me-2"></i> Tambah Pegawai Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">NIP / ID Pegawai <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nip" required placeholder="Contoh: 198001012005011002">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" class="form-control border-primary" name="nama_lengkap" required placeholder="Nama beserta gelar">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Role Akses <span class="text-danger">*</span></label>
                        <select class="form-select" name="role_id" required>
                            <option value="">-- Pilih Role --</option>
                            <?php foreach ($daftar_roles as $r): ?>
                                <option value="<?= $r['id']; ?>"><?= $r['nama_role']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Unit Kerja / Departemen <span class="text-danger">*</span></label>
                        <select class="form-select" name="unit_id" required>
                            <option value="">-- Pilih Unit Kerja --</option>
                            <?php foreach ($daftar_units as $u): ?>
                                <option value="<?= $u['id']; ?>"><?= $u['nama_unit']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="alert alert-warning py-2 mb-0 border-warning border-start border-4" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-key me-1"></i> Pegawai yang ditambahkan akan memiliki status <strong>Belum Aktivasi</strong>. Pegawai harus membuat password sendiri melalui menu Registrasi Akun.
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary fw-bold"><i class="fa-solid fa-plus me-1"></i> Simpan Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>