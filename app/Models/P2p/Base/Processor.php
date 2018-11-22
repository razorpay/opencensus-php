<?php

namespace RZP\Models\P2p\Base;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Models\P2p\Base\Traits\ApplicationTrait;

/**
 *
 * Class Processor
 * @package RZP\Models\P2p\Base
 */
class Processor
{
    use ApplicationTrait;

    public function __construct()
    {
        $this->bootApplicationTrait();
    }

    protected function initialize(string $action, array $input = [], $validate = false)
    {
        $this->initializeApplicationTrait($action, $input);

        $this->validator = $this->getNewValidator();

        if ($validate === true)
        {
            $this->validator->validateInput($action, $input);
        }
    }

    protected function getNewValidator(): Validator
    {
        $className = str_replace('\Processor', '\Validator', static::class);

        return new $className;
    }
}
