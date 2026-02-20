-- Add operator_id field to messages table
-- This tracks which operator sent or triggered AI for a message

ALTER TABLE messages 
ADD COLUMN operator_id INT NULL COMMENT 'Operator (admin_users.id) who sent this message' AFTER sender_id,
ADD INDEX idx_messages_operator_id (operator_id);

-- Add comment to clarify the usage
ALTER TABLE messages MODIFY COLUMN sender_id INT NULL COMMENT 'User who sent the message (NULL for system messages)';

