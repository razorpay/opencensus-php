<?php

namespace RZP\Models\Device;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'device';

    public function findByVerificationToken(string $verificationToken)
    {
        return $this->newQuery()
                    ->where(Entity::VERIFICATION_TOKEN, '=', $verificationToken)
                    ->firstOrFail();
    }

    public function findByAuthToken(string $authToken)
    {
        return $this->newQuery()
                    ->where(Entity::AUTH_TOKEN, '=', $authToken)
                    ->first();
    }
}
