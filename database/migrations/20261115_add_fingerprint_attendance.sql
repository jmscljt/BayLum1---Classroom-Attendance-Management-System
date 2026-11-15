ALTER TABLE students
    ADD COLUMN fingerprint_id SMALLINT UNSIGNED NULL,
    ADD UNIQUE KEY uq_students_fingerprint_id (fingerprint_id);

ALTER TABLE manual_attendance_records
    ADD COLUMN attendance_method ENUM('Manual', 'Fingerprint') NOT NULL DEFAULT 'Manual';
