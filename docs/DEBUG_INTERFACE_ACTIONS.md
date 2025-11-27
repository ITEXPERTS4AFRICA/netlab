# 🔍 Diagnostic des Actions sur les Interfaces

## ✅ Améliorations Apportées

1. **Logs détaillés dans le backend** : Toutes les tentatives de connexion/déconnexion sont maintenant loggées
2. **Logs détaillés dans le frontend** : Console du navigateur avec messages clairs
3. **Gestion d'erreurs améliorée** : Messages d'erreur plus explicites
4. **Header X-Requested-With** : Ajouté pour les requêtes AJAX

## 🔍 Comment Diagnostiquer

### 1. Ouvrir la Console du Navigateur (F12)

Quand vous cliquez sur "Connecter" ou "Déconnecter", vous devriez voir :

```
🖱️ Clic sur bouton interface: { interfaceId: "...", iface: {...}, is_connected: true/false }
🔌 Tentative de connexion d'interface: { labId: "...", interfaceId: "..." }
📡 Réponse reçue: { status: 200, ok: true }
✅ Succès: { success: true, message: "..." }
```

OU en cas d'erreur :

```
❌ Erreur API: { error: "...", status: 500 }
❌ Erreur connectInterface: Error: ...
```

### 2. Vérifier les Logs Laravel

```bash
tail -f storage/logs/laravel.log
```

Vous devriez voir :

```
[INFO] Tentative de connexion d'interface: { lab_id: "...", interface_id: "..." }
[INFO] Résultat de connexion d'interface: { ... }
```

### 3. Vérifier l'Onglet Network (F12)

1. Ouvrir l'onglet **Network** dans les DevTools
2. Cliquer sur "Connecter" ou "Déconnecter"
3. Chercher la requête : `PUT /api/labs/{labId}/interfaces/{interfaceId}/connect`
4. Vérifier :
   - **Status Code** : 200 (succès) ou autre (erreur)
   - **Response** : Contenu de la réponse
   - **Request Headers** : Vérifier que les headers sont corrects

### 4. Problèmes Courants

#### Problème 1 : Erreur 404 (Not Found)
**Cause** : L'ID de l'interface n'est pas correct ou la route n'existe pas
**Solution** : 
- Vérifier que l'ID de l'interface dans la console correspond à celui dans la requête
- Vérifier les routes : `php artisan route:list | grep interface`

#### Problème 2 : Erreur 401 (Unauthorized)
**Cause** : Token CML expiré ou manquant
**Solution** :
- Se reconnecter à l'application
- Vérifier que le token CML est présent dans la session

#### Problème 3 : Erreur 500 (Internal Server Error)
**Cause** : Erreur côté serveur (API CML, format de données, etc.)
**Solution** :
- Vérifier les logs Laravel pour l'erreur exacte
- Vérifier que le lab est démarré
- Vérifier que l'interface existe dans CML

#### Problème 4 : L'action semble fonctionner mais rien ne change
**Cause** : Le rafraîchissement des interfaces ne fonctionne pas
**Solution** :
- Vérifier que `getNodeInterfaces` est bien appelé après l'action
- Augmenter le délai de rafraîchissement (actuellement 1500ms)

## 🧪 Test Manuel

### Test 1 : Vérifier que les interfaces sont récupérées

```javascript
// Dans la console du navigateur
fetch('/api/labs/{labId}/nodes/{nodeId}/interfaces')
  .then(r => r.json())
  .then(console.log)
```

### Test 2 : Tester la connexion d'interface directement

```javascript
// Dans la console du navigateur
fetch('/api/labs/{labId}/interfaces/{interfaceId}/connect', {
  method: 'PUT',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  },
  credentials: 'same-origin'
})
  .then(r => r.json())
  .then(console.log)
```

## 📝 Informations à Fournir en Cas de Problème

Si les actions ne fonctionnent toujours pas, fournir :

1. **Console du navigateur** : Copier tous les messages (🖱️, 🔌, 📡, ✅, ❌)
2. **Onglet Network** : 
   - URL complète de la requête
   - Status code
   - Response body
   - Request headers
3. **Logs Laravel** : Les dernières lignes concernant l'interface
4. **ID de l'interface** : L'ID exact utilisé
5. **État du lab** : Le lab est-il démarré ?

## 🔧 Corrections Appliquées

- ✅ Ajout de logs détaillés backend et frontend
- ✅ Amélioration de la gestion d'erreurs
- ✅ Ajout du header `X-Requested-With`
- ✅ Délai de rafraîchissement augmenté à 1500ms
- ✅ Messages d'erreur plus explicites


