<?php

namespace RZP\Models\P2p\Client;

use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Exception\BadRequestValidationFailureException;

class ArrayAttribute extends ArrayBag
{
    protected $map;

    public function isValid(string $key): bool
    {
        return isset($this->map[$key]);
    }

    public function validate(array $input): self
    {
        foreach ($input as $key => $value)
        {
            if ($this->isValid($key) === false)
            {
                $message = sprintf('Invalid key for %s:%s', get_class($this), $key);

                throw new BadRequestValidationFailureException($message, $key);
            }
        }

        return $this;
    }

    public function merge($values): self
    {
        $merged = parent::merge($values);

        $this->validate($merged->toArray());

        return $merged;
    }

    /**
     * @param string $json
     * @return static
     */
    public static function fromJson($json)
    {
        if (isset($json) === false)
        {
            return new static();
        }

        return new static(json_decode($json, true));
    }
}
