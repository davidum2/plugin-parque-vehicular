<?php
// frontend/class-gpv-frontend.php

if (! defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

class GPV_Frontend
{

    private $database;

    public function __construct()
    {
        global $GPV_Database;
        $this->database = $GPV_Database;
    }
    public function set_database($database)
    {
        $this->database = $database;
    }

    public function get_database()
    {
        return $this->database;
    }
    /**
     * Muestra el listado de movimientos en el frontend
     *
     * @return string HTML del listado de movimientos
     */
    public function mostrar_listado_movimientos()
    {
        // Obtener los movimientos de la base de datos
        $movimientos = $this->database->get_movements();

        ob_start();
?>
        <div class="gpv-listado-container">
            <h2><?php esc_html_e('Listado de Movimientos de Vehículos', 'gestion-parque-vehicular') ?></h2>

            <?php if (empty($movimientos)) : ?>
                <p><?php esc_html_e('No hay movimientos registrados.', 'gestion-parque-vehicular') ?></p>
            <?php else : ?>
                <div class="gpv-table-responsive">
                    <table class="gpv-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Vehículo', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Conductor', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Salida', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Entrada', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Distancia', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Combustible', 'gestion-parque-vehicular') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $movimiento) : ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($movimiento->vehiculo_siglas); ?></strong><br>
                                        <small><?php echo esc_html($movimiento->vehiculo_nombre); ?></small>
                                    </td>
                                    <td><?php echo esc_html($movimiento->conductor); ?></td>
                                    <td>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movimiento->hora_salida))); ?><br>
                                        <small><?php echo esc_html($movimiento->odometro_salida); ?> km</small>
                                    </td>
                                    <td>
                                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movimiento->hora_entrada))); ?><br>
                                        <small><?php echo esc_html($movimiento->odometro_entrada); ?> km</small>
                                    </td>
                                    <td><?php echo esc_html($movimiento->distancia_recorrida); ?> km</td>
                                    <td>
                                        <?php echo esc_html($movimiento->combustible_consumido); ?> L<br>
                                        <small><?php esc_html_e('Nivel:', 'gestion-parque-vehicular'); ?> <?php echo esc_html($movimiento->nivel_combustible); ?> L</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
<?php
        return ob_get_clean();
    }
}
?>
