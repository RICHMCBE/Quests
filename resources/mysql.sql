-- #! mysql
-- # { init
CREATE TABLE IF NOT EXISTS quest_progress
(
    id            INT AUTO_INCREMENT PRIMARY KEY,
    player_name   VARCHAR(50)  NOT NULL,
    quest_id      VARCHAR(100) NOT NULL,
    mission_index INT          NOT NULL,
    progress      INT DEFAULT 0,
    UNIQUE KEY unique_progress (player_name, quest_id, mission_index),
    INDEX idx_player (player_name),
    INDEX idx_quest (quest_id)
) CHARSET = utf8mb4;
-- #&
CREATE TABLE IF NOT EXISTS quest_cleared
(
    id           INT AUTO_INCREMENT PRIMARY KEY,
    player_name  VARCHAR(50)  NOT NULL,
    quest_id     VARCHAR(100) NOT NULL,
    cleared_date DATE         NOT NULL,
    cleared_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cleared (player_name, quest_id, cleared_date),
    INDEX idx_player (player_name),
    INDEX idx_quest (quest_id),
    INDEX idx_date (cleared_date)
) CHARSET = utf8mb4;
-- #&
CREATE TABLE IF NOT EXISTS quest_rewards
(
    id        INT AUTO_INCREMENT PRIMARY KEY,
    quest_id  VARCHAR(100) NOT NULL UNIQUE,
    item_data TEXT         NOT NULL,
    INDEX idx_quest (quest_id)
) CHARSET = utf8mb4;
-- # }

-- # { progress
-- #   { save
-- #     :player_name string
-- #     :quest_id string
-- #     :mission_index int
-- #     :progress int
INSERT INTO quest_progress (player_name, quest_id, mission_index, progress)
VALUES (:player_name, :quest_id, :mission_index, :progress)
ON DUPLICATE KEY UPDATE progress = VALUES(progress);
-- #   }
-- #   { load
-- #     :player_name string
-- #     :quest_id string
SELECT mission_index, progress
FROM quest_progress
WHERE player_name = :player_name AND quest_id = :quest_id;
-- #   }
-- #   { delete
-- #     :player_name string
-- #     :quest_id string
DELETE FROM quest_progress
WHERE player_name = :player_name AND quest_id = :quest_id;
-- #   }
-- #   { reset.daily
DELETE FROM quest_progress WHERE quest_id LIKE 'daily_%';
-- #   }
-- # }

-- # { rewards
-- #   { save
-- #     :quest_id string
-- #     :item_data string
INSERT INTO quest_rewards (quest_id, item_data)
VALUES (:quest_id, :item_data)
ON DUPLICATE KEY UPDATE item_data = VALUES(item_data);
-- #   }
-- #   { load
-- #     :quest_id string
SELECT item_data
FROM quest_rewards
WHERE quest_id = :quest_id;
-- #   }
-- #   { load.all
SELECT quest_id, item_data
FROM quest_rewards;
-- #   }
-- #   { delete
-- #     :quest_id string
DELETE FROM quest_rewards WHERE quest_id = :quest_id;
-- #   }
-- # }

-- # { cleared
-- #   { save
-- #     :player_name string
-- #     :quest_id string
-- #     :cleared_date string
INSERT IGNORE INTO quest_cleared (player_name, quest_id, cleared_date)
VALUES (:player_name, :quest_id, :cleared_date);
-- #   }
-- #   { check.today
-- #     :player_name string
-- #     :quest_id string
-- #     :cleared_date string
SELECT COUNT(*) as cnt
FROM quest_cleared
WHERE player_name = :player_name AND quest_id = :quest_id AND cleared_date = :cleared_date;
-- #   }
-- #   { check.ever
-- #     :player_name string
-- #     :quest_id string
SELECT COUNT(*) as cnt
FROM quest_cleared
WHERE player_name = :player_name AND quest_id = :quest_id;
-- #   }
-- #   { load.all
-- #     :player_name string
SELECT quest_id, cleared_date
FROM quest_cleared
WHERE player_name = :player_name;
-- #   }
-- # }
