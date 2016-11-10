<?php

namespace RZP\Models\Admin\Org\AuthPolicy\Rules;

use RZP\Exception;

class TypeRule extends Base
{
    protected $type;

    protected $types = [
        'alpha_numeric_underscore' => '/^(?=.*[0-9])(?=.*[a-zA-Z_])([a-zA-Z0-9_]+)$/',
        'alpha_numeric'            => '/^(?=.*[0-9])(?=.*[a-zA-Z])([a-zA-Z0-9]+)$/',
        'numeric'                  => '0-9',
        'alpha'                    => 'a-zA-Z',
    ];

    protected $description = [
        'alpha_numeric_underscore' => 'alphabets, digits and underscore(_)',
        'alpha_numeric'            => 'alphabets and digits',
        'numeric'                  => 'alphabets',
        'alpha'                    => 'digits',
    ];

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function validate($admin, $password)
    {
        $type = $this->type;

        $pattern = $this->types[$type];

        $matchingCharacters = preg_replace($pattern, '', $password);

        if (strlen($matchingCharacters) < 1)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Password can contain ' . $this->description[$type] . ' only');
        }
    }
}