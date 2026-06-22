<?php
// Panggil autoloader dari Composer
require_once 'vendor/autoload.php';

// Panggil class PDF Parser
use Smalot\PdfParser\Parser;

$file_pdf = 'contoh_dokumen.pdf';

echo "<h2>🧪 Uji Coba Ekstrak PDF Digital</h2>";
echo "<p>Sedang mengekstrak teks dari: <b>$file_pdf</b>...</p>";

try {
    if (!file_exists($file_pdf)) {
        throw new Exception("File PDF '$file_pdf' tidak ditemukan di folder ini!");
    }

    // Inisialisasi alat pembaca PDF
    $parser = new Parser();
    
    // Baca file PDF-nya
    $pdf = $parser->parseFile($file_pdf);
    
    // Ekstrak seluruh teks yang ada di dalamnya
    $teks_hasil = $pdf->getText();

    echo "<h3 style='color: green;'>✅ Berhasil Diekstrak!</h3>";
    echo "<b>Hasil Teks:</b><br>";
    echo "<textarea style='width: 100%; height: 200px; padding: 10px; font-family: monospace;'>" . htmlspecialchars($teks_hasil) . "</textarea>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Gagal! Ada Error:</h3>";
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid red;'>";
    echo $e->getMessage();
    echo "</div>";
}
?>