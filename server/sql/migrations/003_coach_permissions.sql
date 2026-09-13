-- Bestaande links behouden alleen het weekoverzicht. Uitvoeren vóór de PHP-bestanden worden vervangen.
ALTER TABLE coach_tokens
    ADD COLUMN can_view_overview BOOLEAN NOT NULL DEFAULT TRUE AFTER active,
    ADD COLUMN can_view_training BOOLEAN NOT NULL DEFAULT FALSE AFTER can_view_overview;
