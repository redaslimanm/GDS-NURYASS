/**
 * Service Worker pour GDS NURYASS PWA
 * Gère le cache et le fonctionnement hors ligne
 */

const CACHE_NAME = 'gds-nuryass-v1';
const RUNTIME_CACHE = 'gds-nuryass-runtime-v1';

// Fichiers statiques à mettre en cache lors de l'installation
const STATIC_CACHE_URLS = [
  './',
  './login.php',
  './dashboard.php',
  './manifest.json',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css',
  'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css',
  'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'
];

// Stratégie de cache : Network First pour les pages dynamiques
const NETWORK_FIRST_PATTERNS = [
  /\/clients\//,
  /\/produits\//,
  /\/bons\//,
  /\/factures\//,
  /\/credits\//,
  /\/users\//,
  /\/operations\//,
  /\/historique\//
];

// Stratégie de cache : Cache First pour les ressources statiques
const CACHE_FIRST_PATTERNS = [
  /\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/,
  /\.(?:css|js|woff|woff2|ttf|eot)$/,
  /cdn\.jsdelivr\.net/
];

/**
 * Installation du Service Worker
 */
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Caching static assets');
        return cache.addAll(STATIC_CACHE_URLS.filter(url => {
          // Ne pas mettre en cache les URLs externes qui peuvent échouer
          return !url.startsWith('http') || url.includes('cdn.jsdelivr.net');
        }));
      })
      .then(() => {
        console.log('[Service Worker] Installed successfully');
        return self.skipWaiting(); // Activer immédiatement le nouveau service worker
      })
      .catch((error) => {
        console.error('[Service Worker] Installation failed:', error);
      })
  );
});

/**
 * Activation du Service Worker
 */
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activating...');
  
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            // Supprimer les anciens caches
            if (cacheName !== CACHE_NAME && cacheName !== RUNTIME_CACHE) {
              console.log('[Service Worker] Deleting old cache:', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('[Service Worker] Activated');
        return self.clients.claim(); // Prendre le contrôle immédiatement
      })
  );
});

/**
 * Interception des requêtes
 */
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET
  if (request.method !== 'GET') {
    return;
  }

  // Ignorer les requêtes vers l'API PHP (toujours aller au réseau)
  if (url.pathname.includes('.php') && !url.pathname.includes('index.php') && !url.pathname.includes('dashboard.php') && !url.pathname.includes('login.php')) {
    return;
  }

  event.respondWith(handleRequest(request));
});

/**
 * Gestion des requêtes avec stratégie appropriée
 */
async function handleRequest(request) {
  const url = new URL(request.url);

  // Stratégie Cache First pour les ressources statiques
  if (CACHE_FIRST_PATTERNS.some(pattern => pattern.test(url.pathname) || pattern.test(url.href))) {
    return cacheFirst(request);
  }

  // Stratégie Network First pour les pages dynamiques
  if (NETWORK_FIRST_PATTERNS.some(pattern => pattern.test(url.pathname))) {
    return networkFirst(request);
  }

  // Stratégie Network First par défaut
  return networkFirst(request);
}

/**
 * Stratégie Cache First : vérifie le cache d'abord, puis le réseau
 */
async function cacheFirst(request) {
  const cache = await caches.open(CACHE_NAME);
  const cached = await cache.match(request);

  if (cached) {
    return cached;
  }

  try {
    const response = await fetch(request);
    
    // Mettre en cache seulement les réponses valides
    if (response.status === 200) {
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.error('[Service Worker] Fetch failed:', error);
    
    // Retourner une page hors ligne si disponible
    if (request.mode === 'navigate') {
      return caches.match('./offline.html') || 
             new Response('Hors ligne - Veuillez vérifier votre connexion', {
               status: 503,
               headers: { 'Content-Type': 'text/html' }
             });
    }
    
    throw error;
  }
}

/**
 * Stratégie Network First : essaie le réseau d'abord, puis le cache
 */
async function networkFirst(request) {
  const cache = await caches.open(RUNTIME_CACHE);

  try {
    const response = await fetch(request);
    
    // Mettre en cache les réponses valides
    if (response.status === 200) {
      cache.put(request, response.clone());
    }
    
    return response;
  } catch (error) {
    console.log('[Service Worker] Network failed, trying cache:', error);
    
    const cached = await cache.match(request);
    
    if (cached) {
      return cached;
    }
    
    // Si c'est une navigation et qu'on est hors ligne
    if (request.mode === 'navigate') {
      return caches.match('./offline.html') || 
             new Response('Hors ligne - Veuillez vérifier votre connexion', {
               status: 503,
               headers: { 'Content-Type': 'text/html' }
             });
    }
    
    throw error;
  }
}

/**
 * Gestion des messages depuis le client
 */
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  
  if (event.data && event.data.type === 'CACHE_URLS') {
    event.waitUntil(
      caches.open(CACHE_NAME).then((cache) => {
        return cache.addAll(event.data.urls);
      })
    );
  }
});

