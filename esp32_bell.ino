#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <NTPClient.h>
#include <WiFiUdp.h>

// --- KONFIGURASI WIFI ---
const char* ssid = "YOUR-WIFI";
const char* password = "xxxx";

// --- URL API SERVER ---
// --- sesuaikan dengan server ----
const char* serverCheckUrl = "https://your-domain.combell/check_bell.php"; 
const char* serverLogUrl   = "https://your-domain.com/bell/log_bell.php";

// --- KONFIGURASI PIN ---
const int RELAY_PIN  = 16;  // Pin relay terintegrasi pada board ESP32
const int BUZZER_PIN = 13; // Pin buzzer pasif

// Logika Relay (Active High - Berdasarkan hasil pengujian Anda)
const int RELAY_ON  = HIGH;  
const int RELAY_OFF = LOW;

// --- KONFIGURASI NTP (Waktu Indonesia Barat / UTC+7) ---
WiFiUDP ntpUDP;
NTPClient timeClient(ntpUDP, "pool.ntp.org", 25200, 60000); 

unsigned long lastCheckTime = 0;
// --- KONFIGURASI INTERVAL PENGECEKAN ---
const unsigned long checkInterval = 60000; // Cek ke server setiap 1 menit sekali (60 detik)

// Variabel untuk mencegah bunyi berulang di menit yang sama
String lastRingTime = ""; 

// Fungsi untuk mengeksekusi jenis suara bel
// Fungsi untuk mengeksekusi jenis suara bel
void executeBell(int durationSeconds, String bellType) {
  Serial.print(">>> MENYALAKAN BEL (Tipe: ");
  Serial.print(bellType);
  Serial.print(" | Durasi: ");
  Serial.print(durationSeconds);
  Serial.println(" detik) <<<");

  if (bellType == "putus") {
    // Pola Putus-putus yang lebih panjang & longgar (Misal: Nyala 2 detik, Mati 1 detik)
    int elapsed = 0;
    while (elapsed < durationSeconds) {
      // Nyala lebih lama
      digitalWrite(RELAY_PIN, RELAY_ON);       
      tone(BUZZER_PIN, 2500, 500);             
      delay(2000); // Nyala selama 2 detik          
      elapsed += 2;

      if (elapsed >= durationSeconds) break;

      // Jeda mati lebih lama
      digitalWrite(RELAY_PIN, RELAY_OFF);      
      delay(1000);  // Jeda mati selama 1 detik
      elapsed += 1;
    }
    digitalWrite(RELAY_PIN, RELAY_OFF); // Pastikan mati di akhir
  } else {
    // Pola Kontinu (Menyala terus sepanjang durasi)
    digitalWrite(RELAY_PIN, RELAY_ON);       
    tone(BUZZER_PIN, 2500, 500);             
    delay(durationSeconds * 1000);           
    digitalWrite(RELAY_PIN, RELAY_OFF);      
  }
}

void setup() {
  Serial.begin(115200);
  
  pinMode(RELAY_PIN, OUTPUT);
  digitalWrite(RELAY_PIN, RELAY_OFF);

  pinMode(BUZZER_PIN, OUTPUT);
  delay(2000); // Delay pengaman agar relay tidak berbunyi saat reset
  tone(BUZZER_PIN, 2000, 300);

  WiFi.begin(ssid, password);
  Serial.print("Menghubungkan ke WiFi Office-SMG");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("\nWiFi Terhubung!");
  
  String macAddress = WiFi.macAddress();
  Serial.print("MAC Address: ");
  Serial.println(macAddress);

  timeClient.begin();

  Serial.println("Mengirim laporan status startup ke server...");
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient httpLog;
    String startupLogUrl = String(serverLogUrl) + "?mac=" + macAddress + "&status=device_started";
    httpLog.begin(startupLogUrl);
    int logResponse = httpLog.GET();
    if (logResponse > 0) {
      Serial.println("Log startup berhasil dikirim.");
    }
    httpLog.end();
  }
}

void loop() {
  timeClient.update();
  
  unsigned long currentMillis = millis();

  if (currentMillis - lastCheckTime >= checkInterval) {
    lastCheckTime = currentMillis;

    if (WiFi.status() == WL_CONNECTED) {
      String formattedTime = timeClient.getFormattedTime(); 
      String currentDayTime = formattedTime.substring(0, 5); // Format "HH:MM"
      
      String macAddress = WiFi.macAddress();
      String targetUrl = String(serverCheckUrl) + "?mac=" + macAddress + "&time=" + currentDayTime;

      Serial.print("Mengirim pengecekan ke server: ");
      Serial.println(targetUrl);

      HTTPClient http;
      http.begin(targetUrl);
      int httpResponseCode = http.GET();

      if (httpResponseCode > 0) {
        String payload = http.getString();
        
        Serial.print("Respon Server: ");
        Serial.println(payload);

        StaticJsonDocument<256> doc;
        DeserializationError error = deserializeJson(doc, payload);

        if (!error) {
          bool shouldRing = doc["ring"] | false;
          int durationSeconds = doc["duration"] | 5; 
          String bellType = doc["type"] | "kontinu"; // Menangkap tipe suara dari server

          if (shouldRing) {
            // Cek apakah bel sudah berbunyi pada menit yang sama
            if (currentDayTime == lastRingTime) {
              Serial.println(">>> Abaikan: Bel sudah berbunyi pada menit ini. <<<");
            } else {
              // Jalankan fungsi bel sesuai tipenya (kontinu / putus)
              executeBell(durationSeconds, bellType);
              
              // Simpan waktu terakhir berbunyi agar tidak terpicu lagi di menit ini
              lastRingTime = currentDayTime;

              Serial.println(">>> Bel selesai berbunyi. Mengirim laporan ke server... <<<");

              HTTPClient httpLog;
              String logUrl = String(serverLogUrl) + "?mac=" + macAddress + "&status=success";
              httpLog.begin(logUrl);
              httpLog.GET();
              httpLog.end();
            }
          } else {
            Serial.println("Status: Bel belum waktunya / Hari Libur.");
          }
        }
      } else {
        Serial.print("Gagal terhubung ke HTTP API. Kode error: ");
        Serial.println(httpResponseCode);
      }
      http.end();
    } else {
      Serial.println("WiFi Terputus! Menghubungkan ulang...");
      WiFi.reconnect();
    }
  }
}
