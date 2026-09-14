-- Afzonderlijk leesrecht voor het voedingsoverzicht.
ALTER TABLE coach_tokens
 ADD COLUMN can_view_nutrition BOOLEAN NOT NULL DEFAULT FALSE AFTER can_view_training;
