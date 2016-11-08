<?php

namespace RZP\Models\Admin\Org\PasswordPolicy\Rules;

use RZP\Exception;

class TypeRule extends Rule
{
    protected $type;

    protected $types = [
        'alpha_numeric_underscore' => '/^(?=.*[0-9])(?=.*[a-zA-Z_])([a-zA-Z0-9_]+)$/',
        'alpha_numeric'            => '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
        'numeric'                  => '0-9',
        'alpha'                    => 'a-zA-Z',
    ];

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function validate(string $password)
    {
        $pattern = $this->types[$this->type];

        $matchingCharacters = preg_replace($pattern, '', $password);

        if (strlen($matchingCharacters) < 1)
        {
            throw new Exception\BadRequestException(
                );
        }
    }
}