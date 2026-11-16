<?php
/**
 * Cierre de Sesión
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth/Auth.php';

Auth::logout();
header('Location: login.php');
exit;
