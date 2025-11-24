#!/bin/bash

echo "🧪 Test de l'authentification NetLab"
echo "===================================="
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

info() {
    echo -e "${GREEN}ℹ️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warn() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# 1. Vérifier que les utilisateurs admin existent
info "1. Vérification des utilisateurs admin..."
php artisan tinker --execute="
\$admins = App\Models\User::where('role', 'admin')->get(['id', 'name', 'email']);
if (\$admins->count() > 0) {
    echo 'Utilisateurs admin trouvés:' . PHP_EOL;
    foreach (\$admins as \$admin) {
        echo '  - ' . \$admin->name . ' (' . \$admin->email . ')' . PHP_EOL;
    }
} else {
    echo 'Aucun utilisateur admin trouvé' . PHP_EOL;
}
"

# 2. Tester les routes d'authentification
info ""
info "2. Test des routes d'authentification..."

# Test de la page de login
info "   Test GET /login..."
LOGIN_GET=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/login)
if [ "$LOGIN_GET" = "200" ]; then
    success "   Page de login accessible (HTTP $LOGIN_GET)"
else
    error "   Page de login non accessible (HTTP $LOGIN_GET)"
fi

# Test de la page de register
info "   Test GET /register..."
REGISTER_GET=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/register)
if [ "$REGISTER_GET" = "200" ]; then
    success "   Page d'inscription accessible (HTTP $REGISTER_GET)"
else
    error "   Page d'inscription non accessible (HTTP $REGISTER_GET)"
fi

# 3. Test de connexion (simulation)
info ""
info "3. Test de connexion avec l'utilisateur admin..."

# Créer un script PHP pour tester la connexion
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

\$user = App\Models\User::where('email', 'admin@netlab.local')->first();
if (\$user) {
    echo '✅ Utilisateur admin trouvé: ' . \$user->email . PHP_EOL;
    echo '   Mot de passe par défaut: password' . PHP_EOL;
    echo '   Test de hash du mot de passe...' . PHP_EOL;
    
    if (Hash::check('password', \$user->password)) {
        echo '✅ Mot de passe valide' . PHP_EOL;
    } else {
        echo '❌ Mot de passe invalide' . PHP_EOL;
    }
} else {
    echo '❌ Utilisateur admin non trouvé' . PHP_EOL;
}
"

# 4. Afficher les informations de connexion
echo ""
info "4. Informations de connexion :"
echo ""
echo "   📧 Email admin: admin@netlab.local"
echo "   🔑 Mot de passe: password"
echo ""
echo "   📧 Email admin test: admin@example.com"
echo "   🔑 Mot de passe: admin123"
echo ""
echo "🌐 URLs de test :"
echo "   - Login: http://localhost:8000/login"
echo "   - Register: http://localhost:8000/register"
echo "   - Dashboard: http://localhost:8000/dashboard"
echo ""

success "Tests terminés !"
echo ""
info "Pour tester manuellement :"
echo "1. Ouvrez http://localhost:8000/login"
echo "2. Connectez-vous avec admin@netlab.local / password"
echo "3. Testez la déconnexion"
echo ""

