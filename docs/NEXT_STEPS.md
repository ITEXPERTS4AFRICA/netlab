# Prochaines étapes - Roadmap

## ✅ Complété récemment

1. **Système d'analyse automatique des logs**
   - Extraction d'informations système (kernel, CPU, mémoire)
   - Détection des interfaces réseau
   - Identification des erreurs et avertissements
   - Panneau d'analyse avec onglets (Résumé, Système, Réseau, Erreurs)

2. **Gestion du rate limiting (429)**
   - Backoff exponentiel automatique
   - Arrêt temporaire après erreurs multiples
   - Intervalles de polling optimisés (4-5 secondes)

3. **Console améliorée**
   - Iframe caché pour envoi de commandes
   - Polling intelligent avec cache
   - Gestion des interfaces et liens

## 🚀 Prochaines étapes prioritaires

### 1. Améliorer la gestion des mises à jour de configuration (EN COURS)
**Objectif** : Observer les changements en temps réel après modification de la configuration

**Fonctionnalités à ajouter** :
- [ ] Détection automatique des changements de configuration
- [ ] Mise à jour en temps réel de la topologie après sauvegarde
- [ ] Affichage des différences avant/après modification
- [ ] Validation en temps réel de la syntaxe YAML/JSON
- [ ] Indicateur visuel des changements non sauvegardés
- [ ] Historique des modifications avec possibilité de rollback

**Fichiers à modifier** :
- `resources/js/components/LabConfigEditor.tsx`
- `resources/js/components/LabTopology.tsx`
- `resources/js/hooks/useLabConfig.ts`
- `resources/js/pages/labs/Workspace.tsx`

### 2. Améliorer l'affichage des logs avec coloration syntaxique
**Objectif** : Rendre les logs plus lisibles et faciles à analyser

**Fonctionnalités à ajouter** :
- [ ] Coloration syntaxique par niveau (info, warning, error, success)
- [ ] Coloration syntaxique par catégorie (kernel, network, filesystem)
- [ ] Filtres interactifs (par niveau, catégorie, timestamp)
- [ ] Recherche dans les logs (Ctrl+F)
- [ ] Export des logs (TXT, JSON, CSV)
- [ ] Lignes numérotées pour référence
- [ ] Mode sombre/clair adaptatif

**Fichiers à créer/modifier** :
- `resources/js/components/ConsoleTerminal.tsx` (améliorer)
- `resources/js/utils/logSyntaxHighlighter.ts` (nouveau)
- `resources/js/components/LogFilters.tsx` (nouveau)

### 3. Système de recherche et d'export des logs analysés
**Objectif** : Permettre de rechercher et exporter les informations extraites

**Fonctionnalités à ajouter** :
- [ ] Recherche avancée avec regex
- [ ] Export des informations système (JSON, YAML)
- [ ] Export des erreurs détectées
- [ ] Export de la liste des interfaces réseau
- [ ] Génération de rapports (PDF, HTML)
- [ ] Partage des analyses

**Fichiers à créer** :
- `resources/js/utils/logExporter.ts`
- `resources/js/components/LogSearch.tsx`
- `resources/js/components/LogExportDialog.tsx`

### 4. Tester le système d'analyse de logs avec des logs réels
**Objectif** : Valider que l'extraction fonctionne correctement

**Tests à effectuer** :
- [ ] Tester avec différents types de logs (Linux, IOS, SD-WAN)
- [ ] Valider l'extraction des informations système
- [ ] Vérifier la détection des erreurs
- [ ] Tester avec des logs volumineux (>1000 lignes)
- [ ] Valider les performances

### 5. Améliorations supplémentaires (futures)
- [ ] Comparaison de configurations (diff)
- [ ] Templates de configuration
- [ ] Validation automatique des configurations
- [ ] Suggestions intelligentes basées sur les erreurs
- [ ] Intégration avec Git pour versioning
- [ ] Notifications en temps réel des changements

## 📊 Priorisation

1. **Haute priorité** : Améliorer la gestion des mises à jour de configuration
2. **Moyenne priorité** : Améliorer l'affichage des logs avec coloration
3. **Moyenne priorité** : Système de recherche et d'export
4. **Basse priorité** : Tests et validations

## 🎯 Objectif final

Créer une expérience utilisateur fluide où :
- Les modifications de configuration sont visibles immédiatement
- Les logs sont faciles à lire et analyser
- Les informations importantes sont extraites automatiquement
- L'utilisateur peut rechercher et exporter facilement
