<?php
// includes/class-gpv-shortcodes.php

if (! defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

require_once plugin_dir_path(__FILE__) . '../frontend/class-gpv-forms.php';
require_once plugin_dir_path(__FILE__) . '../frontend/class-gpv-frontend.php';
require_once plugin_dir_path(__FILE__) . '../frontend/views/form-retorno.php';

class GPV_Shortcodes
{
    private $forms;
    private $frontend;

    public function __construct()
    {
        $this->forms = new GPV_Forms();
        $this->frontend = new GPV_Frontend();
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

    public function formulario_retorno_shortcode()
    {
        return gpv_formulario_retorno();
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
add_shortcode('gpv_form_retorno', array($GPV_Shortcodes, 'formulario_retorno_shortcode'));
add_shortcode('gpv_listado_movimientos', array($GPV_Shortcodes, 'listado_movimientos_shortcode'));
