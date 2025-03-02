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
        global $GPV_Database;

        // Asegurar que la base de datos está disponible
        if (!$GPV_Database) {
            if (!class_exists('GPV_Database')) {
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-gpv-database.php';
            }
            $GPV_Database = new GPV_Database();
        }

        $this->forms = new GPV_Forms();

        // Verificar que la base de datos se inicializó correctamente
        if (!$this->forms->get_database()) {
            $this->forms->set_database($GPV_Database);

            // Verificación adicional
            if (!$this->forms->get_database()) {
                error_log('GPV Shortcodes: No se pudo inicializar la base de datos para GPV_Forms');
            }
        }

        $this->frontend = new GPV_Frontend();

        // Verificar que la base de datos se inicializó correctamente para el frontend
        if (!$this->frontend->get_database()) {
            $this->frontend->set_database($GPV_Database);

            // Verificación adicional
            if (!$this->frontend->get_database()) {
                error_log('GPV Shortcodes: No se pudo inicializar la base de datos para GPV_Frontend');
            }
        }
    }

    public function formulario_movimiento_shortcode()
    {
        return $this->forms->formulario_movimiento();
    }

    public function formulario_carga_shortcode()
    {
        return $this->forms->formulario_carga();
    }

    public function formulario_vehiculo_shortcode()
    {
        return $this->forms->formulario_vehiculo();
    }

    public function listado_movimientos_shortcode()
    {
        return $this->frontend->mostrar_listado_movimientos();
    }
}

$GPV_Shortcodes = new GPV_Shortcodes();
add_shortcode('gpv_form_movimiento', array($GPV_Shortcodes, 'formulario_movimiento_shortcode'));
add_shortcode('gpv_form_carga', array($GPV_Shortcodes, 'formulario_carga_shortcode'));
add_shortcode('gpv_form_vehiculo', array($GPV_Shortcodes, 'formulario_vehiculo_shortcode'));
add_shortcode('gpv_listado_movimientos', array($GPV_Shortcodes, 'listado_movimientos_shortcode'));
