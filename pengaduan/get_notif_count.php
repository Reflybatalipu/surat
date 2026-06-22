<?php
session_start();
include '../config/koneksi.php';

$id_user = $_SESSION['user_id'] ?? 0;
$jumlah = 0;

if ($id_user > 0) {
    // Hitung tiket yang sudah selesai (Resolved) tapi mungkin belum dilihat user
    // Atau tiket yang sedang diproses (In Progress)
    $query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM pengaduan 
              WHERE id_user = '$id_user' AND status IN ('In Progress', 'Resolved')");
    $data = mysqli_fetch_assoc($query);
    $jumlah = $data['total'];
}

echo json_encode(['jumlah' => $jumlah]);