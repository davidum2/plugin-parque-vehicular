<?php
if (! defined('ABSPATH')) {
    exit; // Salir si se accede directamente.
}

require_once plugin_dir_path(__FILE__) . '../frontend/class-gpv-forms.php';
require_once plugin_dir_path(__FILE__) . '../frontend/class-gpv-frontend.php';

class GPV_Shortcodes
{
    private $database;
    private $forms;
    private $frontend;

    public function __construct()
    {
        // Obtener instancia de base de datos
        global $GPV_Database;
        $this->database = $GPV_Database;

        $this->forms = new GPV_Forms();
        $this->frontend = new GPV_Frontend();

        // Registrar todos los shortcodes
        $this->register_shortcodes();
    }



    /**
     * Registrar todos los shortcodes del plugin
     */
    private function register_shortcodes()
    {
        // Formularios
        add_shortcode('gpv_form_salida', array($this, 'formulario_salida_shortcode'));
        add_shortcode('gpv_form_entrada', array($this, 'formulario_entrada_shortcode'));
        add_shortcode('gpv_form_movimiento', array($this, 'formulario_movimiento_shortcode'));
        add_shortcode('gpv_movimientos_activos', array($this, 'movimientos_activos_shortcode'));
        add_shortcode('gpv_listado_vehiculos', array($this, 'listado_vehiculos_shortcode'));
        add_shortcode('gpv_form_carga', array($this, 'formulario_carga_shortcode'));
        add_shortcode('gpv_form_vehiculo', array($this, 'formulario_vehiculo_shortcode'));

        // Listados
        add_shortcode('gpv_listado_movimientos', array($this, 'listado_movimientos_shortcode'));

        // Nuevos shortcodes para dashboards
        add_shortcode('gpv_dashboard', array($this, 'dashboard_shortcode'));
        add_shortcode('gpv_driver_panel', array($this, 'driver_panel_shortcode'));
        add_shortcode('gpv_consultant_panel', array($this, 'consultant_panel_shortcode'));
        add_shortcode('gpv_driver_dashboard', array($this, 'driver_dashboard_shortcode'));
    }
    /**
     * Shortcode para mostrar listado de vehículos
     *
     * @return string HTML con la tabla de vehículos
     */

    public function listado_vehiculos_shortcode($atts = [])
    {
        // Obtener la instancia de la base de datos explícitamente
        global $GPV_Database;

        // Si la variable global no está establecida, intentar obtenerla del singleton
        if (!$GPV_Database && function_exists('GPV')) {
            $gpv = GPV();
            if (isset($gpv->database)) {
                $GPV_Database = $gpv->database;
            }
        }

        // Si todavía no está disponible, mostrar mensaje de error
        if (!$GPV_Database) {
            return '<div class="gpv-error">' . __('Error: No se pudo acceder a la base de datos.', 'gestion-parque-vehicular') . '</div>';
        }

        // Verificar permisos
        if (!current_user_can('gpv_view_vehicles') && !current_user_can('gpv_view_assigned_vehicles')) {
            return '<div class="gpv-error">' . __('No tienes permiso para ver este listado.', 'gestion-parque-vehicular') . '</div>';
        }

        // Filtros de usuario
        $args = [];

        // Si es operador, mostrar solo sus vehículos asignados
        if (current_user_can('gpv_view_assigned_vehicles') && !current_user_can('gpv_view_vehicles')) {
            $args['conductor_asignado'] = get_current_user_id();
        }

        // Obtener vehículos
        $vehiculos = $GPV_Database->get_vehicles($args);

        ob_start();
?>
        <div class="gpv-listado-container">
            <h2><?php esc_html_e('Estado de Vehículos', 'gestion-parque-vehicular') ?></h2>
            <?php if (empty($vehiculos)) : ?>
                <p><?php esc_html_e('No hay vehículos disponibles.', 'gestion-parque-vehicular') ?></p>
            <?php else : ?>
                <div class="gpv-table-responsive">
                    <table class="gpv-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Siglas', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Nombre', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Odómetro', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Combustible', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Estado', 'gestion-parque-vehicular') ?></th>
                                <th><?php esc_html_e('Última act.', 'gestion-parque-vehicular') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehiculos as $vehiculo) :
                                // Calcular porcentaje de combustible para mostrar
                                $porcentaje_combustible = ($vehiculo->capacidad_tanque > 0)
                                    ? ($vehiculo->nivel_combustible / $vehiculo->capacidad_tanque) * 100
                                    : 0;

                                // Determinar clase de estilo según nivel
                                $clase_combustible = '';
                                if ($porcentaje_combustible < 20) {
                                    $clase_combustible = 'gpv-nivel-bajo';
                                } elseif ($porcentaje_combustible < 40) {
                                    $clase_combustible = 'gpv-nivel-medio';
                                }
                            ?>
                                <tr>
                                    <td><?php echo esc_html($vehiculo->siglas) ?></td>
                                    <td><?php echo esc_html($vehiculo->nombre_vehiculo) ?></td>
                                    <td><?php echo esc_html($vehiculo->odometro_actual) ?> <?php echo esc_html($vehiculo->medida_odometro) ?></td>
                                    <td class="<?php echo esc_attr($clase_combustible) ?>">
                                        <?php echo esc_html(number_format($vehiculo->nivel_combustible, 2)) ?> L
                                        <br>
                                        <small>(<?php echo esc_html(number_format($porcentaje_combustible, 1)) ?>%)</small>
                                    </td>
                                    <td class="gpv-estado-<?php echo esc_attr($vehiculo->estado) ?>">
                                        <?php echo esc_html(ucfirst($vehiculo->estado)) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($vehiculo->ultima_actualizacion)) : ?>
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($vehiculo->ultima_actualizacion))) ?>
                                        <?php else : ?>
                                            -
                                        <?php endif; ?>
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

    /**
     * Shortcode para formulario de salida
     */
    public function formulario_salida_shortcode($atts = [])
    {
        // Pasar atributos para personalización
        $args = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'assigned_only' => true,
            'tipo' => 'salida'
        ), $atts);

        return $this->forms->formulario_movimiento($args);
    }

    /**
     * Shortcode para formulario de entrada
     */
    public function formulario_entrada_shortcode($atts = [])
    {
        // Pasar atributos para personalización
        $args = shortcode_atts(array(
            'id' => isset($_GET['id']) ? intval($_GET['id']) : 0,
            'tipo' => 'entrada',
            'redirect' => ''
        ), $atts);

        // Si se proporcionó un ID en los parámetros GET, usarlo
        if (isset($_GET['id']) && empty($args['id'])) {
            $args['id'] = intval($_GET['id']);
        }

        return $this->forms->formulario_movimiento($args);
    }

    /**
     * Shortcode para formulario de movimiento (genérico)
     */
    public function formulario_movimiento_shortcode($atts = [])
    {
        // Pasar atributos para personalización
        $args = shortcode_atts(array(
            'tipo' => 'auto', // 'salida', 'entrada', 'completo' o 'auto' (detecta automáticamente)
            'movimiento_id' => isset($_GET['id']) ? intval($_GET['id']) : 0,
            'user_id' => get_current_user_id(),
            'assigned_only' => true,
            'redirect' => ''
        ), $atts);

        // Si estamos en modo "auto", detectar el tipo según parámetros URL
        if ($args['tipo'] === 'auto') {
            if (isset($_GET['action']) && $_GET['action'] === 'entrada' && isset($_GET['id'])) {
                $args['tipo'] = 'entrada';
                $args['movimiento_id'] = intval($_GET['id']);
            } elseif (isset($_GET['action']) && $_GET['action'] === 'completo') {
                $args['tipo'] = 'completo';
            } else {
                $args['tipo'] = 'salida';
            }
        }

        return $this->forms->formulario_movimiento($args);
    }

    /**
     * Shortcode para mostrar movimientos activos
     */
    public function movimientos_activos_shortcode($atts = [])
    {
        // Verificar que el usuario esté logueado
        if (!is_user_logged_in()) {
            return '<div class="gpv-error">' . __('Debe iniciar sesión para ver sus movimientos activos.', 'gestion-parque-vehicular') . '</div>';
        }

        // Parámetros por defecto
        $args = shortcode_atts([
            'redirect' => site_url('/registrar-entrada/'), // URL de la página de registro de entrada
        ], $atts);

        global $wpdb;
        $user_id = get_current_user_id();

        // Obtener movimientos activos
        $tabla_movimientos = $wpdb->prefix . 'gpv_movimientos';
        $movimientos_activos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla_movimientos
                WHERE conductor_id = %d
                AND estado = 'en_progreso'
                ORDER BY hora_salida DESC",
                $user_id
            )
        );

        // Si no hay movimientos activos
        if (empty($movimientos_activos)) {
            return '<div class="gpv-notice">' . __('No tienes movimientos activos en este momento.', 'gestion-parque-vehicular') . '</div>';
        }

        // Preparar salida HTML
        ob_start();
    ?>
        <div class="gpv-movimientos-container">
            <h2><?php esc_html_e('Mis Movimientos Activos', 'gestion-parque-vehicular'); ?></h2>

            <div class="gpv-movimientos-grid">
                <?php foreach ($movimientos_activos as $movimiento): ?>
                    <div class="gpv-movimiento-card">
                        <div class="gpv-movimiento-header">
                            <h3><?php echo esc_html($movimiento->vehiculo_siglas . ' - ' . $movimiento->vehiculo_nombre); ?></h3>
                            <span class="gpv-estado-badge"><?php esc_html_e('En Curso', 'gestion-parque-vehicular'); ?></span>
                        </div>

                        <div class="gpv-movimiento-details">
                            <p>
                                <strong><?php esc_html_e('Salida:', 'gestion-parque-vehicular'); ?></strong>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($movimiento->hora_salida))); ?>
                            </p>
                            <p>
                                <strong><?php esc_html_e('Odómetro:', 'gestion-parque-vehicular'); ?></strong>
                                <?php echo esc_html($movimiento->odometro_salida); ?>
                            </p>
                            <?php if (!empty($movimiento->proposito)): ?>
                                <p>
                                    <strong><?php esc_html_e('Propósito:', 'gestion-parque-vehicular'); ?></strong>
                                    <?php echo esc_html($movimiento->proposito); ?>
                                </p>
                            <?php endif; ?>

                            <p>
                                <strong><?php esc_html_e('Tiempo transcurrido:', 'gestion-parque-vehicular'); ?></strong>
                                <?php
                                $salida = new DateTime($movimiento->hora_salida);
                                $ahora = new DateTime();
                                $intervalo = $ahora->diff($salida);

                                if ($intervalo->days > 0) {
                                    echo esc_html(sprintf(
                                        _n('%d día, %d horas, %d minutos', '%d días, %d horas, %d minutos', $intervalo->days, 'gestion-parque-vehicular'),
                                        $intervalo->days,
                                        $intervalo->h,
                                        $intervalo->i
                                    ));
                                } else {
                                    echo esc_html(sprintf(
                                        __('%d horas, %d minutos', 'gestion-parque-vehicular'),
                                        $intervalo->h,
                                        $intervalo->i
                                    ));
                                }
                                ?>
                            </p>
                        </div>

                        <div class="gpv-movimiento-actions">
                            <a href="<?php echo esc_url(add_query_arg(['action' => 'entrada', 'id' => $movimiento->id], $args['redirect'])); ?>" class="gpv-button">
                                <?php esc_html_e('Registrar Entrada', 'gestion-parque-vehicular'); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .gpv-movimientos-container {
                max-width: 1200px;
                margin: 20px auto;
            }

            .gpv-movimientos-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 20px;
                margin-top: 20px;
            }

            .gpv-movimiento-card {
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .gpv-movimiento-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            }

            .gpv-movimiento-header {
                background-color: #4285F4;
                color: white;
                padding: 15px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .gpv-movimiento-header h3 {
                margin: 0;
                font-size: 18px;
                font-weight: 500;
            }

            .gpv-estado-badge {
                background-color: rgba(255, 255, 255, 0.2);
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: bold;
            }

            .gpv-movimiento-details {
                padding: 15px;
            }

            .gpv-movimiento-details p {
                margin: 8px 0;
                line-height: 1.4;
            }

            .gpv-movimiento-actions {
                padding: 15px;
                border-top: 1px solid #eee;
                text-align: right;
            }

            .gpv-button {
                display: inline-block;
                background-color: #4285F4;
                color: white;
                padding: 8px 16px;
                border-radius: 4px;
                text-decoration: none;
                font-weight: 500;
                transition: background-color 0.2s ease;
            }

            .gpv-button:hover {
                background-color: #3367D6;
                color: white;
            }

            .gpv-error,
            .gpv-notice {
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
            }

            .gpv-error {
                background-color: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }

            .gpv-notice {
                background-color: #fff3cd;
                color: #856404;
                border: 1px solid #ffeeba;
            }

            @media (max-width: 768px) {
                .gpv-movimientos-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
<?php
        return ob_get_clean();
    }

    /**
     * Shortcode para formulario de carga de combustible
     */
    public function formulario_carga_shortcode()
    {
        return $this->forms->formulario_carga();
    }

    /**
     * Shortcode para formulario de vehículo
     */
    public function formulario_vehiculo_shortcode()
    {
        return $this->forms->formulario_vehiculo();
    }

    /**
     * Shortcode para listado de movimientos
     */
    public function listado_movimientos_shortcode()
    {
        return $this->frontend->mostrar_listado_movimientos();
    }

    /**
     * Shortcode para dashboard principal
     */
    public function dashboard_shortcode()
    {
        // Verificar permisos
        if (!current_user_can('gpv_view_dashboard')) {
            return '<div class="gpv-error">' . __('No tienes permiso para ver este dashboard.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para aplicación React
        return '<div id="gpv-app-container" data-panel-type="dashboard"></div>';
    }

    /**
     * Shortcode para panel de conductor
     */
    public function driver_panel_shortcode()
    {
        // Verificar permisos
        if (!current_user_can('gpv_register_movements') && !current_user_can('gpv_register_fuel')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para aplicación React
        return '<div id="gpv-app-container" data-panel-type="driver"></div>';
    }

    /**
     * Shortcode para panel de consultor
     */
    public function consultant_panel_shortcode()
    {
        // Verificar permisos
        if (!current_user_can('gpv_view_dashboard') && !current_user_can('gpv_generate_reports')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para aplicación React
        return '<div id="gpv-app-container" data-panel-type="consultant"></div>';
    }

    /**
     * Shortcode para dashboard de conductor
     */
    public function driver_dashboard_shortcode()
    {
        // Verificar permisos del conductor
        if (!current_user_can('gpv_register_movements') && !current_user_can('gpv_register_fuel')) {
            return '<div class="gpv-error">' . __('No tienes permiso para acceder a este panel.', 'gestion-parque-vehicular') . '</div>';
        }

        // Contenedor para React
        return '<div id="gpv-driver-dashboard"></div>';
    }
}

// Inicializar los shortcodes
$GPV_Shortcodes = new GPV_Shortcodes();

function gpv_enqueue_react_scripts()
{
    // Encolar React y ReactDOM
    wp_enqueue_script('react', 'https://unpkg.com/react@17/umd/react.production.min.js', [], '17.0.2', true);
    wp_enqueue_script('react-dom', 'https://unpkg.com/react-dom@17/umd/react-dom.production.min.js', ['react'], '17.0.2', true);

    // Encolar tu script de frontend-app.js
    wp_enqueue_script(
        'gpv-frontend-app',
        GPV_PLUGIN_URL . 'assets/js/frontend-app.js',
        ['react', 'react-dom'],
        GPV_VERSION,
        true
    );

    // Pasar datos al script
    wp_localize_script('gpv-frontend-app', 'gpvPwaData', [
        'root' => esc_url_raw(rest_url()),
        'nonce' => wp_create_nonce('wp_rest'),
        'pluginUrl' => GPV_PLUGIN_URL,
        'logoUrl' => '', // Opcional: URL del logo
    ]);
}
add_action('wp_enqueue_scripts', 'gpv_enqueue_react_scripts');

/**
 * Añadir menú en el dashboard para acceder rápidamente a estas páginas
 */
function gpv_add_user_menu()
{
    if (is_user_logged_in() && current_user_can('gpv_register_movements')) {
        // Solo mostrar para usuarios con permisos para registrar movimientos
        add_menu_page(
            __('Mis Vehículos', 'gestion-parque-vehicular'),
            __('Mis Vehículos', 'gestion-parque-vehicular'),
            'gpv_register_movements',
            'gpv-user-dashboard',
            null,
            'dashicons-car',
            30
        );

        // Submenu para movimientos activos
        add_submenu_page(
            'gpv-user-dashboard',
            __('Mis Movimientos Activos', 'gestion-parque-vehicular'),
            __('Movimientos Activos', 'gestion-parque-vehicular'),
            'gpv_register_movements',
            'admin.php?page=gpv-user-dashboard&redirect=' . urlencode(get_permalink(get_page_by_path('mis-movimientos-activos')))
        );

        // Submenu para registrar salida
        add_submenu_page(
            'gpv-user-dashboard',
            __('Registrar Salida', 'gestion-parque-vehicular'),
            __('Registrar Salida', 'gestion-parque-vehicular'),
            'gpv_register_movements',
            'admin.php?page=gpv-user-dashboard&redirect=' . urlencode(get_permalink(get_page_by_path('registrar-salida')))
        );
    }
}
add_action('admin_menu', 'gpv_add_user_menu');

/**
 * Redireccionar desde el menú del dashboard a las páginas front-end
 */
function gpv_redirect_from_admin_menu()
{
    if (isset($_GET['page']) && $_GET['page'] === 'gpv-user-dashboard' && isset($_GET['redirect'])) {
        wp_redirect(esc_url_raw($_GET['redirect']));
        exit;
    }
}
add_action('admin_init', 'gpv_redirect_from_admin_menu');
