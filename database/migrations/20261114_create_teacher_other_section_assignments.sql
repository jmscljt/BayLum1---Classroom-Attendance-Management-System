CREATE TABLE teacher_other_section_assignments (
    assignment_id INT NOT NULL AUTO_INCREMENT,
    teacher_id INT NOT NULL,
    section_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (assignment_id),
    UNIQUE KEY uq_teacher_section_subject (teacher_id, section_id, subject),
    KEY idx_teacher_other_section (teacher_id),
    CONSTRAINT fk_other_section_teacher
        FOREIGN KEY (teacher_id) REFERENCES teachers (id) ON DELETE CASCADE,
    CONSTRAINT fk_other_section_section
        FOREIGN KEY (section_id) REFERENCES sections (section_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
