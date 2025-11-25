-- Script SQL pour marquer la migration settings comme exécutée
-- Usage: psql -h 127.0.0.1 -U netlab -d netlab -f scripts/mark-settings-migration-done.sql

-- Vérifier si la table settings existe
DO $$
BEGIN
    IF EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'settings') THEN
        RAISE NOTICE 'Table settings existe déjà';
        
        -- Vérifier si la migration est déjà enregistrée
        IF NOT EXISTS (
            SELECT 1 FROM migrations 
            WHERE migration = '2025_11_17_114322_create_settings_table'
        ) THEN
            -- Insérer l'enregistrement de migration
            INSERT INTO migrations (migration, batch)
            VALUES (
                '2025_11_17_114322_create_settings_table',
                (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations)
            );
            RAISE NOTICE 'Migration marquée comme exécutée';
        ELSE
            RAISE NOTICE 'Migration déjà enregistrée';
        END IF;
    ELSE
        RAISE NOTICE 'Table settings n''existe pas - la migration doit être exécutée normalement';
    END IF;
END $$;

