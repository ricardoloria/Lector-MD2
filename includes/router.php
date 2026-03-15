<?php
/**
 * Sistema de enrutamiento para el lector de Markdown
 * se encuentra en includes/router.php
 */

/**
 * Analiza la URL actual y determina la ruta del archivo a mostrar
 */
function parseUrl() {
    global $config;
    $contentPath = getContentPath();
    $requestUri = $_SERVER['REQUEST_URI'];

    // Eliminar parámetros de consulta
    $requestUri = strtok($requestUri, '?');

    // Manejar base_url
    $baseUrl = $config['base_url'] ?? '/';
    $basePath = rtrim(parse_url($baseUrl, PHP_URL_PATH), '/');

    if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    $requestUri = trim($requestUri, '/');

    // Home
    if (empty($requestUri)) {
        return [
            'type' => 'home',
            'path' => $contentPath,
            'segments' => []
        ];
    }

    $segments = explode('/', $requestUri);
    $filePath = $contentPath;
    $fileFound = false;
    $validSegments = [];

    foreach ($segments as $segment) {
        if (empty($segment)) continue;
        $nextPath = $filePath . '/' . rawurldecode($segment);

        if (is_dir($nextPath)) {
            $filePath = $nextPath;
            $validSegments[] = rawurldecode($segment);
            continue;
        }
        if (file_exists($nextPath . '.md')) {
            $filePath = $nextPath . '.md';
            $validSegments[] = rawurldecode($segment);
            $fileFound = true;
            break;
        }
        return ['type' => '404', 'path' => null, 'segments' => $validSegments];
    }

    if ($fileFound) {
        return ['type' => 'file', 'path' => $filePath, 'segments' => $validSegments];
    }
    if (is_dir($filePath)) {
        return ['type' => 'directory', 'path' => $filePath, 'segments' => $validSegments];
    }
    return ['type' => '404', 'path' => null, 'segments' => $validSegments];
}

/**
 * Renderiza la página según la ruta
 */
function renderPage($route) {
    switch ($route['type']) {
        case 'home':
            renderHome();
            break;
        case 'file':
            renderFile($route['path'], $route['segments']);
            break;
        case 'directory':
            renderDirectory($route['path'], $route['segments']);
            break;
        default:
            render404();
            break;
    }
}

/**
 * Renderiza la página de inicio (Landing de Tarjetas)
 */
function renderHome() {
    global $config;
    $contentPath = getContentPath();
    $allFiles = [];
    
    $directory = new RecursiveDirectoryIterator($contentPath);
    $iterator = new RecursiveIteratorIterator($directory);
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'md' && $file->getFilename() !== 'index.md') {
            $preview = getFilePreview($file->getPathname());
            $allFiles[] = [
                'path' => $file->getPathname(),
                // Ordenar por la fecha del Front Matter (si existe)
                'date_sort' => isset($preview['date']) ? strtotime($preview['date']) : $file->getMTime(),
                'preview' => $preview
            ];
        }
    }

    // Ordenar: Más reciente primero
    usort($allFiles, function($a, $b) {
        return $b['date_sort'] - $a['date_sort'];
    });

    $recentFiles = array_slice($allFiles, 0, 12);
    $title = ($config['app_title'] ?? 'Cotidianeidades') . ' - Inicio';
    
    include getBasePath() . '/includes/home_landing.php';
}

/**
 * Renderiza un archivo Markdown con soporte para Front Matter
 */
function renderFile($filePath, $segments) {
    global $config;
    require_once getBasePath() . '/includes/markdown.php';

    $markdown = file_get_contents($filePath);
    $parser = new MarkdownParser();
    
    // El método parse ahora extrae el Front Matter internamente
    $html = $parser->parse($markdown);
    $meta = $parser->getMetadata();

    // 1. Título: Prioridad Front Matter > H1 Markdown > Nombre Archivo
    $displayTitle = $meta['title'] ?? $parser->getTitle($markdown);
    if ($displayTitle === 'Sin título') {
        $displayTitle = str_replace(['-', '_'], ' ', pathinfo($filePath, PATHINFO_FILENAME));
    }

    // En router.php (dentro de renderFile)
$displayDate = isset($meta['date']) ? date('j M Y', strtotime($meta['date'])) : getFileDate($filePath);

// Pasar explícitamente al layout
$dateHtml = $displayDate;
    // 3. Tags
    $tags = $meta['tags'] ?? [];

    // 4. Metadatos de lectura
    $readingTime = $parser->getReadingTime($html);
    $readingTimeHtml = $readingTime . ' min read';
    $dateHtml = $displayDate;
    
    // 5. Navegación (Breadcrumbs y Flechas superiores)
    $breadcrumbs = generateBreadcrumbs($segments);
    $currentDir = dirname($filePath);
    $currentFileBase = pathinfo($filePath, PATHINFO_FILENAME);
    $adjacentFiles = getAdjacentFiles($currentDir, $currentFileBase);
    $relativeDirPath = getRelativePath($currentDir);

    // Enlaces de flechas superiores
    $prevLink = ''; $nextLink = '';
    if ($adjacentFiles['prev']) {
        $prevUrl = getFullUrl($relativeDirPath ? $relativeDirPath . '/' . $adjacentFiles['prev'] : $adjacentFiles['prev']);
        $prevLink = '<a href="' . htmlspecialchars($prevUrl) . '" class="text-gray-400 hover:text-green-600 p-1" title="Anterior"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg></a>';
    }
    if ($adjacentFiles['next']) {
        $nextUrl = getFullUrl($relativeDirPath ? $relativeDirPath . '/' . $adjacentFiles['next'] : $adjacentFiles['next']);
        $nextLink = '<a href="' . htmlspecialchars($nextUrl) . '" class="text-gray-400 hover:text-green-600 p-1" title="Siguiente"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>';
    }

    // 6. Preparar datos para navegación inferior (Footer editorial)
    $prevFileInfo = null;
    $nextFileInfo = null;

    if ($adjacentFiles['prev']) {
        $prevFileInfo = [
            'name' => $adjacentFiles['prev'],
            'url' => getFullUrl($relativeDirPath ? $relativeDirPath . '/' . $adjacentFiles['prev'] : $adjacentFiles['prev'])
        ];
    }
    if ($adjacentFiles['next']) {
        $nextFileInfo = [
            'name' => $adjacentFiles['next'],
            'url' => getFullUrl($relativeDirPath ? $relativeDirPath . '/' . $adjacentFiles['next'] : $adjacentFiles['next'])
        ];
    }

    // Variables finales para layout.php
    $title = $displayTitle . ' - ' . ($config['app_title'] ?? 'Lector MD');
    $contentHtml = $html;
    $route = ['path' => $filePath, 'type' => 'file', 'segments' => $segments];

    include getBasePath() . '/includes/layout.php';

    // Dentro de renderFile en router.php
$socialMeta = [
    'title' => $displayTitle,
    'description' => $meta['description'] ?? substr(strip_tags($html), 0, 160),
    'image' => $meta['image'] ?? null // Si tienes 'image: url' en el Front Matter
];

$urlToShare = getCurrentShareUrl();
$openGraphTags = generateSocialMetaTags($socialMeta, $urlToShare);
}

/**
 * Renderiza una carpeta (Vista limpia para evitar repetición con sidebar)
 */
function renderDirectory($dirPath, $segments) {
    global $config;
    $dirName = basename($dirPath);
    $title = $dirName . ' - ' . ($config['app_title'] ?? 'Lector MD');
    
    // Contenido minimalista
    $content = '<div class="py-16 text-center">';
    $content .= '  <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-50 text-gray-400 rounded-full mb-6">';
    $content .= '    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>';
    $content .= '  </div>';
    $content .= '  <h1 class="text-3xl font-black text-gray-900 mb-3 uppercase tracking-tight">' . htmlspecialchars($dirName) . '</h1>';
    $content .= '  <p class="text-gray-500 max-w-sm mx-auto uppercase text-xs font-bold tracking-widest">Selecciona un documento en el menú lateral</p>';
    $content .= '</div>';

    $breadcrumbs = generateBreadcrumbs($segments);
    $prevLink = ''; $nextLink = ''; $readingTimeHtml = ''; $dateHtml = '';
    $route = ['path' => $dirPath, 'type' => 'directory', 'segments' => $segments];

    include getBasePath() . '/includes/layout.php';
}

/**
 * Renderiza la página 404
 */
function render404() {
    header('HTTP/1.0 404 Not Found');
    $content = '<div class="text-center py-20">';
    $content .= '  <h1 class="text-6xl font-black text-gray-200 mb-4">404</h1>';
    $content .= '  <p class="text-xl text-gray-600 mb-8 uppercase font-bold tracking-widest">Página no encontrada</p>';
    $content .= '  <a href="' . getFullUrl() . '" class="inline-block bg-black text-white text-xs font-bold py-3 px-8 rounded uppercase tracking-widest hover:bg-green-600 transition-colors">Volver al inicio</a>';
    $content .= '</div>';
    
    $title = '404 - No encontrado';
    $breadcrumbs = ''; $prevLink = ''; $nextLink = ''; $readingTimeHtml = ''; $dateHtml = '';
    $route = ['path' => null, 'type' => '404', 'segments' => []];
    
    include getBasePath() . '/includes/layout.php';
}