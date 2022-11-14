<?php

namespace RZP\Modules\Acs\Wrapper;

use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;

class MerchantDocument extends Base
{
    protected $accountDocumentAsvClient;

    function __construct()
    {
        parent::__construct();
        $this->accountDocumentAsvClient = new AsvClient\AccountDocumentAsvClient();
    }

    /**
     * @param MerchantDocumentEntity $entity
     * @throws \RZP\Exception\IntegrationException
     */
    public function DeleteOrFail(MerchantDocumentEntity $entity)
    {
        $this->accountDocumentAsvClient->DeleteAccountDocument($entity['id']);
    }
}
