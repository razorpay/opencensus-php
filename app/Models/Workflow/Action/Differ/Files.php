<?php

namespace RZP\Models\Workflow\Action\Differ;

use RZP\Models\Merchant\Detail;

class Files
{
    const ADDRESS_PROOF_URL = Detail\Entity::ADDRESS_PROOF_URL;

    public static function exists($type)
    {
        return defined(get_class() . '::' . strtoupper($type));
    }
}
