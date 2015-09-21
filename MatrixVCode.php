<?php
class MatrixVCode {
    var $_char_matrix = array(
        'c-0' => array('01110','10001','10011','10101','11001',
            '10001','10001','01110'),
        'c-1' => array('00100','01100','00100','00100','00100',
            '00100','00100','01110'),
        'c-2' => array('01110','10001','10001','00010','00100',
            '01000','10000','11111'),
        'c-3' => array('01110','10001','00001','00110','00001',
            '00001','10001','01110'),
        'c-4' => array('00001','00011','00101','01001','10001',
            '11111','00001','00001'),
        'c-5' => array('11111','10000','10000','11110','00001',
            '00001','10001','01110'),
        'c-6' => array('01110','10000','10000','11110','10001',
            '10001','10001','01110'),
        'c-7' => array('11111','00001','00001','00010','00010',
            '00100','00100','00100'),
        'c-8' => array('01110','10001','10001','01110','10001',
            '10001','10001','01110'),
        'c-9' => array('01110','10001','10001','10001','01111',
            '00001','00001','01110')
    );
    var $_chars = '0123456789';
    
    var $_rand_len;
    
    function _randChars() {
        $rand_s = '';
        $this->_rand_len = mt_rand(4, 9);
        for ($i = 0; $i < $this->_rand_len; $i++) {
            $char_idx = mt_rand(0, strlen($this->_chars) - 1);
            $rand_s .= substr($this->_chars, $char_idx, 1);
        }
        return $rand_s;
    }
    
    function _getCharLine($char, $line) {
        $char_key = 'c-'.$char;
        if (!isset($this->_char_matrix[$char_key])) {
            return str_repeat('0', 7);
        } else {
            return '0'.$this->_char_matrix[$char_key][$line - 1].'0';
        }
    }
    
    function _formEmptyLine() {
        $line_len = 7 * $this->_rand_len;
        return str_repeat('0', $line_len);
    }
    
    function _formLine($rand_s, $line) {
        for ($i = 0, $len = strlen($rand_s); $i < $len; $i++) {
            $line_s .= $this->_getCharLine(substr($rand_s, $i, 1), $line);
        }
        return $line_s;
    }
    
    function &_genBinaryMatrix($rand_s) {
        $lines[] = $this->_formEmptyLine();
        for ($i = 1; $i <= 8; $i++) {
            $lines[] = $this->_formLine($rand_s, $i);
        }
        $lines[] = $this->_formEmptyLine();
        return $lines;
    }
    
    function render() {
        $rand_s = $this->_randChars();
        $lines =& $this->_genBinaryMatrix($rand_s);
        
        $html = '<table id="MVCode" cellspacing="0">'."\n";
        for ($i = 0, $size = count($lines); $i < $size; $i++) {
            $html .= '<tr>';
            for ($j = 0, $len = strlen($lines[$i]); $j < $len; $j++) {
                if (mt_rand(0, 15) % 16 == 0) {
                    $html .= '<td class="d"></td>';
                } else {
                    if (substr($lines[$i], $j, 1) == '0') {
                        $html .= '<td></td>';
                    } else {
                        $html .= '<td class="d"></td>';
                    }
                }
            }
            $html .= '</tr>'."\n";
        }
        $html .= '</table>'."\n";
        
        echo $html;
        
        return $rand_s;
    }
}
?>
