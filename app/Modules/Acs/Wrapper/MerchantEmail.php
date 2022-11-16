<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

class MerchantEmail extends Base
{
    protected $accountAsvClient;

    function __construct()
    {
        parent::__construct();
        $this->accountAsvClient = new AsvClient\AccountAsvClient();

    }

    /**
     * @param MerchantEmailEntity $entity
     * @throws \RZP\Exception\IntegrationException
     */
    public function Delete(MerchantEmailEntity $entity)
    {
        $this->accountAsvClient->DeleteAccountContact($entity['id'], $entity['merchant_id'], $entity['type']);
    }

}


