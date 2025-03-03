<?php

/**
 * API REST para el plugin
 */
class GPV_API
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Registrar rutas de la API
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Registrar rutas de la API
     */
    public function register_routes()
    {
        // Namespace para todas las rutas
        $namespace = 'gpv/v1';

        // Ruta para vehículos
        register_rest_route($namespace, '/vehicles', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_vehicles'),
                'permission_callback' => array($this, 'check_vehicles_read_permission'),
            ),
            array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_vehicle'),
                'permission_callback' => array($this, 'check_vehicles_create_permission'),
            )
        ));

        register_rest_route($namespace, '/vehicles/(?P<id>\d+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_vehicle'),
                'permission_callback' => array($this, 'check_vehicles_read_permission'),
            ),
            array(
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_vehicle'),
                'permission_callback' => array($this, 'check_vehicles_update_permission'),
            ),
            array(
                'methods'  => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_vehicle'),
                'permission_callback' => array($this, 'check_vehicles_delete_permission'),
            )
        ));

        // Ruta para movimientos
        register_rest_route($namespace, '/movements', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_movements'),
                'permission_callback' => array($this, 'check_movements_read_permission'),
            ),
            array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_movement'),
                'permission_callback' => array($this, 'check_movements_create_permission'),
            )
        ));

        register_rest_route($namespace, '/movements/(?P<id>\d+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_movement'),
                'permission_callback' => array($this, 'check_movements_read_permission'),
            ),
            array(
                'methods'  => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_movement'),
                'permission_callback' => array($this, 'check_movements_update_permission'),
            )
        ));

        // Ruta para cargas de combustible
        register_rest_route($namespace, '/fuels', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_fuels'),
                'permission_callback' => array($this, 'check_fuels_read_permission'),
            ),
            array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_fuel'),
                'permission_callback' => array($this, 'check_fuels_create_permission'),
            )
        ));



        // Ruta para dashboard
        register_rest_route($namespace, '/dashboard', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_dashboard_data'),
                'permission_callback' => array($this, 'check_dashboard_permission'),
            )
        ));

        // Ruta para estadísticas
        register_rest_route($namespace, '/stats/(?P<type>[a-zA-Z0-9-]+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_stats'),
                'permission_callback' => array($this, 'check_stats_permission'),
            )
        ));

        // Ruta para sincronización offline
        register_rest_route($namespace, '/sync', array(
            array(
                'methods'  => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'sync_offline_data'),
                'permission_callback' => array($this, 'check_sync_permission'),
            )
        ));
    }

    /**
     * Verificar permisos para vehículos
     */
    public function check_vehicles_read_permission()
    {
        return current_user_can('gpv_view_vehicles') ||
            current_user_can('gpv_manage_vehicles') ||
            current_user_can('gpv_view_assigned_vehicles');
    }

    public function check_vehicles_create_permission()
    {
        return current_user_can('gpv_manage_vehicles');
    }

    public function check_vehicles_update_permission()
    {
        return current_user_can('gpv_manage_vehicles');
    }

    public function check_vehicles_delete_permission()
    {
        return current_user_can('gpv_manage_vehicles');
    }

    /**
     * Verificar permisos para movimientos
     */
    public function check_movements_read_permission()
    {
        return current_user_can('gpv_view_movements') ||
            current_user_can('gpv_manage_movements') ||
            current_user_can('gpv_view_own_records');
    }

    public function check_movements_create_permission()
    {
        return current_user_can('gpv_register_movements') ||
            current_user_can('gpv_manage_movements');
    }

    public function check_movements_update_permission()
    {
        return current_user_can('gpv_manage_movements') ||
            (current_user_can('gpv_register_movements') && $this->is_own_record('movements', $this->get_id_from_request()));
    }

    /**
     * Verificar permisos para cargas de combustible
     */
    public function check_fuels_read_permission()
    {
        return current_user_can('gpv_view_fuel') ||
            current_user_can('gpv_manage_fuel') ||
            current_user_can('gpv_view_own_records');
    }

    public function check_fuels_create_permission()
    {
        return current_user_can('gpv_register_fuel') ||
            current_user_can('gpv_manage_fuel');
    }



    /**
     * Verificar permisos para dashboard
     */
    public function check_dashboard_permission()
    {
        return current_user_can('gpv_view_dashboard');
    }

    /**
     * Verificar permisos para estadísticas
     */
    public function check_stats_permission()
    {
        return current_user_can('gpv_generate_reports') ||
            current_user_can('gpv_view_dashboard');
    }

    /**
     * Verificar permisos para sincronización
     */
    public function check_sync_permission()
    {
        return is_user_logged_in();
    }

    /**
     * Verificar si un registro pertenece al usuario actual
     */
    private function is_own_record($table, $id)
    {
        global $wpdb;
        $user_id = get_current_user_id();

        if ($table === 'movements') {
            $tabla = $wpdb->prefix . 'gpv_movimientos';
            $result = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE id = %d AND conductor_id = %d",
                    $id,
                    $user_id
                )
            );
        } else if ($table === 'fuels') {
            $tabla = $wpdb->prefix . 'gpv_cargas';
            $result = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $tabla WHERE id = %d AND conductor_id = %d",
                    $id,
                    $user_id
                )
            );
        } else {
            return false;
        }

        return $result > 0;
    }

    /**
     * Obtener ID desde la request
     */
    private function get_id_from_request()
    {
        return isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
    }

    /**
     * Obtener vehículos
     */
    public function get_vehicles($request)
    {
        global $wpdb;
        $database = new GPV_Database();

        // Obtener parámetros de la solicitud
        $params = $request->get_params();

        // Filtrar por usuario si es operador
        if (current_user_can('gpv_view_assigned_vehicles') && !current_user_can('gpv_view_vehicles')) {
            $user_id = get_current_user_id();
            $params['conductor_asignado'] = $user_id;
        }

        $vehicles = $database->get_vehicles($params);

        if (empty($vehicles)) {
            return new WP_REST_Response(array(
                'status' => 'success',
                'data' => array()
            ), 200);
        }

        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => $vehicles
        ), 200);
    }

    /**
     * Obtener un vehículo específico
     */
    public function get_vehicle($request)
    {
        $database = new GPV_Database();
        $id = $request['id'];

        $vehicle = $database->get_vehicle($id);

        if (!$vehicle) {
            return new WP_Error(
                'gpv_not_found',
                __('Vehículo no encontrado', 'gpv-pwa'),
                array('status' => 404)
            );
        }

        // Verificar acceso si es operador
        if (current_user_can('gpv_view_assigned_vehicles') && !current_user_can('gpv_view_vehicles')) {
            $user_id = get_current_user_id();
            if ($vehicle->conductor_asignado != $user_id) {
                return new WP_Error(
                    'gpv_forbidden',
                    __('No tienes permiso para ver este vehículo', 'gpv-pwa'),
                    array('status' => 403)
                );
            }
        }

        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => $vehicle
        ), 200);
    }

    /**
     * Crear un nuevo vehículo
     */
    /**
     * Crear un nuevo vehículo
     */
    public function create_vehicle($request)
    {
        global $wpdb; // Agregar esta línea
        $database = new GPV_Database();
        $params = $request->get_params();

        // Validar campos requeridos
        if (empty($params['siglas']) || empty($params['nombre_vehiculo'])) {
            return new WP_Error(
                'gpv_missing_fields',
                __('Faltan campos requeridos', 'gpv-pwa'),
                array('status' => 400)
            );
        }

        // Insertar vehículo
        $result = $database->insert_vehicle($params);

        if (!$result) {
            return new WP_Error(
                'gpv_creation_failed',
                __('Error al crear el vehículo', 'gpv-pwa'),
                array('status' => 500)
            );
        }

        $new_id = $wpdb->insert_id;
        $vehicle = $database->get_vehicle($new_id);

        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => __('Vehículo creado correctamente', 'gpv-pwa'),
            'data' => $vehicle
        ), 201);
    }

    /**
     * Actualizar un vehículo
     */
    public function update_vehicle($request)
    {
        $database = new GPV_Database();
        $id = $request['id'];
        $params = $request->get_params();

        // Verificar que existe
        $vehicle = $database->get_vehicle($id);

        if (!$vehicle) {
            return new WP_Error(
                'gpv_not_found',
                __('Vehículo no encontrado', 'gpv-pwa'),
                array('status' => 404)
            );
        }

        // Actualizar vehículo
        $result = $database->update_vehicle($id, $params);

        if ($result === false) {
            return new WP_Error(
                'gpv_update_failed',
                __('Error al actualizar el vehículo', 'gpv-pwa'),
                array('status' => 500)
            );
        }

        $updated_vehicle = $database->get_vehicle($id);

        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => __('Vehículo actualizado correctamente', 'gpv-pwa'),
            'data' => $updated_vehicle
        ), 200);
    }

    /**
     * Obtener movimientos
     */
    public function get_movements($request)
    {
        $database = new GPV_Database();

        // Obtener parámetros de la solicitud
        $params = $request->get_params();

        // Filtrar por usuario si es operador
        if (current_user_can('gpv_view_own_records') && !current_user_can('gpv_view_movements')) {
            $user_id = get_current_user_id();
            $params['conductor_id'] = $user_id;
        }

        $movements = $database->get_movements($params);

        return new WP_REST_Response(array(
            'status' => 'success',
            'data' => $movements
        ), 200);
    }

    /**
     * Crear un nuevo movimiento
     */
    public function create_movement($request)
    {
        $database = new GPV_Database();
        $params = $request->get_params();

        // Obtener datos del vehículo
        $vehicle_id = isset($params['vehiculo_id']) ? intval($params['vehiculo_id']) : 0;
        $vehicle = $database->get_vehicle($vehicle_id);

        if (!$vehicle) {
            return new WP_Error(
                'gpv_invalid_vehicle',
                __('Vehículo no válido', 'gpv-pwa'),
                array('status' => 400)
            );
        }

        // Verificar si el conductor tiene acceso
        if (current_user_can('gpv_register_movements') && !current_user_can('gpv_manage_movements')) {
            $user_id = get_current_user_id();

            // Solo puede registrar movimientos para vehículos asignados
            if ($vehicle->conductor_asignado != $user_id && $vehicle->conductor_asignado != 0) {
                return new WP_Error(
                    'gpv_unauthorized',
                    __('No tienes permiso para registrar movimientos para este vehículo', 'gpv-pwa'),
                    array('status' => 403)
                );
            }

            // Asegurarse de que el conductor_id sea el usuario actual
            $params['conductor_id'] = $user_id;

            // Obtener datos del usuario
            $user = get_userdata($user_id);
            $params['conductor'] = $user->display_name;
        }

        // Completar datos del vehículo
        $params['vehiculo_siglas'] = $vehicle->siglas;
        $params['vehiculo_nombre'] = $vehicle->nombre_vehiculo;

        // Insertar movimiento
        $result = $database->insert_movement($params);

        if (!$result) {
            return new WP_Error(
                'gpv_creation_failed',
                __('Error al registrar el movimiento', 'gpv-pwa'),
                array('status' => 500)
            );
        }

        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => __('Movimiento registrado correctamente', 'gpv-pwa'),
            'data' => array(
                'id' => $result
            )
        ), 201);
    }

    /**
     * Actualizar un movimiento (por ejemplo, al regresar)
     */
    public function update_movement($request)
    {
        $database = new GPV_Database();
        $id = $request['id'];
        $params = $request->get_params();

        // Verificar que existe
        $movement = $database->get_movement($id);

        if (!$movement) {
            return new WP_Error(
                'gpv_not_found',
                __('Movimiento no encontrado', 'gpv-pwa'),
                array('status' => 404)
            );
        }

        // Si registra entrada, calcular distancia y consumo
        if (isset($params['odometro_entrada']) && $movement->estado === 'en_progreso') {
            $params['estado'] = 'completado';

            // Calcular distancia recorrida
            $params['distancia_recorrida'] = $params['odometro_entrada'] - $movement->odometro_salida;

            // Obtener vehículo para calcular consumo
            $vehicle = $database->get_vehicle($movement->vehiculo_id);

            if ($vehicle && $vehicle->factor_consumo > 0) {
                // Calcular consumo estimado
                $params['combustible_consumido'] = $params['distancia_recorrida'] / $vehicle->factor_consumo;

                // Calcular nuevo nivel de combustible
                $consumo_porcentaje = ($params['combustible_consumido'] / $vehicle->capacidad_tanque) * 100;
                $nuevo_nivel = max(0, $vehicle->nivel_combustible - $consumo_porcentaje);

                $params['nivel_combustible'] = $nuevo_nivel;
            }
        }

        // Actualizar movimiento
        $result = $database->update_movement($id, $params);

        if ($result === false) {
            return new WP_Error(
                'gpv_update_failed',
                __('Error al actualizar el movimiento', 'gpv-pwa'),
                array('status' => 500)
            );
        }

        $updated_movement = $database->get_movement($id);

        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => __('Movimiento actualizado correctamente', 'gpv-pwa'),
            'data' => $updated_movement
        ), 200);
    }

    /**
     * Obtener datos para el dashboard
     */
    public function get_dashboard_data($request)
    {
        $database = new GPV_Database();

        // Datos básicos para el dashboard
        $response = array(
            'vehicles' => array(
                'total' => 0,
                'available' => 0,
                'in_use' => 0,
                'maintenance' => 0
            ),
            'movements' => array(
                'today' => 0,
                'week' => 0,
                'month' => 0
            ),
            'fuel' => array(
                'month_consumption' => 0,
                'average_consumption' => 0
            ),
            'maintenance' => array(
                'pending' => 0,
                'upcoming' => 0
            )
        );

        // Estadísticas de vehículos
        $all_vehicles = $database->get_vehicles();
        $response['vehicles']['total'] = count($all_vehicles);

        foreach ($all_vehicles as $vehicle) {
            if ($vehicle->estado === 'disponible') {
                $response['vehicles']['available']++;
            } elseif ($vehicle->estado === 'en_uso') {
                $response['vehicles']['in_use']++;
            }
        }

        // Estadísticas de movimientos
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');

        $movements_today = $database->get_movements(array(
            'fecha_desde' => $today . ' 00:00:00',
            'fecha_hasta' => $today . ' 23:59:59'
        ));

        $movements_week = $database->get_movements(array(
            'fecha_desde' => $week_start . ' 00:00:00',
            'fecha_hasta' => $today . ' 23:59:59'
        ));

        $movements_month = $database->get_movements(array(
            'fecha_desde' => $month_start . ' 00:00:00',
            'fecha_hasta' => $today . ' 23:59:59'
        ));

        $response['movements']['today'] = count($movements_today);
        $response['movements']['week'] = count($movements_week);
        $response['movements']['month'] = count($movements_month);

        // Estadísticas de combustible
        $fuels_month = $database->get_fuels(array(
            'fecha_desde' => $month_start,
            'fecha_hasta' => $today
        ));

        $total_litros = 0;
        foreach ($fuels_month as $fuel) {
            $total_litros += $fuel->litros_cargados;
        }

        $response['fuel']['month_consumption'] = $total_litros;

        // Calcular consumo promedio
        if (count($movements_month) > 0) {
            $total_km = 0;
            foreach ($movements_month as $movement) {
                if ($movement->distancia_recorrida > 0) {
                    $total_km += $movement->distancia_recorrida;
                }
            }

            if ($total_km > 0 && $total_litros > 0) {
                $response['fuel']['average_consumption'] = $total_km / $total_litros;
            }
        }
    }

    /**
     * Sincronizar datos offline
     */
    public function sync_offline_data($request)
    {
        $database = new GPV_Database();
        $params = $request->get_params();

        // Verificar si hay datos para sincronizar
        if (empty($params['data']) || !is_array($params['data'])) {
            return new WP_Error(
                'gpv_no_data',
                __('No hay datos para sincronizar', 'gpv-pwa'),
                array('status' => 400)
            );
        }

        $results = array(
            'success' => 0,
            'failed' => 0,
            'items' => array()
        );

        foreach ($params['data'] as $item) {
            $result = array(
                'id' => isset($item['id']) ? $item['id'] : 'unknown',
                'type' => isset($item['type']) ? $item['type'] : 'unknown',
                'status' => 'failed'
            );

            try {
                switch ($item['type']) {
                    case 'movement':
                        if (isset($item['action']) && $item['action'] === 'update') {
                            $db_result = $database->update_movement($item['remote_id'], $item['data']);
                            if ($db_result !== false) {
                                $result['status'] = 'success';
                                $result['remote_id'] = $item['remote_id'];
                                $results['success']++;
                            } else {
                                $results['failed']++;
                            }
                        } else {
                            $db_result = $database->insert_movement($item['data']);
                            if ($db_result) {
                                $result['status'] = 'success';
                                $result['remote_id'] = $db_result;
                                $results['success']++;
                            } else {
                                $results['failed']++;
                            }
                        }
                        break;

                    case 'fuel':
                        $db_result = $database->insert_fuel($item['data']);
                        if ($db_result) {
                            $result['status'] = 'success';
                            $result['remote_id'] = $db_result;
                            $results['success']++;
                        } else {
                            $results['failed']++;
                        }
                        break;

                    default:
                        $results['failed']++;
                        break;
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $results['failed']++;
            }

            $results['items'][] = $result;
        }

        return new WP_REST_Response(array(
            'status' => 'success',
            'message' => sprintf(
                __('Sincronización completada: %d exitosos, %d fallidos', 'gpv-pwa'),
                $results['success'],
                $results['failed']
            ),
            'data' => $results
        ), 200);
    }
}
