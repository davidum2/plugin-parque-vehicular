<?php

/**
 * Custom Post Type para Vehículos
 */
class GPV_Vehicle_Post_Type
{

    /**
     * Database instance
     *
     * @var GPV_Database
     */
    private $database;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $GPV_Database;
        $this->database = $GPV_Database;

        // Registrar Custom Post Type
        add_action('init', array($this, 'register_vehicle_post_type'));

        // Registrar meta boxes
        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));

        // Guardar meta datos
        add_action('save_post_gpv_vehiculo', array($this, 'save_vehicle_meta'));

        // Sincronizar Custom Post con tabla de vehículos
        add_action('save_post_gpv_vehiculo', array($this, 'sync_post_with_vehicle_table'), 20, 3);

        // Sincronizar tabla de vehículos con Custom Post
        add_action('gpv_after_vehicle_update', array($this, 'sync_vehicle_table_with_post'), 10, 2);

        // Programar sincronización periódica
        if (!wp_next_scheduled('gpv_sync_vehicles_data')) {
            wp_schedule_event(time(), 'hourly', 'gpv_sync_vehicles_data');
        }
        add_action('gpv_sync_vehicles_data', array($this, 'sync_all_vehicles'));
    }

    /**
     * Registrar el Custom Post Type para vehículos
     */
    public function register_vehicle_post_type()
    {
        $labels = array(
            'name'               => __('Vehículos', 'gestion-parque-vehicular'),
            'singular_name'      => __('Vehículo', 'gestion-parque-vehicular'),
            'menu_name'          => __('Vehículos GPV', 'gestion-parque-vehicular'),
            'add_new'            => __('Añadir Nuevo', 'gestion-parque-vehicular'),
            'add_new_item'       => __('Añadir Nuevo Vehículo', 'gestion-parque-vehicular'),
            'edit_item'          => __('Editar Vehículo', 'gestion-parque-vehicular'),
            'new_item'           => __('Nuevo Vehículo', 'gestion-parque-vehicular'),
            'view_item'          => __('Ver Vehículo', 'gestion-parque-vehicular'),
            'search_items'       => __('Buscar Vehículos', 'gestion-parque-vehicular'),
            'not_found'          => __('No se encontraron vehículos', 'gestion-parque-vehicular'),
            'not_found_in_trash' => __('No se encontraron vehículos en la papelera', 'gestion-parque-vehicular'),
        );

        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'vehiculos'),
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-car',
            'show_in_rest'        => true,
            'rest_base'           => 'gpv-vehiculos',
        );

        register_post_type('gpv_vehiculo', $args);
    }

    /**
     * Registrar metaboxes para los datos del vehículo
     */
    public function register_meta_boxes()
    {
        add_meta_box(
            'gpv_vehicle_data',
            __('Datos del Vehículo', 'gestion-parque-vehicular'),
            array($this, 'render_vehicle_meta_box'),
            'gpv_vehiculo',
            'normal',
            'high'
        );

        add_meta_box(
            'gpv_vehicle_status',
            __('Estado Actual', 'gestion-parque-vehicular'),
            array($this, 'render_vehicle_status_meta_box'),
            'gpv_vehiculo',
            'side',
            'high'
        );
    }

    /**
     * Renderizar metabox de datos del vehículo
     */
    public function render_vehicle_meta_box($post)
    {
        // Obtener metadatos
        $siglas = get_post_meta($post->ID, '_gpv_siglas', true);
        $anio = get_post_meta($post->ID, '_gpv_anio', true);
        $odometro = get_post_meta($post->ID, '_gpv_odometro', true);
        $nivel_combustible = get_post_meta($post->ID, '_gpv_nivel_combustible', true);
        $capacidad_tanque = get_post_meta($post->ID, '_gpv_capacidad_tanque', true);
        $tipo_combustible = get_post_meta($post->ID, '_gpv_tipo_combustible', true);
        $medida_odometro = get_post_meta($post->ID, '_gpv_medida_odometro', true);
        $factor_consumo = get_post_meta($post->ID, '_gpv_factor_consumo', true);
        $vehicle_id = get_post_meta($post->ID, '_gpv_vehicle_id', true);

        // Nonce para seguridad
        wp_nonce_field('gpv_vehicle_meta_nonce', 'gpv_vehicle_meta_nonce');

        // Mostrar campos
?>
        <table class="form-table">
            <tr>
                <th><label for="gpv_siglas"><?php _e('Siglas o Placa', 'gestion-parque-vehicular'); ?></label></th>
                <td><input type="text" id="gpv_siglas" name="gpv_siglas" value="<?php echo esc_attr($siglas); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="gpv_anio"><?php _e('Año', 'gestion-parque-vehicular'); ?></label></th>
                <td><input type="number" id="gpv_anio" name="gpv_anio" value="<?php echo esc_attr($anio); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th><label for="gpv_odometro"><?php _e('Odómetro Actual', 'gestion-parque-vehicular'); ?></label></th>
                <td><input type="number" id="gpv_odometro" name="gpv_odometro" value="<?php echo esc_attr($odometro); ?>" class="regular-text" step="0.1"></td>
            </tr>
            <tr>
                <th><label for="gpv_medida_odometro"><?php _e('Medida Odómetro', 'gestion-parque-vehicular'); ?></label></th>
                <td>
                    <select id="gpv_medida_odometro" name="gpv_medida_odometro">
                        <option value="Kilómetros" <?php selected($medida_odometro, 'Kilómetros'); ?>><?php _e('Kilómetros', 'gestion-parque-vehicular'); ?></option>
                        <option value="Millas" <?php selected($medida_odometro, 'Millas'); ?>><?php _e('Millas', 'gestion-parque-vehicular'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gpv_nivel_combustible"><?php _e('Nivel de Combustible (L)', 'gestion-parque-vehicular'); ?></label></th>
                <td><input type="number" id="gpv_nivel_combustible" name="gpv_nivel_combustible" value="<?php echo esc_attr($nivel_combustible); ?>" class="regular-text" step="0.1"></td>
            </tr>
            <tr>
                <th><label for="gpv_capacidad_tanque"><?php _e('Capacidad del Tanque (L)', 'gestion-parque-vehicular'); ?></label></th>
                <td><input type="number" id="gpv_capacidad_tanque" name="gpv_capacidad_tanque" value="<?php echo esc_attr($capacidad_tanque); ?>" class="regular-text" step="0.1"></td>
            </tr>
            <tr>
                <th><label for="gpv_tipo_combustible"><?php _e('Tipo de Combustible', 'gestion-parque-vehicular'); ?></label></th>
                <td>
                    <select id="gpv_tipo_combustible" name="gpv_tipo_combustible">
                        <option value="Gasolina" <?php selected($tipo_combustible, 'Gasolina'); ?>><?php _e('Gasolina', 'gestion-parque-vehicular'); ?></option>
                        <option value="Diesel" <?php selected($tipo_combustible, 'Diesel'); ?>><?php _e('Diesel', 'gestion-parque-vehicular'); ?></option>
                        <option value="Gas LP" <?php selected($tipo_combustible, 'Gas LP'); ?>><?php _e('Gas LP', 'gestion-parque-vehicular'); ?></option>
                        <option value="Eléctrico" <?php selected($tipo_combustible, 'Eléctrico'); ?>><?php _e('Eléctrico', 'gestion-parque-vehicular'); ?></option>
                        <option value="Híbrido" <?php selected($tipo_combustible, 'Híbrido'); ?>><?php _e('Híbrido', 'gestion-parque-vehicular'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="gpv_factor_consumo"><?php _e('Factor de Consumo (km/L)', 'gestion-parque-vehicular'); ?></label></th>
                <td><input type="number" id="gpv_factor_consumo" name="gpv_factor_consumo" value="<?php echo esc_attr($factor_consumo); ?>" class="regular-text" step="0.1"></td>
            </tr>
            <?php if ($vehicle_id) : ?>
                <tr>
                    <th><label><?php _e('ID en sistema GPV', 'gestion-parque-vehicular'); ?></label></th>
                    <td><code><?php echo esc_html($vehicle_id); ?></code></td>
                </tr>
            <?php endif; ?>
        </table>
    <?php
    }

    /**
     * Renderizar metabox de estado
     */
    public function render_vehicle_status_meta_box($post)
    {
        $estado = get_post_meta($post->ID, '_gpv_estado', true);
        $ubicacion = get_post_meta($post->ID, '_gpv_ubicacion', true);
        $ultima_actualizacion = get_post_meta($post->ID, '_gpv_ultima_actualizacion', true);

        // Porcentaje de combustible para mostrar
        $nivel_combustible = get_post_meta($post->ID, '_gpv_nivel_combustible', true);
        $capacidad_tanque = get_post_meta($post->ID, '_gpv_capacidad_tanque', true);
        $porcentaje = ($capacidad_tanque > 0) ? ($nivel_combustible / $capacidad_tanque) * 100 : 0;
    ?>
        <div class="gpv-vehicle-status">
            <p>
                <strong><?php _e('Estado:', 'gestion-parque-vehicular'); ?></strong><br>
                <select id="gpv_estado" name="gpv_estado">
                    <option value="disponible" <?php selected($estado, 'disponible'); ?>><?php _e('Disponible', 'gestion-parque-vehicular'); ?></option>
                    <option value="en_uso" <?php selected($estado, 'en_uso'); ?>><?php _e('En Uso', 'gestion-parque-vehicular'); ?></option>
                    <option value="mantenimiento" <?php selected($estado, 'mantenimiento'); ?>><?php _e('En Mantenimiento', 'gestion-parque-vehicular'); ?></option>
                </select>
            </p>

            <p>
                <strong><?php _e('Ubicación Actual:', 'gestion-parque-vehicular'); ?></strong><br>
                <input type="text" id="gpv_ubicacion" name="gpv_ubicacion" value="<?php echo esc_attr($ubicacion); ?>" class="widefat">
            </p>

            <p>
                <strong><?php _e('Combustible:', 'gestion-parque-vehicular'); ?></strong><br>
                <?php echo esc_html(number_format($nivel_combustible, 2)); ?> L
                (<?php echo esc_html(number_format($porcentaje, 1)); ?>%)
            </p>

            <?php if ($ultima_actualizacion) : ?>
                <p>
                    <strong><?php _e('Última Actualización:', 'gestion-parque-vehicular'); ?></strong><br>
                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($ultima_actualizacion))); ?>
                </p>
            <?php endif; ?>
        </div>
<?php
    }

    /**
     * Guardar metadatos del vehículo
     */
    public function save_vehicle_meta($post_id)
    {
        // Verificar nonce
        if (!isset($_POST['gpv_vehicle_meta_nonce']) || !wp_verify_nonce($_POST['gpv_vehicle_meta_nonce'], 'gpv_vehicle_meta_nonce')) {
            return;
        }

        // Verificar autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar campos
        $fields = array(
            'gpv_siglas' => '_gpv_siglas',
            'gpv_anio' => '_gpv_anio',
            'gpv_odometro' => '_gpv_odometro',
            'gpv_medida_odometro' => '_gpv_medida_odometro',
            'gpv_nivel_combustible' => '_gpv_nivel_combustible',
            'gpv_tipo_combustible' => '_gpv_tipo_combustible',
            'gpv_capacidad_tanque' => '_gpv_capacidad_tanque',
            'gpv_factor_consumo' => '_gpv_factor_consumo',
            'gpv_estado' => '_gpv_estado',
            'gpv_ubicacion' => '_gpv_ubicacion',
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }

        // Actualizar última actualización
        update_post_meta($post_id, '_gpv_ultima_actualizacion', current_time('mysql'));
    }

    /**
     * Sincronizar Custom Post con tabla de vehículos
     */
    public function sync_post_with_vehicle_table($post_id, $post, $update)
    {
        // Evitar recursión infinita
        remove_action('gpv_after_vehicle_update', array($this, 'sync_vehicle_table_with_post'), 10);

        // Verificar tipo de post
        if ($post->post_type !== 'gpv_vehiculo') {
            return;
        }

        // Obtener meta datos
        $siglas = get_post_meta($post_id, '_gpv_siglas', true);
        $anio = intval(get_post_meta($post_id, '_gpv_anio', true));
        $odometro = floatval(get_post_meta($post_id, '_gpv_odometro', true));
        $nivel_combustible = floatval(get_post_meta($post_id, '_gpv_nivel_combustible', true));
        $capacidad_tanque = floatval(get_post_meta($post_id, '_gpv_capacidad_tanque', true));
        $tipo_combustible = get_post_meta($post_id, '_gpv_tipo_combustible', true);
        $medida_odometro = get_post_meta($post_id, '_gpv_medida_odometro', true);
        $factor_consumo = floatval(get_post_meta($post_id, '_gpv_factor_consumo', true));
        $estado = get_post_meta($post_id, '_gpv_estado', true);
        $ubicacion = get_post_meta($post_id, '_gpv_ubicacion', true);
        $vehicle_id = get_post_meta($post_id, '_gpv_vehicle_id', true);

        // Verificar si es actualización o nuevo vehículo
        if ($vehicle_id) {
            // Actualizar vehículo existente
            $data = array(
                'siglas' => $siglas,
                'anio' => $anio,
                'nombre_vehiculo' => $post->post_title,
                'odometro_actual' => $odometro,
                'nivel_combustible' => $nivel_combustible,
                'tipo_combustible' => $tipo_combustible,
                'medida_odometro' => $medida_odometro,
                'factor_consumo' => $factor_consumo,
                'capacidad_tanque' => $capacidad_tanque,
                'ubicacion_actual' => $ubicacion,
                'estado' => $estado,
                'ultima_actualizacion' => current_time('mysql')
            );

            $this->database->update_vehicle($vehicle_id, $data);
        } else {
            // Crear nuevo vehículo
            $data = array(
                'siglas' => $siglas,
                'anio' => $anio,
                'nombre_vehiculo' => $post->post_title,
                'odometro_actual' => $odometro,
                'nivel_combustible' => $nivel_combustible,
                'tipo_combustible' => $tipo_combustible,
                'medida_odometro' => $medida_odometro,
                'factor_consumo' => $factor_consumo,
                'capacidad_tanque' => $capacidad_tanque,
                'ubicacion_actual' => $ubicacion,
                'estado' => $estado,
                'ultima_actualizacion' => current_time('mysql')
            );

            $new_id = $this->database->insert_vehicle($data);

            if ($new_id) {
                // Guardar el ID del vehículo en el post
                update_post_meta($post_id, '_gpv_vehicle_id', $new_id);
            }
        }

        // Restaurar acción
        add_action('gpv_after_vehicle_update', array($this, 'sync_vehicle_table_with_post'), 10, 2);
    }

    /**
     * Sincronizar tabla de vehículos con Custom Post
     */
    public function sync_vehicle_table_with_post($vehicle_id, $data)
    {
        // Buscar post existente por ID de vehículo
        $args = array(
            'post_type' => 'gpv_vehiculo',
            'meta_key' => '_gpv_vehicle_id',
            'meta_value' => $vehicle_id,
            'posts_per_page' => 1
        );

        $posts = get_posts($args);

        // Datos del vehículo
        $vehicle = $this->database->get_vehicle($vehicle_id);

        if (empty($vehicle)) {
            return;
        }

        // Si existe un post, actualizarlo
        if (!empty($posts)) {
            $post_id = $posts[0]->ID;

            // Evitar recursión infinita
            remove_action('save_post_gpv_vehiculo', array($this, 'sync_post_with_vehicle_table'), 20);

            // Actualizar post
            wp_update_post(array(
                'ID' => $post_id,
                'post_title' => $vehicle->nombre_vehiculo,
                'post_content' => isset($data['descripcion']) ? $data['descripcion'] : '',
                'post_status' => 'publish'
            ));

            // Actualizar meta datos
            update_post_meta($post_id, '_gpv_siglas', $vehicle->siglas);
            update_post_meta($post_id, '_gpv_anio', $vehicle->anio);
            update_post_meta($post_id, '_gpv_odometro', $vehicle->odometro_actual);
            update_post_meta($post_id, '_gpv_nivel_combustible', $vehicle->nivel_combustible);
            update_post_meta($post_id, '_gpv_tipo_combustible', $vehicle->tipo_combustible);
            update_post_meta($post_id, '_gpv_medida_odometro', $vehicle->medida_odometro);
            update_post_meta($post_id, '_gpv_factor_consumo', $vehicle->factor_consumo);
            update_post_meta($post_id, '_gpv_capacidad_tanque', $vehicle->capacidad_tanque);
            update_post_meta($post_id, '_gpv_estado', $vehicle->estado);
            update_post_meta($post_id, '_gpv_ubicacion', $vehicle->ubicacion_actual);
            update_post_meta($post_id, '_gpv_ultima_actualizacion', $vehicle->ultima_actualizacion);

            // Restaurar acción
            add_action('save_post_gpv_vehiculo', array($this, 'sync_post_with_vehicle_table'), 20, 3);
        } else {
            // Crear nuevo post
            $post_data = array(
                'post_title' => $vehicle->nombre_vehiculo,
                'post_content' => isset($data['descripcion']) ? $data['descripcion'] : '',
                'post_status' => 'publish',
                'post_type' => 'gpv_vehiculo',
            );

            // Evitar recursión infinita
            remove_action('save_post_gpv_vehiculo', array($this, 'sync_post_with_vehicle_table'), 20);

            // Insertar post
            $post_id = wp_insert_post($post_data);

            if (!is_wp_error($post_id)) {
                // Añadir meta datos
                update_post_meta($post_id, '_gpv_vehicle_id', $vehicle_id);
                update_post_meta($post_id, '_gpv_siglas', $vehicle->siglas);
                update_post_meta($post_id, '_gpv_anio', $vehicle->anio);
                update_post_meta($post_id, '_gpv_odometro', $vehicle->odometro_actual);
                update_post_meta($post_id, '_gpv_nivel_combustible', $vehicle->nivel_combustible);
                update_post_meta($post_id, '_gpv_tipo_combustible', $vehicle->tipo_combustible);
                update_post_meta($post_id, '_gpv_medida_odometro', $vehicle->medida_odometro);
                update_post_meta($post_id, '_gpv_factor_consumo', $vehicle->factor_consumo);
                update_post_meta($post_id, '_gpv_capacidad_tanque', $vehicle->capacidad_tanque);
                update_post_meta($post_id, '_gpv_estado', $vehicle->estado);
                update_post_meta($post_id, '_gpv_ubicacion', $vehicle->ubicacion_actual);
                update_post_meta($post_id, '_gpv_ultima_actualizacion', $vehicle->ultima_actualizacion);
            }

            // Restaurar acción
            add_action('save_post_gpv_vehiculo', array($this, 'sync_post_with_vehicle_table'), 20, 3);
        }
    }

    /**
     * Sincronizar todos los vehículos
     */
    public function sync_all_vehicles()
    {
        $vehicles = $this->database->get_vehicles();

        foreach ($vehicles as $vehicle) {
            $this->sync_vehicle_table_with_post($vehicle->id, array());
        }
    }
}

// Inicializar
$gpv_vehicle_post_type = new GPV_Vehicle_Post_Type();
