-- Meal Planning Feature - Database Schema
-- Requires: PostgreSQL 9.4+ (for GIN jsonb_path_ops)
--
-- Run: psql -f api/migrations/001_create_meals.sql

CREATE TABLE meals (
  id SERIAL PRIMARY KEY,
  slug VARCHAR(255) NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT,
  snapshot JSONB NOT NULL,
  is_stale BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  deleted_at TIMESTAMP WITH TIME ZONE DEFAULT NULL
);

-- Partial unique index allows reusing slugs after soft delete
CREATE UNIQUE INDEX idx_meals_slug_unique ON meals(slug) WHERE deleted_at IS NULL;

-- Partial indexes filter out soft-deleted meals
CREATE INDEX idx_meals_slug ON meals(slug) WHERE deleted_at IS NULL;
CREATE INDEX idx_meals_created_at ON meals(created_at DESC) WHERE deleted_at IS NULL;
CREATE INDEX idx_meals_stale ON meals(is_stale) WHERE is_stale = TRUE AND deleted_at IS NULL;

-- GIN index for JSONB recipe lookup - index the recipes array specifically
CREATE INDEX idx_meals_snapshot_recipes ON meals USING GIN ((snapshot->'recipes') jsonb_path_ops);

-- Constraint to ensure snapshot has required structure
ALTER TABLE meals ADD CONSTRAINT snapshot_is_object
  CHECK (jsonb_typeof(snapshot) = 'object');

-- Verification queries (run after migration):
--
-- 1. Verify table structure:
--    \d meals
--
-- 2. Test GIN index is being used:
--    EXPLAIN ANALYZE
--    SELECT id, slug FROM meals
--    WHERE snapshot->'recipes' @> '[{"slug": "user-test-recipe"}]'::jsonb
--    AND deleted_at IS NULL;
--    -- Should show: "Bitmap Index Scan on idx_meals_snapshot_recipes"
--
-- 3. Test partial unique index (soft delete support):
--    -- Create a meal, soft delete it, create another with same title
--    -- Both should succeed if partial unique index works correctly
