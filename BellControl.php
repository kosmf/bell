<?php
// --- HANDLE AKSI AJAX DI BARIS PALING ATAS (MURNI JSON) ---
if (isset($_GET['action'])) {
    include('includes/session.php');
    date_default_timezone_set('Asia/Jakarta');
    
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    // 2. Tambah / Update Jadwal
    if ($_GET['action'] == 'save_schedule') {
        $id = intval($_GET['id'] ?? 0);
        $jam =$_GET['jam'] ?? '';
        $ket =$_GET['keterangan'] ?? '';
        $dur = intval($_GET['duration'] ?? 5);
        $type =$_GET['bell_type'] ?? 'kontinu';

        if ($jam) {
            try {
                if ($id > 0) {$sql = "UPDATE schedules SET jam = '" . $jam . "', keterangan = '" . $ket . "', duration = " . $dur . ", bell_type = '" . $type . "' WHERE id = " . $id;
                } else {
                    $sql = "INSERT INTO schedules (jam, keterangan, duration, bell_type) VALUES ('" . $jam . "', '" . $ket . "', " . $dur . ", '" . $type . "')";
                }
                DB_query($sql);
                echo json_encode(["status" => "success"]);
            } catch (Exception $e) {
                echo json_encode(["status" => "error", "message" => $e->getMessage()]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Jam tidak boleh kosong."]);
        }
        exit;
    }
}

// --- HALAMAN UTAMA HTML ---
$Scriptis = 'BellControl.php';
include('includes/session.php');
$Title = "Kontrol & Jadwal Bel Pabrik";
include('includes/header.php');

date_default_timezone_set('Asia/Jakarta');

$devices = []; $schedules = [];$logs = []; $queues = [];$weekend_days = ['0'];

try {
    $setResult = DB_query("SELECT setting_value FROM bell_settings WHERE setting_key = 'weekend_days'");
    if ($row = DB_fetch_array($setResult)) {
        $weekend_days = explode(',',$row['setting_value']);
    }
} catch (Exception $e) {}

$devResult = DB_query("SELECT DISTINCT mac_address FROM bell_logs UNION SELECT DISTINCT mac_address FROM bell_queue");
while ($row = DB_fetch_array($devResult)) { if(!empty($row['mac_address'])) $devices[] =$row; }

$schedResult = DB_query("SELECT * FROM schedules ORDER BY jam ASC");
while ($row = DB_fetch_array($schedResult)) { $schedules[] =$row; }

$queueResult = DB_query("SELECT * FROM bell_queue ORDER BY id DESC");
while ($row = DB_fetch_array($queueResult)) { $queues[] =$row; }

$logResult = DB_query("SELECT * FROM bell_logs ORDER BY triggered_at DESC LIMIT 10");
while ($row = DB_fetch_array($logResult)) { $logs[] =$row; }
?>

<div style="max-width: 950px; margin: auto; font-family: Arial, sans-serif;">
    <h2>Dashboard Kontrol & Jadwal Bel Pabrik</h2>
    <hr style="margin-bottom: 20px;">

    <!-- PENGATURAN HARI LIBUR MINGGUAN -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
        <h3 style="margin-top:0;">Pengaturan Hari Libur Rutin (Mingguan)</h3>
        <p style="font-size: 13px; color: #666;">Centang hari-hari di mana bel otomatis **tidak akan berbunyi**:</p>
        <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
            <label><input type="checkbox" name="weekend" value="1" <?= in_array('1', $weekend_days) ? 'checked' : '' ?>> Senin</label>
            <label><input type="checkbox" name="weekend" value="2" <?= in_array('2', $weekend_days) ? 'checked' : '' ?>> Selasa</label>
            <label><input type="checkbox" name="weekend" value="3" <?= in_array('3', $weekend_days) ? 'checked' : '' ?>> Rabu</label>
            <label><input type="checkbox" name="weekend" value="4" <?= in_array('4', $weekend_days) ? 'checked' : '' ?>> Kamis</label>
            <label><input type="checkbox" name="weekend" value="5" <?= in_array('5', $weekend_days) ? 'checked' : '' ?>> Jumat</label>
            <label><input type="checkbox" name="weekend" value="6" <?= in_array('6', $weekend_days) ? 'checked' : '' ?>> Sabtu</label>
            <label><input type="checkbox" name="weekend" value="0" <?= in_array('0', $weekend_days) ? 'checked' : '' ?>> Minggu</label>
            <button onclick="saveWeekendSettings()" style="background-color: #0275d8; color: white; border: none; padding: 6px 15px; font-weight: bold; cursor: pointer; border-radius: 4px;">Simpan Hari Libur</button>
        </div>
    </div>

    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
        <!-- KONTROL MANUAL -->
        <div style="flex: 1; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; min-width: 300px;">
            <h3>Kontrol Manual</h3>
            <label style="font-weight: bold; display: block; margin-top: 10px;">Pilih Perangkat (MAC):</label>
            <select id="deviceSelect" style="width: 100%; padding: 8px; margin-top: 5px;">
                <?php if (count($devices) > 0): ?>
                    <?php foreach ($devices as$d): ?>
                        <option value="<?= htmlspecialchars($d['mac_address']) ?>"><?= htmlspecialchars($d['mac_address']) ?></option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">-- Belum ada perangkat terdeteksi --</option>
                <?php endif; ?>
            </select>

            <label style="font-weight: bold; display: block; margin-top: 10px;">Durasi Total (Detik):</label>
            <input type="number" id="durationInput" value="3" min="1" max="60" style="width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;">

            <label style="font-weight: bold; display: block; margin-top: 10px;">Jenis Suara:</label>
            <select id="manualTypeInput" style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="kontinu">Kontinu (Terus-menerus)</option>
                <option value="putus">Putus-putus (Bip... Bip...)</option>
            </select>

            <button onclick="triggerBell()" style="background-color: #d9534f; color: white; border: none; padding: 10px; width: 100%; margin-top: 15px; font-weight: bold; cursor: pointer; border-radius: 4px;">BUNYIKAN SEKARANG</button>
            <div id="statusMsg" style="margin-top:10px; font-weight:bold; text-align:center;"></div>
        </div>

        <!-- FORM TAMBAH / EDIT JADWAL -->
        <div style="flex: 1; background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; min-width: 300px;">
            <h3 id="formTitle">Tambah Jadwal Baru</h3>
            <input type="hidden" id="scheduleId" value="">

            <label style="font-weight: bold; display: block; margin-top: 10px;">Jam (HH:MM):</label>
            <input type="time" id="jamInput" style="width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;" required>

            <label style="font-weight: bold; display: block; margin-top: 10px;">Keterangan:</label>
            <input type="text" id="ketInput" placeholder="Contoh: Istirahat" style="width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;">

            <label style="font-weight: bold; display: block; margin-top: 10px;">Durasi Total (Detik):</label>
            <input type="number" id="durSchedInput" value="5" min="1" style="width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;">

            <label style="font-weight: bold; display: block; margin-top: 10px;">Jenis Suara:</label>
            <select id="typeSchedInput" style="width: 100%; padding: 8px; margin-top: 5px;">
                <option value="kontinu">Kontinu (Terus-menerus)</option>
                <option value="putus">Putus-putus (Bip... Bip...)</option>
            </select>

            <button id="btnSaveSchedule" onclick="saveSchedule()" style="background-color: #5cb85c; color: white; border: none; padding: 10px; width: 100%; margin-top: 15px; font-weight: bold; cursor: pointer; border-radius: 4px;">SIMPAN JADWAL</button>
            <button id="btnCancelEdit" onclick="resetForm()" style="background-color: #6c757d; color: white; border: none; padding: 8px; width: 100%; margin-top: 5px; font-weight: bold; cursor: pointer; border-radius: 4px; display: none;">Batal Edit</button>
        </div>
    </div>

    <!-- TABEL MONITOR ANTREAN MANUAL (QUEUE) -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin:0;">Antrean Manual Menunggu Eksekusi (Queue)</h3>
            <button onclick="location.reload()" style="background: #0275d8; color: white; border: none; padding: 5px 10px; font-size: 12px; cursor: pointer; border-radius: 3px; width: auto; margin:0;">Refresh Status</button>
        </div>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">ID Antrean</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">MAC Address</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Durasi</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Jenis Suara</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($queues) > 0): ?>
                    <?php foreach ($queues as$q): ?>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($q['id']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($q['mac_address']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($q['duration']) ?> detik</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-transform: capitalize;"><?= htmlspecialchars($q['bell_type'] ?? 'kontinu') ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px; color: #f0ad4e; font-weight: bold;">Menunggu ESP32 Polling...</td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #6c757d;">Tidak ada antrean aktif saat ini (Bersih).</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TABEL JADWAL -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">
        <h3>Daftar Jadwal Bel Otomatis</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Jam</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Keterangan</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Durasi</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Jenis Suara</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($schedules) > 0): ?>
                    <?php foreach ($schedules as$s): ?>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><strong><?= htmlspecialchars($s['jam']) ?></strong></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($s['keterangan']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($s['duration']) ?> detik</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-transform: capitalize;"><?= htmlspecialchars($s['bell_type'] ?? 'kontinu') ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;">
                            <button onclick="editSchedule(<?= $s['id'] ?>, '<?= htmlspecialchars($s['jam']) ?>', '<?= htmlspecialchars($s['keterangan']) ?>', <?= $s['duration'] ?>, '<?= htmlspecialchars($s['bell_type'] ?? 'kontinu') ?>')" style="background: #f0ad4e; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Edit</button>
                            <button onclick="deleteSchedule(<?= $s['id'] ?>)" style="background: #d9534f; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 3px;">Hapus</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="border: 1px solid #ddd; padding: 8px; text-align: center;">Belum ada jadwal tersimpan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- TABEL LOG AKTIVITAS TERAKHIR -->
    <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px; margin-bottom: 30px;">
        <h3>Log Aktivitas Perangkat Terbaru</h3>
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Waktu</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">MAC Address</th>
                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as$l): ?>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($l['triggered_at']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px;"><?= htmlspecialchars($l['mac_address']) ?></td>
                        <td style="border: 1px solid #ddd; padding: 8px; color: green; font-weight: bold;"><?= htmlspecialchars($l['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: center;">Belum ada log aktivitas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function saveWeekendSettings() {
    let checkboxes = document.querySelectorAll('input[name="weekend"]:checked');
    let values = [];
    checkboxes.forEach((checkbox) => {
        values.push(checkbox.value);
    });
    let offDaysStr = values.join(",");

    fetch(`BellControl.php?action=save_settings&off_days=${offDaysStr}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === "success") {
                alert("Pengaturan hari libur berhasil disimpan!");
                location.reload();
            } else {
                alert("Gagal menyimpan pengaturan.");
            }
        });
}

function triggerBell() {
    let mac = document.getElementById("deviceSelect").value;
    let duration = document.getElementById("durationInput").value;
    let type = document.getElementById("manualTypeInput").value;
    let msg = document.getElementById("statusMsg");

    if (!mac) {
        msg.innerHTML = "Pilih perangkat terlebih dahulu!";
        msg.style.color = "red";
        return;
    }

    msg.innerHTML = "Mengirim perintah ke antrean...";
    msg.style.color = "#333";

    fetch(`BellControl.php?action=trigger&mac=${mac}&duration=${duration}&type=${type}`)
        .then(res => res.json())
        .then(data => {
            if(data.status === "success") {
                msg.innerHTML = "Berhasil! Menunggu ESP32 polling (maks 1 menit)...";
                msg.style.color = "green";
                setTimeout(() => { location.reload(); }, 2000);
            } else {
                msg.innerHTML = "Gagal: " + data.message;
                msg.style.color = "red";
            }
        });
}

function saveSchedule() {
    let id = document.getElementById("scheduleId").value;
    let jam = document.getElementById("jamInput").value;
    let ket = document.getElementById("ketInput").value;
    let dur = document.getElementById("durSchedInput").value;
    let type = document.getElementById("typeSchedInput").value;

    if (!jam || !ket) {
        alert("Jam dan Keterangan wajib diisi!");
        return;
    }

    let url = `BellControl.php?action=save_schedule&id=${id}&jam=${jam}&keterangan=${encodeURIComponent(ket)}&duration=${dur}&bell_type=${type}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if(data.status === "success") {
                location.reload();
            } else {
                alert("Gagal menyimpan jadwal: " + (data.message || "Terjadi kesalahan pada server."));
            }
        })
        .catch(err => {
            console.error("Error:", err);
            alert("Terjadi kesalahan koneksi ke server.");
        });
}

function editSchedule(id, jam, keterangan, duration, bellType) {
    document.getElementById("scheduleId").value = id;
    document.getElementById("jamInput").value = jam.substring(0, 5);
    document.getElementById("ketInput").value = keterangan;
    document.getElementById("durSchedInput").value = duration;
    document.getElementById("typeSchedInput").value = bellType;
    
    document.getElementById("formTitle").innerText = "Edit Jadwal Bel";
    document.getElementById("btnSaveSchedule").innerText = "PERBARUI JADWAL";
    document.getElementById("btnCancelEdit").style.display = "block";
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function resetForm() {
    document.getElementById("scheduleId").value = "";
    document.getElementById("jamInput").value = "";
    document.getElementById("ketInput").value = "";
    document.getElementById("durSchedInput").value = "5";
    document.getElementById("typeSchedInput").value = "kontinu";
    
    document.getElementById("formTitle").innerText = "Tambah Jadwal Baru";
    document.getElementById("btnSaveSchedule").innerText = "SIMPAN JADWAL";
    document.getElementById("btnCancelEdit").style.display = "none";
}

function deleteSchedule(id) {
    if (confirm("Yakin ingin menghapus jadwal ini?")) {
        fetch(`BellControl.php?action=del_schedule&id=${id}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === "success") {
                    location.reload();
                } else {
                    alert("Gagal menghapus jadwal.");
                }
            });
    }
}
</script>

<?php
include('includes/footer.php');
?>
