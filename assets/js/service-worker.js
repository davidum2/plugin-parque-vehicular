/**
 * Service Worker para GPV PWA
 */

// Versión de caché
const CACHE_VERSION = 'gpv-pwa-v1';

// Recursos para cachear en la instalación
const CACHE_ASSETS = [
  '/',
  '/wp-content/plugins/gpv-pwa/assets/css/frontend.css',
  '/wp-content/plugins/gpv-pwa/assets/css/dashboard.css',
  '/wp-content/plugins/gpv-pwa/assets/js/frontend-app.js',
  '/wp-content/plugins/gpv-pwa/assets/js/dashboard.js',
  '/wp-content/plugins/gpv-pwa/assets/images/logo.png',
  'https://unpkg.com/react@17/umd/react.production.min.js',
  'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js',
  'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
];

// Páginas principales del sitio para cachear
const CACHE_PAGES = [
  '/gpv-dashboard/',
  '/gpv-driver-panel/',
  '/gpv-consultant-panel/',
];

// Evento de instalación del Service Worker
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Instalando...');

  // Cachear archivos estáticos
  event.waitUntil(
    caches
      .open(CACHE_VERSION)
      .then((cache) => {
        console.log('[Service Worker] Precacheando archivos');
        return cache.addAll([...CACHE_ASSETS, ...CACHE_PAGES]);
      })
      .then(() => {
        console.log('[Service Worker] Instalación completada');
        return self.skipWaiting(); // Forzar activación inmediata
      })
  );
});

// Evento de activación del Service Worker
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Activando...');

  // Limpiar cachés antiguas
  event.waitUntil(
    caches
      .keys()
      .then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== CACHE_VERSION) {
              console.log(
                '[Service Worker] Eliminando caché antigua:',
                cacheName
              );
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log(
          '[Service Worker] Ahora está activo y controlando la página'
        );
        return self.clients.claim(); // Tomar control de clientes no controlados
      })
  );
});

// Evento de fetch (interceptar solicitudes de red)
self.addEventListener('fetch', (event) => {
  // No interceptar solicitudes a otros dominios
  if (
    !event.request.url.includes(self.location.origin) &&
    !event.request.url.includes('unpkg.com') &&
    !event.request.url.includes('cdn.jsdelivr.net')
  ) {
    return;
  }

  // No interceptar solicitudes no GET
  if (event.request.method !== 'GET') {
    // Para solicitudes POST en modo offline, encolarlas para sincronizar después
    if (
      !navigator.onLine &&
      (event.request.url.includes('/wp-json/gpv/v1/movements') ||
        event.request.url.includes('/wp-json/gpv/v1/fuels') ||
        event.request.url.includes('/wp-json/gpv/v1/sync'))
    ) {
      event.respondWith(handleOfflinePost(event));
    }

    return;
  }

  // Solicitudes a la API
  if (event.request.url.includes('/wp-json/gpv/v1/')) {
    event.respondWith(
      fetch(event.request)
        .then((response) => {
          // Si la respuesta está OK, clonarla y cachearla
          if (response && response.status === 200) {
            const clonedResponse = response.clone();
            caches.open(CACHE_VERSION).then((cache) => {
              cache.put(event.request, clonedResponse);
            });
          }
          return response;
        })
        .catch((error) => {
          console.log(
            '[Service Worker] Error en fetch de API, usando caché',
            error
          );
          return caches.match(event.request);
        })
    );
    return;
  }

  // Estrategia para recursos estáticos y páginas: Stale While Revalidate
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      // Devolver respuesta en caché mientras se actualiza en segundo plano
      const fetchPromise = fetch(event.request)
        .then((networkResponse) => {
          // Actualizar la caché con la nueva respuesta
          if (networkResponse && networkResponse.status === 200) {
            const clonedResponse = networkResponse.clone();
            caches.open(CACHE_VERSION).then((cache) => {
              cache.put(event.request, clonedResponse);
            });
          }
          return networkResponse;
        })
        .catch((error) => {
          console.log('[Service Worker] Error en fetch de recurso:', error);
          // No hacer nada si la red falla, ya que usaremos la caché
        });

      // Devolver la respuesta en caché o la de red (lo que llegue primero)
      return cachedResponse || fetchPromise;
    })
  );
});

// Manejar solicitudes POST en modo offline
async function handleOfflinePost(event) {
  try {
    // Clonar la solicitud para poder leerla
    const requestClone = event.request.clone();
    const requestData = await requestClone.json();

    // Guardar la solicitud en IndexedDB
    await saveOfflineRequest({
      url: event.request.url,
      method: event.request.method,
      headers: Array.from(event.request.headers.entries()),
      body: requestData,
      timestamp: Date.now(),
    });

    // Registrar evento de sincronización para cuando vuelva la conexión
    if ('sync' in self.registration) {
      await self.registration.sync.register('gpv-sync');
    }

    // Responder con una confirmación
    return new Response(
      JSON.stringify({
        status: 'offline',
        message: 'La operación se completará cuando se restablezca la conexión',
        offline_id: Date.now(),
      }),
      {
        headers: {
          'Content-Type': 'application/json',
        },
      }
    );
  } catch (error) {
    console.error('[Service Worker] Error manejando solicitud offline:', error);

    // Responder con error
    return new Response(
      JSON.stringify({
        status: 'error',
        message: 'No se pudo procesar la solicitud offline',
      }),
      {
        status: 503,
        headers: {
          'Content-Type': 'application/json',
        },
      }
    );
  }
}

// Guardar solicitud offline en IndexedDB
function saveOfflineRequest(data) {
  return new Promise((resolve, reject) => {
    const request = self.indexedDB.open('gpv-offline', 1);

    request.onerror = (event) => reject(event.target.error);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('offline_queue')) {
        db.createObjectStore('offline_queue', { keyPath: 'timestamp' });
      }
    };

    request.onsuccess = (event) => {
      const db = event.target.result;
      const tx = db.transaction('offline_queue', 'readwrite');
      const store = tx.objectStore('offline_queue');

      const storeRequest = store.add(data);
      storeRequest.onsuccess = () => resolve(true);
      storeRequest.onerror = (event) => reject(event.target.error);
    };
  });
}

// Evento de sincronización en background
self.addEventListener('sync', (event) => {
  if (event.tag === 'gpv-sync') {
    console.log('[Service Worker] Sincronizando datos offline...');
    event.waitUntil(syncOfflineData());
  }
});

// Sincronizar datos offline
async function syncOfflineData() {
  try {
    // Obtener solicitudes pendientes
    const offlineRequests = await getOfflineRequests();

    if (offlineRequests.length === 0) {
      console.log(
        '[Service Worker] No hay solicitudes offline para sincronizar'
      );
      return;
    }

    console.log(
      '[Service Worker] Sincronizando',
      offlineRequests.length,
      'solicitudes'
    );

    // Procesarlas una por una
    const results = await Promise.all(
      offlineRequests.map(async (request) => {
        try {
          // Recrear la solicitud
          const response = await fetch(request.url, {
            method: request.method,
            headers: new Headers(Object.fromEntries(request.headers)),
            body: JSON.stringify(request.body),
            credentials: 'same-origin',
          });

          if (response.ok) {
            // Eliminar de la cola si se sincronizó correctamente
            await removeOfflineRequest(request.timestamp);
            return { success: true, timestamp: request.timestamp };
          } else {
            console.error(
              '[Service Worker] Error sincronizando solicitud:',
              await response.text()
            );
            return { success: false, timestamp: request.timestamp };
          }
        } catch (error) {
          console.error('[Service Worker] Error procesando solicitud:', error);
          return { success: false, timestamp: request.timestamp };
        }
      })
    );

    // Notificar al usuario si la sincronización fue exitosa
    const successCount = results.filter((r) => r.success).length;
    if (successCount > 0) {
      await showSyncNotification(successCount, offlineRequests.length);
    }

    return true;
  } catch (error) {
    console.error('[Service Worker] Error en sincronización:', error);
    return false;
  }
}

// Obtener solicitudes offline de IndexedDB
function getOfflineRequests() {
  return new Promise((resolve, reject) => {
    const request = self.indexedDB.open('gpv-offline', 1);

    request.onerror = (event) => reject(event.target.error);

    request.onsuccess = (event) => {
      const db = event.target.result;
      if (!db.objectStoreNames.contains('offline_queue')) {
        resolve([]);
        return;
      }

      const tx = db.transaction('offline_queue', 'readonly');
      const store = tx.objectStore('offline_queue');
      const getRequest = store.getAll();

      getRequest.onsuccess = () => resolve(getRequest.result);
      getRequest.onerror = (event) => reject(event.target.error);
    };
  });
}

// Eliminar solicitud offline de IndexedDB
function removeOfflineRequest(timestamp) {
  return new Promise((resolve, reject) => {
    const request = self.indexedDB.open('gpv-offline', 1);

    request.onerror = (event) => reject(event.target.error);

    request.onsuccess = (event) => {
      const db = event.target.result;
      const tx = db.transaction('offline_queue', 'readwrite');
      const store = tx.objectStore('offline_queue');

      const deleteRequest = store.delete(timestamp);
      deleteRequest.onsuccess = () => resolve(true);
      deleteRequest.onerror = (event) => reject(event.target.error);
    };
  });
}

// Mostrar notificación de sincronización
async function showSyncNotification(successCount, totalCount) {
  if (!('Notification' in self)) return;

  try {
    // Solicitar permiso si es necesario
    if (Notification.permission !== 'granted') return;

    // Mostrar notificación
    return self.registration.showNotification('Sincronización Completada', {
      body: `Se sincronizaron ${successCount} de ${totalCount} operaciones pendientes.`,
      icon: '/wp-content/plugins/gpv-pwa/assets/images/icon-192x192.png',
      badge: '/wp-content/plugins/gpv-pwa/assets/images/badge-72x72.png',
      vibrate: [100, 50, 100],
      data: {
        dateOfArrival: Date.now(),
        primaryKey: 1,
      },
      actions: [
        {
          action: 'close',
          title: 'Cerrar',
        },
      ],
    });
  } catch (error) {
    console.error('[Service Worker] Error mostrando notificación:', error);
  }
}

// Evento de notificación click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  // Abrir la ventana principal al hacer clic en la notificación
  if (event.action !== 'close') {
    event.waitUntil(
      clients.matchAll({ type: 'window' }).then((clientList) => {
        // Si ya hay una ventana abierta, enfocarla
        for (const client of clientList) {
          if (client.url.includes('/gpv-') && 'focus' in client) {
            return client.focus();
          }
        }

        // Si no hay ventanas abiertas, abrir una nueva
        if (clients.openWindow) {
          return clients.openWindow('/gpv-dashboard/');
        }
      })
    );
  }
});
