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

        // Nombres de nuevas tablas para reportes
        $tabla_reportes_movimientos = $this->wpdb->prefix . 'gpv_reportes_movimientos';
        $tabla_firmantes = $this->wpdb->prefix . 'gpv_firmantes_autorizados';


        $sql = "";

        // Tabla de Reportes de Movimientos
        $sql .= "CREATE TABLE IF NOT EXISTS $tabla_reportes_movimientos (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vehiculo_id mediumint(9) NOT NULL,
        vehiculo_siglas varchar(20) NOT NULL,
        vehiculo_nombre varchar(100) NOT NULL,
        odometro_inicial float NOT NULL,
        odometro_final float NOT NULL,
        fecha_inicial date NOT NULL,
        hora_inicial time NOT NULL,
        fecha_final date NOT NULL,
        hora_final time NOT NULL,
        distancia_total float NOT NULL,
        conductor_id int(11) NOT NULL,
        conductor varchar(100) NOT NULL,
        movimientos_incluidos text NOT NULL,
        numero_mensaje varchar(50) DEFAULT NULL,
        firmante_id mediumint(9) DEFAULT NULL,
        fecha_reporte date NOT NULL,
        estado varchar(20) DEFAULT 'pendiente',
        notas text DEFAULT NULL,
        creado_en datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY vehiculo_idx (vehiculo_id)
    ) $charset_collate;";

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
            reportado tinyint(1) DEFAULT 0,
            reporte_id mediumint(9) DEFAULT NULL,
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
                // Calcular nuevo nivel de combustible en litros
                $nuevo_nivel = min($vehiculo->capacidad_tanque, $vehiculo->nivel_combustible + $data['litros_cargados']);

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
                    // Restar el valor anterior en litros
                    $nivel_previo = $vehiculo->nivel_combustible - $current_fuel->litros_cargados;
                    // Sumar el nuevo valor en litros
                    $nuevo_nivel = min($vehiculo->capacidad_tanque, $nivel_previo + $data['litros_cargados']);


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

    /**
     * Actualizar el estado de un reporte y opcionalmente su archivo PDF
     *
     * @param int $reporte_id ID del reporte
     * @param string $estado Nuevo estado del reporte
     * @param string|null $archivo_pdf Nombre del archivo PDF generado (opcional)
     * @return int|false Número de filas actualizadas o false en caso de error
     */
    public function update_reporte_estado($reporte_id, $estado, $archivo_pdf = null)
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        $data = [
            'estado' => $estado
        ];

        if ($archivo_pdf) {
            $data['archivo_pdf'] = $archivo_pdf;
        }

        return $this->wpdb->update(
            $tabla,
            $data,
            ['id' => $reporte_id]
        );
    }

    /**
     * Obtener un reporte específico por ID
     *
     * @param int $reporte_id ID del reporte
     * @return object|null Objeto con los datos del reporte o null si no existe
     */
    public function get_reporte($reporte_id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM $tabla WHERE id = %d",
                $reporte_id
            )
        );
    }



    /**
     * Obtener movimientos elegibles para reporte
     *
     * @param string $fecha_reporte Fecha para el reporte
     * @return array Lista de movimientos elegibles
     */
    // Modificación a includes/class-gpv-database.php
    public function get_movimientos_para_reporte($fecha_reporte)
    {
        $tabla_movimientos = $this->wpdb->prefix . 'gpv_movimientos';
        $tabla_vehiculos = $this->wpdb->prefix . 'gpv_vehiculos';

        // Verificar si existe la columna reportado
        $reportado_exists = false;
        $columns = $this->wpdb->get_results("SHOW COLUMNS FROM $tabla_movimientos");
        foreach ($columns as $column) {
            if ($column->Field === 'reportado') {
                $reportado_exists = true;
                break;
            }
        }

        // Construir la consulta según la disponibilidad de la columna
        if ($reportado_exists) {
            $query = "SELECT m.*, v.siglas, v.nombre_vehiculo
            FROM $tabla_movimientos m
            JOIN $tabla_vehiculos v ON m.vehiculo_id = v.id
            WHERE m.estado = 'completado'
            AND m.reportado = 0
            AND m.distancia_recorrida IS NOT NULL
            ORDER BY v.id, m.hora_salida";
        } else {
            // Consulta alternativa sin el filtro de reportado
            $query = "SELECT m.*, v.siglas, v.nombre_vehiculo
            FROM $tabla_movimientos m
            JOIN $tabla_vehiculos v ON m.vehiculo_id = v.id
            WHERE m.estado = 'completado'
            AND m.distancia_recorrida IS NOT NULL
            ORDER BY v.id, m.hora_salida";
        }

        // Ejecutar la consulta
        try {
            $movimientos = $this->wpdb->get_results($query);
        } catch (Exception $e) {
            error_log('Error en consulta get_movimientos_para_reporte: ' . $e->getMessage());
            return [];
        }

        // Si no hay movimientos, devolver array vacío
        if (empty($movimientos)) {
            return [];
        }

        // Agrupar por vehículo
        $por_vehiculo = [];
        foreach ($movimientos as $mov) {
            if (!isset($por_vehiculo[$mov->vehiculo_id])) {
                $por_vehiculo[$mov->vehiculo_id] = [];
            }
            $por_vehiculo[$mov->vehiculo_id][] = $mov;
        }

        // Procesar cada vehículo para acumular movimientos
        $elegibles = [];

        foreach ($por_vehiculo as $vehiculo_id => $movs) {
            // Acumular TODOS los movimientos del vehículo
            $acumulado = [];
            $distancia_total = 0;
            $primer_mov = null;
            $ultimo_mov = null;

            foreach ($movs as $mov) {
                if (empty($primer_mov)) {
                    $primer_mov = $mov;
                }

                $ultimo_mov = $mov;
                $distancia_total += floatval($mov->distancia_recorrida);
                $acumulado[] = $mov;
            }

            // Si hay movimientos acumulados, verificar si califican para reporte
            if (!empty($acumulado)) {
                // Criterios para incluir en el reporte:
                // 1. Distancia total es de al menos 30 km O
                // 2. Han pasado más de 7 días desde el primer movimiento O
                // 3. Hay al menos 3 movimientos acumulados

                $dias_pasados = 0;
                if (isset($primer_mov->hora_salida)) {
                    $dias_pasados = (time() - strtotime($primer_mov->hora_salida)) / (60 * 60 * 24);
                }

                if ($distancia_total >= 30 || $dias_pasados > 7 || count($acumulado) >= 3) {
                    // Crear objeto para el reporte
                    $elegible = new stdClass();
                    $elegible->id = implode(',', array_map(function ($m) {
                        return $m->id;
                    }, $acumulado));
                    $elegible->vehiculo_id = $vehiculo_id;
                    $elegible->vehiculo_siglas = $ultimo_mov->siglas;
                    $elegible->vehiculo_nombre = $ultimo_mov->nombre_vehiculo;
                    $elegible->odometro_inicial = $primer_mov->odometro_salida;
                    $elegible->odometro_final = $ultimo_mov->odometro_entrada;

                    $elegible->fecha_inicial = isset($primer_mov->hora_salida)
                        ? date('Y-m-d', strtotime($primer_mov->hora_salida))
                        : $fecha_reporte;
                    $elegible->hora_inicial = isset($primer_mov->hora_salida)
                        ? date('H:i:s', strtotime($primer_mov->hora_salida))
                        : '00:00:00';

                    $elegible->fecha_final = isset($ultimo_mov->hora_entrada)
                        ? date('Y-m-d', strtotime($ultimo_mov->hora_entrada))
                        : $fecha_reporte;
                    $elegible->hora_final = isset($ultimo_mov->hora_entrada)
                        ? date('H:i:s', strtotime($ultimo_mov->hora_entrada))
                        : '23:59:59';

                    $elegible->distancia_total = $distancia_total;
                    $elegible->conductor = isset($ultimo_mov->conductor) ? $ultimo_mov->conductor : '';
                    $elegible->conductor_id = isset($ultimo_mov->conductor_id) ? $ultimo_mov->conductor_id : 0;

                    $elegibles[] = $elegible;
                }
            }
        }

        return $elegibles;
    }


    /**
     * Actualizar firmante autorizado
     *
     * @param int $id ID del firmante
     * @param array $data Datos a actualizar
     * @return int|false Número de filas actualizadas o false en caso de error
     */
    public function update_firmante($id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_firmantes_autorizados';

        return $this->wpdb->update(
            $tabla,
            $data,
            ['id' => $id]
        );
    }

    /**
     * Eliminar firmante autorizado
     *
     * @param int $id ID del firmante
     * @return int|false Número de filas eliminadas o false en caso de error
     */
    public function delete_firmante($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_firmantes_autorizados';

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

    /**
     * Obtener lista de firmantes autorizados
     *
     * @param array $args Argumentos para filtrar
     * @return array Lista de firmantes
     */
    public function get_firmantes($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_firmantes_autorizados';

        // Comprobar si la tabla existe
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$tabla'") != $tabla) {
            return array(); // Devolver array vacío si la tabla no existe
        }

        $query = "SELECT * FROM $tabla";

        // Filtros
        if (!empty($args)) {
            $query .= " WHERE 1=1";

            if (isset($args['activo'])) {
                $query .= $this->wpdb->prepare(" AND activo = %d", $args['activo']);
            }

            if (isset($args['id'])) {
                $query .= $this->wpdb->prepare(" AND id = %d", $args['id']);
            }
        }

        // Ordenación
        $query .= " ORDER BY nombre ASC";

        return $this->wpdb->get_results($query);
    }

    /**
     * Insertar un nuevo firmante autorizado
     *
     * @param array $data Datos del firmante
     * @return int|false ID del firmante insertado o false en caso de error
     */
    public function insert_firmante($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_firmantes_autorizados';

        // Comprobar si la tabla existe
        if ($this->wpdb->get_var("SHOW TABLES LIKE '$tabla'") != $tabla) {
            // Intentar crear la tabla
            $this->update_database_structure();

            // Verificar si se creó correctamente
            if ($this->wpdb->get_var("SHOW TABLES LIKE '$tabla'") != $tabla) {
                return false;
            }
        }

        // Valores por defecto
        $defaults = [
            'activo' => 1,
            'fecha_creacion' => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert($tabla, $data);

        if ($result) {
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Obtener reportes de movimientos
     *
     * @param array $args Argumentos para filtrar
     * @return array Lista de reportes
     */
    public function get_reportes_movimientos($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        $query = "SELECT * FROM $tabla";

        // Filtros
        if (!empty($args)) {
            $query .= " WHERE 1=1";

            if (isset($args['fecha_reporte'])) {
                $query .= $this->wpdb->prepare(" AND fecha_reporte = %s", $args['fecha_reporte']);
            }

            if (isset($args['estado'])) {
                $query .= $this->wpdb->prepare(" AND estado = %s", $args['estado']);
            }

            if (isset($args['vehiculo_id'])) {
                $query .= $this->wpdb->prepare(" AND vehiculo_id = %d", $args['vehiculo_id']);
            }
        }

        // Ordenación
        if (isset($args['orderby'])) {
            $orderby = sanitize_sql_orderby($args['orderby']);
            $order = isset($args['order']) && strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
            $query .= " ORDER BY $orderby $order";
        } else {
            $query .= " ORDER BY fecha_reporte DESC, vehiculo_nombre ASC";
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
     * Insertar un nuevo reporte de movimiento
     *
     * @param array $data Datos del reporte
     * @return int|false ID del reporte insertado o false en caso de error
     */
    public function insert_reporte_movimiento($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        // Valores por defecto
        $defaults = [
            'estado' => 'pendiente',
            'creado_en' => current_time('mysql')
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert($tabla, $data);

        if ($result) {
            $reporte_id = $this->wpdb->insert_id;

            // Actualizar los movimientos incluidos
            if (isset($data['movimientos_incluidos']) && !empty($data['movimientos_incluidos'])) {
                $movimientos_ids = explode(',', $data['movimientos_incluidos']);
                foreach ($movimientos_ids as $movimiento_id) {
                    $this->marcar_movimiento_reportado($movimiento_id, $reporte_id);
                }
            }

            return $reporte_id;
        }

        return false;
    }

    /**
     * Marcar un movimiento como reportado
     *
     * @param int $movimiento_id ID del movimiento
     * @param int $reporte_id ID del reporte
     * @return int|false Número de filas actualizadas o false en caso de error
     */
    public function marcar_movimiento_reportado($movimiento_id, $reporte_id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        return $this->wpdb->update(
            $tabla,
            [
                'reportado' => 1,
                'reporte_id' => $reporte_id
            ],
            ['id' => $movimiento_id],
            ['%d', '%d'],
            ['%d']
        );
    }
    /**
     * Actualizar la estructura de la base de datos manualmente
     */
    public function update_database_structure()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Verificar si existe la columna firmante2_id en la tabla de reportes
        $tabla_reportes = $wpdb->prefix . 'gpv_reportes_movimientos';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reportes'") == $tabla_reportes) {
            if ($wpdb->get_var("SHOW COLUMNS FROM $tabla_reportes LIKE 'firmante2_id'") != 'firmante2_id') {
                $wpdb->query("ALTER TABLE $tabla_reportes ADD COLUMN firmante2_id mediumint(9) DEFAULT NULL");
            }
        }

        // Crear tabla de firmantes si no existe
        $tabla_firmantes = $wpdb->prefix . 'gpv_firmantes_autorizados';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_firmantes'") != $tabla_firmantes) {
            $sql = "CREATE TABLE $tabla_firmantes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nombre varchar(100) NOT NULL,
            cargo varchar(100) NOT NULL,
            grado varchar(50) DEFAULT NULL,
            numero_empleado varchar(20) DEFAULT NULL,
            activo tinyint(1) DEFAULT 1,
            notas text DEFAULT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Agregar columna reportado si no existe
        $tabla_movimientos = $wpdb->prefix . 'gpv_movimientos';

        if ($wpdb->get_var("SHOW COLUMNS FROM $tabla_movimientos LIKE 'reportado'") != 'reportado') {
            $wpdb->query("ALTER TABLE $tabla_movimientos ADD COLUMN reportado tinyint(1) DEFAULT 0");
        }

        // Agregar columna reporte_id si no existe
        if ($wpdb->get_var("SHOW COLUMNS FROM $tabla_movimientos LIKE 'reporte_id'") != 'reporte_id') {
            $wpdb->query("ALTER TABLE $tabla_movimientos ADD COLUMN reporte_id mediumint(9) DEFAULT NULL");
        }

        // Crear tabla de reportes si no existe
        $tabla_reportes = $wpdb->prefix . 'gpv_reportes_movimientos';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_reportes'") != $tabla_reportes) {
            $sql = "CREATE TABLE $tabla_reportes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            vehiculo_id mediumint(9) NOT NULL,
            vehiculo_siglas varchar(20) NOT NULL,
            vehiculo_nombre varchar(100) NOT NULL,
            odometro_inicial float NOT NULL,
            odometro_final float NOT NULL,
            fecha_inicial date NOT NULL,
            hora_inicial time NOT NULL,
            fecha_final date NOT NULL,
            hora_final time NOT NULL,
            distancia_total float NOT NULL,
            conductor_id int(11) NOT NULL,
            conductor varchar(100) NOT NULL,
            movimientos_incluidos text NOT NULL,
            numero_mensaje varchar(50) DEFAULT NULL,
            firmante_id mediumint(9) DEFAULT NULL,
            fecha_reporte date NOT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            archivo_pdf varchar(255) DEFAULT NULL,
            notas text DEFAULT NULL,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vehiculo_idx (vehiculo_id)
        ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        return true;
    }
}
