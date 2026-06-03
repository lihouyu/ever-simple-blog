<?php
declare(strict_types=1);

/**
 * MatrixVCode — pixel-matrix CAPTCHA generator.
 *
 * Renders a matrix-based visual verification code as an HTML table.
 * Each digit is drawn on an 8×7 pixel grid with random noise.
 */

class MatrixVCode
{
    private array $char_matrix = [
        'c-0' => ['01110','10001','10011','10101','11001','10001','10001','01110'],
        'c-1' => ['00100','01100','00100','00100','00100','00100','00100','01110'],
        'c-2' => ['01110','10001','10001','00010','00100','01000','10000','11111'],
        'c-3' => ['01110','10001','00001','00110','00001','00001','10001','01110'],
        'c-4' => ['00001','00011','00101','01001','10001','11111','00001','00001'],
        'c-5' => ['11111','10000','10000','11110','00001','00001','10001','01110'],
        'c-6' => ['01110','10000','10000','11110','10001','10001','10001','01110'],
        'c-7' => ['11111','00001','00001','00010','00010','00100','00100','00100'],
        'c-8' => ['01110','10001','10001','01110','10001','10001','10001','01110'],
        'c-9' => ['01110','10001','10001','10001','01111','00001','00001','01110'],
    ];

    private string $chars = '0123456789';
    private int $rand_len = 4;

    private function randChars(): string
    {
        $rand_s = '';
        $this->rand_len = mt_rand(4, 9);
        $max = strlen($this->chars) - 1;
        for ($i = 0; $i < $this->rand_len; $i++) {
            $rand_s .= $this->chars[mt_rand(0, $max)];
        }
        return $rand_s;
    }

    private function getCharLine(string $char, int $line): string
    {
        $char_key = 'c-' . $char;
        if (!isset($this->char_matrix[$char_key])) {
            return str_repeat('0', 7);
        }
        return '0' . $this->char_matrix[$char_key][$line - 1] . '0';
    }

    private function formEmptyLine(): string
    {
        return str_repeat('0', 7 * $this->rand_len);
    }

    private function formLine(string $rand_s, int $line): string
    {
        $line_s = '';
        for ($i = 0, $len = strlen($rand_s); $i < $len; $i++) {
            $line_s .= $this->getCharLine($rand_s[$i], $line);
        }
        return $line_s;
    }

    private function genBinaryMatrix(string $rand_s): array
    {
        $lines = [$this->formEmptyLine()];
        for ($i = 1; $i <= 8; $i++) {
            $lines[] = $this->formLine($rand_s, $i);
        }
        $lines[] = $this->formEmptyLine();
        return $lines;
    }

    /**
     * Render the CAPTCHA as HTML and return the plain-text code.
     */
    public function render(): string
    {
        $rand_s = $this->randChars();
        $lines  = $this->genBinaryMatrix($rand_s);

        $html = '<table id="MVCode" cellspacing="0">' . "\n";
        foreach ($lines as $line) {
            $html .= '<tr>';
            for ($j = 0, $len = strlen($line); $j < $len; $j++) {
                if (mt_rand(0, 15) % 16 === 0) {
                    // Random noise dot
                    $html .= '<td class="d"></td>';
                } elseif ($line[$j] === '0') {
                    $html .= '<td></td>';
                } else {
                    $html .= '<td class="d"></td>';
                }
            }
            $html .= '</tr>' . "\n";
        }
        $html .= '</table>' . "\n";

        echo $html;
        return $rand_s;
    }
}
