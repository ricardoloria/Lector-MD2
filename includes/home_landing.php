<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;700&display=swap');
        body { font-family: 'IBM Plex Sans', sans-serif; }
    </style>
</head>
<body class="bg-white text-gray-900 flex flex-col min-h-screen">

    <header class="max-w-7xl mx-auto px-8 md:px-16 pt-12 pb-8 flex flex-col items-start w-full">
        <h1 class="text-2xl md:text-2xl font-bold text-gray-600 leading-none">
            <?php echo $config['app_title']; ?>
        </h1>
        <p class="text-xl md:text-xl font-bold  text-gray-500 hover:text-green-600">
            <a href="https://ricardoloria.com/" class="" >Ricardo Loría</a> 
        </p>
    </header>

    <main class="max-w-7xl mx-auto px-8 md:px-16 pb-24 flex-grow w-full">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10">
        
        <?php foreach ($recentFiles as $fileData): 
            $preview = $fileData['preview']; // Datos ya procesados en router.php
            $url = generateFileUrl($fileData['path']);
        ?>
        <article class="relative bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 flex flex-col h-auto md:h-[480px] transition-all duration-500 hover:shadow-2xl hover:-translate-y-2 group">
            
            <a href="<?php echo $url; ?>" class="absolute inset-0 z-10" aria-label="Leer más"></a>

            <?php if (!empty($preview['image'])): ?>
                <div class="h-[240px] overflow-hidden bg-gray-100">
                    <img src="<?php echo $preview['image']; ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                </div>
            <?php endif; ?>

            <div class="p-6 flex-grow flex flex-col">
                <div class="flex-grow">
                    <h2 class="text-xl font-bold leading-tight uppercase group-hover:text-green-600 transition-colors">
                        <?php echo htmlspecialchars($preview['title']); ?>
                    </h2>
                    
                    <?php if (empty($preview['image'])): ?>
                        <p class="text-gray-600 text-bse mt-4 line-clamp-4">
                            <?php echo $preview['excerpt']; ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($preview['tags'])): ?>
                    <div class="flex flex-wrap gap-2 mt-4 relative z-20">
                        <?php foreach ($preview['tags'] as $tag): ?>
                        <span class="text-[12px] font-black tracking-widest uppercase px-2 py-1 bg-green-600 text-white border border-gray-100 rounded-full">
                            <?php echo htmlspecialchars($tag); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-between items-center pt-6 mt-4 border-t border-gray-100 relative z-20">
                    <span class="text-base md:text-xm font-bold uppercase text-gray-900 group-hover:text-green-600">
                        Leer más <i class="fa-solid fa-arrow-right ml-1"></i>
                    </span>
                    <div class="w-9 h-9 rounded-full bg-gray-100 overflow-hidden ring-2 ring-white shadow-sm">
                        <img src="https://ricardoloria.com/images/profile1.png" alt="Ricardo">
                    </div>
                </div>
            </div>
        </article>
        <?php endforeach; ?>

    </div>
</main>

    <footer class="max-w-7xl mx-auto px-8 md:px-16 py-8 w-full border-t border-gray-100">
        <p class="text-[16px] font-bold text-gray-400">
            Ricardo Loría © <?php echo date('Y'); ?>
        </p>
    </footer>

</body>
</html>