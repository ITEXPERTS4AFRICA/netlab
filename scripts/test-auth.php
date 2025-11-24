<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

echo "🧪 Test de l'authentification NetLab\n";
echo "====================================\n\n";

// 1. Vérifier les utilisateurs admin
echo "1. Vérification des utilisateurs admin...\n";
$admins = User::where('role', 'admin')->get(['id', 'name', 'email', 'password']);

if ($admins->count() > 0) {
    echo "✅ Utilisateurs admin trouvés:\n";
    foreach ($admins as $admin) {
        echo "   - {$admin->name} ({$admin->email})\n";
        
        // Tester le mot de passe
        if (Hash::check('password', $admin->password)) {
            echo "     ✅ Mot de passe 'password' valide\n";
        } elseif (Hash::check('admin123', $admin->password)) {
            echo "     ✅ Mot de passe 'admin123' valide\n";
        } else {
            echo "     ⚠️  Mot de passe par défaut non valide\n";
        }
    }
} else {
    echo "❌ Aucun utilisateur admin trouvé\n";
}

// 2. Tester la création d'un utilisateur de test
echo "\n2. Test de création d'utilisateur (signup)...\n";
$testUser = User::firstOrCreate(
    ['email' => 'test@example.com'],
    [
        'name' => 'Test User',
        'password' => Hash::make('password'),
        'role' => 'student',
        'is_active' => true,
        'email_verified_at' => now(),
    ]
);

if ($testUser->wasRecentlyCreated) {
    echo "✅ Utilisateur de test créé: test@example.com / password\n";
} else {
    echo "ℹ️  Utilisateur de test existe déjà: test@example.com / password\n";
}

// 3. Tester l'authentification
echo "\n3. Test d'authentification...\n";
$admin = User::where('email', 'admin@netlab.local')->first();

if ($admin) {
    // Simuler une tentative de connexion
    $credentials = ['email' => 'admin@netlab.local', 'password' => 'password'];
    
    if (Hash::check($credentials['password'], $admin->password)) {
        echo "✅ Authentification réussie pour admin@netlab.local\n";
    } else {
        echo "❌ Échec d'authentification (mot de passe incorrect)\n";
    }
} else {
    echo "❌ Utilisateur admin non trouvé\n";
}

// 4. Résumé
echo "\n📋 Résumé des comptes disponibles:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "👤 Administrateur principal:\n";
echo "   Email: admin@netlab.local\n";
echo "   Mot de passe: password\n";
echo "   Rôle: admin\n\n";

echo "👤 Administrateur test:\n";
echo "   Email: admin@example.com\n";
echo "   Mot de passe: admin123\n";
echo "   Rôle: admin\n\n";

echo "👤 Utilisateur de test:\n";
echo "   Email: test@example.com\n";
echo "   Mot de passe: password\n";
echo "   Rôle: student\n\n";

echo "🌐 URLs de test:\n";
echo "   - Login: http://localhost:8000/login\n";
echo "   - Register: http://localhost:8000/register\n";
echo "   - Dashboard: http://localhost:8000/dashboard\n";
echo "   - Logout: POST http://localhost:8000/logout\n\n";

echo "✅ Tests terminés !\n";

