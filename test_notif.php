<?php
include 'kirim_notifikasi.php';

$token_hp_mu = "cLvT73YtSxipNo1MvVTrBy:APA91bFZssavTBCC2u4HQ4lJXwba6R6PLF9Jaa6SubFamVD7POtqCMMp0BQCYm85oK5MEJaAp7BpUkV9Oeu9VjhJXDYPh75QnQuBZiUp8C-yT_VmimLKCx0";

$judul = "Halo dari SIMPERS!";
$pesan = "Uji coba Push Notification berhasil masuk ke HP!";

echo "Mencoba mengirim notifikasi...<br><br>";

$hasil = kirimNotifikasiFCM($token_hp_mu, $judul, $pesan);

echo "<b>Laporan dari Google:</b><br>";
echo $hasil;
?>