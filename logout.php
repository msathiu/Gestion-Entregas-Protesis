<?php
/**
 * Cierre de Sesión
 * Asignatura: Bases de Datos - Ingeniería de Sistemas (ULA)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/Auth.php';

Auth::logout();
header('Location: login.php');
exit;
