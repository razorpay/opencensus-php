<?php

namespace RZP\Models\Payment\Validation;

class Factory
{
    public static function build(string $entity): Base
    {
        $processor = __NAMESPACE__ . '\\' . studly_case($entity);

        return new $processor();
    }
}
