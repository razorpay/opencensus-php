<?php

namespace RZP\Models\Merchant\Balance\LowBalanceConfig;

use RZP\Models\Base;
use RZP\Models\Base\Traits\ServiceHasCrudMethods;

class Service extends Base\Service
{
    use ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->low_balance_config;
    }

    public function fetchMultiple(array $input): array
    {
        if (isset($input[Entity::ACCOUNT_NUMBER]) === true)
        {
            Validator::validateAndTranslateAccountNumberForBanking($input, $this->merchant);
        }

        $entities = $this->entityRepo->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function disableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->disableConfig($entity);

        return $response->toArrayPublic();
    }

    public function enableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->repo->low_balance_config->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->enableConfig($entity);

        return $response->toArrayPublic();
    }
}
