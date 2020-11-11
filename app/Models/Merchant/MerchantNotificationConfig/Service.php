<?php

namespace RZP\Models\Merchant\MerchantNotificationConfig;

use RZP\Models\Base;
use RZP\Models\Base\Traits\ServiceHasCrudMethods;

class Service extends Base\Service
{
    use ServiceHasCrudMethods;

    /**
     * @var Core
     */
    protected $core;
    protected $entityRepo;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();

        $this->entityRepo = $this->repo->merchant_notification_config;
    }

    public function fetchMultiple(array $input): array
    {
        $entities = $this->entityRepo->fetch($input, $this->merchant->getId());

        return $entities->toArrayPublic();
    }

    public function disableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->disableConfig($entity);

        return $response->toArrayPublic();
    }

    public function enableConfig(string $id)
    {
        /** @var  $entity Entity */
        $entity = $this->entityRepo->findByPublicIdAndMerchant($id, $this->merchant);

        $response = $this->core->enableConfig($entity);

        return $response->toArrayPublic();
    }
}
