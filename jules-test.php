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
        id_curso mediumint(9) NOT NULL,
        id_doce mediumint(9) NOT NULL,
        id_estado mediumint(9) NOT NULL,
        nota text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_notas);

    // Tabla de docentes
    $tabla_docentes = $wpdb->prefix . 'jules_docentes';
    $sql_docentes = "CREATE TABLE $tabla_docentes (
        id_doce mediumint(9) NOT NULL AUTO_INCREMENT,
        nombres varchar(100) NOT NULL,
        apellidos varchar(100) NOT NULL,
        cursos text NOT NULL,
        PRIMARY KEY  (id_doce)
    ) $charset_collate;";
    dbDelta($sql_docentes);

    // Tabla de cursos
    $tabla_cursos = $wpdb->prefix . 'jules_cursos';
    $sql_cursos = "CREATE TABLE $tabla_cursos (
        id_curso mediumint(9) NOT NULL AUTO_INCREMENT,
        nombre_del_curso varchar(100) NOT NULL,
        PRIMARY KEY  (id_curso)
    ) $charset_collate;";
    dbDelta($sql_cursos);

    // Tabla de aulas
    $tabla_aulas = $wpdb->prefix . 'jules_aulas';
    $sql_aulas = "CREATE TABLE $tabla_aulas (
        id_aula mediumint(9) NOT NULL AUTO_INCREMENT,
        aula varchar(100) NOT NULL,
        pabellon varchar(100) NOT NULL,
        PRIMARY KEY  (id_aula)
    ) $charset_collate;";
    dbDelta($sql_aulas);

    // Tabla de estados
    $tabla_estados = $wpdb->prefix . 'jules_estados';
    $sql_estados = "CREATE TABLE $tabla_estados (
        id_estado mediumint(9) NOT NULL AUTO_INCREMENT,
        estado varchar(100) NOT NULL,
        PRIMARY KEY  (id_estado)
    ) $charset_collate;";
    dbDelta($sql_estados);
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
    $curso_id_valor = $item_a_editar ? $item_a_editar->id_curso : 0;
    $doce_id_valor = $item_a_editar ? $item_a_editar->id_doce : 0;
    $estado_id_valor = $item_a_editar ? $item_a_editar->id_estado : 0;
    $nota_valor = $item_a_editar ? esc_textarea($item_a_editar->nota) : '';
    $boton_texto = $item_a_editar ? 'Actualizar Nota' : 'Guardar Nota';
    $titulo_pagina = $item_a_editar ? 'Editar Nota' : 'Gestionar Notas';

    // Obtener listas para los dropdowns
    $personas = $wpdb->get_results("SELECT id, nombre, apellido FROM $tabla_datos ORDER BY nombre ASC");
    $cursos = $wpdb->get_results("SELECT id_curso, nombre_del_curso FROM {$wpdb->prefix}jules_cursos ORDER BY nombre_del_curso ASC");
    $docentes = $wpdb->get_results("SELECT id_doce, nombres, apellidos FROM {$wpdb->prefix}jules_docentes ORDER BY nombres ASC");
    $estados = $wpdb->get_results("SELECT id_estado, estado FROM {$wpdb->prefix}jules_estados ORDER BY estado ASC");

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
                    <th scope="row"><label for="id_curso">Curso</label></th>
                    <td>
                        <select name="id_curso" id="id_curso" required>
                            <option value="">Seleccione un curso...</option>
                            <?php foreach ($cursos as $curso) : ?>
                                <option value="<?php echo intval($curso->id_curso); ?>" <?php selected($curso_id_valor, $curso->id_curso); ?>>
                                    <?php echo esc_html($curso->nombre_del_curso); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="id_doce">Docente</label></th>
                    <td>
                        <select name="id_doce" id="id_doce" required>
                            <option value="">Seleccione un docente...</option>
                            <?php foreach ($docentes as $docente) : ?>
                                <option value="<?php echo intval($docente->id_doce); ?>" <?php selected($doce_id_valor, $docente->id_doce); ?>>
                                    <?php echo esc_html($docente->nombres . ' ' . $docente->apellidos); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="nota">Nota</label></th>
                    <td><textarea name="nota" id="nota" rows="5" class="large-text" required><?php echo $nota_valor; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="id_estado">Estado</label></th>
                    <td>
                        <select name="id_estado" id="id_estado" required>
                            <option value="">Seleccione un estado...</option>
                            <?php foreach ($estados as $estado) : ?>
                                <option value="<?php echo intval($estado->id_estado); ?>" <?php selected($estado_id_valor, $estado->id_estado); ?>>
                                    <?php echo esc_html($estado->estado); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
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
            SELECT n.*, d.nombre as d_nom, d.apellido as d_ape, c.nombre_del_curso, doc.nombres as doc_nom, doc.apellidos as doc_ape, e.estado as estado_nom
            FROM $tabla_notas n
            LEFT JOIN $tabla_datos d ON n.jules_dato_id = d.id
            LEFT JOIN {$wpdb->prefix}jules_cursos c ON n.id_curso = c.id_curso
            LEFT JOIN {$wpdb->prefix}jules_docentes doc ON n.id_doce = doc.id_doce
            LEFT JOIN {$wpdb->prefix}jules_estados e ON n.id_estado = e.id_estado
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
                            <td><?php echo esc_html($fila->d_nom . ' ' . $fila->d_ape); ?></td>
                            <td><?php echo esc_html($fila->nombre_del_curso); ?></td>
                            <td><?php echo nl2br(esc_html($fila->nota)); ?></td>
                            <td><?php echo esc_html($fila->doc_nom . ' ' . $fila->doc_ape); ?></td>
                            <td><?php echo esc_html($fila->estado_nom); ?></td>
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

// Página de administración para Docentes: UI (Formulario y Visualización)
function jules_pagina_docentes_admin() {
    global $wpdb;
    $tabla_docentes = $wpdb->prefix . 'jules_docentes';

    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $item_a_editar = null;

    if ($edit_id > 0) {
        $item_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_docentes WHERE id_doce = %d", $edit_id));
    }

    // Manejo de la lógica de guardado/eliminación
    jules_procesar_docentes();

    $nombres_valor = $item_a_editar ? esc_attr($item_a_editar->nombres) : '';
    $apellidos_valor = $item_a_editar ? esc_attr($item_a_editar->apellidos) : '';
    $cursos_valor = $item_a_editar ? esc_attr($item_a_editar->cursos) : '';
    $boton_texto = $item_a_editar ? 'Actualizar Docente' : 'Guardar Docente';
    $titulo_pagina = $item_a_editar ? 'Editar Docente' : 'Gestionar Docentes';

    ?>
    <div class="wrap">
        <h1>
            Prueba de Jules - <?php echo $titulo_pagina; ?>
            <?php if ($item_a_editar) : ?>
                <a href="admin.php?page=jules-docentes" class="page-title-action">Añadir Nuevo</a>
            <?php endif; ?>
        </h1>

        <form method="post" action="admin.php?page=jules-docentes">
            <?php wp_nonce_field('jules_guardar_docente', 'jules_docente_nonce'); ?>

            <?php if ($item_a_editar) : ?>
                <input type="hidden" name="item_id" value="<?php echo intval($item_a_editar->id_doce); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nombres">Nombres</label></th>
                    <td><input name="nombres" type="text" id="nombres" value="<?php echo $nombres_valor; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="apellidos">Apellidos</label></th>
                    <td><input name="apellidos" type="text" id="apellidos" value="<?php echo $apellidos_valor; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="cursos">Cursos (Separados por coma)</label></th>
                    <td><input name="cursos" type="text" id="cursos" value="<?php echo $cursos_valor; ?>" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_doce" id="submit" class="button button-primary" value="<?php echo $boton_texto; ?>">
                <?php if ($item_a_editar) : ?>
                    <a href="admin.php?page=jules-docentes" class="button">Cancelar Edición</a>
                <?php endif; ?>
            </p>
        </form>

        <hr>

        <h2>Docentes Registrados</h2>
        <?php
        $resultados = $wpdb->get_results("SELECT * FROM $tabla_docentes ORDER BY id_doce DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Cursos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados) : ?>
                    <?php foreach ($resultados as $fila) : ?>
                        <tr>
                            <td><?php echo esc_html($fila->id_doce); ?></td>
                            <td><?php echo esc_html($fila->nombres); ?></td>
                            <td><?php echo esc_html($fila->apellidos); ?></td>
                            <td><?php echo esc_html($fila->cursos); ?></td>
                            <td>
                                <a href="admin.php?page=jules-docentes&edit_id=<?php echo intval($fila->id_doce); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url('admin.php?page=jules-docentes&action=delete_doce&id=' . $fila->id_doce, 'jules_eliminar_doce_' . $fila->id_doce); ?>"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar este docente?');"
                                   style="color:red;">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">No hay docentes registrados aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Página de administración para Cursos: UI (Formulario y Visualización)
function jules_pagina_cursos_admin() {
    global $wpdb;
    $tabla_cursos = $wpdb->prefix . 'jules_cursos';

    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $item_a_editar = null;

    if ($edit_id > 0) {
        $item_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_cursos WHERE id_curso = %d", $edit_id));
    }

    // Manejo de la lógica de guardado/eliminación
    jules_procesar_cursos();

    $nombre_curso_valor = $item_a_editar ? esc_attr($item_a_editar->nombre_del_curso) : '';
    $boton_texto = $item_a_editar ? 'Actualizar Curso' : 'Guardar Curso';
    $titulo_pagina = $item_a_editar ? 'Editar Curso' : 'Gestionar Cursos';

    ?>
    <div class="wrap">
        <h1>
            Prueba de Jules - <?php echo $titulo_pagina; ?>
            <?php if ($item_a_editar) : ?>
                <a href="admin.php?page=jules-cursos" class="page-title-action">Añadir Nuevo</a>
            <?php endif; ?>
        </h1>

        <form method="post" action="admin.php?page=jules-cursos">
            <?php wp_nonce_field('jules_guardar_curso', 'jules_curso_nonce'); ?>

            <?php if ($item_a_editar) : ?>
                <input type="hidden" name="item_id" value="<?php echo intval($item_a_editar->id_curso); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="nombre_del_curso">Nombre del Curso</label></th>
                    <td><input name="nombre_del_curso" type="text" id="nombre_del_curso" value="<?php echo $nombre_curso_valor; ?>" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_curso" id="submit" class="button button-primary" value="<?php echo $boton_texto; ?>">
                <?php if ($item_a_editar) : ?>
                    <a href="admin.php?page=jules-cursos" class="button">Cancelar Edición</a>
                <?php endif; ?>
            </p>
        </form>

        <hr>

        <h2>Cursos Registrados</h2>
        <?php
        $resultados = $wpdb->get_results("SELECT * FROM $tabla_cursos ORDER BY id_curso DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre del Curso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados) : ?>
                    <?php foreach ($resultados as $fila) : ?>
                        <tr>
                            <td><?php echo esc_html($fila->id_curso); ?></td>
                            <td><?php echo esc_html($fila->nombre_del_curso); ?></td>
                            <td>
                                <a href="admin.php?page=jules-cursos&edit_id=<?php echo intval($fila->id_curso); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url('admin.php?page=jules-cursos&action=delete_curso&id=' . $fila->id_curso, 'jules_eliminar_curso_' . $fila->id_curso); ?>"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar este curso?');"
                                   style="color:red;">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3">No hay cursos registrados aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Página de administración para Aulas: UI (Formulario y Visualización)
function jules_pagina_aulas_admin() {
    global $wpdb;
    $tabla_aulas = $wpdb->prefix . 'jules_aulas';

    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $item_a_editar = null;

    if ($edit_id > 0) {
        $item_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_aulas WHERE id_aula = %d", $edit_id));
    }

    // Manejo de la lógica de guardado/eliminación
    jules_procesar_aulas();

    $aula_valor = $item_a_editar ? esc_attr($item_a_editar->aula) : '';
    $pabellon_valor = $item_a_editar ? esc_attr($item_a_editar->pabellon) : '';
    $boton_texto = $item_a_editar ? 'Actualizar Aula' : 'Guardar Aula';
    $titulo_pagina = $item_a_editar ? 'Editar Aula' : 'Gestionar Aulas';

    ?>
    <div class="wrap">
        <h1>
            Prueba de Jules - <?php echo $titulo_pagina; ?>
            <?php if ($item_a_editar) : ?>
                <a href="admin.php?page=jules-aulas" class="page-title-action">Añadir Nueva</a>
            <?php endif; ?>
        </h1>

        <form method="post" action="admin.php?page=jules-aulas">
            <?php wp_nonce_field('jules_guardar_aula', 'jules_aula_nonce'); ?>

            <?php if ($item_a_editar) : ?>
                <input type="hidden" name="item_id" value="<?php echo intval($item_a_editar->id_aula); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="aula">Aula</label></th>
                    <td><input name="aula" type="text" id="aula" value="<?php echo $aula_valor; ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="pabellon">Pabellón</label></th>
                    <td><input name="pabellon" type="text" id="pabellon" value="<?php echo $pabellon_valor; ?>" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_aula" id="submit" class="button button-primary" value="<?php echo $boton_texto; ?>">
                <?php if ($item_a_editar) : ?>
                    <a href="admin.php?page=jules-aulas" class="button">Cancelar Edición</a>
                <?php endif; ?>
            </p>
        </form>

        <hr>

        <h2>Aulas Registradas</h2>
        <?php
        $resultados = $wpdb->get_results("SELECT * FROM $tabla_aulas ORDER BY id_aula DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Aula</th>
                    <th>Pabellón</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados) : ?>
                    <?php foreach ($resultados as $fila) : ?>
                        <tr>
                            <td><?php echo esc_html($fila->id_aula); ?></td>
                            <td><?php echo esc_html($fila->aula); ?></td>
                            <td><?php echo esc_html($fila->pabellon); ?></td>
                            <td>
                                <a href="admin.php?page=jules-aulas&edit_id=<?php echo intval($fila->id_aula); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url('admin.php?page=jules-aulas&action=delete_aula&id=' . $fila->id_aula, 'jules_eliminar_aula_' . $fila->id_aula); ?>"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar esta aula?');"
                                   style="color:red;">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="4">No hay aulas registradas aún.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Página de administración para Estados: UI (Formulario y Visualización)
function jules_pagina_estados_admin() {
    global $wpdb;
    $tabla_estados = $wpdb->prefix . 'jules_estados';

    $edit_id = isset($_GET['edit_id']) ? intval($_GET['edit_id']) : 0;
    $item_a_editar = null;

    if ($edit_id > 0) {
        $item_a_editar = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla_estados WHERE id_estado = %d", $edit_id));
    }

    // Manejo de la lógica de guardado/eliminación
    jules_procesar_estados();

    $estado_valor = $item_a_editar ? esc_attr($item_a_editar->estado) : '';
    $boton_texto = $item_a_editar ? 'Actualizar Estado' : 'Guardar Estado';
    $titulo_pagina = $item_a_editar ? 'Editar Estado' : 'Gestionar Estados';

    ?>
    <div class="wrap">
        <h1>
            Prueba de Jules - <?php echo $titulo_pagina; ?>
            <?php if ($item_a_editar) : ?>
                <a href="admin.php?page=jules-estados" class="page-title-action">Añadir Nuevo</a>
            <?php endif; ?>
        </h1>

        <form method="post" action="admin.php?page=jules-estados">
            <?php wp_nonce_field('jules_guardar_estado', 'jules_estado_nonce'); ?>

            <?php if ($item_a_editar) : ?>
                <input type="hidden" name="item_id" value="<?php echo intval($item_a_editar->id_estado); ?>">
            <?php endif; ?>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="estado">Estado</label></th>
                    <td><input name="estado" type="text" id="estado" value="<?php echo $estado_valor; ?>" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_estado" id="submit" class="button button-primary" value="<?php echo $boton_texto; ?>">
                <?php if ($item_a_editar) : ?>
                    <a href="admin.php?page=jules-estados" class="button">Cancelar Edición</a>
                <?php endif; ?>
            </p>
        </form>

        <hr>

        <h2>Estados Registrados</h2>
        <?php
        $resultados = $wpdb->get_results("SELECT * FROM $tabla_estados ORDER BY id_estado DESC");
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultados) : ?>
                    <?php foreach ($resultados as $fila) : ?>
                        <tr>
                            <td><?php echo esc_html($fila->id_estado); ?></td>
                            <td><?php echo esc_html($fila->estado); ?></td>
                            <td>
                                <a href="admin.php?page=jules-estados&edit_id=<?php echo intval($fila->id_estado); ?>">Editar</a> |
                                <a href="<?php echo wp_nonce_url('admin.php?page=jules-estados&action=delete_estado&id=' . $fila->id_estado, 'jules_eliminar_estado_' . $fila->id_estado); ?>"
                                   onclick="return confirm('¿Estás seguro de que deseas eliminar este estado?');"
                                   style="color:red;">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="3">No hay estados registrados aún.</td>
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
    $tabla_cursos = $wpdb->prefix . 'jules_cursos';
    $tabla_docentes = $wpdb->prefix . 'jules_docentes';
    $tabla_estados = $wpdb->prefix . 'jules_estados';

    $query = "
        SELECT n.*, d.nombre as d_nom, d.apellido as d_ape, c.nombre_del_curso, doc.nombres as doc_nom, doc.apellidos as doc_ape, e.estado as estado_nom
        FROM $tabla_notas n
        LEFT JOIN $tabla_datos d ON n.jules_dato_id = d.id
        LEFT JOIN $tabla_cursos c ON n.id_curso = c.id_curso
        LEFT JOIN $tabla_docentes doc ON n.id_doce = doc.id_doce
        LEFT JOIN $tabla_estados e ON n.id_estado = e.id_estado
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
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->d_nom . ' ' . $fila->d_ape) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->nombre_del_curso) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . nl2br(esc_html($fila->nota)) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->doc_nom . ' ' . $fila->doc_ape) . '</td>';
            $output .= '<td style="border: 1px solid #ccc; padding: 8px;">' . esc_html($fila->estado_nom) . '</td>';
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

// Función para procesar la subida, actualización o eliminación de AULAS
function jules_procesar_aulas() {
    global $wpdb;
    $tabla_aulas = $wpdb->prefix . 'jules_aulas';

    // Manejar Eliminación de Aulas
    if (isset($_GET['action']) && $_GET['action'] === 'delete_aula' && isset($_GET['id'])) {
        $id_a_eliminar = intval($_GET['id']);

        // Verificar nonce de eliminación
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jules_eliminar_aula_' . $id_a_eliminar)) {
            wp_die('Error de seguridad. No se puede procesar la eliminación.');
        }

        $resultado = $wpdb->delete($tabla_aulas, array('id_aula' => $id_a_eliminar), array('%d'));

        if ($resultado) {
            echo '<div class="updated"><p>Aula eliminada correctamente.</p></div>';
        } else {
            echo '<div class="error"><p>Hubo un error al eliminar el aula.</p></div>';
        }
    }

    // Manejar Guardado/Actualización de Aulas
    if (isset($_POST['submit_aula'])) {
        // Verificar nonce por seguridad
        if (!isset($_POST['jules_aula_nonce']) || !wp_verify_nonce($_POST['jules_aula_nonce'], 'jules_guardar_aula')) {
            wp_die('Error de seguridad. No se puede procesar la solicitud.');
        }

        $aula = sanitize_text_field($_POST['aula']);
        $pabellon = sanitize_text_field($_POST['pabellon']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if (!empty($aula) && !empty($pabellon)) {
            if ($item_id > 0) {
                // Actualizar aula existente
                $resultado = $wpdb->update(
                    $tabla_aulas,
                    array(
                        'aula' => $aula,
                        'pabellon' => $pabellon,
                    ),
                    array('id_aula' => $item_id),
                    array('%s', '%s'),
                    array('%d')
                );
            } else {
                // Insertar nueva aula
                $resultado = $wpdb->insert(
                    $tabla_aulas,
                    array(
                        'aula' => $aula,
                        'pabellon' => $pabellon,
                    ),
                    array('%s', '%s')
                );
            }

            if ($resultado !== false) {
                echo '<div class="updated"><p>Aula guardada correctamente.</p></div>';
            } else {
                echo '<div class="error"><p>Hubo un error al guardar el aula.</p></div>';
            }
        }
    }
}

// Función para procesar la subida, actualización o eliminación de ESTADOS
function jules_procesar_estados() {
    global $wpdb;
    $tabla_estados = $wpdb->prefix . 'jules_estados';

    // Manejar Eliminación de Estados
    if (isset($_GET['action']) && $_GET['action'] === 'delete_estado' && isset($_GET['id'])) {
        $id_a_eliminar = intval($_GET['id']);

        // Verificar nonce de eliminación
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jules_eliminar_estado_' . $id_a_eliminar)) {
            wp_die('Error de seguridad. No se puede procesar la eliminación.');
        }

        $resultado = $wpdb->delete($tabla_estados, array('id_estado' => $id_a_eliminar), array('%d'));

        if ($resultado) {
            echo '<div class="updated"><p>Estado eliminado correctamente.</p></div>';
        } else {
            echo '<div class="error"><p>Hubo un error al eliminar el estado.</p></div>';
        }
    }

    // Manejar Guardado/Actualización de Estados
    if (isset($_POST['submit_estado'])) {
        // Verificar nonce por seguridad
        if (!isset($_POST['jules_estado_nonce']) || !wp_verify_nonce($_POST['jules_estado_nonce'], 'jules_guardar_estado')) {
            wp_die('Error de seguridad. No se puede procesar la solicitud.');
        }

        $estado = sanitize_text_field($_POST['estado']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if (!empty($estado)) {
            if ($item_id > 0) {
                // Actualizar estado existente
                $resultado = $wpdb->update(
                    $tabla_estados,
                    array('estado' => $estado),
                    array('id_estado' => $item_id),
                    array('%s'),
                    array('%d')
                );
            } else {
                // Insertar nuevo estado
                $resultado = $wpdb->insert(
                    $tabla_estados,
                    array('estado' => $estado),
                    array('%s')
                );
            }

            if ($resultado !== false) {
                echo '<div class="updated"><p>Estado guardado correctamente.</p></div>';
            } else {
                echo '<div class="error"><p>Hubo un error al guardar el estado.</p></div>';
            }
        }
    }
}

// Función para procesar la subida, actualización o eliminación de CURSOS
function jules_procesar_cursos() {
    global $wpdb;
    $tabla_cursos = $wpdb->prefix . 'jules_cursos';

    // Manejar Eliminación de Cursos
    if (isset($_GET['action']) && $_GET['action'] === 'delete_curso' && isset($_GET['id'])) {
        $id_a_eliminar = intval($_GET['id']);

        // Verificar nonce de eliminación
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jules_eliminar_curso_' . $id_a_eliminar)) {
            wp_die('Error de seguridad. No se puede procesar la eliminación.');
        }

        $resultado = $wpdb->delete($tabla_cursos, array('id_curso' => $id_a_eliminar), array('%d'));

        if ($resultado) {
            echo '<div class="updated"><p>Curso eliminado correctamente.</p></div>';
        } else {
            echo '<div class="error"><p>Hubo un error al eliminar el curso.</p></div>';
        }
    }

    // Manejar Guardado/Actualización de Cursos
    if (isset($_POST['submit_curso'])) {
        // Verificar nonce por seguridad
        if (!isset($_POST['jules_curso_nonce']) || !wp_verify_nonce($_POST['jules_curso_nonce'], 'jules_guardar_curso')) {
            wp_die('Error de seguridad. No se puede procesar la solicitud.');
        }

        $nombre_del_curso = sanitize_text_field($_POST['nombre_del_curso']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if (!empty($nombre_del_curso)) {
            if ($item_id > 0) {
                // Actualizar curso existente
                $resultado = $wpdb->update(
                    $tabla_cursos,
                    array('nombre_del_curso' => $nombre_del_curso),
                    array('id_curso' => $item_id),
                    array('%s'),
                    array('%d')
                );
            } else {
                // Insertar nuevo curso
                $resultado = $wpdb->insert(
                    $tabla_cursos,
                    array('nombre_del_curso' => $nombre_del_curso),
                    array('%s')
                );
            }

            if ($resultado !== false) {
                echo '<div class="updated"><p>Curso guardado correctamente.</p></div>';
            } else {
                echo '<div class="error"><p>Hubo un error al guardar el curso.</p></div>';
            }
        }
    }
}

// Función para procesar la subida, actualización o eliminación de DOCENTES
function jules_procesar_docentes() {
    global $wpdb;
    $tabla_docentes = $wpdb->prefix . 'jules_docentes';

    // Manejar Eliminación de Docentes
    if (isset($_GET['action']) && $_GET['action'] === 'delete_doce' && isset($_GET['id'])) {
        $id_a_eliminar = intval($_GET['id']);

        // Verificar nonce de eliminación
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'jules_eliminar_doce_' . $id_a_eliminar)) {
            wp_die('Error de seguridad. No se puede procesar la eliminación.');
        }

        $resultado = $wpdb->delete($tabla_docentes, array('id_doce' => $id_a_eliminar), array('%d'));

        if ($resultado) {
            echo '<div class="updated"><p>Docente eliminado correctamente.</p></div>';
        } else {
            echo '<div class="error"><p>Hubo un error al eliminar al docente.</p></div>';
        }
    }

    // Manejar Guardado/Actualización de Docentes
    if (isset($_POST['submit_doce'])) {
        // Verificar nonce por seguridad
        if (!isset($_POST['jules_docente_nonce']) || !wp_verify_nonce($_POST['jules_docente_nonce'], 'jules_guardar_docente')) {
            wp_die('Error de seguridad. No se puede procesar la solicitud.');
        }

        $nombres = sanitize_text_field($_POST['nombres']);
        $apellidos = sanitize_text_field($_POST['apellidos']);
        $cursos = sanitize_text_field($_POST['cursos']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if (!empty($nombres) && !empty($apellidos)) {
            if ($item_id > 0) {
                // Actualizar docente existente
                $resultado = $wpdb->update(
                    $tabla_docentes,
                    array(
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'cursos' => $cursos,
                    ),
                    array('id_doce' => $item_id),
                    array('%s', '%s', '%s'),
                    array('%d')
                );
            } else {
                // Insertar nuevo docente
                $resultado = $wpdb->insert(
                    $tabla_docentes,
                    array(
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'cursos' => $cursos,
                    ),
                    array('%s', '%s', '%s')
                );
            }

            if ($resultado !== false) {
                echo '<div class="updated"><p>Docente guardado correctamente.</p></div>';
            } else {
                echo '<div class="error"><p>Hubo un error al guardar al docente.</p></div>';
            }
        }
    }
}

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
        $id_curso = intval($_POST['id_curso']);
        $id_doce = intval($_POST['id_doce']);
        $id_estado = intval($_POST['id_estado']);
        $nota = sanitize_textarea_field($_POST['nota']);
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;

        if ($jules_dato_id > 0 && $id_curso > 0 && $id_doce > 0 && $id_estado > 0 && !empty($nota)) {
            if ($item_id > 0) {
                // Actualizar nota existente
                $resultado = $wpdb->update(
                    $tabla_notas,
                    array(
                        'jules_dato_id' => $jules_dato_id,
                        'id_curso' => $id_curso,
                        'id_doce' => $id_doce,
                        'id_estado' => $id_estado,
                        'nota' => $nota,
                    ),
                    array('id' => $item_id),
                    array('%d', '%d', '%d', '%d', '%s'),
                    array('%d')
                );
            } else {
                // Insertar nueva nota
                $resultado = $wpdb->insert(
                    $tabla_notas,
                    array(
                        'jules_dato_id' => $jules_dato_id,
                        'id_curso' => $id_curso,
                        'id_doce' => $id_doce,
                        'id_estado' => $id_estado,
                        'nota' => $nota,
                    ),
                    array('%d', '%d', '%d', '%d', '%s')
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

    // Submenú para Docentes
    add_submenu_page(
        'jules-prueba',
        'Gestionar Docentes',
        'Gestionar Docentes',
        'manage_options',
        'jules-docentes',
        'jules_pagina_docentes_admin'
    );

    // Submenú para Cursos
    add_submenu_page(
        'jules-prueba',
        'Gestionar Cursos',
        'Gestionar Cursos',
        'manage_options',
        'jules-cursos',
        'jules_pagina_cursos_admin'
    );

    // Submenú para Aulas
    add_submenu_page(
        'jules-prueba',
        'Gestionar Aulas',
        'Gestionar Aulas',
        'manage_options',
        'jules-aulas',
        'jules_pagina_aulas_admin'
    );

    // Submenú para Estados
    add_submenu_page(
        'jules-prueba',
        'Gestionar Estados',
        'Gestionar Estados',
        'manage_options',
        'jules-estados',
        'jules_pagina_estados_admin'
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
