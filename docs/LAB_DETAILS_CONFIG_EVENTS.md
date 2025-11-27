# Documentation : Détails Labs, Configurations et Événements

## Vue d'ensemble

Ce document décrit les nouvelles fonctionnalités ajoutées pour gérer les détails complets des labs, les configurations des nodes, et les événements/logs.

## Endpoints API

### Lab Details (`LabDetailsController`)

#### `GET /api/labs/{labId}/details`
Récupère tous les détails d'un lab :
- Informations du lab
- Topologie
- État
- Événements
- Nodes
- Links
- Interfaces
- Annotations
- Statistiques de simulation
- Adresses Layer 3

**Réponse :**
```json
{
  "lab": {...},
  "topology": {...},
  "state": {...},
  "events": [...],
  "nodes": [...],
  "links": [...],
  "interfaces": [...],
  "annotations": [...],
  "simulation_stats": {...},
  "layer3_addresses": {...}
}
```

#### `GET /api/labs/{labId}/simulation-stats`
Récupère les statistiques de simulation du lab.

#### `GET /api/labs/{labId}/layer3-addresses`
Récupère toutes les adresses Layer 3 du lab.

### Lab Events (`LabEventsController`)

#### `GET /api/labs/{labId}/events`
Récupère tous les événements d'un lab.

**Paramètres de requête :**
- `type` : Filtrer par type d'événement
- `limit` : Limiter le nombre de résultats

**Réponse :**
```json
{
  "events": [...],
  "count": 10
}
```

#### `GET /api/labs/{labId}/nodes/{nodeId}/events`
Récupère les événements d'un node spécifique.

**Paramètres de requête :**
- `limit` : Limiter le nombre de résultats

#### `GET /api/labs/{labId}/interfaces/{interfaceId}/events`
Récupère les événements d'une interface spécifique.

**Paramètres de requête :**
- `limit` : Limiter le nombre de résultats

### Configuration Management (`ConfigController`)

#### `GET /api/labs/{labId}/nodes/{nodeId}/config`
Récupère la configuration d'un node.

**Réponse :**
```json
{
  "success": true,
  "configuration": "hostname Router1\n...",
  "node_id": "...",
  "lab_id": "..."
}
```

#### `POST /api/labs/{labId}/nodes/{nodeId}/config/upload`
Upload une configuration pour un node.

**Body :**
```json
{
  "configuration": "hostname Router1\n...",
  "name": "startup-config" // Optionnel
}
```

#### `PUT /api/labs/{labId}/nodes/{nodeId}/config/extract`
Extrait la configuration actuelle d'un node depuis le device.

#### `GET /api/labs/{labId}/nodes/{nodeId}/config/export`
Exporte la configuration d'un node (téléchargement de fichier).

#### `GET /api/labs/{labId}/export`
Exporte le lab complet au format YAML (téléchargement de fichier).

## Hooks React

### `useLabDetails`

```typescript
const {
  loading,
  error,
  details,
  getLabDetails,
  getSimulationStats,
  getLayer3Addresses,
} = useLabDetails();

// Récupérer tous les détails
await getLabDetails(labId);

// Récupérer les stats
await getSimulationStats(labId);

// Récupérer les adresses Layer 3
await getLayer3Addresses(labId);
```

### `useLabEvents`

```typescript
const {
  loading,
  error,
  events,
  getLabEvents,
  getNodeEvents,
  getInterfaceEvents,
} = useLabEvents();

// Récupérer les événements du lab
await getLabEvents(labId, { type: 'node_started', limit: 50 });

// Récupérer les événements d'un node
await getNodeEvents(labId, nodeId, 20);

// Récupérer les événements d'une interface
await getInterfaceEvents(labId, interfaceId, 20);
```

### `useNodeConfig`

```typescript
const {
  loading,
  error,
  getNodeConfig,
  uploadNodeConfig,
  extractNodeConfig,
  exportNodeConfig,
  exportLab,
} = useNodeConfig();

// Récupérer la configuration
const config = await getNodeConfig(labId, nodeId);

// Uploader une configuration
await uploadNodeConfig(labId, nodeId, configString, 'startup-config');

// Extraire la configuration depuis le device
await extractNodeConfig(labId, nodeId);

// Exporter la configuration (téléchargement)
await exportNodeConfig(labId, nodeId);

// Exporter le lab complet (téléchargement)
await exportLab(labId);
```

## Exemples d'utilisation

### Afficher les détails complets d'un lab

```typescript
import { useLabDetails } from '@/hooks/useLabDetails';

function LabDetailsPanel({ labId }: { labId: string }) {
  const { loading, details, getLabDetails } = useLabDetails();

  useEffect(() => {
    void getLabDetails(labId);
  }, [labId]);

  if (loading) return <div>Chargement...</div>;
  if (!details) return <div>Aucun détail disponible</div>;

  return (
    <div>
      <h2>Lab: {details.lab?.title}</h2>
      <p>État: {details.state?.state}</p>
      <p>Nodes: {details.nodes?.length}</p>
      <p>Links: {details.links?.length}</p>
      <p>Événements: {details.events?.length}</p>
    </div>
  );
}
```

### Gérer les configurations

```typescript
import { useNodeConfig } from '@/hooks/useNodeConfig';

function NodeConfigPanel({ labId, nodeId }: { labId: string; nodeId: string }) {
  const { loading, getNodeConfig, uploadNodeConfig, extractNodeConfig, exportNodeConfig } = useNodeConfig();
  const [config, setConfig] = useState('');

  useEffect(() => {
    getNodeConfig(labId, nodeId).then(setConfig);
  }, [labId, nodeId]);

  const handleUpload = async () => {
    await uploadNodeConfig(labId, nodeId, config);
  };

  const handleExtract = async () => {
    await extractNodeConfig(labId, nodeId);
    // Recharger la config après extraction
    const newConfig = await getNodeConfig(labId, nodeId);
    setConfig(newConfig || '');
  };

  return (
    <div>
      <textarea value={config} onChange={(e) => setConfig(e.target.value)} />
      <button onClick={handleUpload}>Upload</button>
      <button onClick={handleExtract}>Extraire depuis device</button>
      <button onClick={() => exportNodeConfig(labId, nodeId)}>Exporter</button>
    </div>
  );
}
```

### Afficher les événements

```typescript
import { useLabEvents } from '@/hooks/useLabEvents';

function LabEventsPanel({ labId }: { labId: string }) {
  const { loading, events, getLabEvents } = useLabEvents();

  useEffect(() => {
    void getLabEvents(labId, { limit: 50 });
  }, [labId]);

  return (
    <div>
      <h3>Événements du lab</h3>
      {events.map((event, index) => (
        <div key={index}>
          <p>{event.timestamp} - {event.type}: {event.message}</p>
        </div>
      ))}
    </div>
  );
}
```

## Notes importantes

1. **Cache** : Les endpoints utilisent un cache pour optimiser les performances. Le cache est invalidé automatiquement lors des modifications.

2. **Authentification** : Tous les endpoints nécessitent une authentification et un token CML valide.

3. **Limites** :
   - Configuration max : 20MB
   - Les événements peuvent être filtrés et limités

4. **Format des configurations** :
   - Peut être une string simple
   - Ou un tableau de fichiers avec `name` et `content`

5. **Export** : Les exports génèrent des fichiers téléchargeables automatiquement.

## Prochaines étapes

- [ ] Créer des composants UI complets pour afficher les détails
- [ ] Ajouter un éditeur de configuration avec syntax highlighting
- [ ] Créer une timeline visuelle pour les événements
- [ ] Ajouter des filtres avancés pour les événements
- [ ] Implémenter l'upload de fichiers de configuration


