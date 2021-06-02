<?php

namespace RZP\Models\QrCode\NonVirtualAccountQrCode;

use RZP\Models\QrCode;
use RZP\Models\Base\PublicEntity;

class Repository extends QrCode\Repository
{
    public function isEsSyncNeeded(string $action, array $dirty = null, PublicEntity $qrCode = null): bool
    {
        if ($qrCode->source !== null)
        {
            return false;
        }

        return parent::isEsSyncNeeded($action, $dirty, $qrCode);
    }
}
