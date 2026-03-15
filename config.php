<?php
/**
 * Archivo de configuración para el Lector de Markdown
 */

// Detectar automáticamente la URL base
function detectBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https:" : "http:";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $baseDir = dirname($scriptName);
    
    // Si está en el directorio raíz, usar '/'
    if ($baseDir === '/' || $baseDir === '\\') {
        return $protocol . '//' . $host . '/';
    }
    
    return $protocol . '//' . $host . $baseDir . '/';
}

// Configuración de rutas y opciones
$config = [
    // Auto-detectar base_url para desarrollo y producción
    'base_url' => detectBaseUrl(),
    // Para forzar una URL específica, descomenta la siguiente línea:
    // 'base_url' => 'https://www.ricardoloria.com/cotidianeidades/',
    
    'content_dir' => 'content',
    'app_title' => 'Lector MD',
    'autor_title' => 'Juan Pérez',
    'display_errors' => true,
    'theme_color' => 'blue',
    'display_options' => [
        'show_reading_time' => true,
        'show_date' => true,
        'show_navigation' => true
    ]
];

// Activar o desactivar la visualización de errores según la configuración
ini_set('display_errors', $config['display_errors'] ? 1 : 0);
ini_set('display_startup_errors', $config['display_errors'] ? 1 : 0);
if ($config['display_errors']) {
    error_reporting(E_ALL);
} else {
    error_reporting(0);
}
?>