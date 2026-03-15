<?php
/**
 * Lector de archivos Markdown
 * Punto de entrada principal de la aplicación
 */
// Configuración de errores
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Incluir archivos necesarios
require_once __DIR__ . '/config.php'; 
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/router.php';

// Analizar la URL actual
$route = parseUrl();

// Renderizar la página correspondiente
renderPage($route);
?>