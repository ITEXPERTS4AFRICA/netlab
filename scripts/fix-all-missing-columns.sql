-- Script SQL pour ajouter toutes les colonnes manquantes à la table labs
-- Exécuter avec: psql -h 127.0.0.1 -U netlab -d netlab -f scripts/fix-all-missing-columns.sql

-- Ajouter toutes les colonnes de métadonnées si elles n'existent pas
DO $$ 
BEGIN
    -- Colonnes de base
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'price_cents') THEN
        ALTER TABLE labs ADD COLUMN price_cents BIGINT;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'currency') THEN
        ALTER TABLE labs ADD COLUMN currency VARCHAR(3) DEFAULT 'XOF';
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'readme') THEN
        ALTER TABLE labs ADD COLUMN readme TEXT;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'short_description') THEN
        ALTER TABLE labs ADD COLUMN short_description TEXT;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'tags') THEN
        ALTER TABLE labs ADD COLUMN tags JSONB;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'categories') THEN
        ALTER TABLE labs ADD COLUMN categories JSONB;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'difficulty_level') THEN
        ALTER TABLE labs ADD COLUMN difficulty_level VARCHAR(50);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'estimated_duration_minutes') THEN
        ALTER TABLE labs ADD COLUMN estimated_duration_minutes INTEGER;
    END IF;

    -- Colonnes importantes pour la publication
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'is_featured') THEN
        ALTER TABLE labs ADD COLUMN is_featured BOOLEAN DEFAULT false;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'is_published') THEN
        ALTER TABLE labs ADD COLUMN is_published BOOLEAN DEFAULT false;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'view_count') THEN
        ALTER TABLE labs ADD COLUMN view_count INTEGER DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'reservation_count') THEN
        ALTER TABLE labs ADD COLUMN reservation_count INTEGER DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'rating') THEN
        ALTER TABLE labs ADD COLUMN rating DECIMAL(3,2);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'rating_count') THEN
        ALTER TABLE labs ADD COLUMN rating_count INTEGER DEFAULT 0;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'requirements') THEN
        ALTER TABLE labs ADD COLUMN requirements JSONB;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'learning_objectives') THEN
        ALTER TABLE labs ADD COLUMN learning_objectives JSONB;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'labs' AND column_name = 'metadata') THEN
        ALTER TABLE labs ADD COLUMN metadata JSONB;
    END IF;
END $$;

-- Créer les index pour les performances
CREATE INDEX IF NOT EXISTS labs_is_published_index ON labs(is_published);
CREATE INDEX IF NOT EXISTS labs_is_featured_index ON labs(is_featured);
CREATE INDEX IF NOT EXISTS labs_difficulty_level_index ON labs(difficulty_level);
CREATE INDEX IF NOT EXISTS labs_price_cents_index ON labs(price_cents);

-- Marquer tous les labs existants comme publiés par défaut
UPDATE labs SET is_published = true WHERE is_published IS NULL OR is_published = false;

-- Vérifier le résultat
SELECT 
    id, 
    lab_title, 
    is_published, 
    is_featured,
    price_cents,
    currency
FROM labs 
LIMIT 5;

