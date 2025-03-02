<?php
// admin/class-gpv-admin.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Clase para gestionar el panel de administración
 */
class GPV_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        // Incluir archivos de vistas
        $this->include_view_files();

        // Agregar menú en el panel de administración
        add_action( 'admin_menu', array( $this, 'gpv_agregar_menu_admin' ) );

        // Agregar estilos y scripts
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Incluir archivos de vistas
     */
    private function include_view_files() {
        require_once plugin_dir_path( __FILE__ ) . 'views/vehiculos-listado.php';
        require_once plugin_dir_path( __FILE__ ) . 'views/movimientos-listado.php';
        require_once plugin_dir_path( __FILE__ ) . 'views/cargas-listado.php';
    }

    /**
     * Cargar estilos y scripts de administración
     */
    public function enqueue_admin_assets() {
        // Solo cargar en las páginas del plugin
        $screen = get_current_screen();
        if (strpos($screen->id, 'gpv_') === false) {
            return;
        }

        // Cargar estilos
        wp_enqueue_style(
            'gpv-admin-styles',
            plugin_dir_url( __FILE__ ) . '../assets/css/admin.css',
            array(),
            GPV_VERSION
        );

        // Cargar scripts
        wp_enqueue_script(
            'gpv-admin-scripts',
            plugin_dir_url( __FILE__ ) . '../assets/js/admin-app.js',
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
    public function gpv_agregar_menu_admin() {
        add_menu_page(
            __( 'Parque Vehicular', 'gestion-parque-vehicular' ), // Título del menú
            __( 'Parque Vehicular', 'gestion-parque-vehicular' ), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_menu', // Slug del menú
            array( $this, 'gpv_pagina_principal' ), // Función para mostrar la página principal
            'dashicons-car', // Icono del menú
            6 // Posición del menú
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __( 'Panel Principal', 'gestion-parque-vehicular' ), // Título del submenú
            __( 'Panel Principal', 'gestion-parque-vehicular' ), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_menu', // Slug del submenú (igual al padre para el primer submenú)
            array( $this, 'gpv_pagina_principal' ) // Función para mostrar la página principal
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __( 'Vehículos', 'gestion-parque-vehicular' ), // Título del submenú
            __( 'Vehículos', 'gestion-parque-vehicular' ), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_vehiculos', // Slug del submenú
            'gpv_listado_vehiculos' // Función para mostrar el listado de vehículos
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __( 'Movimientos Diarios', 'gestion-parque-vehicular' ), // Título del submenú
            __( 'Movimientos Diarios', 'gestion-parque-vehicular' ), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_movimientos', // Slug del submenú
            'gpv_listado_movimientos' // Función para mostrar el listado de movimientos
        );

        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __( 'Cargas de Combustible', 'gestion-parque-vehicular' ), // Título del submenú
            __( 'Cargas de Combustible', 'gestion-parque-vehicular' ), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_cargas', // Slug del submenú
            'gpv_listado_cargas' // Función para mostrar el listado de cargas
        );

        // Agregar submenú de mantenimientos
        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __( 'Mantenimientos', 'gestion-parque-vehicular' ), // Título del submenú
            __( 'Mantenimientos', 'gestion-parque-vehicular' ), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_mantenimientos', // Slug del submenú
            array( $this, 'gpv_listado_mantenimientos' ) // Función para mostrar mantenimientos
        );

        // Agregar submenú de configuración
        add_submenu_page(
            'gpv_menu', // Slug del menú padre
            __( 'Configuración', 'gestion-parque-vehicular' ), // Título del submenú
            __( 'Configuración', 'gestion-parque-vehicular' ), // Título a mostrar
            'manage_options', // Capacidad necesaria
            'gpv_configuracion', // Slug del submenú
            array( $this, 'gpv_pagina_configuracion' ) // Función para mostrar configuración
        );
    }

    /**
     * Muestra la página principal del plugin en el panel de administración.
     */
    public function gpv_pagina_principal() {
        echo '<div class="wrap gpv-admin-page">';
        echo '<h2>' . esc_html__( 'Gestión de Parque Vehicular', 'gestion-parque-vehicular' ) . '</h2>';
        echo '<p>' . esc_html__( 'Bienvenido al panel de administración del Parque Vehicular.', 'gestion-parque-vehicular' ) . '</p>';

        // Obtener datos estadísticos
        global $GPV_Database;
        $stats = $GPV_Database->get_dashboard_stats();

        // Mostrar resumen de datos
        echo '<div class="gpv-dashboard-widgets">';

        // Widget de vehículos
        echo '<div class="gpv-dashboard-widget">';
        echo '<h3>' . esc_html__( 'Vehículos', 'gestion-parque-vehicular' ) . '</h3>';
        echo '<div class="gpv-widget-content">';
        echo '<div class="gpv-stat-card">';
        echo '<div class="gpv-stat-icon"><span class="dashicons dashicons-car"></span></div>';
        echo '<div class="gpv-stat-details">';
        echo '<h4>' . esc_html($stats['vehicles']['total']) . '</h4>';
        echo '<p>' . esc_html__( 'Total de vehículos', 'gestion-parque-vehicular' ) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<p>' . esc_html__( 'Disponibles', 'gestion-parque-vehicular' ) . ': ' . esc_html($stats['vehicles']['available']) . '</p>';
        echo '<p>' . esc_html__( 'En uso', 'gestion-parque-vehicular' ) . ': ' . esc_html($stats['vehicles']['in_use']) . '</p>';
        echo '<p>' . esc_html__( 'En mantenimiento', 'gestion-parque-vehicular' ) . ': ' . esc_html($stats['vehicles']['maintenance']) . '</p>';
        echo '</div>';
        echo '</div>';

        // Widget de movimientos
        echo '<div class="gpv-dashboard-widget">';
        echo '<h3>' . esc_html__( 'Movimientos', 'gestion-parque-vehicular' ) . '</h3>';
        echo '<div class="gpv-widget-content">';
        echo '<div class="gpv-stat-card">';
        echo '<div class="gpv-stat-icon"><span class="dashicons dashicons-location"></span></div>';
        echo '<div class="gpv-stat-details">';
        echo '<h4>' . esc_html($stats['movements']['today']) . '</h4>';
        echo '<p>' . esc_html__( 'Movimientos hoy', 'gestion-parque-vehicular' ) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<p>' . esc_html__( 'Este mes', 'gestion-parque-vehicular' ) . ': ' . esc_html($stats['movements']['month']) . '</p>';
        echo '<p>' . esc_html__( 'Distancia total', 'gestion-parque-vehicular' ) . ': ' . esc_html(number_format($stats['movements']['total_distance'], 2)) . ' km</p>';
        echo '<p>' . esc_html__( 'Movimientos activos', 'gestion-parque-vehicular' ) . ': ' . esc_html($stats['movements']['active']) . '</p>';
        echo '</div>';
        echo '</div>';

        // Widget de combustible
        echo '<div class="gpv-dashboard-widget">';
        echo '<h3>' . esc_html__( 'Combustible', 'gestion-parque-vehicular' ) . '</h3>';
        echo '<div class="gpv-widget-content">';
        echo '<div class="gpv-stat-card">';
        echo '<div class="gpv-stat-icon"><span class="dashicons dashicons-dashboard"></span></div>';
        echo '<div class="gpv-stat-details">';
        echo '<h4>' . esc_html(number_format($stats['fuel']['month_consumption'], 2)) . ' L</h4>';
        echo '<p>' . esc_html__( 'Consumo mensual', 'gestion-parque-vehicular' ) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<p>' . esc_html__( 'Consumo promedio', 'gestion-parque-vehicular' ) . ': ' . esc_html(number_format($stats['fuel']['average_consumption'], 2)) . ' km/L</p>';
        echo '</div>';
        echo '</div>';

        // Widget de mantenimientos
        echo '<div class="gpv-dashboard-widget">';
        echo '<h3>' . esc_html__( 'Mantenimientos', 'gestion-parque-vehicular' ) . '</h3>';
        echo '<div class="gpv-widget-content">';
        echo '<div class="gpv-stat-card">';
        echo '<div class="gpv-stat-icon"><span class="dashicons dashicons-admin-tools"></span></div>';
        echo '<div class="gpv-stat-details">';
        echo '<h4>' . esc_html($stats['maintenance']['pending']) . '</h4>';
        echo '<p>' . esc_html__( 'Mantenimientos pendientes', 'gestion-parque-vehicular' ) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<p>' . esc_html__( 'Próximos', 'gestion-parque-vehicular' ) . ': ' . esc_html($stats['maintenance']['upcoming']) . '</p>';
        echo '</div>';
        echo '</div>';

        echo '</div>'; // Fin dashboard widgets

        // Enlaces rápidos
        echo '<h3>' . esc_html__( 'Enlaces Rápidos', 'gestion-parque-vehicular' ) . '</h3>';
        echo '<ul class="admin-links">';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=gpv_vehiculos' ) ) . '">' . esc_html__( 'Gestionar Vehículos', 'gestion-parque-vehicular' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=gpv_movimientos' ) ) . '">' . esc_html__( 'Ver Movimientos Diarios', 'gestion-parque-vehicular' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=gpv_cargas' ) ) . '">' . esc_html__( 'Ver Cargas de Combustible', 'gestion-parque-vehicular' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=gpv_mantenimientos' ) ) . '">' . esc_html__( 'Gestionar Mantenimientos', 'gestion-parque-vehicular' ) . '</a></li>';
        echo '</ul>';

        echo '</div>'; // Fin wrap
    }

    /**
     * Muestra la página de mantenimientos
     */
    public function gpv_listado_mantenimientos() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'gpv_mantenimientos';
        $resultados = $wpdb->get_results( "SELECT m.*, v.siglas, v.nombre_vehiculo
                                         FROM $tabla AS m
                                         LEFT JOIN {$wpdb->prefix}gpv_vehiculos AS v ON m.vehiculo_id = v.id
                                         ORDER BY m.fecha_programada ASC" );

        echo '<div class="wrap">';
        echo '<h2>' . esc_html__( 'Mantenimientos Programados', 'gestion-parque-vehicular' ) . '</h2>';

        // Botón para agregar nuevo mantenimiento
        echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_mantenimientos&action=new')) . '" class="page-title-action">';
        echo esc_html__('Agregar Nuevo', 'gestion-parque-vehicular');
        echo '</a>';

        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__( 'ID', 'gestion-parque-vehicular' ) . '</th>';
        echo '<th>' . esc_html__( 'Vehículo', 'gestion-parque-vehicular' ) . '</th>';
        echo '<th>' . esc_html__( 'Tipo', 'gestion-parque-vehicular' ) . '</th>';
        echo '<th>' . esc_html__( 'Fecha Programada', 'gestion-parque-vehicular' ) . '</th>';
        echo '<th>' . esc_html__( 'Descripción', 'gestion-parque-vehicular' ) . '</th>';
        echo '<th>' . esc_html__( 'Estado', 'gestion-parque-vehicular' ) . '</th>';
        echo '<th>' . esc_html__( 'Acciones', 'gestion-parque-vehicular' ) . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        if( $resultados ) {
            foreach ( $resultados as $mantenimiento ) {
                // Determinar clase de fila según estado y fecha
                $row_class = '';
                if ($mantenimiento->estado === 'programado') {
                    $fecha_programada = new DateTime($mantenimiento->fecha_programada);
                    $hoy = new DateTime();
                    if ($fecha_programada < $hoy) {
                        $row_class = 'gpv-alerta-urgente';
                    } elseif ($fecha_programada->diff($hoy)->days <= 7) {
                        $row_class = 'gpv-alerta-proxima';
                    }
                } elseif ($mantenimiento->estado === 'completado') {
                    $row_class = 'gpv-completado';
                }

                echo '<tr class="' . esc_attr($row_class) . '">';
                echo '<td>' . esc_html( $mantenimiento->id ) . '</td>';
                echo '<td>' . esc_html( $mantenimiento->siglas . ' - ' . $mantenimiento->nombre_vehiculo ) . '</td>';
                echo '<td>' . esc_html( $mantenimiento->tipo ) . '</td>';
                echo '<td>' . esc_html( date_i18n(get_option('date_format'), strtotime($mantenimiento->fecha_programada)) ) . '</td>';
                echo '<td>' . esc_html( $mantenimiento->descripcion ) . '</td>';
                echo '<td>' . esc_html( ucfirst($mantenimiento->estado) ) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_mantenimientos&action=edit&id=' . $mantenimiento->id)) . '" class="button-secondary">';
                echo esc_html__('Editar', 'gestion-parque-vehicular');
                echo '</a> ';
                if ($mantenimiento->estado === 'programado') {
                    echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_mantenimientos&action=complete&id=' . $mantenimiento->id)) . '" class="button-primary">';
                    echo esc_html__('Marcar Completado', 'gestion-parque-vehicular');
                    echo '</a>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7">' . esc_html__( 'No hay mantenimientos registrados.', 'gestion-parque-vehicular' ) . '</td></tr>';
        }
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }

    /**
     * Muestra la página de configuración
     */
    public function gpv_pagina_configuracion() {
        global $GPV_Database;

        // Guardar cambios si se envió el formulario
        if (isset($_POST['gpv_save_settings']) && isset($_POST['gpv_nonce']) && wp_verify_nonce($_POST['gpv_nonce'], 'gpv_save_settings')) {
            // Procesar cada configuración
            if (isset($_POST['alerta_mantenimiento_dias'])) {
                $GPV_Database->update_setting('alerta_mantenimiento_dias', sanitize_text_field($_POST['alerta_mantenimiento_dias']));
            }

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
        $alerta_mantenimiento_dias = $GPV_Database->get_setting('alerta_mantenimiento_dias');
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

        // Días de antelación para alertas de mantenimiento
        echo '<tr>';
        echo '<th scope="row"><label for="alerta_mantenimiento_dias">' . esc_html__('Días de antelación para alertas', 'gestion-parque-vehicular') . '</label></th>';
        echo '<td><input type="number" name="alerta_mantenimiento_dias" id="alerta_mantenimiento_dias" value="' . esc_attr($alerta_mantenimiento_dias) . '" min="1" max="30" step="1" class="regular-text">';
        echo '<p class="description">' . esc_html__('Días de antelación para mostrar alertas de mantenimientos programados.', 'gestion-parque-vehicular') . '</p></td>';
        echo '</tr>';

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
    }
}

// Inicializar la clase
$gpv_admin = new GPV_Admin();
