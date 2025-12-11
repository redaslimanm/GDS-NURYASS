# Guide d'Installation PWA - GDS NURYASS

Ce guide vous explique comment convertir votre application web GDS NURYASS en Progressive Web App (PWA) installable comme application desktop.

## 📋 Prérequis

- PHP avec extension GD (pour générer les icônes)
- Navigateur moderne supportant PWA (Chrome, Edge, Firefox, Safari)
- Serveur web (XAMPP, Apache, etc.)

## 🚀 Installation

### 1. Générer les icônes PWA

**Option A : Via le navigateur (Recommandé)**
1. Accédez à : `http://localhost/GDS-NURYASS/generate-icons-web.php`
2. Cliquez sur "Générer toutes les icônes"
3. Les icônes seront créées automatiquement

**Option B : Via la ligne de commande**
Si PHP est dans votre PATH, exécutez :
```bash
php generate-icons.php
```

Ou avec le chemin complet XAMPP :
```powershell
C:\xampp\php\php.exe generate-icons.php
```

Ce script va :
- Créer toutes les tailles d'icônes nécessaires (72x72 à 512x512)
- Les placer dans le dossier `images/`
- Utiliser votre logo existant ou créer une icône par défaut

**Note :** Si vous avez déjà des icônes, assurez-vous qu'elles sont nommées :
- `icon-72x72.png`
- `icon-96x96.png`
- `icon-128x128.png`
- `icon-144x144.png`
- `icon-152x152.png`
- `icon-192x192.png`
- `icon-384x384.png`
- `icon-512x512.png`

### 2. Vérifier les fichiers PWA

Assurez-vous que ces fichiers existent :
- ✅ `manifest.json` - Configuration de l'application
- ✅ `service-worker.js` - Service worker pour le cache
- ✅ `offline.html` - Page affichée hors ligne
- ✅ `images/icon-*.png` - Toutes les icônes

### 3. Accéder via HTTPS (Recommandé)

Les PWA fonctionnent mieux avec HTTPS. Pour le développement local :

**Option A : Utiliser localhost (fonctionne sans HTTPS)**
- Accédez à `http://localhost/GDS-NURYASS/`

**Option B : Configurer HTTPS local**
- Utilisez un outil comme `mkcert` pour créer un certificat SSL local
- Configurez Apache pour utiliser HTTPS

## 📱 Installation de l'Application

### Sur Chrome/Edge (Desktop)

1. Ouvrez l'application dans Chrome/Edge
2. Cliquez sur l'icône d'installation dans la barre d'adresse (ou utilisez le bouton "Installer l'application" dans le menu)
3. Confirmez l'installation
4. L'application s'ouvrira dans une fenêtre séparée

### Sur Firefox (Desktop)

1. Ouvrez l'application dans Firefox
2. Cliquez sur le menu (☰) > "Installer"
3. Confirmez l'installation
4. L'application sera accessible depuis le menu Applications

### Sur Safari (macOS)

1. Ouvrez l'application dans Safari
2. Cliquez sur "Partager" > "Ajouter à l'écran d'accueil"
3. L'application sera disponible dans Launchpad

## 🔧 Configuration

### Modifier le manifest.json

Si vous devez modifier les paramètres de l'application, éditez `manifest.json` :

```json
{
  "name": "GDS NURYASS - Gestion de Stock",
  "short_name": "GDS NURYASS",
  "start_url": "/GDS-NURYASS/",
  "scope": "/GDS-NURYASS/",
  "display": "standalone"
}
```

**Options de display :**
- `standalone` - Application en plein écran (recommandé)
- `fullscreen` - Mode plein écran sans barre d'adresse
- `minimal-ui` - Interface minimale
- `browser` - Comme un navigateur normal

### Personnaliser le Service Worker

Le fichier `service-worker.js` gère :
- Le cache des ressources statiques
- Le fonctionnement hors ligne
- Les stratégies de mise en cache

Pour mettre à jour le cache, modifiez `CACHE_NAME` dans `service-worker.js`.

## 🧪 Tester la PWA

### Outils de développement

1. **Chrome DevTools**
   - F12 > Application > Service Workers
   - Vérifiez que le service worker est actif
   - Testez le mode hors ligne

2. **Lighthouse**
   - F12 > Lighthouse > PWA
   - Vérifiez le score PWA

### Vérifications

- ✅ Le manifest.json est chargé
- ✅ Le service worker est enregistré
- ✅ Les icônes sont accessibles
- ✅ L'application fonctionne hors ligne (pages mises en cache)

## 🐛 Dépannage

### Le bouton d'installation n'apparaît pas

**Causes possibles :**
1. L'application est déjà installée
2. Le manifest.json n'est pas valide
3. L'application n'est pas servie en HTTPS (ou localhost)
4. Le service worker n'est pas enregistré

**Solutions :**
- Vérifiez la console du navigateur (F12)
- Vérifiez que `manifest.json` est accessible
- Vérifiez que `service-worker.js` est enregistré

### Le service worker ne se charge pas

**Vérifications :**
1. Le fichier `service-worker.js` existe
2. Le chemin dans `footer.php` est correct
3. Le serveur permet l'accès au fichier
4. Aucune erreur dans la console

### Les icônes ne s'affichent pas

**Vérifications :**
1. Les fichiers d'icônes existent dans `images/`
2. Les chemins dans `manifest.json` sont corrects
3. Les permissions de fichiers sont correctes (755)

## 📝 Notes importantes

1. **Cache du navigateur** : Après une mise à jour, videz le cache ou faites un hard refresh (Ctrl+Shift+R)

2. **Mise à jour du Service Worker** : Modifiez `CACHE_NAME` dans `service-worker.js` pour forcer une mise à jour

3. **HTTPS requis en production** : Les PWA nécessitent HTTPS en production (sauf localhost)

4. **Compatibilité** : 
   - Chrome/Edge : Support complet
   - Firefox : Support partiel (installation limitée)
   - Safari : Support sur iOS/macOS uniquement

## 🎯 Fonctionnalités PWA

Une fois installée, votre application bénéficie de :

- ✅ Installation comme application desktop
- ✅ Fonctionnement hors ligne (pages mises en cache)
- ✅ Icône sur le bureau/écran d'accueil
- ✅ Lancement en mode standalone
- ✅ Mise à jour automatique via service worker
- ✅ Raccourcis vers les sections principales

## 📞 Support

Pour toute question ou problème, consultez :
- [MDN Web Docs - PWA](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Web.dev - PWA](https://web.dev/progressive-web-apps/)

