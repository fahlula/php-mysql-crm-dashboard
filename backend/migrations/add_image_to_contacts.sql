-- Migration: Add image field to contacts table
-- Date: 2024-01-15

ALTER TABLE contacts ADD COLUMN image TEXT NULL;

-- Add index for better performance if needed
-- CREATE INDEX idx_contacts_user_id ON contacts(user_id);