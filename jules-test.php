<?php
/*
Plugin Name: Prueba de Jules
Description: Un plugin simple de prueba para mostrar un aviso y guardar datos.
Version: 1.1
Author: Jules
*/

// Evitar el acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Función para crear la tabla al activar el plugin
function jules_crear_tabla() {
    global $wpdb;
    $tabla_nombre = $wpdb->prefix . 'jules_datos';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla_nombre (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        apellido varchar(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

register_activation_hook(__FILE__, 'jules_crear_tabla');

// Función para mostrar el aviso
function jules_mostrar_aviso_admin() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p>¡Hola! Este plugin fue creado por Jules.</p>
    </div>
    <?php
}
add_action('admin_notices', 'jules_mostrar_aviso_admin');

// Añadir el menú de administración
function jules_menu_admin() {
    add_menu_page(
        'Prueba de Jules',
        'Prueba de Jules',
        'manage_options',
        'jules-prueba',
        'jules_pagina_admin',
        'dashicons-admin-plugins'
    );
}
add_action('admin_menu', 'jules_menu_admin');

// Función para procesar la subida de datos
function jules_procesar_datos() {
    if (isset($_POST['submit_datos'])) {
        // Verificar nonce por seguridad
        if (!isset($_POST['jules_nonce']) || !wp_verify_nonce($_POST['jules_nonce'], 'jules_guardar_datos')) {
            wp_die('Error de seguridad. No se puede procesar la solicitud.');
        }

        global $wpdb;
        $tabla_nombre = $wpdb->prefix . 'jules_datos';

        $nombre = sanitize_text_field($_POST['nombre']);
        $apellido = sanitize_text_field($_POST['apellido']);

        if (!empty($nombre) && !empty($apellido)) {
            $resultado = $wpdb->insert(
                $tabla_nombre,
                array(
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                ),
                array('%s', '%s')
            );

            if ($resultado) {
                echo '<div class="updated"><p>Datos guardados correctamente.</p></div>';
            } else {
                echo '<div class="error"><p>Hubo un error al guardar los datos.</p></div>';
            }
        }
    }
}

// Página de administración: UI (Formulario y Visualización)
function jules_pagina_admin() {
    global $wpdb;
    $tabla_nombre = $wpdb->prefix . 'jules_datos';

    // Manejo de la lógica de guardado
    jules_procesar_datos();

    ?>
    <div class="wrap">
        <h1>Prueba de Jules - Registro de Datos</h1>

        <form method="post" action="">
            <?php wp_nonce_field('jules_guardar_datos', 'jules_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nombre">Nombre</label></th>
                    <td><input name="nombre" type="text" id="nombre" value="" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="apellido">Apellido</label></th>
                    <td><input name="apellido" type="text" id="apellido" value="" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_datos" id="submit" class="button button-primary" value="Guardar Datos">
            </p>
        </form>

        <hr>

        <h2>Datos Registrados</h2>
        <?php
        $resultados = $wpdb->get_results("SELECT * FROM $tabla_nombre ORDER BY id DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados) : ?>
                    <?php foreach ($resultados as $fila) : ?>
                        <tr>
                            <td><?php echo esc_html($fila->id); ?></td>
                            <td><?php echo esc_html($fila->nombre); ?></td>
                            <td><?php echo esc_html($fila->apellido); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3">No hay datos registrados aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
