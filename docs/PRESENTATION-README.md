# Présentation NetLab - Slidev

Présentation moderne et interactive du projet NetLab créée avec [Slidev](https://sli.dev/).

## 🚀 Utilisation rapide

### 1. Installation des dépendances

```bash
# Installation de Slidev et des thèmes
npm install -g @slidev/cli
# ou avec le package local
npm install
```

### 2. Démarrage du mode développement

```bash
# Mode développement avec rechargement automatique
npm run dev
# ou directement avec slidev
slidev presentation.md
```

La présentation sera disponible sur **http://localhost:3030**

### 3. Build pour production

```bash
# Build statique pour déploiement
npm run build
```

### 4. Export PDF/PNG

```bash
# Export en PDF
npm run export
```

## 🎨 Fonctionnalités de la présentation

### 📊 Contenu inclus

- **Vue d'ensemble** du projet NetLab
- **Architecture technique** (Laravel + React/TypeScript)
- **Fonctionnalités principales** détaillées
- **Intégration Cisco CML** complète
- **État d'avancement** actuel (75-80% complet)
- **Technologies utilisées** et stack technique
- **Interface utilisateur** et design
- **Fonctionnalités avancées** (annotations, analytics)
- **Roadmap** et prochaines étapes
- **Guide d'installation** et déploiement
- **Section démonstration** avec cas d'usage

### ✨ Fonctionnalités Slidev

- **Animations fluides** entre les slides
- **Code highlighting** avec thème personnalisé
- **Grille responsive** pour les comparaisons
- **Images et diagrammes** intégrés
- **Navigation interactive** avec la souris/touch
- **Mode présentateur** avec notes
- **Export PDF** haute qualité
- **Thème moderne** et professionnel

## 🎯 Navigation

### Contrôles de présentation

| Touche/Action | Fonction |
|---------------|----------|
| `→` / Espace / Clic | Slide suivante |
| `←` | Slide précédente |
| `F` | Plein écran |
| `P` | Mode présentateur |
| `O` | Vue d'ensemble |
| `1-9` | Aller au slide numéro |

### Mode présentateur

- Appuyez sur `P` pour activer le mode présentateur
- Affiche les notes et le slide actuel
- Aperçu du slide suivant
- Timer intégré

## 📱 Responsive Design

La présentation est optimisée pour :
- **Ordinateurs de bureau** (résolution optimale)
- **Tablettes** (navigation tactile)
- **Projecteurs** (contraste élevé)
- **Impression** (format PDF)

## 🎨 Personnalisation

### Thèmes disponibles

```bash
# Utiliser le thème par défaut
slidev presentation.md

# Thème Seriph (recommandé pour les présentations)
slidev presentation.md --theme seriph
```

### Couleurs et styles

Les couleurs peuvent être personnalisées dans le frontmatter :

```markdown
---
theme: seriph
background: '#1e293b'
color: '#f8fafc'
---
```

## 🚀 Déploiement

### Options de déploiement

1. **Serveur local** (recommandé pour développement)
   ```bash
   npm run dev
   ```

2. **Build statique** (pour production)
   ```bash
   npm run build
   # Fichiers générés dans dist/
   ```

3. **GitHub Pages / Netlify**
   ```bash
   npm run build
   # Déployer le dossier dist/
   ```

### Export PDF

```bash
# Export haute qualité pour impression
slidev export presentation.md --output netlab-presentation.pdf
```

## 📋 Checklist présentation

### Préparation avant présentation

- [ ] Tester la présentation sur l'écran de destination
- [ ] Vérifier les animations et transitions
- [ ] Préparer les notes du présentateur
- [ ] Avoir une sauvegarde PDF
- [ ] Tester les contrôles à distance

### Contenu à personnaliser

- [ ] Ajouter des captures d'écran réelles
- [ ] Mettre à jour les métriques du projet
- [ ] Personnaliser les couleurs de l'entreprise
- [ ] Ajouter le logo de l'entreprise
- [ ] Inclure des démos en direct

## 🔧 Dépannage

### Problèmes courants

**La présentation ne se lance pas :**
```bash
# Réinstaller les dépendances
npm install
# Vérifier que Slidev est installé globalement
npm install -g @slidev/cli
```

**Problèmes d'affichage :**
- Vérifier la résolution de l'écran
- Tester avec un autre navigateur
- Régler le zoom à 100%

**Animations lentes :**
- Désactiver les animations du navigateur
- Fermer les autres applications
- Utiliser le mode plein écran

## 📞 Support

Pour toute question ou personnalisation :

- **Documentation Slidev** : https://sli.dev/
- **Guide officiel** : https://sli.dev/guide/
- **Thèmes disponibles** : https://sli.dev/themes/

---

**Créée avec ❤️ pour présenter le projet NetLab**
*Dernière mise à jour : Octobre 2025*
