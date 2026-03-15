<?php
// Activar la visualización de errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de funciones en lugar de redeclarar las funciones
require_once __DIR__ . '/includes/functions.php';

/**
 * Lector de archivos Markdown - Versión de diagnóstico
 * Punto de entrada principal de la aplicación
 */

// Verificar si las carpetas existen
echo "<h1>Diagnóstico del Lector de Markdown</h1>";
echo "<h2>Verificación de estructura de carpetas:</h2>";
echo "<ul>";
echo "<li>Directorio base: " . getBasePath() . " - " . (is_dir(getBasePath()) ? "✅ Existe" : "❌ No existe") . "</li>";
echo "<li>Directorio de contenido: " . getContentPath() . " - " . (is_dir(getContentPath()) ? "✅ Existe" : "❌ No existe") . "</li>";
echo "<li>Directorio de includes: " . getBasePath() . "/includes - " . (is_dir(getBasePath() . "/includes") ? "✅ Existe" : "❌ No existe") . "</li>";
echo "<li>Directorio de assets: " . getBasePath() . "/assets - " . (is_dir(getBasePath() . "/assets") ? "✅ Existe" : "❌ No existe") . "</li>";
echo "</ul>";

// Verificar si los archivos principales existen
echo "<h2>Verificación de archivos principales:</h2>";
echo "<ul>";
$files = [
    "/includes/functions.php",
    "/includes/markdown.php",
    "/includes/router.php",
    "/includes/sidebar.php",
    "/includes/layout.php",
    "/.htaccess"
];

foreach ($files as $file) {
    $path = getBasePath() . $file;
    echo "<li>$file: " . (file_exists($path) ? "✅ Existe" : "❌ No existe") . "</li>";
}
echo "</ul>";

// Verificar si hay archivos de contenido
echo "<h2>Verificación de archivos de contenido:</h2>";
echo "<ul>";
if (is_dir(getContentPath())) {
    $hasContent = false;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(getContentPath(), RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        if ($item->isFile() && pathinfo($item->getPathname(), PATHINFO_EXTENSION) === 'md') {
            echo "<li>" . str_replace(getBasePath(), '', $item->getPathname()) . " - ✅ Existe</li>";
            $hasContent = true;
        }
    }
    
    if (!$hasContent) {
        echo "<li>❌ No se encontraron archivos Markdown en la carpeta de contenido</li>";
    }
} else {
    echo "<li>❌ No se puede acceder a la carpeta de contenido</li>";
}
echo "</ul>";

// Verificar la configuración de PHP
echo "<h2>Configuración de PHP:</h2>";
echo "<ul>";
echo "<li>Versión de PHP: " . phpversion() . "</li>";
echo "<li>display_errors: " . ini_get('display_errors') . "</li>";
echo "<li>error_reporting: " . ini_get('error_reporting') . "</li>";
echo "</ul>";

// Intentar incluir los archivos principales para ver si hay errores
echo "<h2>Prueba de inclusión de archivos:</h2>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<p>Archivos functions.php ya incluido correctamente</p>";

// Intentar incluir otros archivos principales
$files = [
    "/includes/markdown.php",
    "/includes/router.php",
    "/includes/sidebar.php",
    "/includes/layout.php"
];

foreach ($files as $file) {
    $path = getBasePath() . $file;
    echo "<p>Intentando incluir $file...</p>";
    if (file_exists($path)) {
        try {
            include_once $path;
            echo "<p style='color: green;'>✅ $file incluido correctamente</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error al incluir $file: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ No se puede incluir $file porque no existe</p>";
    }
}

// Intentar acceder a la URL actual
echo "<h2>Información de la solicitud:</h2>";
echo "<ul>";
echo "<li>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</li>";
echo "<li>SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "<li>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "</ul>";

// Sugerencias para solucionar problemas
echo "<h2>Posibles soluciones:</h2>";
echo "<ol>";
echo "<li>Asegúrate de que todos los archivos se hayan subido correctamente al servidor.</li>";
echo "<li>Verifica que el módulo mod_rewrite esté habilitado en Apache.</li>";
echo "<li>Comprueba que el archivo .htaccess esté presente y tenga los permisos correctos.</li>";
echo "<li>Asegúrate de que la configuración de AllowOverride en Apache permita el uso de .htaccess.</li>";
echo "<li>Si sigues teniendo problemas, prueba la versión alternativa con parámetros GET.</li>";
echo "</ol>";

echo "<p><a href='index.php?path=2025/abril/250417' style='background-color: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>Probar versión con parámetros GET</a></p>";
echo "</div>";
?>