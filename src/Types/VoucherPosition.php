<?php

namespace Frknakk\Internetmarke\Types;

class VoucherPosition
{
    protected $x = 1;
    protected $y = 1;
    protected $page = 1;

    public function x($val) {
        $this->x = $val;
        return $this;
    }

    public function y($val) {
        $this->y = $val;
        return $this;
    }

    public function page($val) {
        $this->page = $val;
        return $this;
    }

    public function toArray()
    {
        return [
            'labelX' => $this->x,
            'labelY' => $this->y,
            'page' => $this->page
        ];
    }

    public static function create()
    {
        return new static;
    }
}
