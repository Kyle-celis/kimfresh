#include <WiFi.h>
#include <DHT22.h>
#include <NimBLEDevice.h>
#include <LittleFS.h>

#define DHT22_PIN 5
#define LED_PIN 2

const float BAD_TEMP = 10.0;
const float GOOD_TEMP = 4.0;
const float GOOD_HUM = 90.0;
const float HUM_RANGE = 5.0;

const unsigned long SENSOR_INTERVAL = 20000;
const char* STORAGE_FILE = "/readings.txt";

DHT22 dht22(DHT22_PIN);
bool bleConnected = false;
bool isAlert = false;
unsigned long lastSensorRead = 0;

NimBLEServer* pServer = NULL;
NimBLECharacteristic* pCharacteristic = NULL;
NimBLECharacteristic* pCommandCharacteristic = NULL;

void sendStoredReadings();
void storeReading(float temp, float hum, bool alert);
void reportStoredCount();
void checkConditions(float temp, float hum);

class MyServerCallbacks : public NimBLEServerCallbacks {
  void onConnect(NimBLEServer* pServer, NimBLEConnInfo& connInfo) override {
    bleConnected = true;
    Serial.println("Phone connected!");
    digitalWrite(LED_PIN, LOW);
    
    if (pCharacteristic) {
      delay(300);
      String data = "CONNECTED|" + String(millis());
      pCharacteristic->setValue(data.c_str());
      pCharacteristic->notify();
      Serial.println("Sent: CONNECTED notification");
    }
  }
  
  void onDisconnect(NimBLEServer* pServer, NimBLEConnInfo& connInfo, int reason) override {
    bleConnected = false;
    Serial.println("Phone disconnected - device stays advertising!");
    digitalWrite(LED_PIN, HIGH);
    NimBLEDevice::getAdvertising()->start();
    Serial.println("Advertising restarted");
  }
};

class CommandCallbacks : public NimBLECharacteristicCallbacks {
  void onWrite(NimBLECharacteristic* pChar, NimBLEConnInfo& connInfo) override {
    String value = pChar->getValue().c_str();
    value.trim();
    Serial.println("Command received: " + value);
    if (value == "REQUEST_STORED") {
      sendStoredReadings();
    }
  }
};

void setup() {
  Serial.begin(115200);
  WiFi.mode(WIFI_OFF);
  
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, HIGH);
  
  if (!LittleFS.begin(true)) {
    Serial.println("LittleFS mount failed!");
  } else {
    Serial.println("LittleFS ready");
  }
  
  NimBLEDevice::init("Kimchi_Monitor");
  NimBLEDevice::setMTU(100);
  pServer = NimBLEDevice::createServer();
  pServer->setCallbacks(new MyServerCallbacks());
  
  NimBLEService* pService = pServer->createService("ABCD");
  
  pCharacteristic = pService->createCharacteristic(
    "1234",
    NIMBLE_PROPERTY::READ | NIMBLE_PROPERTY::NOTIFY
  );
  pCharacteristic->setValue("Waiting for sensor...");
  
  pCommandCharacteristic = pService->createCharacteristic(
    "5678",
    NIMBLE_PROPERTY::WRITE
  );
  pCommandCharacteristic->setCallbacks(new CommandCallbacks());
  
  pService->start();
  NimBLEAdvertising* pAdvertising = NimBLEDevice::getAdvertising();
  pAdvertising->addServiceUUID("ABCD");
  pAdvertising->start();
  
  Serial.println("=== Kimchi Monitor Started ===");
  reportStoredCount();
}

void loop() {
  unsigned long currentTime = millis();

  if (currentTime - lastSensorRead >= SENSOR_INTERVAL) {
    lastSensorRead = currentTime;

    float temp = dht22.getTemperature();
    float hum = dht22.getHumidity();

    if (dht22.getLastError() == dht22.OK) {
      Serial.print("Temp: ");
      Serial.print(temp, 1);
      Serial.print(" C | Humidity: ");
      Serial.print(hum, 1);
      Serial.println(" %");

      checkConditions(temp, hum);

      String data = "T:" + String(temp, 1) + "C H:" + String(hum, 1) + "%";
      data += isAlert ? " !" : " OK";

      if (bleConnected) {
        pCharacteristic->setValue(data.c_str());
        pCharacteristic->notify();
        Serial.println("Data sent to phone!");
      } else {
        storeReading(temp, hum, isAlert);
      }
    } else {
      Serial.println("Sensor error!");
    }
  }
}

void checkConditions(float temp, float hum) {
  bool tempBad = (temp > BAD_TEMP);
  bool humBad = (hum > GOOD_HUM + HUM_RANGE) || (hum < GOOD_HUM - HUM_RANGE);
  bool tempCold = (temp < GOOD_TEMP - 2);
  
  if (tempBad || humBad || tempCold) {
    if (!isAlert) {
      Serial.println(">>> ALERT! Problem detected! <<<");
      isAlert = true;
    }
  } else {
    if (isAlert) {
      Serial.println(">>> All good now! <<<");
      isAlert = false;
    }
  }
}

void storeReading(float temp, float hum, bool alert) {
  File file = LittleFS.open(STORAGE_FILE, "a");
  if (!file) {
    Serial.println("Failed to open storage file!");
    return;
  }
  file.print(temp, 1);
  file.print(",");
  file.print(hum, 1);
  file.print(",");
  file.println(alert ? "1" : "0");
  file.close();
  Serial.println("Reading saved to flash");
}

void sendStoredReadings() {
  if (!LittleFS.exists(STORAGE_FILE)) {
    Serial.println("No stored readings to send");
    return;
  }
  
  File file = LittleFS.open(STORAGE_FILE, "r");
  if (!file) {
    Serial.println("Failed to open storage file for reading!");
    return;
  }
  
  String combined = "";
  int count = 0;
  
  while (file.available()) {
    String line = file.readStringUntil('\n');
    line.trim();
    if (line.length() == 0) continue;
    
    int firstComma = line.indexOf(',');
    int secondComma = line.indexOf(',', firstComma + 1);
    if (firstComma == -1 || secondComma == -1) continue;
    
    String tempStr = line.substring(0, firstComma);
    String humStr = line.substring(firstComma + 1, secondComma);
    String alertStr = line.substring(secondComma + 1);
    
    if (count > 0) combined += ",";
    combined += tempStr + "/" + humStr;
    if (alertStr == "1") combined += "!";
    
    count++;
  }
  file.close();
  
  if (count > 0) {
    String data = "S" + String(count) + ":" + combined;
    pCharacteristic->setValue(data.c_str());
    pCharacteristic->notify();
    Serial.println("Sent stored readings: " + data);
    
    LittleFS.remove(STORAGE_FILE);
    Serial.print(count);
    Serial.println(" stored reading(s) sent and cleared.");
  } else {
    Serial.println("Storage file empty");
  }
}

void reportStoredCount() {
  if (!LittleFS.exists(STORAGE_FILE)) return;
  
  File file = LittleFS.open(STORAGE_FILE, "r");
  int count = 0;
  while (file.available()) {
    String line = file.readStringUntil('\n');
    line.trim();
    if (line.length() > 0) count++;
  }
  file.close();
  
  if (count > 0) {
    Serial.print(count);
    Serial.println(" stored reading(s) waiting");
  }
}
