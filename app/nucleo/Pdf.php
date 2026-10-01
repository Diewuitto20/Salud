<?php
defined('RAIZ') or exit;

/*
 * Generador mínimo de PDF sin librerías externas: páginas A4, Helvetica normal y negrita,
 * texto con acentos (Windows-1252), rectángulos y líneas. Las coordenadas van desde arriba.
 */
class Pdf
{
    public const ANCHO = 595.28;
    public const ALTO = 841.89;
    private array $paginas = [];

    public function pagina(): void
    {
        $this->paginas[] = '';
    }

    private function dibujar(string $orden): void
    {
        if (!$this->paginas) $this->pagina();
        $this->paginas[count($this->paginas) - 1] .= $orden . "\n";
    }

    public function texto(float $x, float $y, string $texto, float $tam = 11, bool $negrita = false, string $color = '22393D'): void
    {
        $this->dibujar(sprintf('BT %s rg /%s %.2F Tf %.2F %.2F Td (%s) Tj ET',
            $this->rgb($color), $negrita ? 'F2' : 'F1', $tam, $x, self::ALTO - $y, $this->escapar($texto)));
    }

    /* Texto alineado a la derecha o al centro de un ancho dado */
    public function textoAlineado(float $x, float $y, float $ancho, string $texto, float $tam, bool $negrita, string $color, string $alinear = 'centro'): void
    {
        $w = $this->ancho($texto, $tam, $negrita);
        $this->texto($alinear === 'derecha' ? $x + $ancho - $w : $x + ($ancho - $w) / 2, $y, $texto, $tam, $negrita, $color);
    }

    /* Escribe un párrafo con salto de línea automático y devuelve la «y» siguiente */
    public function parrafo(float $x, float $y, float $ancho, string $texto, float $tam = 10, string $color = '55696C', bool $negrita = false): float
    {
        $linea = '';
        foreach (explode(' ', $texto) as $palabra) {
            $prueba = $linea === '' ? $palabra : "$linea $palabra";
            if ($linea !== '' && $this->ancho($prueba, $tam, $negrita) > $ancho) {
                $this->texto($x, $y, $linea, $tam, $negrita, $color);
                $y += $tam * 1.45;
                $linea = $palabra;
            } else {
                $linea = $prueba;
            }
        }
        if ($linea !== '') {
            $this->texto($x, $y, $linea, $tam, $negrita, $color);
            $y += $tam * 1.45;
        }
        return $y;
    }

    public function rect(float $x, float $y, float $w, float $h, string $color): void
    {
        $this->dibujar(sprintf('%s rg %.2F %.2F %.2F %.2F re f', $this->rgb($color), $x, self::ALTO - $y - $h, $w, $h));
    }

    public function linea(float $x1, float $y1, float $x2, float $y2, string $color = 'EFE5D7', float $grosor = 1): void
    {
        $this->dibujar(sprintf('%s RG %.2F w %.2F %.2F m %.2F %.2F l S', $this->rgb($color), $grosor, $x1, self::ALTO - $y1, $x2, self::ALTO - $y2));
    }

    /* Ancho aproximado en puntos con las proporciones de Helvetica */
    public function ancho(string $texto, float $tam, bool $negrita = false): float
    {
        $suma = 0;
        foreach (mb_str_split($texto) as $c) {
            $suma += match (true) {
                $c === ' ' => 0.278,
                str_contains('iljI.,:;\'|!·', $c) => 0.26,
                str_contains('ftr()-', $c) => 0.36,
                str_contains('mwMW%', $c) => 0.85,
                ctype_digit($c) => 0.556,
                $c !== mb_strtolower($c) => 0.69,
                default => 0.53,
            };
        }
        return $suma * $tam * ($negrita ? 1.06 : 1);
    }

    public function salida(): string
    {
        if (!$this->paginas) $this->pagina();
        $objetos = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $hijos = [];
        $n = 5;
        foreach ($this->paginas as $contenido) {
            $hijos[] = "$n 0 R";
            $objetos[$n] = sprintf('<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::ANCHO, self::ALTO, $n + 1);
            $objetos[$n + 1] = '<< /Length ' . strlen($contenido) . " >>\nstream\n" . $contenido . "endstream";
            $n += 2;
        }
        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $hijos) . '] /Count ' . count($hijos) . ' >>';
        ksort($objetos);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $posiciones = [];
        foreach ($objetos as $id => $cuerpo) {
            $posiciones[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$cuerpo\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . ($n) . "\n0000000000 65535 f \n";
        for ($i = 1; $i < $n; $i++) $pdf .= sprintf("%010d 00000 n \n", $posiciones[$i]);
        return $pdf . "trailer\n<< /Size $n /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
    }

    private function rgb(string $hex): string
    {
        [$r, $g, $b] = array_map(fn($p) => hexdec($p) / 255, str_split($hex, 2));
        return sprintf('%.3F %.3F %.3F', $r, $g, $b);
    }

    private function escapar(string $texto): string
    {
        $texto = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto) ?: $texto;
        return strtr($texto, ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
    }
}
