# Lector de Archivos Markdown - Documentación

## Descripción
Este proyecto es un lector de archivos Markdown con PHP y JavaScript basado en Tailwind CSS. Permite navegar por carpetas, visualizar archivos Markdown y acceder a ellos mediante URLs amigables.

## Características
- Diseño responsivo basado en Tailwind CSS
- Navegación por carpetas en el sidebar
- Parsing de archivos Markdown a HTML
- URLs amigables (www.midominio.com/2025/abril/250417/)
- Soporte para todos los elementos de Markdown (encabezados, listas, código, enlaces, imágenes, etc.)
- Tiempo estimado de lectura y fecha de modificación
- Navegación entre archivos (anterior/siguiente)

## Estructura del Proyecto
```
md-reader/
├── .htaccess                # Configuración para URLs amigables
├── index.php                # Punto de entrada principal
├── includes/                # Archivos PHP del sistema
│   ├── functions.php        # Funciones auxiliares
│   ├── layout.php           # Plantilla principal con Tailwind CSS
│   ├── markdown.php         # Parser de Markdown nativo
│   ├── router.php           # Sistema de enrutamiento
│   └── sidebar.php          # Generador del sidebar
└── content/                 # Carpeta para archivos Markdown
    └── 2025/                # Estructura de ejemplo
        └── abril/           # Subcarpeta por mes
            ├── 250417.md    # Archivo de ejemplo
            └── subcarpeta/  # Subcarpeta adicional
                └── ejemplo.md
```

## Requisitos
- Servidor web con PHP
- mod_rewrite habilitado para URLs amigables

## Instalación
1. Sube todos los archivos a un servidor con PHP
2. Asegúrate de que el servidor tenga habilitado mod_rewrite
3. Coloca tus archivos Markdown en la carpeta content/ siguiendo la estructura de carpetas deseada

## Uso
- Accede a la raíz del sitio para ver la página principal
- Navega por las carpetas usando el sidebar
- Accede directamente a archivos mediante URLs como: www.midominio.com/2025/abril/250417/

## Personalización
- Modifica los estilos en layout.php para cambiar la apariencia
- Ajusta el parser de Markdown en markdown.php para personalizar el procesamiento

## Créditos
Desarrollado siguiendo el diseño proporcionado en layout2.html
