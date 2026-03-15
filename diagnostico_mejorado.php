<?php
/**
 * Diagnóstico mejorado para verificar funcionalidad
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Diagnóstico del Lector de Markdown</h1>";

// 1. Verificar configuración
echo "<h2>1. Configuración</h2>";
echo "<p><strong>Base URL:</strong> " . $config['base_url'] . "</p>";
echo "<p><strong>Content Dir:</strong> " . $config['content_dir'] . "</p>";
echo "<p><strong>App Title:</strong> " . $config['app_title'] . "</p>";

// 2. Verificar rutas
echo "<h2>2. Rutas</h2>";
$contentPath = getContentPath();
echo "<p><strong>Content Path:</strong> " . $contentPath . "</p>";
echo "<p><strong>Existe Content Path:</strong> " . (is_dir($contentPath) ? 'Sí' : 'No') . "</p>";

// 3. Buscar archivo más reciente
echo "<h2>3. Archivo Más Reciente</h2>";
$latestFile = findMostRecentMarkdownFile($contentPath);
if ($latestFile) {
    echo "<p><strong>Archivo encontrado:</strong> " . $latestFile . "</p>";
    echo "<p><strong>Fecha modificación:</strong> " . date('Y-m-d H:i:s', filemtime($latestFile)) . "</p>";
    $relPath = getRelativePath($latestFile);
    echo "<p><strong>Ruta relativa:</strong> " . $relPath . "</p>";
    $url = generateFileUrl($latestFile);
    echo "<p><strong>URL generada:</strong> <a href='" . $url . "'>" . $url . "</a></p>";
} else {
    echo "<p><strong>Error:</strong> No se encontró ningún archivo .md</p>";
}

// 4. Listar estructura de contenido
echo "<h2>4. Estructura de Contenido</h2>";
if (is_dir($contentPath)) {
    $structure = scanDirectory($contentPath);
    echo "<pre>";
    print_r($structure);
    echo "</pre>";
} else {
    echo "<p><strong>Error:</strong> El directorio de contenido no existe</p>";
}

// 5. Verificar archivos clave
echo "<h2>5. Archivos Clave</h2>";
$keyFiles = [
    'index.php',
    'config.php',
    '.htaccess',
    'assets/js/script.js',
    'assets/css/styles.css',
    'includes/functions.php',
    'includes/layout.php',
    'includes/sidebar.php',
    'content/index.md'
];

foreach ($keyFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "<p><strong>" . $file . ":</strong> " . ($exists ? '✅ Existe' : '❌ No existe') . "</p>";
}

// 6. Verificar funciones nuevas
echo "<h2>6. Funciones Nuevas</h2>";
echo "<p><strong>extractContentMetadata:</strong> " . (function_exists('extractContentMetadata') ? '✅ Disponible' : '❌ No disponible') . "</p>";
echo "<p><strong>generateOpenGraphTags:</strong> " . (function_exists('generateOpenGraphTags') ? '✅ Disponible' : '❌ No disponible') . "</p>";

// 7. Prueba de metadatos
if (function_exists('extractContentMetadata') && $latestFile) {
    echo "<h2>7. Prueba de Metadatos</h2>";
    $content = file_get_contents($latestFile);
    require_once __DIR__ . '/includes/markdown.php';
    $parser = new MarkdownParser();
    $html = $parser->parse($content);
    $title = $parser->getTitle($content);
    
    $metadata = extractContentMetadata($html, $title);
    echo "<h3>Metadatos extraídos:</h3>";
    echo "<pre>";
    print_r($metadata);
    echo "</pre>";
    
    $currentUrl = $config['base_url'];
    $openGraphTags = generateOpenGraphTags($metadata, $currentUrl);
    echo "<h3>Meta tags generados:</h3>";
    echo "<pre>" . htmlspecialchars($openGraphTags) . "</pre>";
}

echo "<hr>";
echo "<p><strong>Diagnóstico completado</strong> - " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='index.php'>← Volver al inicio</a></p>";
?>
