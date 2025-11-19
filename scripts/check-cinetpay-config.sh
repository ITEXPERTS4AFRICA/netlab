#!/bin/bash

# Script de vérification de la configuration CinetPay
# Usage: ./scripts/check-cinetpay-config.sh

echo "🔍 Vérification de la configuration CinetPay..."
echo ""

ENV_FILE=".env"

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Fichier .env introuvable"
    exit 1
fi

# Vérifier les variables requises
echo "📋 Variables requises :"
echo ""

REQUIRED_VARS=("CINETPAY_API_KEY" "CINETPAY_SITE_ID" "CINETPAY_MODE")
MISSING_VARS=()

for var in "${REQUIRED_VARS[@]}"; do
    if grep -q "^${var}=" "$ENV_FILE"; then
        value=$(grep "^${var}=" "$ENV_FILE" | cut -d '=' -f2- | tr -d ' ')
        if [ -z "$value" ]; then
            echo "⚠️  $var est défini mais vide"
            MISSING_VARS+=("$var")
        else
            # Vérifier si la valeur est collée avec une autre variable
            if echo "$value" | grep -qE "^[a-zA-Z_]+="; then
                echo "❌ $var a une valeur invalide (probablement collée avec une autre variable)"
                echo "   Valeur actuelle: $value"
                echo "   Correction: Mettre $var sur une ligne séparée"
                MISSING_VARS+=("$var")
            else
                echo "✅ $var = $value"
            fi
        fi
    else
        echo "❌ $var n'est pas défini"
        MISSING_VARS+=("$var")
    fi
done

echo ""
echo "📋 Variables optionnelles :"
echo ""

OPTIONAL_VARS=("CINETPAY_NOTIFY_URL" "CINETPAY_RETURN_URL" "CINETPAY_CANCEL_URL" "CINETPAY_API_URL")
for var in "${OPTIONAL_VARS[@]}"; do
    if grep -q "^${var}=" "$ENV_FILE"; then
        value=$(grep "^${var}=" "$ENV_FILE" | cut -d '=' -f2- | tr -d ' ')
        echo "✅ $var = $value"
    else
        echo "ℹ️  $var non défini (sera généré automatiquement)"
    fi
done

echo ""
echo "🔧 Vérification du mode CinetPay :"
echo ""

CINETPAY_MODE=$(grep "^CINETPAY_MODE=" "$ENV_FILE" | cut -d '=' -f2- | tr -d ' ' | tr '[:upper:]' '[:lower:]')

if [ -z "$CINETPAY_MODE" ]; then
    echo "❌ CINETPAY_MODE n'est pas défini"
elif [[ "$CINETPAY_MODE" == *"production"* ]] || [[ "$CINETPAY_MODE" == *"prod"* ]]; then
    echo "✅ Mode: production"
elif [[ "$CINETPAY_MODE" == *"sandbox"* ]] || [[ "$CINETPAY_MODE" == *"test"* ]]; then
    echo "✅ Mode: sandbox"
else
    echo "⚠️  Mode invalide: $CINETPAY_MODE"
    echo "   Valeurs acceptées: production, sandbox, test"
fi

echo ""
echo "🌐 Vérification de APP_URL :"
echo ""

if grep -q "^APP_URL=" "$ENV_FILE"; then
    APP_URL=$(grep "^APP_URL=" "$ENV_FILE" | cut -d '=' -f2- | tr -d ' ')
    echo "✅ APP_URL = $APP_URL"
    echo ""
    echo "   URLs de callback qui seront utilisées :"
    echo "   - Webhook: $APP_URL/api/payments/cinetpay/webhook"
    echo "   - Return:  $APP_URL/api/payments/return"
    echo "   - Cancel:  $APP_URL/api/payments/cancel"
else
    echo "⚠️  APP_URL n'est pas défini"
    echo "   Les URLs de callback utiliseront l'URL par défaut de Laravel"
fi

echo ""
if [ ${#MISSING_VARS[@]} -eq 0 ]; then
    echo "✅ Configuration CinetPay semble correcte !"
    echo ""
    echo "💡 Pour appliquer les changements, exécutez :"
    echo "   php artisan config:clear"
    echo "   php artisan cache:clear"
    exit 0
else
    echo "❌ Configuration incomplète ou incorrecte"
    echo ""
    echo "📝 Variables à corriger :"
    for var in "${MISSING_VARS[@]}"; do
        echo "   - $var"
    done
    echo ""
    echo "💡 Consultez docs/CINETPAY_CONFIG.md pour plus d'informations"
    exit 1
fi

