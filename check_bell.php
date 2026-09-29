<?php
$Scriptis = 'check_bell.php';
date_default_timezone_set('Asia/Jakarta');
require_once '../../config.php'; // Sesuaikan path config webERP Anda jika berbeda

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

    // Ambil semua jadwal hari ini atau gunakan toleransi rentang waktu (misal: dalam 2 menit terakhir)
    // Atau cara paling aman: Cek jadwal yang waktunya <= waktu sekarang DAN belum dibunyikan hari ini.
    // Namun, jika struktur tabel saat ini belum ada kolom "status_hari_ini", 
    // kita bisa gunakan rentang waktu (window) misal dalam 60 detik terakhir:
    
    $timestampNow = time();
    $formattedNow = date('H:i:s', $timestampNow);
    $formattedPast = date('H:i:s', $timestampNow - 75); // Toleransi mundur 75 detik ke belakang

    // Mencari jadwal dalam rentang waktu yang terlewat (misal telat 1-2 menit)
    $scheduleStmt = $pdo->prepare("SELECT duration, bell_type, jam FROM schedules WHERE jam > ? AND jam <= ? AND shift_id = 1 ORDER BY jam DESC LIMIT 1");
    $scheduleStmt->execute([$formattedPast, $formattedNow]);
    $schedRow = $scheduleStmt->fetch(PDO::FETCH_ASSOC);

    if ($schedRow) {
        // OPSIONAL: Jika ingin memastikan satu jadwal hanya berbunyi sekali, 
        // Anda bisa mencatat log eksekusi hari ini ke database. 
        // Tapi dengan rentang ini, jika ESP32 telat manggil di 08:31:00, 
        // dia akan tetap mendeteksi jadwal 08:30:00 yang masuk dalam rentang 75 detik terakhir.
        
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
