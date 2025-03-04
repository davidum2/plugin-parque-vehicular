<?php

/**
 * Plugin Name: Gestión de Parque Vehicular PWA
 * Description: Sistema completo para gestionar flota vehicular con funcionalidad PWA, roles específicos y dashboard en tiempo real.
 * Version: 2.1.0
 * Author: Desarrollo Digital
 * Text Domain: gestion-parque-vehicular
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definiciones globales
define('GPV_VERSION', '2.1.0');
define('GPV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GPV_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GPV_PLUGIN_FILE', __FILE__);
if (!defined('GPV_PLUGIN_DIR')) {
    define('GPV_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
// Clase principal del plugin
class GPV_Plugin
{
    /**
     * Instancia única de la clase (Singleton)
     */
    private static $instance = null;

    /**
     * Base de datos del plugin
     */
    public $database = null;

    /**
     * Constructor
     */
    private function __construct()
    {
        // Carga de dependencias
        $this->load_dependencies();

        // Hooks de inicialización
        add_action('plugins_loaded', array($this, 'init_plugin'));


        // Hooks para activación/desactivación
        register_activation_hook(GPV_PLUGIN_FILE, array($this, 'activate_plugin'));
        register_deactivation_hook(GPV_PLUGIN_FILE, array($this, 'deactivate_plugin'));
    }

    /**
     * Obtener instancia única (Singleton)
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Carga de dependencias
     */
    private function load_dependencies()
    {
        // Core y DB
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-database.php';
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-roles.php';

        // Modelos
        require_once GPV_PLUGIN_DIR . 'includes/models/class-gpv-vehicle.php';
        require_once GPV_PLUGIN_DIR . 'includes/models/class-gpv-movement.php';
        require_once GPV_PLUGIN_DIR . 'includes/models/class-gpv-fuel.php';


        // Frontend
        require_once GPV_PLUGIN_DIR . 'frontend/class-gpv-frontend.php';
        require_once GPV_PLUGIN_DIR . 'frontend/class-gpv-forms.php';

        // API REST
        require_once GPV_PLUGIN_DIR . 'includes/api/class-gpv-api.php';

        // PWA
        require_once GPV_PLUGIN_DIR . 'includes/pwa/class-gpv-pwa.php';

        // Shortcodes
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-shortcodes.php';
        require_once GPV_PLUGIN_DIR . 'frontend/form-carga-de-combustible.php';

        // Dashboard
        require_once GPV_PLUGIN_DIR . 'includes/dashboard/class-gpv-driver-dashboard.php';

        // Nuevos componentes para dashboard de conductor
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-quick-actions.php';
        require_once GPV_PLUGIN_DIR . 'includes/api/class-gpv-driver-dashboard-api.php';

        // Admin
        require_once GPV_PLUGIN_DIR . 'admin/class-gpv-admin.php';
    }

    /**
     * Inicialización del plugin
     */
    public function init_plugin()
    {
        // Internacionalización
        load_plugin_textdomain('gestion-parque-vehicular', false, dirname(plugin_basename(GPV_PLUGIN_FILE)) . '/languages');

        // Inicializar clases principales
        global $GPV_Database;
        $GPV_Database = new GPV_Database();
        $this->database = $GPV_Database;

        // Inicializar componentes
        $gpv_roles = new GPV_Roles();
        $gpv_dashboard = new GPV_Driver_Dashboard();
        $gpv_driver_dashboard = new GPV_Driver_Dashboard();
        $gpv_pwa = new GPV_PWA();
        $gpv_api = new GPV_API();
        $gpv_quick_actions = new GPV_Quick_Actions();

        // Inicializar assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }


    /**
     * Enqueue de assets frontend
     */
    public function enqueue_frontend_assets()
    {
        // CSS principal para frontend
        wp_enqueue_style('gpv-frontend-style', GPV_PLUGIN_URL . 'assets/css/frontend.css', [], GPV_VERSION);
        wp_enqueue_style('gpv-styles', GPV_PLUGIN_URL . 'assets/css/gpv-styles.css', [], GPV_VERSION);

        // Scripts principales para frontend
        wp_enqueue_script('gpv-scripts', GPV_PLUGIN_URL . 'assets/js/gpv-scripts.js', ['jquery'], GPV_VERSION, true);

        // Script de frontend-app se carga solo en páginas del plugin con React
        if ($this->is_gpv_page()) {
            wp_enqueue_script('gpv-frontend-app', GPV_PLUGIN_URL . 'assets/js/frontend-app.js', ['jquery', 'wp-api'], GPV_VERSION, true);
        }

        // Script específico para dashboard de conductor
        if ($this->is_driver_dashboard_page()) {
            // Cargar dependencias de React
            wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', [], '17.0.2', true);
            wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', ['react'], '17.0.2', true);

            // Cargar script del dashboard de conductor
            wp_enqueue_script('gpv-driver-dashboard', GPV_PLUGIN_URL . 'assets/js/driver-dashboard.js', ['react', 'react-dom', 'wp-api'], GPV_VERSION, true);
            wp_enqueue_style('gpv-driver-dashboard', GPV_PLUGIN_URL . 'assets/css/driver-dashboard.css', [], GPV_VERSION);
        }

        // Servicio PWA solo para usuarios logueados
        if (is_user_logged_in() && $this->is_gpv_page() && class_exists('GPV_PWA')) {
            // Localizar script para PWA
            wp_localize_script('gpv-frontend-app', 'gpvPwaData', [
                'root' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'user_role' => $this->get_user_gpv_role(),
                'offline_enabled' => true,
                'pluginUrl' => GPV_PLUGIN_URL
            ]);
        }
    }

    /**
     * Enqueue de assets admin
     */
    public function enqueue_admin_assets()
    {
        // CSS principal para admin
        wp_enqueue_style('gpv-admin-style', GPV_PLUGIN_URL . 'assets/css/admin.css', [], GPV_VERSION);

        // Scripts principales para admin
        wp_enqueue_script('gpv-admin-app', GPV_PLUGIN_URL . 'assets/js/admin-app.js', ['jquery', 'wp-api'], GPV_VERSION, true);

        // Localizar script para admin
        wp_localize_script('gpv-admin-app', 'gpvAdminData', [
            'root' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'user_role' => $this->get_user_gpv_role(),
            'ajax_url' => admin_url('admin-ajax.php')
        ]);
    }

    /**
     * Verificar si estamos en una página del plugin
     */
    private function is_gpv_page()
    {
        if (!is_singular()) {
            return false;
        }

        global $post;

        if (!$post || !isset($post->post_content)) {
            return false;
        }

        $gpv_shortcodes = [
            'gpv_dashboard',
            'gpv_driver_panel',
            'gpv_consultant_panel',
            'gpv_form_salida',
            'gpv_form_carga',
            'gpv_form_vehiculo',
            'gpv_listado_movimientos'
        ];

        foreach ($gpv_shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si es la página de dashboard de conductor
     */
    private function is_driver_dashboard_page()
    {
        global $post;
        return is_singular() &&
            $post &&
            has_shortcode($post->post_content, 'gpv_driver_dashboard');
    }

    /**
     * Obtener rol de usuario en el sistema GPV
     */
    private function get_user_gpv_role()
    {
        $user = wp_get_current_user();

        if (in_array('gpv_administrator', (array) $user->roles)) {
            return 'administrator';
        } elseif (in_array('gpv_consultant', (array) $user->roles)) {
            return 'consultant';
        } elseif (in_array('gpv_operator', (array) $user->roles)) {
            return 'operator';
        } elseif (in_array('administrator', (array) $user->roles)) {
            return 'administrator'; // El admin de WP también tiene permisos completos
        }

        return 'visitor';
    }

    /**
     * Activación del plugin
     */
    public function activate_plugin()
    {
        // Instalar tablas DB
        $database = new GPV_Database();
        $database->install_tables();

        // Crear roles
        $roles = new GPV_Roles();
        $roles->add_roles();

        // Crear páginas necesarias
        $this->create_required_pages();

        // Limpiar caché de rewrite rules
        flush_rewrite_rules();

        // Flag de activación para redirects
        add_option('gpv_plugin_activated', true);
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate_plugin()
    {
        // No eliminamos tablas ni datos

        // Limpiar caché de rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Crear páginas requeridas
     */
    private function create_required_pages()
    {
        $pages = [
            'gpv-dashboard' => [
                'title' => __('Dashboard de Flota', 'gestion-parque-vehicular'),
                'content' => '<!-- wp:shortcode -->[gpv_dashboard]<!-- /wp:shortcode -->'
            ],
            'gpv-driver-panel' => [
                'title' => __('Panel de Conductor', 'gestion-parque-vehicular'),
                'content' => '<!-- wp:shortcode -->[gpv_driver_dashboard]<!-- /wp:shortcode -->'
            ],
            'gpv-consultant-panel' => [
                'title' => __('Panel de Consulta', 'gestion-parque-vehicular'),
                'content' => '<!-- wp:shortcode -->[gpv_consultant_panel]<!-- /wp:shortcode -->'
            ]
        ];

        foreach ($pages as $slug => $page_data) {
            // Solo crear si no existe
            $page_check = get_page_by_path($slug);

            if (!$page_check) {
                wp_insert_post([
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ]);
            }
        }
    }
}
// Iniciar sesión PHP si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Función de inicialización del plugin
function GPV()
{
    return GPV_Plugin::get_instance();
}
$GLOBALS['GPV'] = GPV();
