# Exemples de Réponses JSON - Système de Génération Intelligente de Commandes

## 📋 Endpoints et Réponses

### 1. GET /api/labs/{labId}/commands/analyze

**Description** : Analyser le lab et générer des commandes intelligentes pour tous les nodes.

**Réponse JSON** :
```json
{
    "lab_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "total_nodes": 3,
    "total_commands": 15,
    "commands_by_node": {
        "node-1-uuid": {
            "node_id": "node-1-uuid",
            "node_label": "Router-1",
            "node_definition": "iosv",
            "commands": [
                {
                    "command": "show version",
                    "description": "Afficher la version du système",
                    "category": "system",
                    "priority": 1
                },
                {
                    "command": "show running-config",
                    "description": "Afficher la configuration en cours",
                    "category": "configuration",
                    "priority": 2
                },
                {
                    "command": "show ip interface brief",
                    "description": "Afficher un résumé des interfaces IP",
                    "category": "interface",
                    "priority": 3
                },
                {
                    "command": "show ip route",
                    "description": "Afficher la table de routage",
                    "category": "routing",
                    "priority": 4
                },
                {
                    "command": "show ip ospf neighbor",
                    "description": "Afficher les voisins OSPF",
                    "category": "routing",
                    "priority": 5
                }
            ]
        },
        "node-2-uuid": {
            "node_id": "node-2-uuid",
            "node_label": "Switch-1",
            "node_definition": "iosv-l2",
            "commands": [
                {
                    "command": "show version",
                    "description": "Afficher la version du système",
                    "category": "system",
                    "priority": 1
                },
                {
                    "command": "show vlan brief",
                    "description": "Afficher un résumé des VLANs",
                    "category": "vlan",
                    "priority": 3
                },
                {
                    "command": "show interface status",
                    "description": "Afficher le statut des interfaces",
                    "category": "interface",
                    "priority": 4
                },
                {
                    "command": "show spanning-tree",
                    "description": "Afficher l'état du spanning tree",
                    "category": "switching",
                    "priority": 5
                },
                {
                    "command": "show mac address-table",
                    "description": "Afficher la table d'adresses MAC",
                    "category": "switching",
                    "priority": 6
                }
            ]
        }
    }
}
```

---

### 2. GET /api/labs/{labId}/nodes/{nodeId}/commands/recommended

**Description** : Obtenir les commandes recommandées pour un node spécifique.

**Réponse JSON** :
```json
{
    "lab_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "node_id": "node-1-uuid",
    "node_label": "Router-1",
    "node_definition": "iosv",
    "commands": [
        {
            "command": "show version",
            "description": "Afficher la version du système",
            "category": "system",
            "priority": 1
        },
        {
            "command": "show running-config",
            "description": "Afficher la configuration en cours",
            "category": "configuration",
            "priority": 2
        },
        {
            "command": "show ip interface brief",
            "description": "Afficher un résumé des interfaces IP",
            "category": "interface",
            "priority": 3
        },
        {
            "command": "show ip route",
            "description": "Afficher la table de routage",
            "category": "routing",
            "priority": 4
        },
        {
            "command": "show interface GigabitEthernet0/0/0",
            "description": "Afficher les détails de l'interface GigabitEthernet0/0/0",
            "category": "interface",
            "priority": 7
        }
    ],
    "total_commands": 5
}
```

---

### 3. GET /api/labs/{labId}/commands/script

**Description** : Générer un script de configuration automatique pour tout le lab.

**Réponse JSON** :
```json
{
    "lab_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "script": "! Script de configuration généré automatiquement\n! Lab ID: a1b2c3d4-e5f6-7890-abcd-ef1234567890\n! Généré le: 2025-01-15 10:30:00\n\n! Configuration pour: Router-1 (iosv)\n! Node ID: node-1-uuid\n\n! --- system ---\nshow version\nshow running-config\n\n! --- interface ---\nshow ip interface brief\nshow interface GigabitEthernet0/0/0\n\n! --- routing ---\nshow ip route\nshow ip ospf neighbor\n",
    "analysis": {
        "lab_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
        "total_nodes": 3,
        "total_commands": 15,
        "commands_by_node": {
            // Structure identique à analyzeLab
        }
    }
}
```

---

### 4. POST /api/labs/{labId}/nodes/{nodeId}/commands/execute

**Description** : Préparer l'exécution d'une commande générée.

**Requête JSON** :
```json
{
    "command": "show version",
    "category": "system"
}
```

**Réponse JSON** :
```json
{
    "lab_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "node_id": "node-1-uuid",
    "command": "show version",
    "category": "system",
    "instructions": {
        "step_1": "La commande doit être tapée dans la console IOS",
        "step_2": "Utiliser le polling des logs pour récupérer les résultats",
        "step_3": "GET /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log"
    },
    "note": "CML n'expose pas d'API REST pour exécuter des commandes CLI. La commande doit être tapée manuellement dans la console."
}
```

---

## ❌ Réponses d'Erreur

### Erreur 401 (Non autorisé)
```json
{
    "error": "Token CML non disponible. Veuillez vous reconnecter.",
    "status": 401
}
```

### Erreur 404 (Non trouvé)
```json
{
    "error": "Aucune commande trouvée pour ce node",
    "node_id": "node-1-uuid"
}
```

### Erreur 500 (Erreur serveur)
```json
{
    "error": "Erreur lors de l'analyse: Impossible de récupérer la topologie du lab",
    "status": 500
}
```

---

## 📊 Catégories de Commandes

Les commandes sont organisées par catégories :

- **system** : Commandes système (`show version`, `show running-config`)
- **configuration** : Commandes de configuration
- **interface** : Commandes d'interface (`show ip interface brief`, `show interface ...`)
- **routing** : Commandes de routage (`show ip route`, `show ip ospf neighbor`)
- **vlan** : Commandes VLAN (`show vlan brief`)
- **switching** : Commandes de switching (`show spanning-tree`, `show mac address-table`)

---

## 🔢 Priorités

Les commandes ont une priorité numérique (1 = plus prioritaire) :

1. **Priority 1-2** : Commandes système de base
2. **Priority 3-4** : Commandes d'interface et de routage de base
3. **Priority 5-6** : Commandes avancées (OSPF, spanning-tree, etc.)
4. **Priority 7+** : Commandes spécifiques aux interfaces connectées

---

## 📝 Notes Importantes

1. **Pas d'API REST pour exécution** : CML n'expose pas d'API pour exécuter des commandes directement. Les commandes doivent être tapées dans la console IOS.

2. **Polling des logs** : Les résultats sont récupérés via `GET /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log`.

3. **Génération automatique** : Les commandes sont générées automatiquement selon :
   - Le type de node (routeur, switch, etc.)
   - Les interfaces connectées
   - Les protocoles de routage détectés
   - La structure du lab

---

## 🧪 Test des Réponses

Pour tester les réponses JSON, exécutez :

```bash
php test-json-responses-examples.php
```

Ou utilisez le script de test complet :

```bash
php test-intelligent-commands-api.php
```


