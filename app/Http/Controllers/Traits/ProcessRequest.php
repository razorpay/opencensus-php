<?php

namespace RZP\Http\Controllers\Traits;

trait ProcessRequest
{
    public function trimSpaces($entity)
    {
        if(is_array($entity))
        {
            return array_map([$this, "trimSpaces"], $entity);
        }
        elseif (is_string($entity))
        {
            return trim($entity);
        }
        return $entity;
    }
}
