<?php
/**
 * Clase para manejar el dashboard
 */
class GPV_Dashboard {

    /**
     * Constructor
     */
    public function __construct() {
        // Registrar shortcodes para paneles
        add_shortcode('gpv_dashboard', array($this, 'render_dashboard'));
        add_shortcode('gpv_driver_panel', array($this, 'render_driver_panel'));
        add_shortcode('gpv_consultant_panel', array($this, 'render_consultant_panel'));

        // Enqueue scripts específicos para dashboard
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dashboard_scripts'));
    }

    /**
     * Cargar scripts para dashboard
     */
    public function enqueue_dashboard_scripts() {
        global $post;

        // Verificar si estamos en una página con shortcode del plugin
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'gpv_dashboard') ||
            has_shortcode($post->post_content, 'gpv_driver_panel') ||
            has_shortcode($post->post_content, 'gpv_consultant_panel')
        )) {
            // Cargar React y ReactDOM
            wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', array(), '17.0.2', true);
            wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', array('react'), '17.0.2', true);

            // Cargar Chart.js para gráficos
            wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', array(), '3.7.1', true);

            // Cargar estilos específicos
            wp_enqueue_style('gpv-dashboard-style', GPV_PLUGIN_URL . 'assets/css/dashboard.css', array(), GPV_VERSION);

            // Cargar scripts específicos
            wp_enqueue_script('gpv-dashboard-script', GPV_PLUGIN_URL . 'assets/js/dashboard.js', array('jquery', 'react', 'react-dom', 'chartjs'), GPV_VERSION, true);
        }
    }

    /**
     * Renderizar dashboard general
     */
    public function render_dashboard($atts) {
        // Verificar permisos
        if (!current_user_can('gpv_view_dashboard')) {
            return '<div class="gpv-error">' . __('No tienes permiso para ver este dashboard.', 'gpv-pwa') . '</div>';
        }

        // Iniciar buffer
        ob_start();

        // Container para la aplicación React
        echo '<div id="gpv-app-container" data-panel-type="dashboard"></div>';

        // Retornar contenido
        return ob_get_clean();
    }

    /**
     * Renderizar panel de conductor
     */
    public function render_driver_panel($atts) {
        // Verificar permisos
        if (!current_user_can('gpv_register_movements') && !current_user_can('gpv_register_fuel')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gpv-pwa') . '</div>';
        }

        // Iniciar buffer
        ob_start();

        // Container para la aplicación React
        echo '<div id="gpv-app-container" data-panel-type="driver"></div>';

        // Retornar contenido
        return ob_get_clean();
    }

    /**
     * Renderizar panel de consultor
     */
    public function render_consultant_panel($atts) {
        // Verificar permisos
        if (!current_user_can('gpv_view_dashboard') && !current_user_can('gpv_generate_reports')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gpv-pwa') . '</div>';
        }

        // Iniciar buffer
        ob_start();

        // Container para la aplicación React
        echo '<div id="gpv-app-container" data-panel-type="consultant"></div>';

        // Retornar contenido
        return ob_get_clean();
    }

    /**
     * Obtener estadísticas para el dashboard
     */
    public function get_dashboard_stats() {
        global $wpdb;
        $database = new GPV_Database();

        // Fechas para filtros
        $today = date('Y-m-d');
        $month_start = date('Y-m-01');
        $last_month_start = date('Y-m-01', strtotime('-1 month'));
        $last_month_end = date('Y-m-t', strtotime('-1 month'));

        // Estadísticas de vehículos
        $vehicles_stats = array(
            'total' => 0,
            'available' => 0,
            'in_use' => 0,
            'maintenance' => 0,
            'fuel_level' => array(
                'low' => 0,
                'medium' => 0,
                'high' => 0
            )
        );

        $vehicles = $database->get_vehicles();
        $vehicles_stats['total'] = count($vehicles);

        foreach ($vehicles as $vehicle) {
            // Estado
            if ($vehicle->estado === 'disponible') {
                $vehicles_stats['available']++;
            } elseif ($vehicle->estado === 'en_uso') {
                $vehicles_stats['in_use']++;
            } elseif ($vehicle->estado === 'mantenimiento') {
                $vehicles_stats['maintenance']++;
            }

            // Nivel de combustible
            if ($vehicle->nivel_combustible <= 20) {
                $vehicles_stats['fuel_level']['low']++;
            } elseif ($vehicle->nivel_combustible <= 60) {
                $vehicles_stats['fuel_level']['medium']++;
            } else {
                $vehicles_stats['fuel_level']['high']++;
            }
        }

        // Estadísticas de movimientos
        $movements_stats = array(
            'today' => 0,
            'month' => 0,
            'total_distance' => 0,
            'active' => 0
        );

        // Movimientos de hoy
        $movements_today = $database->get_movements(array(
            'fecha_desde' => $today . ' 00:00:00',
            'fecha_hasta' => $today . ' 23:59:59'
        ));
        $movements_stats['today'] = count($movements_today);

        // Movimientos del mes
        $movements_month = $database->get_movements(array(
            'fecha_desde' => $month_start . ' 00:00:00',
            'fecha_hasta' => $today . ' 23:59:59'
        ));
        $movements_stats['month'] = count($movements_month);

        // Distancia total recorrida este mes
        foreach ($movements_month as $movement) {
            if ($movement->distancia_recorrida) {
                $movements_stats['total_distance'] += $movement->distancia_recorrida;
            }
        }

        // Movimientos activos
        $active_movements = $database->get_movements(array(
            'estado' => 'en_progreso'
        ));
        $movements_stats['active'] = count($active_movements);

        // Estadísticas de combustible
        $fuel_stats = array(
            'month_consumption' => 0,
            'month_cost' => 0,
            'last_month_consumption' => 0,
            'last_month_cost' => 0,
            'average_consumption' => 0,
            'total_year' => 0
        );

        // Consumo de este mes
        $fuels_month = $database->get_fuels(array(
            'fecha_desde' => $month_start,
            'fecha_hasta' => $today
        ));

        foreach ($fuels_month as $fuel) {
            $fuel_stats['month_consumption'] += $fuel->litros_cargados;
            $fuel_stats['month_cost'] += ($fuel->litros_cargados * $fuel->precio);
        }

        // Consumo del mes pasado
        $fuels_last_month = $database->get_fuels(array(
            'fecha_desde' => $last_month_start,
            'fecha_hasta' => $last_month_end
        ));

        foreach ($fuels_last_month as $fuel) {
            $fuel_stats['last_month_consumption'] += $fuel->litros_cargados;
            $fuel_stats['last_month_cost'] += ($fuel->litros_cargados * $fuel->precio);
        }

        // Calcular consumo promedio
        if ($movements_stats['total_distance'] > 0 && $fuel_stats['month_consumption'] > 0) {
            $fuel_stats['average_consumption'] = $movements_stats['total_distance'] / $fuel_stats['month_consumption'];
        }

        // Consumo total del año
        $year_start = date('Y-01-01');
        $fuels_year = $database->get_fuels(array(
            'fecha_desde' => $year_start,
            'fecha_hasta' => $today
        ));

        foreach ($fuels_year as $fuel) {
            $fuel_stats['total_year'] += ($fuel->litros_cargados * $fuel->precio);
        }

        // Estadísticas de mantenimiento
        $maintenance_stats = array(
            'pending' => 0,
            'upcoming' => 0,
            'completed' => 0,
            'month_cost' => 0,
            'total_year' => 0
        );

        // Mantenimientos pendientes
        $pending_maintenance = $database->get_maintenances(array(
            'estado' => 'programado',
            'fecha_hasta' => $today
        ));
        $maintenance_stats['pending'] = count($pending_maintenance);

        // Mantenimientos próximos
        $upcoming_maintenance = $database->get_maintenances(array(
            'estado' => 'programado',
            'fecha_desde' => date('Y-m-d', strtotime('+1 day')),
            'fecha_hasta' => date('Y-m-d', strtotime('+7 days'))
        ));
        $maintenance_stats['upcoming'] = count($upcoming_maintenance);

        // Mantenimientos completados este mes
        $completed_maintenance = $database->get_maintenances(array(
            'estado' => 'completado',
            'fecha_desde' => $month_start,
            'fecha_hasta' => $today
        ));
        $maintenance_stats['completed'] = count($completed_maintenance);

        // Costo de mantenimientos este mes
        foreach ($completed_maintenance as $maintenance) {
            $maintenance_stats['month_cost'] += $maintenance->costo;
        }

        // Costo total de mantenimientos este año
        $year_maintenance = $database->get_maintenances(array(
            'estado' => 'completado',
            'fecha_desde' => $year_start,
            'fecha_hasta' => $today
        ));

        foreach ($year_maintenance as $maintenance) {
            $maintenance_stats['total_year'] += $maintenance->costo;
        }

        // Retornar todas las estadísticas
        return array(
            'vehicles' => $vehicles_stats,
            'movements' => $movements_stats,
            'fuel' => $fuel_stats,
            'maintenance' => $maintenance_stats,
            'last_update' => current_time('mysql')
        );
    }
}
