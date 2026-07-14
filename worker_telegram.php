<?php

declare(strict_types=1);

require __DIR__ . '/config/koneksi.php';
require __DIR__ . '/fungsi_telegram.php';

set_time_limit(0);
ignore_user_abort(true);

date_default_timezone_set('Asia/Jakarta');

$lockFile = __DIR__ . '/worker.lock';

$fp = fopen($lockFile, 'c');

if (!$fp) {
    exit("Tidak dapat membuat lock file.\n");
}

if (!flock($fp, LOCK_EX | LOCK_NB)) {
    exit("Worker sudah berjalan.\n");
}

function tulisLog($pesan)
{
    $log = __DIR__ . '/logs/telegram_worker.log';

    file_put_contents(
        $log,
        "[" . date('Y-m-d H:i:s') . "] " . $pesan . PHP_EOL,
        FILE_APPEND
    );
}

tulisLog("Worker Telegram dimulai.");

$running = true;

if (function_exists('pcntl_signal')) {

    pcntl_async_signals(true);

    pcntl_signal(SIGTERM, function() use (&$running) {
        $running = false;
    });

    pcntl_signal(SIGINT, function() use (&$running) {
        $running = false;
    });
}

while ($running) {

    mysqli_ping($koneksi);

    $query = mysqli_query($koneksi,"
        SELECT *
        FROM antrean_telegram
        WHERE status_kirim='pending'
        ORDER BY id ASC
        LIMIT 20
    ");

    if (!$query) {

        tulisLog(mysqli_error($koneksi));

        sleep(5);

        continue;
    }

    if (mysqli_num_rows($query)==0){

        sleep(3);

        continue;
    }

    while($row=mysqli_fetch_assoc($query)){

        $id=$row['id'];

        $chat_id=$row['telegram_id'];

        $pesan=$row['pesan'];

        $file=$row['file_path'];

        try{

            if(
                !empty($file)
                &&
                file_exists(__DIR__.'/modul_surat_masuk/'.$file)
            ){

                $hasil=kirim_dokumen_telegram(
                    $chat_id,
                    __DIR__.'/modul_surat_masuk/'.$file,
                    $pesan
                );

            }else{

                $hasil=kirim_telegram(
                    $chat_id,
                    $pesan
                );

            }

            if($hasil!==false){

                mysqli_query(
                    $koneksi,
                    "UPDATE antrean_telegram
                     SET status_kirim='sent'
                     WHERE id=$id"
                );

                tulisLog("SUKSES ID $id");

            }else{

                mysqli_query(
                    $koneksi,
                    "UPDATE antrean_telegram
                     SET status_kirim='failed'
                     WHERE id=$id"
                );

                tulisLog("FAILED ID $id");

            }

        }catch(Throwable $e){

            mysqli_query(
                $koneksi,
                "UPDATE antrean_telegram
                 SET status_kirim='failed'
                 WHERE id=$id"
            );

            tulisLog($e->getMessage());

        }

        usleep(500000);

    }

    mysqli_query(
        $koneksi,
        "DELETE
         FROM antrean_telegram
         WHERE status_kirim IN('sent','failed')
         AND created_at < NOW()-INTERVAL 7 DAY"
    );

}

tulisLog("Worker dihentikan.");

flock($fp,LOCK_UN);

fclose($fp);
