<?php

namespace RZP\Models\Gateway\Downtime;

use RZP\Exception\LogicException;
use RZP\Models\Payment\Method;

class InputFormatter
{
    public static function editFormat(array $input, Entity $downWindow)
    {
        $input[Entity::METHOD] = $downWindow->getMethod();

        return self::format($input);
    }

}