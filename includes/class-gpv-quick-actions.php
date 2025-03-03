<?php

/**
 * Quick Actions Handler for Vehicle Fleet Management
 *
 * @package GPV
 * @subpackage Actions
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GPV_Quick_Actions
{
    /**
     * Database instance
     *
     * @var GPV_Database
     */
    private $database;

    /**
     * Current user ID
     *
     * @var int
     */
    private $user_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize database
        global $GPV_Database;
        $this->database = $GPV_Database;

        // Get current user ID
        $this->user_id = get_current_user_id();

        // Add action hooks
        add_action('init', array($this, 'handle_quick_actions'));
    }

    /**
     * Handle quick actions from driver dashboard
     */
    public function handle_quick_actions()
    {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return;
        }

        // Check basic permissions
        if (
            !current_user_can('gpv_register_movements') &&
            !current_user_can('gpv_register_fuel')
        ) {
            return;
        }

        // Check for nonce and action
        if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'gpv_quick_action')) {
            return;
        }

        // Determine action
        $action = sanitize_text_field($_POST['action']);

        switch ($action) {
            case 'register_movement':
                $this->handle_movement_registration();
                break;
            case 'register_fuel_load':
                $this->handle_fuel_load_registration();
                break;
        }
    }

    /**
     * Handle movement registration
     */
    private function handle_movement_registration()
    {
        // Validate required fields
        $required_fields = array(
            'vehiculo_id',
            'odometro_salida',
            'hora_salida'
        );

        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $this->add_error_message(__('Por favor, complete todos los campos requeridos.', 'gestion-parque-vehicular'));
                wp_redirect(wp_get_referer());
                exit;
            }
        }

        // Sanitize and validate input
        $vehicle_id = intval($_POST['vehiculo_id']);
        $odometro_salida = floatval($_POST['odometro_salida']);
        $hora_salida = sanitize_text_field($_POST['hora_salida']);
        $proposito = isset($_POST['proposito']) ?
            sanitize_textarea_field($_POST['proposito']) : '';

        // Get vehicle details
        $vehicle = $this->database->get_vehicle($vehicle_id);

        if (!$vehicle) {
            $this->add_error_message(__('Vehículo no encontrado.', 'gestion-parque-vehicular'));
            wp_redirect(wp_get_referer());
            exit;
        }

        // Prepare movement data
        $movement_data = array(
            'vehiculo_id' => $vehicle_id,
            'vehiculo_siglas' => $vehicle->siglas,
            'vehiculo_nombre' => $vehicle->nombre_vehiculo,
            'odometro_salida' => $odometro_salida,
            'hora_salida' => date('Y-m-d H:i:s', strtotime($hora_salida)),
            'conductor_id' => $this->user_id,
            'conductor' => wp_get_current_user()->display_name,
            'estado' => 'en_progreso',
            'proposito' => $proposito
        );

        // Insert movement
        $result = $this->database->insert_movement($movement_data);

        if ($result) {
            // Update vehicle status
            $this->database->update_vehicle($vehicle_id, array(
                'estado' => 'en_uso',
                'odometro_actual' => $odometro_salida
            ));

            $this->add_success_message(__('Movimiento registrado correctamente.', 'gestion-parque-vehicular'));
        } else {
            $this->add_error_message(__('Error al registrar el movimiento.', 'gestion-parque-vehicular'));
        }

        // Redirect back
        wp_redirect(wp_get_referer());
        exit;
    }

    /**
     * Handle fuel load registration
     */
    private function handle_fuel_load_registration()
    {
        // Validate required fields
        $required_fields = array(
            'vehiculo_id',
            'odometro_carga',
            'litros_cargados',
            'precio'
        );

        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                $this->add_error_message(__('Por favor, complete todos los campos requeridos.', 'gestion-parque-vehicular'));
                wp_redirect(wp_get_referer());
                exit;
            }
        }

        // Sanitize and validate input
        $vehicle_id = intval($_POST['vehiculo_id']);
        $odometro_carga = floatval($_POST['odometro_carga']);
        $litros_cargados = floatval($_POST['litros_cargados']);
        $precio = floatval($_POST['precio']);
        $km_desde_ultima_carga = isset($_POST['km_desde_ultima_carga']) ?
            floatval($_POST['km_desde_ultima_carga']) : 0;

        // Get vehicle details
        $vehicle = $this->database->get_vehicle($vehicle_id);

        if (!$vehicle) {
            $this->add_error_message(__('Vehículo no encontrado.', 'gestion-parque-vehicular'));
            wp_redirect(wp_get_referer());
            exit;
        }

        // Prepare fuel load data
        $fuel_data = array(
            'vehiculo_id' => $vehicle_id,
            'vehiculo_siglas' => $vehicle->siglas,
            'vehiculo_nombre' => $vehicle->nombre_vehiculo,
            'odometro_carga' => $odometro_carga,
            'litros_cargados' => $litros_cargados,
            'precio' => $precio,
            'km_desde_ultima_carga' => $km_desde_ultima_carga,
            'conductor_id' => $this->user_id,
            'fecha_carga' => current_time('mysql')
        );

        // Insert fuel load
        $result = $this->database->insert_fuel($fuel_data);

        if ($result) {
            // Update vehicle's fuel level (in liters) and odometer
            $nuevo_nivel = min($vehicle->capacidad_tanque, $vehicle->nivel_combustible + $litros_cargados);

            $this->database->update_vehicle($vehicle_id, array(
                'odometro_actual' => $odometro_carga,
                'nivel_combustible' => $nuevo_nivel
            ));

            $this->add_success_message(__('Carga de combustible registrada correctamente.', 'gestion-parque-vehicular'));
        } else {
            $this->add_error_message(__('Error al registrar la carga de combustible.', 'gestion-parque-vehicular'));
        }

        // Redirect back
        wp_redirect(wp_get_referer());
        exit;
    }

    /**
     * Add success message to user session
     *
     * @param string $message Success message
     */
    private function add_success_message($message)
    {
        add_filter('wp_redirect', function ($redirect_url) use ($message) {
            $_SESSION['gpv_success_message'] = $message;
            return $redirect_url;
        });
    }

    /**
     * Add error message to user session
     *
     * @param string $message Error message
     */
    private function add_error_message($message)
    {
        add_filter('wp_redirect', function ($redirect_url) use ($message) {
            $_SESSION['gpv_error_message'] = $message;
            return $redirect_url;
        });
    }
}

// Initialize Quick Actions
$gpv_quick_actions = new GPV_Quick_Actions();
