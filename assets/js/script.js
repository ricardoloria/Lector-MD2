// assets/js/script.js
// (Versión con estado de expansión guardado en localStorage)

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM cargado, inicializando scripts...');

    const sidebar = document.getElementById('sidebar'); // Referencia al sidebar

    // --- CLAVE DE LOCALSTORAGE ---
    const FOLDER_STATE_PREFIX = 'folderState-';

    // --- APLICAR ESTADO GUARDADO AL CARGAR ---
    function applyStoredState() {
        document.querySelectorAll('#sidebar .folder-toggle-trigger').forEach(triggerDiv => {
            const folderId = triggerDiv.getAttribute('data-folder-id');
            const targetSelector = triggerDiv.getAttribute('data-target');
            const arrow = triggerDiv.querySelector('.folder-arrow');
            const subfolder = targetSelector ? document.querySelector(targetSelector) : null;

            if (folderId && subfolder && arrow) {
                const storedState = localStorage.getItem(FOLDER_STATE_PREFIX + folderId);

                if (storedState === 'true') { // Explícitamente abierto
                    subfolder.classList.add('show');
                    arrow.classList.add('rotated');
                    // Opcional: Animación
                    // subfolder.style.maxHeight = subfolder.scrollHeight + "px";
                } else if (storedState === 'false') { // Explícitamente cerrado
                    subfolder.classList.remove('show');
                    arrow.classList.remove('rotated');
                    // Opcional: Animación
                    // subfolder.style.maxHeight = '0px';
                }
                // Si storedState es null (no guardado), se respeta el estado inicial del HTML (puesto por PHP)
            }
        });
    }

    // --- GUARDAR ESTADO AL HACER CLIC EN CARPETA ---
    document.querySelectorAll('#sidebar .folder-toggle-trigger').forEach(triggerDiv => {
        triggerDiv.addEventListener('click', function(e) {
            e.preventDefault(); // Prevenir acción por defecto

            const folderId = this.getAttribute('data-folder-id');
            const targetSelector = this.getAttribute('data-target');
            const arrow = this.querySelector('.folder-arrow');
            const subfolder = targetSelector ? document.querySelector(targetSelector) : null;

            if (folderId && subfolder && arrow) {
                // Alternar clases visuales
                arrow.classList.toggle('rotated');
                const isNowOpen = subfolder.classList.toggle('show');

                // Guardar el NUEVO estado en localStorage
                localStorage.setItem(FOLDER_STATE_PREFIX + folderId, isNowOpen ? 'true' : 'false');

                // Opcional: Animación
                /*
                if (isNowOpen) {
                    subfolder.style.maxHeight = subfolder.scrollHeight + "px";
                } else {
                    subfolder.style.maxHeight = '0px';
                }
                */
            }
        });
    });

    // --- FUNCIONALIDAD MENÚ HAMBURGUESA (MÓVIL) ---
    const menuToggle = document.getElementById('menuToggle');
    const overlay = document.getElementById('overlay');
    if (menuToggle && sidebar && overlay) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('sidebar-hidden');
            overlay.classList.toggle('hidden');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.add('sidebar-hidden');
            overlay.classList.add('hidden');
        });
    }

    // --- CERRAR MENÚ MÓVIL AL HACER CLIC EN ENLACE DE ARCHIVO ---
    document.querySelectorAll('#sidebar a.sidebar-file, #sidebar a.active-file').forEach(link => { // Solo enlaces de archivos
        link.addEventListener('click', function() {
            if (window.innerWidth < 768 && sidebar && overlay) {
                sidebar.classList.add('sidebar-hidden');
                overlay.classList.add('hidden');
            }
        });
    });

 // --- OBTENER CARPETAS ANCESTROS DEL ARCHIVO ACTIVO ---
    function getActiveFileAncestorFolders() {
        const activeFileLink = document.querySelector('#sidebar a.active-file');
        const ancestorFolders = new Set();
        
        if (activeFileLink && sidebar) {
            console.log('Archivo activo encontrado:', activeFileLink.textContent.trim());
            let element = activeFileLink.closest('.subfolder');

            while (element) {
                const container = element.parentElement;
                const triggerDiv = container ? container.querySelector('.folder-toggle-trigger') : null;
                const folderId = triggerDiv ? triggerDiv.getAttribute('data-folder-id') : null;
                
                if (folderId) {
ancestorFolders.add(folderId);
                    console.log('Carpeta ancestro detectada:', folderId);                }
                
                element = container ? container.closest('.subfolder') : null;
            }
            } else {
            console.log('No se encontró archivo activo');
        }
        
        return ancestorFolders;
    }

    // --- COLAPSAR CARPETAS NO RELACIONADAS CON EL ARCHIVO ACTIVO ---
    function collapseNonActiveDirectories() {
        const activeAncestorFolders = getActiveFileAncestorFolders();
        
        // Solo colapsar si hay un archivo activo
        if (activeAncestorFolders.size === 0) {
            console.log('No hay archivo activo, no se colapsa nada');
            return;
        }
        
        // Colapsar todas las carpetas que NO son ancestros del archivo activo
        document.querySelectorAll('#sidebar .folder-toggle-trigger').forEach(triggerDiv => {
            const folderId = triggerDiv.getAttribute('data-folder-id');
            const targetSelector = triggerDiv.getAttribute('data-target');
            const arrow = triggerDiv.querySelector('.folder-arrow');
            const subfolder = targetSelector ? document.querySelector(targetSelector) : null;
            
            if (folderId && subfolder && arrow) {
                // Si esta carpeta NO es ancestro del archivo activo, colapsarla
                if (!activeAncestorFolders.has(folderId)) {
                    subfolder.classList.remove('show');
                    arrow.classList.remove('rotated');
                    // No guardamos en localStorage aquí para respetar las preferencias del usuario
                    // Solo aplicamos el estado visual
                }
            }
        });
    }

// --- EXPANDIR PADRES DEL ACTIVO (MEJORADO) ---
    function expandActiveSidebarItem() {
        const activeFileLink = document.querySelector('#sidebar a.active-file');
         
        if (!activeFileLink) {
            console.log('No se encontró enlace de archivo activo');
            return;
        }
        
        console.log('Expandiendo carpetas para archivo activo:', activeFileLink.textContent.trim());
            let element = activeFileLink.closest('.subfolder');

            while (element) {
                const container = element.parentElement; // El div que contiene el trigger y el subfolder
                const triggerDiv = container ? container.querySelector('.folder-toggle-trigger') : null;
                const folderId = triggerDiv ? triggerDiv.getAttribute('data-folder-id') : null;
                const storedState = folderId ? localStorage.getItem(FOLDER_STATE_PREFIX + folderId) : null;

                console.log('Procesando carpeta:', folderId, 'Estado guardado:', storedState);

            // Expandir SIEMPRE las carpetas ancestros del archivo activo (ignorar localStorage para esto)
            if (!element.classList.contains('show')) {
                    console.log('Expandida carpeta:', folderId);


                    if (triggerDiv) {
                        const arrow = triggerDiv.querySelector('.folder-arrow');
                        if (arrow && !arrow.classList.contains('rotated')) {
                            arrow.classList.add('rotated');
                        }
                    }
                     
                }

                // Subir al siguiente padre
                 element = container ? container.closest('.subfolder') : null;
        }
    }

     // --- EJECUCIÓN INICIAL CON DELAYS PARA ASEGURAR CARGA COMPLETA ---
    console.log('Iniciando configuración del sidebar...');
    
    // 1º Aplicar estados guardados inmediatamente
    applyStoredState();
    
    // 2º Expandir archivo activo primero (sin delay)
    expandActiveSidebarItem();
    
    // 3º Colapsar carpetas no relacionadas después de un pequeño delay
    setTimeout(() => {
        collapseNonActiveDirectories();
        
        // 4º Verificar y expandir de nuevo el archivo activo por si fue colapsado incorrectamente
        setTimeout(() => {
            expandActiveSidebarItem();
            console.log('Configuración del sidebar completada');
        }, 50);
    }, 100);

    // ===========================================
    // FUNCIONALIDAD DE ZOOM PARA IMÁGENES
    // ===========================================
    
    // Crear modal para imagen ampliada
    function createImageModal() {
        const modal = document.createElement('div');
        modal.className = 'image-modal';
        modal.innerHTML = `
            <button class="modal-close" aria-label="Cerrar">&times;</button>
            <img class="modal-image" src="" alt="">
        `;
        document.body.appendChild(modal);
        return modal;
    }
    
    // Inicializar modal
    const imageModal = createImageModal();
    const modalImage = imageModal.querySelector('.modal-image');
    const closeButton = imageModal.querySelector('.modal-close');
    
    // Función para mostrar imagen en modal
    function showImageModal(imageSrc, imageAlt) {
        modalImage.src = imageSrc;
        modalImage.alt = imageAlt;
        imageModal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevenir scroll
    }
    
    // Función para cerrar modal
    function closeImageModal() {
        imageModal.classList.remove('active');
        document.body.style.overflow = ''; // Restaurar scroll
        
        // Limpiar src después de la animación para optimizar memoria
        setTimeout(() => {
            if (!imageModal.classList.contains('active')) {
                modalImage.src = '';
            }
        }, 300);
    }
    
    // Función para determinar si una imagen necesita zoom
    function imageNeedsZoom(img) {
        const container = img.closest('.md-image-container');
        if (!container) return false;
        
        return new Promise((resolve) => {
            // Si la imagen ya tiene dimensiones naturales, evaluarla inmediatamente
            if (img.naturalWidth > 0 && img.naturalHeight > 0) {
                const containerWidth = container.offsetWidth;
                const naturalWidth = img.naturalWidth;
                
                // Más estricto: la imagen debe ser significativamente más grande
                const needsZoom = naturalWidth > containerWidth * 1.5; // Cambiar de 1.2 a 1.5
                console.log(`Evaluando imagen: ${img.src}, Natural: ${naturalWidth}px, Container: ${containerWidth}px, Necesita zoom: ${needsZoom}`);
                resolve(needsZoom);
                return;
            }
            
            // Si no, crear imagen temporal
            const tempImg = new Image();
            tempImg.src = img.src;
            
            tempImg.onload = function() {
                const containerWidth = container.offsetWidth;
                const naturalWidth = this.naturalWidth;
                
                const needsZoom = naturalWidth > containerWidth * 1.5;
                console.log(`Evaluando imagen (temporal): ${img.src}, Natural: ${naturalWidth}px, Container: ${containerWidth}px, Necesita zoom: ${needsZoom}`);
                resolve(needsZoom);
            };
            
            tempImg.onerror = function() {
                console.log(`Error cargando imagen: ${img.src}`);
                resolve(false);
            };
        });
    }
    
    // Configurar eventos para todas las imágenes zoomables
    function setupImageZoom() {
        const zoomableImages = document.querySelectorAll('.zoomable-image');
        console.log('Configurando zoom para', zoomableImages.length, 'imágenes');
        
        zoomableImages.forEach(img => {
            const container = img.closest('.md-image-container');
            const overlay = container ? container.querySelector('.zoom-overlay') : null;
            
            if (!container || !overlay) return;
            
            // Asegurar que el overlay esté oculto inicialmente
            container.classList.remove('zoomable');
            overlay.style.display = 'none';
            
            // Función para evaluar zoom de una imagen
            const evaluateImageZoom = async (image) => {
                const needsZoom = await imageNeedsZoom(image);
                console.log('Imagen evaluada:', image.src, 'necesita zoom:', needsZoom);
                
                if (needsZoom) {
                    container.classList.add('zoomable');
                    overlay.style.display = 'flex';
                } else {
                    container.classList.remove('zoomable');
                    overlay.style.display = 'none';
                }
            };
            
            // Verificar si la imagen necesita zoom cuando se carga
            img.addEventListener('load', function() {
                evaluateImageZoom(this);
            });
            
            // Eventos de click para zoom
            const clickElements = [img, overlay];
            clickElements.forEach(element => {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Solo abrir modal si el contenedor tiene la clase 'zoomable'
                    if (container.classList.contains('zoomable')) {
                        const zoomUrl = img.getAttribute('data-zoom-url') || img.src;
                        showImageModal(zoomUrl, img.alt);
                    }
                });
            });
            
            // Si la imagen ya está cargada, verificar inmediatamente
            if (img.complete && img.naturalHeight !== 0) {
                console.log('Imagen ya cargada, evaluando inmediatamente:', img.src);
                evaluateImageZoom(img);
            }
        });
    }
    
    // Eventos del modal
    closeButton.addEventListener('click', closeImageModal);
    
    // Cerrar modal al hacer click en el fondo
    imageModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeImageModal();
        }
    });
    
    // Cerrar modal con la tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && imageModal.classList.contains('active')) {
            closeImageModal();
        }
    });
    
    // Cerrar modal al hacer click en la imagen ampliada
    modalImage.addEventListener('click', closeImageModal);
    
    // Inicializar zoom de imágenes
    setupImageZoom();
    
    // Re-inicializar cuando se carga contenido dinámico (si fuera necesario)
    const observer = new MutationObserver(function(mutations) {
        let shouldReinitialize = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1 && (node.classList.contains('zoomable-image') || node.querySelector('.zoomable-image'))) {
                        shouldReinitialize = true;
                    }
                });
            }
        });
        
        if (shouldReinitialize) {
            setupImageZoom();
        }
    });
    
    // Observar cambios en el contenido principal
    const mainContent = document.querySelector('.content, main, [role="main"]') || document.body;
    observer.observe(mainContent, {
        childList: true,
        subtree: true
    });
    
    // Re-evaluar imágenes al redimensionar ventana
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const zoomableImages = document.querySelectorAll('.zoomable-image');
            zoomableImages.forEach(async img => {
                const container = img.closest('.md-image-container');
                const overlay = container ? container.querySelector('.zoom-overlay') : null;
                
                if (container && overlay && img.complete) {
                    const needsZoom = await imageNeedsZoom(img);
                    
                    if (needsZoom) {
                        container.classList.add('zoomable');
                        overlay.style.display = 'flex';
                    } else {
                        container.classList.remove('zoomable');
                        overlay.style.display = 'none';
                    }
                }
            });
        }, 250); // Debounce de 250ms
    });

}); // Fin de DOMContentLoaded

// Funcionalidad para contraer/expandir el sidebar
const toggleCollapse = document.getElementById('toggleCollapse');
const sidebar = document.getElementById('sidebar');
const headerTexts = document.getElementById('sidebarHeaderTexts');

if (toggleCollapse && sidebar) {
    toggleCollapse.addEventListener('click', function() {
        // Alternar el ancho del sidebar entre expandido (w-64) y contraído (w-20)
        sidebar.classList.toggle('w-64');
        sidebar.classList.toggle('w-20');

        // Ocultar o mostrar los textos de la cabecera
        if (headerTexts) {
            headerTexts.classList.toggle('hidden');
        }

        // Ocultar o mostrar los textos y flechas dentro de la navegación
        // Esto busca todos los span (nombres de archivos) y las flechas de carpetas
        sidebar.querySelectorAll('nav span, .folder-arrow').forEach(element => {
            element.classList.toggle('hidden');
        });

        // Opcional: Centrar los iconos cuando esté contraído
        sidebar.querySelectorAll('nav a, .folder-toggle').forEach(item => {
            item.classList.toggle('justify-center');
        });
    });
}
// Agregar al final de tu script.js
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('.zoomable');
    
    images.forEach(img => {
        img.addEventListener('click', () => {
            // Crear modal de zoom
            const overlay = document.createElement('div');
            overlay.className = 'fixed inset-0 bg-white/95 z-[100] flex items-center justify-center cursor-zoom-out animate-in fade-in duration-300';
            
            const fullImg = document.createElement('img');
            fullImg.src = img.src;
            fullImg.className = 'max-w-[95%] max-h-[95%] shadow-2xl rounded-sm';
            
            overlay.appendChild(fullImg);
            document.body.appendChild(overlay);
            
            overlay.onclick = () => {
                overlay.classList.add('fade-out');
                setTimeout(() => overlay.remove(), 300);
            };
        });
    });
});