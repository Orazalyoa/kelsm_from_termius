-- Add ai_assistant type to messages table
-- This migration adds support for AI assistant messages

ALTER TABLE messages 
MODIFY COLUMN type ENUM('text', 'document', 'image', 'system', 'ai_assistant') DEFAULT 'text';

