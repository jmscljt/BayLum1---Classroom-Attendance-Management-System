#include <HardwareSerial.h>
#include <Adafruit_Fingerprint.h>

#define AS608_RX_PIN 16
#define AS608_TX_PIN 17

HardwareSerial fingerprintSerial(2);
Adafruit_Fingerprint fingerprint(&fingerprintSerial);

uint32_t lastMatchAt = 0;
uint16_t lastMatchedId = 0;
bool sensorReady = false;
String serialBuffer = "";

bool captureFingerprint(uint8_t bufferNumber) {
  uint8_t result;
  do {
    result = fingerprint.getImage();
    if (result == FINGERPRINT_NOFINGER) {
      delay(50);
    }
  } while (result == FINGERPRINT_NOFINGER);

  if (result != FINGERPRINT_OK) {
    return false;
  }
  return fingerprint.image2Tz(bufferNumber) == FINGERPRINT_OK;
}

void waitForFingerRemoval() {
  while (fingerprint.getImage() != FINGERPRINT_NOFINGER) {
    delay(100);
  }
  delay(300);
}

void processDelete(uint16_t fingerprintId) {
  if (fingerprint.deleteModel(fingerprintId) == FINGERPRINT_OK) {
    Serial.println("DELETED:" + String(fingerprintId));
  } else {
    Serial.println("ERROR:DELETE_FAILED");
  }
}

void processEnroll(uint16_t fingerprintId) {
  if (fingerprintId < 1 || fingerprintId > 127) {
    Serial.println("ERROR:INVALID_ID");
    return;
  }
  if (fingerprint.loadModel(fingerprintId) == FINGERPRINT_OK) {
    Serial.println("ERROR:ID_IN_USE");
    return;
  }

  Serial.println("ENROLL:PLACE_FINGER");
  if (!captureFingerprint(1)) {
    Serial.println("ERROR:IMAGE_CAPTURE");
    return;
  }

  Serial.println("ENROLL:REMOVE_FINGER");
  waitForFingerRemoval();
  Serial.println("ENROLL:PLACE_AGAIN");

  if (!captureFingerprint(2)) {
    Serial.println("ERROR:IMAGE_CAPTURE");
    return;
  }
  if (fingerprint.createModel() != FINGERPRINT_OK) {
    Serial.println("ERROR:FINGER_MISMATCH");
    return;
  }

  Serial.println("ENROLL:REMOVE_FINGER");
  waitForFingerRemoval();
  Serial.println("ENROLL:PLACE_THIRD");

  if (!captureFingerprint(2)) {
    Serial.println("ERROR:IMAGE_CAPTURE");
    return;
  }
  if (fingerprint.createModel() != FINGERPRINT_OK) {
    Serial.println("ERROR:FINGER_MISMATCH");
    return;
  }
  if (fingerprint.storeModel(fingerprintId) != FINGERPRINT_OK) {
    Serial.println("ERROR:STORE_FAILED");
    return;
  }

  waitForFingerRemoval();
  Serial.println("ENROLLED:" + String(fingerprintId));
}

void scanFingerprint() {
  uint8_t result = fingerprint.getImage();
  if (result == FINGERPRINT_NOFINGER) {
    delay(50);
    return;
  }
  if (result != FINGERPRINT_OK) {
    waitForFingerRemoval();
    return;
  }
  if (fingerprint.image2Tz() != FINGERPRINT_OK) {
    waitForFingerRemoval();
    return;
  }

  if (fingerprint.fingerFastSearch() == FINGERPRINT_OK) {
    uint16_t fingerprintId = fingerprint.fingerID;
    uint32_t now = millis();
    if (fingerprintId != lastMatchedId || now - lastMatchAt > 3000) {
      lastMatchedId = fingerprintId;
      lastMatchAt = now;
      Serial.println("MATCH:" + String(fingerprintId));
    }
  } else {
    Serial.println("NO_MATCH");
  }
  waitForFingerRemoval();
}

void processSerialCommand(String command) {
  command.trim();
  if (command.startsWith("ENROLL:")) {
    uint16_t fingerprintId = (uint16_t) command.substring(7).toInt();
    processEnroll(fingerprintId);
  } else if (command.startsWith("DELETE:")) {
    uint16_t fingerprintId = (uint16_t) command.substring(7).toInt();
    processDelete(fingerprintId);
  } else if (command == "PING") {
    Serial.println(sensorReady ? "READY" : "AS608_NOT_READY");
  }
}

void setup() {
  Serial.begin(115200);
  fingerprintSerial.begin(57600, SERIAL_8N1, AS608_RX_PIN, AS608_TX_PIN);
  fingerprint.begin(57600);
  delay(1000);

  sensorReady = fingerprint.verifyPassword();
  if (sensorReady) {
    Serial.println("READY");
  } else {
    Serial.println("ERROR:AS608_NOT_FOUND");
  }
}

void loop() {
  while (Serial.available() > 0) {
    char received = (char) Serial.read();
    if (received == '\n' || received == '\r') {
      if (serialBuffer.length() > 0) {
        processSerialCommand(serialBuffer);
        serialBuffer = "";
      }
    } else {
      serialBuffer += received;
    }
  }

  if (!sensorReady) {
    sensorReady = fingerprint.verifyPassword();
    if (sensorReady) {
      Serial.println("READY");
    } else {
      delay(500);
    }
    return;
  }

  scanFingerprint();
}
