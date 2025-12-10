# Guide d'Installation - GDS NURYASS

## 🔧 Vérification de l'Installation

### 1. Vérifier que XAMPP est démarré

- Ouvrez le **Panneau de contrôle XAMPP**
- Vérifiez que **Apache** et **MySQL** sont démarrés (boutons verts)

### 2. Vérifier l'emplacement du projet

Le projet doit être dans : `C:\xampp\htdocs\GDS-NURYASS\`

### 3. Tester l'accès

1. **Test PHP simple** :
   ```
   http://localhost/GDS-NURYASS/test.php
   ```
   Si vous voyez une page avec "Test PHP - GDS NURYASS", PHP fonctionne.

2. **Page d'accueil** :
   ```
   http://localhost/GDS-NURYASS/
   ```
   ou
   ```
   http://localhost/GDS-NURYASS/index.php
   ```
   Cela devrait vous rediriger vers `login.php` si vous n'êtes pas connecté.

3. **Page de connexion** :
   ```
   http://localhost/GDS-NURYASS/login.php
   ```

4. **Dashboard** (après connexion) :
   ```
   http://localhost/GDS-NURYASS/dashboard.php
   ```

## ⚠️ Si vous obtenez "Not Found" (404)

### Solution 1 : Vérifier le chemin
Assurez-vous que l'URL est exactement :
```
http://localhost/GDS-NURYASS/nom_du_fichier.php
```

**ATTENTION** : Le nom du dossier doit être exactement `GDS-NURYASS` (avec les majuscules et tirets).

### Solution 2 : Vérifier que le dossier existe
1. Ouvrez l'explorateur Windows
2. Allez dans `C:\xampp\htdocs\`
3. Vérifiez que le dossier `GDS-NURYASS` existe

### Solution 3 : Vérifier Apache
1. Ouvrez le Panneau de contrôle XAMPP
2. Cliquez sur "Config" à côté d'Apache
3. Sélectionnez "httpd.conf"
4. Vérifiez que cette ligne existe et n'est pas commentée :
   ```
   DocumentRoot "C:/xampp/htdocs"
   ```
5. Redémarrez Apache

### Solution 4 : Tester avec un fichier simple
Créez un fichier `info.php` dans `C:\xampp\htdocs\GDS-NURYASS\` avec :
```php
<?php phpinfo(); ?>
```
Puis accédez à : `http://localhost/GDS-NURYASS/info.php`

## 📋 URLs Correctes

| Page | URL |
|------|-----|
| Accueil | `http://localhost/GDS-NURYASS/` |
| Login | `http://localhost/GDS-NURYASS/login.php` |
| Dashboard | `http://localhost/GDS-NURYASS/dashboard.php` |
| Clients | `http://localhost/GDS-NURYASS/clients/index.php` |
| Produits | `http://localhost/GDS-NURYASS/produits/index.php` |
| Installation | `http://localhost/GDS-NURYASS/install.php` |
| Créer Admin | `http://localhost/GDS-NURYASS/create_admin.php` |

## 🔍 Diagnostic

Si rien ne fonctionne :

1. **Testez PHP** :
   ```
   http://localhost/GDS-NURYASS/test.php
   ```

2. **Vérifiez les erreurs** :
   - Ouvrez la console du navigateur (F12)
   - Regardez l'onglet "Console" et "Network"

3. **Vérifiez les logs Apache** :
   - `C:\xampp\apache\logs\error.log`

4. **Vérifiez les logs PHP** :
   - `C:\xampp\php\logs\php_error_log`

## ✅ Checklist

- [ ] XAMPP est installé
- [ ] Apache est démarré (vert dans XAMPP)
- [ ] MySQL est démarré (vert dans XAMPP)
- [ ] Le dossier `GDS-NURYASS` existe dans `C:\xampp\htdocs\`
- [ ] Tous les fichiers sont présents
- [ ] La base de données `gds_nuryass` est créée
- [ ] L'utilisateur admin existe dans la base de données

## 🆘 Support

Si le problème persiste, vérifiez :
1. Le port 80 n'est pas utilisé par un autre programme
2. Le pare-feu Windows n'bloque pas Apache
3. Les permissions du dossier sont correctes

