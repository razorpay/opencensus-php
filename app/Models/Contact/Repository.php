<?php

namespace RZP\Models\Contact;

use RZP\Models\Base;

/**
 * Class Repository
 *
 * @package RZP\Models\Contact
 */
class Repository extends Base\Repository
{
    protected $entity = 'contact';

    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }
}
