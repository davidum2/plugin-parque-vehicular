<?php
// includes/class-gpv-plugin.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Clase principal del plugin Gestión de Parque Vehicular
 *
 * Esta clase inicializa todas las demás clases y gestiona los hooks principales del plugin.
 */
class GPV_Plugin {

    /**
     * Instancia única de la clase (patrón Singleton)
     *
     * @var GPV_Plugin
     */
    private static $instance = null;

    /**
     * Versión de la base de datos
     *
     * @var string
     */
    private $db_version = '1.0';

    /**
     * Instancia de la clase de base de datos
     *
     * @var GPV_Database
     */
    public $database;

    /**
     * Instancia de la clase de administración
     *
     * @var GPV_Admin
     */
    public $admin;

    /**
     * Instancia de la clase de shortcodes
     *
     * @var GPV_Shortcodes
     */
    public $shortcodes;

    /**
     * Constructor de la clase
     */
    private function __construct() {
        // Definir constantes
        $this->define_constants();

        // Incluir archivos necesarios
        $this->include_files();

        // Inicializar clases
        $this->init_classes();

        // Registrar hooks
        $this->register_hooks();
    }

    /**
     * Obtener la instancia única de la clase (patrón Singleton)
     *
     * @return GPV_Plugin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Definir constantes del plugin
     */
    private function define_constants() {
        define( 'GPV_VERSION', '1.0' );
        define( 'GPV_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'GPV_PLUGIN_URL', plugin_dir_url( dirname( __FILE__ ) ) );
        define( 'GPV_PLUGIN_BASENAME', plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/gestion-parque-vehicular.php' );
    }

    /**
     * Incluir archivos necesarios
     */
    private function include_files() {
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-database.php';
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-vehicle.php';
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-movement.php';
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-fuel.php';
        require_once GPV_PLUGIN_DIR . 'admin/class-gpv-admin.php';
        require_once GPV_PLUGIN_DIR . 'includes/class-gpv-shortcodes.php';
    }

    /**
     * Inicializar clases
     */
    private function init_classes() {
        global $gpv_db_version;
        $gpv_db_version = $this->db_version;

        // Inicializar la base de datos
        $this->database = new GPV_Database();

        // Inicializar la administración
        $this->admin = new GPV_Admin();

        // Inicializar los shortcodes
        $this->shortcodes = new GPV_Shortcodes();
    }

    /**
     * Registrar hooks
     */
    private function register_hooks() {
        // Hook de activación
        register_activation_hook( GPV_PLUGIN_BASENAME, array( $this, 'activate' ) );

        // Hook de desactivación
        register_deactivation_hook( GPV_PLUGIN_BASENAME, array( $this, 'deactivate' ) );

        // Hook de desinstalación
        register_uninstall_hook( GPV_PLUGIN_BASENAME, array( 'GPV_Plugin', 'uninstall' ) );

        // Enqueue scripts y estilos
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Activar el plugin
     */
    public function activate() {
        // Instalar tablas
        $this->database->install_tables();

        // Limpiar caché de rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desactivar el plugin
     */
    public function deactivate() {
        // Limpiar caché de rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Desinstalar el plugin
     */
    public static function uninstall() {
        // Eliminar tablas
        $database = new GPV_Database();
        $database->uninstall_tables();

        // Eliminar opciones
        delete_option( 'gpv_db_version' );
    }

    /**
     * Encolar scripts y estilos
     */
    public function enqueue_scripts() {
        wp_enqueue_style( 'gpv-styles', GPV_PLUGIN_URL . 'assets/css/gpv-styles.css', array(), GPV_VERSION );
        wp_enqueue_script( 'gpv-scripts', GPV_PLUGIN_URL . 'assets/js/gpv-scripts.js', array( 'jquery' ), GPV_VERSION, true );
    }
}
