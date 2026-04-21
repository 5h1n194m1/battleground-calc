USE `battleground_calc`;

SET @status_column_exists := (
    SELECT COUNT(*)
    FROM `information_schema`.`COLUMNS`
    WHERE `TABLE_SCHEMA` = 'battleground_calc'
      AND `TABLE_NAME` = 'tournaments'
      AND `COLUMN_NAME` = 'status'
);

SET @sql_add_status := IF(
    @status_column_exists = 0,
    'ALTER TABLE `tournaments` ADD COLUMN `status` VARCHAR(20) NULL DEFAULT ''belum_mulai'' AFTER `name`;',
    'SELECT ''Column status already exists'';'
);
PREPARE stmt FROM @sql_add_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `tournaments`
SET `status` = 'belum_mulai'
WHERE `status` IS NULL OR `status` = '';

ALTER TABLE `tournaments`
MODIFY COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'belum_mulai';

UPDATE `tournaments`
SET `status` = CASE
    WHEN `status` NOT IN ('belum_mulai', 'start', 'selesai') THEN 'belum_mulai'
    ELSE `status`
END;

CREATE TABLE IF NOT EXISTS `team_members` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_id` INT UNSIGNED NOT NULL,
    `registration_id` INT UNSIGNED NULL,
    `player_name` VARCHAR(150) NOT NULL,
    `player_role` VARCHAR(100) NULL,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_team_members_team_id` (`team_id`),
    KEY `idx_team_members_registration_id` (`registration_id`),
    CONSTRAINT `fk_team_members_team_id`
        FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `fk_team_members_registration_id`
        FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `team_members` (`team_id`, `registration_id`, `player_name`, `player_role`, `created_at`, `updated_at`)
SELECT
    t.`id`,
    r.`id`,
    p.`player_name`,
    p.`player_role`,
    NOW(),
    NOW()
FROM `teams` t
JOIN `registrations` r
    ON LOWER(REPLACE(TRIM(r.`team_name`), ' ', '')) COLLATE utf8mb4_unicode_ci
     = LOWER(REPLACE(TRIM(t.`name`), ' ', '')) COLLATE utf8mb4_unicode_ci
JOIN `players` p
    ON p.`registration_id` = r.`id`
LEFT JOIN `team_members` tm
    ON tm.`team_id` = t.`id`
   AND tm.`registration_id` = r.`id`
   AND tm.`player_name` COLLATE utf8mb4_unicode_ci = p.`player_name` COLLATE utf8mb4_unicode_ci
WHERE tm.`id` IS NULL;

SELECT `id`, `name`, `status`, `created_at`, `updated_at`
FROM `tournaments`
ORDER BY `id` ASC;

SHOW TABLES LIKE 'team_members';
