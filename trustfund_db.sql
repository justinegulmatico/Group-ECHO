-- Cleaned reference schema (from original trustfund_db (1).sql)
-- Use this to set up your local DB for the Paluwagan app.
-- (Full original dump with sample data was in the repo on other branches)

CREATE DATABASE IF NOT EXISTS trustfund_db;
USE trustfund_db;

-- (For brevity in this commit the full CREATEs + sample data are the same as the previous trustfund_db (1).sql)
-- Key tables used by the simplified logic:
-- users, groups (contribution_amount, cycle_length, invite_code, created_by, is_active, status), group_members, cycles, contributions, payouts, user_verifications

-- You can import the full original SQL file from the local project or other branches if you need the sample data.