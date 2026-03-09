<?php
/*
Plugin Name: Prueba de Jules
Description: Un plugin simple de prueba para mostrar un aviso.
Version: 1.0
Author: Jules
*/

// Función para mostrar el aviso
function jules_mostrar_aviso_admin() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p>¡Hola! Este plugin fue creado por Jules.</p>
    </div>
    <?php
}

// Conectar la función al gancho (hook) de avisos de admin
add_action('admin_notices', 'jules_mostrar_aviso_admin');
