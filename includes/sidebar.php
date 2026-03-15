<?php
/**
 * Genera el código HTML para el sidebar (menú lateral)
 * Se encuentra en includes/sidebar.php
 */

// Asegúrate de que las funciones necesarias (getContentPath, scanDirectory, etc.)
// estén disponibles (incluidas previamente, por ejemplo, desde functions.php)

/**
 * Función principal para generar el HTML completo del sidebar.
 *
 * @param string|null $currentPath La ruta física absoluta del archivo/directorio actual (para marcar como activo).
 * @return string HTML del sidebar.
 */
function generateSidebar($currentPath = null) {
    // Obtiene la ruta base donde están los archivos .md
    $contentPath = getContentPath();
    // Escanea el directorio de contenido para obtener la estructura
    $structure = scanDirectory($contentPath); // Asume que scanDirectory está definida

    // Inicia la etiqueta <nav> del sidebar
    // Puedes ajustar el padding general aquí si es necesario (p-2)
    $html = '<nav class="pl-4 pr-2 py-2">'; // <-- Padding general del contenido del nav

    // Llama a la función recursiva para renderizar la estructura
    $html .= renderDirectoryStructure($structure, $contentPath, $currentPath);

    $html .= '</nav>'; // Cierra la etiqueta <nav>
    return $html;
}

/**
 * Renderiza recursivamente la estructura de directorios y archivos para el sidebar.
 * (Versión con toggle de flecha separado de navegación de carpeta)
 *
 * @param array $structure La estructura de archivos/carpetas.
 * @param string $basePath La ruta física base del nivel actual.
 * @param string|null $currentPath La ruta física del elemento activo actual.
 * @param int $level Nivel de anidamiento.
 * @return string HTML de la estructura de este nivel.
 */
/**
 * Renderiza recursivamente la estructura de directorios y archivos para el sidebar.
 * (Versión con carpetas NO clickeables, solo despliegan/pliegan)
 * (Versión con estado de expansión guardado en localStorage)
 */
function renderDirectoryStructure($structure, $basePath, $currentPath, $level = 0) {
    $html = '';

    // Ordenar (directorios primero, luego orden natural por nombre)
    uksort($structure, function($nameA, $nameB) use ($structure) {
        $itemA = $structure[$nameA];
        $itemB = $structure[$nameB];
        
        // Directorios siempre van primero
        if ($itemA['type'] === 'directory' && $itemB['type'] !== 'directory') return -1;
        if ($itemA['type'] !== 'directory' && $itemB['type'] === 'directory') return 1;
        
        // Ordenamiento natural por nombre (abril, mayo, junio funcionará correctamente)
        return strnatcasecmp($nameA, $nameB);
    });

    foreach ($structure as $name => $item) {
        $path = $basePath . '/' . $name;

        // --- Directorio ---
        if ($item['type'] === 'directory') {
            // Determinar estado inicial por PHP (si la ruta activa está dentro)
             $isInitiallyExpanded = false;
             if($currentPath && realpath($path) && realpath($currentPath)){
                $isInitiallyExpanded = strpos(realpath($currentPath), realpath($path)) === 0;
             }

            // --- ID ÚNICO Y PERSISTENTE para la carpeta ---
            $relativeDirPath = getRelativePath($path, true); // Obtener ruta relativa (con extensión si la tuviera, aunque aquí no aplica)
            // Sanitizar la ruta para usarla como ID (reemplazar / con - y otros caracteres no válidos)
            $folderId = 'folder-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $relativeDirPath);
            $subfolderId = 'subfolder-' . $folderId;
            // --- Fin ID ---

            // Contenedor principal (ajusta mb-1)
            $html .= '<div class="mb-1 relative">';

            // DIV clickeable para desplegar/plegar
            // - Añadimos el ID de la carpeta como un atributo 'data-folder-id'
            // - 'folder-toggle-trigger' para JS
            $html .= '<div class="flex items-center justify-between p-1 hover:bg-gray-100 rounded cursor-pointer folder-toggle-trigger" data-target="#' . $subfolderId . '" data-folder-id="' . htmlspecialchars($folderId) . '">'; // <-- data-folder-id AÑADIDO

            // Contenedor para flecha y nombre (ajusta space-x-1.5)
            $html .= '<div class="flex items-center space-x-1.5">';
            // Flecha (añadimos 'folder-arrow' para poder encontrarla fácilmente)
            $html .= '<svg class="folder-arrow w-4 h-4 text-gray-500 transition-transform duration-200 ' . ($isInitiallyExpanded ? 'rotated' : '') . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>'; // <-- folder-arrow AÑADIDO
            // Nombre de la carpeta (ajusta clases de texto)
            $html .= '<span class="text-gray-700 font-medium text-sm select-none">' . htmlspecialchars($name) . '</span>';
            $html .= '</div>';

            $html .= '</div>'; // Cierre del DIV clickeable

            // Contenedor de subelementos (con ID único)
            // - Clase 'show' se añadirá/quitará por JS basado en estado inicial y localStorage
            // - Ajusta ml-4 para indentación
            $html .= '<div id="' . $subfolderId . '" class="subfolder ml-4 ' . ($isInitiallyExpanded ? 'show' : '') . '">'; // <-- ID, ajusta ml-4
            $html .= renderDirectoryStructure($item['children'], $path, $currentPath, $level + 1);
            $html .= '</div>'; // Cierre subfolder

            $html .= '</div>'; // Cierre contenedor principal carpeta

        // --- Archivo ---
        } else {
            $url = generateFileUrl($path);
            $isActive = isFileActive($path, $currentPath);
            // Debug: mostrar información en comentarios HTML para debugging
            $debugInfo = "<!-- Debug: File=$path, Current=$currentPath, Active=" . ($isActive ? 'YES' : 'NO') . " -->";
            if ($isActive) {
                $debugInfo .= "<!-- ARCHIVO ACTIVO DETECTADO: $path -->";
            }
            $activeBgClass = $isActive ? 'bg-blue-50' : '';
            $activeTextClass = $isActive ? 'text-blue-700 font-semibold' : 'text-gray-600';
            $activeFileClass = $isActive ? 'active-file' : 'sidebar-file'; // Solo archivos activos tienen la clase active-file
            $dotBaseClass = 'h-2 w-2 rounded-full transition-colors duration-150 mr-2 flex-shrink-0';
            $dotActiveClass = $isActive ? 'bg-green-500' : 'bg-transparent';
            $dotHoverClass = 'group-hover:bg-orange-400';

            // Wrapper indentado (ajusta ml-4 y mb-0.5)
            $html .= '<div class="ml-4 mb-0.5">';
            $html .= $debugInfo . "\n"; // Agregar debug info
            // Enlace del archivo (ajusta py-1 px-2)
            $html .= '<a href="' . htmlspecialchars($url) . '" class="flex items-center py-1 px-2 hover:bg-gray-100 rounded group ' . $activeBgClass . ' ' . $activeTextClass . ' ' . $activeFileClass . '">'; // Solo active-file si está activo
            // Punto indicador (ajusta mr-2)
            $html .= '<span class="' . $dotBaseClass . ' ' . $dotActiveClass . ' ' . $dotHoverClass . ' sidebar-dot"></span>';
            // Nombre archivo (ajusta text-sm)
            $html .= '<span class="text-sm truncate">' . htmlspecialchars(pathinfo($name, PATHINFO_FILENAME)) . '</span>';
            $html .= '</a>'; // Cierre enlace archivo
            $html .= '</div>'; // Cierre wrapper archivo
        }
    }
    return $html;
}



?>