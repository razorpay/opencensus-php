<?php

namespace RZP\Traits;

trait TrimSpace
{
    public function trimSpaces($data)
    {
        if (is_array($data) === true)
        {
            $trimmedArray = [];

            foreach ($data as $key => $value)
            {
                $key = $this->trimSpaces($key);

                $value = $this->trimSpaces($value);

                $trimmedArray[$key] = $value;
            }

            return $trimmedArray;
        }
        else if (is_string($data) === true)
        {
            return trim($data);
        }

        return $data;
    }
}
