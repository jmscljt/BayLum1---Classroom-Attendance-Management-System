CREATE TABLE IF NOT EXISTS teacher_attendance_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    attendance_type VARCHAR(16) NOT NULL,
    code CHAR(4) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_attendance_type (teacher_id, attendance_type),
    UNIQUE KEY uq_teacher_attendance_code (code)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS manual_attendance_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    attendance_type VARCHAR(16) NOT NULL,
    attendance_date DATE NOT NULL,
    opened_at DATETIME NOT NULL,
    closed_at DATETIME NULL,
    INDEX idx_manual_session_teacher_active (teacher_id, closed_at),
    INDEX idx_manual_session_section_date (section_id, attendance_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS manual_attendance_records (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    student_id VARCHAR(19) NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_type VARCHAR(16) NOT NULL,
    recorded_at DATETIME NOT NULL,
    attendance_method VARCHAR(16) NOT NULL DEFAULT 'Manual',
    UNIQUE KEY uq_manual_attendance_record
        (session_id, student_id, attendance_type),
    INDEX idx_manual_record_lookup
        (teacher_id, section_id, attendance_date, attendance_type)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS manual_attendance_session_sequence (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS manual_attendance_record_sequence (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB;
