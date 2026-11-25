# BayLum1---Classroom-Attendance-Management-System
A web-based dashboard, monitoring, and management system for an IoT-powered fingerprint biometric classroom attendance monitoring system for Bayan Luma 1 Elementary School.

## AS608 fingerprint attendance (USB-connected ESP32)

The ESP32 connects to the laptop by USB and uses Web Serial. It does not need Wi-Fi or internet access for fingerprint scanning. The laptop browser sends the matched fingerprint ID or enrollment result to the TiDB-backed website on Vercel, so the laptop must remain connected to the internet.

1. Install the **Adafruit Fingerprint Sensor Library** in Arduino IDE. Open `hardware/as608_attendance/as608_attendance.ino`, select the ESP32 board, and upload the sketch.
2. Wire AS608 `TX` to ESP32 `GPIO16`, `RX` to ESP32 `GPIO17`, and `GND` to `GND`. Power the sensor according to its board specifications.
3. Sign in as a teacher and open Attendance Settings in current Chrome or Edge at localhost or HTTPS. Click **Connect ESP32 Reader** and choose the ESP32 serial port. Close Arduino IDE Serial Monitor first; only one application can use the USB serial port.
4. To enroll a student, choose an active student, and click **Enroll Selected Student**. Follow the prompts and scan the same finger twice.
5. Open a time-in or time-out session with the existing attendance code flow. While the reader remains connected, scan a registered student's finger. The AS608 matched ID is sent by the browser to TiDB/Vercel and saved in the attendance records.

If a scan is rejected, the dashboard reports the exact AS608 slot it matched and whether that slot is unregistered, mapped to another section, or inactive. Re-enrolling to a new slot changes the website mapping but leaves the old sensor template in place; after confirming the new enrollment, remove the old template with `DELETE:<old-id>` in Arduino IDE Serial Monitor if it is no longer needed. Fingerprint templates are stored in the sensor, not the Uno; the Uno sketch does not erase them on reset. Never reuse a slot without resolving its existing template and student mapping. The web serial connection requires localhost or HTTPS and is not supported by every browser.

## Deploying to Vercel

The root `Dockerfile` runs the PHP app with FrankenPHP, and the `Caddyfile` sends app requests through `index.php` while serving `/public/` and `/images/` assets. Keep Vercel's **Root Directory** set to the repository root. If the Vercel project already has another framework preset, select the **Container** preset.

Before deploying, create an externally hosted MySQL database and import the application's schema and required migrations. This repository does not include a full schema dump, so export the initial schema from the existing database. Then add these environment variables in the Vercel project settings:

- `DB_HOST`: database hostname
- `DB_PORT`: database port (TiDB Serverless commonly uses `4000`; use the value shown by TiDB)
- `DB_NAME`: database name
- `DB_USER`: database username
- `DB_PASS` (or `DB_PASSWORD`): database password
- `DB_SSL_CA`: CA certificate path for TLS connections (for example, `/etc/ssl/cert.pem` on Vercel)

Do not use the local XAMPP database as the production database. The application currently uses PHP's default file-based sessions; for reliable logins across scaled Vercel instances, configure a shared session store before production use.

## TiDB teacher schema migration

After importing the database, run `database/migrations/20261124_fix_teacher_id_autoincrement.sql` in TiDB. The `teachers.id` column must use `AUTO_INCREMENT` because account signup inserts a teacher without supplying the ID.

Run `database/migrations/20261125_add_manual_attendance_schema.sql` as well. It creates or repairs the manual-attendance code, session, and record tables. Imported schemas may already contain these tables, so the migration ensures their `id` columns use `AUTO_INCREMENT`.
