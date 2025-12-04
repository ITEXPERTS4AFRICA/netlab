# Activer les extensions PHP nécessaires

## Pour Windows

1. **Ouvrir le fichier php.ini** :
   ```
   C:\php\php.ini
   ```

2. **Rechercher et décommenter (enlever le `;` devant) ces lignes** :
   ```ini
   ;extension=fileinfo
   ;extension=pdo_sqlite
   ```
   
   Deviennent :
   ```ini
   extension=fileinfo
   extension=pdo_sqlite
   ```

3. **Sauvegarder le fichier**

4. **Redémarrer votre terminal PowerShell**

5. **Vérifier que les extensions sont chargées** :
   ```powershell
   php -m | Select-String -Pattern "fileinfo|pdo_sqlite"
   ```

Vous devriez voir :
```
fileinfo
pdo_sqlite
```

## Note

Si les extensions ne sont pas disponibles dans votre installation PHP, vous devrez peut-être les télécharger ou réinstaller PHP avec ces extensions incluses.

