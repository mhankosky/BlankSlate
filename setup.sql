CREATE DATABASE IF NOT EXISTS blank_slate;
USE blank_slate;

CREATE TABLE IF NOT EXISTS players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    total_score INT DEFAULT 0,
    hidden TINYINT(1) DEFAULT 0
);

CREATE TABLE IF NOT EXISTS game_state (
    id INT PRIMARY KEY,
    status VARCHAR(20) DEFAULT 'waiting',
    round_number INT DEFAULT 1,
    word_left VARCHAR(100),
    word_right VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS game_settings (
    id INT PRIMARY KEY,
    allow_spaces TINYINT(1) DEFAULT 1
);

CREATE TABLE IF NOT EXISTS answers (
    player_id INT,
    round_number INT,
    answer_text VARCHAR(100),
    points_earned INT DEFAULT 0,
    PRIMARY KEY (player_id, round_number)
);

CREATE TABLE IF NOT EXISTS round_history (
    round_number INT PRIMARY KEY,
    word_left VARCHAR(100),
    word_right VARCHAR(100)
);

INSERT IGNORE INTO game_state (id, status, round_number) VALUES (1, 'waiting', 1);
INSERT IGNORE INTO game_settings (id, allow_spaces) VALUES (1, 1);