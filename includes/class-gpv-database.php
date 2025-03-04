<?php

/**
 * Clase mejorada y documentada para gestionar la base de datos del plugin GPV (Gestión de Parque Vehicular).
 *
 * Esta clase encapsula toda la lógica de acceso y manipulación de datos para el plugin,
 * utilizando la API de WordPress $wpdb para interactuar con la base de datos de forma segura y eficiente.
 *
 * Versión refactorizada y documentada por [Tu Nombre/Nombre del Refactorizador].
 * Versión de la base de datos: 2.0
 */
class GPV_Database
{
    /**
     * @var string Prefijo de las tablas de la base de datos de WordPress.
     */
    private $prefix; // **¡Añade esta línea para declarar la propiedad $prefix!**

    /**
     * @var string Versión actual de la base de datos del plugin.
     * Se utiliza para la gestión de actualizaciones de la base de datos.
     */
    private $db_version;

    /**
     * @var wpdb Objeto global de WordPress para la interacción con la base de datos.
     * Permite ejecutar consultas SQL y acceder a la base de datos de WordPress.
     */
    private $wpdb;

    /**
     * Constructor de la clase GPV_Database.
     *
     * Inicializa la conexión a la base de datos de WordPress y establece la versión de la base de datos.
     */
    public function __construct()
    {
        global $wpdb; // Importa la instancia global de $wpdb de WordPress.
        $this->wpdb = $wpdb;
        $this->db_version = '2.0'; // Define la versión actual de la base de datos.

        // ** Añadido: Llama a la función para verificar y actualizar la estructura de la base de datos **
        $this->update_database_structure();
    }
    /**
     * Obtiene las estadísticas para el dashboard principal.
     *
     * @return array Array asociativo con las estadísticas del dashboard.
     */
    /**
     * Obtiene las estadísticas para el dashboard principal.
     *
     * @return array Array asociativo con las estadísticas del dashboard.
     */
    public function obtener_estadisticas_dashboard()
    {
        global $wpdb;

        $stats = array(
            'vehicles' => array(),
            'movements' => array(),
            'fuel' => array()
        );
        // --- Estadísticas de Vehículos ---
        $sql_vehiculos_total = "SELECT COUNT(*) FROM {$this->prefix}vehiculos";
        $stats['vehicles']['total'] = $wpdb->get_var($sql_vehiculos_total);

        $sql_vehiculos_disponibles = "SELECT COUNT(*) FROM {$this->prefix}vehiculos WHERE estado = 'disponible'"; // Ajusta 'disponible' si usas otro valor
        $stats['vehicles']['available'] = $wpdb->get_var($sql_vehiculos_disponibles);

        $sql_vehiculos_en_uso = "SELECT COUNT(*) FROM {$this->prefix}vehiculos WHERE estado = 'en_uso'"; // Ajusta 'en_uso' si usas otro valor
        $stats['vehicles']['in_use'] = $wpdb->get_var($sql_vehiculos_en_uso);


        // --- Estadísticas de Movimientos (Ejemplo muy básico, deberías refinar las consultas) ---
        $sql_movimientos_hoy = "SELECT COUNT(*) FROM {$this->prefix}movimientos WHERE DATE(hora_salida) = CURDATE()"; // Ajusta 'hora_salida' y la tabla si es necesario
        $stats['movements']['today'] = $wpdb->get_var($sql_movimientos_hoy);

        $sql_movimientos_mes = "SELECT COUNT(*) FROM {$this->prefix}movimientos WHERE MONTH(hora_salida) = MONTH(CURDATE()) AND YEAR(hora_salida) = YEAR(CURDATE())"; // Ajusta 'hora_salida' y la tabla si es necesario
        $stats['movements']['month'] = $wpdb->get_var($sql_movimientos_mes);

        $sql_distancia_total = "SELECT SUM(distancia_recorrida) FROM {$this->prefix}movimientos"; // Ajusta 'distancia_recorrida' y la tabla si es necesario
        $stats['movements']['total_distance'] = $wpdb->get_var($sql_distancia_total);
        $stats['movements']['total_distance'] = $stats['movements']['total_distance'] ? $stats['movements']['total_distance'] : 0; // Asegurar que sea 0 si es NULL

        $sql_movimientos_activos = "SELECT COUNT(*) FROM {$this->prefix}movimientos WHERE estado = 'activo'"; // Ajusta 'activo' y la tabla si es necesario
        $stats['movements']['active'] = $wpdb->get_var($sql_movimientos_activos);


        // --- Estadísticas de Combustible (Ejemplo muy básico, necesitas lógica para consumo mensual y promedio) ---
        $stats['fuel']['month_consumption'] = 0; // **Debes implementar la lógica real**
        $stats['fuel']['average_consumption'] = 0; // **Debes implementar la lógica real**


        return $stats;
    }
    /**
     * **AÑADIDO: Verifica si la estructura de la base de datos está actualizada y la actualiza si es necesario.**
     *
     * Este método compara la versión actual de la base de datos almacenada en la opción 'gpv_db_version'
     * con la versión actual definida en la clase ($this->db_version). Si las versiones no coinciden,
     * se ejecuta el proceso de actualización de las tablas.
     *
     * @return void
     */
    private function update_database_structure()
    {
        $installed_db_version = get_option('gpv_db_version');

        // Si la versión instalada es diferente a la actual, o si no hay versión instalada (instalación nueva)
        if ($installed_db_version != $this->db_version) {
            $this->install_tables(); // Llama al método install_tables para (re)instalar las tablas.

            // Actualiza la versión de la base de datos en las opciones de WordPress
            update_option('gpv_db_version', $this->db_version);
        }
    }


    /**
     * Instala las tablas necesarias para el plugin en la base de datos de WordPress.
     *
     * Utiliza dbDelta para crear y actualizar las tablas de forma segura,
     * y guarda la versión de la base de datos y la configuración inicial.
     *
     * @return void
     */
    public function install_tables()
    {
        $charset_collate = $this->wpdb->get_charset_collate(); // Obtiene el cotejamiento de caracteres de WordPress.
        $sql = ""; // Inicializa la variable para construir las sentencias SQL.

        // -- Definición de nombres de tablas utilizando el prefijo de WordPress --
        $tabla_vehiculos = $this->wpdb->prefix . 'gpv_vehiculos';
        $tabla_movimientos = $this->wpdb->prefix . 'gpv_movimientos';
        $tabla_cargas = $this->wpdb->prefix . 'gpv_cargas';
        $tabla_usuarios = $this->wpdb->prefix . 'gpv_usuarios';
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';
        $tabla_reportes_movimientos = $this->wpdb->prefix . 'gpv_reportes_movimientos';
        $tabla_firmantes = $this->wpdb->prefix . 'gpv_firmantes_autorizados'; // Tabla no definida en el código original, asumida.

        // -- Sentencia SQL para crear la tabla de Reportes de Movimientos --
        $sql .= "CREATE TABLE IF NOT EXISTS {$tabla_reportes_movimientos} (
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
            firmante2_id mediumint(9) DEFAULT NULL,  -- Añadido firmante2_id, presente en el formulario pero no en la tabla original.
            fecha_reporte date NOT NULL,
            estado varchar(20) DEFAULT 'pendiente',
            notas text DEFAULT NULL,
            creado_en datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY vehiculo_idx (vehiculo_id)
        ) {$charset_collate};";

        // -- Sentencia SQL para crear la tabla de Vehículos (ampliada) --
        $sql .= "CREATE TABLE {$tabla_vehiculos} (
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
        ) {$charset_collate};";

        // -- Sentencia SQL para crear la tabla de Movimientos (ampliada) --
        $sql .= "CREATE TABLE {$tabla_movimientos} (
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
        ) {$charset_collate};";

        // -- Sentencia SQL para crear la tabla de Cargas de Combustible (ampliada) --
        $sql .= "CREATE TABLE {$tabla_cargas} (
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
        ) {$charset_collate};";

        // -- Sentencia SQL para crear la tabla de Usuarios (nueva) --
        $sql .= "CREATE TABLE {$tabla_usuarios} (
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
        ) {$charset_collate};";

        // -- Sentencia SQL para crear la tabla de Configuración (nueva) --
        $sql .= "CREATE TABLE {$tabla_configuracion} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            clave varchar(50) NOT NULL,
            valor longtext NOT NULL,
            descripcion text DEFAULT NULL,
            modificado_en datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY clave (clave)
        ) {$charset_collate};";

        // -- Ejecuta las sentencias SQL para crear las tablas --
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); // Carga funciones de actualización de WordPress.
        dbDelta($sql); // Utiliza dbDelta para crear/actualizar las tablas de manera segura.

        update_option('gpv_db_version', $this->db_version); // Guarda la versión de la base de datos en las opciones de WordPress.

        $this->insert_default_settings(); // Inserta la configuración inicial por defecto.
    }

    /**
     * Elimina las tablas del plugin de la base de datos.
     *
     * Se llama generalmente durante la desinstalación del plugin para limpiar la base de datos.
     *
     * @return void
     */
    public function uninstall_tables()
    {
        $tablas = [ // Array con los nombres de las tablas a eliminar.
            $this->wpdb->prefix . 'gpv_vehiculos',
            $this->wpdb->prefix . 'gpv_movimientos',
            $this->wpdb->prefix . 'gpv_cargas',
            $this->wpdb->prefix . 'gpv_usuarios',
            $this->wpdb->prefix . 'gpv_configuracion',
            $this->wpdb->prefix . 'gpv_reportes_movimientos', // Añadida la tabla de reportes para ser eliminada.
            $this->wpdb->prefix . 'gpv_firmantes_autorizados' // Añadida la tabla de firmantes para ser eliminada.
        ];

        foreach ($tablas as $tabla) {
            $this->wpdb->query("DROP TABLE IF EXISTS {$tabla}"); // Elimina cada tabla si existe.
        }

        delete_option('gpv_db_version'); // Elimina la opción de versión de la base de datos al desinstalar.
    }

    /**
     * Inserta la configuración por defecto en la tabla de configuración si no existen registros.
     *
     * Este método asegura que la tabla de configuración tenga valores iniciales al instalar el plugin.
     *
     * @return void
     */
    private function insert_default_settings()
    {
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';

        $exists = $this->wpdb->get_var("SELECT COUNT(*) FROM {$tabla_configuracion}"); // Verifica si existen registros en la tabla.

        if (!$exists) { // Si no existen registros, inserta la configuración por defecto.
            $default_settings = [ // Array con la configuración por defecto.
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
                $this->wpdb->insert($tabla_configuracion, $setting); // Inserta cada configuración en la tabla.
            }
        }
    }

    /**
     * Sección de Métodos para la Gestión de Configuración
     * ----------------------------------------------------
     */

    /**
     * Obtiene el valor de una configuración específica mediante su clave.
     *
     * @param string $key Clave de la configuración a obtener.
     * @return string|null Valor de la configuración si existe, NULL si no.
     */
    public function get_setting($key)
    {
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';

        $result = $this->wpdb->get_row(
            $this->wpdb->prepare( // Utiliza prepare para prevenir inyección SQL.
                "SELECT valor FROM {$tabla_configuracion} WHERE clave = %s",
                $key
            )
        );

        return $result ? $result->valor : null; // Retorna el valor o NULL si no se encuentra.
    }

    /**
     * Actualiza el valor de una configuración existente.
     *
     * @param string $key Clave de la configuración a actualizar.
     * @param string $value Nuevo valor para la configuración.
     * @return int|false Número de filas actualizadas en caso de éxito, FALSE en caso de error.
     */
    public function update_setting($key, $value)
    {
        $tabla_configuracion = $this->wpdb->prefix . 'gpv_configuracion';

        return $this->wpdb->update( // Utiliza update para actualizar registros.
            $tabla_configuracion,
            ['valor' => $value], // Datos a actualizar (valor).
            ['clave' => $key]    // Condición WHERE (clave).
        );
    }

    /**
     * Sección de Métodos para la Gestión de Vehículos
     * ------------------------------------------------
     */

    /**
     * Obtiene un listado de vehículos con opciones de filtrado y ordenamiento.
     *
     * @param array $args Argumentos para filtrar y ordenar los vehículos (opcional).
     *                    - 'estado': string, Filtra por estado del vehículo (ej: 'disponible').
     *                    - 'conductor_asignado': int, Filtra por ID del conductor asignado.
     *                    - 'siglas': string, Filtra por siglas del vehículo (LIKE).
     *                    - 'categoria': string, Filtra por categoría del vehículo.
     *                    - 'orderby': string, Campo para ordenar los resultados (ej: 'nombre_vehiculo').
     *                    - 'order': string, Dirección de ordenamiento ('ASC' o 'DESC').
     *                    - 'limit': int, Límite de resultados a obtener.
     *                    - 'offset': int, Desplazamiento para la paginación.
     * @return array Listado de vehículos que cumplen con los criterios de búsqueda.
     */
    public function get_vehicles($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';
        $query = "SELECT * FROM {$tabla}"; // Inicia la consulta SQL.
        $where_clauses = []; // Array para almacenar las cláusulas WHERE.
        $prepare_args = []; // Array para almacenar argumentos para prepare.

        // -- Construcción de cláusulas WHERE basadas en los argumentos --
        if (!empty($args)) {
            if (isset($args['estado'])) {
                $where_clauses[] = 'estado = %s';
                $prepare_args[] = $args['estado'];
            }
            if (isset($args['conductor_asignado'])) {
                $where_clauses[] = 'conductor_asignado = %d';
                $prepare_args[] = $args['conductor_asignado'];
            }
            if (isset($args['siglas'])) {
                $where_clauses[] = 'siglas LIKE %s';
                $prepare_args[] = '%' . $this->wpdb->esc_like($args['siglas']) . '%';
            }
            if (isset($args['categoria'])) {
                $where_clauses[] = 'categoria = %s';
                $prepare_args[] = $args['categoria'];
            }
        }

        if (!empty($where_clauses)) { // Añade las cláusulas WHERE a la consulta si existen.
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // -- Ordenamiento --
        $orderby_allowed_values = ['id', 'siglas', 'anio', 'nombre_vehiculo', 'odometro_actual', 'nivel_combustible', 'tipo_combustible', 'medida_odometro', 'factor_consumo', 'capacidad_tanque', 'ubicacion_actual', 'categoria', 'conductor_asignado', 'estado', 'ultima_actualizacion', 'imagen_id', 'notas']; // Lista blanca de columnas ordenables.
        $order_allowed_values = ['ASC', 'DESC'];
        $orderby = isset($args['orderby']) && in_array($args['orderby'], $orderby_allowed_values) ? sanitize_sql_orderby($args['orderby']) : 'nombre_vehiculo';
        $order = isset($args['order']) && in_array(strtoupper($args['order']), $order_allowed_values) ? strtoupper($args['order']) : 'ASC';
        $query .= " ORDER BY {$orderby} {$order}"; // Añade la cláusula ORDER BY a la consulta.


        // -- Límite y Offset (Paginación) --
        if (isset($args['limit']) && is_numeric($args['limit'])) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $args['limit']);
            if (isset($args['offset']) && is_numeric($args['offset'])) {
                $query .= $this->wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }

        return $this->wpdb->get_results($this->wpdb->prepare($query, $prepare_args)); // Ejecuta la consulta preparada y retorna los resultados.
    }

    /**
     * Obtiene la información de un vehículo específico por su ID.
     *
     * @param int $id ID del vehículo a obtener.
     * @return object|null Objeto con los datos del vehículo si se encuentra, NULL si no.
     */
    public function get_vehicle($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        return $this->wpdb->get_row(
            $this->wpdb->prepare( // Utiliza prepare para prevenir inyección SQL.
                "SELECT * FROM {$tabla} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Inserta un nuevo vehículo en la base de datos.
     *
     * @param array $data Datos del vehículo a insertar.
     *                    Debe incluir 'siglas' y 'nombre_vehiculo' como campos obligatorios.
     *                    Otros campos son opcionales y se utilizan valores por defecto si no se proporcionan.
     * @return int|false ID del vehículo insertado en caso de éxito, FALSE en caso de error.
     */
    public function insert_vehicle($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        if (empty($data['siglas']) || empty($data['nombre_vehiculo'])) { // Valida campos obligatorios.
            return false; // Retorna FALSE si faltan campos obligatorios.
        }

        $defaults = [ // Valores por defecto para campos opcionales.
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
        $data = wp_parse_args($data, $defaults); // Combina los datos proporcionados con los valores por defecto.

        $result = $this->wpdb->insert($tabla, $data); // Intenta insertar el vehículo en la base de datos.

        if ($result) {
            $vehicle_id = $this->wpdb->insert_id;
            do_action('gpv_after_vehicle_insert', $vehicle_id, $data); // Ejecuta acción 'gpv_after_vehicle_insert'.
            return $vehicle_id; // Retorna el ID del vehículo insertado.
        }

        return false; // Retorna FALSE si la inserción falla.
    }

    /**
     * Actualiza la información de un vehículo existente.
     *
     * @param int $id ID del vehículo a actualizar.
     * @param array $data Array asociativo con los datos a actualizar.
     * @return int|false Número de filas actualizadas en caso de éxito, FALSE en caso de error.
     */
    public function update_vehicle($id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        if (!$id || !is_numeric($id)) { // Valida que el ID sea válido.
            return false; // Retorna FALSE si el ID no es válido.
        }
        if (empty($data)) { // Valida que haya datos para actualizar.
            return false; // Retorna FALSE si no hay datos para actualizar.
        }

        if (!isset($data['ultima_actualizacion'])) { // Actualiza la fecha de modificación si no está presente.
            $data['ultima_actualizacion'] = current_time('mysql');
        }

        $result = $this->wpdb->update( // Intenta actualizar el vehículo en la base de datos.
            $tabla,
            $data,
            ['id' => $id] // Cláusula WHERE para identificar el vehículo a actualizar.
        );

        if ($result !== false) {
            do_action('gpv_after_vehicle_update', $id, $data); // Ejecuta acción 'gpv_after_vehicle_update'.
        }

        return $result; // Retorna el resultado de la operación update.
    }

    /**
     * Elimina un vehículo de la base de datos.
     *
     * @param int $id ID del vehículo a eliminar.
     * @return int|false Número de filas eliminadas en caso de éxito, FALSE en caso de error.
     */
    public function delete_vehicle($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_vehiculos';

        $vehicle = $this->get_vehicle($id); // Verifica si el vehículo existe antes de eliminarlo.
        if (!$vehicle) {
            return false; // Retorna FALSE si el vehículo no existe.
        }

        do_action('gpv_before_vehicle_delete', $id, $vehicle); // Ejecuta acción 'gpv_before_vehicle_delete' antes de eliminar.

        return $this->wpdb->delete( // Intenta eliminar el vehículo.
            $tabla,
            ['id' => $id], // Cláusula WHERE para identificar el vehículo a eliminar.
            ['%d']        // Formato del ID (entero).
        );
    }

    /**
     * Sección de Métodos para la Gestión de Movimientos
     * -------------------------------------------------
     */

    /**
     * Obtiene un listado de movimientos con opciones de filtrado y ordenamiento.
     *
     * @param array $args Argumentos para filtrar y ordenar los movimientos (opcional).
     *                    - 'vehiculo_id': int, Filtra por ID del vehículo.
     *                    - 'conductor_id': int, Filtra por ID del conductor.
     *                    - 'estado': string, Filtra por estado del movimiento (ej: 'en_progreso').
     *                    - 'fecha_desde': string (fecha), Filtra movimientos desde esta fecha (hora_salida >=).
     *                    - 'fecha_hasta': string (fecha), Filtra movimientos hasta esta fecha (hora_salida <=).
     *                    - 'search': string, Término de búsqueda para filtrar por siglas, nombre vehículo, conductor o propósito (LIKE).
     *                    - 'orderby': string, Campo para ordenar (ej: 'hora_salida').
     *                    - 'order': string, Dirección de ordenamiento ('ASC' o 'DESC').
     *                    - 'limit': int, Límite de resultados a obtener.
     *                    - 'offset': int, Desplazamiento para la paginación.
     * @return array Listado de movimientos que cumplen con los criterios de búsqueda.
     */
    public function get_movements($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';
        $query = "SELECT * FROM {$tabla}"; // Inicia la consulta SQL.
        $where_clauses = []; // Array para almacenar las cláusulas WHERE.
        $prepare_args = []; // Array para almacenar argumentos para prepare.

        // -- Construcción de cláusulas WHERE basadas en los argumentos --
        if (!empty($args)) {
            if (isset($args['vehiculo_id'])) {
                $where_clauses[] = 'vehiculo_id = %d';
                $prepare_args[] = $args['vehiculo_id'];
            }
            if (isset($args['conductor_id'])) {
                $where_clauses[] = 'conductor_id = %d';
                $prepare_args[] = $args['conductor_id'];
            }
            if (isset($args['estado'])) {
                $where_clauses[] = 'estado = %s';
                $prepare_args[] = $args['estado'];
            }
            if (isset($args['fecha_desde'])) {
                $where_clauses[] = 'hora_salida >= %s';
                $prepare_args[] = $args['fecha_desde'];
            }
            if (isset($args['fecha_hasta'])) {
                $where_clauses[] = 'hora_salida <= %s';
                $prepare_args[] = $args['fecha_hasta'];
            }
            if (isset($args['search'])) {
                $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
                $where_clauses[] = "(vehiculo_siglas LIKE %s OR vehiculo_nombre LIKE %s OR conductor LIKE %s OR proposito LIKE %s)";
                array_push($prepare_args, $search, $search, $search, $search); // Añade múltiples veces el mismo argumento para prepare.
            }
        }
        if (!empty($where_clauses)) { // Añade las cláusulas WHERE a la consulta si existen.
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // -- Ordenamiento --
        $orderby_allowed_values = ['id', 'vehiculo_id', 'vehiculo_siglas', 'vehiculo_nombre', 'odometro_salida', 'hora_salida', 'odometro_entrada', 'hora_entrada', 'distancia_recorrida', 'combustible_consumido', 'nivel_combustible', 'conductor_id', 'conductor', 'proposito', 'ruta', 'estado', 'notas', 'reportado', 'reporte_id', 'creado_en', 'modificado_en']; // Lista blanca de columnas ordenables.
        $order_allowed_values = ['ASC', 'DESC'];

        $orderby = isset($args['orderby']) && in_array($args['orderby'], $orderby_allowed_values) ? sanitize_sql_orderby($args['orderby']) : 'hora_salida';
        $order = isset($args['order']) && in_array(strtoupper($args['order']), $order_allowed_values) ? strtoupper($args['order']) : 'DESC';
        $query .= " ORDER BY {$orderby} {$order}"; // Añade la cláusula ORDER BY a la consulta.

        // -- Límite y Offset (Paginación) --
        if (isset($args['limit']) && is_numeric($args['limit'])) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $args['limit']);
            if (isset($args['offset']) && is_numeric($args['offset'])) {
                $query .= $this->wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }


        return $this->wpdb->get_results($this->wpdb->prepare($query, $prepare_args)); // Ejecuta la consulta preparada y retorna los resultados.
    }

    /**
     * Obtiene la información de un movimiento específico por su ID.
     *
     * @param int $id ID del movimiento a obtener.
     * @return object|null Objeto con los datos del movimiento si se encuentra, NULL si no.
     */
    public function get_movement($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        return $this->wpdb->get_row(
            $this->wpdb->prepare( // Utiliza prepare para prevenir inyección SQL.
                "SELECT * FROM {$tabla} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Inserta un nuevo movimiento en la base de datos.
     *
     * @param array $data Datos del movimiento a insertar.
     *                    Debe incluir 'vehiculo_id', 'odometro_salida' y 'hora_salida' como campos obligatorios.
     *                    Establece 'estado' a 'en_progreso' por defecto y fecha de creación si no se proporcionan.
     * @return int|false ID del movimiento insertado en caso de éxito, FALSE en caso de error.
     */
    public function insert_movement($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        if (empty($data['vehiculo_id']) || empty($data['odometro_salida']) || empty($data['hora_salida'])) { // Valida campos obligatorios.
            return false; // Retorna FALSE si faltan campos obligatorios.
        }

        $data['estado'] = $data['estado'] ?? 'en_progreso'; // Establece 'estado' a 'en_progreso' por defecto si no se proporciona.
        $data['creado_en'] = $data['creado_en'] ?? current_time('mysql'); // Establece fecha de creación si no se proporciona.


        $result = $this->wpdb->insert($tabla, $data); // Intenta insertar el movimiento en la base de datos.

        if ($result) {
            $movement_id = $this->wpdb->insert_id;
            $vehicle_id = $data['vehiculo_id'];
            $odometro_salida = $data['odometro_salida'];

            $this->update_vehicle( // Actualiza el vehículo asociado al movimiento.
                $vehicle_id,
                [
                    'odometro_actual' => $odometro_salida, // Actualiza odómetro del vehículo.
                    'estado' => 'en_uso',                // Cambia el estado del vehículo a 'en_uso'.
                    'ultima_actualizacion' => current_time('mysql') // Actualiza la fecha de última actualización del vehículo.
                ]
            );
            do_action('gpv_after_movement_insert', $movement_id, $data); // Ejecuta acción 'gpv_after_movement_insert'.

            return $movement_id; // Retorna el ID del movimiento insertado.
        }

        return false; // Retorna FALSE si la inserción falla.
    }

    /**
     * Actualiza la información de un movimiento existente.
     *
     * @param int $id ID del movimiento a actualizar.
     * @param array $data Array asociativo con los datos a actualizar.
     * @return int|false Número de filas actualizadas en caso de éxito, FALSE en caso de error.
     */
    public function update_movement($id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        $current_movement = $this->get_movement($id); // Obtiene el movimiento actual para comparaciones.
        if (!$current_movement) {
            return false; // Retorna FALSE si el movimiento no existe.
        }

        $data['modificado_en'] = current_time('mysql'); // Actualiza la fecha de modificación.

        $result = $this->wpdb->update( // Intenta actualizar el movimiento en la base de datos.
            $tabla,
            $data,
            ['id' => $id] // Cláusula WHERE para identificar el movimiento a actualizar.
        );

        if ($result !== false) {
            if (isset($data['odometro_entrada']) && $current_movement->estado === 'en_progreso') { // Si se está finalizando el movimiento.
                $distancia = isset($data['distancia_recorrida']) ? $data['distancia_recorrida'] : ($data['odometro_entrada'] - $current_movement->odometro_salida); // Calcula la distancia si no se proporciona.
                $vehicle_updates = [ // Datos para actualizar el vehículo al finalizar el movimiento.
                    'odometro_actual' => $data['odometro_entrada'], // Actualiza el odómetro del vehículo.
                    'estado' => 'disponible',                   // Cambia el estado del vehículo a 'disponible'.
                    'ultima_actualizacion' => current_time('mysql') // Actualiza la fecha de última actualización del vehículo.
                ];
                if (isset($data['nivel_combustible'])) { // Si se proporciona nivel de combustible, actualiza también.
                    $vehicle_updates['nivel_combustible'] = $data['nivel_combustible'];
                }

                $this->wpdb->update( // Actualiza la tabla de movimientos con la distancia recorrida.
                    $tabla,
                    ['distancia_recorrida' => $distancia],
                    ['id' => $id]
                );
                $this->update_vehicle($current_movement->vehiculo_id, $vehicle_updates); // Actualiza el vehículo asociado.
            }
            do_action('gpv_after_movement_update', $id, $data, $current_movement); // Ejecuta acción 'gpv_after_movement_update'.
        }

        return $result; // Retorna el resultado de la operación update.
    }

    /**
     * Elimina un movimiento de la base de datos.
     *
     * @param int $id ID del movimiento a eliminar.
     * @return int|false Número de filas eliminadas en caso de éxito, FALSE en caso de error.
     */
    public function delete_movement($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        $movement = $this->get_movement($id); // Verifica si el movimiento existe antes de eliminarlo.
        if (!$movement) {
            return false; // Retorna FALSE si el movimiento no existe.
        }

        do_action('gpv_before_movement_delete', $id, $movement); // Ejecuta acción 'gpv_before_movement_delete' antes de eliminar.

        return $this->wpdb->delete( // Intenta eliminar el movimiento.
            $tabla,
            ['id' => $id], // Cláusula WHERE para identificar el movimiento a eliminar.
            ['%d']        // Formato del ID (entero).
        );
    }

    /**
     * Sección de Métodos para la Gestión de Cargas de Combustible
     * -----------------------------------------------------------
     */

    /**
     * Obtiene un listado de cargas de combustible con opciones de filtrado y ordenamiento.
     *
     * @param array $args Argumentos para filtrar y ordenar las cargas de combustible (opcional).
     *                    - 'vehiculo_id': int, Filtra por ID del vehículo.
     *                    - 'conductor_id': int, Filtra por ID del conductor.
     *                    - 'fecha_desde': string (fecha), Filtra cargas desde esta fecha (fecha_carga >=).
     *                    - 'fecha_hasta': string (fecha), Filtra cargas hasta esta fecha (fecha_carga <=).
     *                    - 'estacion_servicio': string, Filtra por nombre de la estación de servicio.
     *                    - 'search': string, Término de búsqueda para filtrar por siglas, nombre vehículo, estación de servicio o número de factura (LIKE).
     *                    - 'orderby': string, Campo para ordenar (ej: 'fecha_carga').
     *                    - 'order': string, Dirección de ordenamiento ('ASC' o 'DESC').
     *                    - 'limit': int, Límite de resultados a obtener.
     *                    - 'offset': int, Desplazamiento para la paginación.
     * @return array Listado de cargas de combustible que cumplen con los criterios de búsqueda.
     */
    public function get_fuels($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';
        $query = "SELECT * FROM {$tabla}"; // Inicia la consulta SQL.
        $where_clauses = []; // Array para almacenar las cláusulas WHERE.
        $prepare_args = []; // Array para almacenar argumentos para prepare.

        // -- Construcción de cláusulas WHERE basadas en los argumentos --
        if (!empty($args)) {
            if (isset($args['vehiculo_id'])) {
                $where_clauses[] = 'vehiculo_id = %d';
                $prepare_args[] = $args['vehiculo_id'];
            }
            if (isset($args['conductor_id'])) {
                $where_clauses[] = 'conductor_id = %d';
                $prepare_args[] = $args['conductor_id'];
            }
            if (isset($args['fecha_desde'])) {
                $where_clauses[] = 'fecha_carga >= %s';
                $prepare_args[] = $args['fecha_desde'];
            }
            if (isset($args['fecha_hasta'])) {
                $where_clauses[] = 'fecha_carga <= %s';
                $prepare_args[] = $args['fecha_hasta'];
            }
            if (isset($args['estacion_servicio'])) {
                $where_clauses[] = 'estacion_servicio = %s';
                $prepare_args[] = $args['estacion_servicio'];
            }
            if (isset($args['search'])) {
                $search = '%' . $this->wpdb->esc_like($args['search']) . '%';
                $where_clauses[] = "(vehiculo_siglas LIKE %s OR vehiculo_nombre LIKE %s OR estacion_servicio LIKE %s OR numero_factura LIKE %s)";
                array_push($prepare_args, $search, $search, $search, $search); // Añade múltiples veces el mismo argumento para prepare.
            }
        }
        if (!empty($where_clauses)) { // Añade las cláusulas WHERE a la consulta si existen.
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // -- Ordenamiento --
        $orderby_allowed_values = ['id', 'vehiculo_id', 'vehiculo_siglas', 'vehiculo_nombre', 'odometro_carga', 'litros_cargados', 'precio', 'km_desde_ultima_carga', 'factor_consumo', 'conductor_id', 'estacion_servicio', 'numero_factura', 'fecha_carga', 'registrado_en', 'notas']; // Lista blanca de columnas ordenables.
        $order_allowed_values = ['ASC', 'DESC'];
        $orderby = isset($args['orderby']) && in_array($args['orderby'], $orderby_allowed_values) ? sanitize_sql_orderby($args['orderby']) : 'fecha_carga';
        $order = isset($args['order']) && in_array(strtoupper($args['order']), $order_allowed_values) ? strtoupper($args['order']) : 'DESC';
        $query .= " ORDER BY {$orderby} {$order}"; // Añade la cláusula ORDER BY a la consulta.


        // -- Límite y Offset (Paginación) --
        if (isset($args['limit']) && is_numeric($args['limit'])) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $args['limit']);
            if (isset($args['offset']) && is_numeric($args['offset'])) {
                $query .= $this->wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }

        return $this->wpdb->get_results($this->wpdb->prepare($query, $prepare_args)); // Ejecuta la consulta preparada y retorna los resultados.
    }

    /**
     * Obtiene la información de una carga de combustible específica por su ID.
     *
     * @param int $id ID de la carga de combustible a obtener.
     * @return object|null Objeto con los datos de la carga de combustible si se encuentra, NULL si no.
     */
    public function get_fuel($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        return $this->wpdb->get_row(
            $this->wpdb->prepare( // Utiliza prepare para prevenir inyección SQL.
                "SELECT * FROM {$tabla} WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Inserta una nueva carga de combustible en la base de datos.
     *
     * @param array $data Datos de la carga de combustible a insertar.
     *                    Debe incluir 'vehiculo_id', 'odometro_carga' y 'litros_cargados' como campos obligatorios.
     *                    Establece 'fecha_carga' y 'registrado_en' por defecto si no se proporcionan.
     * @return int|false ID de la carga de combustible insertada en caso de éxito, FALSE en caso de error.
     */
    public function insert_fuel($data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        if (empty($data['vehiculo_id']) || empty($data['odometro_carga']) || empty($data['litros_cargados'])) { // Valida campos obligatorios.
            return false; // Retorna FALSE si faltan campos obligatorios.
        }

        $data['fecha_carga'] = $data['fecha_carga'] ?? current_time('mysql'); // Establece fecha de carga por defecto si no se proporciona.
        $data['registrado_en'] = $data['registrado_en'] ?? current_time('mysql'); // Establece fecha de registro por defecto si no se proporciona.


        $result = $this->wpdb->insert($tabla, $data); // Intenta insertar la carga de combustible en la base de datos.

        if ($result) {
            $fuel_id = $this->wpdb->insert_id;
            $vehiculo = $this->get_vehicle($data['vehiculo_id']); // Obtiene el vehículo asociado a la carga.

            if ($vehiculo) { // Si se encuentra el vehículo, actualiza su información.
                $nuevo_nivel = min($vehiculo->capacidad_tanque, $vehiculo->nivel_combustible + $data['litros_cargados']); // Calcula el nuevo nivel de combustible.

                $this->update_vehicle( // Actualiza el vehículo con el nuevo odómetro y nivel de combustible.
                    $data['vehiculo_id'],
                    [
                        'odometro_actual' => $data['odometro_carga'],     // Actualiza el odómetro del vehículo.
                        'nivel_combustible' => $nuevo_nivel,            // Actualiza el nivel de combustible del vehículo.
                        'ultima_actualizacion' => current_time('mysql')  // Actualiza la fecha de última actualización del vehículo.
                    ]
                );
            }
            do_action('gpv_after_fuel_insert', $fuel_id, $data); // Ejecuta acción 'gpv_after_fuel_insert'.

            return $fuel_id; // Retorna el ID de la carga de combustible insertada.
        }

        return false; // Retorna FALSE si la inserción falla.
    }

    /**
     * Actualiza la información de una carga de combustible existente.
     *
     * @param int $id ID de la carga de combustible a actualizar.
     * @param array $data Array asociativo con los datos a actualizar.
     * @return int|false Número de filas actualizadas en caso de éxito, FALSE en caso de error.
     */
    public function update_fuel($id, $data)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        $current_fuel = $this->get_fuel($id); // Obtiene la carga de combustible actual para comparaciones.
        if (!$current_fuel) {
            return false; // Retorna FALSE si la carga de combustible no existe.
        }

        $result = $this->wpdb->update( // Intenta actualizar la carga de combustible en la base de datos.
            $tabla,
            $data,
            ['id' => $id] // Cláusula WHERE para identificar la carga de combustible a actualizar.
        );

        if ($result !== false) {
            if (isset($data['litros_cargados']) && $data['litros_cargados'] != $current_fuel->litros_cargados) { // Si se modifican los litros cargados.
                $vehiculo = $this->get_vehicle($current_fuel->vehiculo_id); // Obtiene el vehículo asociado a la carga.

                if ($vehiculo) { // Si se encuentra el vehículo, actualiza su nivel de combustible.
                    $nivel_previo = $vehiculo->nivel_combustible - $current_fuel->litros_cargados; // Calcula el nivel previo restando los litros originales.
                    $nuevo_nivel = min($vehiculo->capacidad_tanque, $nivel_previo + $data['litros_cargados']); // Calcula el nuevo nivel sumando los nuevos litros.


                    $this->update_vehicle( // Actualiza el vehículo con el nuevo nivel de combustible.
                        $current_fuel->vehiculo_id,
                        [
                            'nivel_combustible' => $nuevo_nivel,           // Actualiza el nivel de combustible del vehículo.
                            'ultima_actualizacion' => current_time('mysql') // Actualiza la fecha de última actualización del vehículo.
                        ]
                    );
                }
            }
            do_action('gpv_after_fuel_update', $id, $data, $current_fuel); // Ejecuta acción 'gpv_after_fuel_update'.
        }

        return $result; // Retorna el resultado de la operación update.
    }

    /**
     * Elimina una carga de combustible de la base de datos.
     *
     * @param int $id ID de la carga de combustible a eliminar.
     * @return int|false Número de filas eliminadas en caso de éxito, FALSE en caso de error.
     */
    public function delete_fuel($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_cargas';

        $fuel = $this->get_fuel($id); // Verifica si la carga de combustible existe antes de eliminarla.
        if (!$fuel) {
            return false; // Retorna FALSE si la carga de combustible no existe.
        }

        do_action('gpv_before_fuel_delete', $id, $fuel); // Ejecuta acción 'gpv_before_fuel_delete' antes de eliminar.

        return $this->wpdb->delete( // Intenta eliminar la carga de combustible.
            $tabla,
            ['id' => $id], // Cláusula WHERE para identificar la carga de combustible a eliminar.
            ['%d']        // Formato del ID (entero).
        );
    }

    /**
     * Sección de Métodos para la Gestión de Reportes de Movimientos
     * -------------------------------------------------------------
     */


    /**
     * Obtiene un listado de reportes de movimientos con opciones de filtrado y ordenamiento.
     *
     * @param array $args Argumentos para filtrar y ordenar los reportes (opcional).
     *                    - 'reporte_id': int, Filtra por ID del reporte.
     *                    - 'vehiculo_id': int, Filtra por ID del vehículo asociado al reporte.
     *                    - 'estado': string, Filtra por estado del reporte (ej: 'pendiente', 'aprobado').
     *                    - 'fecha_desde': string (fecha), Filtra reportes desde esta fecha de reporte (fecha_reporte >=).
     *                    - 'fecha_hasta': string (fecha), Filtra reportes hasta esta fecha de reporte (fecha_reporte <=).
     *                    - 'orderby': string, Campo para ordenar (ej: 'fecha_reporte').
     *                    - 'order': string, Dirección de ordenamiento ('ASC' o 'DESC').
     *                    - 'limit': int, Límite de resultados a obtener.
     *                    - 'offset': int, Desplazamiento para la paginación.
     * @return array Listado de reportes de movimientos que cumplen con los criterios de búsqueda.
     */
    public function obtener_reportes_movimientos($args = [])
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';
        $query = "SELECT * FROM {$tabla}"; // Inicia la consulta SQL.
        $where_clauses = []; // Array para almacenar las cláusulas WHERE.
        $prepare_args = []; // Array para almacenar argumentos para prepare.

        // -- Construcción de cláusulas WHERE basadas en los argumentos --
        if (!empty($args)) {
            if (isset($args['reporte_id'])) {
                $where_clauses[] = 'id = %d';
                $prepare_args[] = $args['reporte_id'];
            }
            if (isset($args['vehiculo_id'])) {
                $where_clauses[] = 'vehiculo_id = %d';
                $prepare_args[] = $args['vehiculo_id'];
            }
            if (isset($args['estado'])) {
                $where_clauses[] = 'estado = %s';
                $prepare_args[] = $args['estado'];
            }
            if (isset($args['fecha_desde'])) {
                $where_clauses[] = 'fecha_reporte >= %s';
                $prepare_args[] = $args['fecha_desde'];
            }
            if (isset($args['fecha_hasta'])) {
                $where_clauses[] = 'fecha_reporte <= %s';
                $prepare_args[] = $args['fecha_hasta'];
            }
        }
        if (!empty($where_clauses)) { // Añade las cláusulas WHERE a la consulta si existen.
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // -- Ordenamiento --
        $orderby_allowed_values = ['id', 'vehiculo_id', 'fecha_reporte', 'estado', 'creado_en']; // Lista blanca de columnas ordenables.
        $order_allowed_values = ['ASC', 'DESC'];

        $orderby = isset($args['orderby']) && in_array($args['orderby'], $orderby_allowed_values) ? sanitize_sql_orderby($args['orderby']) : 'fecha_reporte';
        $order = isset($args['order']) && in_array(strtoupper($args['order']), $order_allowed_values) ? strtoupper($args['order']) : 'DESC';
        $query .= " ORDER BY {$orderby} {$order}"; // Añade la cláusula ORDER BY a la consulta.

        // -- Límite y Offset (Paginación) --
        if (isset($args['limit']) && is_numeric($args['limit'])) {
            $query .= $this->wpdb->prepare(" LIMIT %d", $args['limit']);
            if (isset($args['offset']) && is_numeric($args['offset'])) {
                $query .= $this->wpdb->prepare(" OFFSET %d", $args['offset']);
            }
        }

        return $this->wpdb->get_results($this->wpdb->prepare($query, $prepare_args)); // Ejecuta la consulta preparada y retorna los resultados.
    }



    /**
     * Elimina un reporte de movimiento de la base de datos.
     *
     * @param int $id ID del reporte de movimiento a eliminar.
     * @return int|false Número de filas eliminadas en caso de éxito, FALSE en caso de error.
     */
    public function delete_reporte($id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        return $this->wpdb->delete( // Intenta eliminar el reporte de movimiento.
            $tabla,
            ['id' => $id], // Cláusula WHERE para identificar el reporte a eliminar.
            ['%d']        // Formato del ID (entero).
        );
    }

    /**
     * Desmarca un movimiento como reportado, revirtiendo el estado y eliminando la referencia al reporte.
     *
     * @param int $movimiento_id ID del movimiento a desmarcar como reportado.
     * @return int|false Número de filas actualizadas en caso de éxito, FALSE en caso de error.
     */
    public function desmarcar_movimiento_reportado($movimiento_id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_movimientos';

        return $this->wpdb->update( // Intenta actualizar el movimiento en la base de datos.
            $tabla,
            [
                'reportado' => 0,    // Establece 'reportado' a 0 (no reportado).
                'reporte_id' => null // Elimina la referencia al reporte (establece reporte_id a NULL).
            ],
            ['id' => $movimiento_id], // Cláusula WHERE para identificar el movimiento a desmarcar.
            ['%d', '%d'],          // Formatos de los valores a actualizar (entero, entero).
            ['%d']                 // Formato del ID (entero).
        );
    }

    /**
     * Actualiza el estado de un reporte de movimiento y opcionalmente su archivo PDF asociado.
     *
     * @param int $reporte_id ID del reporte de movimiento a actualizar.
     * @param string $estado Nuevo estado del reporte (ej: 'pendiente', 'aprobado').
     * @param string|null $archivo_pdf Nombre del archivo PDF generado para el reporte (opcional).
     * @return int|false Número de filas actualizadas en caso de éxito, FALSE en caso de error.
     */
    public function update_reporte_estado($reporte_id, $estado, $archivo_pdf = null)
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        $data = [ // Datos a actualizar.
            'estado' => $estado // Actualiza el estado del reporte.
        ];

        if ($archivo_pdf) { // Si se proporciona nombre de archivo PDF, lo incluye en la actualización.
            $data['archivo_pdf'] = $archivo_pdf;
        }

        return $this->wpdb->update( // Intenta actualizar el reporte en la base de datos.
            $tabla,
            $data,
            ['id' => $reporte_id] // Cláusula WHERE para identificar el reporte a actualizar.
        );
    }

    /**
     * Obtiene un reporte de movimiento específico por su ID.
     *
     * @param int $reporte_id ID del reporte de movimiento a obtener.
     * @return object|null Objeto con los datos del reporte de movimiento si se encuentra, NULL si no.
     */
    public function get_reporte($reporte_id)
    {
        $tabla = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        return $this->wpdb->get_row(
            $this->wpdb->prepare( // Utiliza prepare para prevenir inyección SQL.
                "SELECT * FROM {$tabla} WHERE id = %d",
                $reporte_id
            )
        );
    }

    /**
     * Obtiene movimientos elegibles para ser incluidos en un reporte.
     *
     * Los movimientos elegibles son aquellos que no han sido reportados previamente.
     *
     * @param string $fecha_reporte Fecha para la que se genera el reporte (actualmente no utilizada en la consulta).
     * @return array Lista de movimientos elegibles para reporte.
     */
    public function get_movimientos_para_reporte($fecha_reporte)
    {
        $tabla_movimientos = $this->wpdb->prefix . 'gpv_movimientos';
        $query = $this->wpdb->prepare(
            "SELECT * FROM {$tabla_movimientos}
            WHERE reportado = 0
              AND hora_entrada IS NOT NULL
              AND distancia_recorrida > 30" // Criterios de elegibilidad: No reportado, con hora de entrada y distancia > 30km (ejemplo).
        );

        return $this->wpdb->get_results($query); // Ejecuta la consulta y retorna los movimientos elegibles.
    }

    /**
     * Obtiene la lista de firmantes autorizados activos.
     *
     * Utilizado para poblar los selectores de firmantes en los formularios de reportes.
     *
     * @param array $args Argumentos para filtrar los firmantes (opcional).
     *                   - 'activo': int (0 o 1), Filtra por estado activo del firmante.
     * @return array Lista de firmantes autorizados que cumplen con los criterios de búsqueda.
     */
    public function get_firmantes($args = [])
    {
        $tabla_firmantes = $this->wpdb->prefix . 'gpv_firmantes_autorizados'; // Asume nombre de tabla de firmantes.
        $query = "SELECT * FROM {$tabla_firmantes}"; // Consulta base.
        $where_clauses = []; // Cláusulas WHERE.
        $prepare_args = []; // Argumentos para prepare.

        if (!empty($args)) {
            if (isset($args['activo'])) {
                $where_clauses[] = 'activo = %d';
                $prepare_args[] = $args['activo'];
            }
            // Se pueden añadir más filtros aquí si es necesario.
        }

        if (!empty($where_clauses)) { // Añade cláusulas WHERE si existen.
            $query .= " WHERE " . implode(' AND ', $where_clauses);
        }

        return $this->wpdb->get_results($this->wpdb->prepare($query, $prepare_args)); // Ejecuta consulta preparada y retorna firmantes.
    }
    /**
     * Inserta un nuevo reporte de movimiento en la base de datos.
     *
     * @param array $data Datos del reporte a insertar.
     * @return int|false ID del reporte insertado en caso de éxito, FALSE en caso de error.
     */
    public function insert_reporte_movimiento($data)
    {
        $tabla_reportes_movimientos = $this->wpdb->prefix . 'gpv_reportes_movimientos';

        $result = $this->wpdb->insert($tabla_reportes_movimientos, $data);

        if ($result) {
            $reporte_id = $this->wpdb->insert_id;

            // Marcar movimientos como reportados y asociarlos al reporte
            $movimiento_ids = explode(',', $data['movimientos_incluidos']);
            $tabla_movimientos = $this->wpdb->prefix . 'gpv_movimientos';
            foreach ($movimiento_ids as $movimiento_id) {
                $this->wpdb->update(
                    $tabla_movimientos,
                    ['reportado' => 1, 'reporte_id' => $reporte_id],
                    ['id' => $movimiento_id],
                    ['%d', '%d'],
                    ['%d']
                );
            }
            return $reporte_id;
        }
        return false;
    }
}
