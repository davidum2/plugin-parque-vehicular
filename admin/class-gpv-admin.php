<?php

/**
 * class-gpv-admin.php
 *
 * Clase principal para la gestión del panel de administración del plugin Gestión Parque Vehicular (GPV).
 *
 * Esta clase se encarga de configurar el menú de administración, gestionar las vistas del panel,
 * y procesar las acciones relacionadas con la administración del plugin, como la generación y
 * descarga de reportes, la gestión de la configuración, etc.
 *
 * @package GestionParqueVehicular
 */

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class GPV_Admin
{
    /**
     * @var string SLUG_MENU_PRINCIPAL Slug para el menú principal del plugin.
     */
    const SLUG_MENU_PRINCIPAL = 'gpv_menu';

    /**
     * @var string NONCE_ACTION_DELETE_REPORTE Acción nonce para eliminar reportes.
     */
    const NONCE_ACTION_DELETE_REPORTE = 'delete_reporte_';

    /**
     * @var string NONCE_ACTION_GENERATE_REPORTE Acción nonce para generar reportes.
     */
    const NONCE_ACTION_GENERATE_REPORTE = 'gpv_generate_report';

    /**
     * @var string NONCE_ACTION_DOWNLOAD_REPORTE Acción nonce para descargar reportes.
     */
    const NONCE_ACTION_DOWNLOAD_REPORTE = 'gpv_download_report';

    /**
     * @var string NONCE_ACTION_CEI_REPORTE Acción nonce para generar reportes CEI.
     */
    const NONCE_ACTION_CEI_REPORTE = 'gpv_cei_report';

    /**
     * @var string NONCE_NOMBRE_REPORTE Nombre del nonce para reportes.
     */
    const NONCE_NOMBRE_REPORTE = 'gpv_report_nonce';

    /**
     * @var string NONCE_NOMBRE_CEI_REPORTE Nombre del nonce para reportes CEI.
     */
    const NONCE_NOMBRE_CEI_REPORTE = 'gpv_cei_report_nonce';

    /**
     * @var string NONCE_ACTION_UPDATE_DB Acción nonce para actualizar la base de datos.
     */
    const NONCE_ACTION_UPDATE_DB = 'gpv_update_db';

    /**
     * @var string NONCE_NOMBRE_UPDATE_DB Nombre del nonce para actualizar la base de datos.
     */
    const NONCE_NOMBRE_UPDATE_DB = 'gpv_update_db_nonce';

    /**
     * Constructor de la clase GPV_Admin.
     *
     * Inicializa la clase, incluye los archivos de vista, define las acciones para el menú de administración,
     * y encola los assets (estilos y scripts) necesarios para el panel de administración.
     */
    public function __construct()
    {
        $this->include_view_files(); // Incluir archivos de vistas

        add_action('admin_menu', array($this, 'agregar_menu_admin')); // Agregar menú en el panel de administración

        // Define las acciones para procesar formularios y descargas
        add_action('admin_post_gpv_generate_hello_world', array($this, 'generate_hello_world_pdf'));
        add_action('admin_post_gpv_generate_cei_report', array($this, 'generate_cei_report'));
        add_action('admin_post_gpv_generate_movement_report', array($this, 'generate_movement_report'));
        add_action('admin_post_gpv_download_movement_report', array($this, 'download_movement_report'));
        add_action('admin_post_gpv_delete_movement_report', array($this, 'delete_movement_report'));

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets')); // Encolar estilos y scripts para admin
    }

    /**
     * Incluye todos los archivos de vista necesarios para el panel de administración.
     *
     * Organiza y carga los archivos PHP que contienen la estructura HTML y la lógica de presentación
     * para las diferentes secciones del panel de administración del plugin.
     *
     * @access private
     * @return void
     */
    private function include_view_files()
    {
        require_once plugin_dir_path(__FILE__) . 'views/vehiculos-listado.php';
        require_once plugin_dir_path(__FILE__) . 'views/movimientos-listado.php';
        require_once plugin_dir_path(__FILE__) . 'views/cargas-listado.php';
        require_once plugin_dir_path(__FILE__) . 'views/views-reportes.php';
        require_once plugin_dir_path(__FILE__) . 'views/configuracion.php'; // Añadimos la inclusión del nuevo archivo
    }

    /**
     * Encola los estilos CSS y scripts JavaScript necesarios para el panel de administración.
     *
     * Este método se asegura de que los archivos de assets del panel de administración solo se carguen
     * en las páginas correspondientes del plugin, optimizando el rendimiento y evitando conflictos.
     *
     * @access public
     * @action admin_enqueue_scripts
     * @return void
     */
    public function enqueue_admin_assets()
    {
        $current_screen = get_current_screen(); // Obtiene la pantalla actual de WordPress

        // Carga assets solo en las páginas del plugin (identificadas por el prefijo 'gpv_')
        if (strpos($current_screen->id, 'gpv_') === false) {
            return; // Sale de la función si no es una página del plugin
        }

        // Encola hoja de estilos CSS para el admin panel
        wp_enqueue_style(
            'gpv-admin-styles',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array(), // Sin dependencias
            GPV_VERSION // Usa la versión del plugin para el control de caché
        );

        // Encola script JavaScript para funcionalidades del admin panel
        wp_enqueue_script(
            'gpv-admin-scripts',
            plugin_dir_url(__FILE__) . '../assets/js/admin-app.js',
            array('jquery'), // Dependencia de jQuery
            GPV_VERSION, // Usa la versión del plugin para el control de caché
            true // Cargar en el footer
        );

        // Localiza el script 'gpv-admin-scripts' para pasar datos de PHP a JavaScript
        wp_localize_script(
            'gpv-admin-scripts',
            'gpvAdminData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('gpv-admin-nonce'), // Genera un nonce para seguridad en AJAX
                'root'     => esc_url_raw(rest_url()), // URL base para la API REST
            )
        );
    }

    /**
     * Agrega el menú principal y submenús del plugin al panel de administración de WordPress.
     *
     * Define la estructura del menú de GPV, incluyendo el menú principal y sus submenús
     * para cada sección del plugin (Panel Principal, Vehículos, Reportes, Movimientos, Cargas, Configuración, Reporte CEI).
     *
     * @access public
     * @action admin_menu
     * @return void
     */
    public function agregar_menu_admin()
    {
        $menu_capability = 'manage_options'; // Capacidad requerida para ver el menú de GPV

        // Menu Principal
        add_menu_page(
            __('Parque Vehicular', 'gestion-parque-vehicular'),
            __('Parque Vehicular', 'gestion-parque-vehicular'),
            $menu_capability,
            self::SLUG_MENU_PRINCIPAL,
            array($this, 'gpv_pagina_principal'),
            'dashicons-car',
            6
        );

        // Submenús - Panel Principal (mismo slug que el principal para que sea el index)
        add_submenu_page(
            self::SLUG_MENU_PRINCIPAL,
            __('Panel Principal', 'gestion-parque-vehicular'),
            __('Panel Principal', 'gestion-parque-vehicular'),
            $menu_capability,
            self::SLUG_MENU_PRINCIPAL,
            array($this, 'gpv_pagina_principal')
        );

        // Submenú - Vehículos
        add_submenu_page(
            self::SLUG_MENU_PRINCIPAL,
            __('Vehículos', 'gestion-parque-vehicular'),
            __('Vehículos', 'gestion-parque-vehicular'),
            $menu_capability,
            'gpv_vehiculos',
            'gpv_listado_vehiculos' // Función de la vista vehiculos-listado.php
        );

        // Submenú - Reportes de Movimientos
        add_submenu_page(
            self::SLUG_MENU_PRINCIPAL,
            __('Reportes de Movimientos', 'gestion-parque-vehicular'),
            __('Reportes', 'gestion-parque-vehicular'),
            $menu_capability,
            'gpv_reportes',
            array($this, 'gpv_pagina_reportes')
        );

        // Submenú - Movimientos Diarios
        add_submenu_page(
            self::SLUG_MENU_PRINCIPAL,
            __('Movimientos Diarios', 'gestion-parque-vehicular'),
            __('Movimientos Diarios', 'gestion-parque-vehicular'),
            $menu_capability,
            'gpv_movimientos',
            'gpv_listado_movimientos' // Función de la vista movimientos-listado.php
        );

        // Submenú - Cargas de Combustible
        add_submenu_page(
            self::SLUG_MENU_PRINCIPAL,
            __('Cargas de Combustible', 'gestion-parque-vehicular'),
            __('Cargas de Combustible', 'gestion-parque-vehicular'),
            $menu_capability,
            'gpv_cargas',
            'gpv_listado_cargas' // Función de la vista cargas-listado.php
        );

        // Submenú - Configuración
        add_submenu_page(
            self::SLUG_MENU_PRINCIPAL,
            __('Configuración', 'gestion-parque-vehicular'),
            __('Configuración', 'gestion-parque-vehicular'),
            $menu_capability,
            'gpv_configuracion',
            array($this, 'gpv_pagina_configuracion')
        );

        // Submenú - Reporte C.E.I.
        add_submenu_page(
            self::SLUG_MENU_PRINCIPAL,
            __('Reporte C.E.I.', 'gestion-parque-vehicular'),
            __('Reporte C.E.I.', 'gestion-parque-vehicular'),
            $menu_capability,
            'gpv_cei_report',
            array($this, 'gpv_pagina_cei_report')
        );
    }

    /**
     * Muestra la página principal del plugin en el panel de administración.
     *
     * Renderiza la vista principal del dashboard del plugin GPV, mostrando un resumen estadístico
     * y enlaces rápidos a las secciones principales del plugin.
     *
     * @access public
     * @return void
     */
    public function gpv_pagina_principal()
    {
        require_once GPV_PLUGIN_DIR . 'admin/views/pagina-principal.php'; // Incluye la vista principal del admin
        gpv_pagina_principal_view(); // Llama a la función que renderiza la vista (definida en el archivo incluido)
    }

    /**
     * Muestra la página de configuración del plugin.
     *
     * Renderiza el formulario de configuración del plugin, permitiendo al usuario modificar
     * las opciones generales del plugin GPV y gestionarlas a través de la base de datos.
     *
     * @access public
     * @return void
     */
    public function gpv_pagina_configuracion()
    {
        require_once GPV_PLUGIN_DIR . 'admin/views/configuracion.php'; // Incluye la vista de configuración
        gpv_configuracion_view(); // Llama a la función que renderiza la vista (definida en el archivo incluido)
    }


    /**
     * Muestra la página de listado de reportes de movimientos.
     *
     * Gestiona la visualización de la página que lista los reportes de movimientos, permitiendo
     * seleccionar entre la lista de reportes o las opciones para crear, editar o gestionar firmantes.
     *
     * @access public
     * @return void
     */
    public function gpv_pagina_reportes()
    {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list'; // Obtiene la acción desde la URL, 'list' por defecto

        switch ($action) {
            case 'new': // Vista para crear un nuevo reporte
                require_once GPV_PLUGIN_DIR . 'admin/views/views-reportes.php';
                gpv_reportes_nuevo_view();
                break;
            case 'edit': // Vista para editar un reporte existente
                require_once GPV_PLUGIN_DIR . 'admin/views/views-reportes.php';
                gpv_reportes_editar_view();
                break;
            case 'firmantes': // Vista para gestionar firmantes autorizados
                require_once GPV_PLUGIN_DIR . 'admin/views/views-reportes.php';
                gpv_reportes_firmantes_view();
                break;
            default: // Vista por defecto: listado de reportes
                require_once GPV_PLUGIN_DIR . 'admin/views/views-reportes.php';
                gpv_reportes_listado_view();
                break;
        }
    }

    /**
     * Procesa la eliminación de un reporte de movimiento.
     *
     * Valida los permisos del usuario, verifica el nonce de seguridad, y procede a eliminar el reporte
     * y su archivo PDF asociado (si existe), además de desmarcar los movimientos que incluía.
     * Redirige al usuario de vuelta a la página de reportes con un mensaje de estado.
     *
     * @access public
     * @action admin_post_gpv_delete_movement_report
     * @return void
     */
    public function delete_movement_report()
    {
        // --- Seguridad y Validaciones ---
        $nonce_action = self::NONCE_ACTION_DELETE_REPORTE . $_GET['id']; // Nonce específico para cada reporte
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], $nonce_action)) {
            wp_die(__('Error de seguridad: Nonce verification failed.', 'gestion-parque-vehicular'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes.', 'gestion-parque-vehicular'));
        }

        $report_id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Obtiene y sanitiza el ID del reporte
        if (!$report_id) {
            wp_die(__('ID de reporte inválido.', 'gestion-parque-vehicular'));
        }

        global $GPV_Database;
        $reporte = $GPV_Database->obtener_reporte($report_id); // Obtiene los datos del reporte
        if (!$reporte) {
            $this->redirect_con_mensaje('gpv_reportes', 'not_found'); // Redirige si no se encuentra el reporte
            return;
        }

        // --- Eliminar Archivo PDF (si existe) ---
        if (!empty($reporte->archivo_pdf)) {
            $upload_dir = wp_upload_dir();
            $pdf_path = $upload_dir['basedir'] . '/gpv-reportes/' . $reporte->archivo_pdf;
            if (file_exists($pdf_path)) {
                @unlink($pdf_path); // Intenta eliminar el archivo, ignora errores
            }
        }

        // --- Desmarcar Movimientos Reportados ---
        $movimiento_ids = explode(',', $reporte->movimientos_incluidos);
        foreach ($movimiento_ids as $movimiento_id) {
            $GPV_Database->desmarcar_movimiento_reportado($movimiento_id); // Reestablece el estado de los movimientos
        }

        // --- Eliminar Reporte de la Base de Datos ---
        $delete_result = $GPV_Database->eliminar_reporte($report_id);

        // --- Redirección con Mensaje de Estado ---
        $message_type = $delete_result ? 'deleted' : 'delete_error'; // Define el tipo de mensaje según el resultado
        $this->redirect_con_mensaje('gpv_reportes', $message_type);
    }

    /**
     * Procesa la generación del reporte de movimientos en formato PDF.
     *
     * Verifica los permisos, el nonce, y el ID del reporte, luego utiliza la clase GPV_Report_PDF
     * para generar el archivo PDF. Redirige de vuelta a la página de reportes con un mensaje de éxito o error.
     *
     * @access public
     * @action admin_post_gpv_generate_movement_report
     * @return void
     */
    public function generate_movement_report()
    {
        // --- Seguridad y Validaciones ---
        if (!isset($_REQUEST[self::NONCE_NOMBRE_REPORTE]) || !wp_verify_nonce($_REQUEST[self::NONCE_NOMBRE_REPORTE], self::NONCE_ACTION_GENERATE_REPORTE)) {
            wp_die(__('Error de seguridad: Nonce verification failed.', 'gestion-parque-vehicular'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes.', 'gestion-parque-vehicular'));
        }
        $report_id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Obtiene y sanitiza el ID del reporte
        if (!$report_id) {
            wp_die(__('ID de reporte inválido.', 'gestion-parque-vehicular'));
        }

        // --- Incluir Clase y Generar PDF ---
        require_once GPV_PLUGIN_DIR . 'includes/reports/class-gpv-report-pdf.php';
        $report_pdf = new GPV_Report_PDF();
        $pdf_filepath = $report_pdf->generate_report($report_id); // Genera el PDF

        // --- Redirección con Mensaje de Estado ---
        $message_type = $pdf_filepath ? 'pdf_generated' : 'pdf_error'; // Define el tipo de mensaje según el resultado
        $this->redirect_con_mensaje('gpv_reportes', $message_type, $report_id);
    }


    /**
     * Procesa la solicitud de descarga de un reporte de movimiento en PDF.
     *
     * Verifica los permisos, nonce, y la existencia del reporte y su archivo PDF,
     * luego fuerza la descarga del archivo PDF al navegador del usuario.
     *
     * @access public
     * @action admin_post_gpv_download_movement_report
     * @return void
     */
    public function download_movement_report()
    {
        // --- Seguridad y Validaciones ---
        if (!isset($_REQUEST[self::NONCE_NOMBRE_REPORTE]) || !wp_verify_nonce($_REQUEST[self::NONCE_NOMBRE_REPORTE], self::NONCE_ACTION_DOWNLOAD_REPORTE)) {
            wp_die(__('Error de seguridad: Nonce verification failed.', 'gestion-parque-vehicular'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes.', 'gestion-parque-vehicular'));
        }
        $report_id = isset($_GET['id']) ? intval($_GET['id']) : 0; // Obtiene y sanitiza el ID del reporte
        if (!$report_id) {
            wp_die(__('ID de reporte inválido.', 'gestion-parque-vehicular'));
        }

        global $GPV_Database;
        $reporte = $GPV_Database->obtener_reporte($report_id); // Obtiene los datos del reporte

        if (!$reporte || empty($reporte->archivo_pdf)) {
            wp_die(__('Archivo PDF no encontrado.', 'gestion-parque-vehicular'));
        }

        $upload_dir = wp_upload_dir();
        $pdf_path = $upload_dir['basedir'] . '/gpv-reportes/' . $reporte->archivo_pdf;

        if (!file_exists($pdf_path)) {
            wp_die(__('Archivo PDF no encontrado en el servidor.', 'gestion-parque-vehicular'));
        }

        // --- Forzar Descarga del Archivo PDF ---
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($pdf_path) . '"');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        ob_clean(); // Limpia el buffer de salida
        flush();     // Fuerza la salida al navegador
        readfile($pdf_path); // Lee y envía el archivo al navegador
        exit;        // Termina la ejecución del script
    }





    /**
     * Redirige a una página del plugin con un mensaje y un ID de reporte opcional.
     *
     * Utilizado para mantener la consistencia en las redirecciones después de procesar acciones
     * como la generación o eliminación de reportes, permitiendo mostrar mensajes de estado al usuario.
     *
     * @access private
     * @param string $page_slug Slug de la página de administración a redirigir (sin 'admin.php?page=').
     * @param string $message_type Tipo de mensaje a pasar como parámetro en la URL ('success', 'error', etc.).
     * @param int|null $report_id ID del reporte a incluir en la URL (opcional).
     * @return void
     */
    private function redirect_con_mensaje($page_slug, $message_type, $report_id = null)
    {
        $redirect_url = admin_url("admin.php?page={$page_slug}&message={$message_type}"); // URL base de redirección

        if ($report_id) {
            $redirect_url .= "&id={$report_id}"; // Añade el ID del reporte si se proporciona
        }

        wp_redirect($redirect_url); // Realiza la redirección
        exit; // Termina la ejecución del script después de la redirección
    }
}

// Inicializar la clase GPV_Admin para activar sus funcionalidades en el panel de administración
new GPV_Admin();
