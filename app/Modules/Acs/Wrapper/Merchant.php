<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Merchant\Entity as MerchantEntity;

class Merchant extends Base
{
    public $saveApiHelper;

    function __construct()
    {
        parent::__construct();
        $this->saveApiHelper = new SaveApiHelper();
    }

    /**
     * @param MerchantEntity $entity
     * @throws \Google\ApiCore\ValidationException
     * @throws \RZP\Exception\IntegrationException
     */
    function SaveOrFail(MerchantEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }

    function FindOrFail(string $id)
    {
        //TODO: Call the Account Fetch Api Of Account Service and return response
    }
}
