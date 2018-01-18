<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant;
use RZP\Constants\Entity as E;

class EsRepository extends Merchant\EsRepository
{
    /**
     * Overridden: Usage merchant index only for now.
     *
     * @return string
     */
    public function getIndexSuffix(): string
    {
        return E::MERCHANT . '_' . $this->mode;
    }
}
