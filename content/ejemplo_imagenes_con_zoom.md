---
title: Ejemplo
date: 2026-03-14
image: ![](hhttps://res.cloudinary.com/dvazd4c0t/image/upload/v1773603145/samaltman_f4pa8s.png)
description: Esto es un ejemplo
tags:
  - EJEMPLO
---
Este archivo demuestra la nueva funcionalidad de imágenes con zoom estilo Medium.

## Imágenes Normales (sin dimensiones específicas)
![](https://ricardoloria.com/cotidianeidades/attachments/ayabrije.png)

Esπta imagen se mostrará en su tamaño natural, con funcionalidad de zpoom si es más grande que el contenedor.


## Imágenes con Ancho Específico

![|600](https://ricardoloria.com/cotidianeidades/attachments/ayabrije.png)

Esta imagen tendrá un ancho de 600 píxeles y alto automático proporcional.

## Imágenes con Dimensiones Específicas

![|500x300](https://ricardoloria.com/cotidianeidades/attachments/ayabrije.png)

Esta imagen tendrá exactamente 500 píxeles de ancho por 300 de alto.

## Imagen Pequeña (sin zoom)

![https://picsum.photos/400/300?random=4|300]

Esta imagen es pequeña, por lo que no debería mostrar el icono de zoom.

## Imagen Grande con Zoom

![https://picsum.photos/1920/1080?random=5|800x450]

Esta imagen es grande y debería mostrar el icono de zoom al pasar el cursor por encima.

---

### Instrucciones de Uso

- **Sintaxis básica**: ! [https://example.com/image.jpg]
- **Con ancho específico**:  ```[https://example.com/image.jpg|600]```
- **Con dimensiones específicas**:
```
 ! [https://example.com/image.jpg|600x400]
```

### Funcionalidades

1. **Hover**: Al pasar el cursor sobre imágenes grandes, aparece un icono de lupa
2. **Zoom**: Al hacer click en la imagen o la lupa, se abre en un modal ampliado
3. **Cerrar**: Click fuera de la imagen, botón X, tecla Escape, o click en la imagen ampliada
4. **Responsive**: Se adapta automáticamente a dispositivos móviles
