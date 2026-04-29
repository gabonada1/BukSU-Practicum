<?php

namespace App\Support\Reports;

class PlainPdf
{
    public static function render(array $pages, string $title = 'Report'): string
    {
        $objects = [];
        $pageReferences = [];
        $fontObject = 3;
        $nextObject = 4;

        foreach ($pages as $pageLines) {
            $content = self::contentStream($pageLines);
            $contentObject = $nextObject++;
            $pageObject = $nextObject++;

            $objects[$contentObject] = "<< /Length ".strlen($content)." >>\nstream\n".$content."\nendstream";
            $objects[$pageObject] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 {$fontObject} 0 R >> >> /Contents {$contentObject} 0 R >>";
            $pageReferences[] = "{$pageObject} 0 R";
        }

        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [".implode(' ', $pageReferences).'] /Count '.count($pageReferences).' >>';
        $objects[$fontObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

        ksort($objects);

        $pdf = "%PDF-1.4\n%".chr(226).chr(227).chr(207).chr(211)."\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R /Info << /Title (".self::escape($title).") >> >>\nstartxref\n{$xrefOffset}\n%%EOF";
    }

    protected static function contentStream(array $lines): string
    {
        $stream = "BT\n/F1 9 Tf\n14 TL\n36 555 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "T*\n";
            }

            $stream .= '('.self::escape($line).") Tj\n";
        }

        return $stream."ET";
    }

    protected static function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
