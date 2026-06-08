-- ============================================================
-- olap_full_setup.sql
-- ONE FILE - SAFE TO PASTE IN ONE GO into phpMyAdmin (XAMPP/MariaDB)
--
-- INSTRUCTIONS:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Click the SQL tab (you can be on any database)
-- 3. Copy EVERYTHING from below this line to the end of the file
-- 4. Paste it all at once into the SQL box
-- 5. Click "Go"
--
-- This file is structured with explicit ";" separators and subqueries
-- for ROLLUP so phpMyAdmin's parser won't complain about "WITH" being
-- a CTE or throw #1221 ORDER BY error.
--
-- IMPORTANT: Compatible with MariaDB 10.4 (your current XAMPP version).
-- GROUPING() function removed (requires 10.5+). ROLLUP works fine.
--
-- After it finishes, your trustfund_olap database will be ready.
-- The queries at the bottom will also execute and show results.
-- ============================================================

CREATE DATABASE IF NOT EXISTS trustfund_olap
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE trustfund_olap;

;

-- ============================================================
-- DIMENSION TABLES
-- ============================================================

CREATE TABLE IF NOT EXISTS dim_time (
    time_key            INT AUTO_INCREMENT PRIMARY KEY,
    full_date           DATE NOT NULL UNIQUE,
    year                SMALLINT NOT NULL,
    quarter             TINYINT NOT NULL,
    month               TINYINT NOT NULL,
    month_name          VARCHAR(20),
    day                 TINYINT NOT NULL,
    day_of_week         TINYINT,
    week_of_year        TINYINT,
    is_weekend          BOOLEAN DEFAULT FALSE,
    fiscal_year         SMALLINT,
    fiscal_quarter      TINYINT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dim_user (
    user_key            INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT NOT NULL,
    username            VARCHAR(50),
    full_name           VARCHAR(101),
    role                ENUM('member','admin') DEFAULT 'member',
    status              VARCHAR(20),
    created_at          DATETIME,
    UNIQUE KEY uk_user_natural (user_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dim_group (
    group_key           INT AUTO_INCREMENT PRIMARY KEY,
    group_id            INT NOT NULL,
    group_name          VARCHAR(50),
    privacy             ENUM('public','private'),
    contribution_amount DECIMAL(10,2),
    max_members         INT,
    frequency           ENUM('weekly','biweekly','monthly'),
    status              VARCHAR(20),
    created_by_user_id  INT,
    created_at          DATETIME,
    UNIQUE KEY uk_group_natural (group_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dim_cycle (
    cycle_key           INT AUTO_INCREMENT PRIMARY KEY,
    cycle_id            INT NOT NULL,
    group_key           INT NOT NULL,
    cycle_number        INT NOT NULL,
    start_date          DATE,
    end_date            DATE,
    payout_status       VARCHAR(20),
    payout_member_id    INT,
    UNIQUE KEY uk_cycle_natural (cycle_id),
    KEY idx_cycle_group (group_key)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dim_member (
    member_key          INT AUTO_INCREMENT PRIMARY KEY,
    member_id           INT NOT NULL,
    user_key            INT NOT NULL,
    group_key           INT NOT NULL,
    position            INT,
    joined_at           DATETIME,
    UNIQUE KEY uk_member_natural (member_id),
    KEY idx_member_user (user_key),
    KEY idx_member_group (group_key)
) ENGINE=InnoDB;

;

-- ============================================================
-- FACT TABLE + INDEXES
-- ============================================================

CREATE TABLE IF NOT EXISTS fact_transactions (
    fact_id                 BIGINT AUTO_INCREMENT PRIMARY KEY,
    time_key                INT NOT NULL,
    user_key                INT NOT NULL,
    group_key               INT NOT NULL,
    cycle_key               INT NOT NULL,
    member_key              INT NOT NULL,
    transaction_type        ENUM('contribution','payout') NOT NULL,
    amount                  DECIMAL(10,2) NOT NULL,
    status                  VARCHAR(20),
    recorded_by_user_key    INT,
    source_transaction_id   INT,
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_fact_source (source_transaction_id),

    amount_contribution     DECIMAL(10,2) GENERATED ALWAYS AS (
        CASE WHEN transaction_type = 'contribution' THEN amount ELSE 0 END
    ) STORED,
    amount_payout           DECIMAL(10,2) GENERATED ALWAYS AS (
        CASE WHEN transaction_type = 'payout' THEN amount ELSE 0 END
    ) STORED,

    CONSTRAINT fk_fact_time     FOREIGN KEY (time_key)   REFERENCES dim_time(time_key),
    CONSTRAINT fk_fact_user     FOREIGN KEY (user_key)   REFERENCES dim_user(user_key),
    CONSTRAINT fk_fact_group    FOREIGN KEY (group_key)  REFERENCES dim_group(group_key),
    CONSTRAINT fk_fact_cycle    FOREIGN KEY (cycle_key)  REFERENCES dim_cycle(cycle_key),
    CONSTRAINT fk_fact_member   FOREIGN KEY (member_key) REFERENCES dim_member(member_key)
) ENGINE=InnoDB;

CREATE INDEX idx_fact_time_group_type ON fact_transactions(time_key, group_key, transaction_type);
CREATE INDEX idx_fact_group_cycle ON fact_transactions(group_key, cycle_key);
CREATE INDEX idx_fact_time_group ON fact_transactions(time_key, group_key);

;

-- ============================================================
-- ETL CONTROL TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS etl_control (
    entity_name         VARCHAR(50) PRIMARY KEY,
    last_sync_timestamp DATETIME,
    last_processed_id   BIGINT,
    rows_processed      INT DEFAULT 0,
    status              VARCHAR(20) DEFAULT 'success',
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO etl_control (entity_name, last_sync_timestamp) VALUES
('dim_time', '2000-01-01 00:00:00'),
('dim_user', '2000-01-01 00:00:00'),
('dim_group', '2000-01-01 00:00:00'),
('dim_cycle', '2000-01-01 00:00:00'),
('dim_member', '2000-01-01 00:00:00'),
('fact_transactions', '2000-01-01 00:00:00');

;

-- ============================================================
-- SAFE dim_time POPULATION (Recursive CTE - no DELIMITER issues)
-- Populates 2024-2027
-- ============================================================

INSERT IGNORE INTO dim_time (
    full_date, year, quarter, month, month_name, 
    day, day_of_week, week_of_year, is_weekend,
    fiscal_year, fiscal_quarter
)
WITH RECURSIVE date_range AS (
    SELECT '2024-01-01' AS d
    UNION ALL
    SELECT DATE_ADD(d, INTERVAL 1 DAY)
    FROM date_range
    WHERE d < '2027-12-31'
)
SELECT 
    d AS full_date,
    YEAR(d) AS year,
    QUARTER(d) AS quarter,
    MONTH(d) AS month,
    MONTHNAME(d) AS month_name,
    DAY(d) AS day,
    DAYOFWEEK(d) AS day_of_week,
    WEEK(d, 1) AS week_of_year,
    (DAYOFWEEK(d) IN (1,7)) AS is_weekend,
    YEAR(d) AS fiscal_year,
    QUARTER(d) AS fiscal_quarter
FROM date_range;

;

-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW vw_monthly_summary AS
SELECT 
    dt.year,
    dt.month,
    dt.month_name,
    dg.group_name,
    ft.transaction_type,
    COUNT(*) AS transaction_count,
    SUM(ft.amount) AS total_amount,
    AVG(ft.amount) AS avg_amount
FROM fact_transactions ft
JOIN dim_time dt     ON ft.time_key = dt.time_key
JOIN dim_group dg    ON ft.group_key = dg.group_key
GROUP BY dt.year, dt.month, dt.month_name, dg.group_name, ft.transaction_type
ORDER BY dt.year DESC, dt.month DESC;

CREATE OR REPLACE VIEW vw_group_performance AS
SELECT 
    dg.group_name,
    dt.year,
    dt.quarter,
    ft.transaction_type,
    SUM(ft.amount_contribution) AS total_contributions,
    SUM(ft.amount_payout)       AS total_payouts,
    COUNT(DISTINCT ft.member_key) AS active_members
FROM fact_transactions ft
JOIN dim_time dt   ON ft.time_key = dt.time_key
JOIN dim_group dg  ON ft.group_key = dg.group_key
GROUP BY dg.group_name, dt.year, dt.quarter, ft.transaction_type;

;

-- ============================================================
-- DEMONSTRATION QUERIES (will run when you paste the whole file)
-- These are safe because of subqueries + explicit separators
-- ============================================================

USE trustfund_olap;

;

-- 1. ROLL-UP (safe subquery version)
-- Note: GROUPING() removed for MariaDB 10.4 compatibility (XAMPP default).
-- In 10.4, NULLs in the GROUP BY columns mean subtotal/total rows.
SELECT * FROM (
    SELECT 
        dt.year,
        dt.quarter,
        dg.group_name,
        SUM(ft.amount_contribution) AS total_contributions,
        SUM(ft.amount_payout)       AS total_payouts,
        COUNT(*)                    AS transaction_count,
        AVG(ft.amount)              AS avg_transaction
    FROM fact_transactions ft
    JOIN dim_time   dt ON ft.time_key = dt.time_key
    JOIN dim_group  dg ON ft.group_key = dg.group_key
    WHERE ft.transaction_type IN ('contribution', 'payout')
    GROUP BY dt.year, dt.quarter, dg.group_name WITH ROLLUP
) AS rollup_results
ORDER BY 
    rollup_results.year DESC, 
    rollup_results.quarter, 
    rollup_results.group_name;

;

-- 2. DRILL-DOWN
SELECT 
    dg.group_name,
    du.full_name,
    dt.year,
    dt.month_name,
    SUM(ft.amount_contribution) AS contributed_by_member,
    COUNT(*) AS contribution_count,
    RANK() OVER (PARTITION BY dg.group_name ORDER BY SUM(ft.amount_contribution) DESC) AS rank_in_group
FROM fact_transactions ft
JOIN dim_time   dt  ON ft.time_key = dt.time_key
JOIN dim_group  dg  ON ft.group_key = dg.group_key
JOIN dim_user   du  ON ft.user_key = du.user_key
WHERE dt.year = 2025 
  AND ft.transaction_type = 'contribution'
GROUP BY dg.group_name, du.full_name, dt.year, dt.month_name
ORDER BY dg.group_name, contributed_by_member DESC;

;

-- 3. SLICE (one specific group)
SELECT 
    dt.year,
    dt.month_name,
    ft.transaction_type,
    SUM(ft.amount) AS monthly_total,
    COUNT(*) AS tx_count
FROM fact_transactions ft
JOIN dim_time  dt ON ft.time_key = dt.time_key
JOIN dim_group dg ON ft.group_key = dg.group_key
WHERE dg.group_name = 'Paluwagan Alpha'
GROUP BY dt.year, dt.month_name, ft.transaction_type
ORDER BY dt.year, dt.month;

;

-- 4. DICE
SELECT 
    dg.group_name,
    dt.quarter,
    ft.transaction_type,
    SUM(ft.amount) as total,
    COUNT(DISTINCT ft.user_key) as unique_users
FROM fact_transactions ft
JOIN dim_time dt ON ft.time_key = dt.time_key
JOIN dim_group dg ON ft.group_key = dg.group_key
WHERE dg.group_name IN ('Paluwagan Alpha', 'Savings Circle B')
  AND dt.year = 2025
  AND ft.transaction_type = 'contribution'
GROUP BY dg.group_name, dt.quarter, ft.transaction_type
ORDER BY total DESC;

;

-- 5. Advanced Window Functions
SELECT 
    dt.year,
    dt.month,
    dg.group_name,
    SUM(ft.amount_contribution) AS monthly_contrib,
    SUM(SUM(ft.amount_contribution)) OVER (PARTITION BY dg.group_name ORDER BY dt.year, dt.month) AS running_total,
    LAG(SUM(ft.amount_contribution)) OVER (PARTITION BY dg.group_name ORDER BY dt.year, dt.month) AS prev_month,
    ROUND(
        (SUM(ft.amount_contribution) - LAG(SUM(ft.amount_contribution)) OVER (PARTITION BY dg.group_name ORDER BY dt.year, dt.month)) 
        / NULLIF(LAG(SUM(ft.amount_contribution)) OVER (PARTITION BY dg.group_name ORDER BY dt.year, dt.month), 0) * 100, 1
    ) AS pct_change
FROM fact_transactions ft
JOIN dim_time  dt ON ft.time_key = dt.time_key
JOIN dim_group dg ON ft.group_key = dg.group_key
WHERE ft.transaction_type = 'contribution'
  AND dt.year >= 2025
GROUP BY dt.year, dt.month, dg.group_name
ORDER BY dg.group_name, dt.year, dt.month;

;

-- 6. Payouts by Position
SELECT 
    dm.position,
    dg.group_name,
    COUNT(*) AS times_received,
    SUM(ft.amount_payout) AS total_received,
    AVG(ft.amount_payout) AS avg_payout
FROM fact_transactions ft
JOIN dim_member dm ON ft.member_key = dm.member_key
JOIN dim_group  dg ON ft.group_key = dg.group_key
WHERE ft.transaction_type = 'payout'
GROUP BY dm.position, dg.group_name
ORDER BY dg.group_name, dm.position;

-- ============================================================
-- END
-- Your OLAP database is now ready.
-- Run the PHP ETL (etl_sync.php) later to load real data from your OLTP.
-- ============================================================