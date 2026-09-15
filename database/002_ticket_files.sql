-- Additive migration: preserve existing attachment metadata and employee photo data.
ALTER TABLE ticket_attachments ADD COLUMN file_data MEDIUMBLOB NULL;
