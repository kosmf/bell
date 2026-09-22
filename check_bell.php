<?php
$Scriptis = 'check_bell.php';
date_default_timezone_set('Asia/Jakarta');
require_once '../../config.php'; // Sesuaikan path config webERP Anda jika berbeda

$currentDate = date('Y-m-d'); 
$currentTime = date('H:i'); 
$currentDayOfWeek = date('w'); // 0 = Minggu, 1 = Senin, dst.
$deviceMac = isset($_GET['mac']) ? $_GET['mac'] : '';

header('Content-Type: application/json');

// --- 1. CEK ANTREAN MANUAL ---
if ($deviceMac) {
    try {
        $queueStmt = $pdo->prepare("SELECT id, duration, bell_type FROM bell_queue WHERE mac_address = ? LIMIT 1");
        $queueStmt->execute([$deviceMac]);
        $queueRow = $queueStmt->fetch(PDO::FETCH_ASSOC);

        if ($queueRow) {
            $delStmt = $pdo->prepare("DELETE FROM bell_queue WHERE id = ?");
            $delStmt->execute([$queueRow['id']]);

            echo json_encode([
                "ring" => true, 
                "duration" => intval($queueRow['duration']), 
                "type" => $queueRow['bell_type'] ?? 'kontinu'
            ]);
            exit;
        }
    } catch (PDOException $e) {}
}

// --- 2. CEK HARI LIBUR MINGGUAN / RUTIN ---
try {
    $setStmt = $pdo->query("SELECT setting_value FROM bell_settings WHERE setting_key = 'weekend_days'");
    $setRow = $setStmt->fetch(PDO::FETCH_ASSOC);
    $offDays = $setRow ? explode(',', $setRow['setting_value']) : ['0']; // Default Minggu (0)

    if (in_array((string)$currentDayOfWeek, $offDays)) {
        echo json_encode(["ring" => false, "reason" => "Hari Libur Rutin"]);
        exit;
    }
} catch (PDOException $e) {
    // Fallback default jika tabel belum ada: hanya Minggu (0)
    if ($currentDayOfWeek == '0') {
        echo json_encode(["ring" => false, "reason" => "Hari Minggu"]);
        exit;
    }
}

// --- 3. CEK LIBUR NASIONAL ---
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
