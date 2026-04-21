USE `battleground_calc`;

ALTER TABLE `tournaments`
ADD COLUMN IF NOT EXISTS `status` VARCHAR(20) NULL DEFAULT 'belum_mulai' AFTER `name`;

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

SELECT `id`, `name`, `status`, `created_at`, `updated_at`
FROM `tournaments`
ORDER BY `id` ASC;
