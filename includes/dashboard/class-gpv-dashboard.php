<?php

/**
 * Driver Dashboard Class for Vehicle Fleet Management
 *
 * @package GPV
 * @subpackage Dashboard
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class GPV_Driver_Dashboard
{
    /**
     * Database instance
     *
     * @var GPV_Database
     */
    private $database;

    /**
     * Current user ID
     *
     * @var int
     */
    private $user_id;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Initialize database
        global $GPV_Database;
        $this->database = $GPV_Database;

        // Get current user ID
        $this->user_id = get_current_user_id();

        // Register shortcodes
        add_shortcode('gpv_driver_dashboard', array($this, 'render_driver_dashboard'));
    }

    /**
     * Render driver dashboard
     *
     * @return string HTML content of the driver dashboard
     */
    public function render_driver_dashboard()
    {
        // Check user permissions
        if (
            !current_user_can('gpv_register_movements') &&
            !current_user_can('gpv_register_fuel') &&
            !current_user_can('gpv_view_own_records')
        ) {

            return '<div class="gpv-error">' .
                __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') .
                '</div>';
        }

        // Prepare dashboard data
        $data = $this->prepare_dashboard_data();

        // Start output buffering
        ob_start();
?>
        <div id="gpv-driver-dashboard-container" class="gpv-driver-dashboard">
            <!-- Dashboard header -->
            <div class="gpv-dashboard-header">
                <h1><?php _e('Panel del Conductor', 'gestion-parque-vehicular'); ?></h1>
                <div class="gpv-dashboard-actions">
                    <a href="#" class="gpv-btn gpv-btn-primary" data-action="new-movement">
                        <?php _e('Registrar Movimiento', 'gestion-parque-vehicular'); ?>
                    </a>
                    <a href="#" class="gpv-btn gpv-btn-secondary" data-action="new-fuel-load">
                        <?php _e('Registrar Carga de Combustible', 'gestion-parque-vehicular'); ?>
                    </a>
                </div>
            </div>

            <!-- Dashboard sections -->
            <div class="gpv-dashboard-grid">
                <!-- Assigned Vehicles Section -->
                <section class="gpv-dashboard-section gpv-vehicles">
                    <h2><?php _e('Mis Vehículos', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_vehicles_section($data['vehicles']); ?>
                </section>

                <!-- Recent Movements Section -->
                <section class="gpv-dashboard-section gpv-movements">
                    <h2><?php _e('Movimientos Recientes', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_movements_section($data['movements']); ?>
                </section>

                <!-- Fuel Loads Section -->
                <section class="gpv-dashboard-section gpv-fuel-loads">
                    <h2><?php _e('Cargas de Combustible', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_fuel_loads_section($data['fuel_loads']); ?>
                </section>

                <!-- Upcoming Maintenances Section -->
                <section class="gpv-dashboard-section gpv-maintenances">
                    <h2><?php _e('Mantenimientos Próximos', 'gestion-parque-vehicular'); ?></h2>
                    <?php $this->render_maintenances_section($data['maintenances']); ?>
                </section>
            </div>
        </div>

        <!-- Modal containers for forms -->
        <div id="gpv-movement-modal" class="gpv-modal" style="display:none;">
            <?php $this->render_movement_form(); ?>
        </div>

        <div id="gpv-fuel-load-modal" class="gpv-modal" style="display:none;">
            <?php $this->render_fuel_load_form(); ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Prepare dashboard data
     *
     * @return array Dashboard data
     */
    private function prepare_dashboard_data()
    {
        return array(
            'vehicles' => $this->get_assigned_vehicles(),
            'movements' => $this->get_recent_movements(),
            'fuel_loads' => $this->get_recent_fuel_loads(),
            'maintenances' => $this->get_upcoming_maintenances()
        );
    }

    /**
     * Get vehicles assigned to the current driver
     *
     * @return array Assigned vehicles
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
     * Get recent movements for the current driver
     *
     * @return array Recent movements
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
     * Get recent fuel loads for the current driver
     *
     * @return array Recent fuel loads
     */
    private function get_recent_fuel_loads()
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
     * Get upcoming maintenances
     *
     * @return array Upcoming maintenances
     */
    private function get_upcoming_maintenances()
    {
        $args = array(
            'estado' => 'programado',
            'fecha_desde' => date('Y-m-d'),
            'fecha_hasta' => date('Y-m-d', strtotime('+30 days')),
            'limit' => 5
        );
        return $this->database->get_maintenances($args);
    }

    /**
     * Render vehicles section
     *
     * @param array $vehicles Vehicles to render
     */
    private function render_vehicles_section($vehicles)
    {
        if (empty($vehicles)) {
            echo '<p>' . __('No hay vehículos asignados.', 'gestion-parque-vehicular') . '</p>';
            return;
        }

        echo '<div class="gpv-vehicles-grid">';
        foreach ($vehicles as $vehicle) {
        ?>
            <div class="gpv-vehicle-card" data-vehicle-id="<?php echo esc_attr($vehicle->id); ?>">
                <div class="gpv-vehicle-header">
                    <h3><?php echo esc_html($vehicle->siglas . ' - ' . $vehicle->nombre_vehiculo); ?></h3>
                    <span class="gpv-vehicle-status <?php echo esc_attr(strtolower($vehicle->estado)); ?>">
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
                </div>
            </div>
        <?php
        }
        echo '</div>';
    }

    /**
     * Render movements section
     *
     * @param array $movements Movements to render
     */
    private function render_movements_section($movements)
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
     * Render fuel loads section
     *
     * @param array $fuel_loads Fuel loads to render
     */
    private function render_fuel_loads_section($fuel_loads)
    {
        if (empty($fuel_loads)) {
            echo '<p>' . __('No hay cargas de combustible recientes.', 'gestion-parque-vehicular') . '</p>';
            return;
        }

        echo '<table class="gpv-data-table">';
        echo '<thead><tr>';
        echo '<th>' . __('Vehículo', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Fecha', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Litros', 'gestion-parque-vehicular') . '</th>';
        echo '<th>' . __('Costo', 'gestion-parque-vehicular') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($fuel_loads as $fuel) {
            echo '<tr>';
            echo '<td>' . esc_html($fuel->vehiculo_siglas . ' - ' . $fuel->vehiculo_nombre) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($fuel->fecha_carga))) . '</td>';
            echo '<td>' . esc_html(number_format($fuel->litros_cargados, 2) . ' L') . '</td>';
            echo '<td>$' . esc_html(number_format($fuel->litros_cargados * $fuel->precio, 2)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Render maintenances section
     *
     * @param array $maintenances Maintenances to render
     */
    private function render_maintenances_section($maintenances)
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
        echo '<th>' . __('Días Restantes', 'gestion-parque-vehicular') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($maintenances as $maintenance) {
            $dias_restantes = round((strtotime($maintenance->fecha_programada) - strtotime(date('Y-m-d'))) / (60 * 60 * 24));

            echo '<tr>';
            echo '<td>' . esc_html($maintenance->vehiculo_siglas . ' - ' . $maintenance->vehiculo_nombre) . '</td>';
            echo '<td>' . esc_html($maintenance->tipo) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($maintenance->fecha_programada))) . '</td>';
            echo '<td>' . esc_html($dias_restantes) . ' ' . __('días', 'gestion-parque-vehicular') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Render movement form modal
     */
    private function render_movement_form()
    {
        // Obtener vehículos asignados para el formulario
        $vehicles = $this->get_assigned_vehicles();
        ?>
        <div class="gpv-modal-content">
            <h2><?php _e('Registrar Movimiento', 'gestion-parque-vehicular'); ?></h2>
            <form id="gpv-movement-form" method="post">
                <?php wp_nonce_field('gpv_movement_registration', 'gpv_movement_nonce'); ?>

                <div class="gpv-form-group">
                    <label for="vehiculo_id"><?php _e('Vehículo:', 'gestion-parque-vehicular'); ?></label>
                    <select name="vehiculo_id" id="vehiculo_id" required>
                        <option value=""><?php _e('Seleccionar Vehículo', 'gestion-parque-vehicular'); ?></option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo esc_attr($vehicle->id); ?>
                            <?php echo esc_attr($vehicle->id); ?>">
                                <?php echo esc_html($vehicle->siglas . ' - ' . $vehicle->nombre_vehiculo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="gpv-form-group">
                    <label for="odometro_salida"><?php _e('Odómetro de Salida:', 'gestion-parque-vehicular'); ?></label>
                    <input type="number" name="odometro_salida" id="odometro_salida" step="0.1" required>
                </div>

                <div class="gpv-form-group">
                    <label for="hora_salida"><?php _e('Hora de Salida:', 'gestion-parque-vehicular'); ?></label>
                    <input type="datetime-local" name="hora_salida" id="hora_salida" required>
                </div>

                <div class="gpv-form-group">
                    <label for="proposito"><?php _e('Propósito del Viaje:', 'gestion-parque-vehicular'); ?></label>
                    <textarea name="proposito" id="proposito"></textarea>
                </div>

                <div class="gpv-form-actions">
                    <button type="submit" class="gpv-btn gpv-btn-primary">
                        <?php _e('Registrar Movimiento', 'gestion-parque-vehicular'); ?>
                    </button>
                    <button type="button" class="gpv-btn gpv-btn-secondary gpv-modal-close">
                        <?php _e('Cancelar', 'gestion-parque-vehicular'); ?>
                    </button>
                </div>
            </form>
        </div>
    <?php
    }

    /**
     * Render fuel load form modal
     */
    private function render_fuel_load_form()
    {
        // Obtener vehículos asignados para el formulario
        $vehicles = $this->get_assigned_vehicles();
    ?>
        <div class="gpv-modal-content">
            <h2><?php _e('Registrar Carga de Combustible', 'gestion-parque-vehicular'); ?></h2>
            <form id="gpv-fuel-load-form" method="post">
                <?php wp_nonce_field('gpv_fuel_load_registration', 'gpv_fuel_load_nonce'); ?>

                <div class="gpv-form-group">
                    <label for="vehiculo_id"><?php _e('Vehículo:', 'gestion-parque-vehicular'); ?></label>
                    <select name="vehiculo_id" id="vehiculo_id" required>
                        <option value=""><?php _e('Seleccionar Vehículo', 'gestion-parque-vehicular'); ?></option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo esc_attr($vehicle->id); ?>">
                                <?php echo esc_html($vehicle->siglas . ' - ' . $vehicle->nombre_vehiculo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="gpv-form-group">
                    <label for="odometro_carga"><?php _e('Odómetro Actual:', 'gestion-parque-vehicular'); ?></label>
                    <input type="number" name="odometro_carga" id="odometro_carga" step="0.1" required>
                </div>

                <div class="gpv-form-group">
                    <label for="litros_cargados"><?php _e('Litros Cargados:', 'gestion-parque-vehicular'); ?></label>
                    <input type="number" name="litros_cargados" id="litros_cargados" step="0.1" required>
                </div>

                <div class="gpv-form-group">
                    <label for="precio"><?php _e('Precio por Litro:', 'gestion-parque-vehicular'); ?></label>
                    <input type="number" name="precio" id="precio" step="0.01" required>
                </div>

                <div class="gpv-form-group">
                    <label for="km_desde_ultima_carga"><?php _e('Kilómetros desde Última Carga:', 'gestion-parque-vehicular'); ?></label>
                    <input type="number" name="km_desde_ultima_carga" id="km_desde_ultima_carga" step="0.1">
                </div>

                <div class="gpv-form-actions">
                    <button type="submit" class="gpv-btn gpv-btn-primary">
                        <?php _e('Registrar Carga', 'gestion-parque-vehicular'); ?>
                    </button>
                    <button type="button" class="gpv-btn gpv-btn-secondary gpv-modal-close">
                        <?php _e('Cancelar', 'gestion-parque-vehicular'); ?>
                    </button>
                </div>
            </form>
        </div>
<?php
    }
}

// Inicializar el dashboard del conductor
$gpv_driver_dashboard = new GPV_Driver_Dashboard();
