<?php
/**
 * Générateur PDF moderne avec mise en page avancée
 */

declare(strict_types=1);

namespace Helpers;

class PdfReport
{
    private array $sections = [];
    private string $title = '';
    private string $subtitle = '';
    private array $metadata = [];
    private array $stats = [];

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    public function addMetadata(string $key, string $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function addStats(array $stats): void
    {
        $this->stats = $stats;
    }

    public function addSection(string $title, array $data, string $type = 'table'): void
    {
        $this->sections[] = [
            'title' => $title,
            'data' => $data,
            'type' => $type,
        ];
    }

    public function output(string $filename): void
    {
        $pdf = $this->buildPdf();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf;
        exit;
    }

    private function buildPdf(): string
    {
        // Construire le contenu
        $content = $this->buildContentStream();
        
        // IDs des objets
        $fontId = 5;
        $fontBoldId = 6;
        $contentId = 4;
        $pageId = 3;
        $pagesId = 2;
        $catalogId = 1;

        // Construire les objets
        $objects = [];
        
        // Fonts
        $objects[$fontId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[$fontBoldId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";
        
        // Content stream
        $objects[$contentId] = "<< /Length " . strlen($content) . " >> stream\n" . $content . "\nendstream";
        
        // Page
        $objects[$pageId] = "<< /Type /Page /Parent " . $pagesId . " 0 R /MediaBox [0 0 595 842] /Contents " . $contentId . " 0 R /Resources << /Font << /F1 " . $fontId . " 0 R /F2 " . $fontBoldId . " 0 R >> >> >>";
        
        // Pages
        $objects[$pagesId] = "<< /Type /Pages /Kids [" . $pageId . " 0 R] /Count 1 >>";
        
        // Catalog
        $objects[$catalogId] = "<< /Type /Catalog /Pages " . $pagesId . " 0 R >>";

        // Build PDF
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        $maxId = max(array_keys($objects));

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        
        for ($i = 1; $i <= $maxId; $i++) {
            $offset = $offsets[$i] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer << /Size " . ($maxId + 1) . " /Root " . $catalogId . " 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function buildContentStream(): string
    {
        $y = 800;
        $margin = 50;
        $pageWidth = 595;
        $content = "q\n";

        // Header avec fond coloré
        $content .= $this->drawRectangle($margin, $y - 80, $pageWidth - 2 * $margin, 70, [0.13, 0.43, 0.99]);
        $content .= $this->drawText($this->title, $margin + 20, $y - 30, 24, 'F2', [1, 1, 1]);
        
        if ($this->subtitle) {
            $content .= $this->drawText($this->subtitle, $margin + 20, $y - 55, 12, 'F1', [1, 1, 1]);
        }

        $y -= 100;

        // Métadonnées
        if (!empty($this->metadata)) {
            $content .= $this->drawText('Informations', $margin, $y, 14, 'F2', [0, 0, 0]);
            $y -= 20;
            
            foreach ($this->metadata as $key => $value) {
                $content .= $this->drawText((string)$key . ': ' . (string)$value, $margin + 10, $y, 10, 'F1', [0.3, 0.3, 0.3]);
                $y -= 15;
            }
            $y -= 10;
        }

        // Statistiques en cartes
        if (!empty($this->stats)) {
            $content .= $this->drawText('Statistiques', $margin, $y, 14, 'F2', [0, 0, 0]);
            $y -= 25;
            
            $cardWidth = ($pageWidth - 2 * $margin - 20) / 3;
            $cardIndex = 0;
            
            foreach ($this->stats as $label => $value) {
                $x = $margin + ($cardIndex * ($cardWidth + 10));
                
                // Carte avec fond
                $content .= $this->drawRectangle($x, $y - 50, $cardWidth, 45, [0.95, 0.95, 0.95]);
                $content .= $this->drawRectangle($x, $y - 50, $cardWidth, 5, [0.13, 0.43, 0.99]);
                
                // Label
                $content .= $this->drawText((string)$label, $x + 10, $y - 25, 9, 'F1', [0.5, 0.5, 0.5]);
                
                // Valeur
                $content .= $this->drawText((string)$value, $x + 10, $y - 10, 16, 'F2', [0, 0, 0]);
                
                $cardIndex++;
                if ($cardIndex >= 3) {
                    $cardIndex = 0;
                    $y -= 70;
                }
            }
            
            if ($cardIndex > 0) {
                $y -= 70;
            }
            $y -= 20;
        }

        // Sections
        foreach ($this->sections as $section) {
            if ($y < 100) {
                break; // Nouvelle page nécessaire
            }

            $content .= $this->drawText($section['title'], $margin, $y, 14, 'F2', [0, 0, 0]);
            $y -= 25;

            if ($section['type'] === 'table' && !empty($section['data'])) {
                // En-tête du tableau
                $headerY = $y;
                $content .= $this->drawRectangle($margin, $y - 15, $pageWidth - 2 * $margin, 20, [0.13, 0.43, 0.99]);
                
                $colWidths = $this->calculateColumnWidths($section['data'][0] ?? []);
                $x = $margin + 5;
                $colIndex = 0;
                
                foreach (array_keys($section['data'][0] ?? []) as $header) {
                    $content .= $this->drawText((string)$header, $x, $y - 5, 9, 'F2', [1, 1, 1]);
                    $x += $colWidths[$colIndex++] ?? 100;
                }
                
                $y -= 30;

                // Lignes du tableau
                $rowIndex = 0;
                foreach (array_slice($section['data'], 0, 30) as $row) {
                    if ($y < 50) break;
                    
                    // Alternance de couleurs
                    if ($rowIndex % 2 === 0) {
                        $content .= $this->drawRectangle($margin, $y - 12, $pageWidth - 2 * $margin, 15, [0.98, 0.98, 0.98]);
                    }
                    
                    $x = $margin + 5;
                    $colIndex = 0;
                    foreach ($row as $cell) {
                        $content .= $this->drawText($this->truncate((string)$cell, 30), $x, $y - 3, 8, 'F1', [0, 0, 0]);
                        $x += $colWidths[$colIndex++] ?? 100;
                    }
                    
                    $y -= 18;
                    $rowIndex++;
                }
                
                $y -= 10;
            }
        }

        // Footer
        $content .= $this->drawText('Généré le ' . date('d/m/Y à H:i') . ' - TimeDesk', $margin, 30, 8, 'F1', [0.5, 0.5, 0.5]);

        $content .= "Q\n";
        return $content;
    }

    private function drawText(string $text, float $x, float $y, float $size, string $font, array $color): string
    {
        $text = $this->escapeText($text);
        $r = $color[0];
        $g = $color[1];
        $b = $color[2];
        $pdfY = 842 - $y;
        return sprintf("%.2f %.2f %.2f rg\nBT\n/%s %.1f Tf\n1 0 0 1 %.2f %.2f Tm\n(%s) Tj\nET\n", $r, $g, $b, $font, $size, $x, $pdfY, $text);
    }

    private function drawRectangle(float $x, float $y, float $width, float $height, array $color): string
    {
        $r = $color[0];
        $g = $color[1];
        $b = $color[2];
        $pdfY = 842 - $y;
        return sprintf("%.2f %.2f %.2f rg\n%.2f %.2f %.2f %.2f re\nf\n", $r, $g, $b, $x, $pdfY - $height, $width, $height);
    }

    private function calculateColumnWidths(array $firstRow): array
    {
        $colCount = count($firstRow);
        $totalWidth = 495; // 595 - 2*50 (marges)
        $baseWidth = $totalWidth / $colCount;
        
        return array_fill(0, $colCount, $baseWidth);
    }

    private function truncate(string $text, int $length): string
    {
        return mb_strlen($text) > $length ? mb_substr($text, 0, $length - 3) . '...' : $text;
    }

    private function escapeText(string $text): string
    {
        // Convertir UTF-8 en ISO-8859-1 (Latin-1) qui supporte bien les caractères français
        // ISO-8859-1 est compatible avec Windows-1252 pour les caractères français courants
        $converted = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        
        // Si la conversion échoue, utiliser une table de remplacement pour les caractères problématiques
        if ($converted === false) {
            // Table de remplacement pour les caractères non supportés
            $replacements = [
                '€' => 'EUR',
                '→' => '->', '←' => '<-', '↑' => '^', '↓' => 'v',
                'œ' => 'oe', 'Œ' => 'OE',
                'æ' => 'ae', 'Æ' => 'AE',
            ];
            
            $text = strtr($text, $replacements);
            $converted = @mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8') ?: $text;
        }
        
        // Échapper les caractères spéciaux PDF
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $converted
        );
    }
}
