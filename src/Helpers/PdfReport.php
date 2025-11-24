<?php
/**
 * Générateur PDF minimaliste (texte)
 */

declare(strict_types=1);

namespace Helpers;

class PdfReport
{
    /** @var string[] */
    private array $lines = [];

    public function addLine(string $line): void
    {
        $this->lines[] = $line;
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
        $content = $this->buildContentStream();

        $objects = [
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj",
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj",
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj",
            sprintf("4 0 obj << /Length %d >> stream\n%s\nendstream\nendobj", strlen($content), $content),
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xrefPosition = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function buildContentStream(): string
    {
        $lineHeight = 14;
        $startY = 800;
        $currentY = $startY;
        $content = "BT\n/F1 11 Tf\n";

        foreach ($this->lines as $line) {
            if ($currentY < 50) {
                break;
            }

            $content .= sprintf("1 0 0 1 40 %.2f Tm (%s) Tj\n", $currentY, $this->escapeText($line));
            $currentY -= $lineHeight;
        }

        $content .= "ET";
        return $content;
    }

    private function escapeText(string $text): string
    {
        $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );
    }
}

