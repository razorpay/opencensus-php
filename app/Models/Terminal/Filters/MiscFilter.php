<?php

namespace RZP\Models\Terminal\Filters;

use RZP\Models\Terminal;

class MiscFilter extends Terminal\Filter
{
    protected $properties = [
        'exclude',
    ];

    /**
     * Filter to exclude certain terminals from the $input array
     * @param Terminal\Entity $terminal
     * @param array $input
     * @return bool
     */
    public function excludeFilter(Terminal\Entity $terminal, array $input)
    {
        if (isset($input['exclude']))
        {
            if (in_array($terminal->getId(), $input['exclude']) === true)
            {
                return false;
            }

            return true;
        }

        return true;
    }
}
