# Test des Commandes CLI dans la Console IOS

## Vue d'ensemble

Ce document explique comment tester l'utilisation de commandes CLI dans la console IOS pour gérer les équipements réseau dans les labs CML.

## Architecture

### Limitations de CML

**Important** : CML (Cisco Modeling Labs) n'expose **pas d'API directe** pour envoyer des commandes CLI aux équipements réseau. Les commandes doivent être tapées directement dans l'iframe de la console.

### Solution implémentée

Le système utilise une approche hybride :

1. **Mode iframe** : Les commandes sont tapées directement dans l'iframe CML
2. **Polling des logs** : Les résultats sont récupérés via l'API CML (`/api/v0/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/log`)
3. **Mode IOS Intelligent** : Interface améliorée avec auto-complétion et historique

## Comment tester

### 1. Accéder à la console

1. Ouvrir un lab dans NetLab
2. Sélectionner un nœud (routeur, switch, etc.)
3. Cliquer sur "Ouvrir une session" dans le panneau Console
4. Choisir le mode :
   - **IOS Intelligent** : Interface améliorée avec auto-complétion
   - **Standard** : iframe CML native

### 2. Envoyer des commandes CLI

#### Mode IOS Intelligent

1. Taper une commande dans le champ de saisie
2. Utiliser Tab pour l'auto-complétion
3. Utiliser les flèches ↑/↓ pour naviguer dans l'historique
4. Appuyer sur Enter pour envoyer

#### Mode Standard (iframe)

1. Cliquer dans l'iframe pour activer le focus
2. Taper directement les commandes dans le terminal
3. Les résultats s'affichent dans l'iframe

### 3. Tester avec le composant TDD

Le composant `ConsoleCommandTester` permet de tester automatiquement plusieurs commandes :

1. Dans le panneau Console, faire défiler jusqu'à "Tests TDD - Commandes Console"
2. Cliquer sur "Lancer les tests"
3. Le système va :
   - Envoyer chaque commande
   - Attendre les résultats
   - Afficher les statistiques (succès, erreurs, temps)

## Commandes testées

### Commandes de base

- `show version` - Afficher la version IOS
- `show ip interface brief` - Afficher les interfaces IP
- `show running-config` - Afficher la configuration en cours
- `show clock` - Afficher l'heure système
- `show inventory` - Afficher l'inventaire matériel

### Commandes de configuration

- `configure terminal` - Entrer en mode configuration
- `interface gigabitethernet 0/0` - Configurer une interface
- `ip address 192.168.1.1 255.255.255.0` - Configurer une adresse IP
- `no shutdown` - Activer une interface
- `write memory` - Sauvegarder la configuration

### Commandes réseau

- `ping 8.8.8.8` - Tester la connectivité
- `traceroute 8.8.8.8` - Tracer le chemin réseau
- `show ip route` - Afficher la table de routage

## Tests automatisés

### Tests PHP (Backend)

Exécuter les tests PHP pour vérifier l'accès à l'API console :

```bash
php artisan test --filter ConsoleCommandTest
```

**Prérequis** : Configurer dans `.env` :
- `CML_TOKEN` : Token d'authentification CML
- `TEST_LAB_ID` : ID d'un lab de test
- `TEST_NODE_ID` : ID d'un nœud de test
- `TEST_CONSOLE_ID` : ID d'une console de test (optionnel)

### Tests Frontend (TDD)

Les tests frontend sont intégrés dans le composant `ConsoleCommandTester` :

1. Ouvrir la console d'un lab
2. Faire défiler jusqu'à "Tests TDD - Commandes Console"
3. Cliquer sur "Lancer les tests"
4. Observer les résultats en temps réel

## Vérification des résultats

### Via les logs

Les résultats des commandes sont récupérés via l'API CML :

```
GET /api/labs/{labId}/nodes/{nodeId}/consoles/{consoleId}/log
```

### Via l'interface

1. **Console IOS Intelligent** : Les résultats s'affichent dans le terminal
2. **Mode Standard** : Les résultats s'affichent dans l'iframe
3. **Tests TDD** : Les résultats sont affichés dans le panneau de test

## Dépannage

### Problème : Les commandes ne s'envoient pas

**Solution** :
1. Vérifier que la session console est ouverte
2. Vérifier que le nœud est démarré (`state: STARTED`)
3. Vérifier que le token CML est valide

### Problème : Les logs ne se récupèrent pas

**Solution** :
1. Vérifier que le `consoleId` est correct
2. Vérifier que l'API CML est accessible
3. Vérifier les logs Laravel pour les erreurs API

### Problème : Les tests TDD échouent

**Solution** :
1. Vérifier que la console est prête (session ouverte)
2. Augmenter le délai entre les commandes
3. Vérifier que les commandes sont valides pour le type d'équipement

## Notes importantes

1. **CML n'expose pas d'API pour envoyer des commandes** : Les commandes doivent être tapées dans l'iframe
2. **Polling des logs** : Le système poll les logs toutes les 2 secondes pour récupérer les résultats
3. **Délais** : Les commandes `show` nécessitent plus de temps (2 secondes) que les autres (1 seconde)
4. **Limitation** : Le système ne peut pas "envoyer" directement des commandes, seulement récupérer les résultats après saisie manuelle

## Améliorations futures

- [ ] Support WebSocket si CML l'expose un jour
- [ ] Injection automatique de commandes via l'iframe (si possible)
- [ ] Cache des résultats pour améliorer les performances
- [ ] Support de commandes batch (plusieurs commandes en une fois)


