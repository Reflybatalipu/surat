<?php
require_once 'config.php';
$donor_id = isset($_GET['donor_id']) ? $conn->real_escape_string($_GET['donor_id']) : '';
$result   = $conn->query("SELECT golongan_darah,kota,jumlah_tambah,keterangan,created_at
                          FROM riwayat_stok WHERE donor_id='$donor_id' ORDER BY created_at DESC");
$data = [];
while ($row = $result->fetch_assoc()) { $data[] = $row; }
echo json_encode(['status'=>'success','data'=>$data]);
?>

