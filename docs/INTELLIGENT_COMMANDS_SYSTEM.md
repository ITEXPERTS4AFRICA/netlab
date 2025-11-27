# Système de Génération Intelligente de Commandes CLI

## 🎯 Problème Résolu

L'iframe console CML renvoie une erreur 404 (`/notfound?id=...`). CML n'expose pas d'API REST pour envoyer des commandes CLI directement.

**Solution** : Système de génération automatique de commandes CLI basé sur la structure du lab, avec envoi via JSON et récupération des résultats via polling.

---

## 📋 Architecture

### 1. Service Backend : `IntelligentCommandGenerator`

**Fichier** : `app/Services/IntelligentCommandGenerator.php`

**Fonctionnalités** :
- Analyse la topologie du lab (nodes, interfaces, liens)
- Génère automatiquement des commandes CLI adaptées selon :
  - Le type de node (routeur, switch, etc.)
  - Les interfaces connectées
  - Les protocoles de routage détectés
  - La structure du lab

**Méthodes principales** :
- `analyzeLabAndGenerateCommands()` : Analyse complète du lab
- `generateConfigurationScript()` : Génère un script de configuration
- `generateCommandsForNode()` : Génère des commandes pour un node spécifique

### 2. Contrôleur API : `IntelligentCommandController`

**Fichier** : `app/Http/Controllers/Api/IntelligentCommandController.php`

**Endpoints** :
- `GET /api/labs/{labId}/commands/analyze` : Analyser le lab et générer des commandes
- `GET /api/labs/{labId}/commands/script` : Générer un script de configuration
- `GET /api/labs/{labId}/nodes/{nodeId}/commands/recommended` : Commandes recommandées pour un node
- `POST /api/labs/{labId}/nodes/{nodeId}/commands/execute` : Préparer l'exécution d'une commande

### 3. Hook React : `useIntelligentCommands`

**Fichier** : `resources/js/hooks/useIntelligentCommands.ts`

**Fonctionnalités** :
- `analyzeLab()` : Analyser le lab et obtenir toutes les commandes
- `getRecommendedCommands()` : Obtenir les commandes recommandées pour un node
- `generateScript()` : Générer un script de configuration
- `executeCommand()` : Préparer l'exécution d'une commande

---

## 🔧 Utilisation

### Backend (PHP)

```php
use App\Services\IntelligentCommandGenerator;
use App\Services\CiscoApiService;

$cisco = new CiscoApiService();
$generator = new IntelligentCommandGenerator($cisco);

// Analyser un lab
$analysis = $generator->analyzeLabAndGenerateCommands($labId);

// Générer un script
$script = $generator->generateConfigurationScript($labId);
```

### Frontend (React/TypeScript)

```typescript
import { useIntelligentCommands } from '@/hooks/useIntelligentCommands';

function MyComponent() {
    const { 
        analyzeLab, 
        getRecommendedCommands, 
        recommendedCommands,
        loading 
    } = useIntelligentCommands();

    // Analyser le lab
    useEffect(() => {
        analyzeLab(labId).then(analysis => {
            console.log('Commandes générées:', analysis);
        });
    }, [labId]);

    // Obtenir les commandes recommandées pour un node
    const handleGetCommands = async () => {
        const commands = await getRecommendedCommands(labId, nodeId);
        console.log('Commandes recommandées:', commands);
    };

    return (
        <div>
            {recommendedCommands.map(cmd => (
                <button onClick={() => executeCommand(cmd.command)}>
                    {cmd.description}
                </button>
            ))}
        </div>
    );
}
```

---

## 📊 Types de Commandes Générées

### Commandes Système (tous les équipements)
- `show version` : Version du système
- `show running-config` : Configuration en cours

### Commandes Routeur
- `show ip interface brief` : Résumé des interfaces IP
- `show ip route` : Table de routage
- `show ip ospf neighbor` : Voisins OSPF
- `show ip eigrp neighbors` : Voisins EIGRP

### Commandes Switch
- `show vlan brief` : Résumé des VLANs
- `show interface status` : Statut des interfaces
- `show spanning-tree` : État du spanning tree
- `show mac address-table` : Table d'adresses MAC

### Commandes Interface
- `show interface GigabitEthernet0/0/0` : Détails d'une interface spécifique
- Générées automatiquement selon les interfaces connectées

### Commandes Routage
- `show ip protocols` : Protocoles de routage configurés
- `show ip ospf database` : Base de données OSPF

---

## 🔄 Flux d'Exécution

```
1. Analyse du Lab
   ↓
2. Génération de Commandes Intelligentes
   ↓
3. Affichage des Commandes Recommandées
   ↓
4. Sélection d'une Commande
   ↓
5. Envoi via Console IOS (pas d'API directe)
   ↓
6. Polling des Logs pour Récupérer les Résultats
```

---

## ⚠️ Limitations

1. **Pas d'API REST pour commandes** : CML n'expose pas d'API pour envoyer des commandes directement
2. **Polling nécessaire** : Les résultats sont récupérés via `GET /consoles/{console_id}/log`
3. **Console ID requis** : Il faut connaître le `console_id` pour récupérer les logs

---

## 🎯 Prochaines Étapes

1. ✅ Système de génération intelligente créé
2. ✅ API endpoints opérationnels
3. ✅ Hook React disponible
4. 🔄 Intégration dans le composant console
5. 🔄 Correction de l'URL console (404)

---

## 📝 Notes

- Les commandes sont générées automatiquement selon la structure du lab
- Le système détecte automatiquement les types de nodes et génère les commandes appropriées
- Les commandes sont groupées par catégorie (system, interface, routing, etc.)
- Chaque commande a une priorité pour l'ordre d'affichage

---

## 🔗 Références

- Documentation CML 2.9.x : `docs/CML_2.9_CONSOLE_API_DOCUMENTATION.md`
- Endpoints console : `routes/api.php`
- Service console : `app/Services/Cisco/ConsoleService.php`


