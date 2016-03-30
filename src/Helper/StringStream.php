<?php

namespace Rsf\Helper;

class StringStream {

    private $str, $pos;

    public function __construct($str) {
        $this->str = $str;
        $this->pos = 0;
    }

    public function get($len = 1) {
        $ss = substr($this->str, $this->pos, $len);
        $this->pos += $len;
        if ($this->pos > strlen($this->str)) {
            $ss = $ss;
        }
        return $ss;
    }

    public function unget($len = 1) {
        $this->pos -= $len;
    }

}