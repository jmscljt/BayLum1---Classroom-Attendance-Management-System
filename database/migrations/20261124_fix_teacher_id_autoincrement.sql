CREATE TABLE IF NOT EXISTS teacher_id_sequence (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

INSERT INTO teacher_id_sequence (id)
SELECT COALESCE(MAX(id), 0) FROM teachers
WHERE NOT EXISTS (
    SELECT 1 FROM teacher_id_sequence
);
