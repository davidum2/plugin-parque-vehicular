<?php
// admin/views/movimientos-listado.php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Salir si se accede directamente.
}

/**
 * Muestra el listado de movimientos en el panel de administración
 */
function gpv_listado_movimientos() {
    global $wpdb;
    $tabla = $wpdb->prefix . 'gpv_movimientos';

    // Usar join para obtener nombres de vehículos incluso si se han eliminado las referencias
    $sql = "SELECT m.*, v.siglas, v.nombre_vehiculo
            FROM $tabla AS m
            LEFT JOIN {$wpdb->prefix}gpv_vehiculos AS v ON m.vehiculo_id = v.id
            ORDER BY m.hora_salida DESC";

    $resultados = $wpdb->get_results($sql);

    echo '<div class="wrap">';
    echo '<h2>' . esc_html__( 'Movimientos Diarios', 'gestion-parque-vehicular' ) . '</h2>';

    // Botón para agregar nuevo movimiento
    echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_movimientos&action=new')) . '" class="page-title-action">';
    echo esc_html__('Registrar Movimiento', 'gestion-parque-vehicular');
    echo '</a>';

    echo '<table class="widefat">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>' . esc_html__( 'ID', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Vehículo', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Conductor', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Salida', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Entrada', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Distancia', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Combustible', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Estado', 'gestion-parque-vehicular' ) . '</th>';
    echo '<th>' . esc_html__( 'Acciones', 'gestion-parque-vehicular' ) . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    if( $resultados && !empty($resultados) ) {
        foreach ( $resultados as $movimiento ) {
            // Formatear fechas
            $salida_formateada = isset($movimiento->hora_salida) && !empty($movimiento->hora_salida)
                ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movimiento->hora_salida))
                : '';

            $entrada_formateada = isset($movimiento->hora_entrada) && !empty($movimiento->hora_entrada)
                ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movimiento->hora_entrada))
                : '';

            // Determinar clase de fila según estado
            $row_class = '';
            if (isset($movimiento->estado)) {
                if ($movimiento->estado === 'en_progreso') {
                    $row_class = 'gpv-en-progreso';
                } elseif ($movimiento->estado === 'completado') {
                    $row_class = 'gpv-completado';
                }
            }

            echo '<tr class="' . esc_attr($row_class) . '">';
            echo '<td>' . esc_html( $movimiento->id ) . '</td>';
            echo '<td>' . esc_html( $movimiento->siglas . ' - ' . $movimiento->nombre_vehiculo ) . '</td>';
            echo '<td>' . esc_html( $movimiento->conductor ) . '</td>';
            echo '<td>' .
                esc_html( $salida_formateada ) . '<br>' .
                '<small>' . esc_html__('Odómetro:', 'gestion-parque-vehicular') . ' ' . esc_html( $movimiento->odometro_salida ) . '</small>' .
            '</td>';
            echo '<td>';
            if (!empty($entrada_formateada)) {
                echo esc_html( $entrada_formateada ) . '<br>' .
                    '<small>' . esc_html__('Odómetro:', 'gestion-parque-vehicular') . ' ' . esc_html( $movimiento->odometro_entrada ) . '</small>';
            } else {
                echo esc_html__('En progreso', 'gestion-parque-vehicular');
            }
            echo '</td>';
            echo '<td>' . esc_html( !empty($movimiento->distancia_recorrida) ? number_format($movimiento->distancia_recorrida, 2) . ' km' : '-' ) . '</td>';
            echo '<td>' . esc_html( !empty($movimiento->combustible_consumido) ? number_format($movimiento->combustible_consumido, 2) . ' L' : '-' ) . '<br>' .
                 '<small>' . esc_html__('Nivel:', 'gestion-parque-vehicular') . ' ' . esc_html( !empty($movimiento->nivel_combustible) ? number_format($movimiento->nivel_combustible, 2) . '%' : '-' ) . '</small></td>';
            echo '<td>' . esc_html( isset($movimiento->estado) ? ucfirst($movimiento->estado) : '' ) . '</td>';
            echo '<td>';
            echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_movimientos&action=view&id=' . $movimiento->id)) . '" class="button-secondary">';
            echo esc_html__('Ver', 'gestion-parque-vehicular');
            echo '</a> ';

            if (isset($movimiento->estado) && $movimiento->estado === 'en_progreso') {
                echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_movimientos&action=complete&id=' . $movimiento->id)) . '" class="button-primary">';
                echo esc_html__('Completar', 'gestion-parque-vehicular');
                echo '</a> ';
            }

            echo '<a href="' . esc_url(admin_url('admin.php?page=gpv_movimientos&action=delete&id=' . $movimiento->id . '&nonce=' . wp_create_nonce('delete_movimiento_' . $movimiento->id))) . '" class="button-secondary" onclick="return confirm(\'' . esc_js(__('¿Estás seguro de que deseas eliminar este movimiento?', 'gestion-parque-vehicular')) . '\');">';
            echo esc_html__('Eliminar', 'gestion-parque-vehicular');
            echo '</a>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="9">' . esc_html__( 'No hay movimientos registrados.', 'gestion-parque-vehicular' ) . '</td></tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
