<?php
date_default_timezone_set('Asia/Jakarta');
//sesuaikan dengan dir config
require_once '../../config.php';

$mac = isset($_GET['mac']) ? $_GET['mac'] : 'Unknown';
$status = isset($_GET['status']) ? $_GET['status'] : 'success';
$timestamp = date('Y-m-d H:i:s');

try {
    // Pastikan Anda sudah membuat tabel 'bell_logs' di database, atau simpan ke file teks/log sederhana
    $stmt = $pdo->prepare("INSERT INTO bell_logs (mac_address, status, triggered_at) VALUES (?, ?, ?)");
    $stmt->execute([$mac, $status, $timestamp]);
    
    echo json_encode(["status" => "logged"]);
} catch (PDOException $e) {
    // Jika tabel belum ada, buat tabel otomatis atau abaikan error
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
