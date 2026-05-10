-- Forum Moments migration
-- Run once on existing database: um_rental_system

USE um_rental_system;

ALTER TABLE forum_posts
    ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NULL AFTER content;

CREATE TABLE IF NOT EXISTS forum_post_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reply_content TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_forum_reply_post
      FOREIGN KEY (post_id) REFERENCES forum_posts(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE,
    CONSTRAINT fk_forum_reply_user
      FOREIGN KEY (user_id) REFERENCES users(id)
      ON DELETE CASCADE
      ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_forum_reply_post_time ON forum_post_replies (post_id, created_at);
