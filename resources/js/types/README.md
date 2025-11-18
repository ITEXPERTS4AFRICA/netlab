# Types TypeScript pour l'API CML

Ce répertoire contient les types TypeScript générés automatiquement à partir de la spécification OpenAPI de l'API CML 2.9.

## Fichiers

- **`cml-api.d.ts`** : Types générés automatiquement depuis `app/Services/openapi.json`
  - ⚠️ **Ne pas modifier manuellement** - Ce fichier est régénéré automatiquement
- **`cml-api.ts`** : Types utilitaires et helpers pour faciliter l'utilisation
- **`annotation.ts`** : Types pour les annotations de lab
- **`index.d.ts`** : Types partagés de l'application

## Génération des types

Pour régénérer les types après une mise à jour de `openapi.json` :

```bash
npm run generate:api-types
```

## Utilisation

### Import des types de base

```typescript
import type { paths, components } from '@/types/cml-api';
```

### Utilisation des types utilitaires

```typescript
import type { 
  LabResponse, 
  InterfaceResponse, 
  Topology,
  AnnotationResponse,
  ResponseType 
} from '@/types/cml-api';

// Exemple : Type de réponse pour la liste des labs
type LabsListResponse = ResponseType<'/labs', 'get'>;

// Exemple : Type de réponse pour un lab spécifique
type LabDetailResponse = ResponseType<'/labs/{lab_id}', 'get'>;
```

### Exemple complet

```typescript
import type { LabResponse, ResponseType } from '@/types/cml-api';

// Utiliser le type de réponse d'une opération spécifique
type GetLabResponse = ResponseType<'/labs/{lab_id}', 'get'>;

async function fetchLab(labId: string): Promise<GetLabResponse> {
  const response = await fetch(`/api/v0/labs/${labId}`);
  return response.json();
}

// Utiliser les types de schémas directement
function processLab(lab: LabResponse) {
  console.log(lab.id, lab.title, lab.state);
}
```

## Types disponibles

### Types de réponse
- `LabResponse` : Réponse pour un lab
- `InterfaceResponse` : Réponse pour une interface
- `Topology` : Topologie d'un lab
- `AnnotationResponse` : Union type pour toutes les annotations

### Types de requête
- `LabCreate` : Données pour créer un lab
- `AnnotationCreate` : Union type pour créer une annotation

### Helpers
- `ResponseType<Path, Method>` : Extrait le type de réponse d'une opération
- `Operations` : Type union de toutes les opérations

## Notes

- Les types sont générés depuis la spécification OpenAPI officielle de CML 2.9
- Régénérez les types après chaque mise à jour de `openapi.json`
- Utilisez les types utilitaires dans `cml-api.ts` plutôt que d'importer directement depuis `cml-api.d.ts`



