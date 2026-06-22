<?php
// Ganti dengan token kamu
$bot_token = "8750324075:AAGI3fu1xFQjf4bzzYKn6QuAbJlxUqY9xRA";
// Ganti dengan ID Telegram kamu
$chat_id = "ISI_DENGAN_ID_TELEGRAM_KAMU"; 

$url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
$data = ['chat_id' => $chat_id, 'text' => 'Halo, ini tes dari InfinityFree!'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$hasil = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Hasil Tes Telegram:</h3>";
if ($hasil === false) {
    echo "<b style='color:red;'>GAGAL! Alasan Server: " . $error . "</b>";
} else {
    echo "<b style='color:green;'>SUKSES! Jawaban Telegram: " . $hasil . "</b>";
}
?>