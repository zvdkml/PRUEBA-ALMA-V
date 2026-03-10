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

// Función para crear las tablas al activar el plugin
function jules_crear_tabla() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Tabla de datos principales
    $tabla_datos = $wpdb->prefix . 'jules_datos';
    $sql_datos = "CREATE TABLE $tabla_datos (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre varchar(100) NOT NULL,
        apellido varchar(100) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_datos);

    // Tabla de notas
    $tabla_notas = $wpdb->prefix . 'jules_notas';
    $sql_notas = "CREATE TABLE $tabla_notas (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        jules_dato_id mediumint(9) NOT NULL,
        curso varchar(100) NOT NULL,
        nota text NOT NULL,
        docente_nombre varchar(100) NOT NULL,
        estado varchar(50) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_notas);
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

// Página de administración para Notas: UI (Formulario y Visualización)
function jules_pagina_notas_admin() {
    global $wpdb;
    $tabla_notas = $wpdb->prefix . 'jules_notas';
    $tabla_datos = $wpdb->prefix . 'jules_datos';

    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $item_a_editar = null;

    if ($edit_id > 0) {
        $item_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_notas WHERE id = %d", $edit_id));
    }

    // Manejo de la lógica de guardado/eliminación de notas
    jules_procesar_notas();

    $dato_id_valor = $item_a_editar ? $item_a_editar->jules_dato_id : 0;
    $curso_valor = $item_a_editar ? esc_attr($item_a_editar->curso) : '';
    $nota_valor = $item_a_editar ? esc_textarea($item_a_editar->nota) : '';
    $docente_valor = $item_a_editar ? esc_attr($item_a_editar->docente_nombre) : '';
    $estado_valor = $item_a_editar ? esc_attr($item_a_editar->estado) : '';
    $boton_texto = $item_a_editar ? 'Actualizar Nota' : 'Guardar Nota';
    $titulo_pagina = $item_a_editar ? 'Editar Nota' : 'Gestionar Notas';

    // Obtener lista de personas para el dropdown
    $personas = $wpdb->get_results("SELECT id, nombre, apellido FROM $tabla_datos ORDER BY nombre ASC");

    ?>
    <div class="wrap">
        <h1>
            Prueba de Jules - <?php echo $titulo_pagina; ?>
            <?php if ($item_a_editar) : ?>
                <a href="admin.php?page=jules-notas" class="page-title-action">Añadir Nueva</a>
            <?php endif; ?>
        </h1>

        <form method="post" action="admin.php?page=jules-notas">
            <?php wp_nonce_field('jules_guardar_notas', 'jules_notas_nonce'); ?>

            <?php if ($item_a_editar) : ?>
                <input type="hidden" name="item_id" value="<?php echo intval($item_a_editar->id); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="jules_dato_id">Persona</label></th>
                    <td>
                        <select name="jules_dato_id" id="jules_dato_id" required>
                            <option value="">Seleccione una persona...</option>
                            <?php foreach ($personas as $persona) : ?>
                                <option value="<?php echo intval($persona->id); ?>" <?php selected($dato_id_valor, $persona->id); ?>>
                                    <?php echo esc_html($persona->nombre . ' ' . $persona->apellido); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="curso">Curso</label></th>
                    <td><input name="curso" type="text" id="curso" value="<?php echo $curso_valor; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="nota">Nota</label></th>
                    <td><textarea name="nota" id="nota" rows="5" class="large-text" required><?php echo $nota_valor; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="docente_nombre">Docente</label></th>
                    <td><input name="docente_nombre" type="text" id="docente_nombre" value="<?php echo $docente_valor; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="estado">Estado</label></th>
                    <td><input name="estado" type="text" id="estado" value="<?php echo $estado_valor; ?>" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_nota" id="submit" class="button button-primary" value="<?php echo $boton_texto; ?>">
                <?php if ($item_a_editar) : ?>
                    <a href="admin.php?page=jules-notas" class="button">Cancelar Edición</a>
                <?php endif; ?>
            </p>
        </form>

        <hr>

        <h2>Notas Registradas</h2>
        <?php
        $query = "
            SELECT n.*, d.nombre, d.apellido
            FROM $tabla_notas n
            LEFT JOIN $tabla_datos d ON n.jules_dato_id = d.id
            ORDER BY n.id DESC";
        $resultados = $wpdb->get_results($query);
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Persona</th>
                    <th>Curso</th>
                    <th>Nota</th>
                    <th>Docente</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados) : ?>
                    <?php foreach ($resultados as $fila) : ?>
                        <tr>
                            <td><?php echo esc_html($fila->id); ?></td>
                            <td><?php echo esc_html($fila->nombre . ' ' . $fila->apellido); ?></td>
                            <td><?php echo esc_html($fila->curso); ?></td>
                            <td><?php echo nl2br(esc_html($fila->nota)); ?></td>
                            <td><?php echo esc_html($fila->docente_nombre); ?></td>
                            <td><?php echo esc_html($fila->estado); ?></td>
                            <td>
                                <a href="admin.php?page=jules-notas&edit_id=<?php echo intval($fila->id); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url('admin.php?page=jules-notas&action=delete_nota&id=' . $fila->id, 'jules_eliminar_nota_' . $fila->id); ?>"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar esta nota?');"
                                   style="color:red;">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">No hay notas registradas aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Función para el shortcode [jules_datos]
function jules_shortcode_datos($atts) {
    global $wpdb;
    $tabla_nombre = $wpdb->prefix . 'jules_datos';
    $resultados = $wpdb->get_results("SELECT * FROM $tabla_nombre ORDER BY id DESC");

    $output = '<div class="jules-datos-display">';
    $output .= '<h3>Lista de Nombres y Apellidos</h3>';
    $output .= '<table style="width:100%; border-collapse: collapse; border: 1px solid #ccc;">';
    $output .= '<thead><tr><th style="border: 1px solid #ccc; padding: 8px;">Nombre</th><th style="border: 1px solid #ccc; padding: 8px;">Apellido</th></tr></thead>';
    $output .= '<tbody>';

    if ($resultados) {
        foreach ($resultados as $fila) {
            $output .= '<tr>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->nombre) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->apellido) . '</td>';
            $output .= '</tr>';
        }
    } else {
        $output .= '<tr><td colspan="2" style="border: 1px solid #ccc; padding: 8px; text-align:center;">No hay datos registrados aún.</td></tr>';
    }

    $output .= '</tbody></table></div>';

    return $output;
}
add_shortcode('jules_datos', 'jules_shortcode_datos');

// Función para el shortcode [jules_notas]
function jules_shortcode_notas($atts) {
    global $wpdb;
    $tabla_notas = $wpdb->prefix . 'jules_notas';
    $tabla_datos = $wpdb->prefix . 'jules_datos';

    $query = "
        SELECT n.*, d.nombre, d.apellido
        FROM $tabla_notas n
        LEFT JOIN $tabla_datos d ON n.jules_dato_id = d.id
        ORDER BY n.id DESC";
    $resultados = $wpdb->get_results($query);

    $output = '<div class="jules-notas-display">';
    $output .= '<h3>Notas Registradas</h3>';
    $output .= '<table style="width:100%; border-collapse: collapse; border: 1px solid #ccc;">';
    $output .= '<thead><tr>';
    $output .= '<th style="border: 1px solid #ccc; padding: 8px;">Persona</th>';
    $output .= '<th style="border: 1px solid #ccc; padding: 8px;">Curso</th>';
    $output .= '<th style="border: 1px solid #ccc; padding: 8px;">Nota</th>';
    $output .= '<th style="border: 1px solid #ccc; padding: 8px;">Docente</th>';
    $output .= '<th style="border: 1px solid #ccc; padding: 8px;">Estado</th>';
    $output .= '</tr></thead>';
    $output .= '<tbody>';

    if ($resultados) {
        foreach ($resultados as $fila) {
            $output .= '<tr>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->nombre . ' ' . $fila->apellido) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->curso) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . nl2br(esc_html($fila->nota)) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->docente_nombre) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->estado) . '</td>';
            $output .= '</tr>';
        }
    } else {
        $output .= '<tr><td colspan="5" style="border: 1px solid #ccc; padding: 8px; text-align:center;">No hay notas registradas aún.</td></tr>';
    }

    $output .= '</tbody></table></div>';

    return $output;
}
add_shortcode('jules_notas', 'jules_shortcode_notas');
add_action('admin_notices', 'jules_mostrar_aviso_admin');

// Función para procesar la subida, actualización o eliminación de NOTAS
function jules_procesar_notas() {
    global $wpdb;
    $tabla_notas = $wpdb->prefix . 'jules_notas';

    // Manejar Eliminación de Notas
    if (isset($_GET['action']) && $_GET['action'] === 'delete_nota' && isset($_GET['id'])) {
        $id_a_eliminar = intval($_GET['id']);

        // Verificar nonce de eliminación
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jules_eliminar_nota_' . $id_a_eliminar)) {
            wp_die('Error de seguridad. No se puede procesar la eliminación.');
        }

        $resultado = $wpdb->delete($tabla_notas, array('id' => $id_a_eliminar), array('%d'));

        if ($resultado) {
            echo '<div class="updated"><p>Nota eliminada correctamente.</p></div>';
        } else {
            echo '<div class="error"><p>Hubo un error al eliminar la nota.</p></div>';
        }
    }

    // Manejar Guardado/Actualización de Notas
    if (isset($_POST['submit_nota'])) {
        // Verificar nonce por seguridad
        if (!isset($_POST['jules_notas_nonce']) || !wp_verify_nonce($_POST['jules_notas_nonce'], 'jules_guardar_notas')) {
            wp_die('Error de seguridad. No se puede procesar la solicitud.');
        }

        $jules_dato_id = intval($_POST['jules_dato_id']);
        $curso = sanitize_text_field($_POST['curso']);
        $nota = sanitize_textarea_field($_POST['nota']);
        $docente_nombre = sanitize_text_field($_POST['docente_nombre']);
        $estado = sanitize_text_field($_POST['estado']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if ($jules_dato_id > 0 && !empty($curso) && !empty($nota)) {
            if ($item_id > 0) {
                // Actualizar nota existente
                $resultado = $wpdb->update(
                    $tabla_notas,
                    array(
                        'jules_dato_id' => $jules_dato_id,
                        'curso' => $curso,
                        'nota' => $nota,
                        'docente_nombre' => $docente_nombre,
                        'estado' => $estado,
                    ),
                    array('id' => $item_id),
                    array('%d', '%s', '%s', '%s', '%s'),
                    array('%d')
                );
            } else {
                // Insertar nueva nota
                $resultado = $wpdb->insert(
                    $tabla_notas,
                    array(
                        'jules_dato_id' => $jules_dato_id,
                        'curso' => $curso,
                        'nota' => $nota,
                        'docente_nombre' => $docente_nombre,
                        'estado' => $estado,
                    ),
                    array('%d', '%s', '%s', '%s', '%s')
                );
            }

            if ($resultado !== false) {
                echo '<div class="updated"><p>Nota guardada correctamente.</p></div>';
            } else {
                echo '<div class="error"><p>Hubo un error al guardar la nota.</p></div>';
            }
        }
    }
}

// Añadir el menú de administración
function jules_menu_admin() {
    // Menú principal
    add_menu_page(
        'Prueba de Jules',
        'Prueba de Jules',
        'manage_options',
        'jules-prueba',
        'jules_pagina_admin',
        'dashicons-admin-plugins'
    );

    // Submenú para Notas
    add_submenu_page(
        'jules-prueba',
        'Gestionar Notas',
        'Gestionar Notas',
        'manage_options',
        'jules-notas',
        'jules_pagina_notas_admin'
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
