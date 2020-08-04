<?php

namespace RZP\Modules\Migrate;

class Record
{
    /** @var string */
    public $key;

    /** @var mixed */
    public $value;

    public function __construct(string $key, $value)
    {
        $this->key   = $key;
        $this->value = $value;
    }
}
