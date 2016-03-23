<?php

namespace Models\Customer\App;

use EE\Exception;
use EE\Error\ErrorCode;
use Models\Base;
use Models\Customer\App;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'CustomerApps';

    public function findByAppAndMerchant($appId, $merchantId)
    {
        $repo = $this->repo;

        return $repo::where(App\Entity::APP_ID, '=', $appId)
                    ->where(App\Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }
}
