<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Address\Entity as AddressEntity;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;

class MerchantStakeholder extends Base
{
    public $saveApiHelper;

    function __construct()
    {
        parent::__construct();
        $this->saveApiHelper = new SaveApiHelper();
    }

    /**
     * @param MerchantStakeholderEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantStakeholderEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    /**
     * @param MerchantStakeholderEntity $stakeholderEntity
     * @param AddressEntity $addressEntity
     * @throws \Google\ApiCore\ValidationException
     * @throws \RZP\Exception\IntegrationException
     */
    public function SaveOrFailAddress(MerchantStakeholderEntity $stakeholderEntity, AddressEntity $addressEntity)
    {
        $entityArray = $stakeholderEntity->toArray();
        $addressEntityArray = $addressEntity->toArray();
        $entityArray['addresses']['residential'] = $addressEntityArray;
        $this->saveApiHelper->saveOrFail($stakeholderEntity->getMerchantId(), $stakeholderEntity->getEntityName(), $addressEntity->getEntityName(), $entityArray);
    }
}

