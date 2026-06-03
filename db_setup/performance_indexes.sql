-- ─────────────────────────────────────────────────────────────────────────────
-- Performance indexes for Adildata
-- Run once on your MySQL/cPanel database (safe to re-run — uses IF NOT EXISTS)
-- ─────────────────────────────────────────────────────────────────────────────

-- users_tbl: token lookup (removes the full-table-scan in verify_token)
ALTER TABLE users_tbl ADD INDEX IF NOT EXISTS idx_token (token(64));

-- users_tbl: BVN/NIN duplicate checks
ALTER TABLE users_tbl ADD INDEX IF NOT EXISTS idx_bvn (bvn);
ALTER TABLE users_tbl ADD INDEX IF NOT EXISTS idx_nin (nin);

-- users_tbl: login by email
ALTER TABLE users_tbl ADD INDEX IF NOT EXISTS idx_email (email);

-- wallet_tbl: balance lookup by user_id (email)
ALTER TABLE wallet_tbl ADD INDEX IF NOT EXISTS idx_user_id (user_id(100));

-- wallet_history_tbl: duplicate-payment check (trans_id) + user history
ALTER TABLE wallet_history_tbl ADD INDEX IF NOT EXISTS idx_trans_id (trans_id(64));
ALTER TABLE wallet_history_tbl ADD INDEX IF NOT EXISTS idx_email (email(100));

-- transactions_tbl: user transaction history
ALTER TABLE transactions_tbl ADD INDEX IF NOT EXISTS idx_email (email(100));

-- payment_history_tbl: webhook status update
ALTER TABLE payment_history_tbl ADD INDEX IF NOT EXISTS idx_trans_id (trans_id(64));

-- notifications_tbl: user notification fetch
ALTER TABLE notifications_tbl ADD INDEX IF NOT EXISTS idx_status_target (status, target(20));
