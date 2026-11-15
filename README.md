# BayLum1---Classroom-Attendance-Management-System
A web-based dashboard, monitoring, and management system for an IoT-powered fingerprint biometric classroom attendance monitoring system for Bayan Luma 1 Elementary School.

## AS608 fingerprint attendance (Arduino Uno)

The Uno connects to the computer hosting the website by USB. Apply `database/migrations/20261115_add_fingerprint_attendance.sql` to `bl1es_db` if the `students.fingerprint_id` and `manual_attendance_records.attendance_method` columns are not already present. Do not apply it a second time after those columns exist.

1. Install the **Adafruit Fingerprint Sensor Library** in Arduino IDE. Open `hardware/as608_attendance/as608_attendance.ino`, select Arduino Uno, and upload the sketch.
2. Wire AS608 `TX` to Uno `D2`, `RX` to Uno `D3` through a suitable level shifter/voltage divider, and `GND` to `GND`. Power the sensor according to its board specifications.
3. Sign in as a teacher and open the dashboard in current Chrome or Edge at localhost or HTTPS. Click **Connect AS608 Reader** and choose the Uno's serial port. Close Arduino IDE Serial Monitor first; only one application can use the Uno's COM port.
4. To enroll a student, choose an active student on the dashboard, enter an unused sensor ID from 1-127, and click **Enroll Selected Student**. Follow the prompts and scan the same finger twice. After the AS608 confirms the template was saved, the site records that ID on the student's profile automatically. You can choose a different unused slot to re-enroll a student with an existing mapping. The enrollment flow refuses a sensor slot that already contains a template.
5. Open a time-in or time-out session with the existing attendance code flow. While the reader remains connected, scan a registered student's finger. The AS608 matched ID is sent to the server, mapped to the student's LRN, and saved in the existing attendance records. The logs show whether a record was made manually or by fingerprint.

If a scan is rejected, the dashboard reports the exact AS608 slot it matched and whether that slot is unregistered, mapped to another section, or inactive. Re-enrolling to a new slot changes the website mapping but leaves the old sensor template in place; after confirming the new enrollment, remove the old template with `DELETE:<old-id>` in Arduino IDE Serial Monitor if it is no longer needed. Fingerprint templates are stored in the sensor, not the Uno; the Uno sketch does not erase them on reset. Never reuse a slot without resolving its existing template and student mapping. The web serial connection requires localhost or HTTPS and is not supported by every browser.
