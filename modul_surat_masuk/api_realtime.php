<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized']));
}

$role = $_SESSION['nama_role'];
$user_id = (int)$_SESSION['user_id'];
$response = ['stats' => [], 'notifications' => []];

// 1. Ambil Statistik (Sesuai role)
if ($role == 'Admin_TU') {
    $response['stats']['val_a'] = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_masuk WHERE deleted_at IS NULL"))['t'];
    $response['stats']['val_b'] = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(sm.id) as t FROM surat_masuk sm LEFT JOIN disposisi d ON sm.id=d.surat_id WHERE sm.status_workflow='Baru' AND sm.deleted_at IS NULL AND d.id IS NULL"))['t'];
} elseif ($role == 'Kepala_Sekolah' || $role == 'Kepala Sekolah') {
    $response['stats']['val_a'] = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(sm.id) as t FROM surat_masuk sm LEFT JOIN disposisi d ON sm.id=d.surat_id WHERE sm.status_workflow='Baru' AND sm.deleted_at IS NULL AND d.id IS NULL"))['t'];
    $response['stats']['val_b'] = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as t FROM surat_keluar WHERE status_workflow='Review' AND deleted_at IS NULL"))['t'];
} else {
    $response['stats']['val_c'] = (int)mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(id) as t FROM disposisi WHERE ke_user_id='$user_id'"))['t'];
}

// 2. Ambil Notifikasi (Contoh: jumlah pesan belum dibaca)
// Anda bisa menambah logic notifikasi di sini nanti

header('Content-Type: application/json');
echo json_encode($response);