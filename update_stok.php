<?php
require_once 'config.php';
$input = json_decode(file_get_contents('php://input'), true);
if (!isset($input['donor_id'],$input['golongan_darah'],$input['kota'])) {
    http_response_code(400);
    echo json_encode(['status'=>'error','message'=>'Data tidak lengkap']); exit;
}
$donor_id = $conn->real_escape_string($input['donor_id']);
$golongan = $conn->real_escape_string($input['golongan_darah']);
$kota     = $conn->real_escape_string($input['kota']);
$tambah   = isset($input['jumlah_tambah']) ? (int)$input['jumlah_tambah'] : 1;
$ket      = isset($input['keterangan'])    ? $conn->real_escape_string($input['keterangan']) : '';

$conn->query("UPDATE stok_darah SET jumlah=jumlah+$tambah WHERE kota='$kota' AND golongan_darah='$golongan'");
$conn->query("INSERT INTO riwayat_stok (donor_id,golongan_darah,kota,jumlah_tambah,keterangan)
              VALUES ('$donor_id','$golongan','$kota',$tambah,'$ket')");
$stokBaru = $conn->query("SELECT jumlah FROM stok_darah WHERE kota='$kota' AND golongan_darah='$golongan'")->fetch_assoc()['jumlah'];
echo json_encode(['status'=>'success','message'=>'Stok diperbarui','stok_terbaru'=>(int)$stokBaru]);
?>

