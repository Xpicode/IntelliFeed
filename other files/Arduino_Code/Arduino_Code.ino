/*
 * IntelliFeed - Arduino Uno (FULL FIXED - Plate IN first)
 */

#include <Wire.h>
#include <U8g2lib.h>
#include <Servo.h>

#define STEP_PIN  12
#define DIR_PIN   13
#define ENA_PIN   11
#define FOOD_PIN    6
#define PLATE_PIN   5
#define OTHER_PIN   3
#define PUMP_PIN    7
#define PLATE_TRIG_PIN            9
#define PLATE_ECHO_PIN            10
#define PLATE_US_TIMEOUT          5000UL
#define PLATE_DISPENSER_THRESHOLD_CM  8.0
#define WSTORAGE_TRIG_PIN   A1
#define WSTORAGE_ECHO_PIN   A2
#define WSTORAGE_US_TIMEOUT 5000UL
#define TANK_FULL_DIST       9.7
#define TANK_EMPTY_DIST     21.5
#define WSTORAGE_LOW_PCT    20.0
#define WATER_STORAGE_MIN_PCT  20.0
#define PLATE_DISPENSE_TIMEOUT_MS 15000
#define WATER_PLATE_MIN_GRAMS 3.5

// Food dispenser timeout settings
#define FOOD_DISPENSER_TIMEOUT_MS  15000UL
#define FOOD_EMPTY_MIN_GAIN        2.0

unsigned long lastPlateUsRead  = 0;
const long    plateUsInterval  = 1000;
unsigned long lastWstorageRead = 0;
const long    wstorageInterval = 3000;
float wStoragePct   = 0.0;
float wStorageLvlCm = 0.0;
bool  wStorageLow   = false;

#define BTN1_PIN    4
#define BTN2_PIN   A0
#define DEBOUNCE_MS 50UL
bool          btn1Last = HIGH, btn2Last = HIGH;
unsigned long btn1Time = 0,    btn2Time = 0;
static bool   btn1Handled = false;
static bool   btn2Handled = false;

#define STEP_DELAY 800
#define FOOD_RUN        0
#define FOOD_STOP       90
#define PLATE_DOWN      180
#define PLATE_FINAL     90
#define PLATE_NEUTRAL   0
#define OTHER_PUSH      180
#define OTHER_REST      170
#define PLATE_DETECT_GRAMS  20.5
#define WEIGHT_DROP_GRAMS   2.0
#define DISP_DUR            5000UL
#define DISP_INT            500UL
#define MAX_FEED            90000UL
#define MAX_PLATE_TIME      4500000UL
#define PLATE_MOVE_TIME     3050UL
#define WEIGHT_MONITOR_MS   30000UL
#define NO_PLATE_TIMEOUT_MS 10000UL

// AUTO-DISPOSE
#define AUTO_DISPOSE_MS        2700000UL
#define AUTO_DISPOSE_THRESHOLD 2.0
#define AUTO_DISPOSE_WARN_MS   2400000UL

bool noPlateWarned   = false;
bool noPlateDetected = false;

float         autoDispLastWeight = 0.0;
unsigned long autoDispLastChange = 0;
bool          autoDispDone       = false;
bool          autoDispWarned     = false;

bool          plateDispenseTimeout = false;
unsigned long plateDispenseStartTime = 0;

U8G2_SSD1306_128X64_NONAME_1_HW_I2C u8g2(U8G2_R0, U8X8_PIN_NONE);
Servo foodServo;
Servo plateServo;
Servo otherServo;

bool  feeding  = false, webFeed = false;
bool  newPlate = false, plateMov = false, foodOpen = false;
float targetG  = 30.0,  currentG = 0.0;
unsigned long feedStart  = 0, plateStart = 0, lastHB = 0;
float         weightWhenFoodOpened = 0.0;
unsigned long foodOpenedTime       = 0;
bool          foodEmptyWarned      = false;
bool          plateOut         = false;
float         weightAtPlateOut = 0.0;
unsigned long plateOutTime     = 0;

// Food dispenser tracking
unsigned long foodDispenserStartTime = 0;
float         weightAtFoodStart = 0.0;
bool          foodDispenserTimeout = false;
bool          foodStorageEmpty = false;

float waterPlateWeight = 0.0;
unsigned long lastWaterWeightReceived = 0;

#define BTN2_HOLD_MS   5000UL
unsigned long btn2HoldStart  = 0;
bool          btn2HoldActive = false;
bool          btn2SetupSent  = false;
bool          esp32SetupMode  = false;

unsigned long lastStepTime = 0;
bool          stepState    = false;
bool          dispOn   = false;
unsigned long dispOff  = 0, lastDisp = 0;
char    rxBuf[48];
uint8_t rxLen = 0;
uint8_t       dots    = 0;
unsigned long dotTime = 0;
bool          pumpActive  = false;
unsigned long pumpEndTime = 0;

float readUltrasonic(int trigPin, int echoPin, unsigned long timeout) {
  digitalWrite(trigPin, LOW);  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH); delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  long dur = pulseIn(echoPin, HIGH, timeout);
  if (dur == 0) return -1;
  float d = dur * 0.01715;
  return (d <= 0 || d > 200) ? -1 : d;
}

void checkPlateDispenser() {
  float d = readUltrasonic(PLATE_TRIG_PIN, PLATE_ECHO_PIN, PLATE_US_TIMEOUT);
  if (d < 0) return;
  bool present = (d < PLATE_DISPENSER_THRESHOLD_CM);
  static int8_t last = -1;
  if ((int8_t)present != last) {
    last = (int8_t)present;
    if (!present) {
      Serial.println(F("PLATE_OUTSIDE"));
    } else {
      Serial.println(F("PLATE_INSIDE"));
    }
  }
}

void checkWaterStorage() {
  float d = readUltrasonic(WSTORAGE_TRIG_PIN, WSTORAGE_ECHO_PIN, WSTORAGE_US_TIMEOUT);
  if (d < 0) return;
  if (d < TANK_FULL_DIST)  d = TANK_FULL_DIST;
  if (d > TANK_EMPTY_DIST) d = TANK_EMPTY_DIST;
  float range = TANK_EMPTY_DIST - TANK_FULL_DIST;
  float pct   = ((TANK_EMPTY_DIST - d) / range) * 100.0;
  float lvl   = TANK_EMPTY_DIST - d;
  if (pct > 100) pct = 100;
  if (pct < 0)   pct = 0;
  wStoragePct   = pct;
  wStorageLvlCm = lvl;
  wStorageLow   = (pct <= WSTORAGE_LOW_PCT);
  Serial.print(F("WSTORAGE:")); Serial.print(pct, 1);
  Serial.print(F(":")); Serial.println(lvl, 1);
}

void executeDisposeSequence() {
  Serial.println(F("[DISPOSE] Executing dispose sequence..."));
  otherServo.write(OTHER_PUSH); delay(800);
  plateServo.attach(PLATE_PIN);
  plateServo.write(PLATE_DOWN); delay(PLATE_MOVE_TIME);
  plateServo.write(PLATE_NEUTRAL); delay(PLATE_MOVE_TIME);
  plateServo.detach();
  otherServo.write(OTHER_REST); delay(500);
  plateOut   = false;
  autoDispDone    = false;
  autoDispWarned  = false;
  dispOff = millis() + DISP_DUR;
  Serial.println(F("DISPOSE_COMPLETE"));
  delay(200);
}

// CRITICAL FIX: Function to bring plate inside and SEND CONFIRMATION
void bringPlateInside() {
  if (!plateOut) {
    Serial.println(F("[PLATE] Already inside"));
    Serial.println(F("PLATE_IN_COMPLETE"));
    return;
  }
  
  Serial.println(F("========================================"));
  Serial.println(F("[PLATE] PLATE IS OUT - BRINGING INSIDE!"));
  Serial.println(F("========================================"));
  
  // Execute plate servo to bring inside
  plateServoIn();
  
  // Wait for servo to complete
  delay(3000);
  
  // Set plateOut to false
  plateOut = false;
  
  Serial.println(F("[PLATE] Plate successfully brought inside"));
  Serial.println(F("PLATE_IN_COMPLETE"));  // Send confirmation to ESP32
  
  delay(500);
}

void disposeAndGetNewPlate() {
  Serial.println(F("[NEW PLATE] Disposing old plate first..."));
  
  // Check if there's a plate to dispose
  if (currentG >= PLATE_DETECT_GRAMS) {
    executeDisposeSequence();
    delay(1000);
  }
  
  Serial.println(F("[NEW PLATE] Getting new plate..."));
  startPlate();
}

void startFoodDispensing() {
  foodOpen = true;
  weightWhenFoodOpened = currentG;
  weightAtFoodStart = currentG;
  foodOpenedTime = millis();
  foodDispenserStartTime = millis();
  foodDispenserTimeout = false;
  foodStorageEmpty = false;
  foodEmptyWarned = false;
  foodServo.write(FOOD_RUN);
  Serial.println(F("[FOOD] Food dispenser started"));
  Serial.print(F("[FOOD] Initial weight: ")); Serial.print(currentG, 1); Serial.println(F("g"));
  Serial.print(F("[FOOD] Target: ")); Serial.print(targetG, 1); Serial.println(F("g"));
}

void checkFoodDispenser() {
  if (!foodOpen) return;
  
  float weightGain = currentG - weightAtFoodStart;
  unsigned long elapsedTime = millis() - foodDispenserStartTime;
  
  // Check if we've reached target weight
  if (currentG >= targetG) {
    float dispensed = currentG - weightWhenFoodOpened;
    stopFeed();
    Serial.print(F("FEED_COMPLETE:")); Serial.println(dispensed, 1);
    onFeedComplete();
    Serial.println(F("[FOOD] Target reached"));
    return;
  }
  
  // Check for food dispenser timeout (15 seconds with no food detected)
  if (!foodDispenserTimeout && elapsedTime >= FOOD_DISPENSER_TIMEOUT_MS && weightGain < FOOD_EMPTY_MIN_GAIN) {
    foodDispenserTimeout = true;
    foodStorageEmpty = true;
    
    Serial.println(F("[FOOD] FOOD DISPENSER TIMEOUT! No food detected after 15 seconds!"));
    
    // Stop the food dispenser
    foodServo.write(FOOD_STOP);
    foodOpen = false;
    
    // Send notification to ESP32
    Serial.println(F("FOOD_EMPTY"));
    
    // Still complete the feed with whatever was dispensed
    float dispensed = currentG - weightWhenFoodOpened;
    if (dispensed > 0) {
      Serial.print(F("[FOOD] Partial feed delivered: ")); Serial.print(dispensed, 1); Serial.println(F("g"));
      stopFeed();
      Serial.print(F("FEED_COMPLETE:")); Serial.println(dispensed, 1);
      onFeedComplete();
    } else {
      // No food at all - cancel feed
      Serial.println(F("[FOOD] No food dispensed - cancelling feed"));
      stopFeed();
      Serial.println(F("FEED_STOP_DUE_TO_NO_FOOD"));
    }
    return;
  }
  
  // Log progress
  static unsigned long lastProgressLog = 0;
  if (millis() - lastProgressLog >= 2000) {
    Serial.print(F("[FOOD] Progress: ")); Serial.print(weightGain, 1);
    Serial.print(F("g | Time: ")); Serial.print(elapsedTime / 1000);
    Serial.println(F("s"));
    lastProgressLog = millis();
  }
}

void checkButtons() {
  bool b1 = digitalRead(BTN1_PIN);
  if (b1 != btn1Last) { btn1Time = millis(); btn1Last = b1; }
  if ((millis() - btn1Time) >= DEBOUNCE_MS) {
    if (b1 == LOW && !btn1Handled) {
      btn1Handled = true;
      Serial.println(F("[BTN1] Feed Now pressed"));
      dispOn = true; u8g2.setPowerSave(0);
      dispOff = millis() + 2000UL;

      float btnFeedGrams = 30.0;
      
      // CRITICAL: If plate is out, bring it in first
      if (plateOut) {
        bringPlateInside();
      }
      
      // Check if there's already a plate on scale
      if (currentG >= PLATE_DETECT_GRAMS) {
        Serial.println(F("[BTN1] Stock plate feed"));
        plateOut = false; feeding = true; webFeed = true; newPlate = false;
        targetG = btnFeedGrams; feedStart = millis();
        dots = 0; dotTime = millis(); autoDispDone = false; autoDispWarned = false;
        startFoodDispensing();
        Serial.println(F("BTN_FEED"));
      } else {
        // NEW PLATE: Dispose first if there's any plate, then get new one
        Serial.println(F("[BTN1] New plate - disposing old first"));
        plateOut = false; feeding = true; webFeed = true; newPlate = true;
        targetG = btnFeedGrams; feedStart = millis();
        dots = 0; dotTime = millis(); autoDispDone = false; autoDispWarned = false;
        disposeAndGetNewPlate();
        foodOpen = false;
        Serial.println(F("BTN_FEED"));
      }
    } else if (b1 == HIGH) { btn1Handled = false; }
  }
  
  bool b2 = digitalRead(BTN2_PIN);
  if (b2 != btn2Last) { btn2Time = millis(); btn2Last = b2; }
  if ((millis() - btn2Time) >= DEBOUNCE_MS) {
    if (b2 == LOW) {
      if (!btn2HoldActive) {
        btn2HoldActive = true;
        btn2HoldStart  = millis();
        btn2SetupSent  = false;
      }
      unsigned long held = millis() - btn2HoldStart;
      if (held >= 2000UL && !btn2SetupSent) {
        dispOn = true; u8g2.setPowerSave(0);
      }
      if (held >= BTN2_HOLD_MS && !btn2SetupSent) {
        btn2SetupSent = true;
        Serial.println(F("BTN_SETUP"));
        dispOn = true; u8g2.setPowerSave(0);
        dispOff = millis() + 5000UL;
      }
    } else {
      if (btn2HoldActive && !btn2SetupSent) {
        btn2Handled = true;
        Serial.println(F("BTN_DISPOSE"));
        dispOn = true; u8g2.setPowerSave(0);
        dispOff = millis() + 2000UL;
      }
      btn2HoldActive = false;
      btn2HoldStart  = 0;
      btn2Handled    = false;
      btn2SetupSent  = false;
    }
  }
}

void enableStepper()  { digitalWrite(ENA_PIN, LOW); }
void disableStepper() { digitalWrite(ENA_PIN, HIGH); digitalWrite(STEP_PIN, LOW); }

void startPlate() {
  enableStepper(); digitalWrite(DIR_PIN, HIGH);
  lastStepTime = micros(); stepState = false;
  plateMov = true; plateStart = millis();
  plateDispenseStartTime = millis();
  plateDispenseTimeout = false;
  noPlateWarned = false; noPlateDetected = false;
  Serial.print(F("[PLATE] NEMA17 running..."));
}

void stopPlate() { 
  plateMov = false; 
  plateDispenseTimeout = false;
  disableStepper(); 
}

void plateServoOut() {
  plateServo.attach(PLATE_PIN);
  plateServo.write(PLATE_DOWN); delay(PLATE_MOVE_TIME);
  plateServo.detach();
  Serial.println(F("[PLATE SERVO] Plate out"));
}

void plateServoIn() {
  plateServo.attach(PLATE_PIN);
  plateServo.write(PLATE_FINAL); delay(PLATE_MOVE_TIME);
  plateServo.write(PLATE_NEUTRAL); delay(PLATE_MOVE_TIME);
  plateServo.detach();
  Serial.println(F("[PLATE SERVO] Plate in"));
}

void stopFeed() {
  feeding = false; webFeed = false; newPlate = false;
  foodOpen = false; 
  stopPlate(); 
  if (!foodDispenserTimeout) {
    foodServo.write(FOOD_STOP);
  }
  dispOff = millis() + DISP_DUR;
  foodEmptyWarned = false;
  foodDispenserTimeout = false;
  foodStorageEmpty = false;
  weightWhenFoodOpened = 0.0; 
  weightAtFoodStart = 0.0;
  foodDispenserStartTime = 0;
  Serial.println(F("[STOP] Feed stopped"));
}

void onFeedComplete() {
  Serial.println(F("[POST-FEED] Plate out..."));
  plateServoOut();
  weightAtPlateOut = currentG; plateOutTime = millis();
  plateOut = true; dispOn = true; u8g2.setPowerSave(0);
  autoDispLastWeight = currentG;
  autoDispLastChange = millis();
  autoDispDone       = false;
  autoDispWarned     = false;
}

void checkNoPlateWeight() {
  if (!plateMov || noPlateWarned || currentG >= PLATE_DETECT_GRAMS) return;
  if (millis() - plateStart >= NO_PLATE_TIMEOUT_MS) {
    noPlateWarned = true; noPlateDetected = true;
    Serial.println(F("PLATE_OUTSIDE"));
    dispOn = true; u8g2.setPowerSave(0);
    dispOff = millis() + 10000UL;
  }
}

void checkPlateOut() {
  if (!plateOut) return;

  if (!autoDispDone) {
    float diff = abs(currentG - autoDispLastWeight);
    if (diff >= AUTO_DISPOSE_THRESHOLD) {
      autoDispLastWeight = currentG;
      autoDispLastChange = millis();
    }
    unsigned long inactive = millis() - autoDispLastChange;

    if (!autoDispWarned && inactive >= AUTO_DISPOSE_WARN_MS) {
      autoDispWarned = true;
      Serial.println(F("PLATE_IDLE_WARN"));
    }

    if (inactive >= AUTO_DISPOSE_MS) {
      autoDispDone = true;
      Serial.println(F("AUTO_DISPOSE_COMPLETE"));
      dispOn = true; u8g2.setPowerSave(0);
      executeDisposeSequence();
      return;
    }
  }
}

void updateStepper() {
  if (!plateMov) return;
  unsigned long now = micros();
  if (now - lastStepTime >= STEP_DELAY) {
    lastStepTime = now; stepState = !stepState;
    digitalWrite(STEP_PIN, stepState ? HIGH : LOW);
  }
}

void checkPump() {
  if (!pumpActive) return;
  if (millis() >= pumpEndTime) {
    pumpActive = false; pumpEndTime = 0;
    digitalWrite(PUMP_PIN, HIGH);
    Serial.println(F("[PUMP] Done"));
  }
}

bool isWaterPlatePresent() {
  bool hasRecentData = (millis() - lastWaterWeightReceived < 5000);
  bool hasWeight = (waterPlateWeight >= WATER_PLATE_MIN_GRAMS);
  return (hasRecentData && hasWeight);
}

void processCmd(char* cmd) {
  while (*cmd == ' ') cmd++;
  uint8_t len = strlen(cmd);
  while (len > 0 && cmd[len-1] == ' ') cmd[--len] = 0;
  if (len == 0) return;
  Serial.print(F("[RX] ")); Serial.println(cmd);

  if (strcmp(cmd, "TEST_PLATE") == 0)     { startPlate(); return; }
  if (strcmp(cmd, "TEST_STOP") == 0)      { stopPlate(); return; }
  if (strcmp(cmd, "TEST_FOOD") == 0) {
    foodServo.write(FOOD_RUN); delay(5000); foodServo.write(FOOD_STOP);
    return;
  }
  if (strcmp(cmd, "TEST_PLATE_OUT") == 0) { plateServoOut(); return; }
  if (strcmp(cmd, "TEST_PLATE_IN") == 0)  { plateServoIn(); plateOut = false; return; }
  if (strcmp(cmd, "TEST_OTHER") == 0) {
    otherServo.write(OTHER_PUSH); delay(1000); otherServo.write(OTHER_REST);
    return;
  }
  if (strcmp(cmd, "TEST_PUMP") == 0) {
    if (!isWaterPlatePresent()) { return; }
    pumpActive = true;
    pumpEndTime = millis() + 3000UL;
    digitalWrite(PUMP_PIN, LOW);
    return;
  }
  if (strcmp(cmd, "TEST_RESET") == 0) {
    stopPlate(); foodServo.write(FOOD_STOP); plateOut = false;
    noPlateWarned = false; noPlateDetected = false; autoDispDone = false;
    foodDispenserTimeout = false;
    foodStorageEmpty = false;
    return;
  }
  if (strcmp(cmd, "PLATE_IN_THEN_DISPOSE") == 0) {
    dispOn = true; u8g2.setPowerSave(0);
    Serial.println(F("[PLATE_IN_THEN_DISPOSE] Bringing plate in first..."));
    
    // Step 1: Bring plate inside
    plateServoIn();
    plateOut = false;
    delay(500);
    
    // Step 2: Now dispose
    if (currentG >= PLATE_DETECT_GRAMS) {
        executeDisposeSequence();
    } else {
        Serial.println(F("DISPOSE_SKIPPED"));
    }
    
    dispOff = millis() + DISP_DUR;
    Serial.println(F("DISPOSE_COMPLETE"));
    return;
}
  if (strcmp(cmd, "TEST_DISPOSE") == 0) {
    executeDisposeSequence();
    return;
  }

  if (strcmp(cmd, "PLATE_IN_ONLY") == 0) {
    dispOn = true; u8g2.setPowerSave(0);
    Serial.println(F("[PLATE_IN_ONLY] Returning plate inside..."));
    plateServoIn();
    plateOut = false;
    dispOff = millis() + DISP_DUR;
    Serial.println(F("PLATE_IN_COMPLETE"));
    return;
  }

  if (strcmp(cmd, "DISPOSE") == 0) {
    dispOn = true; u8g2.setPowerSave(0);
    if (currentG < PLATE_DETECT_GRAMS) {
      Serial.println(F("DISPOSE_SKIPPED"));
      dispOff = millis() + DISP_DUR; 
      return;
    }
    executeDisposeSequence();
    return;
  }
  
  if (strncmp(cmd, "PUMP:", 5) == 0) {
    int secs = atoi(cmd + 5);
    if (!isWaterPlatePresent()) { return; }
    if (wStoragePct <= WATER_STORAGE_MIN_PCT) { return; }
    if (secs <= 0) {
      pumpActive = false; digitalWrite(PUMP_PIN, HIGH);
      return;
    }
    if (secs > 120) secs = 120;
    pumpActive = true;
    pumpEndTime = millis() + ((unsigned long)secs * 1000UL);
    digitalWrite(PUMP_PIN, LOW);
    return;
  }
  
  if (strncmp(cmd, "FEED_START:", 11) == 0) {
    char* p1 = cmd + 11;
    char* p2 = strchr(p1, ':');
    if (!p2) { return; }
    *p2 = 0;
    float grams = atof(p2 + 1);
    if (grams <= 0) grams = 30.0;
    bool isNew = (strcmp(p1, "new") == 0);
    
    Serial.println(F("========================================"));
    Serial.print(F("[FEED] Command received: "));
    Serial.print(isNew ? "NEW" : "STOCK");
    Serial.print(F(" ")); 
    Serial.print(grams); 
    Serial.println(F("g"));
    Serial.print(F("[FEED] PlateOut status: "));
    if (plateOut) {
      Serial.println(F("YES (OUTSIDE)"));
    } else {
      Serial.println(F("NO (INSIDE)"));
    }
    
    // CRITICAL FIX: If plate is out, bring it in first
    if (plateOut) {
      bringPlateInside();
    }
    
    Serial.println(F("[FEED] Starting feed sequence..."));
    
    plateOut = false; feeding = true; webFeed = true; newPlate = isNew;
    targetG = grams; feedStart = millis();
    dots = 0; dotTime = millis(); dispOn = true; u8g2.setPowerSave(0);
    autoDispDone = false; autoDispWarned = false;
    foodDispenserTimeout = false;
    foodStorageEmpty = false;
    
    if (isNew) { 
      // NEW PLATE: Dispose first if there's any plate, then get new one
      disposeAndGetNewPlate();
      foodOpen = false; 
    } else {
      startFoodDispensing();
    }
    return;
  }
  if (strcmp(cmd, "FEED_STOP") == 0) { stopFeed(); return; }
  
  if (strncmp(cmd, "WEIGHT:", 7) == 0) {
    currentG = atof(cmd + 7); lastHB = millis();
    
    if (plateMov && !plateDispenseTimeout) {
      if (millis() - plateDispenseStartTime >= PLATE_DISPENSE_TIMEOUT_MS) {
        plateDispenseTimeout = true;
        stopPlate();
        Serial.println(F("PLATE_DISPENSE_TIMEOUT"));
        dispOn = true; u8g2.setPowerSave(0);
        dispOff = millis() + 10000UL;
        if (feeding) { stopFeed(); Serial.println(F("FEED_STOP_DUE_TO_NO_PLATE")); }
        return;
      }
    }
    
    if (plateMov && currentG >= PLATE_DETECT_GRAMS) {
      stopPlate(); noPlateWarned = false; noPlateDetected = false;
      if (feeding && !foodOpen && newPlate) {
        startFoodDispensing();
      }
    }
    
    // Check food dispenser status
    if (feeding && foodOpen) {
      checkFoodDispenser();
    }
    
    return;
  }
  
  if (strncmp(cmd, "WATER_WEIGHT:", 13) == 0) {
    waterPlateWeight = atof(cmd + 13);
    lastWaterWeightReceived = millis();
    return;
  }
  
  if (strcmp(cmd, "SETUP_MODE") == 0) {
    esp32SetupMode = true; dispOn = true; u8g2.setPowerSave(0);
    dispOff = millis() + 30000UL;
    return;
  }
  if (strncmp(cmd, "ESP32", 5) == 0) {
    lastHB = millis();
    esp32SetupMode = false;
    return;
  }
}

void checkSerial() {
  while (Serial.available()) {
    char c = (char)Serial.read();
    if (c == '\n') { rxBuf[rxLen] = 0; processCmd(rxBuf); rxLen = 0; }
    else if (c != '\r') { if (rxLen < 47) rxBuf[rxLen++] = c; else rxLen = 0; }
  }
}

void checkPlateTimeout() {
  if (!plateMov) return;
  if (millis() - plateStart > MAX_PLATE_TIME) {
    stopPlate(); stopFeed();
  }
}

void checkTimeout() {
  if (!feeding) return;
  if (millis() - feedStart > MAX_FEED) { stopFeed(); Serial.println(F("FEED_TIMEOUT")); }
}

void checkDispOff() {
  if (feeding || plateMov || plateOut) return;
  if (dispOn && millis() >= dispOff) { dispOn = false; u8g2.setPowerSave(1); }
}

void drawDisplay() {
  if (millis() - dotTime >= 500) { dots = (dots + 1) % 4; dotTime = millis(); }
  u8g2.firstPage();
  do {
    u8g2.setFont(u8g2_font_6x10_tr);
    if (esp32SetupMode) {
      u8g2.drawStr(0, 10, "WIFI SETUP MODE");
      u8g2.drawStr(0, 26, "Connect: IntelliFeed");
    } else if (noPlateDetected && plateMov) {
      u8g2.drawStr(0, 10, "!! NO PLATE !!");
    } else if (!feeding && !plateMov && !plateOut) {
      bool linked = (millis() - lastHB < 5000UL);
      u8g2.drawStr(0, 10, "IntelliFeed");
      u8g2.drawStr(72, 10, linked ? "OK" : "--");
      u8g2.drawHLine(0, 12, 128);
      u8g2.setFont(u8g2_font_9x15_tr);
      char buf[12]; dtostrf(currentG, 5, 1, buf); strcat(buf, "g");
      u8g2.drawStr(0, 34, buf);
      u8g2.setFont(u8g2_font_6x10_tr);
      u8g2.drawStr(0, 48, "on plate");
      int bw = (int)(wStoragePct / 100.0 * 60);
      u8g2.drawFrame(60, 56, 60, 6);
      if (bw > 0) u8g2.drawBox(60, 56, bw, 6);
    } else if (plateMov) {
      u8g2.drawStr(0, 10, "GETTING PLATE");
      u8g2.drawHLine(0, 12, 128);
      u8g2.setFont(u8g2_font_9x15_tr);
      char wbuf[12]; dtostrf(currentG, 4, 1, wbuf); strcat(wbuf, "g");
      u8g2.drawStr(0, 34, wbuf);
      float pct = currentG / PLATE_DETECT_GRAMS;
      if (pct > 1.0f) pct = 1.0f;
      int bw = (int)(pct * 118);
      u8g2.drawFrame(5, 52, 118, 6);
      if (bw > 0) u8g2.drawBox(5, 52, bw, 6);
    } else if (plateOut) {
      u8g2.drawStr(0, 10, "PLATE OUT");
      u8g2.drawHLine(0, 12, 128);
      u8g2.setFont(u8g2_font_9x15_tr);
      char wbuf[12]; dtostrf(currentG, 4, 1, wbuf); strcat(wbuf, "g");
      u8g2.drawStr(0, 34, wbuf);
    } else if (feeding && foodOpen) {
      u8g2.drawStr(0, 10, foodStorageEmpty ? "FOOD EMPTY!" : "FEEDING");
      u8g2.drawHLine(0, 12, 128);
      u8g2.setFont(u8g2_font_9x15_tr);
      char wbuf[16]; dtostrf(currentG, 4, 1, wbuf); strcat(wbuf, "/");
      char tbuf[6]; dtostrf(targetG, 3, 0, tbuf); strcat(wbuf, tbuf); strcat(wbuf, "g");
      u8g2.drawStr(0, 34, wbuf);
      if (targetG > 0) {
        float pct = currentG / targetG;
        if (pct > 1.0f) pct = 1.0f;
        int bw = (int)(pct * 118);
        u8g2.drawFrame(5, 38, 118, 6); 
        if (bw > 0) u8g2.drawBox(5, 38, bw, 6);
      }
      u8g2.setFont(u8g2_font_6x10_tr);
      char lbuf[20];
      if (foodDispenserTimeout) strcpy(lbuf, "NO FOOD!");
      else strcpy(lbuf, "Feeding...");
      u8g2.drawStr(0, 54, lbuf);
    } else {
      u8g2.drawStr(0, 10, newPlate ? "NEW PLATE" : "STOCK");
      u8g2.drawHLine(0, 12, 128);
      u8g2.setFont(u8g2_font_9x15_tr);
      char wbuf[16]; dtostrf(currentG, 4, 1, wbuf); strcat(wbuf, "/");
      char tbuf[6]; dtostrf(targetG, 3, 0, tbuf); strcat(wbuf, tbuf); strcat(wbuf, "g");
      u8g2.drawStr(0, 34, wbuf);
      u8g2.setFont(u8g2_font_6x10_tr);
      char lbuf[14];
      if (foodOpen) strcpy_P(lbuf, PSTR("Feeding"));
      else          strcpy_P(lbuf, PSTR("Ready"));
      u8g2.drawStr(0, 54, lbuf);
    }
  } while (u8g2.nextPage());
}

void setup() {
  Serial.begin(9600);
  pinMode(STEP_PIN, OUTPUT); pinMode(DIR_PIN, OUTPUT); pinMode(ENA_PIN, OUTPUT);
  digitalWrite(STEP_PIN, LOW); digitalWrite(DIR_PIN, HIGH);
  disableStepper();
  foodServo.attach(FOOD_PIN); otherServo.attach(OTHER_PIN);
  foodServo.write(FOOD_STOP); otherServo.write(OTHER_REST);
  pinMode(PUMP_PIN, OUTPUT); digitalWrite(PUMP_PIN, HIGH);
  pinMode(PLATE_TRIG_PIN, OUTPUT); pinMode(PLATE_ECHO_PIN, INPUT);
  pinMode(WSTORAGE_TRIG_PIN, OUTPUT); pinMode(WSTORAGE_ECHO_PIN, INPUT);
  digitalWrite(PLATE_TRIG_PIN, LOW); digitalWrite(WSTORAGE_TRIG_PIN, LOW);
  pinMode(BTN1_PIN, INPUT_PULLUP);
  pinMode(BTN2_PIN, INPUT_PULLUP);
  delay(300);
  u8g2.begin();
  u8g2.setPowerSave(1);
  Serial.println(F("========================="));
  Serial.println(F("  IntelliFeed Arduino Uno"));
  Serial.println(F("========================="));
  Serial.println(F("[FIX] Plate out -> bring in first (WITH CONFIRMATION)"));
  Serial.println(F("[FIX] New plate -> dispose first"));
  Serial.println(F("[FIX] Food timeout: 15 seconds"));
  Serial.println(F("[READY]"));
}

void loop() {
  updateStepper();
  checkSerial();
  checkPump();
  checkButtons();
  checkNoPlateWeight();
  checkPlateOut();
  checkPlateTimeout();
  checkTimeout();
  checkDispOff();
  if (millis() - lastPlateUsRead >= plateUsInterval) {
    checkPlateDispenser(); lastPlateUsRead = millis();
  }
  if (millis() - lastWstorageRead >= wstorageInterval) {
    checkWaterStorage(); lastWstorageRead = millis();
  }
  if (dispOn && millis() - lastDisp >= DISP_INT) {
    drawDisplay(); lastDisp = millis();
  }
}