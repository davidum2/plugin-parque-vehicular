<?php
/**
 * Clase para gestionar la funcionalidad PWA
 */
class GPV_PWA {

    /**
     * Constructor
     */
    public function __construct() {
        // Agregar manifest.json
        add_action('wp_head', array($this, 'add_manifest'));

        // Registrar service worker
        add_action('wp_footer', array($this, 'register_service_worker'));

        // Agregar meta tags para PWA
        add_action('wp_head', array($this, 'add_pwa_meta_tags'));

        // Soporte para Apple PWA
        add_action('wp_head', array($this, 'add_apple_pwa_support'));

        // Verificar si hay un script de desinstalación
        add_action('wp_head', array($this, 'check_uninstall_script'));
    }

    /**
     * Añadir enlace al manifest.json
     */
    public function add_manifest() {
        echo '<link rel="manifest" href="' . GPV_PLUGIN_URL . 'manifest.json">';

        // Generar manifest dinámicamente si no existe
        if (!file_exists(GPV_PLUGIN_DIR . 'manifest.json')) {
            $this->generate_manifest();
        }
    }

    /**
     * Generar manifest.json dinámicamente
     */
    private function generate_manifest() {
        // Obtener colores de tema
        $primary_color = '#4285F4'; // Por defecto

        // Crear contenido del manifest
        $manifest = array(
            'name' => __('Gestión de Parque Vehicular', 'gpv-pwa'),
            'short_name' => __('GPV', 'gpv-pwa'),
            'description' => __('Sistema de gestión de parque vehicular', 'gpv-pwa'),
            'start_url' => home_url('/gpv-dashboard/'),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => $primary_color,
            'orientation' => 'portrait-primary',
            'icons' => array(
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png'
                ),
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png'
                ),
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-128x128.png',
                    'sizes' => '128x128',
                    'type' => 'image/png'
                ),
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png'
                ),
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-152x152.png',
                    'sizes' => '152x152',
                    'type' => 'image/png'
                ),
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ),
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-384x384.png',
                    'sizes' => '384x384',
                    'type' => 'image/png'
                ),
                array(
                    'src' => GPV_PLUGIN_URL . 'assets/images/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                )
            )
        );

        // Guardar manifest.json
        file_put_contents(
            GPV_PLUGIN_DIR . 'manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Registrar service worker
     */
    public function register_service_worker() {
        if (!is_user_logged_in()) {
            return;
        }

        // Asegurarse de que el service worker existe
        if (!file_exists(GPV_PLUGIN_DIR . 'assets/js/service-worker.js')) {
            $this->generate_service_worker();
        }

        // Script para registrar el service worker
        ?>
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('<?php echo GPV_PLUGIN_URL; ?>assets/js/service-worker.js')
                        .then(function(registration) {
                            console.log('GPV Service Worker registrado con éxito:', registration.scope);
                        })
                        .catch(function(error) {
                            console.log('Error al registrar GPV Service Worker:', error);
                        });
                });
            }
        </script>
        <?php
    }

    /**
     * Generar service worker
     */
    private function generate_service_worker() {
        // Versión de caché
        $cache_version = 'gpv-v1';

        // Archivos a cachear
        $files_to_cache = array(
            '/',
            '/gpv-dashboard/',
            '/gpv-driver-panel/',
            GPV_PLUGIN_URL . 'assets/css/frontend.css',
            GPV_PLUGIN_URL . 'assets/js/frontend-app.js',
            GPV_PLUGIN_URL . 'assets/images/logo.png',
            // Iconos
            GPV_PLUGIN_URL . 'assets/images/icon-72x72.png',
            GPV_PLUGIN_URL . 'assets/images/icon-96x96.png',
            GPV_PLUGIN_URL . 'assets/images/icon-128x128.png',
            GPV_PLUGIN_URL . 'assets/images/icon-144x144.png',
            GPV_PLUGIN_URL . 'assets/images/icon-152x152.png',
            GPV_PLUGIN_URL . 'assets/images/icon-192x192.png',
            GPV_PLUGIN_URL . 'assets/images/icon-384x384.png',
            GPV_PLUGIN_URL . 'assets/images/icon-512x512.png'
        );

        // Contenido del service worker
        $service_worker_content = "
            const CACHE_NAME = '$cache_version';
            const urlsToCache = " . json_encode($files_to_cache) . ";

            // Instalación del Service Worker
            self.addEventListener('install', event => {
                event.waitUntil(
                    caches.open(CACHE_NAME)
                        .then(cache => {
                            return cache.addAll(urlsToCache);
                        })
                );
            });

            // Activación del Service Worker
            self.addEventListener('activate', event => {
                event.waitUntil(
                    caches.keys().then(cacheNames => {
                        return Promise.all(
                            cacheNames.map(cacheName => {
                                if (cacheName !== CACHE_NAME) {
                                    return caches.delete(cacheName);
                                }
                            })
                        );
                    })
                );
            });

            // Estrategia de caché: Network First, fallback to cache
            self.addEventListener('fetch', event => {
                // Solo interceptar peticiones GET
                if (event.request.method !== 'GET') return;

                // Solo interceptar peticiones al mismo origen o a nuestros assets
                const url = new URL(event.request.url);
                if (url.origin !== self.location.origin && !event.request.url.includes('" . GPV_PLUGIN_URL . "')) {
                    return;
                }

                // Especial manejo para API REST
                if (event.request.url.includes('/wp-json/gpv/v1/')) {
                    return handleApiRequest(event);
                }

                // Manejo estándar para otros recursos
                event.respondWith(
                    fetch(event.request)
                        .then(response => {
                            // Clonar la respuesta para guardarla en caché
                            let responseToCache = response.clone();

                            caches.open(CACHE_NAME)
                                .then(cache => {
                                    cache.put(event.request, responseToCache);
                                });

                            return response;
                        })
                        .catch(() => {
                            return caches.match(event.request);
                        })
                );
            });

            // Manejo especial para solicitudes a la API
            function handleApiRequest(event) {
                event.respondWith(
                    fetch(event.request)
                        .then(response => {
                            return response;
                        })
                        .catch(() => {
                            // Si la solicitud falla, verificar si es una solicitud que se puede encolar
                            if (event.request.method === 'POST') {
                                // Encolar solicitud para sincronización posterior
                                return saveRequestForSync(event.request.clone())
                                    .then(() => {
                                        return new Response(JSON.stringify({
                                            status: 'queued',
                                            message: 'Request queued for sync'
                                        }), {
                                            headers: { 'Content-Type': 'application/json' }
                                        });
                                    });
                            }

                            // Para solicitudes GET, intentar recuperar de caché
                            return caches.match(event.request);
                        })
                );
            }

            // Guardar solicitud para sincronización posterior
            async function saveRequestForSync(request) {
                try {
                    const db = await openDatabase();
                    const tx = db.transaction('offline_queue', 'readwrite');
                    const store = tx.objectStore('offline_queue');

                    const requestData = {
                        url: request.url,
                        method: request.method,
                        headers: Array.from(request.headers.entries()),
                        body: await request.text(),
                        timestamp: Date.now()
                    };

                    await store.add(requestData);

                    // Registrar para sincronización en background
                    if ('sync' in registration) {
                        await registration.sync.register('gpv-sync');
                    }

                    return true;
                } catch (error) {
                    console.error('Error guardando solicitud para sincronización:', error);
                    return false;
                }
            }

            // Abrir la base de datos IndexedDB
            function openDatabase() {
                return new Promise((resolve, reject) => {
                    const request = indexedDB.open('gpv-offline', 1);

                    request.onupgradeneeded = event => {
                        const db = event.target.result;
                        if (!db.objectStoreNames.contains('offline_queue')) {
                            db.createObjectStore('offline_queue', { keyPath: 'timestamp' });
                        }
                    };

                    request.onsuccess = event => resolve(event.target.result);
                    request.onerror = event => reject(event.target.error);
                });
            }

            // Sincronización en background
            self.addEventListener('sync', event => {
                if (event.tag === 'gpv-sync') {
                    event.waitUntil(syncOfflineData());
                }
            });

            // Sincronizar datos offline
            async function syncOfflineData() {
                try {
                    const db = await openDatabase();
                    const tx = db.transaction('offline_queue', 'readonly');
                    const store = tx.objectStore('offline_queue');
                    const requests = await store.getAll();

                    if (requests.length === 0) return;

                    // Procesar cada solicitud
                    const results = await Promise.all(requests.map(async requestData => {
                        try {
                            const response = await fetch(requestData.url, {
                                method: requestData.method,
                                headers: new Headers(Object.fromEntries(requestData.headers)),
                                body: requestData.method !== 'GET' ? requestData.body : undefined,
                                credentials: 'same-origin'
                            });

                            if (response.ok) {
                                return { success: true, timestamp: requestData.timestamp };
                            } else {
                                return { success: false, timestamp: requestData.timestamp };
                            }
                        } catch (error) {
                            return { success: false, timestamp: requestData.timestamp };
                        }
                    }));

                    // Eliminar solicitudes sincronizadas con éxito
                    const successTimestamps = results
                        .filter(result => result.success)
                        .map(result => result.timestamp);

                    if (successTimestamps.length > 0) {
                        const deleteTx = db.transaction('offline_queue', 'readwrite');
                        const deleteStore = deleteTx.objectStore('offline_queue');

                        await Promise.all(successTimestamps.map(timestamp => {
                            return new Promise((resolve, reject) => {
                                const request = deleteStore.delete(timestamp);
                                request.onsuccess = () => resolve();
                                request.onerror = () => reject();
                            });
                        }));
                    }

                    return true;
                } catch (error) {
                    console.error('Error sincronizando datos offline:', error);
                    return false;
                }
            }
        ";

        // Guardar service worker
        file_put_contents(
            GPV_PLUGIN_DIR . 'assets/js/service-worker.js',
            $service_worker_content
        );
    }

    /**
     * Agregar meta tags para PWA
     */
    public function add_pwa_meta_tags() {
        // Color del tema
        echo '<meta name="theme-color" content="#4285F4">';

        // Viewport
        echo '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';

        // Description
        echo '<meta name="description" content="' . esc_attr__('Sistema de gestión de parque vehicular', 'gpv-pwa') . '">';
    }

    /**
     * Agregar soporte para PWA en dispositivos Apple
     */
    public function add_apple_pwa_support() {
        // Apple Touch Icon
        echo '<link rel="apple-touch-icon" href="' . GPV_PLUGIN_URL . 'assets/images/icon-192x192.png">';

        // Apple status bar
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';

        // Apple web app capable
        echo '<meta name="apple-mobile-web-app-capable" content="yes">';

        // Apple web app title
        echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr__('GPV', 'gpv-pwa') . '">';

        // Splash screens para diferentes dispositivos Apple
        $splash_screens = array(
            array('2048x2732', 'iPad Pro 12.9"'),
            array('1668x2224', 'iPad Pro 10.5"'),
            array('1536x2048', 'iPad Mini, iPad Air'),
            array('1125x2436', 'iPhone X/XS'),
            array('1242x2688', 'iPhone XS Max'),
            array('828x1792', 'iPhone XR'),
            array('1242x2208', 'iPhone 8 Plus'),
            array('750x1334', 'iPhone 8, iPhone 7, iPhone 6s, iPhone 6'),
            array('1125x2436', 'iPhone X')
        );

        foreach ($splash_screens as $screen) {
            list($size, $device) = $screen;
            list($width, $height) = explode('x', $size);

            echo '<link rel="apple-touch-startup-image" href="' . GPV_PLUGIN_URL . 'assets/images/splash-' . $size . '.png" media="(device-width: ' . $width . 'px) and (device-height: ' . $height . 'px) and (-webkit-device-pixel-ratio: 3)">';
        }
    }

    /**
     * Verificar si hay script de desinstalación
     */
    public function check_uninstall_script() {
        $uninstall_script = get_option('gpv_unregister_sw');

        if ($uninstall_script) {
            echo '<script>' . $uninstall_script . '</script>';
            delete_option('gpv_unregister_sw');
        }
    }
}
