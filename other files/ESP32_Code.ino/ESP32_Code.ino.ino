/*
 * IntelliFeed - ESP32 (FULL FIXED - Schedule Feeding with Proper Plate Handling)
 */

#include <WiFi.h>
#include <WebServer.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include "HX711.h"
#include "soc/soc.h"
#include "soc/rtc_cntl_reg.h"

// WiFi
const char* ssid = "kian1"; 
const char* password = "kian12345";

// API URLs
const char* arduinoDataUrl    = "http://10.137.170.60/IntelliFeed/api/arduino_data.php";
const char* feedingCommandUrl = "http://10.137.170.60/IntelliFeed/api/get_feeding_command.php";
const char* scheduleUrl       = "http://10.137.170.60/IntelliFeed/api/check_schedule.php";
const char* feedCompleteUrl   = "http://10.137.170.60/IntelliFeed/api/log_feed_complete.php";

// Must match 'device_key' in api/config.php on the server
const char* deviceKey = "change-me";

// id of the web command that started the current feed (0 = physical button)
long lastFeedCommandId = 0;

// HX711 Food Scale
#define LOADCELL_DT   18
#define LOADCELL_SCK  19
HX711 scale;
float calibrationFactor = -690.0;

// HX711 Water Plate Scale
#define WATER_DT   25
#define WATER_SCK  26
HX711 waterScale;
float waterCalFactor = 1200.0;
float waterWeight    = 0.0;
unsigned long lastWaterSentToUno = 0;
const long waterSendInterval = 1000;

// Plate + Water Storage state
float plateDistance = 0.0;
bool  plateInside   = false;
bool  plateMov      = false;
bool  plateOut      = false;

#define LOW_LEVEL_PCT  20.0
#define WATER_STORAGE_MIN_PCT  20.0
float wStorageLevelCm = 0.0;
float wStoragePct     = 0.0;
bool  wStorageLow     = false;

bool waterScaleConnected = false;

// Serial to Arduino
#define RXD2 16
#define TXD2 17

// Water plate auto pump
#define WATER_LOW_GRAMS    60.0   // start refilling below this
#define WATER_TARGET_GRAMS 85.0   // stop refilling at this (the gap prevents on/off chatter)
bool  autoPumping        = false;
bool  manualPumping      = false;
unsigned long pumpStartTime = 0;
const unsigned long maxPumpTime = 30000UL;
unsigned long pumpDurationMs = maxPumpTime;   // limit for the current pump run

// How long to wait for the Uno to finish a servo move before giving up
#define DISPOSE_WAIT_MS  25000UL   // plate in (6s) + dispose (8s) + margin
#define PLATE_IN_WAIT_MS 12000UL   // plate in takes about 6s

// 1 = brownout detector off (original behaviour). Set to 0 once the power supply
// is solid: with it off, voltage dips cause silent glitches instead of a clean reset.
#define DISABLE_BROWNOUT_DETECTOR 1

// State
float currentWeight  = 0.0;
bool  foodEmpty      = false;
bool  noPlate        = false;
bool  isFeeding      = false;
bool  webFeeding     = false;
bool  plateIdleWarn  = false;
bool  plateTimeout   = false;

unsigned long feedingStartTime = 0;
const unsigned long maxFeedTime = 90000UL;

// Timing
unsigned long lastWeightRead  = 0;
const long    weightInterval  = 500;
unsigned long lastWaterRead   = 0;
const long    waterInterval   = 500;
unsigned long lastWebSend     = 0;
const long    webSendInterval = 3000;
unsigned long lastCmdCheck    = 0;
const long    cmdCheckInterval = 2000;
unsigned long lastSchedCheck  = 0;
const long    schedCheckInterval = 30000;
unsigned long lastWifiCheck       = 0;
const long    wifiCheckInterval   = 30000;
unsigned long lastHeartbeat       = 0;
const long    heartbeatInterval   = 10000;
unsigned long lastSerialPrint     = 0;
const long    serialPrintInterval = 1000;

void connectWiFi() {
  Serial.println("[WiFi] Connecting to " + String(ssid) + "...");
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.persistent(false);
  WiFi.begin(ssid, password);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 30) {
    delay(500); tries++;
    if (tries % 6 == 0) Serial.printf("[WiFi] Waiting... (%ds)\n", tries / 2);
  }
  if (WiFi.status() == WL_CONNECTED) {
    WiFi.setSleep(false);
    Serial.println("[WiFi] ✓ Connected! IP: " + WiFi.localIP().toString());
  } else {
    Serial.println("[WiFi] ✗ Failed — auto-reconnect will keep trying");
  }
}

void checkWiFi() {
  if (millis() - lastWifiCheck < wifiCheckInterval) return;
  lastWifiCheck = millis();
  if (WiFi.status() != WL_CONNECTED && WiFi.status() != WL_DISCONNECTED) {
    Serial.printf("[WiFi] Stuck state (%d) — force reconnect\n", WiFi.status());
    WiFi.disconnect(true);
    delay(1000);
    WiFi.begin(ssid, password);
  }
}

void readFoodWeight() {
  if (!scale.is_ready()) return;
  float w = scale.get_units(3);
  if (w < 0) w = 0;
  currentWeight = w;
  if (millis() - lastSerialPrint >= serialPrintInterval) {
    Serial.print("[WEIGHT] Food:"); Serial.print(currentWeight, 1);
    Serial.print("g WaterPlate:"); Serial.print(waterWeight, 1);
    Serial.print("g Storage:"); Serial.print(wStoragePct, 0);
    Serial.print("% PlateOut:"); Serial.println(plateOut ? "YES" : "NO");
    lastSerialPrint = millis();
  }
  Serial2.print("WEIGHT:"); Serial2.println(currentWeight, 1);
}

void readWaterWeight() {
  if (!waterScaleConnected) return;
  if (!waterScale.is_ready()) return;
  float ww = waterScale.get_units(3);
  if (ww < 0) ww = 0;
  waterWeight = ww;
  
  if (millis() - lastWaterSentToUno >= waterSendInterval) {
    Serial2.print("WATER_WEIGHT:"); Serial2.println(waterWeight, 1);
    lastWaterSentToUno = millis();
  }

  bool isPumping = autoPumping || manualPumping;
  if (isPumping) {
    bool targetReached  = (waterWeight >= WATER_TARGET_GRAMS);
    bool timedOut       = (millis() - pumpStartTime >= pumpDurationMs);
    if (targetReached || timedOut) {
      Serial2.println("PUMP:0");
      if (autoPumping) {
        autoPumping = false;
        Serial.printf("[AUTO PUMP] Stopped — %.1fg\n", waterWeight);
      }
      if (manualPumping) {
        manualPumping = false;
        Serial.printf("[MANUAL PUMP] Stopped — %.1fg\n", waterWeight);
      }
    }
  } else {
    if (waterWeight < WATER_LOW_GRAMS && !isFeeding && waterScaleConnected && wStoragePct > 20.0) {
      autoPumping  = true;
      pumpStartTime = millis();
      pumpDurationMs = maxPumpTime;
      Serial2.println("PUMP:30");
      Serial.printf("[AUTO PUMP] Started — water low (%.1fg)\n", waterWeight);
    }
  }
}

void applyHttpTimeout(HTTPClient& http);

void sendDataToWeb() {
  if (WiFi.status() != WL_CONNECTED) return;
  HTTPClient http;
  http.begin(arduinoDataUrl);
  http.addHeader("X-Device-Key", deviceKey);
  applyHttpTimeout(http);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<320> doc;
  doc["weight"]            = currentWeight;
  doc["water_weight"]      = waterWeight;
  doc["feeding"]           = isFeeding;
  doc["food_empty"]        = foodEmpty;
  doc["no_plate"]          = noPlate;
  doc["plate_inside"]      = plateInside;
  doc["plate_distance"]    = plateDistance;
  doc["plate_idle_warn"]   = plateIdleWarn;
  doc["wstorage_pct"]      = wStoragePct;
  doc["wstorage_level_cm"] = wStorageLevelCm;
  doc["wstorage_low"]      = wStorageLow;
  doc["plate_out"]         = plateOut;
  doc["plate_mov"]         = plateMov;
  doc["plate_timeout"]     = plateTimeout;
  doc["device_id"]         = "esp32_main";

  String body;
  serializeJson(doc, body);
  int code = http.POST(body);
  Serial.printf("[WEB] POST code: %d\n", code);
  http.end();
}

// Tell the server a feed really finished, so history shows actual grams
void logFeedComplete(float dispensedGrams) {
  if (WiFi.status() != WL_CONNECTED || dispensedGrams <= 0) return;
  HTTPClient http;
  http.begin(feedCompleteUrl);
  http.addHeader("X-Device-Key", deviceKey);
  http.setTimeout(5000);
  http.addHeader("Content-Type", "application/json");
  StaticJsonDocument<128> doc;
  doc["device_id"]   = "esp32_main";
  doc["dispensed_g"] = dispensedGrams;
  doc["command_id"]  = lastFeedCommandId;
  String body;
  serializeJson(doc, body);
  int code = http.POST(body);
  Serial.printf("[HISTORY] Logged %.1fg (cmd %ld) -> HTTP %d\n", dispensedGrams, lastFeedCommandId, code);
  http.end();
  lastFeedCommandId = 0;
}

// How long HTTP calls may block. While food is pouring the Uno stops the servo
// only when it receives WEIGHT updates, so the loop must not stall for long.
void applyHttpTimeout(HTTPClient& http) {
  int ms = isFeeding ? 1500 : 5000;
  http.setConnectTimeout(ms);
  http.setTimeout(ms);
}

// Every message the Uno sends is handled here (from loop() and while waiting).
void handleArduinoMessage(const String& msg) {
  if (msg.length() == 0) return;

  if (msg == "FOOD_EMPTY") foodEmpty = true;
  else if (msg == "PLATE_OUTSIDE") {
    plateInside = false; noPlate = false; plateOut = true;
    Serial.println("[STATE] Plate is OUTSIDE");
  }
  else if (msg == "PLATE_INSIDE") {
    plateInside = true; noPlate = false; plateOut = false;
    Serial.println("[STATE] Plate is INSIDE");
  }
  else if (msg == "DISPOSE_SKIPPED") { noPlate = true; plateOut = false; }
  else if (msg == "DISPOSE_COMPLETE") plateOut = false;
  else if (msg == "AUTO_DISPOSE_COMPLETE") { plateIdleWarn = true; plateOut = false; }
  else if (msg == "PLATE_IDLE_WARN") plateIdleWarn = true;
  else if (msg == "PLATE_IN_COMPLETE") {
    Serial.println("[UNO] Plate return complete");
    plateOut = false;
  }
  else if (msg == "PLATE_DISPENSE_TIMEOUT") {
    Serial.println("[UNO] Plate dispense timeout!");
    plateTimeout = true;
    noPlate = true;
  }
  else if (msg == "FEED_STOP_DUE_TO_NO_PLATE" || msg == "FEED_STOP_DUE_TO_NO_FOOD") {
    isFeeding = false; webFeeding = false; feedingStartTime = 0;
    Serial.println("[UNO] Feed stopped: " + msg);
  }
  else if (msg.startsWith("WSTORAGE:")) {
    String data = msg.substring(9);
    int colon = data.indexOf(':');
    if (colon > 0) {
      wStoragePct = data.substring(0, colon).toFloat();
      wStorageLevelCm = data.substring(colon + 1).toFloat();
      wStorageLow = (wStoragePct <= LOW_LEVEL_PCT);
    }
  }
  else if (msg == "BTN_FEED") {
    isFeeding = true; webFeeding = true; feedingStartTime = millis(); plateOut = false;
    plateTimeout = false;
    lastFeedCommandId = 0;   // physical button feed
  }
  else if (msg.startsWith("FEED_COMPLETE:")) {
    isFeeding = false; webFeeding = false; feedingStartTime = 0; plateIdleWarn = false; plateOut = true;
    logFeedComplete(msg.substring(14).toFloat());
  }
  else if (msg == "FEED_TIMEOUT") { isFeeding = false; webFeeding = false; feedingStartTime = 0; }
}

// The Uno cannot read serial while its servos move (it sits in delay()), and its
// receive buffer is only 64 bytes. So after a command that moves a servo we send
// NOTHING until the Uno reports it is done (or the timeout passes).
bool waitForArduino(const char* doneA, const char* doneB, unsigned long timeoutMs) {
  unsigned long start = millis();
  while (millis() - start < timeoutMs) {
    while (Serial2.available()) {
      String msg = Serial2.readStringUntil('\n');
      msg.trim();
      handleArduinoMessage(msg);
      if (msg == doneA || (doneB && msg == doneB)) return true;
    }
    // keep the dashboard's "connected" status alive during long moves
    if (millis() - lastWebSend >= webSendInterval) { sendDataToWeb(); lastWebSend = millis(); }
    delay(20);
  }
  Serial.printf("[UNO] Timed out waiting for %s\n", doneA);
  return false;
}

// Give the Uno a few fresh weight readings before the next command, so it does
// not decide "plate present" from a reading taken before the plate moved.
void refreshArduinoWeight() {
  for (int i = 0; i < 3; i++) { readFoodWeight(); delay(300); }
}

void sendDispose() {
  Serial2.println(plateOut ? "PLATE_IN_THEN_DISPOSE" : "DISPOSE");
  waitForArduino("DISPOSE_COMPLETE", "DISPOSE_SKIPPED", DISPOSE_WAIT_MS);
  plateInside = false;
  plateOut    = false;
}

void checkWebCommands() {
  if (WiFi.status() != WL_CONNECTED) return;
  HTTPClient http;
  http.begin(feedingCommandUrl);
  http.addHeader("X-Device-Key", deviceKey);
  applyHttpTimeout(http);
  int code = http.GET();
  String response = (code > 0) ? http.getString() : "";
  http.end();                       // close before any long wait below
  if (code <= 0) return;

  StaticJsonDocument<384> doc;
  if (deserializeJson(doc, response) || doc["command"].isNull()) return;
  String command = doc["command"].as<String>();
  float  portion = doc["portion"] | 0.0f;

  if (command == "pump") {
    // "portion" carries the requested seconds for pump commands
    if (!autoPumping && !manualPumping && waterWeight < WATER_TARGET_GRAMS) {
      int secs = constrain((int)portion, 1, 60);
      manualPumping  = true;
      pumpStartTime  = millis();
      pumpDurationMs = secs * 1000UL;
      Serial2.print("PUMP:"); Serial2.println(secs);
      Serial.printf("[PUMP] Manual pump started (%ds)\n", secs);
    }
    return;
  }

  if (command == "tare") {
    scale.tare();
    Serial.println("[CMD] Food scale tared");
    return;
  }

  if (command == "plate_in") {
    Serial2.println("PLATE_IN_ONLY");
    Serial.println("[CMD] Plate in only");
    waitForArduino("PLATE_IN_COMPLETE", nullptr, PLATE_IN_WAIT_MS);
    refreshArduinoWeight();
    return;
  }

  if (command == "dispose") {
    Serial.println("[CMD] Dispose");
    sendDispose();
    refreshArduinoWeight();
    return;
  }

  if (command == "feed_now" && !isFeeding) {
    String plateType   = doc["plate_type"] | "stock";
    float  grams       = portion > 0 ? portion : 30.0f;
    bool   isScheduled = doc["is_scheduled"] | false;
    bool   isNew       = (plateType == "new" || isScheduled);
    lastFeedCommandId  = doc["command_id"] | 0L;

    Serial.printf("[CMD] FEED %s %.1fg scheduled=%d plateOut=%d\n",
      isNew ? "new" : "stock", grams, isScheduled, plateOut);

    // Step 1: get the old plate out of the way, and WAIT for the Uno to finish.
    if (isNew) {
      sendDispose();                       // old plate (if any) is thrown away
    } else if (plateOut) {
      Serial2.println("PLATE_IN_ONLY");    // stock plate: just bring it back in
      waitForArduino("PLATE_IN_COMPLETE", nullptr, PLATE_IN_WAIT_MS);
      plateOut = false;
    }
    refreshArduinoWeight();

    // Step 2: start the feed. The Uno is idle now, so it will receive this.
    isFeeding = true; webFeeding = true;
    feedingStartTime = millis();
    foodEmpty = false; noPlate = false; plateTimeout = false;
    Serial2.print("FEED_START:"); Serial2.print(isNew ? "new" : "stock");
    Serial2.print(":"); Serial2.println(grams, 1);
    return;
  }

  if (command == "feed_stop" && isFeeding) {
    isFeeding = false; webFeeding = false; feedingStartTime = 0;
    Serial2.println("FEED_STOP");
    Serial.println("[CMD] Feed stopped");
  }
}

void checkSchedule() {
  if (WiFi.status() != WL_CONNECTED) return;
  HTTPClient http;
  http.begin(scheduleUrl);
  http.addHeader("X-Device-Key", deviceKey);
  applyHttpTimeout(http);
  int code = http.GET();
  if (code > 0) {
    String response = http.getString();
    StaticJsonDocument<200> doc;
    if (!deserializeJson(doc, response) && doc["triggered"].as<bool>())
      Serial.println("[SCHED] Schedule triggered");
  }
  http.end();
}

void handleSerialCommands() {
  if (!Serial.available()) return;
  String input = Serial.readStringUntil('\n');
  input.trim(); input.toLowerCase();

  if      (input == "tare")   { scale.tare(); Serial.println("[TARE] Done"); }
  else if (input == "wtare")  {
    if (!waterScaleConnected) { Serial.println("[WATER] Not connected!"); return; }
    waterScale.tare();
    waterWeight = 0;
    Serial.println("[WATER TARE] Done");
  }
  else if (input == "raw")    { if (scale.is_ready()) { Serial.print("[RAW] "); Serial.println(scale.get_value(3)); } }
  else if (input == "status") {
    Serial.printf("Food:%.1fg Water:%.1fg Storage:%.1f%% Feeding:%d PlateOut:%d\n",
      currentWeight, waterWeight, wStoragePct, isFeeding, plateOut);
  }
}

void setup() {
#if DISABLE_BROWNOUT_DETECTOR
  WRITE_PERI_REG(RTC_CNTL_BROWN_OUT_REG, 0);
#endif
  setCpuFrequencyMhz(80);

  Serial.begin(115200);
  Serial2.begin(9600, SERIAL_8N1, RXD2, TXD2);

  Serial.println("\n================================");
  Serial.println("  IntelliFeed ESP32");
  Serial.println("================================");
  Serial.println("[FIX] Schedule Feeding with Proper Plate Disposal");

  Serial.print("[INIT] Food scale... ");
  scale.begin(LOADCELL_DT, LOADCELL_SCK);
  unsigned long t = millis();
  while (!scale.is_ready() && millis() - t < 2000) { delay(100); Serial.print("."); }
  if (scale.is_ready()) { scale.set_scale(calibrationFactor); scale.tare(); Serial.println(" OK"); }
  else Serial.println(" FAILED");

  Serial.println("[INIT] Water plate scale...");
  waterScale.begin(WATER_DT, WATER_SCK);
  unsigned long wt = millis();
  while (!waterScale.is_ready() && millis() - wt < 3000) { delay(50); }

  if (!waterScale.is_ready()) {
    waterScaleConnected = false;
    Serial.println("WATER SCALE ERROR - check wiring");
  } else {
    waterScaleConnected = true;
    waterScale.set_scale(waterCalFactor);
    waterScale.tare();
    Serial.println("Water scale OK");
  }

  connectWiFi();
  delay(500);
  Serial2.println("ESP32 READY");
}

void loop() {
  checkWiFi();
  handleSerialCommands();

  if (millis() - lastWeightRead >= weightInterval) {
    readFoodWeight();
    lastWeightRead = millis();
  }
  if (millis() - lastWaterRead >= waterInterval) {
    readWaterWeight();
    lastWaterRead = millis();
  }

  while (Serial2.available()) {
    String msg = Serial2.readStringUntil('\n');
    msg.trim();
    handleArduinoMessage(msg);
  }

  if (isFeeding && webFeeding && feedingStartTime > 0 && millis() - feedingStartTime > maxFeedTime) {
    isFeeding = false; webFeeding = false; feedingStartTime = 0;
    Serial2.println("FEED_STOP");
  }

  if (millis() - lastWebSend >= webSendInterval) { sendDataToWeb(); lastWebSend = millis(); }
  if (millis() - lastCmdCheck >= cmdCheckInterval) { checkWebCommands(); lastCmdCheck = millis(); }
  if (millis() - lastSchedCheck >= schedCheckInterval) { checkSchedule(); lastSchedCheck = millis(); }
  if (millis() - lastHeartbeat >= heartbeatInterval) {
    Serial2.println("ESP32 READY");
    lastHeartbeat = millis();
  }

  delay(25);
}