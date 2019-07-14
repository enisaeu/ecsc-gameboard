# mysql -t -u root -p < schema.sql

DROP DATABASE IF EXISTS ecsc;

CREATE DATABASE ecsc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; -- Reference: https://dba.stackexchange.com/a/76789
GRANT ALL ON ecsc.* TO ecsc@localhost IDENTIFIED BY '<blank>';

USE ecsc;

###

CREATE TABLE teams (
    team_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    login_name VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(1024) NOT NULL,
    country_code CHAR(2),
    email VARCHAR(1024) NOT NULL,
    password_hash VARCHAR(1024) NOT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE chat (
    message_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED DEFAULT NULL,
    content TEXT NOT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

CREATE TABLE privates (
    private_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    from_id INT UNSIGNED,
    to_id INT UNSIGNED,
    cash INT DEFAULT NULL,
    message TEXT DEFAULT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT _ UNIQUE (from_id, ts),
    FOREIGN KEY (from_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    FOREIGN KEY (to_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

CREATE TABLE contracts (
    contract_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(1024) NOT NULL,
    description TEXT NOT NULL,
    categories VARCHAR(1024) NOT NULL,
    hidden BOOLEAN NOT NULL DEFAULT FALSE,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE constraints (
    contract_id INT UNSIGNED NOT NULL UNIQUE,
    min_cash INT,
    min_awareness INT,
    FOREIGN KEY (contract_id) REFERENCES contracts(contract_id) ON DELETE CASCADE
);

CREATE TABLE tasks (
    task_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    contract_id INT UNSIGNED NOT NULL,
    title VARCHAR(1024) NOT NULL,
    description TEXT NOT NULL,
    answer VARCHAR(1024) NOT NULL,
    cash INT NOT NULL,
    awareness INT NOT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (contract_id) REFERENCES contracts(contract_id) ON DELETE CASCADE
);

CREATE TABLE options (
    task_id INT UNSIGNED NOT NULL UNIQUE,
    note VARCHAR(1024) DEFAULT NULL,
    is_regex BOOLEAN NOT NULL DEFAULT FALSE,
    ignore_case BOOLEAN NOT NULL DEFAULT FALSE,
    ignore_order BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    team_id INT UNSIGNED DEFAULT NULL,
    content TEXT NOT NULL,
    category VARCHAR(20),       -- e.g. success, info, etc. (Reference: https://www.w3schools.com/bootstrap4/bootstrap_alerts.asp)
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

CREATE TABLE hide (
    notification_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED DEFAULT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications(notification_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

CREATE TABLE solved (
    task_id INT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT _ UNIQUE (task_id, team_id),
    FOREIGN KEY (task_id) REFERENCES tasks(task_id) ON DELETE CASCADE,
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE
);

CREATE TABLE accepted (
    team_id INT UNSIGNED NOT NULL,
    contract_id INT UNSIGNED NOT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT _ UNIQUE (team_id, contract_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id) ON DELETE CASCADE,
    FOREIGN KEY (contract_id) REFERENCES contracts(contract_id) ON DELETE CASCADE
);

CREATE TABLE settings (
    name VARCHAR(100) NOT NULL PRIMARY KEY,
    value VARCHAR(1024) NOT NULL,
    ts TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE FUNCTION last_update()
    RETURNS INT
    RETURN (SELECT UNIX_TIMESTAMP(MAX(UPDATE_TIME)) as last_update FROM information_schema.tables WHERE TABLE_SCHEMA=DATABASE() GROUP BY TABLE_SCHEMA);

###

INSERT INTO teams(login_name, full_name, country_code, email, password_hash) VALUES("admin", "Administrator", "EU", "info@enisa.europa.eu", "$2y$10$jWKgJS7Wv2x.XnauUpoUle2G.0Ux1cRzHdTtEM.iIlmF4qI60ZstG");  -- - <?php echo password_hash("changeme!", PASSWORD_BCRYPT); ?>
