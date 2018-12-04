<?php

namespace RZP\Models\FundAccount;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Contact;

/**
 * Class Repository
 *
 * @package RZP\Models\FundAccount
 */
class Repository extends Base\Repository
{
    protected $entity = 'fund_account';

    protected $expands = [
        Entity::ACCOUNT,
    ];
}
