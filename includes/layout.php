<?php
// Asegúrate que las funciones necesarias estén disponibles
// (getBasePath, getFullUrl, etc.)
// y que $config esté disponible si se usa aquí directamente
// Ejemplo: $appTitle = $config['app_title'] ?? 'Lector MD';
$appTitle = $config['app_title'] ?? 'Lector de Markdown'; // Obtener título desde config
$autorTitle = $config['autor_title'] ?? 'Autor';
// Obtener variables pasadas desde el router (o definir defaults)
$pageTitle = $title ?? $appTitle; // Título específico de la página o el general
$breadcrumbsHtml = $breadcrumbs ?? ''; // HTML de breadcrumbs generado por la función
$prevLinkHtml = $prevLink ?? ''; // HTML del enlace Anterior
$nextLinkHtml = $nextLink ?? ''; // HTML del enlace Siguiente
$readingTimeHtml = isset($readingTime) ? $readingTime . ' min read' : ''; // Tiempo de lectura
$dateHtml = $dateHtml ?? $date ?? '';
$contentHtml = $html ?? $content ?? ''; // Contenido principal (HTML o Markdown parseado)
$currentPathForSidebar = $route['path'] ?? null; // Ruta actual para marcar activo en sidebar

// Función para URL actual (se puede mover a functions.php si se prefiere)
function getCurrentShareUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https:" : "http:";
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $host . $uri;
}
$urlToShare = getCurrentShareUrl();

// Generar metadatos para redes sociales
$socialMetadata = extractContentMetadata($contentHtml, $pageTitle);
$openGraphTags = generateOpenGraphTags($socialMetadata, $urlToShare);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php echo $openGraphTags; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&family=IBM+Plex+Serif:wght@400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo getFullUrl('assets/css/styles.css'); ?>">
    <link rel="stylesheet" href="<?php echo getFullUrl('assets/css/theme.css'); ?>">
    <style>
    /* Asegura transiciones suaves al cambiar el ancho */
    #sidebar {
        transition: width 0.3s ease-in-out;
    }
    /* Evita que el texto se desborde antes de ocultarse */
    #sidebar span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
    <script>
        // Configuración de Tailwind (incluye ambas fuentes)
        tailwind.config = {
             theme: {
                extend: {
                    fontFamily: {
                        sans: ['"IBM Plex Sans"', 'sans-serif'],
                        serif: ['"IBM Plex Serif"', 'serif'],
                        mono: ['Consolas', 'Monaco', "'Andale Mono'", "'Ubuntu Mono'", 'monospace']
                    },
                }
            }
        }
    </script>
    
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-8Q7TGNZ4CY"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'G-8Q7TGNZ4CY');
    </script>
</head>
<body class="bg-gray-100">

    <div id="mobileHeader" class="md:hidden fixed top-0 left-0 right-0 z-50 h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4">
         <button id="menuToggle" class="p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100 focus:outline-none">
            <svg class="hamburger w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
         </button>
         <div class="text-center">
             <h1 class="text-lg font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($appTitle); ?></h1>
         </div>
         <div class="w-10"></div> 
    </div>

    <div class="flex h-screen">
        
        <div id="sidebar" class="sidebar fixed md:relative z-40 w-64 h-full bg-gray-50 border-r border-gray-200 overflow-y-auto sidebar-hidden md:translate-x-0 transition-all duration-300">
            <div class="p-4 sticky top-0 bg-gray-50 z-10 border-b border-gray-200 flex items-center justify-between">
                <div id="sidebarHeaderTexts">
                    <a href="<?php echo getFullUrl(); ?>" class="block hover:text-gray-900" title="Ir al inicio">
                         <h1 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($appTitle); ?></h1>
                    </a>
                    <a href="#" class="block hover:text-gray-900" title="About">
                        <p class="text-gray-500 text-sm"><?php echo htmlspecialchars($autorTitle); ?></p>
                    </a>
                </div>
                <button id="toggleCollapse" class="hidden md:block p-1.5 rounded-lg hover:bg-gray-200 text-gray-500 focus:outline-none transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <?php 
            require_once getBasePath() . '/includes/sidebar.php';
            echo generateSidebar($currentPathForSidebar);
            ?>
        </div>

        <div id="overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden hidden"></div>

        <div class="flex-1 overflow-y-auto bg-white pt-16 md:pt-0">
    <div class="max-w-4xl mx-auto p-4 md:p-8">

        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-8 md:mb-12 gap-4">
            <nav aria-label="Breadcrumb">
                 <?php echo $breadcrumbsHtml; ?>
            </nav>
             <div class="flex space-x-2 self-end md:self-center">
                <?php echo $prevLinkHtml; ?>
                <?php echo $nextLinkHtml; ?>
            </div>
        </div>

        <h1 class="text-3xl md:text-3xl font-bold text-gray-600 mb-6">
            <?php echo htmlspecialchars($displayTitle); ?>
        </h1>

        <div class="flex flex-col md:flex-row md:items-center justify-between pb-8 mb-10 gap-6">
            <div class="flex flex-col gap-4">
                <div class="flex items-center text-base md:text-lg text-gray-600 font-semibold space-x-4">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <?php echo htmlspecialchars($readingTimeHtml); ?>
                    </span>
                    <span class="text-gray-300">•</span>
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="text-gray-800"><?php echo htmlspecialchars($dateHtml); ?></span>
                    </span>
                </div>

                <?php if (!empty($tags)): ?>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($tags as $tag): ?>
                    <span class="px-3 py-1 text-xs md:text-sm font-bold uppercase bg-green-600 text-white border border-gray-100 rounded-full">
                        <?php echo htmlspecialchars($tag); ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="relative self-start md:self-center">
                <button onclick="copyToClipboard('<?php echo htmlspecialchars($urlToShare); ?>')" class="flex items-center space-x-2 text-sm font-bold uppercase tracking-widest text-gray-500 hover:text-green-600 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Share</span>
                </button>
                <div id="copyAlert" class="hidden absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 whitespace-nowrap bg-black text-white px-4 py-2 rounded shadow-xl text-xs font-bold">
                     ENLACE COPIADO
                </div>
            </div>
        </div>

        <div class="content max-w-3xl mx-auto prose prose-lg md:prose-xl prose-img:rounded-2xl prose-headings:uppercase prose-headings:tracking-tighter">
            <?php echo $contentHtml; ?>







                     




        </div>
<div class="mt-24 pt-12 flex items-stretch justify-between mb-24">
            <div class="w-1/2 pr-6">
                <?php if (isset($prevFileInfo)): ?>
                    <span class="text-lg font-bold uppercase text-gray-400 block mb-4">Anterior</span>
                    <a href="<?php echo $prevFileInfo['url']; ?>" class="group block">
                        <h3 class="text-xl font-bold text-gray-800 group-hover:text-green-600 transition-colors">
                            <?php echo htmlspecialchars(str_replace(['-', '_'], ' ', $prevFileInfo['name'])); ?>
                        </h3>
                    </a>
                <?php endif; ?>
            </div>

            <div class="border-l-2 border-gray-100"></div>

            <div class="w-1/2 pl-6 text-right">
                <?php if (isset($nextFileInfo)): ?>
                    <span class="text-lg font-bold uppercase text-gray-400 block mb-4">Siguiente</span>
                    <a href="<?php echo $nextFileInfo['url']; ?>" class="group block">
                        <h3 class="text-xl font-bold text-gray-800 group-hover:text-green-600 transition-colors leading-none">
                            <?php echo htmlspecialchars(str_replace(['-', '_'], ' ', $nextFileInfo['name'])); ?>
                        </h3>
                    </a>
                <?php endif; ?>
            </div>
        </div>   
        

    </div>
</div>
    </div>

    <script src="<?php echo getFullUrl('assets/js/script.js'); ?>"></script>
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                const alert = document.getElementById('copyAlert');
                if(alert) {
                    alert.classList.remove('hidden');
                    setTimeout(function() {
                        alert.classList.add('hidden');
                    }, 2000);
                }
            });
        }
    </script>
</body>
</html>