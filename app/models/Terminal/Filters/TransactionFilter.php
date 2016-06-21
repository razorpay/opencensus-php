<?php

namespace Models\Terminal\Filters;

use Models\Terminal;

class TransactionFilter extends Terminal\TerminalFilter
{
    protected static $properties = [
        'method',
        'international',
        'bank',
    ];

    public function methodFilter($input, $terminal)
    {
        '';
    }

    public function internationalFilter($input, $terminal)
    {

    }

    public function bankFilter($input, $terminal)
    {

    }
}
