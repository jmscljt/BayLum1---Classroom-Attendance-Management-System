#include <Adafruit_Fingerprint.h>
#include <SoftwareSerial.h>

SoftwareSerial fingerprintSerial(2, 3);
Adafruit_Fingerprint fingerprint(&fingerprintSerial);
bool sensorReady = false;

bool captureFingerprint(uint8_t bufferNumber);
void waitForFingerRemoval();
void processCommand(String command);
void enrollFingerprint(uint8_t id);

void setup() {
  Serial.begin(57600);
  fingerprint.begin(57600);

  if (fingerprint.verifyPassword()) {
    sensorReady = true;
    Serial.println("READY");
  } else {
    Serial.println("ERROR:AS608_NOT_FOUND");
  }
}

void loop() {
  if (Serial.available()) {
    String command = Serial.readStringUntil('\n');
    command.trim();
    processCommand(command);
    return;
  }
  if (!sensorReady) {
    delay(100);
    return;
  }

  uint8_t result = fingerprint.getImage();
  if (result == FINGERPRINT_NOFINGER) {
    delay(50);
    return;
  }
  if (result != FINGERPRINT_OK) {
    Serial.println("ERROR:IMAGE_CAPTURE");
    waitForFingerRemoval();
    return;
  }
  if (fingerprint.image2Tz() != FINGERPRINT_OK) {
    Serial.println("NO_MATCH");
    waitForFingerRemoval();
    return;
  }
  if (fingerprint.fingerFastSearch() == FINGERPRINT_OK) {
    Serial.print("MATCH:");
    Serial.println(fingerprint.fingerID);
  } else {
    Serial.println("NO_MATCH");
  }
  waitForFingerRemoval();
}

bool captureFingerprint(uint8_t bufferNumber) {
  Serial.println(bufferNumber == 1 ? "ENROLL:PLACE_FINGER" : "ENROLL:PLACE_AGAIN");

  uint8_t result;
  do {
    result = fingerprint.getImage();
    if (result == FINGERPRINT_NOFINGER) {
      delay(50);
    }
  } while (result == FINGERPRINT_NOFINGER);

  if (result != FINGERPRINT_OK) {
    Serial.println("ERROR:IMAGE_CAPTURE");
    return false;
  }
  if (fingerprint.image2Tz(bufferNumber) != FINGERPRINT_OK) {
    Serial.println("ERROR:IMAGE_CONVERSION");
    return false;
  }

  return true;
}

void waitForFingerRemoval() {
  while (true) {
    uint8_t result = fingerprint.getImage();
    if (result != FINGERPRINT_OK) {
      return;
    }
    delay(100);
  }
}

void enrollFingerprint(uint8_t id) {
  if (fingerprint.loadModel(id) == FINGERPRINT_OK) {
    Serial.println("ERROR:ID_IN_USE");
    return;
  }

  if (!captureFingerprint(1)) {
    return;
  }
  Serial.println("ENROLL:REMOVE_FINGER");
  waitForFingerRemoval();
  if (!captureFingerprint(2)) {
    return;
  }
  if (fingerprint.createModel() != FINGERPRINT_OK) {
    Serial.println("ERROR:FINGER_MISMATCH");
    waitForFingerRemoval();
    return;
  }
  if (fingerprint.storeModel(id) != FINGERPRINT_OK) {
    Serial.println("ERROR:STORE_FAILED");
    waitForFingerRemoval();
    return;
  }

  Serial.print("ENROLLED:");
  Serial.println(id);
  waitForFingerRemoval();
}

void processCommand(String command) {
  if (!sensorReady) {
    Serial.println("ERROR:AS608_NOT_FOUND");
    return;
  }

  if (command.startsWith("ENROLL:")) {
    String idText = command.substring(7);
    char *end = nullptr;
    long id = strtol(idText.c_str(), &end, 10);
    if (idText.length() == 0 || *end != '\0' || id < 1 || id > 127) {
      Serial.println("ERROR:ID_MUST_BE_1_TO_127");
      return;
    }
    enrollFingerprint((uint8_t)id);
    return;
  }

  if (command.startsWith("DELETE:")) {
    String idText = command.substring(7);
    char *end = nullptr;
    long id = strtol(idText.c_str(), &end, 10);
    if (idText.length() == 0 || *end != '\0' || id < 1 || id > 127) {
      Serial.println("ERROR:ID_MUST_BE_1_TO_127");
      return;
    }
    if (fingerprint.deleteModel((uint16_t)id) == FINGERPRINT_OK) {
      Serial.print("DELETED:");
      Serial.println(id);
    } else {
      Serial.println("ERROR:DELETE_FAILED");
    }
    return;
  }

  if (command.length() > 0) {
    Serial.println("ERROR:UNKNOWN_COMMAND");
  }
}
