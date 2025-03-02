
<?php
// frontend/views/driver-dashboard.php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

/**
 * Shortcode para mostrar el panel del conductor
 *
 * @return string HTML del panel
 */
function gpv_driver_dashboard() {
    // Verificar que el usuario esté logueado
    if (!is_user_logged_in()) {
        return '<div class="error">' . esc_html__('Debe iniciar sesión para acceder al panel de conductor.', 'gestion-parque-vehicular') . '</div>';
    }

    global $wpdb;
    $current_user = wp_get_current_user();
    $current_user_id = $current_user->ID;

    // Obtener movimientos activos del usuario
    $tabla_movimientos = $wpdb->prefix . 'gpv_movimientos';
    $movimientos_activos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $tabla_movimientos
            WHERE conductor_id = %d
            AND estado = 'en_progreso'
            ORDER BY hora_salida DESC",
            $current_user_id
        )
    );

    // Obtener movimientos recientes (completados)
    $movimientos_recientes = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $tabla_movimientos
            WHERE conductor_id = %d
            AND estado = 'completado'
            ORDER BY hora_entrada DESC
            LIMIT 5",
            $current_user_id
        )
    );

    // Obtener estadísticas básicas
    $total_movimientos = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_movimientos WHERE conductor_id = %d",
            $current_user_id
        )
    );

    $total_distancia = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(distancia_recorrida) FROM $tabla_movimientos
            WHERE conductor_id = %d AND estado = 'completado'",
            $current_user_id
        )
    );

    $total_combustible = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(combustible_consumido) FROM $tabla_movimientos
            WHERE conductor_id = %d AND estado = 'completado'",
            $current_user_id
        )
    );

    ob_start();
    ?>
    <div class="gpv-dashboard-container">
        <h2><?php esc_html_e('Panel de Conductor', 'gestion-parque-vehicular'); ?></h2>

        <div class="gpv-welcome-message">
            <p><?php printf(esc_html__('Bienvenido, %s', 'gestion-parque-vehicular'), $current_user->display_name); ?></p>
        </div>

        <div class="gpv-stats-container">
            <div class="gpv-stat-card">
                <div class="gpv-stat-icon">
                    <span class="dashicons dashicons-location-alt"></span>
                </div>
                <div class="gpv-stat-content">
                    <span class="gpv-stat-number"><?php echo esc_html($total_movimientos ? $total_movimientos : '0'); ?></span>
                    <span class="gpv-stat-label"><?php esc_html_e('Movimientos Totales', 'gestion-parque-vehicular'); ?></span>
                </div>
            </div>

            <div class="gpv-stat-card">
                <div class="gpv-stat-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <div class="gpv-stat-content">
                    <span class="gpv-stat-number"><?php echo esc_html($total_distancia ? number_format($total_distancia, 2) : '0'); ?> km</span>
                    <span class="gpv-stat-label"><?php esc_html_e('Distancia Total', 'gestion-parque-vehicular'); ?></span>
                </div>
            </div>

            <div class="gpv-stat-card">
                <div class="gpv-stat-icon">
                    <span class="dashicons dashicons-dashboard"></span>
                </div>
                <div class="gpv-stat-content">
                    <span class="gpv-stat-number"><?php echo esc_html($total_combustible ? number_format($total_combustible, 2) : '0'); ?> L</span>
                    <span class="gpv-stat-label"><?php esc_html_e('Combustible Consumido', 'gestion-parque-vehicular'); ?></span>
                </div>
            </div>
        </div>
