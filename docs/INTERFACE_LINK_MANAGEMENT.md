# Gestion des Interfaces et Liens - Connexion/Déconnexion

## 📋 Vue d'ensemble

Le système permet maintenant de **connecter et déconnecter** les interfaces et liens des nœuds directement depuis l'interface utilisateur du workspace.

## 🎯 Fonctionnalités

### Interfaces
- ✅ **Sélectionner une interface** depuis la liste des interfaces du nœud
- ✅ **Voir les détails** : Type, État, Adresse MAC
- ✅ **Connecter** une interface (démarrer)
- ✅ **Déconnecter** une interface (arrêter)
- ✅ **Indicateur visuel** : Badge "Connectée" / "Déconnectée"

### Liens
- ✅ **Sélectionner un lien** depuis la liste des liens du lab
- ✅ **Voir les détails** : État, Interfaces connectées
- ✅ **Connecter** un lien (démarrer)
- ✅ **Déconnecter** un lien (arrêter)
- ✅ **Indicateur visuel** : Badge avec l'état du lien

## 🔌 Endpoints API

### Interfaces

#### Connecter une interface
```
PUT /api/labs/{labId}/interfaces/{interfaceId}/connect
```

**Réponse :**
```json
{
  "success": true,
  "message": "Interface connectée avec succès",
  "data": { ... }
}
```

#### Déconnecter une interface
```
PUT /api/labs/{labId}/interfaces/{interfaceId}/disconnect
```

**Réponse :**
```json
{
  "success": true,
  "message": "Interface déconnectée avec succès",
  "data": { ... }
}
```

### Liens

#### Connecter un lien
```
PUT /api/labs/{labId}/links/{linkId}/connect
```

**Réponse :**
```json
{
  "success": true,
  "message": "Lien connecté avec succès",
  "data": { ... }
}
```

#### Déconnecter un lien
```
PUT /api/labs/{labId}/links/{linkId}/disconnect
```

**Réponse :**
```json
{
  "success": true,
  "message": "Lien déconnecté avec succès",
  "data": { ... }
}
```

## 🎨 Interface Utilisateur

### Section Interfaces

1. **Sélectionner un nœud** dans le dropdown
2. **Ouvrir la section "Interfaces"** (collapsible)
3. **Sélectionner une interface** depuis le dropdown
4. **Voir les détails** de l'interface (Type, État, MAC)
5. **Cliquer sur "Connecter" ou "Déconnecter"** selon l'état actuel

### Section Liens

1. **Sélectionner un nœud** dans le dropdown
2. **Ouvrir la section "Liens"** (collapsible)
3. **Sélectionner un lien** depuis le dropdown
4. **Voir les détails** du lien (État, Interfaces)
5. **Cliquer sur "Connecter" ou "Déconnecter"** selon l'état actuel

## 🔄 Rafraîchissement Automatique

Après chaque action de connexion/déconnexion :
- ✅ Les interfaces/liens sont automatiquement rafraîchis après 1 seconde
- ✅ Les badges et états sont mis à jour
- ✅ Un message de succès/erreur est affiché via toast

## 🛠️ Implémentation Technique

### Backend

**Fichiers modifiés :**
- `app/Http/Controllers/Api/NodeController.php` : Ajout des méthodes `connectInterface`, `disconnectInterface`, `connectLink`, `disconnectLink`
- `routes/api.php` : Ajout des routes PUT pour connecter/déconnecter

**Services utilisés :**
- `CiscoApiService->interfaces->startInterface()` : Démarrer une interface
- `CiscoApiService->interfaces->stopInterface()` : Arrêter une interface
- `CiscoApiService->links->startLink()` : Démarrer un lien
- `CiscoApiService->links->stopLink()` : Arrêter un lien

### Frontend

**Fichiers modifiés :**
- `resources/js/hooks/useNodeInterfaces.ts` : Ajout des fonctions `connectInterface`, `disconnectInterface`, `connectLink`, `disconnectLink`
- `resources/js/components/lab-console-panel.tsx` : Ajout des boutons et de la logique UI

**Hook `useNodeInterfaces` :**

```typescript
const {
  interfaces,
  links,
  getNodeInterfaces,
  getNodeLinks,
  connectInterface,
  disconnectInterface,
  connectLink,
  disconnectLink,
  loading,
  error
} = useNodeInterfaces();
```

## 📝 Exemple d'utilisation

### Connecter une interface

```typescript
const success = await connectInterface(labId, interfaceId);
if (success) {
  // Rafraîchir les interfaces
  await getNodeInterfaces(labId, nodeId);
}
```

### Déconnecter un lien

```typescript
const success = await disconnectLink(labId, linkId);
if (success) {
  // Rafraîchir les liens
  await getNodeLinks(labId, nodeId);
}
```

## ⚠️ Notes importantes

1. **Token CML requis** : Toutes les opérations nécessitent un token CML valide
2. **Lab démarré** : Le lab doit être démarré pour que les opérations fonctionnent
3. **État des nœuds** : Les nœuds doivent être dans un état approprié (généralement "STARTED")
4. **Gestion d'erreurs** : Les erreurs sont affichées via toast et loggées dans la console

## 🧪 Tests

Pour tester les fonctionnalités :

1. **Démarrer un lab** avec des nœuds et des liens
2. **Sélectionner un nœud** dans le workspace
3. **Ouvrir la section Interfaces** et sélectionner une interface
4. **Cliquer sur "Connecter"** → Vérifier que l'interface se connecte
5. **Cliquer sur "Déconnecter"** → Vérifier que l'interface se déconnecte
6. **Répéter pour les liens**

## 🔗 Références

- Documentation CML API : `/api/v0/labs/{labId}/interfaces/{interfaceId}/state/start`
- Documentation CML API : `/api/v0/labs/{labId}/links/{linkId}/state/start`


