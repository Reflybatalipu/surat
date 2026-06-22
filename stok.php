<?php
require_once 'config.php';
$kota   = isset($_GET['kota']) ? $conn->real_escape_string($_GET['kota']) : 'Gorontalo';
$result = $conn->query("SELECT golongan_darah, jumlah, updated_at FROM stok_darah WHERE kota='$kota'");
$stok = [];
while ($row = $result->fetch_assoc()) {
    $stok[] = ['golongan'=>$row['golongan_darah'], 'jumlah'=>(int)$row['jumlah'], 'updated_at'=>$row['updated_at']];
}
echo json_encode(['status'=>'success','kota'=>$kota,'data'=>$stok]);
?>

