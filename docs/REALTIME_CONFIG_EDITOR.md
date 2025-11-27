# Documentation : Éditeur de Configuration et Topologie en Temps Réel

## Vue d'ensemble

Ce document décrit les nouvelles fonctionnalités pour :
1. **Éditer la configuration complète du lab** avec logs en temps réel
2. **Topologie animée en temps réel** avec infos au survol

## 1. Éditeur de Configuration Complète

### Composant : `LabConfigEditor`

Le composant `LabConfigEditor` permet d'éditer la configuration complète d'un lab et de voir les résultats en temps réel dans les logs.

#### Fonctionnalités

- **Édition YAML** : Éditer la configuration au format YAML
- **Édition JSON** : Éditer la topologie au format JSON
- **Logs en temps réel** : Voir les événements et résultats des modifications en temps réel
- **Upload/Download** : Charger et télécharger des fichiers de configuration
- **Sauvegarde** : Sauvegarder les modifications et voir les résultats immédiatement

#### Utilisation

```typescript
import LabConfigEditor from '@/components/LabConfigEditor';

<LabConfigEditor labId="lab-uuid" />
```

#### Props

- `labId` (string, requis) : L'ID du lab à éditer
- `className` (string, optionnel) : Classes CSS supplémentaires

#### Hooks utilisés

- `useLabConfig` : Gestion de la configuration (chargement, sauvegarde)
- `useRealtimeLogs` : Logs en temps réel des événements du lab

### Endpoints API

#### `GET /api/labs/{labId}/config`
Récupère la configuration complète du lab (lab, topology, yaml).

#### `PUT /api/labs/{labId}/config`
Met à jour la configuration du lab.

**Body :**
```json
{
  "topology": {...},  // Optionnel : topologie JSON
  "yaml": "..."       // Optionnel : configuration YAML
}
```

## 2. Topologie en Temps Réel

### Composant : `LabTopology` (amélioré)

Le composant `LabTopology` a été amélioré avec :
- **Animation en temps réel** : Mise à jour automatique de l'état des nodes et links
- **Infos au survol** : Tooltip affichant les détails des éléments survolés
- **Mise à jour périodique** : Rafraîchissement automatique des données

#### Nouvelles Props

- `labId` (string, optionnel) : ID du lab pour les mises à jour en temps réel
- `realtimeUpdate` (boolean, défaut: true) : Activer/désactiver les mises à jour en temps réel
- `updateInterval` (number, défaut: 3000) : Intervalle de mise à jour en millisecondes

#### Utilisation

```typescript
<LabTopology
  nodes={nodes}
  links={links}
  topology={topology}
  labId="lab-uuid"
  realtimeUpdate={true}
  updateInterval={3000}
/>
```

#### Fonctionnalités

1. **Mise à jour automatique** :
   - Récupère les détails du lab toutes les 3 secondes (par défaut)
   - Met à jour automatiquement les nodes et links
   - Affiche les changements d'état en temps réel

2. **Infos au survol** :
   - **Nodes** : Affiche le label, type, état
   - **Links** : Affiche les interfaces connectées et l'état du lien
   - Tooltip positionné dynamiquement près du curseur

3. **Animation** :
   - Les changements d'état sont visibles immédiatement
   - Les couleurs des nodes/links changent selon leur état
   - Transitions fluides

## 3. Hooks

### `useLabConfig`

```typescript
const {
  loading,
  error,
  config,
  getLabConfig,
  updateLabConfig,
} = useLabConfig();

// Charger la configuration
await getLabConfig(labId);

// Mettre à jour
await updateLabConfig(labId, topology, yaml);
```

### `useRealtimeLogs`

```typescript
const {
  logs,
  loading,
  error,
  addLog,
  clearLogs,
  refresh,
} = useRealtimeLogs(labId, enabled, interval);

// Les logs sont automatiquement mis à jour
// Ajouter un log manuellement
addLog({
  level: 'info',
  message: 'Message',
  source: 'source',
});
```

## 4. Exemple d'utilisation complète

```typescript
import { useState } from 'react';
import LabConfigEditor from '@/components/LabConfigEditor';
import LabTopology from '@/components/LabTopology';
import { useLabDetails } from '@/hooks/useLabDetails';

function LabWorkspace({ labId }: { labId: string }) {
  const { details, getLabDetails } = useLabDetails();
  const [showConfigEditor, setShowConfigEditor] = useState(false);

  return (
    <div className="flex flex-col h-full">
      {/* Topologie en temps réel */}
      <div className="flex-1">
        <LabTopology
          nodes={details?.nodes || []}
          links={details?.links || []}
          topology={details?.topology}
          labId={labId}
          realtimeUpdate={true}
          updateInterval={3000}
        />
      </div>

      {/* Éditeur de configuration */}
      {showConfigEditor && (
        <div className="h-1/2 border-t">
          <LabConfigEditor labId={labId} />
        </div>
      )}

      <button onClick={() => setShowConfigEditor(!showConfigEditor)}>
        {showConfigEditor ? 'Masquer' : 'Afficher'} l'éditeur
      </button>
    </div>
  );
}
```

## 5. Flux de travail

1. **Charger la configuration** :
   - L'éditeur charge automatiquement la config au montage
   - Les logs commencent à s'afficher en temps réel

2. **Éditer** :
   - Modifier le YAML ou JSON
   - Voir les logs en temps réel dans l'onglet "Logs"

3. **Sauvegarder** :
   - Cliquer sur "Sauvegarder"
   - Les modifications sont envoyées au serveur
   - Les logs montrent le résultat de l'opération
   - La topologie se met à jour automatiquement

4. **Voir les résultats** :
   - Les événements du lab apparaissent dans les logs
   - La topologie se met à jour en temps réel
   - Les changements d'état sont visibles immédiatement

## 6. Notes importantes

1. **Performance** :
   - Les mises à jour en temps réel sont limitées à 3 secondes par défaut
   - Les logs sont limités à 100 entrées
   - Le cache est invalidé après chaque modification

2. **Format** :
   - YAML : Format CML2 standard
   - JSON : Topologie complète avec tous les détails

3. **Erreurs** :
   - Les erreurs de validation sont affichées dans les logs
   - Les erreurs API sont affichées avec des toasts

4. **Sécurité** :
   - Toutes les requêtes nécessitent une authentification
   - Le token CML est vérifié à chaque requête

## 7. Prochaines améliorations

- [ ] Validation YAML/JSON en temps réel
- [ ] Syntax highlighting pour YAML/JSON
- [ ] Diff visuel avant/après modification
- [ ] Historique des modifications
- [ ] Rollback des modifications
- [ ] Export/Import de configurations spécifiques


