<?php
/*
Plugin Name: Prueba de Jules
Description: guardar datos
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

// Función para procesar la subida, actualización o eliminación de datos
function jules_procesar_datos() {
    global $wpdb;
    $tabla_nombre = $wpdb->prefix . 'jules_datos';

    // Manejar Eliminación
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id_a_eliminar = intval($_GET['id']);

        // Verificar nonce de eliminación
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jules_eliminar_datos_' . $id_a_eliminar)) {
            wp_die('Error de seguridad. No se puede procesar la eliminación.');
        }

        $resultado = $wpdb->delete($tabla_nombre, array('id' => $id_a_eliminar), array('%d'));

        if ($resultado) {
            echo '<div class="updated"><p>Registro eliminado correctamente.</p></div>';
        } else {
            echo '<div class="error"><p>Hubo un error al eliminar el registro.</p></div>';
        }
    }

    // Manejar Guardado/Actualización
    if (isset($_POST['submit_datos'])) {
        // Verificar nonce por seguridad
        if (!isset($_POST['jules_nonce']) || !wp_verify_nonce($_POST['jules_nonce'], 'jules_guardar_datos')) {
            wp_die('Error de seguridad. No se puede procesar la solicitud.');
        }

        $nombre = sanitize_text_field($_POST['nombre']);
        $apellido = sanitize_text_field($_POST['apellido']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if (!empty($nombre) && !empty($apellido)) {
            if ($item_id > 0) {
                // Actualizar registro existente
                $resultado = $wpdb->update(
                    $tabla_nombre,
                    array(
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                    ),
                    array('id' => $item_id),
                    array('%s', '%s'),
                    array('%d')
                );
            } else {
                // Insertar nuevo registro
                $resultado = $wpdb->insert(
                    $tabla_nombre,
                    array(
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                    ),
                    array('%s', '%s')
                );
            }

            if ($resultado !== false) {
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

    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $item_a_editar = null;

    if ($edit_id > 0) {
        $item_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_nombre WHERE id = %d", $edit_id));
    }

    // Manejo de la lógica de guardado
    jules_procesar_datos();

    $nombre_valor = $item_a_editar ? esc_attr($item_a_editar->nombre) : '';
    $apellido_valor = $item_a_editar ? esc_attr($item_a_editar->apellido) : '';
    $boton_texto = $item_a_editar ? 'Actualizar Datos' : 'Guardar Datos';
    $titulo_pagina = $item_a_editar ? 'Editar Registro' : 'Registro de Datos';

    ?>
    <div class="wrap">
        <h1>
            Prueba de Jules - <?php echo $titulo_pagina; ?>
            <?php if ($item_a_editar) : ?>
                <a href="admin.php?page=jules-prueba" class="page-title-action">Añadir Nuevo</a>
            <?php endif; ?>
        </h1>

        <form method="post" action="admin.php?page=jules-prueba">
            <?php wp_nonce_field('jules_guardar_datos', 'jules_nonce'); ?>

            <?php if ($item_a_editar) : ?>
                <input type="hidden" name="item_id" value="<?php echo intval($item_a_editar->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nombre">Nombre</label></th>
                    <td><input name="nombre" type="text" id="nombre" value="<?php echo $nombre_valor; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="apellido">Apellido</label></th>
                    <td><input name="apellido" type="text" id="apellido" value="<?php echo $apellido_valor; ?>" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_datos" id="submit" class="button button-primary" value="<?php echo $boton_texto; ?>">
                <?php if ($item_a_editar) : ?>
                    <a href="admin.php?page=jules-prueba" class="button">Cancelar Edición</a>
                <?php endif; ?>
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
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados) : ?>
                    <?php foreach ($resultados as $fila) : ?>
                        <tr>
                            <td><?php echo esc_html($fila->id); ?></td>
                            <td><?php echo esc_html($fila->nombre); ?></td>
                            <td><?php echo esc_html($fila->apellido); ?></td>
                            <td>
                                <a href="admin.php?page=jules-prueba&edit_id=<?php echo intval($fila->id); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url('admin.php?page=jules-prueba&action=delete&id=' . $fila->id, 'jules_eliminar_datos_' . $fila->id); ?>"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar este registro?');"
                                   style="color:red;">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4">No hay datos registrados aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
