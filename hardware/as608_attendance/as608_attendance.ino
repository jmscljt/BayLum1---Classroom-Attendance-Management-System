#include <WiFi.h>
#include <HTTPClient.h>
#include <HardwareSerial.h>
#include <Adafruit_Fingerprint.h>

const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";
const char* SERVER_URL = "http://192.168.1.100/MyPHPWebsite/BayLum1ElemSchool/BayLum1---Classroom-Attendance-Management-System/index.php?page=fingerprintEsp32";
const char* DEVICE_SECRET = "CHANGE_THIS_TO_THE_SAME_FINGERPRINT_DEVICE_SECRET";

#define AS608_RX_PIN 16
#define AS608_TX_PIN 17

HardwareSerial fingerprintSerial(2);
Adafruit_Fingerprint fingerprint(&fingerprintSerial);

uint32_t lastPollAt = 0;
uint32_t lastMatchAt = 0;
uint16_t lastMatchedId = 0;
bool sensorReady = false;

String httpPost(const String& payload) {
  if (WiFi.status() != WL_CONNECTED) {
    return "";
  }
  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(8000);
  int code = http.POST(payload);
  String response = code > 0 ? http.getString() : "";
  http.end();
  return response;
}

bool payloadDeviceJson(const char* action, String& payload) {
  payload = String("{\"action\":\"") + action +
            "\",\"device_secret\":\"" + DEVICE_SECRET +
            "\",\"sensor_ready\":" + (sensorReady ? "true" : "false") + "}";
  return WiFi.status() == WL_CONNECTED;
}

int jsonInt(const String& json, const char* key) {
  String needle = String("\"") + key + "\":";
  int start = json.indexOf(needle);
  if (start < 0) {
    return -1;
  }
  start += needle.length();
  while (start < (int)json.length() && json[start] == ' ') {
    start++;
  }
  int endPos = start;
  while (endPos < (int)json.length() &&
         ((json[endPos] >= '0' && json[endPos] <= '9') || json[endPos] == '-')) {
    endPos++;
  }
  return json.substring(start, endPos).toInt();
}

String jsonString(const String& json, const char* key) {
  String needle = String("\"") + key + "\":\"";
  int start = json.indexOf(needle);
  if (start < 0) {
    return "";
  }
  start += needle.length();
  int endPos = json.indexOf('"', start);
  if (endPos < 0) {
    return "";
  }
  return json.substring(start, endPos);
}

bool connectWifi() {
  if (WiFi.status() == WL_CONNECTED) {
    return true;
  }
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  uint32_t started = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - started < 15000) {
    delay(250);
  }
  return WiFi.status() == WL_CONNECTED;
}

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

void reportFailed(int commandId, const char* error) {
  String payload = String("{\"action\":\"report_failed\",\"device_secret\":\"") +
                   DEVICE_SECRET +
                   "\",\"command_id\":" + commandId +
                   ",\"error\":\"" + error + "\"}";
  httpPost(payload);
}

void reportDeleted(int commandId, uint16_t fingerprintId) {
  String payload = String("{\"action\":\"report_deleted\",\"device_secret\":\"") +
                   DEVICE_SECRET +
                   "\",\"command_id\":" + commandId +
                   ",\"fingerprint_id\":" + fingerprintId + "}";
  httpPost(payload);
}

void reportEnrolled(int commandId, uint16_t fingerprintId) {
  String payload = String("{\"action\":\"report_enrolled\",\"device_secret\":\"") +
                   DEVICE_SECRET +
                   "\",\"command_id\":" + commandId +
                   ",\"fingerprint_id\":" + fingerprintId + "}";
  httpPost(payload);
}

void reportMatch(uint16_t fingerprintId) {
  String payload = String("{\"action\":\"report_match\",\"device_secret\":\"") +
                   DEVICE_SECRET +
                   "\",\"fingerprint_id\":" + fingerprintId + "}";
  httpPost(payload);
}

void processDelete(int commandId, uint16_t fingerprintId) {
  if (fingerprint.deleteModel(fingerprintId) == FINGERPRINT_OK) {
    reportDeleted(commandId, fingerprintId);
  } else {
    reportFailed(commandId, "AS608 delete failed.");
  }
}

void processEnroll(int commandId, uint16_t fingerprintId) {
  if (fingerprint.loadModel(fingerprintId) == FINGERPRINT_OK) {
    reportFailed(commandId, "AS608 fingerprint slot is already in use.");
    return;
  }

  if (!captureFingerprint(1)) {
    reportFailed(commandId, "Could not capture the first fingerprint scan.");
    return;
  }
  waitForFingerRemoval();

  if (!captureFingerprint(2)) {
    reportFailed(commandId, "Could not capture the second fingerprint scan.");
    return;
  }
  if (fingerprint.createModel() != FINGERPRINT_OK) {
    reportFailed(commandId, "The two fingerprint scans did not match.");
    return;
  }
  if (fingerprint.storeModel(fingerprintId) != FINGERPRINT_OK) {
    reportFailed(commandId, "AS608 could not store the fingerprint.");
    return;
  }

  waitForFingerRemoval();
  reportEnrolled(commandId, fingerprintId);
}

void pollServer() {
  String payload;
  payloadDeviceJson("poll", payload);

  String response = httpPost(payload);
  if (response.length() == 0) {
    return;
  }

  int commandId = jsonInt(response, "command_id");
  int fingerprintId = jsonInt(response, "fingerprint_id");
  String command = jsonString(response, "command");
  if (commandId < 1 || fingerprintId < 1 || fingerprintId > 127) {
    return;
  }

  if (command == "ENROLL") {
    processEnroll(commandId, (uint16_t)fingerprintId);
  } else if (command == "DELETE") {
    processDelete(commandId, (uint16_t)fingerprintId);
  }
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
      reportMatch(fingerprintId);
    }
  }
  waitForFingerRemoval();
}

void setup() {
  Serial.begin(115200);
  fingerprintSerial.begin(57600, SERIAL_8N1, AS608_RX_PIN, AS608_TX_PIN);
  fingerprint.begin(57600);

  connectWifi();
  sensorReady = fingerprint.verifyPassword();
  if (!sensorReady) {
    Serial.println("AS608 not found.");
  }
}

void loop() {
  if (!connectWifi()) {
    delay(1000);
    return;
  }
  if (!sensorReady) {
    sensorReady = fingerprint.verifyPassword();
    delay(500);
    return;
  }

  if (millis() - lastPollAt >= 750) {
    lastPollAt = millis();
    pollServer();
  }
  scanFingerprint();
}
