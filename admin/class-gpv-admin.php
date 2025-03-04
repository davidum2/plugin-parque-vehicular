<?php
// admin/class-gpv-admin.php

if (! defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

/**
 * Clase para gestionar el panel de administración
 */
class GPV_Admin
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Incluir archivos de vistas
        $this->include_view_files();

        // Agregar menú en el panel de administración
        add_action('admin_menu', array($this, 'gpv_agregar_menu_admin'));
        add_action('admin_post_gpv_generate_hello_world', array($this, 'generate_hello_world_pdf'));
        add_action('admin_post_gpv_generate_cei_report', array($this, 'generate_cei_report'));

        // Agregar estilos y scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Incluir archivos de vistas
     */
    private function include_view_files()
    {
        require_once plugin_dir_path(__FILE__) . 'views/vehiculos-listado.php';
        require_once plugin_dir_path(__FILE__) . 'views/movimientos-listado.php';
        require_once plugin_dir_path(__FILE__) . 'views/cargas-listado.php';
    }

    /**
     * Cargar estilos y scripts de administración
     */
    public function enqueue_admin_assets()
    {
        // Solo cargar en las páginas del plugin
        $screen = get_current_screen();
        if (strpos($screen->id, 'gpv_') === false) {
            return;
        }

        // Cargar estilos
        wp_enqueue_style(
            'gpv-admin-styles',
            plugin_dir_url(__FILE__) . '../assets/css/admin.css',
            array(),
            GPV_VERSION
        );

        // Cargar scripts
        wp_enqueue_script(
            'gpv-admin-scripts',
            plugin_dir_url(__FILE__) . '../assets/js/admin-app.js',
            array('jquery'),
            GPV_VERSION,
            true
        );

        // Pasar datos al script
        wp_localize_script(
            'gpv-admin-scripts',
            'gpvAdminData',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('gpv-admin-nonce'),
                'root' => esc_url_raw(rest_url()),
            )
        );
    }

    /**
     * Agrega el menú principal y submenús del plugin en el panel de administración.
     */
    public function gpv_agregar_menu_admin()
    {
        add_menu_page(
            __('Parque Vehicular', 'gestion-parque-vehicular'), // Título del menú
            __('Parque Vehicular', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_menu', // Slug del menú
            array($this, 'gpv_pagina_principal'), // Función para mostrar la página principal
            'dashicons-car', // Icono del menú
            6 // Posición del menú
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __('Panel Principal', 'gestion-parque-vehicular'), // Título del submenú
            __('Panel Principal', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_menu', // Slug del submenú (igual al padre para el primer submenú)
            array($this, 'gpv_pagina_principal') // Función para mostrar la página principal
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __('Vehículos', 'gestion-parque-vehicular'), // Título del submenú
            __('Vehículos', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_vehiculos', // Slug del submenú
            'gpv_listado_vehiculos' // Función para mostrar el listado de vehículos
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __('Reportes de Movimientos', 'gestion-parque-vehicular'), // Título
            __('Reportes', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_reportes', // Slug del submenú
            array($this, 'gpv_pagina_reportes') // Función para mostrar la página
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __('Movimientos Diarios', 'gestion-parque-vehicular'), // Título del submenú
            __('Movimientos Diarios', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_movimientos', // Slug del submenú
            'gpv_listado_movimientos' // Función para mostrar el listado de movimientos
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __('Cargas de Combustible', 'gestion-parque-vehicular'), // Título del submenú
            __('Cargas de Combustible', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_cargas', // Slug del submenú
            'gpv_listado_cargas' // Función para mostrar el listado de cargas
        );

        // Agregar submenú de configuración
        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __('Configuración', 'gestion-parque-vehicular'), // Título del submenú
            __('Configuración', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_configuracion', // Slug del submenú
            array($this, 'gpv_pagina_configuracion') // Función para mostrar configuración
        );
        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __('Reporte C.E.I.', 'gestion-parque-vehicular'), // Título del submenú
            __('Reporte C.E.I.', 'gestion-parque-vehicular'), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_cei_report', // Slug del submenú
            array($this, 'gpv_pagina_cei_report') // Función para mostrar la página
        );
    }

    /**
     * Método para mostrar la página de reportes C.E.I.
     */
    public function gpv_pagina_cei_report()
    {
        // Incluir archivo de vista
        require_once GPV_PLUGIN_DIR . 'admin/views/cei-report-view.php';
        gpv_cei_report_view();
    }

    /**
     * Muestra la página de reportes de movimientos
     */
    public function gpv_pagina_reportes()
    {
        // Verificamos qué acción queremos realizar
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        switch ($action) {
            case 'new':
                // Página para crear nuevo reporte
                if (file_exists(GPV_PLUGIN_DIR . 'admin/views/reportes-nuevo.php')) {
                    require_once GPV_PLUGIN_DIR . 'admin/views/reportes-nuevo.php';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error: Archivo de vista no encontrado', 'gestion-parque-vehicular') . '</p></div>';
                }
                break;
            case 'edit':
                // Página para editar reporte
                if (file_exists(GPV_PLUGIN_DIR . 'admin/views/reportes-editar.php')) {
                    require_once GPV_PLUGIN_DIR . 'admin/views/reportes-editar.php';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error: Archivo de vista no encontrado', 'gestion-parque-vehicular') . '</p></div>';
                }
                break;
            case 'firmantes':
                // Página para gestionar firmantes
                if (file_exists(GPV_PLUGIN_DIR . 'admin/views/reportes-firmantes.php')) {
                    require_once GPV_PLUGIN_DIR . 'admin/views/reportes-firmantes.php';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error: Archivo de vista de firmantes no encontrado', 'gestion-parque-vehicular') . '</p></div>';
                    // Fallback
                    echo '<div class="wrap"><h1>' . __('Gestionar Firmantes', 'gestion-parque-vehicular') . '</h1>';
                    echo '<p>' . __('La funcionalidad de gestión de firmantes no está disponible en este momento.', 'gestion-parque-vehicular') . '</p>';
                    echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_reportes')) . '" class="button">' . __('Volver a Reportes', 'gestion-parque-vehicular') . '</a>';
                    echo '</div>';
                }
                break;
            default:
                // Listado de reportes (vista por defecto)
                if (file_exists(GPV_PLUGIN_DIR . 'admin/views/reportes-listado.php')) {
                    require_once GPV_PLUGIN_DIR . 'admin/views/reportes-listado.php';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Error: Archivo de vista no encontrado', 'gestion-parque-vehicular') . '</p></div>';
                }
                break;
        }
    }
    /**
     * Muestra la página principal del plugin en el panel de administración.
     */
    public function gpv_pagina_principal()
    {
        echo '<div class="wrap gpv-admin-page">';
        echo '<h2>' . esc_html__('Gestión de Parque Vehicular', 'gestion-parque-vehicular') . '</h2>';
        echo '<p>' . esc_html__('Bienvenido al panel de administración del Parque Vehicular.', 'gestion-parque-vehicular') . '</p>';

        // Obtener datos estadísticos
        global $GPV_Database;
        $stats = $GPV_Database->get_dashboard_stats();

        // Mostrar resumen de datos
        echo '<div class="gpv-dashboard-widgets">';

        // Widget de vehículos
        echo '<div class="gpv-dashboard-widget">';
        echo '<h3>' . esc_html__('Vehículos', 'gestion-parque-vehicular') . '</h3>';
        echo '<div class="gpv-widget-content">';
        echo '<div class="gpv-stat-card">';
        echo '<div class="gpv-stat-icon"><span class="dashicons dashicons-car"></span></div>';
        echo '<div class="gpv-stat-details">';
        echo '<h4>' . esc_html($stats['vehicles']['total']) . '</h4>';
        echo '<p>' . esc_html__('Total de vehículos', 'gestion-parque-vehicular') . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<p>' . esc_html__('Disponibles', 'gestion-parque-vehicular') . ': ' . esc_html($stats['vehicles']['available']) . '</p>';
        echo '<p>' . esc_html__('En uso', 'gestion-parque-vehicular') . ': ' . esc_html($stats['vehicles']['in_use']) . '</p>';
        echo '</div>';
        echo '</div>';

        // Widget de movimientos
        echo '<div class="gpv-dashboard-widget">';
        echo '<h3>' . esc_html__('Movimientos', 'gestion-parque-vehicular') . '</h3>';
        echo '<div class="gpv-widget-content">';
        echo '<div class="gpv-stat-card">';
        echo '<div class="gpv-stat-icon"><span class="dashicons dashicons-location"></span></div>';
        echo '<div class="gpv-stat-details">';
        echo '<h4>' . esc_html($stats['movements']['today']) . '</h4>';
        echo '<p>' . esc_html__('Movimientos hoy', 'gestion-parque-vehicular') . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<p>' . esc_html__('Este mes', 'gestion-parque-vehicular') . ': ' . esc_html($stats['movements']['month']) . '</p>';
        echo '<p>' . esc_html__('Distancia total', 'gestion-parque-vehicular') . ': ' . esc_html(number_format($stats['movements']['total_distance'], 2)) . ' km</p>';
        echo '<p>' . esc_html__('Movimientos activos', 'gestion-parque-vehicular') . ': ' . esc_html($stats['movements']['active']) . '</p>';
        echo '</div>';
        echo '</div>';

        // Widget de combustible
        echo '<div class="gpv-dashboard-widget">';
        echo '<h3>' . esc_html__('Combustible', 'gestion-parque-vehicular') . '</h3>';
        echo '<div class="gpv-widget-content">';
        echo '<div class="gpv-stat-card">';
        echo '<div class="gpv-stat-icon"><span class="dashicons dashicons-dashboard"></span></div>';
        echo '<div class="gpv-stat-details">';
        echo '<h4>' . esc_html(number_format($stats['fuel']['month_consumption'], 2)) . ' L</h4>';
        echo '<p>' . esc_html__('Consumo mensual', 'gestion-parque-vehicular') . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<p>' . esc_html__('Consumo promedio', 'gestion-parque-vehicular') . ': ' . esc_html(number_format($stats['fuel']['average_consumption'], 2)) . ' km/L</p>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // Fin dashboard widgets

        // Enlaces rápidos
        echo '<h3>' . esc_html__('Enlaces Rápidos', 'gestion-parque-vehicular') . '</h3>';
        echo '<ul class="admin-links">';
        echo '<li><a href="' . esc_url(admin_url('admin.php?page=gpv_vehiculos')) . '">' . esc_html__('Gestionar Vehículos', 'gestion-parque-vehicular') . '</a></li>';
        echo '<li><a href="' . esc_url(admin_url('admin.php?page=gpv_movimientos')) . '">' . esc_html__('Ver Movimientos Diarios', 'gestion-parque-vehicular') . '</a></li>';
        echo '<li><a href="' . esc_url(admin_url('admin.php?page=gpv_cargas')) . '">' . esc_html__('Ver Cargas de Combustible', 'gestion-parque-vehicular') . '</a></li>';
        echo '</ul>';

        echo '</div>'; // Fin wrap
    }

    /**
     * Muestra la página de configuración
     */
    public function gpv_pagina_configuracion()
    {
        global $GPV_Database;

        // Guardar cambios si se envió el formulario
        if (isset($_POST['gpv_save_settings']) && isset($_POST['gpv_nonce']) && wp_verify_nonce($_POST['gpv_nonce'], 'gpv_save_settings')) {
            // Procesar cada configuración
            if (isset($_POST['calcular_consumo_automatico'])) {
                $GPV_Database->update_setting('calcular_consumo_automatico', 1);
            } else {
                $GPV_Database->update_setting('calcular_consumo_automatico', 0);
            }

            if (isset($_POST['umbral_nivel_combustible_bajo'])) {
                $GPV_Database->update_setting('umbral_nivel_combustible_bajo', sanitize_text_field($_POST['umbral_nivel_combustible_bajo']));
            }

            if (isset($_POST['mostrar_dashboard_publico'])) {
                $GPV_Database->update_setting('mostrar_dashboard_publico', 1);
            } else {
                $GPV_Database->update_setting('mostrar_dashboard_publico', 0);
            }

            if (isset($_POST['sincronizacion_offline_habilitada'])) {
                $GPV_Database->update_setting('sincronizacion_offline_habilitada', 1);
            } else {
                $GPV_Database->update_setting('sincronizacion_offline_habilitada', 0);
            }

            if (isset($_POST['logo_url'])) {
                $GPV_Database->update_setting('logo_url', esc_url_raw($_POST['logo_url']));
            }

            // Mostrar mensaje de éxito
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada correctamente.', 'gestion-parque-vehicular') . '</p></div>';
        }

        // Obtener configuración actual
        $calcular_consumo_automatico = $GPV_Database->get_setting('calcular_consumo_automatico');
        $umbral_nivel_combustible_bajo = $GPV_Database->get_setting('umbral_nivel_combustible_bajo');
        $mostrar_dashboard_publico = $GPV_Database->get_setting('mostrar_dashboard_publico');
        $sincronizacion_offline_habilitada = $GPV_Database->get_setting('sincronizacion_offline_habilitada');
        $logo_url = $GPV_Database->get_setting('logo_url');

        // Mostrar formulario
        echo '<div class="wrap gpv-admin-page">';
        echo '<h2>' . esc_html__('Configuración del Plugin', 'gestion-parque-vehicular') . '</h2>';

        echo '<form method="post" action="" class="gpv-admin-form">';
        wp_nonce_field('gpv_save_settings', 'gpv_nonce');

        echo '<table class="form-table">';

        // Cálculo automático de consumo
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Cálculo automático de consumo', 'gestion-parque-vehicular') . '</th>';
        echo '<td><fieldset><legend class="screen-reader-text"><span>' . esc_html__('Cálculo automático de consumo', 'gestion-parque-vehicular') . '</span></legend>';
        echo '<label for="calcular_consumo_automatico"><input type="checkbox" name="calcular_consumo_automatico" id="calcular_consumo_automatico" value="1" ' . checked($calcular_consumo_automatico, 1, false) . '> ';
        echo esc_html__('Calcular automáticamente el consumo de combustible en cada movimiento', 'gestion-parque-vehicular') . '</label>';
        echo '</fieldset></td>';
        echo '</tr>';

        // Umbral de nivel bajo de combustible
        echo '<tr>';
        echo '<th scope="row"><label for="umbral_nivel_combustible_bajo">' . esc_html__('Umbral de nivel bajo de combustible (%)', 'gestion-parque-vehicular') . '</label></th>';
        echo '<td><input type="number" name="umbral_nivel_combustible_bajo" id="umbral_nivel_combustible_bajo" value="' . esc_attr($umbral_nivel_combustible_bajo) . '" min="1" max="50" step="1" class="small-text">';
        echo '<p class="description">' . esc_html__('Porcentaje para considerar nivel bajo de combustible y mostrar alertas.', 'gestion-parque-vehicular') . '</p></td>';
        echo '</tr>';

        // Mostrar dashboard público
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Dashboard público', 'gestion-parque-vehicular') . '</th>';
        echo '<td><fieldset><legend class="screen-reader-text"><span>' . esc_html__('Dashboard público', 'gestion-parque-vehicular') . '</span></legend>';
        echo '<label for="mostrar_dashboard_publico"><input type="checkbox" name="mostrar_dashboard_publico" id="mostrar_dashboard_publico" value="1" ' . checked($mostrar_dashboard_publico, 1, false) . '> ';
        echo esc_html__('Mostrar dashboard público a usuarios no logueados', 'gestion-parque-vehicular') . '</label>';
        echo '</fieldset></td>';
        echo '</tr>';

        // Sincronización offline
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Funcionalidad offline', 'gestion-parque-vehicular') . '</th>';
        echo '<td><fieldset><legend class="screen-reader-text"><span>' . esc_html__('Funcionalidad offline', 'gestion-parque-vehicular') . '</span></legend>';
        echo '<label for="sincronizacion_offline_habilitada"><input type="checkbox" name="sincronizacion_offline_habilitada" id="sincronizacion_offline_habilitada" value="1" ' . checked($sincronizacion_offline_habilitada, 1, false) . '> ';
        echo esc_html__('Habilitar sincronización offline (PWA)', 'gestion-parque-vehicular') . '</label>';
        echo '</fieldset></td>';
        echo '</tr>';

        // URL del logo
        echo '<tr>';
        echo '<th scope="row"><label for="logo_url">' . esc_html__('URL del Logo', 'gestion-parque-vehicular') . '</label></th>';
        echo '<td><input type="url" name="logo_url" id="logo_url" value="' . esc_url($logo_url) . '" class="regular-text">';
        echo '<p class="description">' . esc_html__('URL de la imagen del logo para la aplicación (proporción 3:1 recomendada).', 'gestion-parque-vehicular') . '</p></td>';
        echo '</tr>';

        echo '</table>';

        echo '<p class="submit"><input type="submit" name="gpv_save_settings" class="button button-primary" value="' . esc_attr__('Guardar Cambios', 'gestion-parque-vehicular') . '"></p>';
        echo '</form>';

        echo '</div>';
        echo '<div class="gpv-admin-section">';
        echo '<h3>' . esc_html__('Herramientas de Base de Datos', 'gestion-parque-vehicular') . '</h3>';
        echo '<p>' . esc_html__('Utiliza estas herramientas para actualizar la estructura de la base de datos si encuentras errores.', 'gestion-parque-vehicular') . '</p>';
        echo '<form method="post" action="">';
        wp_nonce_field('gpv_update_db', 'gpv_update_db_nonce');
        echo '<p><input type="submit" name="gpv_update_db" class="button button-secondary" value="' . esc_attr__('Actualizar Estructura de Base de Datos', 'gestion-parque-vehicular') . '"></p>';
        echo '</form>';

        // Procesar actualización de BD si se solicitó
        if (isset($_POST['gpv_update_db']) && isset($_POST['gpv_update_db_nonce']) && wp_verify_nonce($_POST['gpv_update_db_nonce'], 'gpv_update_db')) {
            global $GPV_Database;
            $result = $GPV_Database->update_database_structure();

            if ($result) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Base de datos actualizada correctamente.', 'gestion-parque-vehicular') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Error al actualizar la base de datos.', 'gestion-parque-vehicular') . '</p></div>';
            }
        }
        echo '</div>';
    }



    /**
     * Método para generar el reporte C.E.I.
     */
    public function generate_cei_report()
    {
        // Verificar nonce
        if (!isset($_REQUEST['gpv_cei_report_nonce']) || !wp_verify_nonce($_REQUEST['gpv_cei_report_nonce'], 'gpv_cei_report')) {
            wp_die(__('Error de seguridad. Por favor, intenta de nuevo.', 'gestion-parque-vehicular'));
        }

        // Verificar permisos
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permiso para realizar esta acción.', 'gestion-parque-vehicular'));
        }

        // Obtener ID del movimiento
        $movement_id = isset($_POST['movement_id']) ? intval($_POST['movement_id']) : 0;

        if (!$movement_id) {
            wp_die(__('Debe seleccionar un movimiento válido.', 'gestion-parque-vehicular'));
        }

        // Incluir la clase del reporte
        require_once GPV_PLUGIN_DIR . 'includes/reports/class-gpv-cei-report.php';

        // Crear instancia y generar PDF
        $report = new GPV_CEI_Report();
        $report->generate_cei_report($movement_id);
    }
}

// Inicializar la clase
$gpv_admin = new GPV_Admin();
