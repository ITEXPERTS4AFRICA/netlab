/**
 * Types utilitaires pour l'API CML
 * 
 * Ce fichier réexporte et simplifie les types générés depuis openapi.json
 * Utilisez ces types dans votre code au lieu d'importer directement depuis cml-api.d.ts
 */

// Import pour utilisation interne (depuis le fichier .d.ts)
import type { paths, components } from './cml-api.d';

// Réexporter les types principaux
export type { paths, components } from './cml-api.d';

// Types utilitaires pour les opérations
export type Operations = paths[keyof paths][keyof paths[keyof paths]];

// Types pour les réponses communes
export type LabResponse = components['schemas']['LabResponse'];
export type InterfaceResponse = components['schemas']['InterfaceResponse'];
export type Topology = components['schemas']['Topology'];
export type AnnotationResponse = 
    | components['schemas']['TextAnnotationResponse']
    | components['schemas']['RectangleAnnotationResponse']
    | components['schemas']['EllipseAnnotationResponse']
    | components['schemas']['LineAnnotationResponse'];

// Types pour les requêtes communes
export type LabCreate = components['schemas']['LabCreate'];
export type AnnotationCreate = 
    | components['schemas']['TextAnnotation']
    | components['schemas']['RectangleAnnotation']
    | components['schemas']['EllipseAnnotation']
    | components['schemas']['LineAnnotation'];

// Helpers pour extraire les types de réponse d'une opération
export type ResponseType<T extends keyof paths, M extends 'get' | 'post' | 'put' | 'patch' | 'delete'> = 
    paths[T][M] extends { responses: { 200: { content: { 'application/json': infer R } } } }
        ? R
        : never;

// Exemples d'utilisation :
// type LabsListResponse = ResponseType<'/labs', 'get'>;
// type LabDetailResponse = ResponseType<'/labs/{lab_id}', 'get'>;

