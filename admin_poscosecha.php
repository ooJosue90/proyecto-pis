<?php
require_once 'conexion.php';
require_auth('Administrador');
require_once __DIR__ . '/includes/poscosecha_helpers.php';
require_once __DIR__ . '/includes/poscosecha_data.php';
require_once __DIR__ . '/includes/poscosecha_view.php';

render_poscosecha_panel($conn, 'Administrador', (string) $_SESSION['id_usuario'], true);
