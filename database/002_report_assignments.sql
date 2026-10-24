ALTER TABLE reports
    ADD COLUMN assigned_to INT UNSIGNED NULL AFTER status,
    ADD COLUMN due_date DATE NULL AFTER assigned_to,
    ADD KEY idx_reports_assigned_to (assigned_to),
    ADD CONSTRAINT fk_reports_assigned_to
        FOREIGN KEY (assigned_to) REFERENCES users (id)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
