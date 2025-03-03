<?php

/**
 * Driver Dashboard REST API Endpoints
 *
 * @package GPV
 * @subpackage API
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GPV_Driver_Dashboard_API
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

        // Register REST API routes
        add_action('rest_api_init', array($this, 'register_driver_routes'));
    }

    /**
     * Register REST routes for driver dashboard
     */
    public function register_driver_routes()
    {
        // Route for driver dashboard overview
        register_rest_route('gpv/v1', '/driver/dashboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_dashboard_overview'),
            'permission_callback' => array($this, 'check_driver_permissions')
        ));

        // Route for assigned vehicles
        register_rest_route('gpv/v1', '/driver/vehicles', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_assigned_vehicles'),
            'permission_callback' => array($this, 'check_driver_permissions')
        ));

        // Route for recent movements
        register_rest_route('gpv/v1', '/driver/movements', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_recent_movements'),
            'permission_callback' => array($this, 'check_driver_permissions')
        ));

        // Route for fuel loads
        register_rest_route('gpv/v1', '/driver/fuel-loads', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_fuel_loads'),
            'permission_callback' => array($this, 'check_driver_permissions')
        ));

        // Route for upcoming maintenances
        register_rest_route('gpv/v1', '/driver/maintenances', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_upcoming_maintenances'),
            'permission_callback' => array($this, 'check_driver_permissions')
        ));

        // Route for new movement registration
        register_rest_route('gpv/v1', '/driver/movement', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_movement'),
            'permission_callback' => array($this, 'check_movement_registration_permissions')
        ));

        // Route for new fuel load registration
        register_rest_route('gpv/v1', '/driver/fuel-load', array(
            'methods' => 'POST',
            'callback' => array($this, 'register_fuel_load'),
            'permission_callback' => array($this, 'check_fuel_registration_permissions')
        ));
    }

    /**
     * Check driver dashboard permissions
     *
     * @return bool
     */
    public function check_driver_permissions()
    {
        return current_user_can('gpv_view_own_records') ||
            current_user_can('gpv_register_movements') ||
            current_user_can('gpv_register_fuel');
    }

    /**
     * Check movement registration permissions
     *
     * @return bool
     */
    public function check_movement_registration_permissions()
    {
        return current_user_can('gpv_register_movements');
    }

    /**
     * Check fuel registration permissions
     *
     * @return bool
     */
    public function check_fuel_registration_permissions()
    {
        return current_user_can('gpv_register_fuel');
    }

    /**
     * Get dashboard overview
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_dashboard_overview($request)
    {
        $user_id = get_current_user_id();

        // Prepare dashboard data
        $dashboard_data = array(
            'vehicles' => $this->get_assigned_vehicles_data(),
            'movements' => $this->get_recent_movements_data(),
            'fuel_loads' => $this->get_fuel_loads_data(),

            'summary' => $this->get_dashboard_summary($user_id)
        );

        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => $dashboard_data
        ), 200);
    }

    /**
     * Get assigned vehicles
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_assigned_vehicles($request)
    {
        $vehicles = $this->database->get_vehicles(array(
            'conductor_asignado' => $this->user_id,
            'estado' => array('disponible', 'en_uso')
        ));

        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => $vehicles
        ), 200);
    }

    /**
     * Get recent movements
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_recent_movements($request)
    {
        $limit = $request->get_param('limit') ?: 10;

        $movements = $this->database->get_movements(array(
            'conductor_id' => $this->user_id,
            'limit' => $limit,
            'orderby' => 'hora_salida',
            'order' => 'DESC'
        ));

        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => $movements
        ), 200);
    }

    /**
     * Get fuel loads
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_fuel_loads($request)
    {
        $limit = $request->get_param('limit') ?: 10;

        $fuel_loads = $this->database->get_fuels(array(
            'conductor_id' => $this->user_id,
            'limit' => $limit,
            'orderby' => 'fecha_carga',
            'order' => 'DESC'
        ));

        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => $fuel_loads
        ), 200);
    }



    /**
     * Register a new movement
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function register_movement($request)
    {
        // Validate required parameters
        $required_params = array(
            'vehiculo_id',
            'odometro_salida',
            'hora_salida'
        );

        foreach ($required_params as $param) {
            if (!$request->has_param($param)) {
                return new WP_Error(
                    'gpv_missing_param',
                    sprintf(__('Parámetro requerido faltante: %s', 'gestion-parque-vehicular'), $param),
                    array('status' => 400)
                );
            }
        }

        // Sanitize and prepare data
        $vehicle_id = intval($request->get_param('vehiculo_id'));
        $odometro_salida = floatval($request->get_param('odometro_salida'));
        $hora_salida = sanitize_text_field($request->get_param('hora_salida'));
        $proposito = $request->has_param('proposito') ?
            sanitize_textarea_field($request->get_param('proposito')) : '';

        // Get vehicle details
        $vehicle = $this->database->get_vehicle($vehicle_id);

        if (!$vehicle) {
            return new WP_Error(
                'gpv_vehicle_not_found',
                __('Vehículo no encontrado.', 'gestion-parque-vehicular'),
                array('status' => 404)
            );
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

            return new WP_REST_Response(array(
                'status' => 'success',
                'message' => __('Movimiento registrado correctamente.', 'gestion-parque-vehicular'),
                'data' => array('movement_id' => $result)
            ), 201);
        }

        return new WP_Error(
            'gpv_movement_registration_failed',
            __('Error al registrar el movimiento.', 'gestion-parque-vehicular'),
            array('status' => 500)
        );
    }

    /**
     * Register a new fuel load
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function register_fuel_load($request)
    {
        // Validate required parameters
        $required_params = array(
            'vehiculo_id',
            'odometro_carga',
            'litros_cargados',
            'precio'
        );

        foreach ($required_params as $param) {
            if (!$request->has_param($param)) {
                return new WP_Error(
                    'gpv_missing_param',
                    sprintf(__('Parámetro requerido faltante: %s', 'gestion-parque-vehicular'), $param),
                    array('status' => 400)
                );
            }
        }

        // Sanitize and prepare data
        $vehicle_id = intval($request->get_param('vehiculo_id'));
        $odometro_carga = floatval($request->get_param('odometro_carga'));
        $litros_cargados = floatval($request->get_param('litros_cargados'));
        $precio = floatval($request->get_param('precio'));
        $km_desde_ultima_carga = $request->has_param('km_desde_ultima_carga') ?
            floatval($request->get_param('km_desde_ultima_carga')) : 0;

        // Get vehicle details
        $vehicle = $this->database->get_vehicle($vehicle_id);

        if (!$vehicle) {
            return new WP_Error(
                'gpv_vehicle_not_found',
                __('Vehículo no encontrado.', 'gestion-parque-vehicular'),
                array('status' => 404)
            );
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
            // Update vehicle's fuel level and odometer
            // Update vehicle's fuel level (in liters) and odometer
            $nuevo_nivel = min($vehicle->capacidad_tanque, $vehicle->nivel_combustible + $litros_cargados);

            return new WP_REST_Response(array(
                'status' => 'success',
                'message' => __('Carga de combustible registrada correctamente.', 'gestion-parque-vehicular'),
                'data' => array('fuel_load_id' => $result)
            ), 201);
        }

        return new WP_Error(
            'gpv_fuel_load_registration_failed',
            __('Error al registrar la carga de combustible.', 'gestion-parque-vehicular'),
            array('status' => 500)
        );
    }

    /**
     * Get dashboard summary data
     *
     * @param int $user_id
     * @return array
     */
    private function get_dashboard_summary($user_id)
    {
        // Get assigned vehicles
        $vehicles = $this->database->get_vehicles(array(
            'conductor_asignado' => $user_id
        ));

        // Calculate summary metrics
        $summary = array(
            'total_vehicles' => count($vehicles),
            'total_movements' => $this->get_total_movements($user_id),
            'total_fuel_loads' => $this->get_total_fuel_loads($user_id),
            'total_distance' => $this->get_total_distance($user_id),
            'total_fuel_consumption' => $this->get_total_fuel_consumption($user_id)
        );

        return $summary;
    }

    /**
     * Get total number of movements for a driver
     *
     * @param int $user_id
     * @return int
     */
    private function get_total_movements($user_id)
    {
        $movements = $this->database->get_movements(array(
            'conductor_id' => $user_id
        ));

        return count($movements);
    }

    /**
     * Get total number of fuel loads for a driver
     *
     * @param int $user_id
     * @return int
     */
    private function get_total_fuel_loads($user_id)
    {
        $fuel_loads = $this->database->get_fuels(array(
            'conductor_id' => $user_id
        ));

        return count($fuel_loads);
    }

    /**
     * Calculate total distance traveled by a driver
     *
     * @param int $user_id
     * @return float
     */
    private function get_total_distance($user_id)
    {
        $movements = $this->database->get_movements(array(
            'conductor_id' => $user_id
        ));

        $total_distance = 0;
        foreach ($movements as $movement) {
            if (!empty($movement->distancia_recorrida)) {
                $total_distance += floatval($movement->distancia_recorrida);
            }
        }

        return round($total_distance, 2);
    }

    /**
     * Calculate total fuel consumption for a driver
     *
     * @param int $user_id
     * @return float
     */
    private function get_total_fuel_consumption($user_id)
    {
        $fuel_loads = $this->database->get_fuels(array(
            'conductor_id' => $user_id
        ));

        $total_fuel = 0;
        foreach ($fuel_loads as $fuel) {
            $total_fuel += floatval($fuel->litros_cargados);
        }

        return round($total_fuel, 2);
    }


    /**
     * Get vehicle data for assigned vehicles
     *
     * @return array
     */
    private function get_assigned_vehicles_data()
    {
        return $this->database->get_vehicles(array(
            'conductor_asignado' => $this->user_id,
            'estado' => array('disponible', 'en_uso')
        ));
    }

    /**
     * Get recent movements data
     *
     * @return array
     */
    private function get_recent_movements_data()
    {
        return $this->database->get_movements(array(
            'conductor_id' => $this->user_id,
            'limit' => 5,
            'orderby' => 'hora_salida',
            'order' => 'DESC'
        ));
    }

    /**
     * Get fuel loads data
     *
     * @return array
     */
    private function get_fuel_loads_data()
    {
        return $this->database->get_fuels(array(
            'conductor_id' => $this->user_id,
            'limit' => 5,
            'orderby' => 'fecha_carga',
            'order' => 'DESC'
        ));
    }
}

// Inicializar la API del dashboard del conductor
$gpv_driver_dashboard_api = new GPV_Driver_Dashboard_API();
