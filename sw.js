const CACHE = 'alizquiz-v1';
const ASSETS = [
    '/AlizQuiz/accueil.php',
    '/AlizQuiz/assets/css/style.css',
    '/AlizQuiz/assets/js/script.js',
    '/AlizQuiz/assets/icons/icon-192.png',
    '/AlizQuiz/assets/icons/icon-512.png',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(cache => cache.addAll(ASSETS)).catch(() => {})
    );
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', e => {
    // Réseau d'abord, cache en fallback
    e.respondWith(
        fetch(e.request).catch(() => caches.match(e.request))
    );
});
