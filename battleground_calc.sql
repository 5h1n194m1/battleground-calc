-- =========================================================
-- DATABASE: battleground_calc
-- STACK   : Laragon + MariaDB/MySQL + CodeIgniter 4
-- APP     : Battleground Calc
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================
-- 1. CREATE DATABASE
-- =========================================================
CREATE DATABASE IF NOT EXISTS `battleground_calc`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `battleground_calc`;

-- =========================================================
-- 2. DROP OLD APP TABLES IF EXIST
-- =========================================================
DROP VIEW IF EXISTS `v_leaderboard_pot`;
DROP TABLE IF EXISTS `players`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `scores`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `pots`;
DROP TABLE IF EXISTS `tournaments`;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- 3. TOURNAMENTS
-- =========================================================
CREATE TABLE `tournaments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. POTS
-- =========================================================
CREATE TABLE `pots` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tournament_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pots_tournament_id` (`tournament_id`),
    CONSTRAINT `fk_pots_tournament_id`
        FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. TEAMS
-- =========================================================
CREATE TABLE `teams` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pot_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 1,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_teams_pot_id` (`pot_id`),
    CONSTRAINT `fk_teams_pot_id`
        FOREIGN KEY (`pot_id`) REFERENCES `pots` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 6. SCORES
--    1 baris = 1 team, 1 pot, 1 game
-- =========================================================
CREATE TABLE `scores` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pot_id` INT UNSIGNED NOT NULL,
    `team_id` INT UNSIGNED NOT NULL,
    `game_no` INT NOT NULL,
    `rank_no` INT NOT NULL DEFAULT 0,
    `kill_point` INT NOT NULL DEFAULT 0,
    `placement_point` INT NOT NULL DEFAULT 0,
    `total_point` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),

    UNIQUE KEY `uq_scores_pot_team_game` (`pot_id`, `team_id`, `game_no`),
    KEY `idx_scores_team_id` (`team_id`),
    KEY `idx_scores_game_no` (`game_no`),

    CONSTRAINT `fk_scores_pot_id`
        FOREIGN KEY (`pot_id`) REFERENCES `pots` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT `fk_scores_team_id`
        FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT `chk_scores_rank_no`
        CHECK (`rank_no` >= 0),

    CONSTRAINT `chk_scores_kill_point`
        CHECK (`kill_point` >= 0),

    CONSTRAINT `chk_scores_game_no`
        CHECK (`game_no` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 7. REGISTRATIONS
--    Menyimpan hasil form pendaftaran tim
-- =========================================================
CREATE TABLE `registrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `team_name` VARCHAR(150) NOT NULL,
    `leader_name` VARCHAR(150) NOT NULL,
    `whatsapp` VARCHAR(30) NULL,
    `email` VARCHAR(150) NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_registrations_team_name` (`team_name`),
    KEY `idx_registrations_leader_name` (`leader_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 8. PLAYERS
--    Anggota tiap pendaftaran
-- =========================================================
CREATE TABLE `players` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `registration_id` INT UNSIGNED NOT NULL,
    `player_name` VARCHAR(150) NOT NULL,
    `player_role` VARCHAR(100) NULL,
    `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_players_registration_id` (`registration_id`),
    CONSTRAINT `fk_players_registration_id`
        FOREIGN KEY (`registration_id`) REFERENCES `registrations` (`id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 9. VIEW LEADERBOARD PER POT
-- =========================================================
CREATE VIEW `v_leaderboard_pot` AS
SELECT
    p.id AS pot_id,
    p.name AS pot_name,
    t.id AS team_id,
    t.name AS team_name,
    COALESCE(SUM(s.total_point), 0) AS total_score
FROM pots p
JOIN teams t
    ON t.pot_id = p.id
LEFT JOIN scores s
    ON s.team_id = t.id
   AND s.pot_id = p.id
GROUP BY
    p.id,
    p.name,
    t.id,
    t.name;

-- =========================================================
-- 10. TRIGGER BEFORE INSERT SCORES
--     Auto hitung placement_point dan total_point
-- =========================================================
DELIMITER $$

CREATE TRIGGER `bi_scores_calculate_points`
BEFORE INSERT ON `scores`
FOR EACH ROW
BEGIN
    SET NEW.placement_point =
        CASE NEW.rank_no
            WHEN 1 THEN 12
            WHEN 2 THEN 9
            WHEN 3 THEN 8
            WHEN 4 THEN 7
            WHEN 5 THEN 6
            WHEN 6 THEN 5
            WHEN 7 THEN 4
            WHEN 8 THEN 3
            WHEN 9 THEN 2
            WHEN 10 THEN 1
            WHEN 11 THEN 0
            WHEN 12 THEN 0
            ELSE 0
        END;

    SET NEW.total_point = NEW.placement_point + NEW.kill_point;
END$$

CREATE TRIGGER `bu_scores_calculate_points`
BEFORE UPDATE ON `scores`
FOR EACH ROW
BEGIN
    SET NEW.placement_point =
        CASE NEW.rank_no
            WHEN 1 THEN 12
            WHEN 2 THEN 9
            WHEN 3 THEN 8
            WHEN 4 THEN 7
            WHEN 5 THEN 6
            WHEN 6 THEN 5
            WHEN 7 THEN 4
            WHEN 8 THEN 3
            WHEN 9 THEN 2
            WHEN 10 THEN 1
            WHEN 11 THEN 0
            WHEN 12 THEN 0
            ELSE 0
        END;

    SET NEW.total_point = NEW.placement_point + NEW.kill_point;
END$$

DELIMITER ;

-- =========================================================
-- 11. OPTIONAL DEMO DATA
--     Hapus komentar kalau ingin langsung ada contoh data
-- =========================================================

INSERT INTO `tournaments` (`name`) VALUES
('Battleground Demo Tournament');

INSERT INTO `pots` (`tournament_id`, `name`, `sort_order`) VALUES
(1, 'POT 1', 1);

INSERT INTO `teams` (`pot_id`, `name`, `sort_order`) VALUES
(1, 'Team Alpha', 1),
(1, 'Team Bravo', 2),
(1, 'Team Charlie', 3);

INSERT INTO `scores` (`pot_id`, `team_id`, `game_no`, `rank_no`, `kill_point`) VALUES
(1, 1, 1, 1, 5),
(1, 2, 1, 2, 3),
(1, 3, 1, 4, 2),
(1, 1, 2, 3, 4),
(1, 2, 2, 1, 6),
(1, 3, 2, 6, 1);

INSERT INTO `registrations` (`team_name`, `leader_name`, `whatsapp`, `email`, `notes`) VALUES
('Team Alpha', 'Leader Alpha', '081234567890', 'alpha@example.com', 'Contoh registrasi'),
('Team Bravo', 'Leader Bravo', '081298765432', 'bravo@example.com', 'Contoh registrasi');

INSERT INTO `players` (`registration_id`, `player_name`, `player_role`) VALUES
(1, 'Alpha Player 1', 'Captain'),
(1, 'Alpha Player 2', 'Rusher'),
(1, 'Alpha Player 3', 'Support'),
(2, 'Bravo Player 1', 'Captain'),
(2, 'Bravo Player 2', 'Scout');

-- =========================================================
-- 12. EXAMPLE CHECK QUERIES
-- =========================================================

-- Cek leaderboard semua tim dalam satu pot
-- SELECT * FROM v_leaderboard_pot WHERE pot_id = 1 ORDER BY total_score DESC, team_name ASC;

-- Cek score detail
-- SELECT * FROM scores ORDER BY pot_id, team_id, game_no;

-- Cek teams per pot
-- SELECT * FROM teams WHERE pot_id = 1 ORDER BY sort_order ASC;
