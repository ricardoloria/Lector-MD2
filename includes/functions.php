<?php
/**
 * Funciones auxiliares para el lector de Markdown
 * (Versión consolidada y actualizada)
 */

// Funciones básicas de ruta
function getBasePath() {
    // Devuelve el directorio raíz del proyecto (el padre de 'includes')
    return dirname(__DIR__);
}

function getContentPath() {
    global $config;
    // Usa la configuración para encontrar el directorio de contenido
    $contentDir = $config['content_dir'] ?? 'content'; // Default a 'content'
    return getBasePath() . '/' . $contentDir;
}

// --- Funciones de URL y Configuración ---

function getBaseUrl() {
    global $config;
    // Asegura que devuelva al menos '/'
    return $config['base_url'] ?? '/';
}

function getFullUrl($path = '') {
    $baseUrl = getBaseUrl();
    // Limpia la ruta relativa
    $path = ltrim((string)$path, '/');

    // Si la base URL no es la raíz, la prefija asegurando la barra final
    if (!empty($baseUrl) && $baseUrl !== '/') {
         $baseUrl = rtrim($baseUrl, '/') . '/';
         return $baseUrl . $path;
    }
    // Si la base es la raíz, devuelve la ruta desde la raíz
    return '/' . $path;
}

// Obtiene la ruta relativa al directorio de contenido, opcionalmente sin extensión .md
function getRelativePath($path, $keepExtension = false) {
    $contentPath = getContentPath();
    $path = (string)$path; // Asegurar que sea string

    // Comprobar si el path está dentro del contentPath
    if (strpos(realpath($path), realpath($contentPath)) === 0) {
         // Calcular ruta relativa desde contentPath
         $relativePath = ltrim(substr(realpath($path), strlen(realpath($contentPath))), DIRECTORY_SEPARATOR);
         // Reemplazar separadores de directorio por / para URLs
         $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
    } else {
         // Si no está en contentPath (p.ej. es el propio contentPath), devolver vacío o manejar según necesidad
         $relativePath = ''; // O quizás devolver basename($path) si se espera algo?
    }


    // Eliminar extensión .md si no se indica lo contrario
    if (!$keepExtension && pathinfo($relativePath, PATHINFO_EXTENSION) === 'md') {
        $relativePath = substr($relativePath, 0, -3);
    }
    return $relativePath;
}


function generateFileUrl($filePath) {
    // Obtiene la ruta relativa SIN extensión .md por defecto
    $relativePath = getRelativePath($filePath, false);
    return getFullUrl($relativePath); // Construye la URL completa
}

function getThemeColor() {
    global $config;
    $theme = $config['theme_color'] ?? 'blue';
    $colors = [
        'blue' => ['primary' => 'blue-600', 'hover' => 'blue-700', 'active' => 'blue-500', 'light' => 'blue-50'],
        'green' => ['primary' => 'green-600', 'hover' => 'green-700', 'active' => 'green-500', 'light' => 'green-50'],
        'purple' => ['primary' => 'purple-600', 'hover' => 'purple-700', 'active' => 'purple-500', 'light' => 'purple-50'],
        'red' => ['primary' => 'red-600', 'hover' => 'red-700', 'active' => 'red-500', 'light' => 'red-50']
     ];
    return $colors[$theme] ?? $colors['blue'];
}

// --- Funciones de Archivo y Contenido ---

function getFileDate($filePath) {
    global $config;
    if (!($config['display_options']['show_date'] ?? true)) {
        return '';
    }
    if (!file_exists($filePath)) return '';
    $timestamp = filemtime($filePath);
    return date('j M Y', $timestamp); // Formato ej: 17 Apr 2025
}

// Devuelve nombres base de archivos adyacentes (sin .md)
function getAdjacentFiles($dirPath, $currentFile) {
    global $config;
    if (!($config['display_options']['show_navigation'] ?? true)) {
        return ['prev' => null, 'next' => null];
    }
    $files = [];
    if (!is_dir($dirPath)) return ['prev' => null, 'next' => null];

    foreach (scandir($dirPath) as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $dirPath . '/' . $file;
        if (!is_dir($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'md') {
            $files[] = $file; // Guarda el nombre completo del archivo .md
        }
    }
    sort($files); // Ordenar alfabéticamente

     // $currentFile debe ser el nombre base + .md para encontrarlo
     $currentFileMd = $currentFile;
     if (pathinfo($currentFile, PATHINFO_EXTENSION) !== 'md') {
         $currentFileMd = $currentFile . '.md';
     }

    $currentIndex = array_search($currentFileMd, $files);
    if ($currentIndex === false) return ['prev' => null, 'next' => null];

    $prev = ($currentIndex > 0) ? $files[$currentIndex - 1] : null;
    $next = ($currentIndex < count($files) - 1) ? $files[$currentIndex + 1] : null;

    // Devuelve solo el nombre base (sin extensión)
    return [
        'prev' => $prev ? pathinfo($prev, PATHINFO_FILENAME) : null,
        'next' => $next ? pathinfo($next, PATHINFO_FILENAME) : null
    ];
}

function scanDirectory($dir) {
    $result = [];
    if (!is_dir($dir)) return $result;
    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            $result[$item] = [
                'type' => 'directory',
                'children' => scanDirectory($path) // Llamada recursiva
            ];
        } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'md') {
            $result[$item] = [
                'type' => 'file',
                'path' => $path, // Guardar ruta completa física
                'modified' => filemtime($path) // Guardar fecha modificación
            ];
        }
    }
    // Opcional: ordenar carpetas primero, luego archivos, o alfabéticamente
    // uksort($result, function ($a, $b) use ($result) { ... });
    return $result;
}

function isFileActive($filePath, $currentPath) {
    // Compara rutas físicas absolutas para determinar si es el archivo actual
    // Asegúrate que $currentPath también sea una ruta física absoluta
    if (!$filePath || !$currentPath) return false;
    return realpath($filePath) === realpath($currentPath);
}

// --- Funciones de Generación de HTML ---

// Genera breadcrumbs con estructura <ol><li> y clases CSS
function generateBreadcrumbs($segments) {
    $html = '<ol class="breadcrumbs">'; // Usa <ol> con la clase CSS

    // 1. Enlace a Inicio
    $html .= '<li class="breadcrumb-item">';
    $html .= '<a href="' . getFullUrl() . '" class="breadcrumb-link" title="Inicio">';
    // Icono de Home
    $html .= '<svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>';
    $html .= '</a>';
    $html .= '</li>';

    // 2. Segmentos intermedios y final
    $path = '';
    $segments = is_array($segments) ? $segments : [];
    $numSegments = count($segments);

    foreach ($segments as $i => $segment) {
        if (empty($segment)) continue;
        // Decodificar segmento por si tiene caracteres especiales como %20
        $decodedSegment = rawurldecode($segment);
        // Construir la ruta acumulativa para la URL (usando segmentos originales codificados si es necesario?)
        // Para URL usamos el segmento tal cual viene de la ruta.
        $currentSegmentPath = $path ? $path . '/' . $segment : $segment;

        // Limpiar nombre para mostrar (quitar .md, usar decodificado)
        $displaySegment = $decodedSegment;
        if (pathinfo($decodedSegment, PATHINFO_EXTENSION) === 'md') {
            $displaySegment = pathinfo($decodedSegment, PATHINFO_FILENAME);
        }
        // Reemplazar guiones o underscores con espacios para mostrar (opcional)
        // $displaySegment = str_replace(['-', '_'], ' ', $displaySegment);

        // Clase 'active' para el último elemento
        $activeClass = ($i === $numSegments - 1) ? ' active' : '';
        $html .= '<li class="breadcrumb-item' . $activeClass . '">';

        if ($i === $numSegments - 1) {
            // Último segmento: solo texto
            $html .= '<span class="ml-1">' . htmlspecialchars($displaySegment) . '</span>';
        } else {
            // Segmento intermedio: enlace
            // Usar getFullUrl con la ruta acumulativa *sin decodificar* para la URL
             $segmentUrl = getFullUrl($currentSegmentPath);
            $html .= '<a href="' . htmlspecialchars($segmentUrl) . '" class="breadcrumb-link ml-1">' . htmlspecialchars($displaySegment) . '</a>';
        }
        $html .= '</li>';
        // Actualizar path para el siguiente nivel (usar segmento original)
        $path = $currentSegmentPath;
    }

    $html .= '</ol>';
    return $html;
}
/**
 * Encuentra el archivo Markdown (.md) más reciente dentro de un directorio y sus subdirectorios.
 *
 * @param string $dir La ruta al directorio a escanear.
 * @return string|null La ruta completa al archivo .md más reciente, o null si no se encuentran archivos .md.
 */
function findMostRecentMarkdownFile($dir) {
    $latestTime = 0;
    $latestFile = null;

    try {
        // Crear un iterador recursivo para todos los archivos y directorios
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $fileInfo) {
            // Verificar si es un archivo y tiene extensión .md
            if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'md') {
                // Obtener la fecha de modificación
                $mtime = $fileInfo->getMTime();
                // Si es más reciente que el último encontrado, actualizar
                if ($mtime > $latestTime) {
                    $latestTime = $mtime;
                    $latestFile = $fileInfo->getRealPath(); // Obtener la ruta completa real
                }
            }
        }
    } catch (Exception $e) {
        // Manejar posibles errores (ej: directorio no legible)
        error_log("Error al escanear directorio para archivo reciente: " . $e->getMessage());
        return null;
    }

    return $latestFile;
}

/**
 * Extrae metadatos del contenido HTML/Markdown para redes sociales
 * 
 * @param string $content Contenido HTML renderizado
 * @param string $title Título de la página
 * @return array Array con metadatos para Open Graph
 */
function extractContentMetadata($content, $title = '') {
    $metadata = [
        'title' => $title,
        'description' => '',
        'image' => '',
        'type' => 'article'
    ];
    
    // Extraer descripción del primer párrafo
    if (preg_match('/<p[^>]*>(.*?)<\/p>/is', $content, $matches)) {
        $description = strip_tags($matches[1]);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description);
        
        // Limitar a 155 caracteres para SEO
        if (strlen($description) > 155) {
            $description = substr($description, 0, 152) . '...';
        }
        
        $metadata['description'] = $description;
    }
    
    // Extraer primera imagen
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches)) {
        $imageSrc = $matches[1];
        
        // Si la imagen es relativa, convertirla a URL completa
        if (!filter_var($imageSrc, FILTER_VALIDATE_URL)) {
            // Si comienza con /, es relativa al dominio
            if (strpos($imageSrc, '/') === 0) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https:" : "http:";
                $host = $_SERVER['HTTP_HOST'];
                $metadata['image'] = $protocol . '//' . $host . $imageSrc;
            } else {
                // Es relativa al path actual
                $metadata['image'] = getFullUrl($imageSrc);
            }
        } else {
            $metadata['image'] = $imageSrc;
        }
    }
    
    return $metadata;
}

/**
 * Genera meta tags Open Graph para redes sociales
 * 
 * @param array $metadata Array con metadatos
 * @param string $url URL actual de la página
 * @param string $siteName Nombre del sitio
 * @return string HTML con meta tags
 */
function generateOpenGraphTags($metadata, $url, $siteName = '') {
    global $config;
    
    $siteName = $siteName ?: ($config['app_title'] ?? 'Lector de Markdown');
    $author = $config['autor_title'] ?? '';
    
    $html = "\n    <!-- Open Graph Meta Tags -->\n";
    $html .= '    <meta property="og:type" content="' . htmlspecialchars($metadata['type']) . '">' . "\n";
    $html .= '    <meta property="og:site_name" content="' . htmlspecialchars($siteName) . '">' . "\n";
    $html .= '    <meta property="og:title" content="' . htmlspecialchars($metadata['title']) . '">' . "\n";
    
    if (!empty($metadata['description'])) {
        $html .= '    <meta property="og:description" content="' . htmlspecialchars($metadata['description']) . '">' . "\n";
        $html .= '    <meta name="description" content="' . htmlspecialchars($metadata['description']) . '">' . "\n";
    }
    
    if (!empty($metadata['image'])) {
        $html .= '    <meta property="og:image" content="' . htmlspecialchars($metadata['image']) . '">' . "\n";
        
        // Determinar el tipo MIME basado en la extensión del archivo
        $imageExtension = strtolower(pathinfo($metadata['image'], PATHINFO_EXTENSION));
        $mimeType = 'image/jpeg'; // Por defecto
        
        switch ($imageExtension) {
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'webp':
                $mimeType = 'image/webp';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            case 'jpg':
            case 'jpeg':
                $mimeType = 'image/jpeg';
                break;
            case 'svg':
                $mimeType = 'image/svg+xml';
                break;
        }
        
        $html .= '    <meta property="og:image:type" content="' . $mimeType . '">' . "\n";
        $html .= '    <meta property="og:image:width" content="1200">' . "\n";
        $html .= '    <meta property="og:image:height" content="630">' . "\n";
    }
    
    $html .= '    <meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
    
    // Twitter Card
    $html .= "\n    <!-- Twitter Card Meta Tags -->\n";
    $html .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '    <meta name="twitter:title" content="' . htmlspecialchars($metadata['title']) . '">' . "\n";
    
    if (!empty($metadata['description'])) {
        $html .= '    <meta name="twitter:description" content="' . htmlspecialchars($metadata['description']) . '">' . "\n";
    }
    
    if (!empty($metadata['image'])) {
        $html .= '    <meta name="twitter:image" content="' . htmlspecialchars($metadata['image']) . '">' . "\n";
    }
    
    if (!empty($author)) {
        $html .= '    <meta name="twitter:creator" content="@' . htmlspecialchars($author) . '">' . "\n";
    }
    
    // WhatsApp específicos (usa Open Graph)
    $html .= "\n    <!-- WhatsApp Meta Tags -->\n";
    $html .= '    <meta property="og:locale" content="es_ES">' . "\n";
    
    return $html;
}
/**
 * Extrae la primera imagen y un extracto de texto de un contenido Markdown
 */
function getFilePreview($filePath) {
    if (!file_exists($filePath)) return null;
    
    require_once __DIR__ . '/markdown.php';
    $content = file_get_contents($filePath);
    $parser = new MarkdownParser();
    
    // Parseamos para separar el Front Matter del cuerpo
    $html = $parser->parse($content);
    $meta = $parser->getMetadata();
    
    // Buscamos la imagen (Prioridad Front Matter > Contenido)
    $image = $meta['image'] ?? null;
    if (!$image && preg_match('/\!\[.*\]\((.*)\)/', $content, $matches)) {
        $image = $matches[1];
    }

    // Limpiamos el HTML para el extracto (sin etiquetas ni Front Matter)
    $cleanText = strip_tags($html);
    $cleanText = trim(preg_replace('/\s+/', ' ', $cleanText));
    $words = explode(' ', $cleanText);
    $excerpt = implode(' ', array_slice($words, 0, 22)) . '...';

    return [
        'title' => $meta['title'] ?? pathinfo($filePath, PATHINFO_FILENAME),
        'image' => $image,
        'excerpt' => $excerpt,
        'tags' => $meta['tags'] ?? [],
        'date' => $meta['date'] ?? null
    ];
}
/**
 * Genera meta tags para Open Graph (WhatsApp, Telegram, RRSS)
 */
function generateSocialMetaTags($meta, $currentUrl) {
    global $config;
    $appTitle = $config['app_title'] ?? 'Cotidianeidades';
    
    $title = htmlspecialchars($meta['title'] ?? $appTitle);
    $description = htmlspecialchars($meta['description'] ?? 'Notas y reflexiones de Ricardo Loría');
    $image = !empty($meta['image']) ? $meta['image'] : 'https://tu-dominio.com/assets/img/og-default.jpg';
    
    $html = "\n    \n";
    $html .= '    <meta property="og:type" content="article">' . "\n";
    $html .= '    <meta property="og:url" content="' . $currentUrl . '">' . "\n";
    $html .= '    <meta property="og:title" content="' . $title . '">' . "\n";
    $html .= '    <meta property="og:description" content="' . $description . '">' . "\n";
    $html .= '    <meta property="og:image" content="' . $image . '">' . "\n";
    
    $html .= "\n    \n";
    $html .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '    <meta name="twitter:title" content="' . $title . '">' . "\n";
    $html .= '    <meta name="twitter:description" content="' . $description . '">' . "\n";
    $html .= '    <meta name="twitter:image" content="' . $image . '">' . "\n";
    
    return $html;
}

/**
 * Escanea el directorio de contenido y devuelve un array de archivos .md
 */
function getMarkdownFiles($dir) {
    $files = [];
    if (!is_dir($dir)) return $files;

    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            $files = array_merge($files, getMarkdownFiles($path));
        } else if (pathinfo($path, PATHINFO_EXTENSION) == 'md') {
            $files[] = $path;
        }
    }
    return $files;
}

/**
 * Parsea el Front Matter de un archivo Markdown
 */
function parseMarkdownFrontMatter($filePath) {
    if (!file_exists($filePath)) return ['title' => basename($filePath, '.md'), 'content' => ''];

    $content = file_get_contents($filePath);
    
    // Expresión regular mejorada: 
    // - Permite espacios/saltos de línea al inicio (\s*)
    // - Maneja variaciones de saltos de línea (\r?\n)
    // - Hace que el cuerpo sea opcional
    $pattern = '/^\s*---\s*\r?\n(.*?)\r?\n---\s*(?:\r?\n(.*))?$/s';

    if (preg_match($pattern, $content, $matches)) {
        $frontMatter = $matches[1];
        $body = isset($matches[2]) ? $matches[2] : '';

        $metadata = [];
        $lines = explode("\n", $frontMatter);
        foreach ($lines as $line) {
            $parts = explode(':', $line, 2);
            if (count($parts) == 2) {
                $key = strtolower(trim($parts[0]));
                $value = trim($parts[1]);
                // Eliminar comillas dobles, simples y espacios extras
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $metadata[$key] = $value;
            }
        }
        
        // Asegurar que existan las claves básicas
        $result = $metadata;
        if (!isset($result['title'])) $result['title'] = basename($filePath, '.md');
        $result['content'] = $body;
        
        return $result;
    }

    return [
        'title' => basename($filePath, '.md'),
        'content' => $content
    ];
}
?>