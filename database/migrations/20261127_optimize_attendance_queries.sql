ALTER TABLE students
    ADD INDEX idx_students_section_active_name (section_id, is_active, last_name, first_name);

ALTER TABLE attendance_logs
    ADD INDEX idx_attendance_lookup (student_id, log_date, attendance_status);

ALTER TABLE manual_attendance_sessions
    ADD INDEX idx_manual_session_closed_section_date
        (section_id, attendance_type, closed_at, attendance_date);

ALTER TABLE manual_attendance_records
    ADD INDEX idx_manual_record_section_student_date_type
        (section_id, student_id, attendance_date, attendance_type);
