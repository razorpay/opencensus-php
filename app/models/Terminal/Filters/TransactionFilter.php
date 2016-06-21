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

    public function methodFilter($terminal, $input)
    {
        '';
    }

    public function internationalFilter($terminal, $input)
    {

    }

    public function bankFilter($terminal, $input)
    {

    }
}
