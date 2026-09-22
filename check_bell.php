<?php
date_default_timezone_set('Asia/Jakarta');

require_once '../../config.php'; //sesuaikan dengan dir config

$currentDate = date('Y-m-d'); 
$currentTime = date('H:i'); 
$deviceMac = isset($_GET['mac']) ? $_GET['mac'] : '';

header('Content-Type: application/json');

// --- 1. CEK ANTREAN MANUAL ---
if ($deviceMac) {
    try {
        // Ambil id, duration, dan bell_type dari tabel bell_queue
        $queueStmt = $pdo->prepare("SELECT id, duration, bell_type FROM bell_queue WHERE mac_address = ? LIMIT 1");
        $queueStmt->execute([$deviceMac]);
        $queueRow = $queueStmt->fetch(PDO::FETCH_ASSOC);

        if ($queueRow) {
            $delStmt = $pdo->prepare("DELETE FROM bell_queue WHERE id = ?");
            $delStmt->execute([$queueRow['id']]);

            // Kirim respons JSON beserta tipe suara yang dipilih di kontrol manual
            echo json_encode([
                "ring" => true, 
                "duration" => intval($queueRow['duration']), 
                "type" => $queueRow['bell_type'] ?? 'kontinu'
            ]);
            exit;
        }
    } catch (PDOException $e) {}
}

// --- 2. CEK HARI LIBUR & JADWAL OTOMATIS ---
$dayOfWeek = date('w');
if ($dayOfWeek == '0') {
    echo json_encode(["ring" => false, "reason" => "Hari Minggu"]);
    exit;
}

try {
    $holidayStmt = $pdo->prepare("SELECT nama FROM holiday WHERE tanggal = ?");
    $holidayStmt->execute([$currentDate]);
    if ($holidayStmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(["ring" => false, "reason" => "Libur Nasional"]);
        exit;
    }

    $scheduleStmt = $pdo->prepare("SELECT duration, bell_type FROM schedules WHERE jam = ?");
    $scheduleStmt->execute([$currentTime . ":00"]);
    $schedRow = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

    if ($schedRow) {
        echo json_encode([
            "ring" => true, 
            "duration" => intval($schedRow['duration']), 
            "type" => $schedRow['bell_type'] ?? 'kontinu'
        ]);
    } else {
        echo json_encode(["ring" => false]);
    }
} catch (PDOException $e) {
    echo json_encode(["ring" => false, "error" => $e->getMessage()]);
}
?>
