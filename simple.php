<?php
// Archivo de prueba simplificado para el lector de Markdown
// Este archivo no depende de mod_rewrite ni de características avanzadas

// Activar la visualización de errores para diagnóstico
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir rutas básicas
$basePath = __DIR__;
$contentPath = $basePath . '/content';

// Función para escanear directorios recursivamente
function scanDirectorySimple($dir) {
    $result = [];
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . '/' . $file;
        
        if (is_dir($path)) {
            $result[$file] = [
                'type' => 'directory',
                'children' => scanDirectorySimple($path)
            ];
        } else if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
            $result[$file] = [
                'type' => 'file'
            ];
        }
    }
    
    return $result;
}

// Función para renderizar la estructura de directorios
function renderDirectoryStructureSimple($structure, $basePath = '') {
    $html = '<ul class="ml-4 space-y-2">';
    
    foreach ($structure as $name => $item) {
        $path = $basePath . '/' . $name;
        $path = ltrim($path, '/');
        
        if ($item['type'] === 'directory') {
            $html .= '<li>';
            $html .= '<div class="flex items-center font-bold">';
            $html .= '<svg class="w-5 h-5 text-yellow-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>';
            $html .= '</svg>';
            $html .= $name;
            $html .= '</div>';
            
            if (!empty($item['children'])) {
                $html .= renderDirectoryStructureSimple($item['children'], $path);
            }
            
            $html .= '</li>';
        } else {
            $html .= '<li>';
            $html .= '<a href="simple.php?file=' . urlencode($basePath . '/' . $name) . '" class="flex items-center hover:text-blue-600">';
            $html .= '<svg class="w-4 h-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>';
            $html .= '</svg>';
            $html .= pathinfo($name, PATHINFO_FILENAME);
            $html .= '</a>';
            $html .= '</li>';
        }
    }
    
    $html .= '</ul>';
    return $html;
}

// Función simple para convertir Markdown a HTML
function parseMarkdownSimple($markdown) {
    // Procesar encabezados
    $markdown = preg_replace('/^#\s+(.+)$/m', '<h1>$1</h1>', $markdown);
    $markdown = preg_replace('/^##\s+(.+)$/m', '<h2>$1</h2>', $markdown);
    $markdown = preg_replace('/^###\s+(.+)$/m', '<h3>$1</h3>', $markdown);
    
    // Procesar párrafos
    $markdown = preg_replace('/^(?!<h[1-6]|<ul|<ol|<li|<blockquote|<pre)(.+)$/m', '<p>$1</p>', $markdown);
    
    // Procesar negrita
    $markdown = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $markdown);
    
    // Procesar cursiva
    $markdown = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $markdown);
    
    // Procesar enlaces
    $markdown = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
        return '<a href="' . $matches[2] . '">' . $matches[1] . '</a>';
    }, $markdown);
    
    // Procesar imágenes
    $markdown = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function($matches) {
        return '<img src="' . $matches[2] . '" alt="' . $matches[1] . '" class="max-w-full h-auto my-4">';
    }, $markdown);
    
    return $markdown;
}

// Obtener la estructura de directorios
$structure = scanDirectorySimple($contentPath);

// Determinar qué archivo mostrar
$fileToShow = isset($_GET['file']) ? $_GET['file'] : null;
$content = '';
$title = 'Lector de Markdown Simple';

if ($fileToShow && file_exists($contentPath . '/' . $fileToShow)) {
    $markdown = file_get_contents($contentPath . '/' . $fileToShow);
    $content = parseMarkdownSimple($markdown);
    $title = pathinfo($fileToShow, PATHINFO_FILENAME);
} else {
    $content = '<h1>Bienvenido al Lector de Markdown Simple</h1>';
    $content .= '<p>Selecciona un archivo del menú lateral para comenzar.</p>';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Lector MD Simple</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .content h1 { font-size: 2.2rem; font-weight: 700; margin-top: 1.8em; margin-bottom: 0.8em; }
        .content h2 { font-size: 1.5rem; font-weight: 600; margin-top: 1.8em; margin-bottom: 0.8em; }
        .content h3 { font-size: 1.25rem; font-weight: 500; margin-top: 1.8em; margin-bottom: 0.8em; }
        .content p { margin-bottom: 1.25em; }
        .content img { max-width: 90%; margin: 2em auto; display: block; border-radius: 0.5rem; }
        .content a { color: #2563eb; text-decoration: underline; }
        .sidebar-hidden { transform: translateX(-100%); }
        @media (min-width: 768px) {
            .sidebar-hidden { transform: translateX(0); }
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 bg-white border-r border-gray-200 fixed inset-y-0 left-0 z-20 transform transition-transform duration-300 md:translate-x-0 md:static md:inset-auto">
            <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                <h1 class="text-xl font-semibold text-gray-800">Lector MD</h1>
                <button id="menuToggle" class="md:hidden p-2 rounded-md hover:bg-gray-100">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-4">
                <h2 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Archivos</h2>
                <?php echo renderDirectoryStructureSimple($structure); ?>
            </div>
        </aside>

        <!-- Overlay para móviles -->
        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-10 hidden md:hidden"></div>

        <!-- Contenido principal -->
        <main class="flex-1 p-6 md:p-8 overflow-auto">
            <div class="max-w-4xl mx-auto">
                <!-- Botón de menú para móviles -->
                <div class="flex items-center justify-between mb-6 md:hidden">
                    <button id="mobileMenuToggle" class="p-2 rounded-md hover:bg-gray-100">
                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido del archivo Markdown -->
                <div class="bg-white shadow-sm rounded-lg p-6 md:p-8 content">
                    <?php echo $content; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Funcionalidad para el menú hamburguesa
        const menuToggle = document.getElementById('menuToggle');
        const mobileMenuToggle = document.getElementById('mobileMenuToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('sidebar-hidden');
            overlay.classList.toggle('hidden');
        }
        
        if (menuToggle && mobileMenuToggle && sidebar && overlay) {
            menuToggle.addEventListener('click', toggleSidebar);
            mobileMenuToggle.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        }
    </script>
</body>
</html>
