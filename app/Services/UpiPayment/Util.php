<?php

namespace RZP\Services\UpiPayment;

use RZP\Constants\Entity;
use RZP\Models\Base\PublicEntity;

class Util
{
    /**
     * converts the input object array to array
     *
     * @param array $input
     * @return void
     */
    public static function convertInputToArray(array &$input)
    {
        if (empty($input[Entity::TERMINAL]) === false)
        {
            $input[Entity::TERMINAL] = $input[Entity::TERMINAL]->toArrayWithPassword();
        }

        foreach ($input as $key => $data)
        {
            if ((is_object($data) === true) and ($data instanceof PublicEntity))
            {
                $input[$key] = $data->toArray();
            }
        }
    }
}
