<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;
use Illuminate\Database\Eloquent\Collection;

class PublicCollection extends Collection
{
    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArrayPublic()
    {
        $array['entity'] = 'collection';
        $array['count'] = count($this->items);

        $array['data'] = array_map(function($value)
        {
            return $value->toArrayPublic();

        }, $this->items);

        return $array;
    }
}