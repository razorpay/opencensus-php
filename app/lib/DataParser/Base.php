<?php

namespace RZP\lib\DataParser;

class Base
{
    protected $input;

    //data parser types
    const TYPEFORM = 'Typeform';

    /**
     * Base constructor.
     *
     * @param array $input
     */
    public function __construct(array $input)
    {
        $this->input = $input;
    }
}
