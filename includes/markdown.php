<?php
/**
 * Clase para procesar archivos Markdown y convertirlos a HTML
 * Implementación nativa sin dependencias externas
 * se encuentra en includes/markdown.php
 */
class MarkdownParser {
    private $metadata = [];

    public function parse($markdown) {
    // 1. Limpieza inicial: eliminamos espacios en blanco al principio y final
    $markdown = ltrim($markdown);
    $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);

    // 2. Regex ultra-flexible para Front Matter
    // Soporta espacios antes de los guiones y espacios después
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n?(.*)$/s', $markdown, $matches)) {
        $this->parseFrontMatter($matches[1]);
        $body = $matches[2];
    } else {
        $this->metadata = [];
        $body = $markdown;
    }

    return $this->parseBlocks($body);
}

    private function parseFrontMatter($yamlText) {
        $lines = explode("\n", $yamlText);
        $currentKey = '';
        foreach ($lines as $line) {
            if (preg_match('/^([a-zA-Z0-9_]+):\s*(.*)$/', $line, $matches)) {
                $key = strtolower(trim($matches[1]));
                $value = trim($matches[2]);
                if ($key === 'tags') {
                    $this->metadata['tags'] = [];
                    $currentKey = 'tags';
                } else {
                    $this->metadata[$key] = $value;
                    $currentKey = '';
                }
            } elseif ($currentKey === 'tags' && preg_match('/^\s*-\s*(.*)$/', $line, $matches)) {
                $this->metadata['tags'][] = trim($matches[1]);
            }
        }
    }

    public function getMetadata() {
        return $this->metadata;
    }
    /**
     * Procesa los bloques de Markdown
     */
    private function parseBlocks($markdown) {
        $lines = explode("\n", $markdown);
        $html = '';
        
        $inCodeBlock = false;
        $codeBlockContent = '';
        $codeLanguage = '';
        $inList = false;
        $listType = '';
        $listContent = '';
        $inBlockquote = false;
        $blockquoteContent = '';
        
        $lineCount = count($lines);
        
        for ($i = 0; $i < $lineCount; $i++) {
            $line = $lines[$i];
            $trimmedLine = trim($line);
            
            // Bloques de código
            if (preg_match('/^```(.*)$/', $trimmedLine, $matches)) {
                if (!$inCodeBlock) {
                    $inCodeBlock = true;
                    $codeLanguage = trim($matches[1]);
                    $codeBlockContent = '';
                } else {
                    $html .= $this->renderCodeBlock($codeBlockContent, $codeLanguage);
                    $inCodeBlock = false;
                }
                continue;
            }
            if ($inCodeBlock) {
                $codeBlockContent .= $line . "\n";
                continue;
            }
            
            // Líneas horizontales
            if (preg_match('/^([-*_])\s*\1\s*\1(\s*\1)*$/', $trimmedLine)) {
                $html .= '<hr>';
                continue;
            }
            
            // Encabezados
            if (preg_match('/^(#{1,6})\s+(.+)$/', $trimmedLine, $matches)) {
                $level = strlen($matches[1]);
                $content = $this->parseInline($matches[2]);
                $html .= "<h{$level}>{$content}</h{$level}>";
                continue;
            }
            
            // Blockquotes
            if (preg_match('/^>\s*(.*)$/', $trimmedLine, $matches)) {
                if (!$inBlockquote) { $inBlockquote = true; $blockquoteContent = ''; }
                $blockquoteContent .= $matches[1] . "\n";
                continue;
            } else if ($inBlockquote && $trimmedLine === '') {
                $html .= $this->renderBlockquote($blockquoteContent);
                $inBlockquote = false;
                continue;
            }
            
            // Listas
            if (preg_match('/^(\s*)([-*+]|\d+\.)\s+(.+)$/', $trimmedLine, $matches)) {
                $newListType = (is_numeric(substr($matches[2], 0, 1))) ? 'ol' : 'ul';
                if (!$inList || $listType !== $newListType) {
                    if ($inList) $html .= $this->renderList($listContent, $listType);
                    $inList = true;
                    $listType = $newListType;
                    $listContent = '';
                }
                $listContent .= $line . "\n";
                continue;
            } else if ($inList && $trimmedLine === '') {
                $html .= $this->renderList($listContent, $listType);
                $inList = false;
                continue;
            }
            
            // Párrafos y Tablas
            if ($trimmedLine !== '') {
                // Tablas
                if (strpos($trimmedLine, '|') !== false && $i + 1 < $lineCount && preg_match('/^\|?\s*[-:]+[-|\s:]*\|?$/', trim($lines[$i + 1]))) {
                    $tableContent = $trimmedLine . "\n" . $lines[$i + 1];
                    $i += 2;
                    while ($i < $lineCount && strpos(trim($lines[$i]), '|') !== false) {
                        $tableContent .= "\n" . $lines[$i]; $i++;
                    }
                    $i--;
                    $html .= $this->renderTable($tableContent);
                    continue;
                }
                
                $content = $trimmedLine;
                while ($i + 1 < $lineCount && trim($lines[$i + 1]) !== '') {
                    $i++; $content .= "\n" . $lines[$i];
                }
                $html .= '<p>' . $this->parseInline($content) . '</p>';
            }
        }
        
        if ($inList) $html .= $this->renderList($listContent, $listType);
        if ($inBlockquote) $html .= $this->renderBlockquote($blockquoteContent);
        if ($inCodeBlock) $html .= $this->renderCodeBlock($codeBlockContent, $codeLanguage);
        
        return $html;
    }
    
    /**
     * Procesa elementos inline (Negritas, Imágenes, Enlaces)
     */
    private function parseInline($text) {
        // 1. Procesar imágenes con sintaxis ![alt|dims](url) - Estilo Obsidian/Avanzado
        $text = preg_replace_callback('/\!\[(.*?)\]\((.*?)\)/', function($matches) {
            $altFull = $matches[1];
            $url = $matches[2];
            $alt = $altFull;
            $dims = '';
            
            if (strpos($altFull, '|') !== false) {
                list($alt, $dims) = explode('|', $altFull, 2);
            }
            return $this->buildImageHtml($url, $alt, $dims);
        }, $text);

        // 2. Procesar imágenes con sintaxis directa ![url|dims]
        $text = preg_replace_callback('/\!\[(https?:\/\/[^|\]\s]+)(?:\|([^\]]+))?\]/', function($matches) {
            $url = $matches[1];
            $dims = isset($matches[2]) ? $matches[2] : '';
            return $this->buildImageHtml($url, '', $dims);
        }, $text);

        // 3. Otros elementos inline
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        
        // Enlaces (solo si no fueron procesados como imágenes antes)
        $text = preg_replace_callback('/(?<!\!)\[([^\]]+)\]\(([^)]+)\)/', function($matches) {
            return '<a href="' . htmlspecialchars($matches[2]) . '">' . $matches[1] . '</a>';
        }, $text);

        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
        
        return $text;
    }

    /**
     * Genera el HTML de la imagen con soporte para dimensiones y zoom
     */
    /**
     * Genera el HTML de la imagen o video con soporte para dimensiones y zoom
     */
    private function buildImageHtml($url, $alt, $dims) {
        // 1. DETECTAR SI ES UN VIDEO DE YOUTUBE
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            $videoId = '';
            
            // Extraer ID si es formato embed
            if (preg_match('/embed\/([^?&]+)/', $url, $matches)) {
                $videoId = $matches[1];
            } 
            // Extraer ID si es formato normal (watch?v=...)
            elseif (preg_match('/v=([^?&]+)/', $url, $matches)) {
                $videoId = $matches[1];
            }
            // Extraer ID si es formato corto (youtu.be/...)
            elseif (preg_match('/youtu\.be\/([^?&]+)/', $url, $matches)) {
                $videoId = $matches[1];
            }

            if ($videoId) {
                return '<div class="my-8 w-full max-w-4xl mx-auto">
                            <div class="aspect-video rounded-xl overflow-hidden shadow-lg border border-gray-100">
                                <iframe class="w-full h-full" 
                                        src="https://www.youtube.com/embed/' . $videoId . '" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen>
                                </iframe>
                            </div>
                            ' . ($alt ? '<span class="text-sm text-gray-500 mt-3 block text-center italic">' . htmlspecialchars($alt) . '</span>' : '') . '
                        </div>';
            }
        }

        // 2. SI NO ES VIDEO, PROCESAR COMO IMAGEN NORMAL (lo que ya tenías)
        $widthAttr = "";
        $heightAttr = "";
        
        if (!empty($dims)) {
            if (strpos($dims, 'x') !== false) {
                list($w, $h) = explode('x', $dims);
                $widthAttr = ' width="' . (int)$w . '"';
                $heightAttr = ' height="' . (int)$h . '"';
            } else {
                $widthAttr = ' width="' . (int)$dims . '"';
            }
        }

        return '<div class="md-image-container my-8 flex flex-col items-center">
                    <div class="relative group inline-block">
                        <img src="' . htmlspecialchars($url) . '" 
                             alt="' . htmlspecialchars($alt) . '"' . 
                             $widthAttr . $heightAttr . ' 
                             class="zoomable-image rounded-lg shadow-md cursor-zoom-in transition-transform duration-300 hover:scale-[1.01]">
                        <div class="zoom-overlay absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
                            <div class="bg-black/20 p-2 rounded-full backdrop-blur-sm">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path></svg>
                            </div>
                        </div>
                    </div>
                    ' . ($alt ? '<span class="text-sm text-gray-500 mt-2 italic">' . htmlspecialchars($alt) . '</span>' : '') . '
                </div>';
    }

    private function renderCodeBlock($content, $language) {
        $class = $language ? ' class="language-' . htmlspecialchars($language) . '"' : '';
        return '<pre><code' . $class . '>' . htmlspecialchars(trim($content)) . '</code></pre>';
    }

    private function renderList($content, $type) {
        $lines = explode("\n", trim($content));
        $html = "<$type>\n";
        foreach ($lines as $line) {
            if (preg_match('/^(\s*)([-*+]|\d+\.)\s+(.+)$/', $line, $matches)) {
                $html .= "<li>" . $this->parseInline($matches[3]) . "</li>\n";
            }
        }
        $html .= "</$type>\n";
        return $html;
    }

    private function renderBlockquote($content) {
        return "<blockquote>" . $this->parseInline(trim($content)) . "</blockquote>";
    }

    private function renderTable($content) {
        $lines = explode("\n", trim($content));
        $html = "<table><thead><tr>";
        $headers = array_shift($lines);
        foreach ($this->parseTableRow($headers) as $cell) {
            $html .= "<th>" . $this->parseInline($cell) . "</th>";
        }
        $html .= "</tr></thead><tbody>";
        array_shift($lines); // saltar separador
        foreach ($lines as $line) {
            $html .= "<tr>";
            foreach ($this->parseTableRow($line) as $cell) {
                $html .= "<td>" . $this->parseInline($cell) . "</td>";
            }
            $html .= "</tr>";
        }
        return $html . "</tbody></table>";
    }

    private function parseTableRow($row) {
        return array_filter(array_map('trim', explode('|', trim($row, '|'))));
    }

    public function getReadingTime($text) {
        $wordCount = str_word_count(strip_tags($text));
        return max(1, ceil($wordCount / 200));
    }

    public function getTitle($markdown) {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) return $matches[1];
        return 'Sin título';
    }
} // <--- AQUÍ TERMINA LA CLASE