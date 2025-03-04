<?php

/**
 * Vista para la página principal del panel de administración de GPV.
 *
 * Muestra un resumen del sistema, estadísticas clave, y enlaces rápidos a las
 * funcionalidades principales del plugin.
 *
 * @package GestionParqueVehicular
 */

// Seguridad: Salir si se accede directamente.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza la página principal del panel de administración.
 *
 * Muestra el título de la página, un mensaje de bienvenida, widgets con estadísticas
 * de vehículos, movimientos y combustible, y enlaces rápidos a las secciones principales.
 *
 * @return void
 */
function gpv_pagina_principal_view()
{
    global $GPV_Database; // Acceder a la instancia global de la base de datos
    $stats = $GPV_Database->obtener_estadisticas_dashboard(); // Obtener datos estadísticos para el dashboard

?>
    <div class="wrap gpv-admin-page">
        <h2><?php esc_html_e('Gestión de Parque Vehicular', 'gestion-parque-vehicular'); ?></h2>
        <p><?php esc_html_e('Bienvenido al panel de administración del Parque Vehicular.', 'gestion-parque-vehicular'); ?></p>

        <div class="gpv-dashboard-widgets">

            <div class="gpv-dashboard-widget">
                <h3><?php esc_html_e('Vehículos', 'gestion-parque-vehicular'); ?></h3>
                <div class="gpv-widget-content">
                    <div class="gpv-stat-card">
                        <div class="gpv-stat-icon"><span class="dashicons dashicons-car"></span></div>
                        <div class="gpv-stat-details">
                            <h4><?php echo esc_html($stats['vehicles']['total']); ?></h4>
                            <p><?php esc_html_e('Total de vehículos', 'gestion-parque-vehicular'); ?></p>
                        </div>
                    </div>
                    <p><?php esc_html_e('Disponibles', 'gestion-parque-vehicular'); ?>: <?php echo esc_html($stats['vehicles']['available']); ?></p>
                    <p><?php esc_html_e('En uso', 'gestion-parque-vehicular'); ?>: <?php echo esc_html($stats['vehicles']['in_use']); ?></p>
                </div>
            </div>

            <div class="gpv-dashboard-widget">
                <h3><?php esc_html_e('Movimientos', 'gestion-parque-vehicular'); ?></h3>
                <div class="gpv-widget-content">
                    <div class="gpv-stat-card">
                        <div class="gpv-stat-icon"><span class="dashicons dashicons-location"></span></div>
                        <div class="gpv-stat-details">
                            <h4><?php echo esc_html($stats['movements']['today']); ?></h4>
                            <p><?php esc_html_e('Movimientos hoy', 'gestion-parque-vehicular'); ?></p>
                        </div>
                    </div>
                    <p><?php esc_html_e('Este mes', 'gestion-parque-vehicular'); ?>: <?php echo esc_html($stats['movements']['month']); ?></p>
                    <p><?php esc_html_e('Distancia total', 'gestion-parque-vehicular'); ?>: <?php echo esc_html(number_format($stats['movements']['total_distance'], 2)); ?> km</p>
                    <p><?php esc_html_e('Movimientos activos', 'gestion-parque-vehicular'); ?>: <?php echo esc_html($stats['movements']['active']); ?></p>
                </div>
            </div>

            <div class="gpv-dashboard-widget">
                <h3><?php esc_html_e('Combustible', 'gestion-parque-vehicular'); ?></h3>
                <div class="gpv-widget-content">
                    <div class="gpv-stat-card">
                        <div class="gpv-stat-icon"><span class="dashicons dashicons-dashboard"></span></div>
                        <div class="gpv-stat-details">
                            <h4><?php echo esc_html(number_format($stats['fuel']['month_consumption'], 2)); ?> L</h4>
                            <p><?php esc_html_e('Consumo mensual', 'gestion-parque-vehicular'); ?></p>
                        </div>
                    </div>
                    <p><?php esc_html_e('Consumo promedio', 'gestion-parque-vehicular'); ?>: <?php echo esc_html(number_format($stats['fuel']['average_consumption'], 2)); ?> km/L</p>
                </div>
            </div>

        </div>
        <h3><?php esc_html_e('Enlaces Rápidos', 'gestion-parque-vehicular'); ?></h3>
        <ul class="admin-links">
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=gpv_vehiculos')); ?>"><?php esc_html_e('Gestionar Vehículos', 'gestion-parque-vehicular'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=gpv_movimientos')); ?>"><?php esc_html_e('Ver Movimientos Diarios', 'gestion-parque-vehicular'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=gpv_cargas')); ?>"><?php esc_html_e('Ver Cargas de Combustible', 'gestion-parque-vehicular'); ?></a></li>
        </ul>

    </div> <?php
        }
