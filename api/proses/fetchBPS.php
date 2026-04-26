<?php
// proses/fetchBPS.php
// Selalu fetch data NASIONAL (wilayah=0000000).
// Filter per-provinsi dilakukan di JavaScript, karena endpoint per-provinsi
// BPS mengembalikan struktur berbeda (data kab/kota, kolom hilang, dll).
header('Content-Type: application/json');
header('Cache-Control: public, max-age=3600'); // cache browser 1 jam

$url = "https://webapi.bps.go.id/v1/api/interoperabilitas/datasource/simdasi/id/25/tahun/2025/id_tabel/biszcFRCUnVKUXNnTDZvWnA3ZWtyUT09/wilayah/0000000/key/edd523f7d5c4c57dbe1a078b5271b69f";

// Cache di server selama 1 jam agar tidak hammer API BPS
$cacheFile = sys_get_temp_dir() . '/bps_faskes_2025.json';
$cacheTTL  = 3600;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    echo file_get_contents($cacheFile);
    exit;
}

$ctx = stream_context_create(['http' => ['timeout' => 15]]);
$response = @file_get_contents($url, false, $ctx);

if ($response === false) {
    // Coba kembalikan cache lama jika ada (meski expired)
    if (file_exists($cacheFile)) {
        echo file_get_contents($cacheFile);
    } else {
        echo json_encode(["error" => "Gagal mengambil data BPS"]);
    }
    exit;
}

// Simpan ke cache
file_put_contents($cacheFile, $response);
echo $response;
?>