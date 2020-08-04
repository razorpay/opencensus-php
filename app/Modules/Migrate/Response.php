<?php

namespace RZP\Modules\Migrate;

class Response extends Record
{
    const ACTION_CREATED  = 'created';
    const ACTION_UPDATED  = 'updated';
    const ACTION_UPSERTED = 'upserted';

    /** @var string */
    public $action;

    public function __construct(string $action, string $key, $value)
    {
        assertTrue(defined(__CLASS__.'::'.'ACTION_'.strtoupper($action)));

        $this->action = $action;

        parent::__construct($key, $value);
    }
}
