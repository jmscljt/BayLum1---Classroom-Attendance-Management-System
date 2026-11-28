ALTER TABLE manual_attendance_records
    ADD INDEX idx_manual_record_recent
        (teacher_id, section_id, recorded_at, id);
