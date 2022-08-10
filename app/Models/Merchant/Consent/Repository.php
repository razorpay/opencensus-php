<?php


namespace RZP\Models\Merchant\Consent;

use RZP\Base\ConnectionType;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive
    {
        saveOrFail as saveOrFailTestAndLive;
    }

    protected $entity = 'merchant_consents';

}
