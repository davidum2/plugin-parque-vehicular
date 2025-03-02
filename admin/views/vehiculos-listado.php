<?php
// admin/views/vehiculos-listado.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Muestra el listado de vehículos en el panel de administración
 */
function gpv_listado_vehiculos() {
    // Verificar si estamos en acción de nuevo vehículo o editar
    if (isset($_GET['action']) && ($_GET['action'] === 'new' || ($_GET['action'] === 'edit' && isset($_GET['id'])))) {
        // Incluir y mostrar el formulario de vehículo
        include_once plugin_dir_path( __FILE__ ) . 'formulario-vehiculo.php';
        return;
    }

    // Verificar si venimos de crear o actualizar un vehículo (mostrar mensaje)
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'created') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Vehículo creado correctamente.', 'gestion-parque-vehicular') . '</p></div>';
        } else if ($_GET['message'] === 'updated') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Vehículo actualizado correctamente.', 'gestion-parque-vehicular') . '</p></div>';
        } else if ($_GET['message'] === 'deleted') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Vehículo eliminado correctamente.', 'gestion-parque-vehicular') . '</p></div>';
        }
    }

    // Código existente para el listado
    global $wpdb;
    $tabla = $wpdb->prefix . 'gpv_vehiculos';
    $resultados = $wpdb->get_results( "SELECT * FROM $tabla ORDER BY nombre_vehiculo ASC" );

    echo '<div class="wrap">';
    echo '<h2>' . esc_html__( 'Vehículos', 'gestion-parque-vehicular' ) . '</h2>';

    // Botón para agregar nuevo vehículo
    echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_vehiculos&action=new')) . '" class="page-title-action">';
    echo esc_html__('Agregar Nuevo', 'gestion-parque-vehicular');
    echo '</a>';

    echo '<table class="widefat">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . esc_html__( 'ID', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Siglas', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Nombre', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Año', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Odómetro', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Nivel Comb.', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Tipo Comb.', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Factor', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Estado', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Acciones', 'gestion-parque-vehicular' ) . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if( $resultados && !empty($resultados) ) {
        foreach ( $resultados as $vehiculo ) {
            // Determinar clase para nivel de combustible
            $fuel_class = '';
            if (isset($vehiculo->nivel_combustible)) {
                if ($vehiculo->nivel_combustible <= 20) {
                    $fuel_class = 'gpv-nivel-bajo';
                } elseif ($vehiculo->nivel_combustible <= 40) {
                    $fuel_class = 'gpv-nivel-medio';
                }
            }

            // Determinar clase para estado
            $status_class = '';
            if (isset($vehiculo->estado)) {
                if ($vehiculo->estado === 'disponible') {
                    $status_class = 'gpv-disponible';
                } elseif ($vehiculo->estado === 'en_uso') {
                    $status_class = 'gpv-en-uso';
                } elseif ($vehiculo->estado === 'mantenimiento') {
                    $status_class = 'gpv-mantenimiento';
                }
            }

            echo '<tr>';
            echo '<td>' . esc_html( $vehiculo->id ) . '</td>';
            echo '<td>' . esc_html( $vehiculo->siglas ) . '</td>';
            echo '<td>' . esc_html( $vehiculo->nombre_vehiculo ) . '</td>';
            echo '<td>' . esc_html( $vehiculo->anio ) . '</td>';
            echo '<td>' . esc_html( $vehiculo->odometro_actual ) . ' ' . esc_html( $vehiculo->medida_odometro ) . '</td>';
            echo '<td class="' . esc_attr($fuel_class) . '">' . esc_html( number_format($vehiculo->nivel_combustible, 2) ) . '%</td>';
            echo '<td>' . esc_html( $vehiculo->tipo_combustible ) . '</td>';
            echo '<td>' . esc_html( $vehiculo->factor_consumo ) . '</td>';
            echo '<td class="' . esc_attr($status_class) . '">' . esc_html( ucfirst($vehiculo->estado) ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_vehiculos&action=edit&id=' . $vehiculo->id)) . '" class="button-secondary">';
            echo esc_html__('Editar', 'gestion-parque-vehicular');
            echo '</a> ';

            // Botón de mantenimiento si está disponible
            if (isset($vehiculo->estado) && $vehiculo->estado === 'disponible') {
                echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_mantenimientos&action=new&vehiculo_id=' . $vehiculo->id)) . '" class="button-secondary">';
                echo esc_html__('Programar Mantenimiento', 'gestion-parque-vehicular');
                echo '</a> ';
            }

            echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_vehiculos&action=delete&id=' . $vehiculo->id . '&nonce=' . wp_create_nonce('delete_vehiculo_' . $vehiculo->id))) . '" class="button-secondary" onclick="return confirm(\'' . esc_js(__('¿Estás seguro de que deseas eliminar este vehículo?', 'gestion-parque-vehicular')) . '\');">';
            echo esc_html__('Eliminar', 'gestion-parque-vehicular');
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="10">' . esc_html__( 'No hay vehículos registrados.', 'gestion-parque-vehicular' ) . '</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/**
 * Procesa la eliminación de un vehículo
 */
function gpv_procesar_eliminar_vehiculo() {
    if (isset($_GET['page']) && $_GET['page'] === 'gpv_vehiculos' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && isset($_GET['nonce'])) {
        $vehiculo_id = intval($_GET['id']);
        $nonce = sanitize_text_field($_GET['nonce']);

        // Verificar nonce
        if (wp_verify_nonce($nonce, 'delete_vehiculo_' . $vehiculo_id)) {
            global $GPV_Database;

            // Eliminar vehículo
            $result = $GPV_Database->delete_vehicle($vehiculo_id);

            // Redirigir con mensaje
            wp_redirect(admin_url('admin.php?page=gpv_vehiculos&message=deleted'));
            exit;
        }
    }
}
add_action('admin_init', 'gpv_procesar_eliminar_vehiculo');
