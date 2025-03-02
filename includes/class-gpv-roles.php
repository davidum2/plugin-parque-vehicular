<?php

/**
 * Gestión de roles y permisos para el plugin Gestión de Parque Vehicular
 */
class GPV_Roles
{

    /**
     * Constructor
     */
    public function __construct()
    {
        // Hook para verificar permisos en cada petición
        add_action('init', array($this, 'check_capabilities'));
    }

    /**
     * Agregar roles personalizados
     */
    public function add_roles()
    {
        // Administrador de flota
        add_role(
            'gpv_administrator',
            __('Administrador de Flota', 'gestion-parque-vehicular'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            )
        );

        // Consultor (gerencia)
        add_role(
            'gpv_consultant',
            __('Consultor de Flota', 'gestion-parque-vehicular'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => false,
            )
        );

        // Operario (conductor)
        add_role(
            'gpv_operator',
            __('Operario de Flota', 'gestion-parque-vehicular'),
            array(
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            )
        );

        // Agregar capacidades específicas
        $this->add_gpv_capabilities();
    }

    /**
     * Agregar capacidades específicas para cada rol
     */
    private function add_gpv_capabilities()
    {
        $admin_role = get_role('gpv_administrator');
        $consultant_role = get_role('gpv_consultant');
        $operator_role = get_role('gpv_operator');
        $admin_wp = get_role('administrator');

        // Si los roles aún no existen, salir sin error
        if (!$admin_role || !$consultant_role || !$operator_role) {
            return;
        }

        // Capacidades para administradores de flota
        $admin_capabilities = array(
            'gpv_view_dashboard'       => true,
            'gpv_manage_vehicles'      => true,
            'gpv_manage_movements'     => true,
            'gpv_manage_fuel'          => true,
            'gpv_manage_maintenance'   => true,
            'gpv_manage_users'         => true,
            'gpv_generate_reports'     => true,
            'gpv_manage_settings'      => true,
            'gpv_edit_records'         => true,
            'gpv_delete_records'       => true,
        );

        // Capacidades para consultores
        $consultant_capabilities = array(
            'gpv_view_dashboard'       => true,
            'gpv_view_vehicles'        => true,
            'gpv_view_movements'       => true,
            'gpv_view_fuel'            => true,
            'gpv_view_maintenance'     => true,
            'gpv_generate_reports'     => true,
        );

        // Capacidades para operarios
        $operator_capabilities = array(
            'gpv_view_assigned_vehicles' => true,
            'gpv_register_movements'     => true,
            'gpv_register_fuel'          => true,
            'gpv_view_own_records'       => true,
        );

        // Asignar capacidades a roles
        foreach ($admin_capabilities as $cap => $grant) {
            if ($admin_role) {
                $admin_role->add_cap($cap, $grant);
            }
            if ($admin_wp) {
                $admin_wp->add_cap($cap, $grant); // También al admin de WP
            }
        }

        foreach ($consultant_capabilities as $cap => $grant) {
            if ($consultant_role) {
                $consultant_role->add_cap($cap, $grant);
            }
        }

        foreach ($operator_capabilities as $cap => $grant) {
            if ($operator_role) {
                $operator_role->add_cap($cap, $grant);
            }
        }
    }

    /**
     * Verificar permisos en cada petición
     */
    public function check_capabilities()
    {
        // Solo verificar en áreas del plugin
        if (!is_admin() && !$this->is_gpv_page()) {
            return;
        }

        // Obtener usuario actual y sus capacidades
        $user = wp_get_current_user();

        // Si es un usuario no logueado, redirigir a login
        if (!$user->exists()) {
            if ($this->is_gpv_page()) {
                wp_redirect(wp_login_url(home_url($_SERVER['REQUEST_URI'])));
                exit;
            }
            return;
        }

        // Verificar acceso a páginas específicas en admin
        if (is_admin() && isset($_GET['page'])) {
            $current_page = sanitize_text_field($_GET['page']);
            if (strpos($current_page, 'gpv_') === 0) {
                $this->verify_page_access($current_page, $user);
            }
        }
    }

    /**
     * Verificar si estamos en una página del plugin
     */
    private function is_gpv_page()
    {
        // Verificar solo si estamos en una página singular
        if (!is_singular()) {
            return false;
        }

        global $post;

        // Verificar si el post existe y tiene contenido
        if (!$post || !isset($post->post_content)) {
            return false;
        }

        // Comprobar si contiene alguno de nuestros shortcodes
        $has_gpv_shortcode = false;
        $gpv_shortcodes = [
            'gpv_dashboard',
            'gpv_driver_panel',
            'gpv_consultant_panel',
            'gpv_form_movimiento',
            'gpv_form_carga',
            'gpv_form_vehiculo',
            'gpv_listado_movimientos',
            'gpv_driver_dashboard'  // Nuevo shortcode añadido
        ];

        foreach ($gpv_shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                $has_gpv_shortcode = true;
                break;
            }
        }

        return $has_gpv_shortcode;
    }

    /**
     * Verificar acceso a páginas específicas
     */
    private function verify_page_access($page, $user)
    {
        $access_denied = false;

        switch ($page) {
            case 'gpv_dashboard':
            case 'gpv_menu':
                $access_denied = !current_user_can('gpv_view_dashboard') && !current_user_can('manage_options');
                break;
            case 'gpv_vehiculos':
                $access_denied = !current_user_can('gpv_view_vehicles') && !current_user_can('gpv_manage_vehicles') && !current_user_can('manage_options');
                break;
            case 'gpv_movimientos':
                $access_denied = !current_user_can('gpv_view_movements') && !current_user_can('gpv_manage_movements') && !current_user_can('manage_options');
                break;
            case 'gpv_cargas':
                $access_denied = !current_user_can('gpv_view_fuel') && !current_user_can('gpv_manage_fuel') && !current_user_can('manage_options');
                break;
            case 'gpv_mantenimientos':
                $access_denied = !current_user_can('gpv_view_maintenance') && !current_user_can('gpv_manage_maintenance') && !current_user_can('manage_options');
                break;
            case 'gpv_usuarios':
                $access_denied = !current_user_can('gpv_manage_users') && !current_user_can('manage_options');
                break;
            case 'gpv_reportes':
                $access_denied = !current_user_can('gpv_generate_reports') && !current_user_can('manage_options');
                break;
            case 'gpv_configuracion':
                $access_denied = !current_user_can('gpv_manage_settings') && !current_user_can('manage_options');
                break;
        }

        if ($access_denied) {
            wp_die(
                __('No tienes permiso para acceder a esta página.', 'gestion-parque-vehicular'),
                __('Acceso Denegado', 'gestion-parque-vehicular'),
                [
                    'response' => 403,
                    'back_link' => true,
                ]
            );
        }
    }
}
