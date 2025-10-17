DROP TABLE IF EXISTS appointment_email_confirmations;
CREATE TABLE appointment_email_confirmations (
  id INT(11) NOT NULL AUTO_INCREMENT,
  appointment_id INT(11) NOT NULL,
  email VARCHAR(255) NOT NULL,
  token CHAR(64) NOT NULL,
  status ENUM('pending','confirmed','expired','cancelled') NOT NULL DEFAULT 'pending',
  ip_created VARBINARY(16) NULL,
  user_agent VARCHAR(255) NULL,
  resend_count INT(11) NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  confirmed_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_aec_token UNIQUE (token),
  PRIMARY KEY (id),
  INDEX idx_aec_appt (appointment_id),
  INDEX idx_aec_status_expires (status, expires_at),
  CONSTRAINT fk_aec_appointment
    FOREIGN KEY (appointment_id)
    REFERENCES ea_appointments(id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
