-- UPDATE 002: select your existing grocery database in phpMyAdmin before importing.
-- Adds email OTP registration without deleting accounts or transactions. Safe to rerun.
SET @grocery_column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='email');
SET @grocery_ddl = IF(@grocery_column_exists=0,'ALTER TABLE users ADD COLUMN email VARCHAR(254) NULL UNIQUE','SELECT 1');
PREPARE grocery_update FROM @grocery_ddl;
EXECUTE grocery_update;
DEALLOCATE PREPARE grocery_update;
SET @grocery_column_exists = (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='users' AND column_name='email_verified_at');
SET @grocery_ddl = IF(@grocery_column_exists=0,'ALTER TABLE users ADD COLUMN email_verified_at DATETIME NULL','SELECT 1');
PREPARE grocery_update FROM @grocery_ddl;
EXECUTE grocery_update;
DEALLOCATE PREPARE grocery_update;
CREATE TABLE IF NOT EXISTS account_invitations (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(254) NOT NULL UNIQUE,
 role_id INT UNSIGNED NOT NULL,
 invited_by INT UNSIGNED NOT NULL,
 otp_hash VARCHAR(255) NULL,
 expires_at DATETIME NULL,
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 last_sent_at DATETIME NULL,
 send_window_at DATETIME NULL,
 sends_in_window INT UNSIGNED NOT NULL DEFAULT 0,
 used_at DATETIME NULL,
 revoked_at DATETIME NULL,
 user_id INT UNSIGNED NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(role_id) REFERENCES roles(id),
 FOREIGN KEY(invited_by) REFERENCES users(id),
 FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;
