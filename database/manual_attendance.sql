CREATE TABLE teacher_attendance_codes (
    teacher_id INT NOT NULL,
    attendance_type ENUM('time_in', 'time_out') NOT NULL,
    code CHAR(4) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    PRIMARY KEY (teacher_id, attendance_type),
    UNIQUE KEY uq_teacher_attendance_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE manual_attendance_sessions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    section_id INT NOT NULL,
    attendance_type ENUM('time_in', 'time_out') NOT NULL,
    attendance_date DATE NOT NULL,
    opened_at DATETIME NOT NULL,
    closed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_manual_session_teacher_open (teacher_id, closed_at),
    KEY idx_manual_session_date (teacher_id, section_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE manual_attendance_records (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    session_id BIGINT UNSIGNED NOT NULL,
    teacher_id INT NOT NULL,
    section_id INT NOT NULL,
    student_id VARCHAR(32) NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_type ENUM('time_in', 'time_out') NOT NULL,
    recorded_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_manual_attendance_daily (teacher_id, attendance_date, attendance_type, student_id),
    KEY idx_manual_attendance_teacher_date (teacher_id, section_id, attendance_date),
    CONSTRAINT fk_manual_attendance_session
        FOREIGN KEY (session_id) REFERENCES manual_attendance_sessions (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
