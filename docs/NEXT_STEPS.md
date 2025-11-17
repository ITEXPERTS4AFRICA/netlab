# 🚀 Prochaines Étapes - Guide de Démarrage NetLab

## ✅ État Actuel

- ✅ Configuration CML : **OK** (URL: https://54.38.146.213)
- ✅ Base de données : **Connectée**
- ✅ Système de synchronisation : **Fonctionnel**
- ✅ Gestion des labs : **Opérationnelle**
- 📊 Labs synchronisés : **1/57** (à compléter)

---

## 📋 Étapes Immédiates

### 1. Synchroniser tous les labs depuis CML

**Action requise :**
1. Aller sur **http://localhost:8000/admin/labs**
2. Cliquer sur le bouton **"Synchroniser depuis CML"** (en haut à droite)
3. Attendre la fin de la synchronisation (peut prendre quelques minutes pour 57 labs)
4. Vérifier le message de confirmation

**Résultat attendu :**
- Tous les 57 labs seront importés dans PostgreSQL
- Les stats seront mises à jour automatiquement
- Les labs apparaîtront dans la liste

**Note :** La synchronisation :
- S'authentifie automatiquement si nécessaire
- Crée les nouveaux labs
- Met à jour les labs existants (sans écraser les métadonnées personnalisées)
- Gère les erreurs et affiche un rapport détaillé

---

### 2. Configurer les métadonnées des labs

Une fois les labs synchronisés, vous pouvez enrichir chaque lab avec :

**Métadonnées disponibles :**
- **Prix** : Définir le prix en centimes (ex: 5000 = 50 XOF)
- **Description courte** : Pour les listes et aperçus
- **README** : Documentation complète en Markdown
- **Tags** : Pour la recherche et le filtrage
- **Catégories** : Organisation par thème
- **Niveau de difficulté** : beginner, intermediate, advanced, expert
- **Durée estimée** : En minutes
- **Objectifs pédagogiques** : Ce que l'utilisateur va apprendre
- **Prérequis** : Équipements et connaissances nécessaires

**Comment faire :**
1. Aller sur `/admin/labs`
2. Cliquer sur l'icône **✏️ Éditer** d'un lab
3. Remplir les champs souhaités
4. Sauvegarder

---

### 3. Publier et mettre en avant les labs

**Actions disponibles :**
- **Publier** : Rendre le lab visible pour les utilisateurs
- **Mettre en avant** : Afficher le lab en priorité
- **Restreindre** : Limiter l'accès à certains utilisateurs

**Comment faire :**
- Dans la liste des labs (`/admin/labs`), utiliser les boutons :
  - 🌐 **Globe** : Publier/Dépublier
  - ⭐ **Star** : Mettre en avant
  - 🔒 **Lock** : Restreindre l'accès

---

### 4. Ajouter de la documentation multimédia

Pour chaque lab, vous pouvez ajouter :

**Types de médias supportés :**
- **Images** : PNG, JPG, etc. (max 10MB)
- **Vidéos** : MP4, etc. (max 10MB)
- **Documents** : PDF, etc.
- **Liens externes** : Vers des ressources en ligne

**Comment faire :**
1. Aller sur les détails d'un lab (`/admin/labs/{id}`)
2. Section "Documentation"
3. Cliquer sur "Ajouter un média"
4. Choisir le type et uploader/ajouter le contenu
5. Réorganiser l'ordre avec le glisser-déposer

---

### 5. Sauvegarder et restaurer les configurations

**Fonctionnalité Snapshots :**

Vous pouvez sauvegarder la configuration actuelle d'un lab pour la restaurer plus tard :

**Sauvegarder :**
1. Aller sur les détails d'un lab
2. Section "Snapshots"
3. Cliquer sur "Créer un snapshot"
4. Donner un nom (ex: "Configuration initiale")
5. Optionnel : Marquer comme snapshot par défaut

**Restaurer :**
1. Dans la liste des snapshots
2. Cliquer sur "Restaurer" pour un snapshot
3. Confirmer l'action
4. Le lab sera restauré avec la configuration sauvegardée

**Cas d'usage :**
- Avant une modification importante
- Après une configuration de test
- Pour revenir à un état précédent

---

## 🔧 Configuration Avancée

### Configuration CML

**Localisation :** `/admin/cml-config`

**Paramètres :**
- URL du serveur CML (sans `/api` à la fin)
- Nom d'utilisateur
- Mot de passe (crypté dans la base de données)

**Test de connexion :**
- Utiliser le bouton "Tester la connexion"
- Vérifier que le token est reçu
- Vérifier le nombre de labs disponibles

---

### Gestion des utilisateurs

**Localisation :** `/admin/users`

**Fonctionnalités :**
- Lister tous les utilisateurs
- Activer/Désactiver des comptes
- Gérer les rôles et permissions
- Synchroniser avec CML (si configuré)

---

## 📊 Tableau de Bord

### Statistiques disponibles

Sur `/admin/labs`, vous pouvez voir :
- **Total** : Nombre total de labs
- **Publiés** : Labs visibles pour les utilisateurs
- **Mis en avant** : Labs en vedette
- **En attente** : Labs non publiés

### Filtres et recherche

**Filtres disponibles :**
- Recherche par titre, description, CML ID
- Filtre par état (STARTED, STOPPED, etc.)
- Filtre par statut de publication
- Filtre par niveau de difficulté

---

## 🎯 Workflow Recommandé

### Pour un nouveau lab

1. **Synchroniser depuis CML** → Le lab apparaît dans la liste
2. **Éditer les métadonnées** → Ajouter prix, description, tags, etc.
3. **Ajouter la documentation** → Images, vidéos, liens
4. **Sauvegarder un snapshot** → "Configuration initiale"
5. **Publier le lab** → Rendre visible aux utilisateurs
6. **Mettre en avant** (optionnel) → Pour les labs importants

### Pour un lab existant

1. **Vérifier la synchronisation** → S'assurer que les données CML sont à jour
2. **Mettre à jour les métadonnées** → Si nécessaire
3. **Ajouter de nouveaux médias** → Enrichir la documentation
4. **Créer un snapshot** → Avant modifications importantes

---

## 🔍 Vérifications

### Vérifier que tout fonctionne

```bash
# Vérifier les labs dans la base de données
php artisan tinker
> App\Models\Lab::count()

# Vérifier la configuration CML
> \App\Helpers\CmlConfigHelper::isConfigured()

# Tester la connexion CML
> $service = new \App\Services\Cisco\LabService();
> $credentials = \App\Helpers\CmlConfigHelper::getCredentials();
> // ... (voir test précédent)
```

### Logs et débogage

**Fichiers de logs :**
- `storage/logs/laravel.log` : Logs généraux
- Rechercher "synchronisation", "CML", "lab" pour les erreurs

**En cas d'erreur :**
1. Vérifier les logs Laravel
2. Vérifier la configuration CML
3. Tester la connexion manuellement
4. Vérifier que le token CML est valide

---

## 🚨 Problèmes Courants

### Les stats sont à 0

**Cause :** Aucun lab synchronisé
**Solution :** Cliquer sur "Synchroniser depuis CML"

### Erreur d'authentification CML

**Cause :** Credentials incorrects ou token expiré
**Solution :** 
1. Aller sur `/admin/cml-config`
2. Vérifier/mettre à jour les credentials
3. Tester la connexion
4. Réessayer la synchronisation

### Labs non synchronisés

**Cause :** Erreur lors de la récupération des détails
**Solution :**
1. Vérifier les logs (`storage/logs/laravel.log`)
2. Vérifier que l'URL CML est correcte
3. Vérifier que le token est valide
4. Réessayer la synchronisation

---

## 📝 Checklist de Démarrage

- [ ] Configuration CML complète (`/admin/cml-config`)
- [ ] Test de connexion CML réussi
- [ ] Synchronisation de tous les labs (57 labs)
- [ ] Vérification des stats (Total = 57)
- [ ] Configuration des métadonnées pour au moins un lab
- [ ] Ajout de documentation multimédia
- [ ] Publication d'au moins un lab
- [ ] Création d'un snapshot de test
- [ ] Test de restauration d'un snapshot

---

## 🎓 Ressources

### Routes Admin

- `/admin` : Tableau de bord
- `/admin/labs` : Gestion des labs
- `/admin/labs/{id}` : Détails d'un lab
- `/admin/labs/{id}/edit` : Édition d'un lab
- `/admin/cml-config` : Configuration CML
- `/admin/users` : Gestion des utilisateurs

### API Endpoints

- `POST /admin/labs/sync-from-cml` : Synchroniser les labs
- `PATCH /admin/labs/{id}/toggle-published` : Publier/Dépublier
- `PATCH /admin/labs/{id}/toggle-featured` : Mettre en avant
- `POST /admin/labs/{id}/snapshots` : Créer un snapshot
- `POST /admin/labs/{id}/snapshots/{snapshot}/restore` : Restaurer

---

## 🎉 Prêt à Démarrer !

Tout est en place. Commencez par synchroniser vos labs depuis CML, puis enrichissez-les avec les métadonnées et la documentation.

**Bonne chance ! 🚀**

