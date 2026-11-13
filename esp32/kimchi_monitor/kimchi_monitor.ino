#include <Arduino.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <NimBLEDevice.h>

// ---------- PINS ----------
#define SDA_PIN 21
#define SCL_PIN 22
#define RX_PIN 16
#define TX_PIN 17
#define DE_PIN 18
#define RE_PIN 19

// ---------- CONFIG ----------
#define SENSOR_ADDRESS 1
#define BAUD_RATE 9600
const float BAD_TEMP = 10.0;

// ---------- OBJECTS ----------
LiquidCrystal_I2C lcd(0x27, 16, 2);
HardwareSerial rs485(1);

// ---------- BLE ----------
NimBLEServer* pServer = NULL;
NimBLECharacteristic* pCharacteristic = NULL;
NimBLECharacteristic* pCommandCharacteristic = NULL;
bool bleConnected = false;

// ---------- STATE ----------
uint16_t reg1 = 0;
uint16_t reg2 = 0;
bool sensorOK = false;
bool isAlert = false;

// ---------- SENSOR READ ----------
bool readSensor() {
  while (rs485.available()) {
    rs485.read();
  }

  // Modbus RTU request: address 1, function 04, starting register 0x0001, read 2 registers.
  byte request[] = {
    SENSOR_ADDRESS, 0x04,
    0x00, 0x01,
    0x00, 0x02,
    0x20, 0x0B
  };

  digitalWrite(RE_PIN, HIGH);
  digitalWrite(DE_PIN, HIGH);
  delay(2);

  rs485.write(request, sizeof(request));
  rs485.flush();

  digitalWrite(DE_PIN, LOW);
  digitalWrite(RE_PIN, LOW);

  delay(300);

  byte response[9];
  int count = 0;

  while (rs485.available() && count < 9) {
    response[count++] = rs485.read();
  }

  if (count != 9 ||
      response[0] != SENSOR_ADDRESS ||
      response[1] != 0x04 ||
      response[2] != 0x04) {
    return false;
  }

  reg1 = ((uint16_t)response[3] << 8) | response[4];
  reg2 = ((uint16_t)response[5] << 8) | response[6];

  return true;
}

// ---------- BLE CALLBACKS ----------
class MyServerCallbacks : public NimBLEServerCallbacks {
  void onConnect(NimBLEServer* pServer, NimBLEConnInfo& connInfo) override {
    bleConnected = true;
    Serial.println("Phone connected!");

    if (pCharacteristic) {
      delay(300);
      String data = "CONNECTED|" + String(millis());
      pCharacteristic->setValue(data.c_str());
      pCharacteristic->notify();
      Serial.println("Sent: CONNECTED");
    }
  }

  void onDisconnect(NimBLEServer* pServer, NimBLEConnInfo& connInfo, int reason) override {
    bleConnected = false;
    Serial.println("Phone disconnected - advertising again");
    NimBLEDevice::getAdvertising()->start();
  }
};

class CommandCallbacks : public NimBLECharacteristicCallbacks {
  void onWrite(NimBLECharacteristic* pChar, NimBLEConnInfo& connInfo) override {
    String value = pChar->getValue().c_str();
    value.trim();
    Serial.println("Command received: " + value);
  }
};

// ---------- SETUP ----------
void setup() {
  Serial.begin(115200);
  delay(1000);
  Serial.println("\n=== KimFresh IoT — RS485 + LCD + BLE ===");

  // Initialize LCD
  Wire.begin(SDA_PIN, SCL_PIN);
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("KimFresh System");
  lcd.setCursor(0, 1);
  lcd.print("Starting...");
  delay(1500);
  lcd.clear();

  // Initialize RS485
  pinMode(DE_PIN, OUTPUT);
  pinMode(RE_PIN, OUTPUT);
  digitalWrite(DE_PIN, LOW);
  digitalWrite(RE_PIN, LOW);
  rs485.begin(BAUD_RATE, SERIAL_8N1, RX_PIN, TX_PIN);

  // Initialize BLE
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
  
  // 1. Setup the Scan Response
  NimBLEAdvertisementData scanRespData;
  scanRespData.setName("Kimchi_Monitor");
  pAdvertising->setScanResponseData(scanRespData);
  
  // 2. Enable scan response (UPDATED METHOD)
  pAdvertising->enableScanResponse(true); 
  
  // 3. Setup the main Advertisement packet
  NimBLEAdvertisementData advData;
  advData.addServiceUUID(NimBLEUUID("ABCD"));
  pAdvertising->setAdvertisementData(advData);

  // 4. Start broadcasting
  pAdvertising->start();

  Serial.println("BLE advertising as Kimchi_Monitor");
}

// ---------- MAIN LOOP ----------
void loop() {
  sensorOK = readSensor();
  lcd.clear();

  if (sensorOK) {
    float temperature = reg1 / 10.0;
    float humidity = reg2 / 10.0;
    isAlert = (temperature > BAD_TEMP);

    Serial.printf("Temp: %.1f C | Hum: %.1f %%\n", temperature, humidity);

    // --- UPDATE LCD ---
    lcd.setCursor(0, 0);
    lcd.print("Temp: ");
    lcd.print(temperature, 1);
    lcd.print((char)223); // Degree symbol
    lcd.print("C");

    lcd.setCursor(0, 1);
    lcd.print("Hum:  ");
    lcd.print(humidity, 1);
    lcd.print("%");

    // --- SEND VIA BLE ---
    String data = "T:" + String(temperature, 1) + "C H:" + String(humidity, 1) + "%";
    data += isAlert ? " !" : " OK";

    if (bleConnected && pCharacteristic) {
      pCharacteristic->setValue(data.c_str());
      pCharacteristic->notify();
      Serial.println("Sent via BLE: " + data);
    }
  } else {
    Serial.println("Sensor read failed");
    lcd.setCursor(0, 0);
    lcd.print("Sensor Error");
    lcd.setCursor(0, 1);
    lcd.print("Check RS485");
  }

  delay(20000);
}