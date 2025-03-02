<?php
// admin/views/formulario-vehiculo.php

if (!defined('ABSPATH')) {
    exit; // Salir si se accede directamente
}

// Inicializar variables
$mensaje = '';
$edit_mode = false;
$vehiculo_id = 0;

// Comprobar si estamos en modo edición
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_mode = true;
    $vehiculo_id = intval($_GET['id']);

    // Obtener datos del vehículo
    global $wpdb;
    $tabla = $wpdb->prefix . 'gpv_vehiculos';
    $vehiculo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $vehiculo_id));

    if ($vehiculo) {
        $datos_form = array(
            'siglas' => $vehiculo->siglas,
            'anio' => $vehiculo->anio,
            'nombre_vehiculo' => $vehiculo->nombre_vehiculo,
            'odometro_actual' => $vehiculo->odometro_actual,
            'nivel_combustible' => $vehiculo->nivel_combustible,
            'tipo_combustible' => $vehiculo->tipo_combustible,
            'medida_odometro' => $vehiculo->medida_odometro,
            'factor_consumo' => $vehiculo->factor_consumo,
            'capacidad_tanque' => $vehiculo->capacidad_tanque,
            'ubicacion_actual' => $vehiculo->ubicacion_actual,
            'categoria' => $vehiculo->categoria,
            'estado' => $vehiculo->estado
        );
    } else {
        echo '<div class="error"><p>' . esc_html__('Vehículo no encontrado.', 'gestion-parque-vehicular') . '</p></div>';
        return;
    }
} else {
    // Valores por defecto para modo nuevo
    $datos_form = array(
        'siglas' => '',
        'anio' => date('Y'),
        'nombre_vehiculo' => '',
        'odometro_actual' => 0,
        'nivel_combustible' => 100,
        'tipo_combustible' => 'Gasolina',
        'medida_odometro' => 'Kilómetros',
        'factor_consumo' => 0,
        'capacidad_tanque' => 0,
        'ubicacion_actual' => '',
        'categoria' => 'General',
        'estado' => 'disponible'
    );
}

// Procesar el formulario cuando se envía
if (isset($_POST['gpv_vehiculo_submit'])) {
    // Verificar nonce para seguridad
    if (!isset($_POST['gpv_vehiculo_nonce']) || !wp_verify_nonce($_POST['gpv_vehiculo_nonce'], 'gpv_vehiculo_action')) {
        $mensaje = '<div class="error"><p>' . esc_html__('Error de seguridad. Por favor, intenta de nuevo.', 'gestion-parque-vehicular') . '</p></div>';
    } else {
        // Sanear y validar datos del formulario
        $datos_form = array(
            'siglas' => sanitize_text_field($_POST['siglas']),
            'anio' => intval($_POST['anio']),
            'nombre_vehiculo' => sanitize_text_field($_POST['nombre_vehiculo']),
            'odometro_actual' => floatval($_POST['odometro_actual']),
            'nivel_combustible' => floatval($_POST['nivel_combustible']),
            'tipo_combustible' => sanitize_text_field($_POST['tipo_combustible']),
            'medida_odometro' => sanitize_text_field($_POST['medida_odometro']),
            'factor_consumo' => floatval($_POST['factor_consumo']),
            'capacidad_tanque' => floatval($_POST['capacidad_tanque']),
            'ubicacion_actual' => sanitize_text_field($_POST['ubicacion_actual']),
            'categoria' => sanitize_text_field($_POST['categoria']),
            'estado' => sanitize_text_field($_POST['estado'])
        );

        // Validar campos requeridos
        if (empty($datos_form['siglas']) || empty($datos_form['nombre_vehiculo'])) {
            $mensaje = '<div class="error"><p>' . esc_html__('Por favor, completa todos los campos obligatorios.', 'gestion-parque-vehicular') . '</p></div>';
        } else {
            global $GPV_Database;

            if ($edit_mode) {
                // Actualizar vehículo existente
                $result = $GPV_Database->update_vehicle($vehiculo_id, $datos_form);

                if ($result !== false) {
                    $mensaje = '<div class="updated"><p>' . esc_html__('Vehículo actualizado correctamente.', 'gestion-parque-vehicular') . '</p></div>';
                } else {
                    $mensaje = '<div class="error"><p>' . esc_html__('Error al actualizar el vehículo.', 'gestion-parque-vehicular') . '</p></div>';
                }
            } else {
                // Insertar nuevo vehículo
                $result = $GPV_Database->insert_vehicle($datos_form);

                if ($result) {
                    $mensaje = '<div class="updated"><p>' . esc_html__('Vehículo registrado correctamente.', 'gestion-parque-vehicular') . '</p></div>';
                    // Resetear formulario para nuevo registro
                    if (!isset($_GET['redirect']) || $_GET['redirect'] !== 'false') {
                        // Redirigir al listado de vehículos
                        wp_redirect(admin_url('admin.php?page=gpv_vehiculos&message=created'));
                        exit;
                    } else {
                        // Resetear el formulario
                        $datos_form = array(
                            'siglas' => '',
                            'anio' => date('Y'),
                            'nombre_vehiculo' => '',
                            'odometro_actual' => 0,
                            'nivel_combustible' => 100,
                            'tipo_combustible' => 'Gasolina',
                            'medida_odometro' => 'Kilómetros',
                            'factor_consumo' => 0,
                            'capacidad_tanque' => 0,
                            'ubicacion_actual' => '',
                            'categoria' => 'General',
                            'estado' => 'disponible'
                        );
                    }
                } else {
                    $mensaje = '<div class="error"><p>' . esc_html__('Error al registrar el vehículo.', 'gestion-parque-vehicular') . '</p></div>';
                }
            }
        }
    }
}

// Título según modo
$titulo = $edit_mode ? __('Editar Vehículo', 'gestion-parque-vehicular') : __('Agregar Nuevo Vehículo', 'gestion-parque-vehicular');
?>

<div class="wrap">
    <h1><?php echo esc_html($titulo); ?></h1>

    <?php echo $mensaje; ?>

    <form method="post" action="" class="gpv-admin-form" id="gpv-vehiculo-form">
        <?php wp_nonce_field('gpv_vehiculo_action', 'gpv_vehiculo_nonce'); ?>


        <div class="form-group ">
            <label for="siglas"><?php echo esc_html__('Siglas o Placa:', 'gestion-parque-vehicular'); ?> <span class="required">*</span></label>
            <input type="text" name="siglas" id="siglas" value="<?php echo esc_attr($datos_form['siglas']); ?>" required>
        </div>

        <div class="form-group ">
            <label for="anio"><?php echo esc_html__('Año:', 'gestion-parque-vehicular'); ?></label>
            <input type="number" name="anio" id="anio" value="<?php echo esc_attr($datos_form['anio']); ?>" min="1900" max="<?php echo date('Y') + 1; ?>">
        </div>


        <div class="form-group">
            <label for="nombre_vehiculo"><?php echo esc_html__('Nombre del Vehículo:', 'gestion-parque-vehicular'); ?> <span class="required">*</span></label>
            <input type="text" name="nombre_vehiculo" id="nombre_vehiculo" value="<?php echo esc_attr($datos_form['nombre_vehiculo']); ?>" required>
        </div>


        <div class="form-group ">
            <label for="odometro_actual"><?php echo esc_html__('Odómetro Actual:', 'gestion-parque-vehicular'); ?></label>
            <input type="number" name="odometro_actual" id="odometro_actual" value="<?php echo esc_attr($datos_form['odometro_actual']); ?>" step="0.1" min="0">
        </div>

        <div class="form-group ">
            <label for="medida_odometro"><?php echo esc_html__('Medida de Odómetro:', 'gestion-parque-vehicular'); ?></label>
            <select name="medida_odometro" id="medida_odometro">
                <option value="Kilómetros" <?php selected($datos_form['medida_odometro'], 'Kilómetros'); ?>>Kilómetros</option>
                <option value="Millas" <?php selected($datos_form['medida_odometro'], 'Millas'); ?>>Millas</option>
            </select>
        </div>



        <div class="form-group ">
            <label for="nivel_combustible"><?php echo esc_html__('Nivel de Combustible (Lts):', 'gestion-parque-vehicular'); ?></label>
            <input type="number" name="nivel_combustible" id="nivel_combustible" value="<?php echo esc_attr($datos_form['nivel_combustible']); ?>" min="0" max="400" step="1">
        </div>

        <div class="form-group ">
            <label for="tipo_combustible"><?php echo esc_html__('Tipo de Combustible:', 'gestion-parque-vehicular'); ?></label>
            <select name="tipo_combustible" id="tipo_combustible">
                <option value="Gasolina" <?php selected($datos_form['tipo_combustible'], 'Gasolina'); ?>>Gasolina</option>
                <option value="Diesel" <?php selected($datos_form['tipo_combustible'], 'Diesel'); ?>>Diesel</option>
                <option value="Gas LP" <?php selected($datos_form['tipo_combustible'], 'Gas LP'); ?>>Gas LP</option>
                <option value="Eléctrico" <?php selected($datos_form['tipo_combustible'], 'Eléctrico'); ?>>Eléctrico</option>
                <option value="Híbrido" <?php selected($datos_form['tipo_combustible'], 'Híbrido'); ?>>Híbrido</option>
            </select>
        </div>



        <div class="form-group ">
            <label for="factor_consumo"><?php echo esc_html__('Factor de Consumo (km/L):', 'gestion-parque-vehicular'); ?></label>
            <input type="number" name="factor_consumo" id="factor_consumo" value="<?php echo esc_attr($datos_form['factor_consumo']); ?>" step="0.1" min="0">
        </div>

        <div class="form-group ">
            <label for="capacidad_tanque"><?php echo esc_html__('Capacidad del Tanque (L):', 'gestion-parque-vehicular'); ?></label>
            <input type="number" name="capacidad_tanque" id="capacidad_tanque" value="<?php echo esc_attr($datos_form['capacidad_tanque']); ?>" step="0.1" min="0">
        </div>


        <div class="form-group ">
            <label for="ubicacion_actual"><?php echo esc_html__('Ubicación Actual:', 'gestion-parque-vehicular'); ?></label>
            <input type="text" name="ubicacion_actual" id="ubicacion_actual" value="<?php echo esc_attr($datos_form['ubicacion_actual']); ?>">
        </div>

        <div class="form-group ">
            <label for="categoria"><?php echo esc_html__('Categoría:', 'gestion-parque-vehicular'); ?></label>
            <select name="categoria" id="categoria">
                <option value="General" <?php selected($datos_form['categoria'], 'General'); ?>>General</option>
                <option value="Utilitario" <?php selected($datos_form['categoria'], 'Utilitario'); ?>>Utilitario</option>
                <option value="Ejecutivo" <?php selected($datos_form['categoria'], 'Ejecutivo'); ?>>Ejecutivo</option>
                <option value="Carga" <?php selected($datos_form['categoria'], 'Carga'); ?>>Carga</option>
                <option value="Maquinaria" <?php selected($datos_form['categoria'], 'Maquinaria'); ?>>Maquinaria</option>
            </select>
        </div>


        <div class="form-group">
            <label for="estado"><?php echo esc_html__('Estado:', 'gestion-parque-vehicular'); ?></label>
            <select name="estado" id="estado">
                <option value="disponible" <?php selected($datos_form['estado'], 'disponible'); ?>>Disponible</option>
                <option value="en_uso" <?php selected($datos_form['estado'], 'en_uso'); ?>>En Uso</option>
                <option value="mantenimiento" <?php selected($datos_form['estado'], 'mantenimiento'); ?>>En Mantenimiento</option>
            </select>
        </div>

        <div class="form-group">
            <?php if ($edit_mode): ?>
                <input type="submit" name="gpv_vehiculo_submit" class="button button-primary" value="<?php echo esc_attr__('Actualizar Vehículo', 'gestion-parque-vehicular'); ?>">
                <a href="<?php echo esc_url(admin_url('admin.php?page=gpv_vehiculos')); ?>" class="button button-secondary"><?php echo esc_html__('Cancelar', 'gestion-parque-vehicular'); ?></a>
            <?php else: ?>
                <input type="submit" name="gpv_vehiculo_submit" class="button button-primary" value="<?php echo esc_attr__('Registrar Vehículo', 'gestion-parque-vehicular'); ?>">
            <?php endif; ?>
        </div>
    </form>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Validación del formulario
        $('#gpv-vehiculo-form').submit(function(e) {
            var isValid = true;

            // Validar campos requeridos
            $(this).find('[required]').each(function() {
                if ($(this).val().trim() === '') {
                    isValid = false;
                    $(this).addClass('error-field');

                    if ($(this).next('.error-message').length === 0) {
                        var fieldName = $(this).prev('label').text() || 'Este campo';
                        $(this).after('<span class="error-message">Este campo es requerido</span>');
                    }
                } else {
                    $(this).removeClass('error-field');
                    $(this).next('.error-message').remove();
                }
            });

            if (!isValid) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('.error-field:first').offset().top - 100
                }, 500);
            }
        });

        // Eliminar mensaje de error al cambiar el valor del campo
        $(document).on('change keyup', '.error-field', function() {
            $(this).removeClass('error-field');
            $(this).next('.error-message').remove();
        });
    });
</script>
