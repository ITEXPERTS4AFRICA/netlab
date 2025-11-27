# Documentation API Console CML 2.9.x - Commandes CLI IOS

## Résumé Exécutif

**Conclusion importante** : CML 2.9.x **n'expose PAS d'API REST** pour envoyer des commandes CLI directement aux équipements réseau. Les commandes doivent être tapées dans la console web (iframe) et les résultats sont récupérés via le polling des logs.

## Endpoints Console Disponibles

### 1. Obtenir la Clé Console

**Endpoint** : `GET /api/v0/labs/{lab_id}/nodes/{node_id}/keys/console`

**Description** : Retourne la clé console pour accéder à la console web d'un node.

**Réponse** :
```json
{
  "console_key": "uuid-de-la-console",
  // ou simplement une string UUID
}
```

**Utilisation** : Cette clé permet d'accéder à la console web via l'URL :
```
{base_url}/console/{console_key}
```

**Test** : ✅ Opérationnel

---

### 2. Obtenir les Logs de la Console

**Endpoint** : `GET /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log`

**Description** : Retourne le log de la console (résultats des commandes tapées).

**Réponse** :
```json
{
  "log": [
    "ligne 1 du log",
    "ligne 2 du log",
    "..."
  ]
  // ou parfois une string avec des \n
}
```

**Utilisation** : C'est le **SEUL moyen** de récupérer les résultats des commandes CLI. Les commandes doivent être tapées dans l'iframe de la console, puis les résultats sont récupérés via cet endpoint.

**Test** : ✅ Opérationnel

---

### 3. Extraire la Configuration

**Endpoint** : `PUT /api/v0/labs/{lab_id}/nodes/{node_id}/extract_configuration`

**Description** : Extrait la configuration actuelle du node (running-config).

**Limitation** : Ne permet **PAS** d'exécuter des commandes arbitraires. C'est une commande prédéfinie pour extraire la configuration.

**Test** : ✅ Opérationnel (mais pas pour exécuter des commandes CLI)

---

## Endpoints qui N'EXISTENT PAS

❌ `POST /api/v0/labs/{lab_id}/nodes/{node_id}/execute_command`  
❌ `POST /api/v0/labs/{lab_id}/nodes/{node_id}/send_command`  
❌ `POST /api/v0/labs/{lab_id}/nodes/{node_id}/run_cli`  
❌ `POST /api/v0/console/session/{session_id}/command`  
❌ `PUT /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/command`

**Conclusion** : Aucun endpoint POST/PUT n'existe pour envoyer des commandes CLI via l'API REST.

---

## Méthode Recommandée (selon doc CML 2.9.x)

### Flux Complet pour Envoyer une Commande CLI

1. **Obtenir la clé console**
   ```
   GET /api/v0/labs/{lab_id}/nodes/{node_id}/keys/console
   → Retourne: { "console_key": "uuid" }
   ```

2. **Accéder à la console web**
   ```
   URL: {base_url}/console/{console_key}
   → Ouvre un iframe avec la console du node
   ```

3. **Taper la commande dans l'iframe**
   - L'utilisateur tape la commande directement dans l'iframe
   - Exemple: `show version`, `configure terminal`, etc.

4. **Récupérer les résultats via polling**
   ```
   GET /api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log
   → Retourne les logs (résultats des commandes)
   ```

### Notre Implémentation

✅ **Conforme à la documentation CML 2.9.x**

- ✅ Utilisation du polling intelligent des logs
- ✅ Les commandes sont tapées via l'interface IOS (pas d'API directe)
- ✅ Les résultats sont récupérés via `GET /consoles/{console_id}/log`
- ✅ Le polling se fait toutes les 2 secondes
- ✅ Aucune référence à CML visible pour les étudiants

---

## Tests Effectués

### Test 1: Endpoint GET /keys/console
- ✅ **Status** : Opérationnel
- ✅ **Réponse** : Retourne la clé console correctement
- ✅ **Structure** : `{ "console_key": "uuid" }` ou string UUID

### Test 2: Endpoint GET /consoles/{console_id}/log
- ✅ **Status** : Opérationnel
- ✅ **Réponse** : Retourne les logs de la console
- ✅ **Format** : Array de strings ou string avec `\n`

### Test 3: Recherche d'endpoint POST pour commandes
- ❌ **Résultat** : Aucun endpoint POST trouvé
- ✅ **Confirmation** : CML n'expose pas d'API pour envoyer des commandes

---

## Exemple de Requête JSON

### Obtenir la Clé Console

```bash
curl -X GET \
  "https://cml.example.com/api/v0/labs/{lab_id}/nodes/{node_id}/keys/console" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Réponse** :
```json
{
  "console_key": "a1b2c3d4-e5f6-7890-abcd-ef1234567890"
}
```

### Récupérer les Logs

```bash
curl -X GET \
  "https://cml.example.com/api/v0/labs/{lab_id}/nodes/{node_id}/consoles/{console_id}/log" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

**Réponse** :
```json
{
  "log": [
    "Router>",
    "Router>show version",
    "Cisco IOS Software, C2960 Software (C2960-LANBASEK9-M), Version 15.0(2)SE7",
    "...",
    "Router>"
  ]
}
```

---

## Limitations et Contraintes

1. **Pas d'API REST pour commandes** : Les commandes doivent être tapées dans l'iframe
2. **Polling nécessaire** : Les résultats ne sont pas en temps réel, nécessite un polling
3. **Console ID requis** : Il faut connaître le `console_id` pour récupérer les logs
4. **Pas de WebSocket** : CML n'utilise pas de WebSocket pour les commandes

---

## Recommandations

### ✅ Ce qui fonctionne bien

1. **Polling intelligent** : Récupération automatique des logs toutes les 2 secondes
2. **Interface IOS** : Console intelligente avec auto-complétion
3. **Masquage de CML** : Aucune référence visible pour les étudiants

### 🔄 Améliorations possibles

1. **Optimisation du polling** : Réduire l'intervalle si nécessaire (actuellement 2s)
2. **Cache des logs** : Éviter de récupérer les mêmes lignes plusieurs fois
3. **Détection de nouvelles commandes** : Identifier automatiquement les nouvelles lignes dans les logs

---

## Conclusion

✅ **Tous les endpoints console sont opérationnels**  
✅ **Notre implémentation est conforme à la documentation CML 2.9.x**  
✅ **Aucun endpoint manquant** - CML n'expose simplement pas d'API pour envoyer des commandes  
✅ **Le polling intelligent est la méthode correcte** pour récupérer les résultats

**La console est prête pour la production !** 🎉


