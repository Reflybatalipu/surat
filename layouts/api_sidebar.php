<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'config/koneksi.php';

if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized',
        'badges'  => [
            'surat_masuk'  => 0,
            'surat_keluar' => 0,
            'disposisi'    => 0,
        ],
    ]);
    exit;
}

$role    = $_SESSION['nama_role'] ?? '';
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$is_admin  = ($role === 'Admin_TU');
$is_kepsek = ($role === 'Kepala_Sekolah' || $role === 'Kepala Sekolah');

$badges = [
    'surat_masuk'  => 0,
    'surat_keluar' => 0,
    'disposisi'    => 0,
];

function simpers_count_query(mysqli $koneksi, string $sql): int
{
    $result = mysqli_query($koneksi, $sql);
    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return isset($row['t']) ? (int)$row['t'] : 0;
}

if ($is_admin || $is_kepsek) {
    // Admin TU dan Kepala Sekolah: surat masuk baru yang belum punya disposisi.
    $badges['surat_masuk'] = simpers_count_query($koneksi, "
        SELECT COUNT(sm.id) AS t
        FROM surat_masuk sm
        LEFT JOIN disposisi d ON sm.id = d.surat_id
        WHERE sm.status_workflow = 'Baru'
        AND sm.deleted_at IS NULL
        AND d.id IS NULL
    ");

    // Kepsek/Admin melihat pekerjaan aktif pada surat keluar yang masih menunggu review/acc.
    $badges['surat_keluar'] = simpers_count_query($koneksi, "
        SELECT COUNT(id) AS t
        FROM surat_keluar
        WHERE status_workflow = 'Review'
        AND deleted_at IS NULL
    ");
} else {
    // Guru/Staff: hanya disposisi aktif milik user login.
    // acted_at NULL dipakai agar disposisi yang sudah ditindak tidak ikut dihitung lagi.
    $badges['disposisi'] = simpers_count_query($koneksi, "
        SELECT COUNT(d.id) AS t
        FROM disposisi d
        JOIN surat_masuk sm ON d.surat_id = sm.id
        WHERE d.ke_user_id = '{$user_id}'
        AND sm.deleted_at IS NULL
        AND d.acted_at IS NULL
        AND (d.status IS NULL OR d.status NOT IN ('Selesai','Ditindaklanjuti','Done','Completed'))
    ");

    // Guru/Staff: surat keluar miliknya yang masih butuh proses.
    $badges['surat_keluar'] = simpers_count_query($koneksi, "
        SELECT COUNT(id) AS t
        FROM surat_keluar
        WHERE draft_by = '{$user_id}'
        AND status_workflow IN ('Draft','Review')
        AND deleted_at IS NULL
    ");
}

echo json_encode([
    'success' => true,
    'role'    => $role,
    'badges'  => $badges,
]);
