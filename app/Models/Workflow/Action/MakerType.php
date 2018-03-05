<?php

namespace RZP\Models\Workflow\Action;

use RZP\Constants\Entity;

class MakerType
{
    const ADMIN    = Entity::ADMIN;
    const MERCHANT = Entity::MERCHANT;

    public static function exists($type)
    {
        return defined(get_class() . '::' . strtoupper($type));
    }
}
