<?php
if (! defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

require_once plugin_dir_path(__FILE__) . '../frontend/class-gpv-forms.php';
require_once plugin_dir_path(__FILE__) . '../frontend/class-gpv-frontend.php';

class GPV_Shortcodes
{
    private $forms;
    private $frontend;

    public function __construct()
    {
        $this->forms = new GPV_Forms();
        $this->frontend = new GPV_Frontend();

        // Registrar todos los shortcodes
        $this->register_shortcodes();
    }

    /**
     * Registrar todos los shortcodes del plugin
     */
    private function register_shortcodes()
    {
        // Formularios
        add_shortcode('gpv_form_movimiento', array($this, 'formulario_movimiento_shortcode'));
        add_shortcode('gpv_form_carga', array($this, 'formulario_carga_shortcode'));
        add_shortcode('gpv_form_vehiculo', array($this, 'formulario_vehiculo_shortcode'));

        // Listados
        add_shortcode('gpv_listado_movimientos', array($this, 'listado_movimientos_shortcode'));

        // Nuevos shortcodes para dashboards
        add_shortcode('gpv_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('gpv_driver_panel', array($this, 'driver_panel_shortcode'));
        add_shortcode('gpv_consultant_panel', array($this, 'consultant_panel_shortcode'));
        add_shortcode('gpv_driver_dashboard', array($this, 'driver_dashboard_shortcode'));
    }

    /**
     * Shortcode para formulario de movimiento
     */
    public function formulario_movimiento_shortcode($atts = [])
    {
        // Pasar atributos para personalización
        $args = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'assigned_only' => true
        ), $atts);

        return $this->forms->formulario_movimiento($args);
    }

    /**
     * Shortcode para formulario de carga de combustible
     */
    public function formulario_carga_shortcode()
    {
        return $this->forms->formulario_carga();
    }

    /**
     * Shortcode para formulario de vehículo
     */
    public function formulario_vehiculo_shortcode()
    {
        return $this->forms->formulario_vehiculo();
    }

    /**
     * Shortcode para listado de movimientos
     */
    public function listado_movimientos_shortcode()
    {
        return $this->frontend->mostrar_listado_movimientos();
    }

    /**
     * Shortcode para dashboard principal
     */
    public function dashboard_shortcode()
    {
        // Verificar permisos
        if (!current_user_can('gpv_view_dashboard')) {
            return '<div class="gpv-error">' . __('No tienes permiso para ver este dashboard.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para aplicación React
        return '<div id="gpv-app-container" data-panel-type="dashboard"></div>';
    }

    /**
     * Shortcode para panel de conductor
     */
    public function driver_panel_shortcode()
    {
        // Verificar permisos
        if (!current_user_can('gpv_register_movements') && !current_user_can('gpv_register_fuel')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para aplicación React
        return '<div id="gpv-app-container" data-panel-type="driver"></div>';
    }

    /**
     * Shortcode para panel de consultor
     */
    public function consultant_panel_shortcode()
    {
        // Verificar permisos
        if (!current_user_can('gpv_view_dashboard') && !current_user_can('gpv_generate_reports')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para aplicación React
        return '<div id="gpv-app-container" data-panel-type="consultant"></div>';
    }

    /**
     * Shortcode para dashboard de conductor
     */
    public function driver_dashboard_shortcode()
    {
        // Verificar permisos del conductor
        if (!current_user_can('gpv_register_movements') && !current_user_can('gpv_register_fuel')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para React
        return '<div id="gpv-driver-dashboard"></div>';
    }
}

// Inicializar los shortcodes
$GPV_Shortcodes = new GPV_Shortcodes();
