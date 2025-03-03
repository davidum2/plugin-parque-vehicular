<?php

/**
 * Clase para gestionar la base de datos del plugin
 */
class GPV_Database
{
    /**
     * Versión de la base de datos
     *
     * @var string
     */
    private $db_version;

    /**
     * Objeto global de WordPress para la base de datos
     *
     * @var object
     */
    private $wpdb;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->db_version = '2.0';
    }

    /**
     * Instalar tablas del plugin
     */
    public function install_tables()
    {
        $charset_collate = $this->wpdb->get_charset_collate();

        // Nombres de tablas
        $tabla_vehiculos = $this->wpdb->prefix . 'gpv_vehiculos';
        $tabla_movimientos = $this->wpdb->prefix . 'gpv_movimientos';
        $tabla_cargas = $this->wpdb->prefix . 'gpv_cargas';

        $tabla_usuarios = $this->wpdb->prefix . 'gpv_usuarios';
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';

        $sql = "";

        // Tabla Vehículos (ampliada)
        $sql .= "CREATE TABLE $tabla_vehiculos (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            siglas varchar(20) NOT NULL,
            anio int(4) NOT NULL,
            nombre_vehiculo varchar(100) NOT NULL,
            odometro_actual float NOT NULL,
            nivel_combustible float NOT NULL,
            tipo_combustible varchar(50) NOT NULL,
            medida_odometro varchar(20) NOT NULL,
            factor_consumo float NOT NULL,
            capacidad_tanque float NOT NULL,
            ubicacion_actual varchar(100) NOT NULL,
            categoria varchar(50) NOT NULL,
            conductor_asignado int(11) DEFAULT NULL,
            estado varchar(20) DEFAULT 'disponible',
            ultima_actualizacion datetime DEFAULT CURRENT_TIMESTAMP,
            imagen_id int(11) DEFAULT NULL,
            notas longtext DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Tabla Movimientos (ampliada)
        $sql .= "CREATE TABLE $tabla_movimientos (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehiculo_id mediumint(9) NOT NULL,
            vehiculo_siglas varchar(20) NOT NULL,
            vehiculo_nombre varchar(100) NOT NULL,
            odometro_salida float NOT NULL,
            hora_salida DATETIME NOT NULL,
            odometro_entrada float DEFAULT NULL,
            hora_entrada DATETIME DEFAULT NULL,
            distancia_recorrida float DEFAULT NULL,
            combustible_consumido float DEFAULT NULL,
            nivel_combustible float DEFAULT NULL,
            conductor_id int(11) NOT NULL,
            conductor varchar(100) NOT NULL,
            proposito varchar(255) DEFAULT NULL,
            ruta varchar(255) DEFAULT NULL,
            estado varchar(20) DEFAULT 'en_progreso',
            notas longtext DEFAULT NULL,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            modificado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vehiculo_idx (vehiculo_id)
        ) $charset_collate;";

        // Tabla Cargas de Combustible (ampliada)
        $sql .= "CREATE TABLE $tabla_cargas (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehiculo_id mediumint(9) NOT NULL,
            vehiculo_siglas varchar(20) NOT NULL,
            vehiculo_nombre varchar(100) NOT NULL,
            odometro_carga float NOT NULL,
            litros_cargados float NOT NULL,
            precio float NOT NULL,
            km_desde_ultima_carga float NOT NULL,
            factor_consumo float NOT NULL,
            conductor_id int(11) NOT NULL,
            estacion_servicio varchar(100) DEFAULT NULL,
            numero_factura varchar(50) DEFAULT NULL,
            fecha_carga datetime NOT NULL,
            registrado_en datetime DEFAULT CURRENT_TIMESTAMP,
            notas longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY vehiculo_idx (vehiculo_id)
        ) $charset_collate;";



        // Tabla Usuarios (nueva)
        $sql .= "CREATE TABLE $tabla_usuarios (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            wp_user_id int(11) NOT NULL,
            tipo_licencia varchar(50) DEFAULT NULL,
            telefono varchar(20) DEFAULT NULL,
            direccion text DEFAULT NULL,
            fecha_contratacion date DEFAULT NULL,
            vehiculos_asignados text DEFAULT NULL,
            ultima_actividad datetime DEFAULT NULL,
            preferencias longtext DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id)
        ) $charset_collate;";

        // Tabla Configuración (nueva)
        $sql .= "CREATE TABLE $tabla_configuracion (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            clave varchar(50) NOT NULL,
            valor longtext NOT NULL,
            descripcion text DEFAULT NULL,
            modificado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY clave (clave)
        ) $charset_collate;";

        // Ejecutar creación de tablas
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Guardar versión instalada
        update_option('gpv_db_version', $this->db_version);

        // Insertar configuración inicial
        $this->insert_default_settings();
    }

    /**
     * Eliminar tablas del plugin
     */
    public function uninstall_tables()
    {
        $tablas = [
            $this->wpdb->prefix . 'gpv_vehiculos',
            $this->wpdb->prefix . 'gpv_movimientos',
            $this->wpdb->prefix . 'gpv_cargas',
            $this->wpdb->prefix . 'gpv_usuarios',
            $this->wpdb->prefix . 'gpv_configuracion'
        ];

        foreach ($tablas as $tabla) {
            $this->wpdb->query("DROP TABLE IF EXISTS $tabla");
        }

        // Eliminar opciones
        delete_option('gpv_db_version');
    }

    /**
     * Insertar configuración por defecto
     */
    private function insert_default_settings()
    {
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';

        // Comprobar si ya hay configuración
        $exists = $this->wpdb->get_var("SELECT COUNT(*) FROM $tabla_configuracion");

        if (!$exists) {
            // Configuraciones por defecto
            $default_settings = [

                [
                    'clave' => 'calcular_consumo_automatico',
                    'valor' => '1',
                    'descripcion' => 'Calcular automáticamente el consumo de combustible en cada movimiento'
                ],
                [
                    'clave' => 'umbral_nivel_combustible_bajo',
                    'valor' => '20',
                    'descripcion' => 'Porcentaje para considerar nivel bajo de combustible'
                ],
                [
                    'clave' => 'mostrar_dashboard_publico',
                    'valor' => '0',
                    'descripcion' => 'Mostrar dashboard público a usuarios no logueados'
                ],
                [
                    'clave' => 'sincronizacion_offline_habilitada',
                    'valor' => '1',
                    'descripcion' => 'Habilitar sincronización offline'
                ],
                [
                    'clave' => 'logo_url',
                    'valor' => '',
                    'descripcion' => 'URL del logo para la aplicación'
                ]
            ];

            foreach ($default_settings as $setting) {
                $this->wpdb->insert($tabla_configuracion, $setting);
            }
        }
    }

    /**
     * Obtener configuración por clave
     *
     * @param string $key Clave de la configuración
     * @return string|null Valor de la configuración o null si no existe
     */
    public function get_setting($key)
    {
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT valor FROM $tabla_configuracion WHERE clave = %s",
                $key
            )
        );

        return $result ? $result->valor : null;
    }

    /**
     * Actualizar configuración
     *
     * @param string $key Clave de la configuración
     * @param string $value Nuevo valor
     * @return int|false Número de filas actualizadas o false en caso de error
     */
    public function update_setting($key, $value)
    {
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';

        return $this->wpdb->update(
            $tabla_configuracion,
            ['valor' => $value],
            ['clave' => $key]
        );
    }

    /**********************
     * MÉTODOS DE VEHÍCULOS
     **********************/

    /**
     * Obtener listado de vehículos con filtros opcionales
     *
     * @param array $args Argumentos para filtrar
     * @return array Listado de vehículos
     */
    public function get_vehicles($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        $query = "SELECT * FROM $tabla";

        // Filtros
        if (!empty($args)) {
            $query .= " WHERE 1=1";

            if (isset($args['estado'])) {
                $query .= $this->wpdb->prepare(" AND estado = %s", $args['estado']);
            }

            if (isset($args['conductor_asignado'])) {
                $query .= $this->wpdb->prepare(" AND conductor_asignado = %d", $args['conductor_asignado']);
            }

            if (isset($args['siglas'])) {
                $query .= $this->wpdb->prepare(" AND siglas LIKE %s", '%' . $this->wpdb->esc_like($args['siglas']) . '%');
            }

            if (isset($args['categoria'])) {
                $query .= $this->wpdb->prepare(" AND categoria = %s", $args['categoria']);
            }
        }

        // Ordenación
        if (isset($args['orderby'])) {
            $orderby = sanitize_sql_orderby($args['orderby']);
            $order = isset($args['order']) && strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
            $query .= " ORDER BY $orderby $order";
        } else {
            $query .= " ORDER BY nombre_vehiculo ASC";
        }

        // Límite
        if (isset($args['limit']) && is_numeric($args['limit'])) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $args['limit']);

            if (isset($args['offset']) && is_numeric($args['offset'])) {
                $query .= $this->wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }

        return $this->wpdb->get_results($query);
    }

    /**
     * Obtener un vehículo por ID
     *
     * @param int $id ID del vehículo
     * @return object|null Objeto con los datos del vehículo o null si no existe
     */
    public function get_vehicle($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $tabla WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Insertar un nuevo vehículo
     *
     * @param array $data Datos del vehículo
     * @return int|false ID del vehículo insertado o false en caso de error
     */
    public function insert_vehicle($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        // Asegurar que los campos obligatorios estén presentes
        if (empty($data['siglas']) || empty($data['nombre_vehiculo'])) {
            return false;
        }

        // Establecer valores por defecto si no están presentes
        $defaults = [
            'anio' => date('Y'),
            'odometro_actual' => 0,
            'nivel_combustible' => 0,
            'tipo_combustible' => 'Gasolina',
            'medida_odometro' => 'Kilómetros',
            'factor_consumo' => 0,
            'capacidad_tanque' => 0,
            'ubicacion_actual' => '',
            'categoria' => 'General',
            'estado' => 'disponible',
            'ultima_actualizacion' => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert($tabla, $data);

        if ($result) {
            do_action('gpv_after_vehicle_insert', $this->wpdb->insert_id, $data);
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Actualizar un vehículo
     *
     * @param int $id ID del vehículo
     * @param array $data Datos a actualizar
     * @return int|false Número de filas actualizadas o false en caso de error
     */
    public function update_vehicle($id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        // Asegurar que el ID sea válido
        if (!$id || !is_numeric($id)) {
            return false;
        }

        // Si no hay datos para actualizar
        if (empty($data)) {
            return false;
        }

        // Actualizar fecha de modificación si no se ha establecido
        if (!isset($data['ultima_actualizacion'])) {
            $data['ultima_actualizacion'] = current_time('mysql');
        }

        $result = $this->wpdb->update(
            $tabla,
            $data,
            ['id' => $id]
        );

        if ($result !== false) {
            do_action('gpv_after_vehicle_update', $id, $data);
        }

        return $result;
    }

    /**
     * Eliminar un vehículo
     *
     * @param int $id ID del vehículo
     * @return int|false Número de filas eliminadas o false en caso de error
     */
    public function delete_vehicle($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        // Verificar si el vehículo existe
        $vehicle = $this->get_vehicle($id);
        if (!$vehicle) {
            return false;
        }

        do_action('gpv_before_vehicle_delete', $id, $vehicle);

        return $this->wpdb->delete(
            $tabla,
            ['id' => $id],
            ['%d']
        );
    }

    /**********************
     * MÉTODOS DE MOVIMIENTOS
     **********************/

    /**
     * Obtener listado de movimientos con filtros opcionales
     *
     * @param array $args Argumentos para filtrar
     * @return array Listado de movimientos
     */
    public function get_movements($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        $query = "SELECT * FROM $tabla";

        // Filtros
        if (!empty($args)) {
            $query .= " WHERE 1=1";

            if (isset($args['vehiculo_id'])) {
                $query .= $this->wpdb->prepare(" AND vehiculo_id = %d", $args['vehiculo_id']);
            }

            if (isset($args['conductor_id'])) {
                $query .= $this->wpdb->prepare(" AND conductor_id = %d", $args['conductor_id']);
            }

            if (isset($args['estado'])) {
                $query .= $this->wpdb->prepare(" AND estado = %s", $args['estado']);
            }

            if (isset($args['fecha_desde'])) {
                $query .= $this->wpdb->prepare(" AND hora_salida >= %s", $args['fecha_desde']);
            }

            if (isset($args['fecha_hasta'])) {
                $query .= $this->wpdb->prepare(" AND hora_salida <= %s", $args['fecha_hasta']);
            }

            if (isset($args['search'])) {
                $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
                $query .= $this->wpdb->prepare(
                    " AND (vehiculo_siglas LIKE %s OR vehiculo_nombre LIKE %s OR conductor LIKE %s OR proposito LIKE %s)",
                    $search,
                    $search,
                    $search,
                    $search
                );
            }
        }

        // Ordenación
        if (isset($args['orderby'])) {
            $orderby = sanitize_sql_orderby($args['orderby']);
            $order = isset($args['order']) && strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
            $query .= " ORDER BY $orderby $order";
        } else {
            $query .= " ORDER BY hora_salida DESC";
        }

        // Límite
        if (isset($args['limit']) && is_numeric($args['limit'])) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $args['limit']);

            if (isset($args['offset']) && is_numeric($args['offset'])) {
                $query .= $this->wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }

        return $this->wpdb->get_results($query);
    }

    /**
     * Obtener un movimiento por ID
     *
     * @param int $id ID del movimiento
     * @return object|null Objeto con los datos del movimiento o null si no existe
     */
    public function get_movement($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $tabla WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Insertar un nuevo movimiento
     *
     * @param array $data Datos del movimiento
     * @return int|false ID del movimiento insertado o false en caso de error
     */
    public function insert_movement($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        // Asegurar que los campos obligatorios estén presentes
        if (empty($data['vehiculo_id']) || empty($data['odometro_salida']) || empty($data['hora_salida'])) {
            return false;
        }

        // Establecer valores por defecto
        if (!isset($data['estado'])) {
            $data['estado'] = 'en_progreso';
        }

        if (!isset($data['creado_en'])) {
            $data['creado_en'] = current_time('mysql');
        }

        $result = $this->wpdb->insert($tabla, $data);

        if ($result) {
            $movement_id = $this->wpdb->insert_id;

            // Actualizar odómetro y estado del vehículo
            $this->update_vehicle($data['vehiculo_id'], [
                'odometro_actual' => $data['odometro_salida'],
                'estado' => 'en_uso',
                'ultima_actualizacion' => current_time('mysql')
            ]);

            do_action('gpv_after_movement_insert', $movement_id, $data);

            return $movement_id;
        }

        return false;
    }

    /**
     * Actualizar un movimiento
     *
     * @param int $id ID del movimiento
     * @param array $data Datos a actualizar
     * @return int|false Número de filas actualizadas o false en caso de error
     */
    public function update_movement($id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        // Obtener movimiento actual para verificar cambios
        $current_movement = $this->get_movement($id);
        if (!$current_movement) {
            return false;
        }

        // Establecer fecha de modificación
        $data['modificado_en'] = current_time('mysql');

        $result = $this->wpdb->update(
            $tabla,
            $data,
            ['id' => $id]
        );

        if ($result !== false) {
            // Si se está finalizando el movimiento (entrada)
            if (isset($data['odometro_entrada']) && $current_movement->estado === 'en_progreso') {
                // Calcular distancia si no se proporcionó
                if (!isset($data['distancia_recorrida']) && $data['odometro_entrada'] > 0) {
                    $distancia = $data['odometro_entrada'] - $current_movement->odometro_salida;
                    $this->wpdb->update(
                        $tabla,
                        ['distancia_recorrida' => $distancia],
                        ['id' => $id]
                    );
                }

                // Actualizar odómetro y estado del vehículo al finalizar
                $vehicle_updates = [
                    'odometro_actual' => $data['odometro_entrada'],
                    'estado' => 'disponible',
                    'ultima_actualizacion' => current_time('mysql')
                ];

                // Si hay nivel de combustible, actualizarlo también
                if (isset($data['nivel_combustible'])) {
                    $vehicle_updates['nivel_combustible'] = $data['nivel_combustible'];
                }

                $this->update_vehicle($current_movement->vehiculo_id, $vehicle_updates);
            }

            do_action('gpv_after_movement_update', $id, $data, $current_movement);
        }

        return $result;
    }

    /**
     * Eliminar un movimiento
     *
     * @param int $id ID del movimiento
     * @return int|false Número de filas eliminadas o false en caso de error
     */
    public function delete_movement($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        // Verificar si el movimiento existe
        $movement = $this->get_movement($id);
        if (!$movement) {
            return false;
        }

        do_action('gpv_before_movement_delete', $id, $movement);

        return $this->wpdb->delete(
            $tabla,
            ['id' => $id],
            ['%d']
        );
    }

    /**********************
     * MÉTODOS DE CARGAS DE COMBUSTIBLE
     **********************/

    /**
     * Obtener listado de cargas de combustible con filtros opcionales
     *
     * @param array $args Argumentos para filtrar
     * @return array Listado de cargas
     */
    public function get_fuels($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        $query = "SELECT * FROM $tabla";

        // Filtros
        if (!empty($args)) {
            $query .= " WHERE 1=1";

            if (isset($args['vehiculo_id'])) {
                $query .= $this->wpdb->prepare(" AND vehiculo_id = %d", $args['vehiculo_id']);
            }

            if (isset($args['conductor_id'])) {
                $query .= $this->wpdb->prepare(" AND conductor_id = %d", $args['conductor_id']);
            }

            if (isset($args['fecha_desde'])) {
                $query .= $this->wpdb->prepare(" AND fecha_carga >= %s", $args['fecha_desde']);
            }

            if (isset($args['fecha_hasta'])) {
                $query .= $this->wpdb->prepare(" AND fecha_carga <= %s", $args['fecha_hasta']);
            }

            if (isset($args['estacion_servicio'])) {
                $query .= $this->wpdb->prepare(" AND estacion_servicio = %s", $args['estacion_servicio']);
            }

            if (isset($args['search'])) {
                $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
                $query .= $this->wpdb->prepare(
                    " AND (vehiculo_siglas LIKE %s OR vehiculo_nombre LIKE %s OR estacion_servicio LIKE %s OR numero_factura LIKE %s)",
                    $search,
                    $search,
                    $search,
                    $search
                );
            }
        }

        // Ordenación
        if (isset($args['orderby'])) {
            $orderby = sanitize_sql_orderby($args['orderby']);
            $order = isset($args['order']) && strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
            $query .= " ORDER BY $orderby $order";
        } else {
            $query .= " ORDER BY fecha_carga DESC";
        }

        // Límite
        if (isset($args['limit']) && is_numeric($args['limit'])) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $args['limit']);

            if (isset($args['offset']) && is_numeric($args['offset'])) {
                $query .= $this->wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }

        return $this->wpdb->get_results($query);
    }

    /**
     * Obtener una carga de combustible por ID
     *
     * @param int $id ID de la carga
     * @return object|null Objeto con los datos de la carga o null si no existe
     */
    public function get_fuel($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $tabla WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Insertar una nueva carga de combustible
     *
     * @param array $data Datos de la carga
     * @return int|false ID de la carga insertada o false en caso de error
     */
    public function insert_fuel($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        // Asegurar que los campos obligatorios estén presentes
        if (empty($data['vehiculo_id']) || empty($data['odometro_carga']) || empty($data['litros_cargados'])) {
            return false;
        }

        // Establecer valores por defecto
        if (!isset($data['fecha_carga'])) {
            $data['fecha_carga'] = current_time('mysql');
        }

        if (!isset($data['registrado_en'])) {
            $data['registrado_en'] = current_time('mysql');
        }

        $result = $this->wpdb->insert($tabla, $data);

        if ($result) {
            $fuel_id = $this->wpdb->insert_id;

            // Actualizar odómetro y nivel de combustible del vehículo
            $vehiculo = $this->get_vehicle($data['vehiculo_id']);

            if ($vehiculo) {
                // Calcular nuevo nivel de combustible
                $nuevo_nivel = min(100, $vehiculo->nivel_combustible + ($data['litros_cargados'] / $vehiculo->capacidad_tanque * 100));

                $this->update_vehicle($data['vehiculo_id'], [
                    'odometro_actual' => $data['odometro_carga'],
                    'nivel_combustible' => $nuevo_nivel,
                    'ultima_actualizacion' => current_time('mysql')
                ]);
            }

            do_action('gpv_after_fuel_insert', $fuel_id, $data);

            return $fuel_id;
        }

        return false;
    }

    /**
     * Actualizar una carga de combustible
     *
     * @param int $id ID de la carga
     * @param array $data Datos a actualizar
     * @return int|false Número de filas actualizadas o false en caso de error
     */
    public function update_fuel($id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        // Obtener carga actual para verificar cambios
        $current_fuel = $this->get_fuel($id);
        if (!$current_fuel) {
            return false;
        }

        $result = $this->wpdb->update(
            $tabla,
            $data,
            ['id' => $id]
        );

        if ($result !== false) {
            // Si cambiaron los litros cargados, actualizar el nivel de combustible del vehículo
            if (isset($data['litros_cargados']) && $data['litros_cargados'] != $current_fuel->litros_cargados) {
                $vehiculo = $this->get_vehicle($current_fuel->vehiculo_id);

                if ($vehiculo) {
                    // Restar el valor anterior
                    $nivel_previo = $vehiculo->nivel_combustible - ($current_fuel->litros_cargados / $vehiculo->capacidad_tanque * 100);

                    // Sumar el nuevo valor
                    $nuevo_nivel = min(100, $nivel_previo + ($data['litros_cargados'] / $vehiculo->capacidad_tanque * 100));

                    $this->update_vehicle($current_fuel->vehiculo_id, [
                        'nivel_combustible' => $nuevo_nivel,
                        'ultima_actualizacion' => current_time('mysql')
                    ]);
                }
            }

            do_action('gpv_after_fuel_update', $id, $data, $current_fuel);
        }

        return $result;
    }

    /**
     * Eliminar una carga de combustible
     *
     * @param int $id ID de la carga
     * @return int|false Número de filas eliminadas o false en caso de error
     */
    public function delete_fuel($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        // Verificar si la carga existe
        $fuel = $this->get_fuel($id);
        if (!$fuel) {
            return false;
        }

        do_action('gpv_before_fuel_delete', $id, $fuel);

        return $this->wpdb->delete(
            $tabla,
            ['id' => $id],
            ['%d']
        );
    }



    /**********************
     * MÉTODOS DE USUARIO
     **********************/

    /**
     * Obtener datos de usuario GPV por ID de usuario WordPress
     *
     * @param int $wp_user_id ID del usuario en WordPress
     * @return object|null Datos del usuario GPV o null si no existe
     */
    public function get_user_data($wp_user_id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_usuarios';

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $tabla WHERE wp_user_id = %d",
                $wp_user_id
            )
        );
    }

    /**
     * Insertar o actualizar datos de usuario GPV
     *
     * @param int $wp_user_id ID del usuario en WordPress
     * @param array $data Datos del usuario GPV
     * @return int|false ID del registro insertado/actualizado o false en caso de error
     */
    public function update_user_data($wp_user_id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_usuarios';

        // Verificar si el usuario existe
        $user_exists = $this->get_user_data($wp_user_id);

        if ($user_exists) {
            // Actualizar
            $result = $this->wpdb->update(
                $tabla,
                $data,
                ['wp_user_id' => $wp_user_id]
            );

            return ($result !== false) ? $user_exists->id : false;
        } else {
            // Insertar
            $data['wp_user_id'] = $wp_user_id;
            $result = $this->wpdb->insert($tabla, $data);

            return ($result) ? $this->wpdb->insert_id : false;
        }
    }

    /**
     * Actualizar última actividad de usuario
     *
     * @param int $wp_user_id ID del usuario en WordPress
     * @return bool Éxito de la operación
     */
    public function update_user_activity($wp_user_id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_usuarios';

        $user_exists = $this->get_user_data($wp_user_id);

        if ($user_exists) {
            $result = $this->wpdb->update(
                $tabla,
                ['ultima_actividad' => current_time('mysql')],
                ['wp_user_id' => $wp_user_id]
            );

            return $result !== false;
        } else {
            $result = $this->wpdb->insert(
                $tabla,
                [
                    'wp_user_id' => $wp_user_id,
                    'ultima_actividad' => current_time('mysql')
                ]
            );

            return $result !== false;
        }
    }

    /**********************
     * MÉTODOS ESTADÍSTICOS
     **********************/

    /**
     * Obtener estadísticas generales para el dashboard
     *
     * @return array Estadísticas generales
     */
    public function get_dashboard_stats()
    {
        // Fechas para filtros
        $today = date('Y-m-d');
        $month_start = date('Y-m-01');
        $year_start = date('Y-01-01');

        // Estadísticas de vehículos
        $vehicles_stats = [
            'total' => 0,
            'available' => 0,
            'in_use' => 0,
            'maintenance' => 0
        ];

        $vehicles = $this->get_vehicles();
        $vehicles_stats['total'] = count($vehicles);

        foreach ($vehicles as $vehicle) {
            switch ($vehicle->estado) {
                case 'disponible':
                    $vehicles_stats['available']++;
                    break;
                case 'en_uso':
                    $vehicles_stats['in_use']++;
                    break;
                case 'mantenimiento':
                    $vehicles_stats['maintenance']++;
                    break;
            }
        }

        // Estadísticas de movimientos
        $movements_today = $this->get_movements([
            'fecha_desde' => $today . ' 00:00:00',
            'fecha_hasta' => $today . ' 23:59:59'
        ]);

        $movements_month = $this->get_movements([
            'fecha_desde' => $month_start . ' 00:00:00',
            'fecha_hasta' => $today . ' 23:59:59'
        ]);

        $total_distance = 0;
        foreach ($movements_month as $movement) {
            $total_distance += (float)$movement->distancia_recorrida;
        }

        $active_movements = $this->get_movements([
            'estado' => 'en_progreso'
        ]);

        $movements_stats = [
            'today' => count($movements_today),
            'month' => count($movements_month),
            'total_distance' => $total_distance,
            'active' => count($active_movements)
        ];

        // Estadísticas de combustible
        $fuels_month = $this->get_fuels([
            'fecha_desde' => $month_start,
            'fecha_hasta' => $today
        ]);

        $month_consumption = 0;
        $month_cost = 0;
        foreach ($fuels_month as $fuel) {
            $month_consumption += (float)$fuel->litros_cargados;
            $month_cost += (float)$fuel->litros_cargados * (float)$fuel->precio;
        }

        $average_consumption = ($total_distance > 0 && $month_consumption > 0) ?
            $total_distance / $month_consumption : 0;

        $fuel_stats = [
            'month_consumption' => $month_consumption,
            'month_cost' => $month_cost,
            'average_consumption' => $average_consumption
        ];



        // Obtener movimientos recientes para el dashboard
        $recent_movements = $this->get_movements([
            'limit' => 5,
            'orderby' => 'creado_en',
            'order' => 'DESC'
        ]);

        return [
            'vehicles' => $vehicles_stats,
            'movements' => $movements_stats,
            'fuel' => $fuel_stats,
            'recentMovements' => $recent_movements,
            'last_update' => current_time('mysql')
        ];
    }

    /**
     * Obtener estadísticas de uso de vehículos
     *
     * @param string $period Periodo (month, year, all)
     * @return array Estadísticas de uso
     */
    public function get_vehicle_usage_stats($period = 'month')
    {
        // Determinar fechas según periodo
        $today = date('Y-m-d');

        switch ($period) {
            case 'month':
                $start_date = date('Y-m-01');
                break;
            case 'year':
                $start_date = date('Y-01-01');
                break;
            case 'all':
            default:
                $start_date = '1970-01-01';
                break;
        }

        $vehicles = $this->get_vehicles();
        $stats = [];

        foreach ($vehicles as $vehicle) {
            // Obtener movimientos para este vehículo en el periodo
            $movements = $this->get_movements([
                'vehiculo_id' => $vehicle->id,
                'fecha_desde' => $start_date . ' 00:00:00',
                'fecha_hasta' => $today . ' 23:59:59'
            ]);

            // Calcular distancia total, tiempo de uso y combustible
            $total_distance = 0;
            $total_fuel = 0;
            $total_usage_hours = 0;

            foreach ($movements as $movement) {
                if ($movement->estado === 'completado' && !empty($movement->distancia_recorrida)) {
                    $total_distance += (float)$movement->distancia_recorrida;

                    if (!empty($movement->combustible_consumido)) {
                        $total_fuel += (float)$movement->combustible_consumido;
                    }

                    // Calcular horas de uso si hay hora de entrada y salida
                    if (!empty($movement->hora_entrada) && !empty($movement->hora_salida)) {
                        $entrada = new DateTime($movement->hora_entrada);
                        $salida = new DateTime($movement->hora_salida);
                        $diferencia = $entrada->diff($salida);
                        $horas = $diferencia->h + ($diferencia->days * 24);
                        $total_usage_hours += $horas;
                    }
                }
            }

            // Calcular consumo promedio
            $avg_consumption = ($total_distance > 0 && $total_fuel > 0) ?
                $total_distance / $total_fuel : 0;

            // Obtener cargas de combustible
            $fuels = $this->get_fuels([
                'vehiculo_id' => $vehicle->id,
                'fecha_desde' => $start_date,
                'fecha_hasta' => $today
            ]);

            $total_recharges = count($fuels);
            $total_cost = 0;

            foreach ($fuels as $fuel) {
                $total_cost += (float)$fuel->litros_cargados * (float)$fuel->precio;
            }




            // Guardar estadísticas
            $stats[$vehicle->id] = [
                'id' => $vehicle->id,
                'siglas' => $vehicle->siglas,
                'nombre' => $vehicle->nombre_vehiculo,
                'total_movements' => count($movements),
                'total_distance' => $total_distance,
                'total_fuel' => $total_fuel,
                'total_usage_hours' => $total_usage_hours,
                'avg_consumption' => $avg_consumption,
                'total_recharges' => $total_recharges,
                'total_fuel_cost' => $total_cost,

                'cost_per_km' => ($total_distance > 0) ?
                    $total_cost / $total_distance : 0
            ];
        }

        return $stats;
    }
}
