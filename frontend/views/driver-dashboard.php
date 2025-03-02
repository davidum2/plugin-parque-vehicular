<?php

/**
 * Driver Dashboard for Vehicle Fleet Management
 *
 * @package GPV
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GPV_Driver_Dashboard
{
    private $database;
    private $user_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $GPV_Database;
        $this->database = $GPV_Database;
        $this->user_id = get_current_user_id();

        // Registrar shortcode para el panel de conductor
        add_shortcode('gpv_driver_dashboard', array($this, 'render_driver_dashboard'));
    }

    /**
     * Renderizar dashboard del conductor
     *
     * @return string HTML del dashboard
     */
    public function render_driver_dashboard()
    {
        // Verificar permisos
        if (!current_user_can('gpv_register_movements') && !current_user_can('gpv_register_fuel')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') . '</div>';
        }

        // Obtener vehículos asignados al conductor
        $vehicles = $this->get_assigned_vehicles();

        // Obtener movimientos recientes
        $recent_movements = $this->get_recent_movements();

        // Obtener cargas de combustible recientes
        $recent_fuels = $this->get_recent_fuels();

        // Obtener próximos mantenimientos
        $upcoming_maintenances = $this->get_upcoming_maintenances();

        ob_start();
?>
        <div class="gpv-driver-dashboard">
            <div class="gpv-dashboard-header">
                <h1><?php _e('Panel del Conductor', 'gestion-parque-vehicular'); ?></h1>
            </div>

            <div class="gpv-dashboard-sections">
                <!-- Sección de Vehículos Asignados -->
                <section class="gpv-section vehicles">
                    <h2><?php _e('Mis Vehículos', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_assigned_vehicles($vehicles); ?>
                </section>

                <!-- Sección de Movimientos Recientes -->
                <section class="gpv-section movements">
                    <h2><?php _e('Movimientos Recientes', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_recent_movements($recent_movements); ?>
                </section>

                <!-- Sección de Cargas de Combustible -->
                <section class="gpv-section fuels">
                    <h2><?php _e('Cargas de Combustible', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_recent_fuels($recent_fuels); ?>
                </section>

                <!-- Sección de Mantenimientos Próximos -->
                <section class="gpv-section maintenances">
                    <h2><?php _e('Mantenimientos Próximos', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_upcoming_maintenances($upcoming_maintenances); ?>
                </section>
            </div>

            <!-- Botones de Acción Rápida -->
            <div class="gpv-quick-actions">
                <a href="<?php echo esc_url(add_query_arg('gpv_action', 'new_movement', get_permalink())); ?>" class="gpv-button gpv-button-primary">
                    <?php _e('Registrar Movimiento', 'gestion-parque-vehicular'); ?>
                </a>
                <a href="<?php echo esc_url(add_query_arg('gpv_action', 'new_fuel', get_permalink())); ?>" class="gpv-button gpv-button-secondary">
                    <?php _e('Registrar Carga de Combustible', 'gestion-parque-vehicular'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener vehículos asignados al conductor
     *
     * @return array Vehículos asignados
     */
    private function get_assigned_vehicles()
    {
        $args = array(
            'conductor_asignado' => $this->user_id,
            'estado' => array('disponible', 'en_uso')
        );
        return $this->database->get_vehicles($args);
    }

    /**
     * Renderizar vehículos asignados
     *
     * @param array $vehicles Vehículos a renderizar
     */
    private function render_assigned_vehicles($vehicles)
    {
        if (empty($vehicles)) {
            echo '<p>' . __('No tienes vehículos asignados.', 'gestion-parque-vehicular') . '</p>';
            return;
        }

        echo '<div class="gpv-vehicles-grid">';
        foreach ($vehicles as $vehicle) {
        ?>
            <div class="gpv-vehicle-card">
                <div class="gpv-vehicle-header">
                    <h3><?php echo esc_html($vehicle->siglas . ' - ' . $vehicle->nombre_vehiculo); ?></h3>
                    <span class="gpv-status <?php echo esc_attr(strtolower($vehicle->estado)); ?>">
                        <?php echo esc_html(ucfirst($vehicle->estado)); ?>
                    </span>
                </div>
                <div class="gpv-vehicle-details">
                    <p>
                        <strong><?php _e('Odómetro:', 'gestion-parque-vehicular'); ?></strong>
                        <?php echo esc_html($vehicle->odometro_actual . ' ' . $vehicle->medida_odometro); ?>
                    </p>
                    <p>
                        <strong><?php _e('Combustible:', 'gestion-parque-vehicular'); ?></strong>
                        <span class="<?php echo $vehicle->nivel_combustible <= 20 ? 'gpv-low-fuel' : ''; ?>">
                            <?php echo esc_html(number_format($vehicle->nivel_combustible, 2) . '%'); ?>
                        </span>
                    </p>
                    <p>
                        <strong><?php _e('Tipo:', 'gestion-parque-vehicular'); ?></strong>
                        <?php echo esc_html($vehicle->tipo_combustible); ?>
                    </p>
                </div>
                <div class="gpv-vehicle-actions">
                    <a href="<?php echo esc_url(add_query_arg('vehicle_id', $vehicle->id, get_permalink())); ?>" class="gpv-button">
                        <?php _e('Detalles', 'gestion-parque-vehicular'); ?>
                    </a>
                </div>
            </div>
<?php
        }
        echo '</div>';
    }

    /**
     * Obtener movimientos recientes del conductor
     *
     * @return array Movimientos recientes
     */
    private function get_recent_movements()
    {
        $args = array(
            'conductor_id' => $this->user_id,
            'limit' => 5,
            'orderby' => 'hora_salida',
            'order' => 'DESC'
        );
        return $this->database->get_movements($args);
    }

    /**
     * Renderizar movimientos recientes
     *
     * @param array $movements Movimientos a renderizar
     */
    private function render_recent_movements($movements)
    {
        if (empty($movements)) {
            echo '<p>' . __('No hay movimientos recientes.', 'gestion-parque-vehicular') . '</p>';
            return;
        }

        echo '<table class="gpv-data-table">';
        echo '<thead><tr>';
        echo '<th>' . __('Vehículo', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Salida', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Entrada', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Distancia', 'gestion-parque-vehicular') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($movements as $movement) {
            echo '<tr>';
            echo '<td>' . esc_html($movement->vehiculo_siglas . ' - ' . $movement->vehiculo_nombre) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movement->hora_salida))) . '</td>';
            echo '<td>';
            if ($movement->hora_entrada) {
                echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movement->hora_entrada)));
            } else {
                echo '<span class="gpv-in-progress">' . __('En Progreso', 'gestion-parque-vehicular') . '</span>';
            }
            echo '</td>';
            echo '<td>' . esc_html($movement->distancia_recorrida ? number_format($movement->distancia_recorrida, 2) . ' km' : '-') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Obtener cargas de combustible recientes
     *
     * @return array Cargas de combustible
     */
    private function get_recent_fuels()
    {
        $args = array(
            'conductor_id' => $this->user_id,
            'limit' => 5,
            'orderby' => 'fecha_carga',
            'order' => 'DESC'
        );
        return $this->database->get_fuels($args);
    }

    /**
     * Renderizar cargas de combustible recientes
     *
     * @param array $fuels Cargas a renderizar
     */
    private function render_recent_fuels($fuels)
    {
        if (empty($fuels)) {
            echo '<p>' . __('No hay cargas de combustible recientes.', 'gestion-parque-vehicular') . '</p>';
            return;
        }

        echo '<table class="gpv-data-table">';
        echo '<thead><tr>';
        echo '<th>' . __('Vehículo', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Fecha', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Litros', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Precio', 'gestion-parque-vehicular') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($fuels as $fuel) {
            echo '<tr>';
            echo '<td>' . esc_html($fuel->vehiculo_siglas . ' - ' . $fuel->vehiculo_nombre) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($fuel->fecha_carga))) . '</td>';
            echo '<td>' . esc_html(number_format($fuel->litros_cargados, 2) . ' L') . '</td>';
            echo '<td>$' . esc_html(number_format($fuel->precio * $fuel->litros_cargados, 2)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Obtener mantenimientos próximos
     *
     * @return array Mantenimientos próximos
     */
    private function get_upcoming_maintenances()
    {
        $args = array(
            'estado' => 'programado',
            'fecha_desde' => date('Y-m-d'),
            'fecha_hasta' => date('Y-m-d', strtotime('+30 days'))
        );
        return $this->database->get_maintenances($args);
    }

    /**
     * Renderizar mantenimientos próximos
     *
     * @param array $maintenances Mantenimientos a renderizar
     */
    private function render_upcoming_maintenances($maintenances)
    {
        if (empty($maintenances)) {
            echo '<p>' . __('No hay mantenimientos próximos.', 'gestion-parque-vehicular') . '</p>';
            return;
        }

        echo '<table class="gpv-data-table">';
        echo '<thead><tr>';
        echo '<th>' . __('Vehículo', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Tipo', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Fecha', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Descripción', 'gestion-parque-vehicular') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($maintenances as $maintenance) {
            $dias_restantes = round((strtotime($maintenance->fecha_programada) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));

            echo '<tr>';
            echo '<td>' . esc_html($maintenance->vehiculo_siglas . ' - ' . $maintenance->vehiculo_nombre) . '</td>';
            echo '<td>' . esc_html($maintenance->tipo) . '</td>';
            echo '<td>';
            echo esc_html(date_i18n(get_option('date_format'), strtotime($maintenance->fecha_programada)));
            echo ' <small>(' . sprintf(__('%d días', 'gestion-parque-vehicular'), $dias_restantes) . ')</small>';
            echo '</td>';
            echo '<td>' . esc_html($maintenance->descripcion) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}

// Inicializar el dashboard del conductor
$gpv_driver_dashboard = new GPV_Driver_Dashboard();
